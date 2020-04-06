<?php

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Exception\ClientException;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Bullhorn_Connection' ) ) : 

class Bullhorn_Connection {

	/**
	 * Stores the settings for the connection, including the client ID, client
	 * secret, etc.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Stores the credentials we need for logging into the API (access token,
	 * refresh token, etc.).
	 *
	 * @var array
	 */
	protected $api_access;

	/**
	 * Stores the session variable we need in requests to Bullhorn.
	 *
	 * @var string
	 */
	protected $session;

	/**
	 * Stores the URL we need to make requests to (includes the corpToken).
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Array to cache the categories retrieved from bullhorn.
	 *
	 * @var array
	 */
	protected $categories = array();

	/**
	 * Constructor that just gets and sets the settings/access arrays.
	 *
	 * @return \Bullhorn_Connection
	 */
	public function __construct() {
		$this->settings = get_option( 'bullpen_settings' );
		$this->api_access = get_option( 'bullhorn_api_access' );
	}

	/**
	 * This should be the only method that is called externally, as it handles
	 * all processing of jobs from Bullhorn into WordPress.
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function sync() {

		// Refresh the token if necessary before doing anything
		//$this->refreshToken();

		$logged_in = $this->login();

		if ( ! $logged_in ) {
			return false;
		}

		wp_defer_term_counting( true );

		$this->getCategoriesFromBullhorn();

		$jobs = $this->getJobsFromBullhorn();
		$existing = $this->getExisting();

		$this->removeOld( $jobs );

		if ( count( $jobs ) ) {
			foreach ( $jobs as $job ) {
				if ( isset( $existing[$job->id] ) ) {
					$this->syncJob( $job, $existing[$job->id] );
				} else {
					$this->syncJob( $job );
				}
			}
		}

		wp_defer_term_counting( false );

		return true;
	}

	public function check_connection() {
		$settings = $this->settings;
		$api_access = $this->api_access;
		if ( isset( $api_access['access_token'], $api_access['refresh_token'] ) ) {
			return $this->refreshToken();
		} else {
			return $this->authorize();
		}
	}

	/**
	 * This is step 1 in setting up a connection with the Bullhorn API
	 */
	public function get_auth_code() {
		$settings = $this->settings;

		// A New Auth Code Means We Should Remove Old API Tokens/Refresh Tokens
		$settings['bullhorn_api_auth_code'] = '';
		update_option( 'bullpen_settings', $settings);
		delete_option( 'bullhorn_api_access' );

		// Refresh the instance arrays
		$this->settings = get_option('bullhorn_api_auth_code');
		$this->api_access = get_option('bullhorn_api_access');

		$url = 'https://auth.bullhornstaffing.com/oauth/authorize';
		$params = array(
			'client_id'     => $settings['bullhorn_api_client_id'],
			'response_type' => 'code',
			'username'      => $settings['bullhorn_api_username'],
			'password'      => $settings['bullhorn_api_password'],
			'action'        => 'Login',
		);
		wp_redirect( $url . '?' . http_build_query( $params ), 302 );
		exit();
	}

	/**
	 * This is step 2 in setting up a connection with the Bullhorn API
	 */
	private function authorize() {
		$settings = $this->settings;
		$api_access = $this->api_access;

		// If the API client settings are not present, return a message.
		if  ( ! isset( $settings['bullhorn_api_client_id'], 
			           $settings['bullhorn_api_client_secret'], 
			           $settings['bullhorn_api_auth_code'] ) ||
		     ( empty ( $settings['bullhorn_api_client_id'] ) || 
	           empty ( $settings['bullhorn_api_client_secret'] ) || 
	           empty ( $settings['bullhorn_api_auth_code'] ) ) ) {

			if ( isset( $settings['bullhorn_api_client_id'], $settings['bullhorn_api_client_secret'] ) && ! ( empty( $settings['bullhorn_api_client_id'] ) || empty( $settings['bullhorn_api_client_id'] ) ) ) {
				return 'Get an authorization code';
			}
			return 'Input your API credentials to use to establish a connection.';
		}

		// Since they are set, attempt to authorize.
		$url = 'https://auth.bullhornstaffing.com/oauth/token';
		$params = array(
			'grant_type'    => 'authorization_code',
			'code'          => $settings['bullhorn_api_auth_code'],
			'client_id'     => $settings['bullhorn_api_client_id'],
			'client_secret' => $settings['bullhorn_api_client_secret'],
		);

		$response = wp_remote_post( $url . '?' . http_build_query( $params ) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( 'authorize() failed: ' . $error_message );
			return 'The Bullhorn server responded with an error ... <br /><strong>' . $error_message . '</strong>';
		} else {
			$response = json_decode( $response['body'], true );
			if ( isset( $response['access_token'] ) ) {
				$response['expires'] = time() + 540; // 540 = 9 minutes, 1 minute of buffer
				update_option( 'bullhorn_api_access', $response );
				$this->api_access = get_option( 'bullhorn_api_access' );
				return 'Congratulations, you\'re connected to the Bullhorn system on a new authorization token.';
			} elseif ( isset( $response['error'] ) ) {
				return 'The Bullhorn server responded with an error ... <br /><strong>' . $response['error_description'] . '</strong>';
			} else {
				return 'Unknown error';
			}
		}
	}

	/**
	 * This is step 3 in setting up a connection with the Bullhorn API
	 * 
	 * This allows our application to log into the API so we can get the session
	 * and corpToken to use in subsequent requests.
	 *
	 * @throws Exception
	 * @return boolean
	 */
 	public function login() {
		$timeout = 0;
		do {
			$url = 'https://rest.bullhornstaffing.com/rest-services/login?version=*&access_token=' . $this->api_access['access_token'];
			$response = wp_remote_get( $url, array( 'timeout' => 180 ));
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				error_log( 'login() failed: ' . $error_message );
				$this -> refreshToken();
			} else {
				$body = json_decode( $response['body'] );
				if ( isset( $body->BhRestToken ) ) {
					$this->session = $body->BhRestToken;
					$this->url = $body->restUrl;
					return true;
				} else {
					// no BhRestToken returned, so our access token is most likely expired
					// so just refresh the token
					$this -> refreshToken();
				}
			}
		} while ( $timeout < 3 );
	}

	/**
	 * Every 10 minutes we need to refresh our access token for continued access
	 * to the API. We first determine if we need to refresh, and then we need to
	 * request a new token from Bullhorn if our current one has expired.
	 *
	 * @return boolean
	 */
	public function refreshToken() {
		$settings = $this->settings;
		$api_access = $this->api_access;
		$url = 'https://auth.bullhornstaffing.com/oauth/token';
		$params = array(
			'grant_type'    => 'refresh_token',
			'refresh_token' => $this->api_access['refresh_token'],
			'client_id'     => $this->settings['bullhorn_api_client_id'],
			'client_secret' => $this->settings['bullhorn_api_client_secret'],
		);
		$response = wp_remote_post( $url . '?' . http_build_query( $params ) );
		
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( 'refreshToken() failed: ' . $error_message );
			return $this -> authorize();
		} else {
			$response = json_decode( $response['body'], true );
			if ( isset( $response['access_token'] ) ) {
				$response['expires'] = time() + 540; // 540 = 9 minutes, 1 minute of buffer
				update_option( 'bullhorn_api_access', $response );
				$this->api_access = get_option( 'bullhorn_api_access' );
				return 'Congratulations, you\'re connected to the Bullhorn system.';
			} else {
				// re-auth with our current code since our refresh token has expired
				return $this->authorize();
			}
		}
	}

	/**
	 * This retreives all available categories from Bullhorn.
	 *
	 * @return array
	 */
	private function getCategoriesFromBullhorn() {
		$url = $this->url . 'options/Category';
		$params = array(
			'BhRestToken' => $this->session,
		);

		$response = $this->request( $url . '?' . http_build_query( $params ) );

		$body = json_decode( $response['body'] );

		if ( isset( $body->data ) ) {
			foreach ( $body->data as $category ) {
				wp_insert_term( $category->label, 'bullpen-categories' );
			}
		}

		return array();
	}

	/**
	 * Gets the description field as chosen by the user in settings.
	 *
	 * @return string
	 */
	private function getDescriptionField()
	{
		if ( isset($this->settings['description_field']) && !empty($this->settings['description_field'])) {
			$description = $this->settings['description_field'];
		} else {
			$description = 'description';
		}

		return $description;
	}

	/**
	 * This retreives all available jobs from Bullhorn.
	 *
	 * @return array
	 */
	private function getJobsFromBullhorn() {
		// Use the specified description field if set, otherwise the default
		$description = $this->getDescriptionField();

		$start = 0;
		$page = 100;
		$jobs = array();
		while ( true ) {
			$url = $this->url . 'query/JobOrder';
			$params = array(
				'BhRestToken' => $this->session,
				//'fields' => '*',
				'fields' => 'id,title,dateAdded,categories,address,salary,assignedUsers(email),' . $description,
				// 'fields' => 'id,title,' . $description . ',dateAdded,categories,address,salary,correlatedCustomText4,customText4',
				'where' => 'isPublic=1 AND isOpen=true AND isDeleted=false',
				'count' => $page,
				'start' => $start,
			);

			if ( isset( $this->settings['client_corporation'] ) and ! empty( $this->settings['client_corporation'] ) ) {
				$ids = explode(',', $this->settings['client_corporation']);
				$ids = array_map('trim', $ids);

				$params['where'] .= ' AND (clientCorporation.id=' . implode(' OR clientCorporation.id=', $ids) . ')';
			}

			$response = $this->request( $url . '?' . http_build_query( $params ) );
			$body = json_decode( $response['body'] );
			
			if ( isset( $body->data ) ) {
				$start += $page;

				$jobs = array_merge( $jobs, $body->data );

				if ( count( $body->data ) < $page ) {
					break;
				}
			} else {
				break;
			}
		}

		// echo "<pre>";
		// print_r($jobs);
		// echo "</pre>";
		// exit;

		return $jobs;
	}

	/**
	 * This will take a job object from Bullhorn and insert it into WordPress
	 * with the proper fields, custom fields, and taxonomy relationships. If
	 * the job already exists in WordPress it simply updates the fields.
	 *
	 * @return boolean
	 */
	private function syncJob( $job, $id = null ) {
		$description = $this->getDescriptionField();

		$post = array(
			'post_title'   => $job->title,
			'post_content' => $job->{$description},
			'post_type'    => 'bullpen-jobs',
			'post_status'  => 'publish',
			'post_date'    => date( 'Y-m-d H:i:s', $job->dateAdded / 1000 ),
		);

		if ( $id ) {
			$post['ID'] = $id;

			$id = wp_update_post( $post );
		} else {
			$id = wp_insert_post( $post );
		}

		$address = (array) $job->address;
		unset( $address['countryID'] );

		// get the assigned user(s) for the job for email notifications later
		// when a new job submission/candidate comes in
		$assignees = array();
		$owners = array();
		$assigned_users = $job -> assignedUsers -> data;
		foreach ( $assigned_users as $assignee ) {
			array_push($assignees, $assignee -> email);
			array_push($owners, $assignee -> id);
		}
		$owner_id = array_shift( $owners );
		$assignees = implode( ',', $assignees );
		
		$custom_fields = array(
			'bullhorn_job_id' => $job -> id,
			'bullhorn_job_address' => implode( ' ', $address ),
			'bullhorn_job_city' => $job -> address -> city,
			'bullhorn_job_state' => $job -> address -> state,
			'bullhorn_job_assignee' => $assignees,
			'bullhorn_job_owner' => $owner_id,
		);
		
		foreach ( $custom_fields as $key => $val ) {
			//add_post_meta( $id, $key, $val, true );
			update_post_meta( $id, $key, $val );
		}

		$categories = array();
		foreach ( $job->categories->data as $category ) {
			$category_id = $category->id;

			// Check to see if this category name has been cached already
			if ( isset( $this->categories[$category_id] ) ) {
				$categories[] = $this->categories[$category_id];
			} else {
				$url = $this->url . 'entity/Category/' . $category_id;
				$params = array('BhRestToken' => $this->session, 'fields' => 'id,name');
				$response = $this->request( $url . '?' . http_build_query( $params ) );

				$category = json_decode( $response['body'] );
				if ( isset( $category->data->name ) ) {
					$categories[] = $category->data->name;

					// Cache this category in an array
					$this->categories[$category_id] = $category->data->name;
				}
			}
		}

		wp_set_object_terms( $id, $categories, 'bullpen-categories' );
		$locations = array();
		$locations[] = $job->address->city . ", " . $job->address->state;
		wp_set_object_terms( $id, $locations, 'bullpen-locations' );

		return true;
	}

	/**
	 * Before we start adding in new jobs, we need to delete jobs that are no
	 * longer in Bullhorn.
	 *
	 * @param  array   $jobs
	 * @return boolean
	 */
	private function removeOld( $jobs ) {
		$ids = array();
		foreach ( $jobs as $job ) {
			$ids[] = $job->id;
		}

		$jobs = new WP_Query( array(
			'post_type'      => 'bullpen-jobs',
			'post_status'    => 'any',
			'posts_per_page' => 500,
			'meta_query'     => array(
				array(
					'key'     => 'bullhorn_job_id',
					'value'   => $ids,
					'compare' => 'NOT IN',
				),
			),
		) );

		if ( $jobs->have_posts() ) {
			while ( $jobs->have_posts() ) {
				$jobs->the_post();

				// Don't trash post, actually delete it
				wp_delete_post( get_the_ID(), true );
			}
		}

		return true;
	}

	/**
	 * Gets an array of IDs for existing jobs in the WordPress CPT.
	 *
	 * @return array
	 */
	private function getExisting() {
		global $wpdb;

		$posts = $wpdb->get_results( "SELECT $wpdb->posts.id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta JOIN $wpdb->posts ON $wpdb->posts.id = $wpdb->postmeta.post_id WHERE meta_key = 'bullhorn_job_id'", ARRAY_A );

		$existing = array();
		foreach ($posts as $post)
		{
			$existing[$post['meta_value']] = $post['id'];
		}

		return $existing;
	}

	/**
	 * Wrapper around wp_remote_get() so any errors are reported to the screen.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function request( $url ) {
		$response = wp_remote_get( $url, array( 'timeout' => 180 ) );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		return $response;
	}

}

endif;
