<?php
/**
 * @package HTMLMinifier
 */
/*
Plugin Name: HTML Minifier
Plugin URI: http://www.terresquall.com/web/html-minifier/
Description: Provides a variety of optimisation options (e.g. minification, caching, code reorganisation) for your site's source code to help meet today's web performance standards.
Version: 2.2.4
Dated: 27/06/2018
Author: Terresquall
Author URI: http://www.terresquall.com/
License: GPLv2 or later
Text Domain: html-minifier
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin; not much I can do when called directly.";
	exit;
}

define('HTML_MINIFIER__PLUGIN_URL', plugin_dir_url(__FILE__) );
define('HTML_MINIFIER__PLUGIN_DIR', plugin_dir_path(__FILE__) );
define('HTML_MINIFIER__CACHE_DIR', HTML_MINIFIER__PLUGIN_DIR . 'cache/' );
define('HTML_MINIFIER__MINIMUM_WP_VERSION', '3.6.4'); // Probably not used at the moment.

// Bear essentials
require_once HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.manager.php';

define('HTML_MINIFIER_PLUGIN_VERSION', '2.2.4');
define('HTML_MINIFIER_PLUGIN_VERSION_DATE', '27 June 2018');

add_action('init',array('HTMLMinifier_Manager','init'));
//add_filter('posts_request',array('HTMLMinifier_Manager','posts_request'),10,2); // Hijack the loop for the main cache.
register_activation_hook(__FILE__,array('HTMLMinifier_Manager','activate'));
register_deactivation_hook(__FILE__,array('HTMLMinifier_Manager','deactivate'));
register_uninstall_hook(__FILE__,array('HTMLMinifier_Manager','uninstall'));

if(is_admin()) {
	require_once HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.admin.php';
	add_action( 'init', array( 'HTMLMinifier_Admin', 'init' ) );
}
?>