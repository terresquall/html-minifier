<?php
/*
HTMLMinifier_admin
==================
This class hooks code that runs when we are in WP-Admin. It handles the interface in Settings > HTML Minifier and
also the post requests for updating options and processing user feedback. 

@author		Terence Pek <mail@terresquall.com>
@website	www.terresquall.com/web/html-minifier/
@dated		18/05/2017
*/
class HTMLMinifier_Admin {
	
	public static function init() {
		add_action('admin_menu', array('HTMLMinifier_Admin','admin_menu'));
		add_action('admin_enqueue_scripts', array('HTMLMinifier_Admin','admin_enqueue_scripts'));
		add_filter('plugin_action_links_html-minifier/html-minifier.php', array('HTMLMinifier_Admin', 'admin_plugin_settings_link' ));
	}
	
	// Takes the $_POST array and processes information in it that relates to HTML Minifier settings.
	public static function save_options($post) {
		
		// If restore default button is pressed, restore the default settings.
		if(isset($post['restore_default'])) {
			HTMLMinifier_Manager::$CurrentOptions = HTMLMinifier_Manager::$Defaults;
			if(update_option(HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'options',HTMLMinifier_Manager::$CurrentOptions)) return -1;
			else return 0;
		} elseif(isset($post['super_safe'])) {
			HTMLMinifier_Manager::$CurrentOptions = HTMLMinifier_Manager::get_presets('super_safe');
			if(update_option(HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'options',HTMLMinifier_Manager::$CurrentOptions)) return -1;
			else return 0;
		} elseif(isset($post['moderate'])) {
			HTMLMinifier_Manager::$CurrentOptions = HTMLMinifier_Manager::get_presets('moderate');
			if(update_option(HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'options',HTMLMinifier_Manager::$CurrentOptions)) return -1;
			else return 0;
		} elseif(isset($post['fully_optimised'])) {
			HTMLMinifier_Manager::$CurrentOptions = HTMLMinifier_Manager::get_presets('fully_optimised');
			if(update_option(HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'options',HTMLMinifier_Manager::$CurrentOptions)) return -1;
			else return 0;
		}
		
		// Loops through each of the post entries to check and sanitize them.
		foreach($post as $k => $v) {
			// Remove post entries that are not used by HTMLMinifier (e.g. "submit" value, nonces, user-hacked entries).
			if(!array_key_exists($k,HTMLMinifier_Manager::$Defaults)) unset($post[$k]);
			
			// Sanitizes post entries based on how they are used in HTMLMinifier.
			switch($k) {
			default:
				// Sanitization for other types will be added when HTMLMinifier starts using them.
				if(gettype(HTMLMinifier_Manager::$Defaults[$k]) === 'boolean')
					$post[$k] = 1; // Force value to 1, in case of user hijack. Value won't be there if the checkbox is unchecked, so no need to sanitize 0s.
				
				break;
			
			case 'compression_mode':
				// If the submitted compression mode is not valid, then force it to the default.
				if(!array_key_exists($v,HTMLMinifier::$CompressionMode) || gettype($v) !== 'string')
					$post[$k] = HTMLMinifier_Manager::$Defaults[$k];
			}
		}
		
		HTMLMinifier_Manager::$CurrentOptions = $post;
		return update_option( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'options',$post) ? 1 : 0;
	}
	
	public static function admin_menu() {
		$p = HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX;
		
		// Nonce for settings change.
		if(isset($_POST[$p.'settings_nonce']) && wp_verify_nonce($_POST[$p.'settings_nonce'],$p.'settings_nonce')) {			
			
			switch(self::save_options($_POST)) {
			case 1:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Settings have been successfully saved and updated.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case -1:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Your settings have been modified to the preset options.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case 0:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> No changes have been made.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated error is-dismissible';
				break;
			}
			
		} elseif(isset($_POST[$p.'feedback_nonce']) && wp_verify_nonce($_POST[$p.'feedback_nonce'],$p.'feedback_nonce')) { // Nonce for feedback.
			
			$curr_user = wp_get_current_user();
			$from_header = $curr_user->user_login . '<' . $curr_user->user_email . '>';
			
			$b = wp_mail(
				'Terresquall <mail@terresquall.com>',
				'Feedback from <'.get_bloginfo('name').'>',
				esc_html($_POST['feedback-text']),
				'Reply-To: ' . $from_header . PHP_EOL . 'From: ' . $from_header . PHP_EOL
			);
			
			if($b) {
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Your feedback has been sent. Thank you for taking your time out to help improve this plugin.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
			} else {
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> There has been an error and your feedback has <strong>not</strong> been sent.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated error is-dismissible';
				$GLOBALS[$p . 'feedback_error_message'] = esc_textarea($_POST['feedback-text']);
			}
		}
		
		add_options_page(__('HTML Minifier','html_minifier'), __('HTML Minifier','html_minifier'), 'manage_options', 'html-minifier-options', array('HTMLMinifier_Admin','display_settings'));
	}
	
	public static function admin_plugin_settings_link($links) { 
		$settings_link = '<a href="'.esc_url(admin_url('options-general.php?page=html-minifier-options')).'">'.__('Settings', 'html-minifier').'</a>';
		array_unshift( $links, $settings_link ); 
		return $links; 
	}
	
	public static function admin_enqueue_scripts() {
		wp_enqueue_style('jquery-ui-tooltip-css',HTML_MINIFIER__PLUGIN_URL.'views/jquery-ui-tooltip.css',false,PLUGIN_VERSION,false);
		wp_enqueue_script('jquery-ui-tooltip');
	}
	
	public static function display_settings() {
		$file = HTML_MINIFIER__PLUGIN_DIR.'views/settings-main.php';
		include $file;
	}
}
?>