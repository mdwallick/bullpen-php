<?php

/**
 * Allow job listings to be sorted by a specified setting by the admin.
 */
function bullhorn_sort_results($query)
{
	if ($query->is_archive('bullpen-jobs')) {
		$settings = (array) get_option('bullpen_settings');
		if (isset($settings['listings_sort']) and !empty($settings['listings_sort'])) {
			// Use in_array() because this list might grow in the future
			if (!in_array($settings['listings_sort'], array('name', 'date'))) {
				$query->set('meta_key', $settings['listings_sort']);
				$query->set('orderby', 'meta_value');
			} else {
				$query->set('orderby', $settings['listings_sort']);
			}

			// All queries should default ascending except date sorts
			if (strstr($settings['listings_sort'], 'date')) {
				$query->set('order', 'DESC');
			} else {
				$query->set('order', 'ASC');
			}
		}
	}

	$modify_query = false;
	$tax_queries = array_filter((array) $query->get('tax_query'));
	if (count($tax_queries) > 0) {
		foreach ($tax_queries as $tax_query) {
			if (strstr($tax_query['taxonomy'], 'bullpen-') !== false) {
				$modify_query = true;
			}
		}
	}

	if (in_array('bullpen-jobs', (array) $query->get('post_type'))) {
		$modify_query = true;
	}

	if ($modify_query === true) {
		if (isset($_GET['bullpen-locations'])) {
			$tax_queries[] = array(
				'taxonomy' => 'bullpen-locations',
				'field'    => 'slug',
				'terms'    => sanitize_key($_GET['bullpen-locations']),
			);
		}

		if (isset($_GET['bullpen-categories'])) {
			$tax_queries[] = array(
				'taxonomy' => 'bullpen-categories',
				'field'    => 'slug',
				'terms'    => sanitize_key($_GET['bullpen-categories']),
			);
		}

		$query->set('tax_query', $tax_queries);
	}
}
add_action('pre_get_posts', 'bullhorn_sort_results');
