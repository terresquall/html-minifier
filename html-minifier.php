<?php
/**
 * @package HTMLMinifier
 */
/*
Plugin Name: HTML Minifier
Plugin URI: http://www.terresquall.com/web/html-minifier/
Description: Minifies the HTML output of your Wordpress site, without any caveats. There are no embedded ads, no feature that is pay-to-use, no tricky money-making mechanisms. Just a source-minifying tool that tries to be as simple and useful as possible.
Version: 1.0.5
Dated: 02/07/2017
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

define( 'HTML_MINIFIER__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HTML_MINIFIER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HTML_MINIFIER__MINIMUM_WP_VERSION', '3.2' ); // Probably not used at the moment.

require_once HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.manager.php';
define('HTML_MINIFIER_VERSION', HTMLMinifier::VERSION);
define('HTML_MINIFIER_PLUGIN_VERSION', '1.0.5');
define('HTML_MINIFIER_PLUGIN_VERSION_DATE', '2 July 2017');

add_action('init',array('HTMLMinifier_Manager','init'));
register_uninstall_hook(__FILE__,array('HTMLMinifier_Manager','uninstall_wp_options'));

if(is_admin()) {
	require_once( HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.admin.php' );
	add_action( 'init', array( 'HTMLMinifier_Admin', 'init' ) );
}

?>