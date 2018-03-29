<?php
/**
 * @package HTMLMinifier
 */
/*
Plugin Name: HTML Minifier
Plugin URI: http://www.terresquall.com/web/html-minifier/
Description: Minifies and caches the HTML output of your Wordpress site, along with accompanying CSS and Javascript files. There are no embedded ads, no feature that is pay-to-use, no tricky money-making mechanisms. Just a source-minifying tool that is as simple and useful as possible.
Version: 2.1.1
Dated: 29/03/2018
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

define('HTML_MINIFIER_PLUGIN_VERSION', '2.1.1');
define('HTML_MINIFIER_PLUGIN_VERSION_DATE', '29 March 2018');

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