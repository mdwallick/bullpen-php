<?php
/*
Plugin Name: Bullpen Jobs Board 
Plugin URI: http://bullhorntowordpress.com
Description: This plugin integrates Bullhorn jobs into a custom post type for front-end display on your wordpress site.
Version: 2.0.alpha
Author: Marketing Press
Author URI: http://marketingpress.com
License: GPL2
*/

/*
Copyright 2013 - 2016 Marketing Press, an Arizona, USA LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !defined('ABSPATH') ) exit;

if ( !class_exists( 'Bullpen' ) ) : 

class Bullpen {

	/**
	 * Instance of Bullpen
	 *
	 * @var instance
	 * @since 2.0.0
	 *
	 */
	private static $instance;

    /**
     * Instance Builder
     *
     * No need to define globals all over the place. Allows for just one 
     * instance of class.
     *
     * @since 2.0.0
     * @static 
     * @return The single instance of Bullpen
     *
     * @uses Bullpen::setup_contants() 		Setup the required constants
     * @uses Bullpen::load_includes()		Load included files
     * @uses Bullpen::add_endpoint()		Add API Endpoint
     */
	public static function instance() {
		if ( !( isset( self::$instance ) ) && !( self::$instance instanceof Bullpen ) ) :
			self::$instance = new Bullpen;
			self::$instance->setup_constants();
			self::$instance->includes();
		endif;
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 2.0.0
	 * @access protected
	 * @return void
	 *
	 * @uses _doing_it_wrong() from WPCore
	 *
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh? You may not clone an instance of Bullpen.', 'bullpen' ), BULLPEN_VERSION );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since 2.0.0
	 * @access protected
	 * @return void
	 *
	 * @uses _doing_it_wrong() from WPCore
	 *
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh? You may not unserialize an instance of Bullpen', 'bullpen' ), BULLPEN_VERSION );
	}

	/**
	 * PLUGIN CONSTANTS
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 *
	 */
	private function setup_constants() {

		global $wpdb;

		// Plugin Version
		if (!defined('BULLPEN_VERSION')) {
			define('BULLPEN_VERSION', '2.0.alpha');
		}

		// Plugin Folder Path
		if (!defined('BULLPEN_PLUGIN_DIR')) {
			define('BULLPEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
		}

		// Plugin Folder URL
		if (!defined('BULLPEN_PLUGIN_URL')) {
			define('BULLPEN_PLUGIN_URL', plugin_dir_url(__FILE__));
		}

		// Plugin Root File
		if (!defined('BULLPEN_PLUGIN_FILE')) {
			define('BULLPEN_PLUGIN_FILE', __FILE__);
		}

		// Plugin API Callback
		if (!defined('BULLPEN_BULLHORN_API_CALLBACK')) {
			define('BULLPEN_BULLHORN_API_CALLBACK', get_site_url() . '/api/bullhorn/');
		}

		// Plugin Options Page
		if (!defined('BULLPEN_PLUGIN_ADMIN_URL')) {
			define('BULLPEN_PLUGIN_ADMIN_URL', admin_url() . 'options-general.php?page=bullpen');
		}
	}

	/**
	* INCLUDE PLUGIN FILES
	*
	* @access private
	* @since 2.0.0
	* @return void
	*
	*/

	private function includes() {
		global $bullpen_settings;
		require_once BULLPEN_PLUGIN_DIR . '../vendor/autoload.php';
		require_once BULLPEN_PLUGIN_DIR . 'admin/settings.php';
		require_once BULLPEN_PLUGIN_DIR . 'class-bullhorn-connection.php';
		require_once BULLPEN_PLUGIN_DIR . 'class-bullpen-sort.php';
		require_once BULLPEN_PLUGIN_DIR . 'bullhorn-api-endpoints.php';
		require_once BULLPEN_PLUGIN_DIR . 'bullpen-post-types.php';
		require_once BULLPEN_PLUGIN_DIR . 'bullpen-taxonomies.php';
		require_once BULLPEN_PLUGIN_DIR . 'bullpen-cron.php';
		require_once BULLPEN_PLUGIN_DIR . 'shortcodes.php';
	}

}

endif;