<?php

/**
 * Shortcodes
 *
 * @package     Bullpen
 * @subpackage  Shortcodes
 * @copyright   Copyright (c) 2016, MarketingPress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!class_exists('Bullpen_Shortcodes')) :

	/**
	 * Registers and sets up the shortcodes
	 *
	 * @since 1.0
	 * @return void
	 *
	 */
	class Bullpen_Shortcodes
	{

		public function __construct()
		{
			add_action('init', array($this, 'init'));
			// add_action( 'the_content', array( $this, 'the_content' ) );
		}

		public function init()
		{
			add_filter('posts_where', array($this, 'title_like_posts_where'), 10, 2);
			add_shortcode('bullpen_jobs', array($this, 'primary_shortcode'));
			add_shortcode('bullpen_search', array($this, 'bullpen_search'));
			// add_shortcode( 'bullpen_location', array($this, 'bullpen_location_shortcode') );
			// add_shortcode( 'bullpen_categories', array($this, 'bullpen_categories_shortcode') );		
		}

		/**
		 * Adds the ability to filter posts in WP_Query by post title.
		 *
		 * @param string   $where
		 * @param WP_Query $wp_query
		 * @return string
		 */
		function title_like_posts_where($where, $wp_query)
		{
			global $wpdb;
			if ($post_title_like = $wp_query->get('post_title_like')) {
				$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' . esc_sql(like_escape($post_title_like)) . '%\'';
			}
			return $where;
		}

		function primary_shortcode($atts)
		{
			extract(shortcode_atts(array(
				'limit'     => 5,
				'show_date' => false,
				'location'  => null,
				'category'  => null,
				'title'     => null,
				'columns'   => 1,
			), $atts));

			$output = null;

			// Only allow up to two columns for now
			if ($columns > 4 or $columns < 1) {
				$columns = 1;
			}

			$args = array(
				'post_type'      => 'bullpen-jobs',
				'posts_per_page' => intval($limit),
				'tax_query'      => array(),
			);

			if ($location) {
				$args['tax_query'][] = array(
					'taxonomy' => 'bullpen-locations',
					'field'    => 'slug',
					'terms'    => sanitize_title($location),
				);
			}

			if (isset($_GET['bullpen-location'])) {
				$args['tax_query'][] = array(
					'taxonomy' => 'bullpen-locations',
					'field'    => 'slug',
					'terms'    => sanitize_key($_GET['bullpen-location']),
				);
			}

			if ($category) {
				$args['tax_query'][] = array(
					'taxonomy' => 'bullpen-categories',
					'field'    => 'slug',
					'terms'    => sanitize_title($category),
				);
			}

			if (isset($_GET['bullpen-category'])) {
				$args['tax_query'][] = array(
					'taxonomy' => 'bullpen-categories',
					'field'    => 'slug',
					'terms'    => sanitize_key($_GET['bullpen-category']),
				);
			}

			if ($title) {
				$args['post_title_like'] = $title;
			}

			$jobs = new WP_Query($args);

			if ($jobs->have_posts()) {
				$output .= '<ul class="bullpen-jobs">';

				while ($jobs->have_posts()) {

					$jobs->the_post();

					$output .= '<li>';
					$output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
					if ($show_date) {
						$output .= ' posted on ' . get_the_date('F jS, Y');
					}
					$output .= '</li>';
				}
				$output .= '</ul>';
			} else {

				$output .= '<p>We\'re sorry, but no jobs match your search.</p>';
			}

			$c = intval($columns);
			$output .= '<style>';
			$output .= '.bullpen-jobs { -moz-column-count: ' . $c . '; -moz-column-gap: 20px; -webkit-column-count: ' . $c . '; -webkit-column-gap: 20px; column-count: ' . $c . '; column-gap: 20px; }';
			$output .= '</style>';
			$output .= '<!--[if lt IE 10]><style>.bullpen-jobs li { width: ' . (100 / $c) . '%; float: left; }</style><![endif]-->';

			return $output;
		}

		/**
		 * Adds the shortcode for generating a list of Categories.
		 *
		 * @param  array  $atts
		 * @return string
		 */
		public function bullhorn_categories($atts)
		{
			$bullhorn_shortcode_opts = $this->bullhorn_shortcode_opts();

			$output = '<select onchange="if (this.value) window.location.href=this.value">';
			$output .= '<option value="">' . $bullhorn_shortcode_opts['label-categories'] . '</option>';

			$categories = get_categories(array(
				'taxonomy'   => 'bullhorn_category',
				'hide_empty' => $bullhorn_shortcode_opts['hide-empty-categories'],
			));
			foreach ($categories as $category) {
				$params = array('bullhorn_category' => $category->slug);
				if (isset($_GET['bullhorn_state'])) {
					$params['bullhorn_state'] = $_GET['bullhorn_state'];
				}

				$selected = null;
				if (isset($_GET['bullhorn_category']) and $_GET['bullhorn_category'] === $category->slug) {
					$selected = 'selected="selected"';
				}

				$output .= '<option value="?' . http_build_query($params) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
			}

			$output .= '</select>';

			return $output;
		}

		/**
		 * Adds the shortcode for generating a list of Bullhorn states.
		 *
		 * @param  array  $atts
		 * @return string
		 */
		public function bullhorn_states($atts)
		{
			$bullhorn_shortcode_opts = $this->bullhorn_shortcode_opts();

			$output = '<select onchange="if (this.value) window.location.href=this.value">';
			$output .= '<option value="">' . $bullhorn_shortcode_opts['label-states'] . '</option>';

			$states = get_categories(array(
				'taxonomy'   => 'bullhorn_state',
				'hide_empty' => $bullhorn_shortcode_opts['hide-empty-categories'],
			));
			foreach ($states as $state) {
				$params = array('bullhorn_state' => $state->slug);
				if (isset($_GET['bullhorn_category'])) {
					$params['bullhorn_category'] = $_GET['bullhorn_category'];
				}

				$selected = null;
				if (isset($_GET['bullhorn_state']) and $_GET['bullhorn_state'] === $state->slug) {
					$selected = 'selected="selected"';
				}

				$output .= '<option value="?' . http_build_query($params) . '" ' . $selected . '>' . esc_html($state->name) . '</option>';
			}

			$output .= '</select>';

			return $output;
		}

		/**
		 * Adds the shortcode for searching job postings.
		 *
		 * @param  array  $atts
		 * @return string
		 */
		function bullpen_search($atts)
		{
			$form = get_search_form(false);
			$hidden = '<input type="hidden" name="post_type" value="bullpen-jobs" />';
			return str_replace('</form>', $hidden . '</form>', $form);
		}

		private function bullhorn_shortcode_opts()
		{
			return array(
				'label-categories' => 'Categories',
				'label-states' => 'Location',
				'hide-empty-categories' => '1',
			);
		}
	}

endif;

$bullpen_shortcodes = new Bullpen_Shortcodes;
