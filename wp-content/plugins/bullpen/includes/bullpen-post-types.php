<?php
/**
 * Taxonomy Functions
 *
 * @package     Bullpen
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, MarketingPress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Bullpen_Post_Types' ) ) : 

/**
 * Registers and sets up the Downloads custom post type
 *
 * @since 1.0
 * @return void
 *
 */
class Bullpen_Post_Types {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );
	}

	public function init() {
		$this->slug = $this->getSlug();
		$this->create_jobs_post_type();
	}

	private $slug;

	private function getSlug() {
		$settings = (array) get_option( 'bullpen_settings' );
		return isset( $settings['listings_page'] ) ? trim( $settings['listings_page'], '/' ) : 'bullpen-jobs';
	}

	/**
	 * Filters the content for single job posts to insert a customizable link
	 * to the form where the user can submit their resume.
	 */
	public function the_content( $content = null ) {
		$settings = (array) get_option( 'bullpen_settings' );
		if ( isset( $settings['form_page'] ) and 'bullpen-jobs' === get_post_type() and is_singular( 'bullpen-jobs' ) ) {
			$job_id = get_post_meta( get_the_id(), 'bullhorn_job_id', true );
			$owner_id = get_post_meta( get_the_id(), 'bullhorn_job_owner', true );
			
			// check to see if we have a source
			$source = 'Website Application';
			if ( isset( $_GET['source'] ) ) {
				$source = $_GET['source'];
			}
			
			if ( is_single() ) {
				$content .= '<a class="button job-submit-resume" href="' . get_permalink( $settings['form_page'] ) . '?position=' . urlencode( get_the_title() ) . '&job=' . urlencode( $job_id ) . '&source=' . urlencode( $source ) . '&owner=' . $owner_id . '">Submit Resume</a>';
			} else {
				$content .= '<a class="button job-submit-resume" href="' . get_permalink( $settings['form_page'] ) . '?position=' . urlencode( get_the_title() ) . '&job=' . urlencode( $job_id ) . '&source=' . urlencode( $source ) . '&owner=' . $owner_id . '">Apply Now</a>';
			}
		}
		return $content;
	}

	public function comments_open( $open, $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( $post_type === 'bullpen-jobs' ) {
			return false;
		}
		return $open;
	}

	private function create_jobs_post_type() {

		$labels = array(
			'name'               => 'Open Positions',
			'singular_name'      => 'Open Position',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Job',
			'edit_item'          => 'Edit Job',
			'new_item'           => 'New Job',
			'all_items'          => 'All Positions',
			'view_item'          => 'View Job',
			'search_items'       => 'Search Jobs',
			'not_found'          => 'No jobs found',
			'not_found_in_trash' => 'No jobs found in Trash',
			'parent_item_colon'  => '',
			'menu_name'          => 'Open Positions'
		);
		$rewrite = array(
			'slug'               => $this->slug,
			'with_front'         => false,
			'pages'              => true,
			'feeds'              => true,
		);
		$supports = array(
			'title',
			'editor',
			'custom-fields',
		);
		$args = array(
			'label'              => 'Open Positions',
			'description'        => 'Jobs Listing from Bullhorn Integration',
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-nametag',
			'query_var'          => true,
			'rewrite'            => $rewrite,
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => $supports,
		);
		register_post_type( 'bullpen-jobs', $args );
	}

}

endif;

$bullhorn_post_types = new Bullpen_Post_Types;
