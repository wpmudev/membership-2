<?php
if(!class_exists('membershipcron')) {

	class membershipcron {

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
				$this->$table = membership_db_prefix($this->db, $table);
			}

			add_action('plugins_loaded', array(&$this, 'load_textdomain'));

			// Set up Actions
			add_action('init', array(&$this, 'initialise_plugin'), 1 );


		}

		function membershipcron() {
			$this->__construct();
		}

		function load_textdomain() {

			$locale = apply_filters( 'membership_locale', get_locale() );
			$mofile = membership_dir( "membershipincludes/languages/membership-$locale.mo" );

			if ( file_exists( $mofile ) )
				load_textdomain( 'membership', $mofile );

		}

		function initialise_plugin() {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite, $M_active, $bp;

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true ) {
				if(function_exists('get_blog_option')) {
					$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
				} else {
					$M_options = get_option('membership_options', array());
				}
			} else {
				$M_options = get_option('membership_options', array());
			}

			// Check if the membership plugin is active
			$M_active = M_get_membership_active();

			// Create our subscription page shortcode
			add_shortcode('subscriptionform', array(&$this, 'do_subscription_shortcode') );
			add_shortcode('accountform', array(&$this, 'do_account_shortcode') );
			add_shortcode('upgradeform', array(&$this, 'do_upgrade_shortcode') );
			add_shortcode('renewform', array(&$this, 'do_renew_shortcode') );

			// Moved extra shortcodes over to the main plugin for new registration forms
			add_shortcode('subscriptiontitle', array(&$this, 'do_subscriptiontitle_shortcode') );
			add_shortcode('subscriptiondetails', array(&$this, 'do_subscriptiondetails_shortcode') );
			add_shortcode('subscriptionprice', array(&$this, 'do_subscriptionprice_shortcode') );
			add_shortcode('subscriptionbutton', array(&$this, 'do_subscriptionbutton_shortcode') );

			do_action('membership_register_shortcodes');

			// Check if we are on a membership specific page
			add_filter('the_posts', array(&$this, 'check_for_membership_pages'), 1);
			// Check for subscription shortcodes - and if needed queue styles
			add_filter('the_posts', array(&$this, 'add_subscription_styles'));

			$user = wp_get_current_user();
			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin') || $M_active == 'no') {
				// Admins can see everything
				return;
			}

			if( $M_active == 'no' ) {
				// The plugin isn't active so just return
				return;
			}

			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin')) {
				// Admins can see everything - unless we have a cookie set to limit viewing
				if(empty($_COOKIE['membershipuselevel']) || $_COOKIE['membershipuselevel'] == '0') {
					return;
				}
			}

			// More tags
			if( isset($M_options['moretagdefault']) && $M_options['moretagdefault'] == 'no' ) {
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

				// Shortcodes now default to protected for those entered by the user (which will be none for new users / installs)
				$this->override_shortcodes();
			}

			// Downloads protection
			if(!empty($M_options['membershipdownloadgroups'])) {
				add_filter('the_content', array(&$this, 'protect_download_content') );
			}

			// Makes sure that despite other rules, the pages set in the options panel are available to the user
			add_action('pre_get_posts', array(&$this, 'ensure_option_pages_visible'), 999 );
			// check for a no-access page and always filter it if needed
			if(!empty($M_options['nocontent_page']) && $M_options['nocontent_page'] != $M_options['registration_page']) {
				add_filter('get_pages', array(&$this, 'hide_nocontent_page_from_menu'), 99);
			}

			// New registration form settings
			if( (isset($M_options['formtype']) && $M_options['formtype'] == 'new') ) {
				add_action( 'wp_ajax_nopriv_buynow', array(&$this, 'popover_signup_form') );

				//login and register are no-priv only because, well they aren't logged in or registered
				add_action( 'wp_ajax_nopriv_register_user', array(&$this, 'popover_register_process') );
				add_action( 'wp_ajax_nopriv_login_user', array(&$this, 'popover_login_process') );

				// if logged in:
				add_action( 'wp_ajax_buynow', array(&$this, 'popover_sendpayment_form') );
				add_action( 'wp_ajax_register_user', array(&$this, 'popover_register_process') );
				add_action( 'wp_ajax_login_user', array(&$this, 'popover_login_process') );
			}

		}

		function initialise_membership_protection($wp) {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite, $M_active;
			// Set up some common defaults

			static $initialised = false;

			if($initialised) {
				// ensure that this is only called once, so return if we've been here already.
				return;
			}

			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}

			if( $M_active == 'no' ) {
				// The plugin isn't active so just return
				return;
			}

			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin')) {
				// Admins can see everything - unless we have a cookie set to limit viewing
				if(!empty($_COOKIE['membershipuselevel']) && $_COOKIE['membershipuselevel'] != '0') {

					$level_id = (int) $_COOKIE['membershipuselevel'];

					$member = new M_Membership($user->ID);
					$member->assign_level( $level_id, true );
				} else {
					return;
				}
			} else {
				// We are not a membershipadmin user
				if(!empty($wp->query_vars['feed'])) {
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
							add_filter('the_posts', array(&$this, 'show_noaccess_feed'), 1 );
						}
					}
				} else {
					// Users
					$member = new M_Membership($user->ID);

					if($user->ID > 0 && $member->has_levels()) {
						// Load the levels for this member - and associated rules
						$member->load_levels( true );
					} else {
						// not logged in so limit based on stranger settings
						// need to grab the stranger settings
						if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
							$member->assign_level( $M_options['strangerlevel'], true );
						} else {
							// This user can't access anything on the site - .
							add_filter('comments_open', array(&$this, 'close_comments'), 99, 2);
							// Changed for this version to see if it helps to get around changed in WP 3.5
							//add_action('pre_get_posts', array(&$this, 'show_noaccess_page'), 1 );
							add_action('the_posts', array(&$this, 'show_noaccess_page'), 1 );
							//the_posts
							// Hide all pages from menus - except the signup one
							add_filter('get_pages', array(&$this, 'remove_pages_menu'));
							// Hide all categories from lists
							add_filter( 'get_terms', array(&$this, 'remove_categories'), 1, 3 );
						}
					}
				}
			}

			// Set up the level shortcodes here
			$shortcodes = apply_filters('membership_level_shortcodes', array() );
			if(!empty($shortcodes)) {
				foreach($shortcodes as $key => $value) {
					if(!empty($value)) {
						if($member->has_level($key)) {
							// member is on this level so can see the content
							add_shortcode(stripslashes(trim($value)), array(&$this, 'do_level_shortcode') );
						} else {
							// member isn't on this level and so can't see the content
							add_shortcode(stripslashes(trim($value)), array(&$this, 'do_levelprotected_shortcode') );
						}
					}
				}
			}

			$shortcodes = apply_filters('membership_not_level_shortcodes', array() );
			if(!empty($shortcodes)) {
				foreach($shortcodes as $key => $value) {
					if(!empty($value)) {
						if(!$member->has_level($key)) {
							// member is on this level so can see the content
							add_shortcode(stripslashes(trim($value)), array(&$this, 'do_level_shortcode') );
						} else {
							// member isn't on this level and so can't see the content
							add_shortcode(stripslashes(trim($value)), array(&$this, 'do_levelprotected_shortcode') );
						}
					}
				}
			}

			do_action('membership-add-shortcodes');

			// Set the initialisation status
			$initialised = true;

		}

	}

}
?>