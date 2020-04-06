=== Plugin Name ===
Contributors: andrewryno
Donate link: http://bullhorntowordpress.com
Tags: bullhorn
Requires at least: 3.6
Tested up to: 4.4.1
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display. Posts are auto-created by synchronizing with Bullhorn auto-deleted if not public or active on Bullhorn.

== Description ==

REQUIRES: PHP 5.4.0 (released 2012/03/01)

This plugin adds Bullhorn jobs to a custom post types (Job Listings) for front-end display. Posts are auto-created by synchronizing with Bullhorn auto-deleted if not public or active on Bullhorn. There is no way to manage the Bullhorn jobs here, the admin menu for Job Listings should be used for viewing only. Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files for special layout.

There is also a shortcode that can be used in sidebar widgets, other pages, etc. Example usages:

Default usage:
[bullhorn]

== Additional Shortcodes ==

Shows contract jobs in CA:
[bullhorn state="California" type="Contract"]

Shows 50 jobs with their posting date:
[bullhorn limit=50 show_date=true]

Only shows jobs that have the word "Intern" in the title:
[bullhorn title="Intern"]

There are two other shortcodes to a list of categories and states available (no options on either):

[bullhorn_categories]

[bullhorn_states]

To have the jobs display in two, three or four columns add:
columns=X to the shortcode on site. Replace X with the number of desired columns.

ex. [bullhorn limit=50 columns=2]

Lastly, there is a shortcode to generate a search form to search job listings:

[bullhorn_search]

== Installation Documentation ==

Thank you for purchasing our Bullhorn to WordPress system. Here is the documentation
needed to integrate the system on your site.

Credentials needed from Bullhorn:

Client ID
Client Secret
Bullhorn API Username
Bullhorn API Password

Please note the above credentials are for the Bullhorn API and not for logging into the Bullhorn
ATS.

VERY IMPORTANT: Please instruct Bullhorn to set your (or your client’s) Bullhorn API redirect
URI to an exact match of your site. If you are deploying this on a staging site, instruct Bullhorn
to set with the staging URL, and once the site goes live you can ask them to change the URI to
the production site.

Implementation Instructions

1. Upload the Bullhorn2WP-master.zip file as a WordPress plugin.
Dashboard > Plugins > Add New > Upload
Once the files are uploaded click Activate.

2. Create the page that you want your job listings to show on as a list. This page will have have
jobs from your Bullhorn system that are marked open and public.

3. On the page created enter one of our shortcodes to help organize the layout of the listings:
[bullhorn] displays all jobs in one column
[bullhorn state=“xyz”] shows jobs from listed state only
[bullhorn_categories] displays jobs listed in certain categories in Bullhorn
[bullhorn limit=50] shows the most rect 50 entries. The number 50 can be set to any number, but
it is highly advised not to set higher than 200.
[bullhorn columns=2] Displays jobs in 2 columns. The number of columns can be set up to 4.
All short codes can be used combination with one another. ex [bullhorn limit=50 3 columns]

4. Create a form and place on form on a new page or applicants respond to posted jobs and
contact the recruiter

5. Navigate to the Bullhorn Setting Page
Dashboard > Settings > Bullhorn
Key in the API credentials
Leave Client Corporation Blank to Return Jobs From Multiple Client Accounts in Bullhorn
Enter the page path you created where the jobs should display ex. /jobs-page
Select the page you placed your form
Select Listings Sort
Select Description Field

Click Connect To Bullhorn and enter the Bullhorn API username and credentials
6. If all instructions were followed and the Bullhorn settings are correct you can navigate to a
‘Job Listings’ post-type in the dashboard and see all published jobs.

— NOTE ON PUBLISHING JOBS IN BULLHORN —
Marking Bullhorn Jobs Open and Public

[From an email from Bullhorn Support:] 
The job publishing status is system defined: -1
submitted but wait for approval,0 not submitted and 1 approval. When you create the job, in the
edit page, you can only have two options to choose from: 0 and -1.
This is a place where people often trip up. All jobs that you want to show on site need to be
marked as 1.

Once a job is entered (correctly) in Bullhorn it may take up to 90 minutes to post to the
WordPress site. We set the ‘cron’ to run every 90 minutes to lessen the impact on the Bullhorn
