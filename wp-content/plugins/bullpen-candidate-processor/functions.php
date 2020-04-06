<?php
/*
 * Bullpen Jobs Board Plugin - Candidate Processor Extension
 *
 * @link              http://bullhorntowordpress.com
 * @since             2.0.alpha3
 * @package           Bullpen Jobs Board
 *
 * @wordpress-plugin
 * Plugin Name: Bullpen Jobs Board Candidate Processor Extension
 * Plugin URI: http://bullhorntowordpress.com
 * Description: This plugin extends Bullpen Jobs Board to process candidate submissions into your Bullhorn account.
 * Version: 2.0.alpha3
 * Author: Marketing Press
 * Author URI: http://marketingpress.com
 * License: GPL2
 */

/**
 * Require all files that are needed to run this plugin.
 */
add_action('init', 'requireFiles');
function requireFiles() {
	include_once (ABSPATH . 'wp-admin/includes/plugin.php');
	if (is_plugin_active(plugin_basename(__FILE__))) {
		require_once plugin_dir_path(__FILE__) . 'shortcodes.php';
		require_once plugin_dir_path(__FILE__) . 'class-bullpen-candidate-processor.php';
	}
}

//* Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'bullhorn2wp_enqueue_styles');
function bullhorn2wp_enqueue_styles() {
  wp_enqueue_style('bullhorn2wp_style', plugins_url('forms.css', __FILE__), '1.2.0');
  wp_enqueue_style('datatables_bs_style', 'https://cdn.datatables.net/v/bs4-4.1.1/dt-1.10.20/datatables.min.css');
  wp_enqueue_script('datatables_bootstrap_4', 'https://cdn.datatables.net/v/bs4-4.1.1/dt-1.10.20/datatables.min.js', array('jquery'), '1.10.20', false);
	wp_enqueue_script('jquery_validate', 'https://cdn.jsdelivr.net/jquery.validation/1.14.0/jquery.validate.min.js', array('jquery'), '1.14.0', true);
	// wp_enqueue_script( 'jquery_validate_more', 'https://cdn.jsdelivr.net/jquery.validation/1.14.0/additional-methods.min.js', array('jquery_validate'), '1.14.0', true );
}

function bullpen_parse_resume() {
	$bullhorn = new Bullhorn_Extended_Connection;
	$resume_file = $bullhorn -> storeResumeFile();
	$resume = false;

	for ($count = 0; $count < 3; $count++) {
		$resume = $bullhorn -> parseResume($resume_file);
		if ($resume) {
			// break out if the resume is parsed successfully
			break;
		}
	}

	if ($resume) {
		$candidate = $bullhorn -> createCandidate($resume);
		$bullhorn -> attachEducation($resume, $candidate);
		$bullhorn -> attachWorkHistory($resume, $candidate);
		$bullhorn -> attachResume($candidate, $resume_file);
		$bullhorn -> attachToJob($candidate);
	} else {
		error_log('Resume ' . $resume_file . ' failed to parse.');
	}

	// email the submission no matter what happens
	$bullhorn -> emailAssignee($candidate, $resume_file);
	$bullhorn -> deleteResumeFile($resume_file);

	wp_redirect($permalink = get_permalink($bullhorn -> settings['thanks_page']) ? : get_home_url());
	exit();
}

function bullpen_parse_application() {
	$bullhorn = new Bullhorn_Extended_Connection;
	$resume_file = $bullhorn -> storeResumeFile();
	$application = $bullhorn -> getApplicationData();
	$resume = false;

	for ($count = 0; $count < 3; $count++) {
		$resume = $bullhorn -> parseResume($resume_file);
		if ($resume) {
			// break out if the resume is parsed successfully
			break;
		}
	}

	$candidate_name = $application -> candidate -> firstName . ' ' . $application -> candidate -> lastName;
	$candidate_name .= " <" . $application -> candidate -> email2 . ">";
	if ($resume) {
		$merged_data = $bullhorn -> mergeFormAndResume($resume, $application);
		$candidate = $bullhorn -> createCandidate($merged_data);
		// if ($candidate) {
			$bullhorn -> attachEducation($resume, $candidate);
			$bullhorn -> attachWorkHistory($resume, $candidate);
			$bullhorn -> attachResume($candidate, $resume_file);
			$bullhorn -> attachToJob($candidate);
		// } else {
			// error_log('Failed to create candidate ' . $candidate_name);
		// }
	} else {
		//error_log('Resume parsing failed for candidate ' . $candidate_name . '. Creating a candidate with an attached resume only');
		$application -> candidate -> description = "Resume parsing failed. See attached file(s)";
		$candidate = $bullhorn -> createCandidateFromForm($application);
		$bullhorn -> attachResume($candidate, $resume_file);
		$bullhorn -> attachToJob($candidate);
	}

	// no matter what happens, notify the assignee(s) of the new submission
	$bullhorn -> emailAssignee($application, $resume_file);
	$bullhorn -> deleteResumeFile($resume_file);

	wp_redirect($permalink = get_permalink($bullhorn -> settings['thanks_page']) ? : get_home_url());
	exit();
}
