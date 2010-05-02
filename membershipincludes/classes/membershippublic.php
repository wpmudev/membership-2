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

			// Set up some common defaults

			// More tags
			if($M_options['moretagdefault'] == 'no' ) {
				// More tag content is not visible by default
				add_filter('the_content_more_link', array(&$this, 'show_moretag_protection'), 99, 2);
				add_filter('the_content', array(&$this, 'replace_moretag_content'), 1);
			}
			//$output .= apply_filters( 'the_content_more_link', ' <a href="' . get_permalink() . "#more-$id\" class=\"more-link\">$more_link_text</a>", $more_link_text );
			//the_content

			// Set up the user based defaults and load the levels
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

		function show_moretag_protection($more_tag_link, $more_tag) {

			global $M_options;

			return stripslashes($M_options['moretagmessage']);

		}

		function replace_moretag_content($the_content) {

			global $M_options;

			$morestartsat = strpos($the_content, '<span id="more-');

			if($morestartsat !== false) {
				$the_content = substr($the_content, 0, $morestartsat);
				$the_content .= stripslashes($M_options['moretagmessage']);
			}

			return $the_content;

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

				$post = new stdClass;
				$post->post_author = 1;
				$post->post_name = 'membershipnoaccess';
				add_filter('the_permalink',create_function('$permalink', 'return "' . get_option('home') . '";'));
				$post->guid = get_bloginfo('wpurl');
				$post->post_title = esc_html(stripslashes($M_options['protectedmessagetitle']));
				$post->post_content = stripslashes($M_options['protectedmessage']);
				$post->ID = -1;
				$post->post_status = 'publish';
				$post->post_type = 'post';
				$post->comment_status = 'closed';
				$post->ping_status = 'open';
				$post->comment_count = 0;
				$post->post_date = current_time('mysql');
				$post->post_date_gmt = current_time('mysql', 1);

				// Reset $wp_query
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