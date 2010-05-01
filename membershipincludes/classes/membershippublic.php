<?php
if(!class_exists('membershippublic')) {

	class membershippublic {

		var $build = 1;

		var $db;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = $wpdb->prefix . $table;
			}

			// Set up Actions
			add_action( 'init', array(&$this, 'initialise_plugin') );




		}

		function membershippublic() {
			$this->__construct();
		}

		function initialise_plugin() {

			global $user, $member, $M_options, $M_Rules;

			$M_options = get_option('membership_options', array());

			$user = wp_get_current_user();


			if($user->ID > 0) {
				// Logged in - check there settings, if they have any.
				$member = new M_Membership($user->ID);
				// Load the levels for this member
				$member->load_levels();
			} else {
				// not logged in so limit based on stranger settings
				// need to grab the stranger settings
				$member = new M_Membership($user->ID);
				if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
					$member->assign_level($M_options['strangerlevel']);
				} else {
					// This user can't access anything on the site - redirect them to a signup page.
					add_action('pre_get_posts', array(&$this, 'show_noaccess_page'), 1 );
				}
			}


		}



		// loop and page overrides
		function process_posts_rules($wp_query) {
			$wp_query->query_vars['s'] = 'should';

			print_r($wp_query);
		}


		function show_noaccess_page() {
			global $wp_query, $M_options;

			if(!isset($M_options['page_template']) || $M_options['page_template'] == 'default') {
				$M_options['page_template'] = 'page.php';
			}

			if (file_exists(TEMPLATEPATH . '/' . $M_options['page_template'])) {

				if(empty($M_options['protectedmessagetitle'])) {
					$M_options['protectedmessagetitle'] = __('No access to this content','membership');
				}

				/**
				 * What we are going to do here, is create a fake post.  A post
				 * that doesn't actually exist. We're gonna fill it up with
				 * whatever values you want.  The content of the post will be
				 * the output from your plugin.  The questions and answers.
				 */
				/**
				 * Clear out any posts already stored in the $wp_query->posts array.
				 */
				$wp_query->posts = array();
				$wp_query->post_count = 0;

				/**
				 * Create a fake post.
				 */
				$post = new stdClass;

				/**
				 * The author ID for the post.  Usually 1 is the sys admin.  Your
				 * plugin can find out the real author ID without any trouble.
				 */
				$post->post_author = 1;

				/**
				 * The safe name for the post.  This is the post slug.
				 */
				$post->post_name = 'membershipnoaccess';

				/**
				 * Not sure if this is even important.  But gonna fill it up anyway.
				 */

				add_filter('the_permalink',create_function('$permalink', 'return "' . get_option('home') . '";'));


				$post->guid = get_bloginfo('wpurl');


				/**
				 * The title of the page.
				 */
				$post->post_title = esc_html(stripslashes($M_options['protectedmessagetitle']));

				/**
				 * This is the content of the post.  This is where the output of
				 * your plugin should go.  Just store the output from all your
				 * plugin function calls, and put the output into this var.
				 */
				$post->post_content = stripslashes($M_options['protectedmessage']);
				/**
				 * Fake post ID to prevent WP from trying to show comments for
				 * a post that doesn't really exist.
				 */
				$post->ID = -1;

				/**
				 * Static means a page, not a post.
				 */
				$post->post_status = 'publish';
				$post->post_type = 'post';

				/**
				 * Turning off comments for the post.
				 */
				$post->comment_status = 'closed';

				/**
				 * Let people ping the post?  Probably doesn't matter since
				 * comments are turned off, so not sure if WP would even
				 * show the pings.
				 */
				$post->ping_status = 'open';

				$post->comment_count = 0;

				/**
				 * You can pretty much fill these up with anything you want.  The
				 * current date is fine.  It's a fake post right?  Maybe the date
				 * the plugin was activated?
				 */
				$post->post_date = current_time('mysql');
				$post->post_date_gmt = current_time('mysql', 1);

				/**
				 * Now add our fake post to the $wp_query->posts var.  When "The Loop"
				 * begins, WordPress will find one post: The one fake post we just
				 * created.
				 */
				$wp_query->posts[] = $post;
				$wp_query->post_count = 1;
				$wp_query->is_home = false;

				/**
				 * And load up the template file.
				 */
				ob_start('template');

				load_template(TEMPLATEPATH . '/' . 'page.php');

				ob_end_flush();

				/**
				 * YOU MUST DIE AT THE END.  BAD THINGS HAPPEN IF YOU DONT
				 */
				die();
			}

			return $post;
		}




	}

}
?>