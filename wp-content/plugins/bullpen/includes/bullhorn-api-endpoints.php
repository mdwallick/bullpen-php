<?php
/**
 * Wordpress Endpoints for Bullhorn API Integration
 *
 * @package     Bullpen
 * @subpackage  Bullhorn
 * @copyright   Copyright (c) 2016, MarketingPress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0.0
 */

if ( !defined('ABSPATH') ) exit;

if ( !class_exists( 'Bullhorn_API_Endpoints' ) ) : 

class Bullhorn_API_Endpoints {

	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_action( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'parse_request', array($this, 'sniff_requests' ) );
	}

	/**
	 * Initialize the rewrite rule
	 *
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_rule('^api/bullhorn/application?([^/]+)','index.php?__api=1&endpoint=application&$matches[1]','top');
		add_rewrite_rule('^api/bullhorn/resume?([^/]+)','index.php?__api=1&endpoint=resume&$matches[1]','top');
		add_rewrite_rule('^api/bullhorn?([^/]+)','index.php?__api=1&endpoint=authorize&$matches[1]','top');
	}

	/**
	 * Set new query vars
	 *
	 * @return all Query Vars
	 */
	public function add_query_vars($vars) {
		$vars[] = '__api';
		$vars[] = 'endpoint';

		return $vars;
	}

	/**
	 * Check for Bullhorn API requests
	 *
	 * @return void
	 */
	public function sniff_requests() {

		global $wp;
		if ( isset( $wp->query_vars[ '__api' ] ) && isset( $wp->query_vars[ 'endpoint' ] ) ) { 

			switch( $wp->query_vars[ 'endpoint'] ) {

				case 'authorize':

					if ( $_GET['code'] ) {
						$get = 'code=' . esc_html( $_GET['code'] );
					} elseif ( isset( $_GET['error_description'] ) ) {
						$get = http_build_query( array( 'error_description' => $_GET['error_description'] ));
					}
					$url = BULLPEN_PLUGIN_ADMIN_URL . '&' . $get;
					
					// if ( current_user_can( 'manage_options' ) ) {

						wp_redirect( $url, '302' );

						exit;

					// } else {

					// 	$response = array(
					// 		'status' => 401,
					// 		'error'	=> 'The endpoint you are trying to reach requires a logged in user.'
					// 	);

					// }
			
					exit(print_r($response));

					break;

				case 'resume':
					if ( class_exists( 'Bullhorn_Extended_Connection' ) )
						bullpen_parse_resume();

					exit;

					break;

				case 'application':
					if ( class_exists( 'Bullhorn_Extended_Connection' ) )
						bullpen_parse_application();

					exit;

					break;

				default:

					$response = array(
						'status' => 404,
						'error' => 'The endpoint you are trying to reach does not exist.'
					);

					exit(print_r($response));
					
			}

		}

	}

} 

endif;

$bullhorn_api_endpoints = new Bullhorn_API_Endpoints;