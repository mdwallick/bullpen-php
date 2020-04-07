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
class Bullpen_Deactivator
{
	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    2.0.0
	 */
	public static function deactivate()
	{
		wp_clear_scheduled_hook('bullhorn_hourly_event');
		add_action('admin_init', array('deactivate_child_plugins'));
		flush_rewrite_rules();
	}

	protected function deactivate_child_plugins()
	{
		deactivate_plugins('bullpen-candidate-processor/functions.php');
	}
}
