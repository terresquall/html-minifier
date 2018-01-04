(function($) {
	"use strict";

	// Activate tooltips.
	$(function() { $(".tooltip").tooltip(); });
	
	// Controls the navigation tab and initializes the other views, storing references to each of them.
	var SettingsView = Backbone.View.extend({
		el: '#SettingsView',
		
		initialize: function() {
			
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
		},
		
		// Determines which tab to open when the page first loads.
		_initNavTabs: function(i,e) {
			if(!window.location.hash) {
				var hash = this.$navTabs.first().addClass('nav-tab-active').get(0).hash;
				$(hash).show();
			} else if(e.hash === window.location.hash) {
				$(e).addClass('nav-tab-active');
				$(e.hash).show();
			}
		},
		
		// Opens the tab when the button is click on.
		_tabsOnClick: function(e) {
			e.preventDefault();
			this.$navTabs.removeClass("nav-tab-active");
			$(e.currentTarget).addClass("nav-tab-active");
			this.$navTabWindows.hide().filter(e.currentTarget.hash).show();
			document.activeElement.blur();
		}
	});
	
	// Controls the Caching Options page.
	var CachingOptionsView = Backbone.View.extend({
		el: '#caching',
		initialize: function() {
			// For the button in the cache tab.
			this.$el.find('#cache-back-to-minify').on('click',function(e) {
				e.preventDefault();
				settingsView.$navTabs.eq(0).trigger('click');
			});
		}
	});
	
	// Minify Settings data model.
	var PrimarySettings = Backbone.Model.extend({
		defaults: {
			'clean_html_comments': true,
			'merge_multiple_head_tags': true,
			'merge_multiple_body_tags': true,
			'show_signature': true,
			
			// Stylesheet optimisations.
			'clean_css_comments': { 'remove_comments_with_cdata_tags_css': true },
			'shift_link_tags_to_head': { 'ignore_link_schema_tags': true },
			'shift_meta_tags_to_head': { 'ignore_meta_schema_tags': true },
			'shift_style_tags_to_head': { 'combine_style_tags': false },
			
			// Javascript optimisations.
			'clean_js_comments': { 'remove_comments_with_cdata_tags_js': false },
			'compression_ignore_script_tags': true,
			'shift_script_tags_to_bottom': false,
			
			// How do you want to compress the script?
			'compression_mode': 'all_whitespace_not_newlines'
		},
		url: HTMLMinifierSettings.restURL + "&rest=core"
	});
	
	// Controls the Minification Settings page.
	var PrimarySettingsView = Backbone.View.extend({
		el: '#primary-settings',
		
		events: {
			'change input,select': 'onChange',
			'submit': 'onSubmit'  
		},
		
		initialize: function() {
			// Get all labels with suboptions.
			this.$subOptions = this.$el.find('label[rel]');
			
			this.formInputs = {}; // Contains an ID value map to all form DOM objects.
			
			// Set the model for this View.
			this.model = new PrimarySettings();
			
			// Disable presets buttons until we preload the settings.
			this.$presetBtns = this.$el.find('.button-presets').prop('disabled',true);
			$.ajax(HTMLMinifierSettings.restURL + '&rest=presets',{
				'dataType': 'json',
				'success': _.bind(function(data) {
					this.$presetBtns.prop('disabled',false);
					this.presetData = data;
				},this)
			});
			
			// Make sure all updates to the model updates the web UI.
			this.listenTo(this.model,'change',this.listenToModel);
			this.model.fetch();
		},
		
		onSubmit: function(e) {
						
			var submitBtn = document.activeElement;
			switch(submitBtn.name) {
			case 'super_safe': case 'safe':
			case 'moderate': case 'fully_optimised':
				/*
				this.models.primarySettings.set(this.presetData[submitBtn.name]);
				quickNotification.open("<strong>Note:</strong> You still need to <em>Save Changes</em> for the Presets to take effect.");
				*/
				return true;
			case 'submit-core':				
				e.preventDefault();
				
				submitBtn.disabled = true;
				quickNotification.close();
				this.model.save(null,{
					success: function(model,response) {
						submitBtn.disabled = false;
						quickNotification.open("Your settings have been saved.");
					},
					error: function(model,response) {
						var msgStr = "There has been an error and your settings have <b>NOT</b> been saved. Please try again.";
						submitBtn.disabled = false;
						if(typeof response.responseText === 'string') msgStr += "<br/>" + response.responseText; 
						quickNotification.open(msgStr);
					}
				});
				return false;
			}
			return false;
		},
		
		onChange: function(e) {
			var field = e.currentTarget;
			
			switch(field.nodeName) {
				
			case 'INPUT':
				if(field.type.toLowerCase() === 'checkbox') {
					if(field.required && !field.checked) field.checked = true;
					if(!field.checked) {
						this.$subOptions.filter('[rel="'+field.id+'"]').children('input').prop('checked',false);
					}
					this.updateModel(field.name,field.checked);
				}
				break;
				
			case 'SELECT':
				this.updateModel(field.name,field.value);
				
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
		
		// Listens to changes in the model and updates them accordingly.
		listenToModel: function(model) {
			for(var k in model.changed) {
				this.updateFormField(k,model.changed[k]);
			}
		},
		
		// Finds the associated key in the model and updates it.
		updateModel: function(key, value, isSilent) {
			
			var changes = {};
			
			if(typeof silent === 'undefined') isSilent = true;
			
			var subkey;
			switch(key) {
			default:
				changes[key] = value;
				this.model.set(changes,{silent:isSilent});
				
				// Only boolean fields can allow the existence of dependent subfields.
				if(typeof value === 'boolean') {
					var $rel = this.$subOptions.filter('[rel="'+key+'"]');
					if($rel.length > 0) {
						// Hide and disable the field or display and enable it.
						if(value) $rel.slideDown(217).children('input').prop('disabled',false);
						else $rel.slideUp(237).children('input').prop('disabled',true);
					}
				}
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
			if(typeof obj !== 'object') obj = {}
			obj[key] = value;
			
			// Recommit this object into the model.
			changes[subkey] = obj;
			this.model.set(changes,{isSilent:true});
			return true;
		},
		
		// Updates the associated form field.
		updateFormField: function(key, value) {
			// Get a reference to the DOM object.
			var dom;
			if(this.formInputs.hasOwnProperty(key)) {
				dom = this.formInputs[key];
			} else {
				// Stores a reference to the found object so we don't have to search for it again.
				dom = this.$el.find('#'+key).get(0);
				if(dom) 
					this.formInputs[key] = dom;
			}
			
			//console.log(key,value,dom);
			if(!dom) {
				console.warn("There is no form field by the ID of <"+key+"> in <PrimarySettings>.");
				return;
			}
			
			// Changes the value of the DOM object to <value>.
			switch(dom.nodeName) {
			case 'INPUT':
				if(dom.type === "checkbox")
					dom.checked = value ? true : false;
				break;
			case 'SELECT':
				for(var i=0;i<dom.options.length;i++) {
					if(dom.options[i].value === value) dom.selectedIndex = i;
				}
				break;
			}
		}
	});
	
	var AdvancedSettings = Backbone.Model.extend({
		defaults: {
			'minifier_version': 'use_latest',
			'minify_wp_admin': false,
			'minify_frontend': true
		},
		url: HTMLMinifierSettings.restURL + "&rest=manager"
	})
	
	var AdvancedSettingsView = Backbone.View.extend({
		el:'#advanced-settings',
		
		events: {
			'change input': 'onChange',
			'submit': 'onSubmit'  
		},
		
		initialize: function() {			
			
			this.formInputs = {}; // Contains an ID value map to all form DOM objects.
			
			// Set the model for this View.
			this.model = new AdvancedSettings();
			
			// Make sure all updates to the model updates the web UI.
			this.listenTo(this.model,'change',this.listenToModel);
			this.model.fetch();
		},
		
		onSubmit: function(e) {
						
			var submitBtn = document.activeElement;
			switch(submitBtn.name) {
			case 'restore_defaults_manager':
				return true;
				
			case 'submit-manager':				
				e.preventDefault();
				
				submitBtn.disabled = true;
				quickNotification.close();
				this.model.save(null,{
					success: function(model,response) {
						submitBtn.disabled = false;
						quickNotification.open("Your settings have been saved.");
					},
					error: function(model,response) {
						var msgStr = "There has been an error and your settings have <b>NOT</b> been saved. Please try again.";
						submitBtn.disabled = false;
						if(typeof response.responseText === 'string') msgStr += "<br/><pre>" + response.responseText + "</pre>"; 
						quickNotification.open(msgStr);
					}
				});
				return false;
			}
			return false;
		},
		
		onChange: function(e) {
			var field = e.currentTarget;
			
			switch(field.nodeName) {	
			case 'INPUT':
				if(field.type.toLowerCase() === 'checkbox') {
					if(field.required && !field.checked) field.checked = true;
					this.updateModel(field.name,field.checked);
				} else if(field.type.toLowerCase() === 'radio') {
					if(field.checked) this.updateModel(field.name,field.value);
				}
				break;
			}
		},
		
		// Listens to changes in the model and updates them accordingly.
		listenToModel: function(model) {
			for(var k in model.changed) {
				this.updateFormField(k,model.changed[k]);
			}
		},
		
		// Finds the associated key in the model and updates it.
		updateModel: function(key, value, isSilent) {
			
			var changes = {};
			
			if(typeof silent === 'undefined') isSilent = true;
			
			changes[key] = value;
			this.model.set(changes,{silent:isSilent});
			
			return true;
		},
		
		// Updates the associated form field.
		// ** Doesn't work for radio fields.
		updateFormField: function(key, value) {
			// Get a reference to the DOM object.
			var dom;
			if(this.formInputs.hasOwnProperty(key)) {
				dom = this.formInputs[key];
			} else {
				// Stores a reference to the found object so we don't have to search for it again.
				dom = document.getElementById(key);
				if(dom) 
					this.formInputs[key] = dom;
				else {
					dom = [];
					var list = document.querySelectorAll('[name="'+key+'"]');
					for(var v of list.values()) dom.push(v);
					this.formInputs[key] = dom;
				}
			}
			
			if(!dom) {
				console.warn("There is no form field by the ID of <"+key+"> in the DOM.");
				return;
			}
			
			// Changes the value of the DOM object to <value>.
			switch(dom.nodeName) {
			case 'INPUT':
				if(dom.type === "checkbox")
					dom.checked = value ? true : false;
				else if(dom.constructor === Array) {
					for(var i=0;i<dom.length;i++)
						if(dom[i].value === value) dom.checked = true;
				}
				break;
			}
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
	
	var settingsView = new SettingsView(),
		quickNotification = new QuickNotification();

})(jQuery);