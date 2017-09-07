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
		self::$Defaults = array_merge(HTMLMinifier::$Defaults,array('minify_wp_admin' => false));
		
		load_plugin_textdomain('html-minifier', false, 'languages'); // Multi-language support text domain.
		self::ob_start(); // Start obfuscation.
	}
	
	public static function ob_start() {
		HTMLMinifier_Manager::init_wp_options(); // Creates an entry for this plugin in `wp_options`.
		
		// Check if we should minify wp-admin and stop if we should not.
		if(is_admin()) {
			if(!isset(self::$CurrentOptions['minify_wp_admin'])) return false;
			if(!self::$CurrentOptions['minify_wp_admin']) return false;
			self::$CurrentOptions['combine_javascript_in_script_tags'] = false; // Force false because it will break Wordpress otherwise.
		}
		
		HTMLMinifier::$Defaults = self::$CurrentOptions;
		return ob_start(array('HTMLMinifier','process')); // The Wordpress engine will close this ob.
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
	
	public static function init_wp_options() {
		$option = get_option( self::PLUGIN_OPTIONS_PREFIX . 'options');
		if($option === false) {
			$option = HTMLMinifier::$Defaults;
			add_option( self::PLUGIN_OPTIONS_PREFIX . 'options',$option);
		}
		
		self::$CurrentOptions = $option;
	}
	
	// Wrapper around the HTMLMinifier function, adding an additional option unique to Wordpress.
	public static function get_presets($type) {
		$r = HTMLMinifier::get_presets($type);
		if($r) return array_merge($r,array('minify_wp_admin' => false));
		return $r;
	}
	
	// Remove the entry for this plugin if it is uninstalled.
	public static function uninstall_wp_options() { delete_option(self::PLUGIN_OPTIONS_PREFIX . 'options'); }

}
?>