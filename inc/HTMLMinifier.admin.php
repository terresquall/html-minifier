<?php
class HTMLMinifier_Admin {
	
	public static function init() {
		add_action('admin_menu', array('HTMLMinifier_Admin','admin_menu'));
		add_action( 'admin_enqueue_scripts', array('HTMLMinifier_Admin','admin_enqueue_scripts') );
		add_filter('plugin_action_links_html-minifier/html-minifier.php', array('HTMLMinifier_Admin', 'admin_plugin_settings_link' ));
	}
	
	public static function save_options($post) {
		
		// If restore default button is pressed, restore the default settings.
		if(isset($post['restore_default'])) {
			HTMLMinifier_Manager::$CurrentOptions = HTMLMinifier_Manager::$Defaults;
			if(update_option(HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX.'options',HTMLMinifier_Manager::$Defaults)) return -1;
			else return 0;
		}
		
		// Remove keys that are not in options.
		foreach($post as $k => $v)
			if(!array_key_exists($k,HTMLMinifier_Manager::$Defaults)) unset($post[$k]);
		
		HTMLMinifier_Manager::$CurrentOptions = $post;
		return update_option( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'options',$post) ? 1 : 0;
	}
	
	public static function admin_menu() {
		$p = HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX;
		
		// If the Wordpress nonce for settings is verified, process the changes in settings.
		if(isset($_POST[$p.'settings_nonce']) && wp_verify_nonce($_POST[$p.'settings_nonce'],$p.'settings_nonce')) {			
			switch(self::save_options($_POST)) {
			case 1:
				$GLOBALS[$p . 'settings_notice_message'] = 'Settings have been successfully saved and updated.';
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case -1:
				$GLOBALS[$p . 'settings_notice_message'] = 'Your settings have been restored to the defaults.';
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case 0:
				$GLOBALS[$p . 'settings_notice_message'] = 'No changes have been made.';
				$GLOBALS[$p . 'settings_notice_class'] = 'updated error is-dismissible';
				break;
			}
		}
		
		// If the Wordpress nonce for feedback is verified, send a mail to mail@terresquall.com.
		if(isset($_POST[$p.'feedback_nonce']) && wp_verify_nonce($_POST[$p.'feedback_nonce'],$p.'feedback_nonce')) {
			$curr_user = wp_get_current_user();
			$from_header = $curr_user->user_login . '<' . $curr_user->user_email . '>';
			
			$b = wp_mail(
				'Terresquall <mail@terresquall.com>',
				'Feedback from <'.get_bloginfo('name').'>',
				esc_html($_POST['feedback-text']),
				'Reply-To: ' . $from_header . PHP_EOL . 'From: ' . $from_header . PHP_EOL
			);
			
			if($b) {
				$GLOBALS[$p . 'settings_notice_message'] = 'Your feedback has been sent to us. Thank you for taking your time out to do this.';
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
			} else {
				$GLOBALS[$p . 'settings_notice_message'] = 'There has been an error and your feedback has <strong>not</strong> been sent. Please resend it.';
				$GLOBALS[$p . 'settings_notice_class'] = 'updated error is-dismissible';
				$GLOBALS[$p . 'feedback_error_message'] = $_POST['feedback-text'];
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
		$file = HTML_MINIFIER__PLUGIN_DIR.'views/settings.php';
		include $file;
	}
}
?>