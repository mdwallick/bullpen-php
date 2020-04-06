<?php
/**
 * Bullpen Jobs Board Plugin
 *
 * @link              http://bullhorntowordpress.com
 * @since             2.0.alpha
 * @package           Bullpen Jobs Board
 *
 * @wordpress-plugin
 * Plugin Name: Bullpen Jobs Board 
 * Plugin URI: http://bullhorntowordpress.com
 * Description: This plugin integrates Bullhorn jobs into a custom post type for front-end display on your wordpress site.
 * Version: 2.0.alpha
 * Author: Marketing Press
 * Author URI: http://marketingpress.com
 * License: GPL2
 */

// If this file is called directly, abort.
if ( !defined('ABSPATH') ) exit;

/**
 * Plugin Activation Setup
 */
function activate_bullpen_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bullpen-activator.php';
	Bullpen_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_bullpen_plugin' );

/**
 * Plugin Deactivation Setup
 */
function deactivate_bullpen_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bullpen-deactivator.php';
	Bullpen_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'deactivate_bullpen_plugin' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bullpen.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_bullpen() {
	$bullpen = new Bullpen();
	$bullpen->instance();
}
run_bullpen();