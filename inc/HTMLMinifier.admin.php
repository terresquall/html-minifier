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
	
	const OPTIONS = 'html-minifier-options';
	
	public static function init() {
		
		// If it is a REST request, handle it here.
		if(preg_match('@^options\\-general\\.php\\?page\\='.preg_quote(self::OPTIONS).'\\&rest\\=([a-z]+)$@i',basename($_SERVER['REQUEST_URI']),$match)) {

			switch($_SERVER['REQUEST_METHOD']) {
			case 'GET': // retrieve
				$key = $match[1];
				if($key === 'presets') {
					echo json_encode(array(
						'super_safe' => HTMLMinifier::get_presets('super_safe'),
						'safe' => HTMLMinifier::get_presets('safe'),
						'moderate' => HTMLMinifier::get_presets('moderate'),
						'fully_optimised' => HTMLMinifier::get_presets('fully_optimised')
					));
				} else {
					if(array_key_exists($key,HTMLMinifier_Manager::$CurrentOptions))
						echo json_encode(HTMLMinifier_Manager::$CurrentOptions[$key]);
					else
						echo json_encode(HTMLMinifier_Manager::$CurrentOptions);
				}
				exit;
			case 'PUT': // update
			case 'POST': // create
				if(!is_user_logged_in()) exit; // Cannot save if you are not logged into wp_admin.
				$data = json_decode(file_get_contents("php://input"),true);
				
				//file_put_contents(__DIR__.'/'.$_SERVER['REQUEST_METHOD'].'.txt',json_encode($data,JSON_PRETTY_PRINT));
				self::save_options($data,$match[1]);
				exit;
			case 'DELETE': // delete.
			}
			exit;
		}
		
		// Otherwise add the other hooks.
		add_action('admin_menu', array('HTMLMinifier_Admin','admin_menu'));
		add_action('admin_enqueue_scripts', array('HTMLMinifier_Admin','admin_enqueue_scripts'));
		add_filter('plugin_action_links_html-minifier/html-minifier.php', array('HTMLMinifier_Admin', 'admin_plugin_settings_link' ));
	}
	
	// Takes the $_POST array and processes information in it that relates to HTML Minifier settings.
	public static function save_options($post, $option_type = 'core') {
		
		// Check if $option_type is valid. Otherwise, terminate this.
		if(!array_key_exists($option_type,HTMLMinifier_Manager::$Defaults)) return 0;
		
		// To handle post reset options when you do a non-AJAX query.
		if(isset($post['safe'])) {
			if(HTMLMinifier_Manager::update_options(HTMLMinifier_Manager::get_presets('safe'))) return -1;
			else return 0;
		} elseif(isset($post['super_safe'])) {
			if(HTMLMinifier_Manager::update_options(HTMLMinifier_Manager::get_presets('super_safe'))) return -1;
			else return 0;
		} elseif(isset($post['moderate'])) {
			if(HTMLMinifier_Manager::update_options(HTMLMinifier_Manager::get_presets('moderate'))) return -1;
			else return 0;
		} elseif(isset($post['fully_optimised'])) {
			if(HTMLMinifier_Manager::update_options(HTMLMinifier_Manager::get_presets('fully_optimised'))) return -1;
			else return 0;
		} elseif(isset($post['restore_defaults_manager'])) { // Restore defaults for manager settings.
			$options = HTMLMinifier_Manager::$CurrentOptions;
			$options['manager'] = HTMLMinifier_Manager::$ManagerDefaults;
			if(HTMLMinifier_Manager::update_options($options)) return -2;
			else return 0;
		} elseif(isset($post['restore_defaults_caching'])) { // Restore defaults for caching settings.
			$options = HTMLMinifier_Manager::$CurrentOptions;
			$options['caching'] = HTMLMinifier_Manager::$CachingDefaults;
			if(HTMLMinifier_Manager::update_options($options)) return -2;
			else return 0;
		} elseif(isset($post['clear_cache'])) {
			HTMLMinifier_Manager::clear_cache();
			return -3;
		}
		
		// Organise post fields into the appropriate arrays for saving.
		// Don't worry about sanitization, because update_options() below cleans up the extra post attributes.
		$options = HTMLMinifier_Manager::$CurrentOptions;
		
		// Determine which data array we are writing to.
		if(isset($post['submit-manager'])) $option_type = 'manager';
		
		foreach(HTMLMinifier_Manager::$Defaults[$option_type] as $k => $v) {
			if(array_key_exists($k,$post))
				$options[$option_type][$k] = $post[$k];
			elseif(gettype($v) === 'boolean')
				$options[$option_type][$k] = false;
		}
		
		return HTMLMinifier_Manager::update_options($options,true) ? 1 : 0;
	}
	
	public static function admin_menu() {
		
		// Listen for posts from HTML Minifier settings. Maybe can change it into an action hook in future.
		self::admin_post_update_settings($_POST);
		
		// Options page on the navigation bar.
		add_options_page(__('HTML Minifier','html-minifier'), __('HTML Minifier','html-minifier'), 'manage_options', self::OPTIONS, array('HTMLMinifier_Admin','display_settings'));
	}
	
	// Handles post request from HTML Minifier settings page (if any).
	private static function admin_post_update_settings($post) {
		
		$p = HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX;
		// Nonce for settings update.
		if(isset($post[$p.'settings_nonce']) && wp_verify_nonce($post[$p.'settings_nonce'],$p.'settings_nonce')) {			
			
			switch(self::save_options($post)) {
			case 1:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Settings have been successfully saved and updated.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case -1:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Your settings have been modified to the preset options.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case -2:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Your settings in this page have been reset to their defaults.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case -3:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> The HTML Minifier cache has been cleared.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				break;
			case 0:
				$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> No changes have been made.','html-minifier');
				$GLOBALS[$p . 'settings_notice_class'] = 'updated error is-dismissible';
				break;
			}
			
		} elseif(isset($post[$p.'feedback_nonce']) && wp_verify_nonce($post[$p.'feedback_nonce'],$p.'feedback_nonce')) { // Nonce for feedback.
			
			$curr_user = wp_get_current_user();
			$from_header = $curr_user->user_login . '<' . $curr_user->user_email . '>';
			
			$b = wp_mail(
				'Terresquall <mail@terresquall.com>',
				'Feedback from <'.get_bloginfo('name').'>',
				esc_html($post['feedback-text']),
				'Reply-To: ' . $from_header . PHP_EOL . 'From: ' . $from_header . PHP_EOL
			);
			
			if(isset($post['ajax'])) {
				if($b)
					_e('<strong>HTML Minifier:</strong> Your feedback has been sent. Thank you for taking your time out to help improve this plugin.','html-minifier');
				else
					_e('<strong>HTML Minifier:</strong> There has been an error and your feedback has <strong>not</strong> been sent.','html-minifier');
				exit;
			} else {
				if($b) {
					$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> Your feedback has been sent. Thank you for taking your time out to help improve this plugin.','html-minifier');
					$GLOBALS[$p . 'settings_notice_class'] = 'updated notice is-dismissible';
				} else {
					$GLOBALS[$p . 'settings_notice_message'] = __('<strong>HTML Minifier:</strong> There has been an error and your feedback has <strong>not</strong> been sent.','html-minifier');
					$GLOBALS[$p . 'settings_notice_class'] = 'updated error is-dismissible';
					$GLOBALS[$p . 'feedback_error_message'] = esc_textarea($post['feedback-text']);
				}
			}
		}
	}
	
	// Adds link to the settings page in the plugin manager page.
	public static function admin_plugin_settings_link($links) { 
		$settings_link = '<a href="'.esc_url(admin_url('options-general.php?page=html-minifier-options')).'">'.__('Settings', 'html-minifier').'</a>';
		array_unshift( $links, $settings_link ); 
		return $links; 
	}
	
	// Enqueue scripts for the settings page.
	public static function admin_enqueue_scripts($page) {
		if($page === 'settings_page_html-minifier-options') {
			wp_enqueue_style('jquery-ui-tooltip-css',HTML_MINIFIER__PLUGIN_URL.'views/lib/jquery-ui-tooltip.css',false,HTML_MINIFIER_PLUGIN_VERSION,false);
			wp_enqueue_style('settings-main-css',HTML_MINIFIER__PLUGIN_URL.'views/lib/settings.css',false,HTML_MINIFIER_PLUGIN_VERSION,false);
			wp_enqueue_script('jquery-ui-tooltip');
			wp_enqueue_script('html-minifier-settings',HTML_MINIFIER__PLUGIN_URL.'views/lib/settings.js',array('backbone'),HTML_MINIFIER_PLUGIN_VERSION,true);
		}
	}
	
	public static function display_settings() {
		$cache = glob(HTML_MINIFIER__PLUGIN_DIR . 'cache' . DIRECTORY_SEPARATOR . '*');
		$size = 0;
		foreach($cache as $file) {
			if(basename($file) === 'index.php') continue;
			$size += strlen(file_get_contents($file));
		}
		$file = HTML_MINIFIER__PLUGIN_DIR.'views/settings-main.php';
		include $file;
	}
}