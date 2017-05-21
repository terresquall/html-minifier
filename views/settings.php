<?php
if (!function_exists( 'add_action' )) {
	echo 'Hi there!  I\'m just a plugin; not much I can do when called directly.';
	exit;
}
?><div class="wrap">
    <h1>HTML Minifier &mdash; Settings</h1><?php
		$p = HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX;
		if(isset($GLOBALS[$p . 'settings_notice_message']) && isset($GLOBALS[$p . 'settings_notice_class']))
			echo '<div class="' . $GLOBALS[$p . 'settings_notice_class'] . '"><p>' . $GLOBALS[$p . 'settings_notice_message'] . '</p></div>';
	?><table style="background:#e8e8e8;padding:0.5em;margin-top:0.5em;font-size:11px;color:#666;max-width:100%;width:550px;border-radius:8px;">
		<tr>
			<td rowspan="3" style="width:76px;text-align:center;"><img src="<?php echo HTML_MINIFIER__PLUGIN_URL; ?>assets/icon-128x128.png" style="height:64px;"/></td>
			<th style="width:100px;text-align:left;">Plugin Version:</th>
			<td style="text-align:left;"><?php echo HTML_MINIFIER_PLUGIN_VERSION; ?></td>
		</tr>
		<tr>
			<th style="width:100px;text-align:left;">Release Date:</th>
			<td style="text-align:left;"><?php echo HTML_MINIFIER_PLUGIN_VERSION_DATE; ?></td>
		</tr>
		<tr>
			<th style="width:100px;text-align:left;">Minifier Version:</th>
			<td style="text-align:left;"><?php echo HTML_MINIFIER_VERSION; ?></td>
		</tr>
	</table>
    <form method="post" id="html-minifier-form-settings">
		<?php echo wp_nonce_field( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'settings_nonce', HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'settings_nonce',true,true); ?>
        <table class="form-table">
            <tbody>
				<tr>
                    <th scope="row">General Options</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Options</span></legend>
							<label for="clean_html_comments" class="tooltip" title="Removes all HTML comments, except those with conditional tags.">
								<input type="checkbox" name="clean_html_comments" id="clean_html_comments" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['clean_html_comments']) && HTMLMinifier_Manager::$CurrentOptions['clean_html_comments']) echo ' checked="checked"';
								?>> Remove HTML comments
							</label>
							<br>
							<label for="show_signature" class="tooltip" title="Adds a HTML comment about this plugin at the end of the minified source.">
								<input type="checkbox" name="show_signature" id="show_signature" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['show_signature']) && HTMLMinifier_Manager::$CurrentOptions['show_signature']) echo ' checked="checked"';
								?>> Show signature in source
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row">Stylesheet Optimisation</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Options</span></legend>
                            <label for="clean_css_comments" class="tooltip" title="Removes all comments in CSS embedded on the page.">
								<input type="checkbox" name="clean_css_comments" id="clean_css_comments" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['clean_css_comments']) && HTMLMinifier_Manager::$CurrentOptions['clean_css_comments']) echo ' checked="checked"';
								?>> Remove comments in <code>&lt;style&gt;</code> tags
							</label>
							<br/>
							<label for="shift_link_tags_to_head" class="tooltip" title="Includes all &lt;link&gt; tags outside of &lt;head&gt;. Only works on stylesheet &lt;link&gt; tags.">
								<input type="checkbox" name="shift_link_tags_to_head" id="shift_link_tags_to_head" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['shift_link_tags_to_head']) && HTMLMinifier_Manager::$CurrentOptions['shift_link_tags_to_head']) echo ' checked="checked"';
								?>> Shift all <code>&lt;link&gt;</code> stylesheet tags into <code>&lt;head&gt;</code>
							</label>
							<br/>
							<label for="shift_style_tags_to_head" class="tooltip" title="Includes all &lt;style&gt; tags outside of &lt;head&gt;.">
								<input type="checkbox" name="shift_style_tags_to_head" id="shift_style_tags_to_head" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) && HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) echo ' checked="checked"';
								?>> Shift all <code>&lt;style&gt;</code> tags into <code>&lt;head&gt;</code>
							</label>
							<br/>
							<label for="combine_style_tags" class="tooltip" title="Combines CSS in the separate &lt;style&gt; tags across the document together. Will not combine tags with different media attributes." rel="shift_style_tags_to_head" style="padding-left:1.7em;<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) || !HTMLMinifier_Manager::$CurrentOptions['shift_style_tags_to_head']) echo 'display:none;'?>">
								<input type="checkbox" name="combine_style_tags" id="combine_style_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['combine_style_tags']) && HTMLMinifier_Manager::$CurrentOptions['combine_style_tags']) echo ' checked="checked"';
								?>> Combine CSS in <code>&lt;style&gt;</code> tags
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row">Script Optimisation<br/><small style="font-weight:100;">If your code messes up, the options in this section are probably the culprit. Try unchecking some of the checkboxes here.</small></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Options</span></legend>
                            
							<label for="clean_js_comments" class="tooltip" title="Removes all comments in Javascript embedded on the page.">
								<input name="clean_js_comments" type="checkbox" id="clean_js_comments" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) && HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) echo ' checked="checked"';
								?>> Remove Javascript comments
							</label>
							<br<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) || !HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) echo ' style="display:none;"'?>/>
							<label for="remove_comments_with_cdata_tags" class="tooltip" title="In XHTML, content inside &lt;script&gt; tags are encapsulated with opening and closing CDATA tags that are commented out. This makes the document XML-compatible, so comments containing CDATA tags should not be removed." rel="clean_js_comments" style="padding-left:1.7em;<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) || !HTMLMinifier_Manager::$CurrentOptions['clean_js_comments']) echo 'display:none;'?>">
								<input type="checkbox" name="remove_comments_with_cdata_tags" id="remove_comments_with_cdata_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['remove_comments_with_cdata_tags']) && HTMLMinifier_Manager::$CurrentOptions['remove_comments_with_cdata_tags']) echo ' checked="checked"';
								?>> Remove comments containing CDATA tags
							</label>
							<br/>
							<label for="compression_ignore_script_tags" class="tooltip" title="If your Javascript code isn't properly-written, i.e. your lines are not properly truncated with semi-colons, check this to ignore compressing them.">
								<input name="compression_ignore_script_tags" type="checkbox" id="compression_ignore_script_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['compression_ignore_script_tags']) && HTMLMinifier_Manager::$CurrentOptions['compression_ignore_script_tags']) echo ' checked="checked"';
								?>> Don't compress content in <code>&lt;script&gt;</code> tags.
							</label>
							<br/>
							<label for="shift_script_tags_to_bottom" class="tooltip" title="Moves all existing &lt;script&gt; tags in the page to the bottom. Might cause on-page Javascript to break, depending on how your page is scripted.">
								<input name="shift_script_tags_to_bottom" type="checkbox" id="shift_script_tags_to_bottom" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) && HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) echo ' checked="checked"';
								?>> Shift all <code>&lt;script&gt;</code> tags to the end of <code>&lt;body&gt;</code>
							</label>
							<br/>
							<label for="combine_javascript_in_script_tags" class="tooltip" title="Only applicable for &lt;script&gt; tags with an unspecified MIME type or of MIME type &quot;text/javascript&quot;." rel="shift_script_tags_to_bottom" style="padding-left:1.7em;<?php if(!isset(HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) || !HTMLMinifier_Manager::$CurrentOptions['shift_script_tags_to_bottom']) echo 'display:none;'?>">
								<input name="combine_javascript_in_script_tags" type="checkbox" id="combine_javascript_in_script_tags" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['combine_javascript_in_script_tags']) && HTMLMinifier_Manager::$CurrentOptions['combine_javascript_in_script_tags']) echo ' checked="checked"';
								?>> Combine Javascript in <code>&lt;script&gt;</code> tags
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row">Compression Mode</th>
                    <td>
                        <fieldset>
                            <select name="compression_mode">
								<?php
								if(class_exists('HTMLMinifier')) 
									foreach(HTMLMinifier::$CompressionMode as $k => $v)
										echo '<option value="'.$k.'"'.(HTMLMinifier_Manager::$CurrentOptions['compression_mode']===$k?' selected="selected"':'').'>'.$v.'</option>';
								?>
							</select>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row">Minify WP-Admin Source<br/><small style="color:#d00;font-weight:100;">Experimental feature. If you run into bugs, it might mess up certain features in WP-Admin!</small></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Options</span></legend>
                            <label for="minify_wp_admin" class="tooltip" title="If this messes up your installation, come back to this page to uncheck it. Or disable this plugin.">
								<input name="minify_wp_admin" type="checkbox" id="minify_wp_admin" value="1"<?php 
									if(isset(HTMLMinifier_Manager::$CurrentOptions['minify_wp_admin']) && HTMLMinifier_Manager::$CurrentOptions['minify_wp_admin']) echo ' checked="checked"';
								?>> Might cause WP-Admin to become unusable
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"></th>
                    <td>
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"/>
						<input type="submit" name="restore_default" id="restore_default" class="button button-secondary" value="Restore Default Settings" onclick="return confirm('Are you sure? Your current settings will be lost.');"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
	<hr/>
	<h2 class="title dashicons-before dashicons-admin-generic">&nbsp;&nbsp;Feedback &amp; Bug Reporting</h2>
	<p style="max-width:100%;width:650px;">HTML Minifier strives to be a useful, lightweight and no-frills plugin that helps to minify the HTML output of your site. Let us know if you find any bugs with the plugin, have suggestions on how we can improve, or if there are additional features you'd like to see in future versions.</p>
	<form method="post" id="html-minifier-form-feedback">
		<?php echo wp_nonce_field( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'feedback_nonce', HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'feedback_nonce',true,true); ?>
        <textarea style="max-width:100%;width:650px;height:165px;" name="feedback-text" id="feedback-text" placeholder="Speak away!"><?php
			if(isset($GLOBALS[HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'feedback_error_message']))
				echo esc_textarea($GLOBALS[HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'feedback_error_message']);
		?></textarea>
		<p class="submit">
			<input type="submit" name="submit-feedback" id="submit-feedback" class="button button-primary" value="Send Your Feedback"/>
		</p>
    </form>
</div>
<script>
(function($) {
	"use strict";

	// Activate tooltips.
	$(function() { $(".tooltip").tooltip(); });

	// Show / hide certain input options.
	var $form = $('#html-minifier-form-settings');
	$form.find('label[rel]').each(function() {
		var rel = this.getAttribute('rel'), $this = $(this),
			$parent = $form.find('#'+rel).on('click',function(evt) {
				if(this.checked) {
					$this.prev().show();
					$this.slideDown(257).children('input').prop('disabled',false);
				} else {
					$this.slideUp(237,function() {
						$this.prev().hide();
					}).children('input').prop('disabled',true);
				}
			});
	});
	
})(jQuery);
</script>