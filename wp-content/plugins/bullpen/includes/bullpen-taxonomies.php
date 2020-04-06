<?php
/**
 * Post Type Functions
 *
 * @package     Bullpen
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, MarketingPress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Bullpen_Taxonomies' ) ) : 

/**
 * Registers and sets up the Bullpen custom post taxonomy
 *
 * @since 1.0
 * @return void
 *
 */
class Bullpen_Taxonomies {

	private $slug;

	public function __construct() {
		add_action( 'init', array(&$this, 'init') );
	}

	public function init() {
		$this->slug = $this->getSlug();
		$this->create_jobs_categories_taxonomy();
		$this->create_jobs_locations_taxonomy();

	}

	private function getSlug() {
		$settings = (array) get_option( 'bullpen_settings' );
		return isset( $settings['listings_page'] ) ? trim( $settings['listings_page'], '/' ) : 'bullpen-jobs';
	}


	private function create_jobs_categories_taxonomy() {
		$settings = (array) get_option( 'bullpen_settings' );

		$labels = array(
			'name'                       => 'Categories',
			'singular_name'              => 'Category',
			'menu_name'                  => 'Categories',
			'all_items'                  => 'All Categories',
			'parent_item'                => '',
			'parent_item_colon'          => '',
			'new_item_name'              => 'New Category',
			'add_new_item'               => 'Add New Category',
			'edit_item'                  => 'Edit Category',
			'update_item'                => 'Update Category',
			'separate_items_with_commas' => 'Separate each with commas',
			'add_or_remove_items'        => 'Add or Remove',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular',
			'search_items'               => 'Search',
			'not_found'                  => 'Not Found',
		);
		$rewrite = array(
			'slug'                       => $this->slug . '/categories',
			'with_front'                 => false,
			'hierarchical'               => false,
		);
		$args = array(
			'labels'                     => $labels,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
			'rewrite'                    => $rewrite,
		);
		register_taxonomy( 'bullpen-categories', array( 'bullpen-jobs' ), $args );
	}

	private function create_jobs_locations_taxonomy() {
		$settings = (array) get_option( 'bullpen_settings' );
		$labels = array(
			'name'                       => 'Locations',
			'singular_name'              => 'Locations',
			'menu_name'                  => 'Locations',
			'all_items'                  => 'All Locations',
			'parent_item'                => '',
			'parent_item_colon'          => '',
			'new_item_name'              => 'New Locations',
			'add_new_item'               => 'Add New Locations',
			'edit_item'                  => 'Edit Locations',
			'update_item'                => 'Update Locations',
			'separate_items_with_commas' => 'Separate each with commas',
			'add_or_remove_items'        => 'Add or Remove',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular',
			'search_items'               => 'Search',
			'not_found'                  => 'Not Found',
		);
		$rewrite = array(
			'slug'                       => $this->slug . '/locations/',
			'with_front'                 => false,
			'hierarchical'               => false,
		);
		$args = array(
			'labels'                     => $labels,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
			'rewrite'                    => $rewrite,
		);
		register_taxonomy( 'bullpen-locations', array( 'bullpen-jobs' ), $args );
	}

}

endif;

$bullpen_taxonomies = new Bullpen_Taxonomies;
