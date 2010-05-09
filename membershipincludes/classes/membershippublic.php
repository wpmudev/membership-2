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
			add_filter( 'query_vars', array(&$this, 'add_queryvars') );
			add_action('generate_rewrite_rules', array(&$this, 'add_rewrites'));

			// Add protection
			add_action('pre_get_posts', array(&$this, 'initialise_membership_protection'), 1 );
			// add feed protection
			//add_action( 'do_feed_rss', array(&$this, 'validate_feed_user'), 1 );
			add_action('pre_get_posts', array(&$this, 'handle_download_protection'), 2 );

		}

		function membershippublic() {
			$this->__construct();
		}

		function initialise_plugin() {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite;

			$M_options = get_option('membership_options', array());

			// More tags
			if($M_options['moretagdefault'] == 'no' ) {
				// More tag content is not visible by default - works for both web and rss content - unfortunately
				add_filter('the_content_more_link', array(&$this, 'show_moretag_protection'), 99, 2);
				add_filter('the_content', array(&$this, 'replace_moretag_content'), 1);
				add_filter('the_content_feed', array(&$this, 'replace_moretag_content'), 1);
			}

			// Shortcodes setup
			if(!empty($M_options['membershipshortcodes'])) {
				foreach($M_options['membershipshortcodes'] as $key => $value) {
					if(!empty($value)) {
						add_shortcode(stripslashes(trim($value)), array(&$this, 'do_membership_shortcode') );
					}
				}
			}

			// Check the shortcodes default and override if needed
			if($M_options['shortcodedefault'] == 'no' ) {
				$this->override_shortcodes();
			}

			// Downloads protection
			if(!empty($M_options['masked_url'])) {
				add_filter('the_content', array(&$this, 'protect_download_content') );
			}

		}

		function add_queryvars($vars) {

			if(!in_array('feedkey',$vars)) $vars[] = 'feedkey';
			if(!in_array('protectedfile',$vars)) $vars[] = 'protectedfile';

			return $vars;
		}

		function add_rewrites($wp_rewrite) {

			global $M_options;

			// This function adds in the api rewrite rules
			// Note the addition of the namespace variable so that we know these are vent based
			// calls

			if(!empty($M_options['masked_url'])) {
				$new_rules = array( trailingslashit($M_options['masked_url']) . '(.+)' =>  'index.php?protectedfile=' . $wp_rewrite->preg_index(1) );
			}

			$new_rules = apply_filters('M_rewrite_rules', $new_rules);

		  	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;

			return $wp_rewrite;
		}

		function initialise_membership_protection($wp_query) {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite;
			// Set up some common defaults

			static $initialised = false;

			if($initialised) {
				// ensure that this is only called once, so return if we've been here already.
				return;
			}

			if(!empty($wp_query->query_vars['feed'])) {
				// This is a feed access
				// Set the feed rules
				if(isset($_GET['k'])) {
					$key = $_GET['k'];

					$user_id = $this->find_user_from_key($key);
					$user_id = (int) $user_id;
					if($user_id > 0) {
						// Logged in - check there settings, if they have any.
						$member = new M_Membership($user_id);
						// Load the levels for this member - and associated rules
						$member->load_levels( true );
					} else {
						$member = new M_Membership(false);
						if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
							$member->assign_level($M_options['strangerlevel'], true );
						} else {
							// This user can't access anything on the site - show a blank feed.
							//add_action('pre_get_posts', array(&$this, 'show_noaccess_feed'), 1 );
							//the_posts
							add_filter('the_posts', array(&$this, 'show_noaccess_feed'), 1 );
						}
					}

				} else {
					// not passing a key so limit based on stranger settings
					// need to grab the stranger settings
					$member = new M_Membership($user->ID);
					if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
						$member->assign_level($M_options['strangerlevel'], true );
					} else {
						// This user can't access anything on the site - show a blank feed.
						//add_action('pre_get_posts', array(&$this, 'show_noaccess_feed'), 1 );
						//the_posts
						add_filter('the_posts', array(&$this, 'show_noaccess_feed'), 1 );
					}
				}


			} else {
				// This is a website access
				// Set the website rules
				// Set up the user based defaults and load the levels
				$user = wp_get_current_user();

				if($user->ID > 0) {
					// Logged in - check there settings, if they have any.
					$member = new M_Membership($user->ID);
					// Load the levels for this member - and associated rules
					$member->load_levels( true );
				} else {
					// not logged in so limit based on stranger settings
					// need to grab the stranger settings
					$member = new M_Membership($user->ID);
					if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
						$member->assign_level($M_options['strangerlevel'], true );
					} else {
						// This user can't access anything on the site - redirect them to a signup page.
						add_action('pre_get_posts', array(&$this, 'show_noaccess_page'), 1 );
					}
				}
			}

			// Set the initialisation status
			$initialised = true;

		}

		function handle_download_protection($wp_query) {

			global $wpdb;

			if(!empty($wp_query->query_vars['protectedfile'])) {
				$protected = explode("/", $wp_query->query_vars['protectedfile']);

				$filename = array_pop($protected);
				$fileid = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . mysql_real_escape_string($filename) . "%'" );

				if(!empty($fileid)) {
					// check for protection
					$protected = get_post_meta($fileid, '_membership_protected_content', true);
					if($protected == 'yes') {
						// check we can see it
					} else {
						// it's not protected so grab and display it
						echo "yep";
					}
				}

				die();
			}


			//query_vars

		}

		function find_user_from_key($key = false) {

			global $wpdb;

			$sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 0,1", 'M_Feedkey', $key );

			$user_id = $wpdb->get_var($sql);

			return $user_id;

		}

		// loop and page overrides

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

		// Output the protected shortcode content
		function do_membership_shortcode($atts, $content = null, $code = "") {

			return $content;

		}

		// Show the protected shortcode message
		function do_protected_shortcode($atts, $content = null, $code = "") {

			global $M_options;

			return stripslashes($M_options['shortcodemessage']);

		}

		// Override the shortcode to display a protected message instead
		function override_shortcodes() {

			global $M_shortcode_tags, $shortcode_tags;

			$M_shortcode_tags = $shortcode_tags;

			foreach($shortcode_tags as $key => $function) {
				$shortcode_tags[$key] = array(&$this, 'do_protected_shortcode');
			}

			return $content;
		}

		function show_noaccess_feed($wp_query) {

			global $M_options;

			//$wp_query->query_vars['post__in'] = array(0);

			if(empty($M_options['protectedmessagetitle'])) {
				$M_options['protectedmessagetitle'] = __('No access to this content','membership');
			}

			/**
			 * What we are going to do here, is create a fake post.  A post
			 * that doesn't actually exist. We're gonna fill it up with
			 * whatever values you want.  The content of the post will be
			 * the output from your plugin.  The questions and answers.
			 */

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

			return array($post);

		}

		function show_noaccess_page($wp_query) {

			global $M_options;

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

		// Content / downloads protection
		function protect_download_content($the_content) {

			global $M_options;

			$the_content = str_replace($M_options['original_url'], trailingslashit(get_option('home')) . $M_options['masked_url'], $the_content);

			return $the_content;

		}

		// Feeds protection

		function validate_feed_user($wp_query) {
			//print_r($wp_query);
		}


	}

}
?>