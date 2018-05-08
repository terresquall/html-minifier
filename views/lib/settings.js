(function($) {
	"use strict";

	// Activate tooltips.
	$(function() { $(".tooltip").tooltip(); });
	
	// ============================
	// BEGIN CONVENIENT IMPORTS
	// ============================
	
	// Version 1.00.
	// Dated December 2017
	var QuickNotification = Backbone.View.extend({
		
		tagName: 'table',
		id: 'QuickNotification',
		template: _.template('<tr><td><p class="message"></p><a href="javascript:" role="close-btn">&times;</a></td></tr>'),
		
		defaultLifetime: 6442, // Open for 5 seconds, then auto-closes.
		_isOpen: false,
		
		events: {
			'click a[role="close-btn"]': 'close'
		},
		
		render: function() {
			this.el.innerHTML = this.template();
			document.body.appendChild(this.el);
			return this;
		},
		
		initialize: function() {
			_.bindAll(this,'open','close');
			this.render();
			this.$messageContainer = this.$el.find('.message');
		},
		
		open:function(message,lifetime) {
						
			// Set lifetime if it is not set.
			if(typeof lifetime === 'undefined' || lifetime < 0) lifetime = this.defaultLifetime;
			
			// If it is open, close it first.
			if(this._isOpen) {
				this.close();
				setTimeout(this.open,300,message,lifetime);
				return;
			}
			
			// Processes the opening of the dialog.
			this.$messageContainer.html(message);
			this.$el.addClass('open');
			this._openTimeout = setTimeout(this.close,lifetime);
			this._isOpen = true;
		},
		
		close:function() {
			this.$el.removeClass('open');
			this._isOpen = false;
			clearTimeout(this._openTimeout);
		}
	});
	
	// For form subclasses to inherit from.
	// Version 1.22, 7 May 2018
	var FormView = Backbone.View.extend({
		'el': 'form',
		
		'events': {'submit': 'submit'},
		
		'initialize': function(options) {
			
			_.bindAll(this,'submit','submitBtnClick');
			this.options = options; // Save options.
			if(options.model) this.model = options.model;
			
			this._getFormElements();
		},
		
		'isDynamic': false, // If this form is dynamic, we will call _getFormElements() before every submit.
		
		'_getFormElements': function() {
			var $relevantFields = this.$el.find('input, textarea, select, button'); // Get all the fields of the form that are tied to the model.

			// Fields that are read into the model.
			this.$formFields = $relevantFields.filter(':not([readonly],[disabled])').filter('input:not([type="submit"],[type="button"],[type="image"]), textarea, select');
			
			// Get all the submit buttons in the form. Does not update.
			this.$submitBtns = $relevantFields.filter('input, button').not(':not([type="submit"],[type="image"],[type="reset"])');
		},
		
		// Regex for detecting [] at the end of form names.
		'_inputArrayDetectorRegex': /\[([a-z0-9_\-]*)\]$/,
		
		// This function is for buttons that are not assigned as form submit buttons.
		// It creates a submit button for the form and clicks on it (so that native form validation is not bypassed).
		// Note: Should we disable the clicked button for future iterations?
		'submitBtnClick': function(e) {
			
			e.preventDefault();
			
			if(!this.hasOwnProperty("__submitBtn__")) {
				this.__submitBtn__ = document.createElement('input');
				this.__submitBtn__.type = "submit";
				this.__submitBtn__.style.display = "none";
				this.el.appendChild(this.__submitBtn__);
			}
			this.__submitBtn__.click();
			
			return false;
			
		},
		
		// Adds some extra actions to Backbone.save() callback functions.
		'_submitCallback': function(func) {
			var that = this;
			return function(model,response) {
				that.$submitBtns.prop('disabled',false);
				func.call(that,model,response);
			}
		},
		
		'submit': function(e) {
			
			// Don't run this function if there isn't a model set.
			e.preventDefault();
			
			if(!this.model) {
				console.error("A form in FormView was submitted without a Model attached!");
				return false;
			}
			
			if(this.isDynamic) this._getFormElements(); // Gets all the form elements in the form again.
			this.updateModel(); // Update the model with form values.
			
			// Save the model.
			if(typeof this.options.save.callbacks !== 'object') this.options.save.callbacks = {};
			this.$submitBtns.prop('disabled',true);
			this.model.save(this.options.save.attributes, {
				'success': this._submitCallback(this.options.save.callbacks.success),
				'error': this._submitCallback(this.options.save.callbacks.error)
			});
			
			return false;
		},
		
		'updateModel': function() {
			
			var emptiedArrays = [], key;
			
			this.$formFields.each(_.bind(function(i,e) {
				
				// If it is an array attribute, empty it if it hasn't been emptied.
				var match = this._inputArrayDetectorRegex.exec(e.name);
				if(match) {
					key = e.name.substr(0,e.name.length-match[0].length);
					if(emptiedArrays.indexOf(key) < 0) {
						this.model.set(key,null);
						emptiedArrays.push(key);
					}
				}
				
				if(e.nodeName === 'INPUT') {
					
					// If the input object is a checkbox or radio, only record the value if it is checked.
					switch(e.type.toLowerCase()) {
						case "checkbox": case "radio":
							this.setModelData(e.name,e.checked);
							return;
						default:
							if(e.name && e.value) this.setModelData(e.name,e.value);
					}
					
				} else if(e.nodeName.match(/^(TEXTAREA|SELECT)$/i)) {
					
					if(e.name && e.value) this.setModelData(e.name, e.value);
					
				}
				
			},this));
		},
		
		// Get and set function for setting values in the model. Declared so that they can be overriden.
		'setModelData': function(key,value) {
			// If the value is suffixed with [], treat it as an array value.
			var match = this._inputArrayDetectorRegex.exec(key);
			if(match) {
				key = key.substr(0,key.length-match[0].length);
				var data = this.getModelData(key);
				if(data instanceof Array) {
					data.push(value);
					this.model.set(key,data);
				} else this.model.set(key,[value]);
				return;
			}
		
			this.model.set(key,value);
		},
		'getModelData': function(key) { return this.model.get(key); },
		
		// Updates all form fields with reference to model data.
		'updateFields': function() {
			this.$formFields.each(_.bind(function(i,e) {
				var val = this.getModelData(e.name);
				if(val) {
					switch(e.nodeName) {
					case "INPUT":
						if(e.type.match(/^(checkbox|radio)$/i)) e.checked = val ? true : false;
						else e.value = val;
						break;
					case 'SELECT':
						for(var i=0;i<e.options.length;i++) {
							if(e.options[i].value == val) e.selectedIndex = i;
						}
						break;
					default:
						e.value = val;
						break;
					}
				}
			},this));
		},
		
		// Only updates fields in the model that are changed.
		'updateChangedFields': function() {
			var $target, target;
			for(var k in this.model.changed) {
				$target = this.$formFields.filter("#" + k);
				if($target.length > 0) {
					target = $target.get(0);
					switch(target.nodeName) {
					default:
						target.value = this.model.changed[k];
						break;
					case "INPUT":
						if(target.type.match(/^(checkbox|radio)$/i)) target.checked = this.model.changed[k] ? true : false;
						else target.value = this.model.changed[k];
					}
				}
			}
		}
	});
	
	// ===========================
	// BEGIN BUSINESS LOGIC CLASSES
	// ===========================
	
	// Save settings for Backbone.save().
	var SettingsSaveOptions = {
		'attributes': null,
		'callbacks': {
			success: function(model,response) {
				quickNotification.open("Your settings have been saved.");
			},
			error: function(model,response) {
				var msgStr = "There has been an error and your settings have <b>NOT</b> been saved. Please try again.";
				if(typeof response.responseText === 'string') msgStr += "<br/><pre>" + response.responseText + "</pre>"; 
				quickNotification.open(msgStr);
			}
		}
	};
	
	// Controls the navigation tab and initializes the other views, storing references to each of them.
	var SettingsView = Backbone.View.extend({
		'el': '#SettingsView',
		
		'initialize': function() {
			
			// Store nav tab objects.
			this.$navTabs = this.$el.find('.nav-tab');			
			this.$navTabWindows = this.$el.find('.nav-window').hide();
			
			_.bindAll(this,'_initNavTabs','_tabsOnClick');
			
			// Initialize nav tabbing functionality.
			this.$navTabs.each(this._initNavTabs).on('click',this._tabsOnClick);
			
			// Initialize the other views.
			this.cachingOptionsView = new CachingOptionsView();
			this.primarySettingsView = new PrimarySettingsView();
			this.advancedSettingsView = new AdvancedSettingsView();
			
			this.$el.slideDown(136);
		},
		
		// Determines which tab to open when the page first loads.
		'_initNavTabs': function(i,e) {
			if(!window.location.hash) {
				var hash = this.$navTabs.first().addClass('nav-tab-active').get(0).hash;
				$(hash).show();
			} else if(e.hash === window.location.hash) {
				$(e).addClass('nav-tab-active');
				$(e.hash).show();
			}
		},
		
		// Opens the tab when the button is click on.
		'_tabsOnClick': function(e) {
			e.preventDefault();
			window.location.hash = e.currentTarget.hash;
			this.$navTabs.removeClass("nav-tab-active");
			$(e.currentTarget).addClass("nav-tab-active");
			this.$navTabWindows.hide().filter(e.currentTarget.hash).show();
			document.activeElement.blur();
		}
	});
	
	// Caching Settings data model.
	var CachingOptions = Backbone.Model.extend({
		defaults: HTMLMinifierSettings.defaults.caching,
		url: HTMLMinifierSettings.restURL + "&rest=caching"
	});
	
	// Controls the Caching Options page.
	var CachingOptionsView = FormView.extend({
		'el': '#caching',
		
		'initialize': function(options) {
			
			// Initialize the FormView object.
			CachingOptionsView.__super__.initialize.call(this, {
				'model': new CachingOptions(),
				'save': SettingsSaveOptions
			});
			
			this.model.fetch({
				'success': _.bind(function() {
					this.updateChangedFields();
				},this)
			});
		},
		
		'submit': function(e) {
			
			var submitBtn = document.activeElement;
			switch(submitBtn.name) {
			case 'restore_defaults_caching': case 'clear_cache':
				return true;
			case 'submit-caching':
				return CachingOptionsView.__super__.submit.call(this, e);
			}
			
			return false;
		}
		
	});
	
	// Minify Settings data model.
	var PrimarySettings = Backbone.Model.extend({
		defaults: HTMLMinifierSettings.defaults.core,
		url: HTMLMinifierSettings.restURL + "&rest=core"
	});
	
	// Controls the Minification Settings page.
	var PrimarySettingsView = FormView.extend({
		
		'el': '#primary-settings',
		
		'events': {
			'submit': 'submit',
			'change input,select': 'onChange',
			'click #compression_ignored_tags a.add-new-tag': 'addCompIgnoreTag',
			'click #compression_ignored_tags a.delete-tag': 'deleteCompIgnoreTag'
		},
		
		// Adds a new compression ignored tag.
		'addCompIgnoreTag': function(e) {
			this.$compressionIgnoredTagsFields.append(this.newIgnoredTagTmpl());
		},
		
		// Delete the compression ignored tag.
		'deleteCompIgnoreTag': function(e) {
			e.currentTarget.parentNode.parentNode.removeChild(e.currentTarget.parentNode);
		},
		
		'initialize': function() {
			
			this.isDynamic = true; // FormView variable.
			this.$subOptions = this.$el.find('label[rel]');
			
			// Compression ignored tags.
			this.$compressionIgnoredTags = $(document.getElementById("compression_ignored_tags"));
			this.$compressionIgnoredTagsFields = this.$compressionIgnoredTags.children('div');
			this.newIgnoredTagTmpl = _.template(this.$compressionIgnoredTags.find('script').html());
			
			// Initialize the FormView object.
			PrimarySettingsView.__super__.initialize.call(this, {
				'model': new PrimarySettings(),
				'save': SettingsSaveOptions
			});
			
			// Update model with form data and make sure the front end display is the same as the model data.
			this.model.fetch({
				'success': _.bind(function() {
					this.updateFields();
				},this)
			});
		},
		
		'submit': function(e) {
						
			var submitBtn = document.activeElement;
			switch(submitBtn.name) {
			case 'super_safe': case 'safe': case 'moderate': case 'fully_optimised':
				/*
				this.models.primarySettings.set(this.presetData[submitBtn.name]);
				quickNotification.open("<strong>Note:</strong> You still need to <em>Save Changes</em> for the Presets to take effect.");
				*/
				return true;
			case 'submit-core':				
				e.preventDefault();
				return PrimarySettingsView.__super__.submit.call(this, e);
			}
			return false;
		},
		
		'onChange': function(e) {
			var field = e.currentTarget;
			
			switch(field.nodeName) {
				
			case 'INPUT':
				
				if(field.type.toLowerCase() === 'checkbox') {
					
					var $rel = this.$subOptions.filter('[rel="'+field.id+'"]');
					
					if(!field.checked) {
						// Disable the related field.
						if($rel.length > 0) $rel.slideUp(237).children('input').prop('disabled',true);
						
						// Force the field to be checked, or uncheck the child field.
						if(field.required) field.checked = true;
						else {
							$rel.children('input').prop('checked',false);
						}
					} else {
						// Enable the related field.
						if($rel.length > 0) $rel.slideDown(217).children('input').prop('disabled',false);
					}
				} else {
					if(field.name === "compression_ignored_tags[]") {
						field.value = field.value.replace(/^(\s*)?\<|\>(\s*)?$/g,"");
					}
				}
				break;
				
			case 'SELECT':				
				// Force clean CSS and JS comments to be checked if we are choosing 'all_whitespace'.
				if(field.name === 'compression_mode') {
					var $clean_comments = this.$el.find('#clean_css_comments, #clean_js_comments');
					if(field.value === 'all_whitespace') {
						$clean_comments.each(function(i,e) {							
							if(!e.checked) $(e).trigger('click');
							e.setAttribute('required','required');
						});
					} else $clean_comments.removeAttr('required');					
				}
				break;
			}
			
		},
		
		// Finds the associated key in the model and updates it.
		'setModelData': function(key, value, isSilent) {
			
			var changes = {};
			
			if(typeof silent === 'undefined') isSilent = true;
			
			var subkey;
			switch(key) {
			default:
				changes[key] = value;
				PrimarySettingsView.__super__.setModelData.call(this,key,value);
				return true;
			case 'remove_comments_with_cdata_tags_css':
				subkey = 'clean_css_comments';
				break;
			case 'ignore_link_schema_tags':
				subkey = 'shift_link_tags_to_head';
				break;
			case 'ignore_meta_schema_tags':
				subkey = 'shift_meta_tags_to_head';
				break;
			case 'combine_style_tags':
				subkey = 'shift_style_tags_to_head';
				break;
			case 'remove_comments_with_cdata_tags_js':
				subkey = 'clean_js_comments';
				break;
			case 'combine_javascript_in_script_tags':
			case 'ignore_async_and_defer_tags':
				subkey = 'shift_script_tags_to_bottom';
				break;
			}
			
			// Set the value in the object.
			var obj = this.model.get(subkey);
			if(typeof obj !== 'object') obj = {};
			obj[key] = value;
			
			// Recommit this object into the model.
			changes[subkey] = obj;
			this.model.set(changes,{isSilent:true});
			return true;
		},
		
		'getModelData': function(key) {
			var subkey;
			switch(key) {
			default:
				return this.model.get(key);
			case 'remove_comments_with_cdata_tags_css':
				subkey = 'clean_css_comments';
				break;
			case 'ignore_link_schema_tags':
				subkey = 'shift_link_tags_to_head';
				break;
			case 'ignore_meta_schema_tags':
				subkey = 'shift_meta_tags_to_head';
				break;
			case 'combine_style_tags':
				subkey = 'shift_style_tags_to_head';
				break;
			case 'remove_comments_with_cdata_tags_js':
				subkey = 'clean_js_comments';
				break;
			case 'combine_javascript_in_script_tags':
			case 'ignore_async_and_defer_tags':
				subkey = 'shift_script_tags_to_bottom';
				break;
			}
			var obj = this.model.get(subkey);
			return obj[key];
		}
	});
	
	var AdvancedSettings = Backbone.Model.extend({
		defaults: HTMLMinifierSettings.defaults.manager,
		url: HTMLMinifierSettings.restURL + "&rest=manager"
	});
	
	var AdvancedSettingsView = FormView.extend({
		'el':'#advanced-settings',
		
		'initialize': function() {			
			
			// Initialize the FormView object.
			AdvancedSettingsView.__super__.initialize.call(this, {
				'model': new AdvancedSettings(),
				'save': SettingsSaveOptions
			});
			
			// Update model with form data and make sure the front end display is the same as the model data.
			this.model.fetch({
				'success': _.bind(function() {
					this.updateChangedFields();
				},this)
			});
		},
		
		'submit': function(e) {
			
			var submitBtn = document.activeElement;
			switch(submitBtn.name) {
			case 'restore_defaults_manager':
				return true;
			case 'submit-manager':
				return AdvancedSettingsView.__super__.submit.call(this, e);
			}
			
			return false;
		}
		
	});
	
	var FeedbackView = Backbone.View.extend({
		el: '#feedback-bug-report',
		events: { 'submit': 'onSubmit' },
		initialize: function() {
			_.bindAll(this,'onSubmit');
		},
		onSubmit: function(e) {
			$.ajax('',{
				dataType: 'html',
				data: this.$el.serialize() + '&ajax=1',
				success: function(response) { quickNotification.open(response); },
				error: function(jqXHR, textStatus, errorThrown) { quickNotification.open(response); }
			});
		}
	});
	
	var settingsView = new SettingsView(),
		quickNotification = new QuickNotification();

})(jQuery);