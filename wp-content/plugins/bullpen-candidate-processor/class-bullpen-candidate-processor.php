<?php

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

if (!defined('ABSPATH'))
	exit;

if (!class_exists('Bullhorn_Connection'))
	exit;

if (!class_exists('Bullhorn_Extended_Connection')) :

	/**
	 * This class is an extension of Bullhorn_Connection.  Its purpose
	 * is to allow for resume and candidate posting
	 *
	 * Class Bullhorn_Extended_Connection
	 */
	class Bullhorn_Extended_Connection extends Bullhorn_Connection
	{

		public $logged_in;

		protected $uploads_directory;

		/**
		 * Class Constructor
		 *
		 * @return \Bullhorn_Extended_Connection
		 */
		public function __construct()
		{
			// Call parent __construct()
			parent::__construct();
			$this->uploadsDirectory();

			// everytime we run this subclass, it'll need to log in.
			$this->extendedLogin();
		}

		public function extendedLogin()
		{
			$this->logged_in = $this->login();
		}

		// ultimately, this should move out of the subclass and into a initializer
		protected function uploadsDirectory()
		{
			$wp_uploads = wp_upload_dir();
			$uploads_dir = $wp_uploads['basedir'] . '/' . 'bullpen_uploads/resumes';

			if (!is_dir($uploads_dir)) {
				if (!wp_mkdir_p($uploads_dir)) {
					$this->throwJsonError(500, 'Unable to upload file to directory. Directory not accessable');
					exit();
				}
			}
			$this->uploads_dir = $uploads_dir;
			return true;
		}

		/**
		 * Retrieves user submitted file and saves it into the uploads folder.
		 *
		 * @return mixed
		 */
		public function storeResumeFile()
		{
			// check to make sure file was posted
			if (!isset($_FILES['resume'])) {
				$this->throwJsonError(500, 'No resume file found.');
				exit();
			}

			// Get the PHP assigned file name & location from the RAM storage of the file
			$tmp_name = $_FILES['resume']['tmp_name'];
			// Get the original file name as uploaded
			$name = $_FILES['resume']['name'];
			// To ensure all files are unique, get the timestamp of the uplaod.
			$timestamp = date('YmdHis');
			// Determine the server location for the file to be stored.
			$location = "$this->uploads_dir/$timestamp-$name";
			// Store the file.
			if (move_uploaded_file($tmp_name, $location)) {
				chmod($location, 0666);
				return $location;
			}
			return $tmp_name;
		}

		/**
		 * Retrieves user submitted data and pushes it into an array structured like Bullhorn API results.
		 *
		 * @return mixed
		 */
		public function getApplicationData()
		{
			$fields = array('firstName', 'lastName', 'email2', 'phone', 'source');
			$data = new stdClass();

			foreach ($fields as $key) {
				if (isset($_POST[$key])) {
					$data->{$key} = $_POST[$key];
				}
			}

			if (isset($_POST['owner'])) {
				// add the owner field
				$data->owner = new stdClass();
				$data->owner->id = $_POST['owner'];
			}

			if ($data->phone) {
				$data->mobile = $data->phone;
			}

			if ($data->firstName && $data->lastName) {
				$data->name = $data->firstName . ' ' . $data->lastName;
			}

			$address_fields = array('address1', 'address2', 'city', 'state', 'zip');
			$address_data = new stdClass();

			foreach ($address_fields as $key) {
				if (isset($_POST[$key])) {
					$address_data->{$key} = $_POST[$key];
				}
			}

			$data->address = $address_data;
			$candidate = new stdClass();
			$candidate->candidate = $data;
			return $candidate;
		}

		/**
		 * Takes the posted 'resume' file and returns a parsed version from bullhorn
		 *
		 * @return mixed
		 */
		public function parseResume($file)
		{
			if (!$file) {
				$this->throwJsonError(500, 'Requires a resume file.');
				exit();
			}

			// Get file extension (NEEDS TO MOVE TO UPLOAD FUNCTION)
			$ext = pathinfo($file, PATHINFO_EXTENSION);

			switch (strtolower($ext)) {
				case 'txt':
					$format = 'TEXT';
					break;
				case 'doc':
					$format = 'DOC';
					break;
				case 'docx':
					$format = 'DOCX';
					break;
				case 'pdf':
					$format = 'PDF';
					break;
				case 'rtf':
					$format = 'RTF';
					break;
				case 'html':
					$format = 'HTML';
					break;
				case 'htm':
					$format = 'HTML';
					break;
				default:
					$this->throwJsonError(500, 'File format error. (txt, html, pdf, doc, docx, rtf)');
			}

			$url = $this->url . 'resume/parseToCandidate';
			$params = array('BhRestToken' => $this->session, 'format' => $format, 'populateDescription' => 'text');

			try {
				$client = new Client();
				$url = $url . '?' . http_build_query($params);
				$response = $client->post($url, [
					'multipart' => [
						[
							'name'     => 'resume',
							'contents' => fopen($file, 'r')
						]
					]
				]);
				return json_decode($response->getBody());
			} catch (GuzzleHttp\Exception\ClientException $e) {
				//$error = json_decode($e -> getResponse() -> getBody());
				return false;
				// $this->throwJsonError(500, $error->errorMessage);
			} catch (GuzzleHttp\Exception\ServerException $e) {
				return false;
				// $this->throwJsonError(500, $error);
			} catch (GuzzleHttp\Exception\ConnectException $e) {
				$error = $e->getMessage();
				error_log($error);
				return false;
				// $this->throwJsonError(500, $error);
			} catch (Exception $e) {
				$error = $e->getMessage();
				error_log($error);
				return false;
				// $this->throwJsonError(404, 'Oh damn');
			}
		}

		public function mergeFormAndResume($resume, $application)
		{
			// HACK: there has to be a better way to do this
			// save the description field (the HTML resume) and add it back in
			// after the two arrays are merged
			$description = $resume->candidate->description;
			$merged = (object) array_merge((array) $resume, (array) $application);
			$merged->candidate->description = $description;
			return $merged;
		}

		/**
		 * Find a candidate by email address.
		 * This is so we don't end up creating duplicate records
		 *
		 * @param $email
		 * @return integer - the candidate's ID, if found, else zero
		 */
		private function getCandidate($email)
		{
			// Create the url && variables array
			$url = $this->url . 'search/Candidate';
			$query = '(email:"' . $email . '" OR email2:"' . $email . '") AND isDeleted:0';
			$params = array('BhRestToken' => $this->session, 'fields' => 'id,email,email2', 'query' => $query);

			try {
				$client = new Client();
				$response = $client->get($url . '?' . http_build_query($params));
				$candidates = json_decode($response->getBody());
				$data = $candidates->data;
				if (count($data) > 0) {
					$candidate = $data[0];
					return $candidate;
				}
				return 0;
			} catch (ClientException $e) {
				$error = json_decode($e->getResponse()->getBody());
				//$this->throwJsonError(500, $error->errorMessage);
				return 0;
			} catch (ServerException $e) {
				return 0;
			}
		}

		/**
		 * Create a candidate in the system
		 *
		 * @param $resume
		 * @return mixed
		 */
		public function createCandidate($resume)
		{
			// Make sure country ID is correct
			if (is_null($resume->candidate->address)) {
				$resume->candidate->address = new stdClass();
			}

			if (!property_exists($resume->candidate->address, 'countryID')) {
				$resume->candidate->address->countryID = null;
			}

			if (is_null($resume->candidate->address->countryID)) {
				$resume->candidate->address->countryID = 1;
			}

			// see if there's already a candidate, so we can update
			// rather than create a duplicate
			$candidate = $this->getCandidate($resume->candidate->email2);
			if ($candidate) {
				$candidate_id = $candidate->id;
			} else {
				$candidate_id = 0;
			}

			$candidate_data = json_encode($resume->candidate);

			// Create the url && variables array
			$url = $this->url . 'entity/Candidate';
			$params = array('BhRestToken' => $this->session);
			try {
				$client = new Client();
				if ($candidate_id) {
					// update, don't create
					$url .= '/' . $candidate_id;
					$response = $client->post($url . '?' . http_build_query($params), array('body' => $candidate_data));
				} else {
					// create a new candidate
					$response = $client->put($url . '?' . http_build_query($params), array('body' => $candidate_data));
				}
				return json_decode($response->getBody());
			} catch (ClientException $e) {
				$error = json_decode($e->getResponse()->getBody());
				// $this->throwJsonError(500, $error->errorMessage);
				return false;
			} catch (ServerException $e) {
				return false;
			}
		}

		public function createCandidateFromForm($application)
		{
			$application = (object) $application;
			$application->candidate = (object) $application->candidate;

			// Make sure country ID is correct
			if (is_null($application->candidate->address)) {
				$application->candidate->address = new stdClass();
				if (is_null($application->candidate->address->countryID)) {
					$application->candidate->address = (object) $application->candidate->address;
					$application->candidate->address->countryID = 1;
				}
			}
			$candidate_data = json_encode($application->candidate);

			// Create the url && variables array
			$url = $this->url . 'entity/Candidate';
			$params = array('BhRestToken' => $this->session);
			$candidate = $this->getCandidate($application->candidate->email2);
			if ($candidate) {
				$candidate_id = $candidate->id;
			} else {
				$candidate_id = 0;
			}

			try {
				$client = new Client();
				if ($candidate_id) {
					// update, don't create
					$url .= '/' . $candidate_id;
					$response = $client->post($url . '?' . http_build_query($params), array('body' => $candidate_data));
				} else {
					// create a new candidate
					$response = $client->put($url . '?' . http_build_query($params), array('body' => $candidate_data));
				}
				return json_decode($response->getBody());
			} catch (ClientException $e) {
				$error = json_decode($e->getResponse()->getBody());
				error_log($error);
				// $this->throwJsonError(500, $error->errorMessage);
				return false;
			} catch (ServerException $e) {
				return false;
			} catch (GuzzleHttp\Ring\Exception\ConnectException $e) {
				$error = $e->getMessage();
				error_log($error);
				return false;
			}
		}

		/**
		 * Attach education to candidates
		 *
		 * @param $resume
		 * @param $candidate
		 * @return mixed
		 */
		public function attachEducation($resume, $candidate)
		{

			// Create the url && variables array
			$url = $this->url . 'entity/CandidateEducation';
			$params = array('BhRestToken' => $this->session);

			$responses = array();

			if (property_exists($resume, 'candidateEducation')) {
				foreach ($resume->candidateEducation as $edu) {
					$edu->candidate = new stdClass;
					$edu->candidate->id = $candidate->changedEntityId;
					if (!is_int($edu->gpa) || !is_float($edu->gpa)) {
						unset($edu->gpa);
					}

					$edu_data = json_encode($edu);

					try {
						$client = new Client();
						$response = $client->put($url . '?' . http_build_query($params), array('body' => $edu_data));
						$responses[] = $response->getBody();
					} catch (ClientException $e) {
						$error = json_decode($e->getResponse()->getBody());
						// $this->throwJsonError(500, $error->errorMessage);
						return false;
					} catch (ServerException $e) {
						return false;
					}
				}
			}
			return json_decode('[' . implode(',', $responses) . ']');
		}

		/**
		 * Attach Work History to a candidate
		 *
		 * @param $resume
		 * @param $candidate
		 * @return mixed
		 */
		public function attachWorkHistory($resume, $candidate)
		{
			// don't bother if there is no work history to process
			if (!property_exists($resume, 'candidateWorkHistory')) {
				return false;
			}

			// Create the url && variables array
			$url = $this->url . 'entity/CandidateWorkHistory';
			$params = array('BhRestToken' => $this->session);

			$responses = array();

			if (property_exists($resume, 'candidateWorkHistory')) {
				foreach ($resume->candidateWorkHistory as $job) {
					$job->candidate = new stdClass;
					$job->candidate->id = $candidate->changedEntityId;

					$job_data = json_encode($job);

					try {
						$client = new Client();
						$response = $client->put($url . '?' . http_build_query($params), array('body' => $job_data));

						$responses[] = $response->getBody();
					} catch (ClientException $e) {
						$error = json_decode($e->getResponse()->getBody());
						// $this->throwJsonError(500, $error->errorMessage);
						return false;
					} catch (ServerException $e) {
						return false;
					}
				}
			}
			return json_decode('[' . implode(',', $responses) . ']');
		}

		/**
		 * Attach Resume to a candidate.
		 *
		 * @param $candidate
		 * @param $file
		 * @return mixed
		 */
		public function attachResume($candidate, $file)
		{
			if (!$file) {
				return false;
			}

			// Create the url && variables array
			$url = $this->url . '/file/Candidate/' . $candidate->changedEntityId . '/raw';
			$params = array('BhRestToken' => $this->session, 'externalID' => 'Portfolio', 'fileType' => 'SAMPLE');

			try {
				$client = new Client();
				$response = $client->put($url, [
					'multipart' => [
						[
							'name'     => 'resume',
							'contents' => fopen($file, 'r')
						]
					]
				]);
				return json_decode($response->getBody());
			} catch (ClientException $e) {
				$error = json_decode($e->getResponse()->getBody());
				// $this->throwJsonError(500, $error->errorMessage);
				return false;
			} catch (ServerException $e) {
				return false;
			}
		}

		public function attachToJob($candidate)
		{
			if (!isset($_POST['job'])) {
				return;
			}
			$job_id = $_POST['job'];

			// Create the url && variables array
			$url = $this->url . 'entity/JobSubmission';
			$params = array('BhRestToken' => $this->session);

			$body = array('candidate' => array('id' => (int) $candidate->changedEntityId), 'jobOrder' => array('id' => (int) $job_id), 'status' => 'New Lead', 'dateWebResponse' => (int) (microtime(true) * 1000));

			try {
				$client = new Client();
				$response = $client->put($url . '?' . http_build_query($params), array('json' => $body));
				return json_decode($response->getBody());
			} catch (ClientException $e) {
				$error = json_decode($e->getResponse()->getBody());
				return false;
				// $this->throwJsonError(500, $error->errorMessage);
			} catch (ServerException $e) {
				return false;
			}
		}

		public function emailAssignee($candidate, $resume_file)
		{
			$candidate = $candidate->candidate;
			$candidate_name = $candidate->firstName . " " . $candidate->lastName;
			$candidate_email = $candidate->email2;
			$candidate_phone = $candidate->phone;
			$candidate_source = $candidate->source;
			$attachment = array($resume_file);

			if (!isset($_POST['job'])) {
				// if there's no job ID, then it's just a resume submission
				$email_to = 'david@advantagetech.net,jen@advantagetech.net';
				$subject = "New resume from " . $candidate_name;
				$message = "A new resume has been submitted.";
				$message .= "\n\nName: " . $candidate_name;
				$message .= "\nEmail: " . $candidate_email;
				$message .= "\nPhone number: " . $candidate_phone;
				$this->sendEmail($email_to, $subject, $message, $attachment);
				return;
			}

			$job_id = $_POST['job'];

			// get the post based on the job ID
			$args = array('numberposts' => 1, 'post_type' => 'bullpen-jobs', 'meta_key' => 'bullhorn_job_id', 'meta_value' => $job_id);

			// default to David in case the assignee field is empty
			$email_to = 'david@advantagetech.net';
			$the_query = new WP_Query($args);
			if ($the_query->have_posts()) {
				while ($the_query->have_posts()) {
					$the_query->the_post();
					$post_id = get_the_ID();
					$job_title = get_the_title();
					$job_id = get_post_meta($post_id, 'bullhorn_job_id', true);
					$email_to = get_post_meta($post_id, 'bullhorn_job_assignee', true);
					$subject = 'New candidate for job ' . $job_title . ' (ID: ' . $job_id . ')';
					$message = "A new job submission for " . $job_title . " has been submitted.";
					$message .= "\n\nName: " . $candidate_name;
					$message .= "\nEmail: " . $candidate_email;
					$message .= "\nPhone number: " . $candidate_phone;
					$message .= "\nSource: " . $candidate_source;
					$this->sendEmail($email_to, $subject, $message, $attachment);
				}
			} else {
				error_log('No posts found for job ID ' . $job_id);
			}
		}

		private function sendEmail($to, $subject, $message, $attachments = array())
		{
			// same headers no matter who's getting emailed
			$headers = "From: Advantage Tech Website Submission <webmaster@advantagetech.net>\r\n";
			$headers .= "Reply-To: webmaster@advantagetech.net\r\n";
			// blind copy me for now, just to keep an eye on things
			//$headers .= "Bcc: mdwallick@gmail.com\r\n";
			$headers .= "X-Mailer: PHP/" . phpversion();
			wp_mail($to, $subject, $message, $headers, $attachments);
		}

		public function deleteResumeFile($file)
		{
			if (!$file) {
				return false;
			}
			try {
				unlink($file);
				return 'Successfully Deleted';
			} catch (Exception $e) {
				return false;
				// $this->throwJsonError(500, $e);
			}
		}

		/**
		 * Send a json error to the screen
		 *
		 * @param $status
		 * @param $error
		 */
		private function throwJsonError($status, $error)
		{
			$response = array('status' => $status, 'error' => $error);
			echo json_encode($response);
			exit;
		}
	}
endif;
