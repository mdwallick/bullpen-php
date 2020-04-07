<?php

class Bullpen_Extension_Shortcodes
{

	public function __construct()
	{
		add_shortcode('bullpen_resume', array(&$this, 'Resume_Form_Shortcode_Handler'));
		add_shortcode('bullpen_application', array(&$this, 'Application_Form_Shortcode_Handler'));
		add_shortcode('bullpen_shortapp', array(&$this, 'ShortApplication_Form_Shortcode_Handler'));
	}

	public function ShortApplication_Form_Shortcode_Handler($atts)
	{

		$a = shortcode_atts(array(
			'job' => NULL,
		), $atts);

		echo '<form id="bullpen_application" action="' . get_site_url();
		echo '/api/bullhorn/application" method="post" ';
		echo 'class="bullpen_application" enctype="multipart/form-data">';

		if ($a['job']) {
			echo '<input type="hidden" value="' . $a['job'] . '" name="job">';
		} ?>

		<input type="hidden" value="Website Application" name="source">
		<div class='firstName fieldgroup'>
			<label for='firstName'>First Name</label>
			<input type='text' name='firstName' placeholder='First Name (required)' minlength="2" required />
		</div>

		<div class='lastName fieldgroup'>
			<label for='lastName'>Last Name</label>
			<input type='text' name='lastName' placeholder='Last Name (required)' minlength="2" required />
		</div>

		<div class='email fieldgroup'>
			<label for='email'>Email Address</label>
			<input type='email' name='email2' placeholder='Email Address (required)' required />
		</div>

		<div class='phone fieldgroup'>
			<label for='phone'>Phone Number</label>
			<input type='tel' name='phone' placeholder='Phone Number (required)' required />
		</div>

		<div class="fieldgroup">
			<label for='resume'>Attach a Resume</label>
			<p>Please attach a resume. This is a required field. If you do not attach a resume,
				your application may not be received. Accepted file types are <strong>.DOC, .DOCX,
					.TXT, .TEXT, .PDF, .RTF, and .HTML</strong>.</p>
			<input class="resume" type='file' name='resume' id='resume' required>
		</div>

		<div><input type='submit' class='button' value='Submit Application' name='submit'></div>

		<?php echo "</form>"; ?>

		<script>
			jQuery.validator.setDefaults({
				debug: true,
				success: 'valid'
			});
			jQuery('.bullpen_application').validate({
				rules: {
					resume: {
						required: true,
						extension: 'doc|docx|pdf|txt|rtf|html'
					}
				}
			});
		</script>
	<?php

	}

	public function Application_Form_Shortcode_Handler($atts)
	{
		echo "<form id='bullpen_application' action='" . get_site_url();
		echo "/api/bullhorn/application' method='post' class='bullpen_application' enctype='multipart/form-data'>";
		if (isset($_GET['job'])) {
			echo '<input type="hidden" value="' . $_GET['job'] . '" name="job">';
		}

		$source = 'Website Application';
		if (isset($_GET['source'])) {
			$source = $_GET['source'];
		}
		echo '<input type="hidden" value="' . $source . '" name="source">';

		if (isset($_GET['owner'])) {
			echo '<input type="hidden" value="' . $_GET['owner'] . '" name="owner">';
		}
	?>

		<h2><?php echo $_GET['position']; ?> - Job ID <?php echo $_GET['job']; ?></h2>

		<input type="hidden" name="description" value="">

		<div class="firstName fieldgroup">
			<label for="firstName">First Name</label>
			<input type="text" name="firstName" placeholder="First Name (required)" minlength="2" required />
		</div>

		<div class="lastName fieldgroup">
			<label for="lastName">Last Name</label>
			<input type="text" name="lastName" placeholder="Last Name (required)" minlength="2" required />
		</div>

		<div class="email fieldgroup">
			<label for="email">Email Address</label>
			<input type="email" name="email2" placeholder="Email Address (required)" required />
		</div>

		<div class="phone fieldgroup">
			<label for="phone">Phone Number</label>
			<input type="tel" name="phone" placeholder="Phone Number (required)" required />
		</div>

		<div class="address1 fieldgroup">
			<label for="address1">Address</label>
			<input type="text" name="address1" placeholder="Address" minlength="2" />
		</div>

		<div class="address2 fieldgroup">
			<label for="address2">Address (Cont.)</label>
			<input type="text" name="address2" placeholder="Address (Cont.)" minlength="2" />
		</div>

		<div class="city fieldgroup">
			<label for="city">City</label>
			<input type="text" name="city" placeholder="City" minlength="2" />
		</div>

		<div class="state fieldgroup">
			<label for="state">State</label>
			<input type="text" name="state" placeholder="State" maxlength="2" />
		</div>

		<div class="zip fieldgroup">
			<label for="zip">Zip Code</label>
			<input type="number" name="zip" placeholder="Zip Code" minlength="2" />
		</div>

		<div class="fieldgroup">
			<label for="resume">Attach a Resume</label>
			<p>Please attach a resume. This is a required field. If you do not attach a resume,
				your application may not be received. Accepted file types are <strong>.DOC, .DOCX,
					.TXT, .TEXT, .PDF, .RTF, and .HTML</strong>.</p>
			<input class="resume" type="file" name="resume" id="resume" required>
		</div>

		<br style="clear:both">
		<div style="text-align: center;">
			<h3>Only one resume submission is needed to consider you for multiple openings</h3>
		</div>

		<div><input type="submit" class="button" value="Submit Application" name="submit"></div>

		<?php echo "</form>"; ?>

		<script>
			jQuery.validator.setDefaults({
				debug: true,
				success: 'valid'
			});
			jQuery('.bullpen_application').validate({
				rules: {
					resume: {
						required: true,
						extension: 'doc|docx|pdf|txt|rtf|html'
					}
				}
			});
		</script>
<?php

	}

	public function Resume_Form_Shortcode_Handler($atts)
	{
		echo '<form action="' . get_site_url() . '/api/bullhorn/resume" method="post" enctype="multipart/form-data">';
		echo '<input type="file" name="resume" id="fileToUpload">';
		echo '<input type="submit" value="Upload Resume" name="submit">';
		echo '</form>';
	}
}

$shortcodes = new Bullpen_Extension_Shortcodes();
?>