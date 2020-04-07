<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      2.0.0
 *
 * @package    Bullpen
 * @subpackage Bullpen/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    Bullpen
 * @subpackage Bullpen/includes
 * 
 */
class Bullpen_Activator
{
	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    2.0.0
	 */
	public static function activate($wp = '4.4', $php = '5.4')
	{

		global $wp_version;

		// Checks for PHP version
		if (version_compare(PHP_VERSION, $php, '<'))
			$flag = 'PHP';

		// Checks for WP version
		elseif (version_compare($wp_version, $wp, '<'))
			$flag = 'WordPress';

		// Passes check, let plugin load.
		else
			flush_rewrite_rules();
		return;

		// Failed check, return error.

		$version = 'PHP' == $flag ? $php : $wp;

		deactivate_plugins(basename(__FILE__));

		die(sprintf('<p>We\'re sorry, but the <strong>Bullhorn Jobs Board</strong> plugin requires %s version %s or greater. Please contact your web hosting provider to update your server to PHP 5.4 or greater.</p>', $flag, $version, get_admin_url()));

		return false;
	}
}
