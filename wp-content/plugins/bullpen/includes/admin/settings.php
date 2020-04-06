<?php
/**
 * Admin Settings
 *
 * @package     Bullpen
 * @subpackage  Admin
 * @copyright   Copyright (c) 2016, MarketingPress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0.0
 */


if ( !defined('ABSPATH') ) exit;

if ( !class_exists( 'Bullpen_Settings' ) ) : 

class Bullpen_Settings {

	protected $settings;

	public function __construct() {

		// If the page is accessed with a sync now request, call the sync feature.
		if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'bullhorn' ) {
			// add_action( 'admin_init', 'bullhorn_sync' );
			add_action( 'admin_init', array( $this, 'bullhorn_test' ) );
		}

		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'admin_menu', array( $this, 'menu' ) );

		$this->settings = get_option( 'bullpen_settings' );
	}

	public function bullhorn_test() {
		$bullhorn_connection = new Bullhorn_Connection;
		$bullhorn_connection->sync();
	}

	/**
	 * Creates a Bullpen Jobs Settings Page in the Admin Menu
	 */
	public function menu() {
		add_options_page( 'Bullpen Jobs', 'Bullpen Jobs', 'manage_options', 'bullpen', array( $this, 'settings_page' ) );
	}	

	/**
	 * Sets up the plugin by adding the settings link
	 */
	public function init() {

		$bullhorn = new Bullhorn_Connection;

		// If a sync was just completed, redirect back the page after the sync is complete.
		if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'bullhorn' ) {
			wp_redirect( BULLPEN_PLUGIN_ADMIN_URL );
		}

		if ( isset( $_GET['authorize'] ) && $_GET['authorize'] == 'code' ) {
			$bullhorn->get_auth_code();
		} 

		if ( isset( $_GET['code'] ) ) {
			$this->settings['bullhorn_api_auth_code'] = esc_html( $_GET['code'] );
			update_option( 'bullpen_settings', $this->settings, true );
		}

		register_setting( 'bullpen_settings', 'bullpen_settings', array( $this, 'validate' ) );

		add_settings_section( 'bullhorn_api', 'Bullhorn API Settings', null, 'bullpen_settings' );
		add_settings_field( 'bullhorn_client_id', 'Client ID', array( $this, 'settings_field_client_id' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'bullhorn_client_secret', 'Client Secret', array( $this, 'settings_field_client_secret' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'bullhorn_api_username', 'API Username', array( $this, 'settings_field_api_username' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'bullhorn_api_password', 'API Password', array( $this, 'settings_field_api_password' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'bullhorn_authorize', 'Authorization Code', array( $this, 'settings_field_authorize' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'bullhorn_check_connection', 'Check Connection', array( $this, 'bullhorn_check_connection'), 'bullpen_settings', 'bullhorn_api' );

		add_settings_section( 'bullpen_plugin_settings', 'Bullpen Jobs Settings', null, 'bullhorn_api' );
		add_settings_field( 'client_corporation', 'Client Corporation', array( $this, 'settings_field_client_corporation' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'listings_page', 'Jobs Permalink URL', array( $this, 'settings_field_listings_page' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'form_page', 'Application Page', array( $this, 'settings_field_form_page' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'thanks_page', 'Thank You Page', array( $this, 'settings_field_thanks_page' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'listings_sort', 'Listings Sort', array( $this, 'settings_field_listings_sort' ), 'bullpen_settings', 'bullhorn_api' );
		add_settings_field( 'description_field', 'Description Field', array( $this, 'settings_field_description_field' ), 'bullpen_settings', 'bullhorn_api' );

	}

	/**
	 * 
	 * Output the main settings page with the title and form
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h2>Bullpen Job Board Settings Page</h2>
			<form id="bullpen-api-options" method="post" action="options.php">
				<?php do_settings_sections( 'bullpen_settings' ); ?>
				<?php settings_fields( 'bullpen_settings' ); ?>
			
				<p class="submit">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
					<a href="<?php echo admin_url( 'options-general.php?page=bullpen&sync=bullhorn' ); ?>" class="button">
						Sync Now
					</a>
				</p>
			</form>
		</div>
		<?php
	}
	/**
	 * Builds and Displays the Bullhorn API Client ID Field
	 */
	public function settings_field_client_id() {
		$setting = isset( $this->settings['bullhorn_api_client_id'] ) ? $this->settings['bullhorn_api_client_id'] : '';
		echo '<input type="password" size="40" name="bullpen_settings[bullhorn_api_client_id]" value="' . esc_attr( $setting ) . '" />';
		// echo '<input type="text" size="40" name="bullpen_settings[bullhorn_api_client_id]" value="' . esc_attr( $setting ) . '" />';
	}

	/**
	 * Builds and Displays the Bullhorn API Client Secret Field
	 */
	public function settings_field_client_secret() {
		$setting = isset( $this->settings['bullhorn_api_client_secret'] ) ? $this->settings['bullhorn_api_client_secret'] : '';
		echo '<input type="password" size="40" name="bullpen_settings[bullhorn_api_client_secret]" value="' . esc_attr( $setting ) . '" />';
		// echo '<input type="text" size="40" name="bullpen_settings[bullhorn_api_client_secret]" value="' . esc_attr( $setting ) . '" />';
	}

	/**
	 * Builds and Displays the Bullhorn API Username Field
	 */
	public function settings_field_api_username() {
		$setting = isset( $this->settings['bullhorn_api_username'] ) ? $this->settings['bullhorn_api_username'] : '';
		echo '<input type="password" size="40" name="bullpen_settings[bullhorn_api_username]" value="' . esc_attr( $setting ) . '" />';
		// echo '<input type="text" size="40" name="bullpen_settings[bullhorn_api_username]" value="' . esc_attr( $setting ) . '" />';
	}

	/**
	 * Builds and Displays the Bullhorn API User Password Field
	 */
	public function settings_field_api_password() {
		$setting = isset( $this->settings['bullhorn_api_password'] ) ? $this->settings['bullhorn_api_password'] : '';
		echo '<input type="password" size="40" name="bullpen_settings[bullhorn_api_password]" value="' . esc_attr( $setting ) . '" />';
		// echo '<input type="text" size="40" name="bullpen_settings[bullhorn_api_password]" value="' . esc_attr( $setting ) . '" />';
	}

	/**
	 * Shows the Bullhorn API Auth Code
	 */
	public function settings_field_authorize() {
		$setting = isset( $this->settings['bullhorn_api_auth_code'] ) ? $this->settings['bullhorn_api_auth_code'] : '';
		// echo '<input disabled type="password" size="40" name="" value="' . esc_attr( $setting ) . '" />';
		echo '<input disabled type="text" size="40" value="' . esc_attr( $setting ) . '" />';
		if ( isset( $this->settings['bullhorn_api_client_id'] ) ) {
			echo sprintf( '<a class="button" href="%s">', BULLPEN_PLUGIN_ADMIN_URL . '&authorize=code');
			echo $setting ? 'Get New Code</a>' : 'Get Authorization Code</a>';
		}
	}

	/**
	 * Callback for the API settings section, which is left blank
	 */
	public function bullhorn_check_connection() {
		if ( isset($_GET['error_description'] ) ) {
			echo 'Error fetching Auth Code: ' . $_GET['error_description'];
		} else {
			$bullhorn = new Bullhorn_Connection;
			echo $bullhorn->check_connection();
		}
		echo '<p><a href="#" onclick="jQuery(\'#bullpen-api-options\').find(\'input[type=password]\').attr(\'type\', \'text\');">Show Hidden Values</a></p>';
	}


	/**
	 * Displays the job listings page settings field.
	 */
	public function settings_field_listings_page() {
		$setting = isset( $this->settings['listings_page']) ? $this->settings['listings_page'] : '';
		echo '<input type="text" size="40" name="bullpen_settings[listings_page]" value="' . esc_attr( $setting ) . '" />';
		echo '<br><span class="description">This will begin the permalink URL to your job listing. It defaults to \'bullpen-jobs\' but can be anything. If a job\'s url is www.yoursite.com/bullpen-jobs/job-name you can update this field to change it. For instance changing this to \'careers\' will make the url www.yoursite.com/careeers/job-name.</span>';
	}

	
	/**
	 * Displays the settings field for picking the client corporation.
	 */
	public function settings_field_client_corporation() {
		$setting = isset( $this->settings['client_corporation'] ) ? $this->settings['client_corporation'] : '';
		echo '<input type="text" size="40" name="bullpen_settings[client_corporation]" value="' . esc_attr( $setting ) . '" />';
		echo '<br><span class="description">This field is optional, but will filter the jobs retreived from Bullhorn to only those listed under a specific Client Corporation. This must be the ID of the corporation. Leave blank to sync all job listings.</span>';
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public function settings_field_form_page() {
		$setting = isset( $this->settings['form_page'] ) ? $this->settings['form_page'] : '';
		wp_dropdown_pages( array(
			'name' => 'bullpen_settings[form_page]',
			'selected' => $setting,
			'show_option_none' => 'Select a page...',
		) );
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public function settings_field_thanks_page() {
		if ( class_exists( 'Bullhorn_Extended_Connection' ) ) {

			$setting = isset( $this->settings['thanks_page'] ) ? $this->settings['thanks_page'] : '';
			wp_dropdown_pages( array(
				'name' => 'bullpen_settings[thanks_page]',
				'selected' => $setting,
				'show_option_none' => 'Select a page...',
			) );

		}

	}

	/**
	 * Displays the description field settings field.
	 */
	public function settings_field_description_field() {
		if ( isset( $this->settings['description_field'] ) && !empty( $this->settings['description_field'] ) ) {
			$description_field = $this->settings['description_field'];
		} else {
			$description_field = 'description';
		}

		$fields = array(
			'description' => 'Description (default)',
			'publicDescription' => 'Public Description',
		);

		echo '<select name="bullpen_settings[description_field]">';
		echo '<option>Select the description field to use...</option>';
		foreach ($fields as $value => $name)
		{
			$selected = ($description_field === $value) ? ' selected="selected"' : '';
			echo '<option value="' . $value . '"' . $selected . '>' . $name . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Displays the job listings sort settings field.
	 */
	public function settings_field_listings_sort() {
		$listings_sort = ( isset( $this->settings['listings_sort'] ) ) ? $this->settings['listings_sort'] : '';

		$sorts = array(
			'date'            => 'Date',
			'employment-type' => 'Employment Type',
			'name'            => 'Name',
			'state'           => 'State',
		);

		echo '<select name="bullpen_settings[listings_sort]">';
		echo '<option value="">Select a field to sort by...</option>';
		foreach ($sorts as $value => $name)
		{
			$selected = ($listings_sort === $value) ? ' selected="selected"' : '';
			echo '<option value="' . $value . '"' . $selected . '>' . $name . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Validates the user input
	 *
	 * @param array   $input POST data
	 * @return array        Sanitized POST data
	 */
	public function validate( $input ) {
		$input['bullhorn_api_client_id']     = esc_html( $input['bullhorn_api_client_id'] );
		$input['bullhorn_api_client_secret'] = esc_html( $input['bullhorn_api_client_secret'] );
		$input['bullhorn_api_username']      = esc_html( $input['bullhorn_api_username'] );
		$input['bullhorn_api_password']      = esc_html( $input['bullhorn_api_password'] );
		$input['listings_page']              = esc_html( $input['listings_page'] );
		$input['client_corporation']         = esc_html( $input['client_corporation'] );
		$input['form_page']                  = esc_html( $input['form_page'] );
		$input['thanks_page']                = esc_html( $input['thanks_page'] );
		$input['description_field']          = esc_html( $input['description_field'] );
		$input['listings_sort']              = esc_html( $input['listings_sort'] );
		flush_rewrite_rules();
		return $input;
	}
	
}

endif;

$bullpen_settings = new Bullpen_Settings;
