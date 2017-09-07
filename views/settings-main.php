<?php
if (!function_exists( 'add_action' )) {
	echo 'Hi there!  I\'m just a component of a Wordpress plugin. Not much I can do when called directly.';
	exit;
}
?><div class="wrap">
    <h1>HTML Minifier &mdash; <?php echo _e('Settings','html-minifier'); ?></h1><?php
		$p = HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX;
		if(isset($GLOBALS[$p . 'settings_notice_message']) && isset($GLOBALS[$p . 'settings_notice_class']))
			echo '<div class="' . $GLOBALS[$p . 'settings_notice_class'] . '"><p>' . $GLOBALS[$p . 'settings_notice_message'] . '</p></div>';
	?><table style="background:#e8e8e8;padding:0.5em;margin-top:0.5em;font-size:11px;color:#666;max-width:100%;width:550px;border-radius:8px;">
		<tr>
			<td rowspan="4" style="width:76px;text-align:center;"><img src="<?php echo HTML_MINIFIER__PLUGIN_URL; ?>assets/icon-128x128.png" style="height:64px;"/></td>
			<th style="width:100px;text-align:left;"><?php _e('Plugin Version','html-minifier'); ?>:</th>
			<td style="text-align:left;"><?php echo HTML_MINIFIER_PLUGIN_VERSION; ?></td>
		</tr>
		<tr>
			<th style="width:100px;text-align:left;"><?php _e('Release Date','html-minifier'); ?>:</th>
			<td style="text-align:left;"><?php echo HTML_MINIFIER_PLUGIN_VERSION_DATE; ?></td>
		</tr>
		<tr>
			<th style="width:100px;text-align:left;"><?php _e('Minifier Version','html-minifier'); ?>:</th>
			<td style="text-align:left;"><?php echo HTML_MINIFIER_VERSION; ?></td>
		</tr>
		<tr>
			<th style="width:100px;text-align:left;"><?php _e('Support','html-minifier'); ?>:</th>
			<td style="text-align:left;"><a href="https://paypal.me/Terresquall" target="_blank"><?php echo __('Donate','html-minifier'); ?></a></td>
		</tr>
	</table>
	<h2 class="nav-tab-wrapper">
		<a href="#minify-options" class="nav-tab"><?php _e('Minification Options','html-minifier'); ?></a>
		<a href="#caching-options" class="nav-tab"><?php _e('Caching Options','html-minifier'); ?></a>
	</h2>
	<form method="post" id="minify-options" class="nav-tabbed">
		<?php echo wp_nonce_field( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'settings_nonce', HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'settings_nonce',true,true); ?>
        <table class="form-table">
            <tbody>
				<tr>
                    <th scope="row"><?php _e('General Options','html-minifier'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Options','html-minifier'); ?></span></legend>
							<label for="clean_html_comments" class="tooltip" title="<?php _e('Removes all HTML comments, except those with conditional tags.','html-minifier'); ?>">
								<input type="checkbox" name="clean_html_comments" id="clean_html_comments" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['clean_html_comments']) && HTMLMinifier_Manager::$CurrentOptions['clean_html_comments']) echo ' checked="checked"';
								?>> <?php _e('Remove HTML comments','html-minifier'); ?>
							</label>
							<br>
							<label for="show_signature" class="tooltip" title="<?php _e('Adds a HTML comment about this plugin at the end of the minified source.','html-minifier'); ?>">
								<input type="checkbox" name="show_signature" id="show_signature" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['show_signature']) && HTMLMinifier_Manager::$CurrentOptions['show_signature']) echo ' checked="checked"';
								?>> <?php _e('Show signature in source','html-minifier'); ?>
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e('Stylesheet Optimisation','html-minifier'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Options','html-minifier'); ?></span></legend>
                            <label for="clean_css_comments" class="tooltip" title="<?php _e('Removes all comments in CSS embedded on the page.','html-minifier'); ?>">
								<input type="checkbox" name="clean_css_comments" id="clean_css_comments" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['clean_css_comments']) && HTMLMinifier_Manager::$CurrentOptions['clean_css_comments']) echo ' checked="checked"';
								?>> <?php _e('Remove comments in <code>&lt;style&gt;</code> tags','html-minifier'); ?>
							</label>
							<br/>
							<label for="shift_link_tags_to_head" class="tooltip" title="<?php _e('Includes all &lt;link&gt; tags outside of &lt;head&gt;. Only works on stylesheet &lt;link&gt; tags.','html-minifier'); ?>">
								<input type="checkbox" name="shift_link_tags_to_head" id="shift_link_tags_to_head" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['shift_link_tags_to_head']) && HTMLMinifier_Manager::$CurrentOptions['shift_link_tags_to_head']) echo ' checked="checked"';
								?>> <?php _e('Shift all <code>&lt;link&gt;</code> stylesheet tags into <code>&lt;head&gt;</code>','html-minifier'); ?>
							</label>
							<br/>
							<label for="shift_style_tags_to_head" class="tooltip" title="<?php _e('Includes all &lt;style&gt; tags outside of &lt;head&gt;.','html-minifier'); ?>">
								<input type="checkbox" name="shift_style_tags_to_head" id="shift_style_tags_to_head" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) && HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) echo ' checked="checked"';
								?>> <?php _e('Shift all <code>&lt;style&gt;</code> tags into <code>&lt;head&gt;</code>','html-minifier'); ?>
							</label>
							<br/>
							<label for="combine_style_tags" class="tooltip" title="<?php _e('Combines CSS in the separate &lt;style&gt; tags across the document together. Will not combine tags with different media attributes.','html-minifier'); ?>" rel="shift_style_tags_to_head" style="padding-left:1.7em;<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) || !HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) echo 'display:none;'?>">
								<input type="checkbox" name="combine_style_tags" id="combine_style_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['combine_style_tags']) && HTMLMinifier_Manager::$CurrentOptions['combine_style_tags']) echo ' checked="checked"';
								?>> <?php _e('Combine CSS in <code>&lt;style&gt;</code> tags','html-minifier'); ?>
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e('Script Optimisation','html-minifier'); ?><br/><small style="font-weight:100;"><?php _e("If your code breaks after activating the plugin, try checking only the default options in this section (i.e. remove Javascript comments and don't compress &lt;script&gt; contents).",'html-minifier'); ?></small></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Options','html-minifier'); ?></span></legend>
                            
							<label for="clean_js_comments" class="tooltip" title="<?php _e('Removes all comments in Javascript embedded on the page.','html-minifier'); ?>">
								<input name="clean_js_comments" type="checkbox" id="clean_js_comments" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) && HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) echo ' checked="checked"';
								?>> <?php _e('Remove Javascript comments','html-minifier'); ?>
							</label>
							<br<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) || !HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) echo ' style="display:none;"'?>/>
							<label for="remove_comments_with_cdata_tags" class="tooltip" title="<?php _e('In XHTML, content inside &lt;script&gt; tags are encapsulated with opening and closing CDATA tags that are commented out. This makes the document XML-compatible, so comments containing CDATA tags should not be removed.','html-minifier'); ?>" rel="clean_js_comments" style="padding-left:1.7em;<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) || !HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) echo 'display:none;'?>">
								<input type="checkbox" name="remove_comments_with_cdata_tags" id="remove_comments_with_cdata_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['remove_comments_with_cdata_tags']) && HTMLMinifier_Manager::$CurrentOptions['remove_comments_with_cdata_tags']) echo ' checked="checked"';
								?>> <?php _e('Remove comments containing CDATA tags','html-minifier'); ?>
							</label>
							<br/>
							<label for="compression_ignore_script_tags" class="tooltip" title="<?php _e("If your Javascript code isn't properly-written, i.e. your lines are not properly truncated with semi-colons, check this to ignore compressing them.",'html-minifier'); ?>">
								<input name="compression_ignore_script_tags" type="checkbox" id="compression_ignore_script_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['compression_ignore_script_tags']) && HTMLMinifier_Manager::$CurrentOptions['compression_ignore_script_tags']) echo ' checked="checked"';
								?>> <?php _e("Don't compress content in <code>&lt;script&gt;</code> tags.",'html-minifier'); ?>
							</label>
							<br/>
							<label for="shift_script_tags_to_bottom" class="tooltip" title="<?php _e('Moves all existing &lt;script&gt; tags in the page to the bottom. Might cause on-page Javascript to break, depending on how your page is scripted.','html-minifier'); ?>">
								<input name="shift_script_tags_to_bottom" type="checkbox" id="shift_script_tags_to_bottom" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) && HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) echo ' checked="checked"';
								?>> <?php _e('Shift all <code>&lt;script&gt;</code> tags to the end of <code>&lt;body&gt;</code>','html-minifier'); ?>
							</label>
							<br/>
							<label for="combine_javascript_in_script_tags" class="tooltip" title="<?php _e('Only applicable for &lt;script&gt; tags with an unspecified MIME type or of MIME type &quot;text/javascript&quot;.','html-minifier'); ?>" rel="shift_script_tags_to_bottom" style="padding-left:1.7em;<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) || !HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) echo 'display:none;'?>">
								<input name="combine_javascript_in_script_tags" type="checkbox" id="combine_javascript_in_script_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['combine_javascript_in_script_tags']) && HTMLMinifier_Manager::$CurrentOptions['combine_javascript_in_script_tags']) echo ' checked="checked"';
								?>> <?php _e('Combine Javascript in <code>&lt;script&gt;</code> tags','html-minifier'); ?>
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e('Compression Mode','html-minifier'); ?></th>
                    <td>
                        <fieldset>
                            <select name="compression_mode" id="compression_mode">
								<?php
								if(class_exists('HTMLMinifier')) 
									foreach(HTMLMinifier::$CompressionMode as $k => $v)
										echo '<option value="'.$k.'"'.(HTMLMinifier_Manager::$CurrentOptions['compression_mode']===$k?' selected="selected"':'').'>'.__($v,'html-minifier').'</option>';
								?>
							</select>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e('Minify WP-Admin Source','html-minifier'); ?><br/>
						<small style="color:#d00;font-weight:100;"><?php _e('Experimental feature. If you run into bugs, it might mess up certain features in WP-Admin!','html-minifier'); ?></small>
					</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Options','html-minifier'); ?></span></legend>
                            <label for="minify_wp_admin" class="tooltip" title="<?php _e('If this messes up your installation, come back to this page to uncheck it. Or disable this plugin.','html-minifier'); ?>">
								<input name="minify_wp_admin" type="checkbox" id="minify_wp_admin" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['minify_wp_admin']) && HTMLMinifier_Manager::$CurrentOptions['minify_wp_admin']) echo ' checked="checked"';
								?>> <?php _e('Might cause WP-Admin to become unusable','html-minifier'); ?>
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e('Quick Presets','html-minifier'); ?><br/>
						<small style="font-weight:100;"><?php _e("Don't know how to setup? Try these presets.",'html-minifier'); ?></small>
					</th>
                    <td valign="top">
                        <input type="submit" name="super_safe" id="super_safe" value="<?php _e('Super Safe','html-minifier'); ?>" class="button-secondary tooltip" onclick="return confirm('<?php _e('Are you sure? Your current settings will be lost.','html-minifier'); ?>');" title="<?php _e('Only the absolute safest minification options are selected.','html-minifier'); ?>"/>
                        <input type="submit" name="restore_default" id="restore_default" value="<?php _e('Safe (Default)','html-minifier'); ?>" class="button-secondary tooltip" onclick="return confirm('<?php _e('Are you sure? Your current settings will be lost.','html-minifier'); ?>');" title="<?php _e('Default settings that the plugin comes with.','html-minifier'); ?>"/>
                        <input type="submit" name="moderate" id="moderate" value="<?php _e('Moderate','html-minifier'); ?>" class="button-secondary tooltip" onclick="return confirm('<?php _e('Are you sure? Your current settings will be lost.','html-minifier'); ?>');" title="<?php _e('Only the riskiest options are left out.','html-minifier'); ?>"/>
                        <input type="submit" name="fully_optimised" id="fully_optimised" value="<?php _e('Fully-Optimised','html-minifier'); ?>" class="button-secondary tooltip" onclick="return confirm('<?php _e('Are you sure? Your current settings will be lost.','html-minifier'); ?>');" title="<?php _e("Optimises everything. 'Nuff said.",'html-minifier'); ?>"/>
                    </td>
                </tr>
				<tr>
                    <th scope="row"></th>
                    <td>
                        <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes','html-minifier'); ?>"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
	
	<form method="post" id="caching-options" class="nav-tabbed">
		<br/>
		<h2 class="title dashicons-before dashicons-hammer">&nbsp;&nbsp;<?php _e('Feature coming soon!','html-minifier'); ?></h2>
		<p><?php 
		_e(sprintf('In the meantime, if you need caching for your site, please try out <a href="%s" target="_blank">WP Super Cache</a>. It has been tested to work with HTML Minifier.',site_url('/wp-admin/plugin-install.php?s=wp+super+cache&tab=search&type=term')),'html-minifier');
		?></p>
		<p><a href="#minify-options" class="button button-secondary" id="cache-back-to-minify"/><?php _e('&larr; Back to Minification Options','html-minifier'); ?></a></p>
		<br/>
		<?php //echo wp_nonce_field( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'settings_nonce', HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'settings_nonce',true,true); ?>
        <!--table class="form-table">
            <tbody>
				<tr>
                    <th scope="row">Enable Caching</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Options</span></legend>
							<label for="enable_caching" class="tooltip" title="When turned on, all your front-end pages will be cached.">
								<input type="checkbox" name="enable_caching" id="enable_caching" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['enable_caching']) && HTMLMinifier_Manager::$CurrentOptions['enable_caching']) echo ' checked="checked"';
								?>>
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row" class="enable_caching">Expiration Time</th>
                    <td>
                        <fieldset class="tooltip" title="How long a cached page is valid for, in hours." rel="enable_caching">
                            <legend class="screen-reader-text"><span>Options</span></legend>
                            <input type="number" name="expiration_time" id="expiration_time" step="1" min="0" max="999" value="<?php 
								if(isset(HTMLMinifier_Manager::$CurrentOptions['expiration_time']) && HTMLMinifier_Manager::$CurrentOptions['expiration_time']) echo HTMLMinifier_Manager::$CurrentOptions['expiration_time'];
								else echo '24'
							?>" style="width:60px;"> hours
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row" class="enable_caching">Cache Post Types</th>
                    <td>
                        <fieldset rel="enable_caching">
                            <legend class="screen-reader-text"><span>Options</span></legend>
							<?php 
							$pt = get_post_types();
							$ptl = count($pt);
							foreach($pt as $i => $p) {
							?>
                            <label for="post_type_<?php echo $p; ?>" class="tooltip" title="Removes all comments in CSS embedded on the page.">
								<input type="checkbox" name="post_type_<?php echo $p; ?>" id="post_type_<?php echo $p; ?>" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['post_type_'.$p]) && HTMLMinifier_Manager::$CurrentOptions['post_type_'.$p]) echo ' checked="checked"';
								?>> <?php echo $p; ?>
							</label>
							<?php 
								if($i < $ptl-1) echo '<br/>';
							} 
							?>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row" class="enable_caching">Clear Cache</th>
                    <td>
                        <input type="submit" name="submit" id="submit" class="button button-secondary" value="Remove All Cached Pages" rel="enable_caching"/>
                    </td>
                </tr>
				<tr>
                    <th scope="row"></th>
                    <td>
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"/>
						<input type="submit" name="restore_default" id="restore_default" class="button button-secondary" value="Restore Defaults" onclick="return confirm('Are you sure? Your current settings will be lost.');"/>
                    </td>
                </tr>
            </tbody>
        </table-->
    </form>
	<hr/>
	<h2 class="title dashicons-before dashicons-admin-generic">&nbsp;&nbsp;<?php _e('Feedback &amp; Bug Reporting','html-minifier'); ?></h2>
	<p style="max-width:100%;width:650px;"><?php 
		_e("HTML Minifier strives to be a useful, lightweight and no-frills plugin that helps to minify the HTML output of your site. Let us know if you find any bugs with the plugin, have suggestions on how we can improve, or if there are additional features you'd like to see in future versions.",'html-minifier');
	?></p>
	<form method="post" id="html-minifier-form-feedback">
		<?php echo wp_nonce_field( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'feedback_nonce', HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'feedback_nonce',true,true); ?>
        <textarea style="max-width:100%;width:650px;height:165px;" name="feedback-text" id="feedback-text" placeholder="<?php _e('Speak away!','html-minifier'); ?>"><?php
			if(isset($GLOBALS[HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'feedback_error_message']))
				echo esc_textarea($GLOBALS[HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'feedback_error_message']);
		?></textarea>
		<p class="submit">
			<input type="submit" name="submit-feedback" id="submit-feedback" class="button button-primary" value="<?php _e('Send Your Feedback','html-minifier'); ?>"/>
		</p>
    </form>
</div>
<script>
(function($) {
	"use strict";

	// Activate tooltips.
	$(function() { $(".tooltip").tooltip(); });
	
	// Make sure the tabbing in the page is correct as per the hash.
	var $forms = $('form.nav-tabbed').hide(),
		$nav_tabs = $(".nav-tab-wrapper").children(".nav-tab");
		
	$nav_tabs.each(function(i,e) {
		if(!window.location.hash) {
			var hash = $nav_tabs.first().addClass('nav-tab-active').get(0).hash;
			$(hash).show();
		} else if(e.hash === window.location.hash) {
			$(this).addClass('nav-tab-active');
			$(e.hash).show();
		}
	}).on('click',function(e) {
		e.preventDefault();
		$nav_tabs.removeClass("nav-tab-active");
		$(this).addClass("nav-tab-active");
		$forms.hide().filter(this.hash).show();
		document.activeElement.blur();
	});
	
	// For the button in the cache tab.
	$(document.getElementById("cache-back-to-minify")).on('click',function(e) { 
		e.preventDefault();
		$nav_tabs.eq(0).trigger('click');
	});

	// Dynamic stuff for the Minify Options form.
	(function MinifyOptionsForm($) {
		
		// Variable containing all form options.
		var $options = {
			'clean_html_comments': $(document.getElementById("clean_html_comments")),
			'show_signature': $(document.getElementById("show_signature")),
			'clean_css_comments': $(document.getElementById("clean_css_comments")),
			'shift_link_tags_to_head': $(document.getElementById("shift_link_tags_to_head")),
			'shift_style_tags_to_head': $(document.getElementById("shift_style_tags_to_head")),
			'combine_style_tags': $(document.getElementById("combine_style_tags")),
			'clean_js_comments': $(document.getElementById("clean_js_comments")),
			'remove_comments_with_cdata_tags': $(document.getElementById("remove_comments_with_cdata_tags")),
			'compression_ignore_script_tags': $(document.getElementById("compression_ignore_script_tags")),
			'shift_script_tags_to_bottom': $(document.getElementById("shift_script_tags_to_bottom"))
		};
		
		// Show / hide certain input options for the minify portion.
		var $form = $('#minify-options');
		$form.find('label[rel]').each(function() {
			var rel = this.getAttribute('rel'), $this = $(this),
				$parent = $form.find('#'+rel).on('click',function(evt) {
					if(this.hasAttribute('required')) {
						alert("<?php _e('Javascript / CSS comments must be removed using the current Compression Mode.','html-minifier'); ?>");
						return false;
					}
					
					if(this.checked) {
						$this.prev().show();
						$this.slideDown(217).children('input').prop('disabled',false);
					} else {
						$this.slideUp(237,function() {
							$this.prev().hide();
						}).children('input').prop('disabled',true);
					}
				});
		});
		
		// Auto-check remove comments in CSS and JS if we select all_whitespace for compression.			
		$(document.getElementById("compression_mode")).on('change',function(e){
			if(this.value === 'all_whitespace') {
				if(!$options.clean_js_comments.prop('checked')) $options.clean_js_comments.trigger('click');
				if(!$options.clean_css_comments.prop('checked')) $options.clean_css_comments.trigger('click');
				$options.clean_js_comments.attr('required','required');
				$options.clean_css_comments.attr('required','required').on('click',function(e) { 
					alert("<?php _e('Javascript / CSS comments must be removed using the current Compression Mode.','html-minifier'); ?>");
					return false;
				});
			} else {
				$options.clean_js_comments.removeAttr("required");
				$options.clean_css_comments.removeAttr("required").off('click');
			}
		}).trigger('change');
		
	})(jQuery);
	
	// Cache Options form.
	/*
	(function CacheOptionsForm($) {
		
		function ToggleEnableCaching(e) {
			if($enable_caching.prop("checked")) {
				$rel.prop("disabled",false);
				$th_row.removeAttr('style');
			} else {
				$rel.prop("disabled",true);
				$th_row.css('','#ccc');
			}
		}
		
		var $form = $('#caching-options'),
			$rel = $form.find('[rel="enable_caching"]'),
			$th_row = $form.find('.enable_caching'),
			$enable_caching = $(document.getElementById('enable_caching')).on('click',ToggleEnableCaching);
		
		ToggleEnableCaching();
		
	})(jQuery);
	*/
})(jQuery);
</script>