<?php
/*
This class interfaces with the HTMLMinifier class to minify the HTML source.
It also interfaces with Wordpress.

LAST_UPDATE: 20 December 2017
*/
class HTMLMinifier_Manager {
	
	const PLUGIN_OPTIONS_PREFIX = 'ts_htmlminifier_';
	const PLUGIN_OPTIONS_VERSION = 2;
	
	static $Defaults; // Set after class declaration.	
	static $CurrentOptions; // Current options for HTMLMinifier
	
	// These are variables that are outside of the HTMLMinifier class.
	static $ManagerDefaults = array(
		'minifier_version' => 'use_latest',
		'minify_wp_admin' => false,
		'minify_frontend' => true
	);
	
	// All the usable minifier versions, for documentation purposes.
	static $MinifierVersions = array(
		'use_latest' => 'inc/src/HTMLMinifier.php',
		'version2' => 'inc/src/HTMLMinifier-v2.php'
	);
	
	// Called with an action hook in the main plugin file.
	public static function init() {
		
		
		// Imports the correct HTMLMinifier version.
		//if(!isset(HTMLMinifier_Manager::$CurrentOptions['manager']['minifier_version']))
			//HTMLMinifier_Manager::$CurrentOptions['manager']['minifier_version'] = 'use_latest'; 
		require_once HTML_MINIFIER__PLUGIN_DIR . self::$MinifierVersions['use_latest'];
		define('HTML_MINIFIER_VERSION', HTMLMinifier::VERSION);
				
		// Get defaults from the HTMLMinifier class, then append an additional option to it.
		self::$Defaults = array(
			'core' => HTMLMinifier::$Defaults,
			'manager' => self::$ManagerDefaults,
			'version' => self::PLUGIN_OPTIONS_VERSION
		);
		
		HTMLMinifier_Manager::init_wp_options(); // Populates self::$CurrentOptions.
		
		//load_plugin_textdomain('html-minifier', false, 'languages'); // Multi-language support text domain.
		self::ob_start(); // Start obfuscation.
	}
	
	private static function ob_start() {
		
		// Check if we should minify WP-Admin and stop if we should not.
		if(is_admin()) {
			if(empty(self::$CurrentOptions['manager']['minify_wp_admin'])) return false;
			self::$CurrentOptions['core']['shift_script_tags_to_bottom'] = false; // Force false because it will break Wordpress otherwise.
		} else {
			if(isset(self::$CurrentOptions['manager']['minify_frontend']) && !self::$CurrentOptions['manager']['minify_frontend']) return false;
		}
		//var_dump(self::$CurrentOptions['core']);die;
		HTMLMinifier::$Defaults = self::$CurrentOptions['core'];
		return ob_start(array('HTMLMinifier','process')); // The Wordpress engine will close this ob.
	}
	
	// Retrieves saved settings from WP Database and makes sure the settings are correct.
	private static function init_wp_options() {
		$option = get_option( self::PLUGIN_OPTIONS_PREFIX . 'options');
		
		if($option === false) { // If there are no options, i.e. first-time use.
			$option = self::$Defaults;
			add_option( self::PLUGIN_OPTIONS_PREFIX . 'options',$option);
		} else { // Check if the options are upgraded, if not upgrade it.
			if(!isset($option['version']) || $option['version'] < self::PLUGIN_OPTIONS_VERSION) {
				require_once HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.upgrader.php';
				$option = HTMLMinifier_upgrader::run($option);
				HTMLMinifier_Manager::update_options($option,true); // Save the upgrade.
			}
		}

		self::$CurrentOptions = $option;
	}
	
	// This function hijacks the main query and injects a cached page if there is any.
	public static function posts_request($request, $query) {
		if ( is_home() && $query->is_main_query() ) {
			$page = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			//$key = 'homepage_query_cache_' . $page;
			//if(wp_cache_get( $key, 'cache_group' ))
			$request = null;
		}
		//var_dump($request,$query);
		//return null;
	}
	
	// Wrapper around the HTMLMinifier function, adding an additional option unique to Wordpress.
	// $save_options: If true, save the preset that we are getting.
	public static function get_presets($type) {
		$r = HTMLMinifier::get_presets($type);
		if($r) {
			return array(
				'core' => $r,
				'manager' => self::$CurrentOptions['manager'],
				'version' => self::PLUGIN_OPTIONS_VERSION
			);
		}
		return $r;
	}
	
	// This is for setting the currently existing HTML Minifier options.
	// Lets move the filters from HTMLMinifier_Admin here.
	public static function update_options($options, $sanitize_fields = false) {
		
		if($sanitize_fields) {
			
			// If options are not properly set up. Terminate.
			if(!(isset($options['core']) && isset($options['manager']))) {
				trigger_error('HTMLMinifier_manager::update_options(): An invalid $options array was passed.');
				return false;
			}
			
			// Loops through the core array to see if it is all right.
			foreach($options['core'] as $k => $v) {
				
				// Remove entries that are not used by HTMLMinifier (e.g. "submit" value, nonces, user-hacked entries).
				if(!array_key_exists($k,HTMLMinifier_Manager::$Defaults['core'])) {
					unset($options['core'][$k]);
					continue;
				}
				
				// Sanitizes for variables depending on how they are used.
				switch($k) {
				default:
					// Force non-array objects into 0s or 1s.
					if(gettype($options['core'][$k]) !== 'array')
						$options['core'][$k] = $options['core'][$k] ? 1 : 0;
					
					break;
				
				case 'compression_mode':
					// If the submitted compression mode is not valid, then force it to the default value.
					if(!array_key_exists($v,HTMLMinifier::$CompressionMode) || gettype($v) !== 'string')
						$options['core'][$k] = self::$Defaults['core'][$k];
				}
			}
			
			// Loops through the manager array to see if it is all right.
			foreach($options['manager'] as $k => $v) {
				
				// Remove entries that are not used by HTMLMinifier (e.g. "submit" value, nonces, user-hacked entries).
				if(!array_key_exists($k,HTMLMinifier_Manager::$Defaults['manager'])) {
					unset($options['manager'][$k]);
					continue;
				}
				
				// Sanitizes for variables depending on how they are used.
				switch($k) {
				default:
					// Force non-array objects into 0s or 1s.
					if(gettype($options['manager'][$k]) !== 'array')
						$options['manager'][$k] = $options['manager'][$k] ? 1 : 0;
					break;
					
				case 'minifier_version':
					if(!array_key_exists($v,self::$MinifierVersions))
						$options['manager'][$k] = self::$ManagerDefaults[$k];
				}
			}
			
			// Recreates the array so that only the 3 main categories remain.
			$options = array('core' => $options['core'], 'manager' => $options['manager'], 'version' => self::PLUGIN_OPTIONS_VERSION);
			
		}
		
		self::$CurrentOptions = $options;
		
		return update_option( HTMLMinifier_Manager::PLUGIN_OPTIONS_PREFIX . 'options',$options);
	}
	
	// Remove the entry for this plugin if it is uninstalled.
	public static function uninstall_wp_options() { delete_option(self::PLUGIN_OPTIONS_PREFIX . 'options'); }

}
?>