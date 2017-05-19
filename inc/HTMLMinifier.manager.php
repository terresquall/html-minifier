<?php
/*
This class interfaces with the HTMLMinifier class to minify the HTML source.
It also interfaces with Wordpress.
*/
require_once HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.php';
class HTMLMinifier_Manager {
	
	const PLUGIN_OPTIONS_PREFIX = 'ts_htmlminifier_';
	
	static $Defaults; // Set after class declaration.	
	static $CurrentOptions; // Current options for HTMLMinifier
	
	// Called with an action hook in the main plugin file.
	public static function init() {
		// Get defaults from the HTMLMinifier class, then append an additional option to it.
		self::$Defaults = array_merge(HTMLMinifier::$Defaults,array(
			'minify_wp_admin' => false
		));
		
		// Start obfuscation.
		self::ob_start();
	}
	
	public static function ob_start() {
		HTMLMinifier_Manager::init_wp_options(); // Creates an entry for this plugin in `wp_options`.
		
		// Check if we should minify wp-admin and stop if we should not.
		if(is_admin()) {
			if(!isset(self::$CurrentOptions['minify_wp_admin'])) return false;
			if(!self::$CurrentOptions['minify_wp_admin']) return false;
		}
		
		HTMLMinifier::$Defaults = self::$CurrentOptions;
		return ob_start(array('HTMLMinifier','process')); // The Wordpress engine will close this ob.
	}
	
	// This is for flushing through all the ob levels. NOT USED.
	public static function ob_end($options = null,$returnOutput = false) { 
		
		$final = '';

		// We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
		// that buffer's output into the final output.
		$levels = ob_get_level();
		
		for($i = 0; $i < $levels; $i++) $final .= ob_get_clean();
		
		$final = self::process($final,$options);
		
		if($returnOutput) return $final;
		echo $final;
	}
	
	public static function init_wp_options() {
		$option = get_option( self::PLUGIN_OPTIONS_PREFIX . 'options');
		if($option === false) {
			$option = HTMLMinifier::$Defaults;
			add_option( self::PLUGIN_OPTIONS_PREFIX . 'options',$option);
		} /*else {
			// If the option is already there, add keys to current options that are not yet in the database.
			foreach(self::$Defaults as $k => $v) {
				if(!array_key_exists($k,HTMLMinifier_Manager::$CurrentOptions))
					self::$CurrentOptions[$k] = HTMLMinifier_Manager::$Defaults[$k];
			}
		}*/
		
		self::$CurrentOptions = $option;
		
		
	}
	
	// Remove the entry for this plugin if it is uninstalled.
	public static function uninstall_wp_options() { delete_option(self::PLUGIN_OPTIONS_PREFIX . 'options'); }

}
?>