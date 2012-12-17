<?php
if(!class_exists('membershippublic')) {

	class membershippublic {

		var $build = 2;

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
			add_filter('query_vars', array(&$this, 'add_queryvars') );
			add_action('generate_rewrite_rules', array(&$this, 'add_rewrites') );

			// Add protection
			add_action('parse_request', array(&$this, 'initialise_membership_protection'), 2 );
			// Download protection
			add_action('pre_get_posts', array(&$this, 'handle_download_protection'), 3 );

			// Payment return
			add_action('pre_get_posts', array(&$this, 'handle_paymentgateways'), 1 );

			// add feed protection
			add_filter('feed_link', array(&$this, 'add_feed_key'), 99, 2);

			// Register
			add_filter('register', array(&$this, 'override_register') );

			// Ultimate Facebook Compatibility
			add_filter( 'wdfb_registration_redirect_url', array(&$this, 'wdfb_registration_redirect_url') );

			// Level shortcodes filters
			add_filter( 'membership_level_shortcodes', array(&$this, 'build_level_shortcode_list' ) );
			add_filter( 'membership_not_level_shortcodes', array(&$this, 'build_not_level_shortcode_list' ) );

		}

		function wdfb_registration_redirect_url($url) {
			global $M_options;
			$url = get_permalink($M_options['registration_page']);
			return $url;
		}

		function membershippublic() {
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

		function add_queryvars($vars) {

			if(!in_array('feedkey',$vars)) $vars[] = 'feedkey';
			if(!in_array('protectedfile',$vars)) $vars[] = 'protectedfile';
			if(!in_array('paymentgateway',$vars)) $vars[] = 'paymentgateway';

			return $vars;
		}

		function add_rewrites($wp_rewrite) {

			global $M_options;

			// This function adds in the api rewrite rules
			// Note the addition of the namespace variable so that we know these are vent based
			// calls

			$new_rules = array();

			if(!empty($M_options['masked_url'])) {
				$new_rules[trailingslashit($M_options['masked_url']) . '(.*)'] = 'index.php?protectedfile=' . $wp_rewrite->preg_index(1);
			}

			$new_rules['paymentreturn/(.+)'] = 'index.php?paymentgateway=' . $wp_rewrite->preg_index(1);

			$new_rules = apply_filters('M_rewrite_rules', $new_rules);

		  	$wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules);

			return $wp_rewrite;
		}

		function override_register( $link ) {

			global $M_options;

			if ( ! is_user_logged_in() ) {
				if ( get_option('users_can_register') ) {
					// get the new registration stuff.
					if(!empty($M_options['registration_page'])) {
						$url = get_permalink( $M_options['registration_page'] );
						$link = preg_replace('/<a href(.+)a>/', '<a href="' . $url . '">' . __('Register', 'membership') . '</a>', $link);
					}

				}
			} else {
				// change to account page?
				if(!empty($M_options['account_page'])) {
					$url = get_permalink( $M_options['account_page'] );
					$link = preg_replace('/<a href(.+)a>/', '<a href="' . $url . '">' . __('My Account', 'membership') . '</a>', $link);
				}
			}

			return $link;
		}

		function add_feed_key( $output, $feed ) {
			global $user;

			if($user->ID > 0) {

				$member = new M_Membership($user->ID);

				if($member->is_member()) {
					$key = get_user_meta($user->ID, '_membership_key');

					if(empty($key)) {
						$key = md5($user->ID . $user->user_pass . time());
						update_user_meta($user->ID, '_membership_key', $key);
					}

					if(!empty($key)) {
						$output = add_query_arg('k', $key, untrailingslashit($output));
					}
				}

			}

			return $output;

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

		function remove_categories($terms, $taxonomies, $args) {

			foreach( (array) $terms as $key => $value ) {
				if($value->taxonomy == 'category') {
					unset($terms[$key]);
				}
			}

			return $terms;
		}

		function remove_pages_menu($pages) {

			global $M_options;

			foreach( (array) $pages as $key => $page ) {
				if(!empty($M_options['registration_page']) && $page->ID == $M_options['registration_page']) {
					// We want to keep this page available
				} else {
					unset($pages[$key]);
				}
			}

			return $pages;
		}

		function handle_paymentgateways($wp_query) {
			if(!empty($wp_query->query_vars['paymentgateway'])) {
				do_action( 'membership_process_payment_return', $wp_query->query_vars['paymentgateway'] );
				// exit();
			}
		}

		function handle_download_protection($wp_query) {

			global $user, $member, $wpdb, $M_options;

			if(!empty($wp_query->query_vars['protectedfile'])) {
				$protected = explode("/", $wp_query->query_vars['protectedfile']);
				$protected = array_pop( $protected );
			}

			if(empty($protected) && !empty($_GET['file'])) {
				$protected = $_GET['file'];
			}

			if(!empty($protected)) {
				// See if the filename has a size extension and if so, strip it out
				$filename_exp = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
				$filematch = array();
				if(preg_match($filename_exp, $protected, $filematch)) {
					// We have an image with an image size attached
					$newfile = $filematch[1] . "." . $filematch[3];
					$size_extension = "-" . $filematch[2];
				} else {
					$newfile = $protected;
					$size_extension = '';
				}
				// Process based on the protection type
				switch($M_options['protection_type']) {
					case 'complete' :	// Work out the post_id again
										$post_id = preg_replace('/^' . MEMBERSHIP_FILE_NAME_PREFIX . '/', '', $newfile);
										$post_id -= (INT) MEMBERSHIP_FILE_NAME_INCREMENT;

										if(is_numeric($post_id) && $post_id > 0) {
											$image = get_post_meta($post_id, '_wp_attached_file', true);
											if(!empty($size_extension)) {
												// Add back in a size extension if we need to
												$image = str_replace( '.' . pathinfo($image, PATHINFO_EXTENSION), $size_extension . '.' . pathinfo($image, PATHINFO_EXTENSION), $image );
												// hack to remove any double extensions :/ need to change when work out a neater way
												$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
											}
										}
										break;

					case 'hybrid' :		// Work out the post_id again

										$post_id = preg_replace('/^' . MEMBERSHIP_FILE_NAME_PREFIX . '/', '', $newfile);
										$post_id -= (INT) MEMBERSHIP_FILE_NAME_INCREMENT;

										if(is_numeric($post_id) && $post_id > 0) {
											$image = get_post_meta($post_id, '_wp_attached_file', true);
											if(!empty($size_extension)) {
												// Add back in a size extension if we need to
												$image = str_replace( '.' . pathinfo($image, PATHINFO_EXTENSION), $size_extension . '.' . pathinfo($image, PATHINFO_EXTENSION), $image );
												// hack to remove any double extensions :/ need to change when work out a neater way
												$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
											}
										}
										break;

					case 'basic' :
					default:			// The basic protection - need to change this
										$sql = $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s", '%' . $newfile . '%' );
										$post_id = $wpdb->get_var( $sql );

										if(empty($post_id)) {
											// Can't find the file in the first pass, try the second pass.
											$sql = $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s", '%' . $protected . '%');
											$post_id = $this->db->get_var( $sql );
										}

										if(is_numeric($post_id) && $post_id > 0) {
											$image = get_post_meta($post_id, '_wp_attached_file', true);
											if(!empty($size_extension)) {
												// Add back in a size extension if we need to
												$image = str_replace( '.' . pathinfo($image, PATHINFO_EXTENSION), $size_extension . '.' . pathinfo($image, PATHINFO_EXTENSION), $image );
												// hack to remove any double extensions :/ need to change when work out a neater way
												$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
											}
										}
										break;
				}


				if(!empty($image) && !empty($post_id) && is_numeric($post_id)) {
					// check for protection
					$group = get_post_meta($post_id, '_membership_protected_content_group', true);

					if(empty($group) || $group == 'no') {
						// it's not protected so grab and display it
						//$file = $wp_query->query_vars['protectedfile'];
						$this->output_file($image);
					} else {
						// check we can see it
						if(empty($member) || !method_exists($member, 'has_level_rule')) {
							$user = wp_get_current_user();
							$member = new M_Membership( $user->ID );
						}

						if( method_exists($member, 'has_level_rule') && $member->has_level_rule('downloads') && $member->pass_thru( 'downloads', array( 'can_view_download' => $group ) ) ) {
							//$file = $wp_query->query_vars['protectedfile'];
							$this->output_file($image);
						} else {
							$this->show_noaccess_image($wp_query);
						}
					}
				} else {
					// We haven't found anything so default to the no access image
					$this->show_noaccess_image($wp_query);
				}

				exit();
			}

		}

		function output_file($pathtofile) {

			global $wpdb, $M_options;

			// The directory and direct path dir
			$uploadpath = membership_wp_upload_dir();
			$file = trailingslashit($uploadpath) . $pathtofile;
			// The url and direct url
			$origpath = membership_upload_url();
			$trueurl = trailingslashit($origpath) . $pathtofile;

			if ( !is_file( $file ) ) {
				status_header( 404 );
				die( '404 &#8212; File not found.' );
			}

			$mime = wp_check_filetype( $file );
			if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
				$mime[ 'type' ] = mime_content_type( $file );

			if( $mime[ 'type' ] )
				$mimetype = $mime[ 'type' ];
			else
				$mimetype = 'image/' . substr( $trueurl, strrpos( $trueurl, '.' ) + 1 );

			header( 'Content-type: ' . $mimetype ); // always send this
			if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
				header( 'Content-Length: ' . filesize( $file ) );

			$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
			$etag = '"' . md5( $last_modified ) . '"';
			header( "Last-Modified: $last_modified GMT" );
			header( 'ETag: ' . $etag );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

			// Support for Conditional GET
			$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

			if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
				$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

			$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
			// If string is empty, return 0. If not, attempt to parse into a timestamp
			$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

			// Make a timestamp for our most recent modification...
			$modified_timestamp = strtotime($last_modified);

			if ( ( $client_last_modified && $client_etag )
				? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
				: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
				) {
				status_header( 304 );
				exit;
			}

			// If we made it this far, just serve the file
			readfile( $file );
		}

		function show_noaccess_image($wp_query) {

			$locale = apply_filters( 'membership_locale', get_locale() );
			if(file_exists(membership_dir( "membershipincludes/images/noaccess/noaccess-$locale.png" ))) {
				$file = membership_dir( "membershipincludes/images/noaccess/noaccess-$locale.png" );
				$trueurl = membership_url( "membershipincludes/images/noaccess/noaccess-$locale.png" );
			} elseif( file_exists(membership_dir( "membershipincludes/images/noaccess/noaccess.png" )) ) {
				$file = membership_dir( "membershipincludes/images/noaccess/noaccess.png" );
				$trueurl = membership_url( "membershipincludes/images/noaccess/noaccess.png" );
			}


			if(!empty($file)) {
				if ( !is_file( $file ) ) {
					status_header( 404 );
					die( '404 &#8212; File not found.' );
				}

				$mime = wp_check_filetype( $file );
				if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
					$mime[ 'type' ] = mime_content_type( $file );

				if( $mime[ 'type' ] )
					$mimetype = $mime[ 'type' ];
				else
					$mimetype = 'image/' . substr( $trueurl, strrpos( $trueurl, '.' ) + 1 );

				header( 'Content-type: ' . $mimetype ); // always send this
				if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
					header( 'Content-Length: ' . filesize( $file ) );

				$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
				$etag = '"' . md5( $last_modified ) . '"';
				header( "Last-Modified: $last_modified GMT" );
				header( 'ETag: ' . $etag );
				header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

				// Support for Conditional GET
				$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

				if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
					$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

				$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
				// If string is empty, return 0. If not, attempt to parse into a timestamp
				$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

				// Make a timestamp for our most recent modification...
				$modified_timestamp = strtotime($last_modified);

				if ( ( $client_last_modified && $client_etag )
					? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
					: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
					) {
					status_header( 304 );
					exit;
				}

				// If we made it this far, just serve the file
				readfile( $file );
			}

		}

		function find_user_from_key($key = false) {

			global $wpdb;

			//$key = get_usermeta($user->ID, '_membership_key');
			$sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 0,1", '_membership_key', $key );

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

		// Output the level based shortcode content
		function do_level_shortcode($atts, $content = null, $code = "") {

			return do_shortcode($content);

		}

		// Output the protected shortcode content
		function do_membership_shortcode($atts, $content = null, $code = "") {

			return do_shortcode($content);

		}

		// Show the protected shortcode message
		function do_protected_shortcode($atts, $content = null, $code = "") {

			global $M_options;

			return stripslashes($M_options['shortcodemessage']);

		}

		// Show the level based protected shortcode message
		function do_levelprotected_shortcode($atts, $content = null, $code = "") {

			global $M_options;

			// Set up the level shortcodes here
			$shortcodes = apply_filters('membership_level_shortcodes', array() );
			$notshortcodes = apply_filters('membership_not_level_shortcodes', array() );

			$code = strtolower( $code );

			if( substr( $code, 0, 4 ) !== "not-" ) {
				if(!empty($shortcodes)) {
					// search positive shortcodes first
					$id = array_search( $code, $shortcodes );
					if($id !== false) {
						// we have found a level so we need to check if it has a custom protected message, otherwise we'll just output the default main on
						$level = new M_Level( $id );
						$message = $level->get_meta( 'level_protectedcontent' );
						if(!empty($message)) {
							return stripslashes($message);
						}
					}
				}
			} else {
				if(!empty($notshortcodes)) {
					// search positive shortcodes first
					$id = array_search( $code, $notshortcodes );
					if($id !== false) {
						// we have found a level so we need to check if it has a custom protected message, otherwise we'll just output the default main on
						$level = new M_Level( $id );
						$message = $level->get_meta( 'level_protectedcontent' );
						if(!empty($message)) {
							return stripslashes($message);
						}
					}
				}
			}

			// If we are here then we have no custom message, or the shortcode wasn't found so just output the standard message
			if(isset($M_options['shortcodemessage'])) {
				return stripslashes($M_options['shortcodemessage']);
			} else {
				return '';
			}


		}


		function override_shortcodes() {
			// By default all the shortcodes are protected to override them here
			global $M_shortcode_tags, $shortcode_tags;

			$M_shortcode_tags = $shortcode_tags;

			if(!empty($M_options['membershipshortcodes'])) {
				foreach($M_options['membershipshortcodes'] as $key => $value) {
					if(!empty($value)) {
						$shortcode_tags[$value] = array(&$this, 'do_protected_shortcode');
					}
				}
			}

		}

		function may_be_singular($wp_query) {

			if( is_archive() || is_author() || is_category() || is_tag() || is_tax() || is_search() ) {
				return false;
			} else {
				return true;
			}

		}

		function check_for_posts_existance($posts, $wp_query) {

			global $bp, $wp_query;

			if(!empty($bp)) {
				// BuddyPress exists so we have to handle "pretend" pages.
				$thepage = substr($wp_query->query['pagename'], 0 , strpos($wp_query->query['pagename'], '/'));
				if(empty($thepage)) $thepage = $wp_query->query['pagename'];

				$bppages = apply_filters('membership_buddypress_pages', (array) $bp->root_components );

				if(in_array($thepage, $bppages)) {
					return $posts;
				}
			}

			$M_options = get_option('membership_options', array());

			if(empty($posts)) {

				if( !empty( $wp_query->query['pagename'] )) {
					// we have a potentially fake page that a plugin is creating or using.
					if( !in_array( $wp_query->query['pagename'], apply_filters( 'membership_notallowed_pagenames', array() ) ) ) {
						return $posts;
					} else {
						$this->show_noaccess_page($wp_query);
					}
				} else {

					if($M_options['override_404'] == 'yes') {

						// empty posts
						$this->show_noaccess_page($wp_query);
					} else {
						return $posts;
					}
				}

				if($this->posts_actually_exist() && $this->may_be_singular($wp_query)) {
					// we have nothing to see because it either doesn't exist, is a pretend or it's protected - move to no access page.
					$this->show_noaccess_page($wp_query);
				} else {
					return $posts;
				}

			}

			return $posts;

		}

		function posts_actually_exist() {

			$sql = $this->db->prepare( "SELECT count(*) FROM {$this->db->posts} WHERE post_type = 'post' AND post_status = 'publish'" );

			if($this->db->get_var( $sql ) > 0) {
				return true;
			} else {
				return false;
			}

		}

		function show_noaccess_feed($wp_query) {

			global $M_options;

			//$wp_query->query_vars['post__in'] = array(0);
			/**
			 * What we are going to do here, is create a fake post.  A post
			 * that doesn't actually exist. We're gonna fill it up with
			 * whatever values you want.  The content of the post will be
			 * the output from your plugin.  The questions and answers.
			 */

			if(!empty($M_options['nocontent_page'])) {
				// grab the content form the no content page
				$post = get_post( $M_options['nocontent_page'] );
			} else {
				if(empty($M_options['protectedmessagetitle'])) {
					$M_options['protectedmessagetitle'] = __('No access to this content','membership');
				}

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
			}

			return array($post);

		}

		function ensure_option_pages_visible($wp_query) {

			global $M_options;

			if(empty($wp_query->query_vars['post__in'])) {
				return;
			}

			$forchecking = array();

			if(!empty($M_options['registration_page'])) {
				$wp_query->query_vars['post__in'][] = $M_options['registration_page'];
				$forchecking[] = $M_options['registration_page'];
			}

			if(!empty($M_options['account_page'])) {
				$wp_query->query_vars['post__in'][] = $M_options['account_page'];
				$forchecking[] = $M_options['account_page'];
			}

			if(!empty($M_options['nocontent_page'])) {
				$wp_query->query_vars['post__in'][] = $M_options['nocontent_page'];
				$forchecking[] = $M_options['nocontent_page'];
			}

			if(!empty($M_options['registrationcompleted_page'])) {
				$wp_query->query_vars['post__in'][] = $M_options['registrationcompleted_page'];
				$forchecking[] = $M_options['registrationcompleted_page'];
			}

			if(!empty($M_options['subscriptions_page'])) {
				$wp_query->query_vars['post__in'][] = $M_options['subscriptions_page'];
				$forchecking[] = $M_options['subscriptions_page'];
			}

			if(is_array($wp_query->query_vars['post__not_in'])) {
				foreach($wp_query->query_vars['post__not_in'] as $key => $value) {
					if(in_array( $value, (array) $forchecking ) ) {
						unset($wp_query->query_vars['post__not_in'][$key]);
					}
				}
			}

			$wp_query->query_vars['post__in'] = array_unique($wp_query->query_vars['post__in']);

		}

		function hide_nocontent_page_from_menu($pages) {

			global $M_options;

			foreach( (array) $pages as $key => $page ) {
				if( ($page->ID == $M_options['nocontent_page']) || ($page->ID == $M_options['registrationcompleted_page'])) {
					unset($pages[$key]);
				}
			}

			return $pages;
		}

		//function show_noaccess_page($wp_query, $forceviewing = false) {
		function show_noaccess_page($posts, $forceviewing = false) {

			global $M_options;

			if(!empty($posts)) {

				if(count($posts) == 1 && isset($posts[0]->post_type) && $posts[0]->post_type == 'page') {
					// We are on a page so get the first page and then check for ones we want to allow
					$page = $posts[0];

					if(!empty($page->ID) && !empty($M_options['nocontent_page']) && $page->ID == $M_options['nocontent_page']) {
						return $posts;
					}

					if(!empty($page->ID) && !empty($M_options['registration_page']) && $page->ID == $M_options['registration_page']) {
						// We know what we are looking at, the registration page has been set and we are trying to access it
						return $posts;
					}

					if(!empty($page->ID) && !empty($M_options['account_page']) && $page->ID == $M_options['account_page']) {
						// We know what we are looking at, the registration page has been set and we are trying to access it
						return $posts;
					}

					if(!empty($page->ID) && !empty($M_options['registrationcompleted_page']) && $page->ID == $M_options['registrationcompleted_page']) {
						// We know what we are looking at, the registration page has been set and we are trying to access it
						return $posts;
					}

					if(!empty($page->ID) && !empty($M_options['subscriptions_page']) && $page->ID == $M_options['subscriptions_page']) {
						// We know what we are looking at, the registration page has been set and we are trying to access it
						return $posts;
					}

					// We are still here so we may be at a page that we shouldn't be able to see
					if(!empty($M_options['nocontent_page']) && isset($page->ID) && $page->ID != $M_options['nocontent_page'] && !headers_sent()) {
						// grab the content form the no content page
						$url = get_permalink( (int) $M_options['nocontent_page'] );

						wp_safe_redirect( $url );
						exit;
					} else {
						return $posts;
					}


				} else {
					// We could be on a posts page / or on a single post.
					if(count($posts) == 1) {
						// We could be on a single posts page, or only have the one post to view
						if(isset($posts[0]->post_type) && $posts[0]->post_type != 'nav_menu_item') {
							// We'll redirect if this isn't a navigation menu item
							$post = $posts[0];

							if(!empty($M_options['nocontent_page']) && isset($post->ID) && $post->ID != $M_options['nocontent_page'] && !headers_sent()) {
								// grab the content form the no content page
								$url = get_permalink( (int) $M_options['nocontent_page'] );

								wp_safe_redirect( $url );
								exit;
							} else {
								return $posts;
							}
						}
					} else {
						// Check the first post in the list
						if(isset($posts[0]->post_type) && $posts[0]->post_type != 'nav_menu_item') {
							// We'll redirect if this isn't a navigation menu item
							$post = $posts[0];

							if(!empty($M_options['nocontent_page']) && isset($post->ID) && $post->ID != $M_options['nocontent_page'] && !headers_sent()) {
								// grab the content form the no content page
								$url = get_permalink( (int) $M_options['nocontent_page'] );

								wp_safe_redirect( $url );
								exit;
							} else {
								return $posts;
							}
						}
					}

				}

			} else {
				// We don't have any posts, so we should just redirect to the no content page.
				if(!empty($M_options['nocontent_page']) && !headers_sent()) {
					// grab the content form the no content page
					$url = get_permalink( (int) $M_options['nocontent_page'] );

					wp_safe_redirect( $url );
					exit;
				} else {
					return $posts;
				}
			}

			// If we've reached here then something weird has happened :/
			return $posts;

			/*
			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				return;
			}
			*/

		}

		function close_comments($open, $postid) {

			return false;

		}

		// Content / downloads protection
		function protect_download_content($the_content) {

			global $M_options;

			$origpath = membership_upload_url();
			$newpath = trailingslashit(trailingslashit(get_option('home')) . $M_options['masked_url']);

			// Find all the urls in the post and then we'll check if they are protected
			/* Regular expression from http://blog.mattheworiordan.com/post/13174566389/url-regular-expression-for-links-with-or-without-the */

			$url_exp = '/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)/';

			$matches = array();
			if(preg_match_all($url_exp, $the_content, $matches)) {
				$home = get_option('home');
				if(!empty($matches) && !empty($matches[2])) {

					foreach((array) $matches[2] as $key => $domain) {
						if(untrailingslashit($home) == untrailingslashit($domain)) {
							$foundlocal = $key;
							$file = basename($matches[4][$foundlocal]);

							$filename_exp = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
							$filematch = array();
							if(preg_match($filename_exp, $file, $filematch)) {
								// We have an image with an image size attached
								$newfile = $filematch[1] . "." . $filematch[3];
								$size_extension = "-" . $filematch[2];
							} else {
								$newfile = $file;
								$size_extension = '';
							}

							$sql = $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s", '%' . $newfile . '%');
							$post_id = $this->db->get_var( $sql );
							if(empty($post_id)) {
								// Can't find the file in the first pass, try the second pass.
								$sql = $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s", '%' . $file . '%');
								$post_id = $this->db->get_var( $sql );
							}

							if(!empty($post_id)) {
								// Found the file and it's in the media library
								$protected = get_post_meta( $post_id, '_membership_protected_content_group', true );

								if(!empty($protected)) {
									// We have a protected file - so we'll mask it
									switch($M_options['protection_type']) {
										case 'complete' :	$protectedfilename = MEMBERSHIP_FILE_NAME_PREFIX . ($post_id + (int) MEMBERSHIP_FILE_NAME_INCREMENT) . $size_extension;
															$protectedfilename .= "." . pathinfo($newfile, PATHINFO_EXTENSION);

															$the_content = str_replace( $matches[0][$foundlocal], $newpath . $protectedfilename, $the_content );
															break;

										case 'hybrid' :		$protectedfilename = MEMBERSHIP_FILE_NAME_PREFIX . ($post_id + (int) MEMBERSHIP_FILE_NAME_INCREMENT) . $size_extension;
															$protectedfilename .= "." . pathinfo($newfile, PATHINFO_EXTENSION);

															$the_content = str_replace( $matches[0][$foundlocal], $newpath . "?file=" . $protectedfilename, $the_content );
															break;

										case 'basic' :
										default:			$the_content = str_replace( $matches[0][$foundlocal], str_replace( $origpath, $newpath, $matches[0][$foundlocal] ), $the_content );

															break;
									}
								}
							}

						}
					}
				}

			}

			return $the_content;

		}

		// Shortcodes

		function show_account_page( $content = null ) {

			global $bp, $profileuser, $user, $user_id;

			if(!is_user_logged_in()) {
				return apply_filters('membership_account_form_not_logged_in', $content );
			}

			require_once(ABSPATH . 'wp-admin/includes/user.php');

			$user = wp_get_current_user();

			$user_id = $user->ID;

			$profileuser = get_user_to_edit($user_id);

			$content = '';

			$content = apply_filters('membership_account_form_before_content', $content);

			ob_start();
			if( defined('MEMBERSHIP_ACCOUNT_FORM') && file_exists( MEMBERSHIP_ACCOUNT_FORM ) ) {
				include_once( MEMBERSHIP_ACCOUNT_FORM );
			} elseif(!empty($bp) && file_exists( apply_filters('membership_override_bpaccount_form', membership_dir('membershipincludes/includes/bp.account.form.php'), $user_id) )) {
				include_once( apply_filters('membership_override_bpaccount_form', membership_dir('membershipincludes/includes/bp.account.form.php'), $user_id) );
			} elseif( file_exists( apply_filters('membership_override_account_form', membership_dir('membershipincludes/includes/account.form.php'), $user_id) ) ) {
				include_once( apply_filters('membership_override_account_form', membership_dir('membershipincludes/includes/account.form.php'), $user_id) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_account_form_after_content', $content, $user_id);

			return $content;

		}

		function show_subpage_one($error = false) {

			global $bp;

			$content = '';

			$content = apply_filters('membership_subscription_form_registration_before_content', $content, $error);

			ob_start();
			if( defined('MEMBERSHIP_REGISTRATION_FORM') && file_exists( MEMBERSHIP_REGISTRATION_FORM ) ) {
				include_once( MEMBERSHIP_REGISTRATION_FORM );
			} elseif(!empty($bp) && file_exists( apply_filters('membership_override_bpregistration_form', membership_dir('membershipincludes/includes/bp.registration.form.php'), $error) )) {
				include_once( apply_filters('membership_override_bpregistration_form', membership_dir('membershipincludes/includes/bp.registration.form.php'), $error) );
			} elseif( file_exists( apply_filters('membership_override_registration_form', membership_dir('membershipincludes/includes/registration.form.php'), $error) ) ) {
				include_once( apply_filters('membership_override_registration_form', membership_dir('membershipincludes/includes/registration.form.php'), $error) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_registration_after_content', $content, $error);

			return $content;

		}

		function show_subpage_two($user_id) {

			$content = '';

			$content = apply_filters('membership_subscription_form_before_content', $content, $user_id);

			ob_start();
			if( defined('MEMBERSHIP_SUBSCRIPTION_FORM') && file_exists( MEMBERSHIP_SUBSCRIPTION_FORM ) ) {
				include_once( MEMBERSHIP_SUBSCRIPTION_FORM );
			} elseif(file_exists( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) ) ) {
				include_once( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_after_content', $content, $user_id );

			return $content;

		}

		function show_subpage_member() {

			$content = '';

			$content = apply_filters('membership_subscription_form_member_before_content', $content, $user_id);

			ob_start();
			if( defined('MEMBERSHIP_MEMBER_FORM') && file_exists( MEMBERSHIP_MEMBER_FORM ) ) {
				include_once( MEMBERSHIP_MEMBER_FORM );
			} elseif(file_exists( apply_filters('membership_override_member_form', membership_dir('membershipincludes/includes/member.form.php')) )) {
				include_once( apply_filters('membership_override_member_form', membership_dir('membershipincludes/includes/member.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_member_after_content', $content, $user_id );

			return $content;

		}

		function show_upgrade_page() {

			$content = '';

			$content = apply_filters('membership_upgrade_form_member_before_content', $content, $user_id);

			ob_start();
			if( defined('MEMBERSHIP_UPGRADE_FORM') && file_exists( MEMBERSHIP_UPGRADE_FORM ) ) {
				include_once( MEMBERSHIP_UPGRADE_FORM );
			} elseif(file_exists( apply_filters('membership_override_upgrade_form', membership_dir('membershipincludes/includes/upgrade.form.php')) )) {
				include_once( apply_filters('membership_override_upgrade_form', membership_dir('membershipincludes/includes/upgrade.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_upgrade_form_member_after_content', $content, $user_id );

			return $content;

		}

		function show_renew_page( $user_id = false ) {

			global $M_options;

			$content = '';

			$content = apply_filters('membership_renew_form_member_before_content', $content, $user_id);

			ob_start();
			if( defined('MEMBERSHIP_RENEW_FORM') && file_exists( MEMBERSHIP_RENEW_FORM ) ) {
				include_once( MEMBERSHIP_RENEW_FORM );
			} elseif(file_exists( apply_filters('membership_override_renew_form', membership_dir('membershipincludes/includes/renew.form.php')) )) {
				include_once( apply_filters('membership_override_renew_form', membership_dir('membershipincludes/includes/renew.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_renew_form_member_after_content', $content, $user_id );

			return $content;

		}

		function do_renew_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$error = array();

			$page = addslashes($_REQUEST['action']);

			$M_options = get_option('membership_options', array());

			$content = $this->show_renew_page();

			$content = apply_filters('membership_renew_form', $content);

			return $content;

		}

		function do_upgrade_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$error = array();

			$page = addslashes($_REQUEST['action']);

			$M_options = get_option('membership_options', array());

			$content = $this->show_upgrade_page();

			$content = apply_filters('membership_upgrade_form', $content);

			return $content;

		}

		function do_account_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$error = array();

			$page = addslashes($_REQUEST['action']);

			$M_options = get_option('membership_options', array());

			$content = $this->show_account_page( $content );

			$content = apply_filters('membership_account_form', $content);

			return $content;

		}

		function do_account_form() {

			global $wp_query, $M_options, $bp;

			$content = $this->show_account_page();

			return $content;

		}


		function do_renew_form() {
			global $wp_query, $M_options, $bp;

			$page = (isset($_REQUEST['action'])) ? addslashes($_REQUEST['action']) : '';
			if(empty($page)) {
				$page = 'renewform';
			}

			$content = '';

			switch($page) {

				case 'subscriptionsignup':
											if(is_user_logged_in()) {

												$member = current_member();
												list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode(':', $_POST['custom']);

												if( wp_verify_nonce($_REQUEST['_wpnonce'], 'free-sub_' . $sub_id) ) {
													$gateway = $_POST['gateway'];
													// Join the new subscription
													$member->create_subscription($sub_id, $gateway);
													// Timestamp the update
													update_user_meta( $user_id, '_membership_last_upgraded', time());
												}
											} else {
												// check if a custom is posted and of so then process the user
												if(isset($_POST['custom'])) {
													list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode(':', $_POST['custom']);

													if( wp_verify_nonce($_REQUEST['_wpnonce'], 'free-sub_' . $sub_id) ) {
														$gateway = $_POST['gateway'];
														// Join the new subscription
														$member = new M_Membership( $user_id );
														$member->create_subscription($sub_id, $gateway);
														// Timestamp the update
														update_user_meta( $user_id, '_membership_last_upgraded', time());
														// Login?
														if(!defined('MEMBERSHIP_NOLOGINONREGISTRATION')) {
															if(!headers_sent()) wp_set_auth_cookie( $user_id );
														}
													}
												}
											}
											$content = $this->show_renew_page();
											break;

				case 'renewform':
				default:					// Just show the page
											$content = $this->show_renew_page();
											break;





			}

			return $content;
		}

		function output_subscriptionform() {

			global $wp_query, $M_options, $bp;

			if(empty($user_id)) {
				$user = wp_get_current_user();

				if(!empty($user->ID) && is_numeric($user->ID) ) {
					$user_id = $user->ID;
				} else {
					$user_id = 0;
				}
			}

			$content = apply_filters('membership_subscription_form_before_content', '', $user_id);
			ob_start();
			if( defined('MEMBERSHIP_SUBSCRIPTION_FORM') && file_exists( MEMBERSHIP_SUBSCRIPTION_FORM ) ) {
				include_once( MEMBERSHIP_SUBSCRIPTION_FORM );
			} elseif(file_exists( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) ) ) {
				include_once( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_after_content', $content, $user_id );

			return $content;
		}

		function output_registeruser( $error = false ) {

			global $wp_query, $M_options, $bp;

			$subscription = (int) $_GET['subscription'];
			$content = apply_filters('membership_subscription_form_registration_before_content', '', $error);
			ob_start();
			if( defined('MEMBERSHIP_REGISTRATION_FORM') && file_exists( MEMBERSHIP_REGISTRATION_FORM ) ) {
				include_once( MEMBERSHIP_REGISTRATION_FORM );
			} elseif(!empty($bp) && file_exists( apply_filters('membership_override_bpregistration_form', membership_dir('membershipincludes/includes/bp.registration.form.php'), $error) )) {
				include_once( apply_filters('membership_override_bpregistration_form', membership_dir('membershipincludes/includes/bp.registration.form.php'), $error) );
			} elseif( file_exists( apply_filters('membership_override_registration_form', membership_dir('membershipincludes/includes/registration.form.php'), $error) ) ) {
				include_once( apply_filters('membership_override_registration_form', membership_dir('membershipincludes/includes/registration.form.php'), $error) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_registration_after_content', $content, $error);

			return $content;
		}

		function output_paymentpage( $user_id = false ) {

			global $wp_query, $M_options;

			$subscription = (int) $_REQUEST['subscription'];

			if(!$user_id) {
				$user = wp_get_current_user();

				if(!empty($user->ID) && is_numeric($user->ID) ) {
					$member = new M_Membership( $user->ID);
				} else {
					$member = current_member();
				}
			} else {
				$member = new M_Membership( $user_id );
			}

			if(empty($error)) {
				$error = '';
			}

			$content = apply_filters('membership_subscription_form_payment_before_content', '', $error);
			ob_start();
			if( defined('MEMBERSHIP_PAYMENT_FORM') && file_exists( MEMBERSHIP_PAYMENT_FORM ) ) {
				include_once( MEMBERSHIP_PAYMENT_FORM );
			} elseif( file_exists( apply_filters('membership_override_payment_form', membership_dir('membershipincludes/includes/payment.form.php'), $error) ) ) {
				include_once( apply_filters('membership_override_payment_form', membership_dir('membershipincludes/includes/payment.form.php'), $error) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_payment_after_content', $content, $error);

			return $content;

		}

		function do_subscription_form() {

			global $wp_query, $M_options, $bp;

			if(isset($_REQUEST['action'])) $page = addslashes($_REQUEST['action']);
			if(empty($page)) {
				$page = 'subscriptionform';
			}

			$content = '';

			switch($page) {

				case 'subscriptionform':	$content = $this->output_subscriptionform();
											break;

				case 'registeruser':		if(!is_user_logged_in()) {
												$content = $this->output_registeruser();
											} else {
												$content = $this->output_paymentpage();
											}
											break;

				case 'subscriptionsignup':	if(!is_user_logged_in()) {
												$content = $this->output_registeruser();
											} else {
												$content = $this->output_paymentpage();
											}
											break;

				case 'validatepage1':	// Page 1 of the form has been submitted - validate
									//include_once(ABSPATH . WPINC . '/registration.php');

									$required = array(	'user_login' => __('Username', 'membership'),
														'user_email' => __('Email address','membership'),
														'password' => __('Password','membership'),
														'password2' => __('Password confirmation','membership'),
													);

									$error = new WP_Error();

									foreach($required as $key => $message) {
										if(empty($_POST[$key])) {
											$error->add($key, __('Please ensure that the ', 'membership') . "<strong>" . $message . "</strong>" . __(' information is completed.','membership'));
										}
									}

									if($_POST['password'] != $_POST['password2']) {
										$error->add('passmatch', __('Please ensure the passwords match.','membership'));
									}

									if(username_exists(sanitize_user($_POST['user_login']))) {
										$error->add('usernameexists', __('That username is already taken, sorry.','membership'));
									}

									if(email_exists($_POST['user_email'])) {
										$error->add('emailexists', __('That email address is already taken, sorry.','membership'));
									}

									$error = apply_filters( 'membership_subscription_form_before_registration_process', $error );

									$result = array('user_name' => $_POST['user_login'], 'orig_username' => $_POST['user_login'], 'user_email' => $_POST['user_email'], 'errors' => $error);

									$result = apply_filters('wpmu_validate_user_signup', $result);

									$error = $result['errors'];

									// Hack for now - eeek
									$anyerrors = $error->get_error_code();
									if(is_wp_error($error) && empty($anyerrors)) {
										// No errors so far - error reporting check for final add user *note $error should always be an error object becuase we created it as such.
										$user_id = wp_create_user( sanitize_user($_POST['user_login']), $_POST['password'], $_POST['user_email'] );

										if(is_wp_error($user_id) && method_exists($userid, 'get_error_message')) {
											$error->add('userid', $userid->get_error_message());
										} else {
											$member = new M_Membership( $user_id );
											if(empty($M_options['enableincompletesignups']) || $M_options['enableincompletesignups'] != 'yes') {
												$member->deactivate();
											}
											$creds = array(
												'user_login' => $_POST['user_login'],
												'user_password' => $_POST['password'],
												'remember' => true
											);
											$is_ssl = (isset($_SERVER['https']) && $_SERVER['https'] == 'on' ? true : false);
											$user = wp_signon( $creds, $is_ssl );

											if( has_action('membership_susbcription_form_registration_notification') ) {
												do_action('membership_susbcription_form_registration_notification', $user_id, $_POST['password']);
											} else {
												wp_new_user_notification($user_id, $_POST['password']);
											}

										}
									}

									do_action( 'membership_subscription_form_registration_process', $error, $user_id );

									// Hack for now - eeek
									$anyerrors = $error->get_error_code();
									if(is_wp_error($error) && !empty($anyerrors)) {
										// we have an error - output
										$messages = $error->get_error_messages();
										$content .= "<div class='alert alert-error'>";
										$content .= implode('<br/>', $messages);
										$content .= "</div>";

										// Show the page again so that it can display the errors
										$content = $this->output_registeruser( $content );

									} else {
										// everything seems fine (so far), so we have our queued user so let's
										// add do the payment and completion page
										if(!defined('MEMBERSHIP_NOLOGINONREGISTRATION')) {
											if(!headers_sent()) {
												wp_set_current_user($user_id);
												wp_set_auth_cookie($user_id);
											}
										}


										$content = $this->output_paymentpage( $user_id );
									}

									break;

				case 'validatepage1bp':
									global $bp;

									//include_once(ABSPATH . WPINC . '/registration.php');

									$required = array(	'signup_username' => __('Username', 'membership'),
														'signup_email' => __('Email address','membership'),
														'signup_password' => __('Password','membership'),
														'signup_password_confirm' => __('Password confirmation','membership'),
													);

									$error = new WP_Error();

									foreach($required as $key => $message) {
										if(empty($_POST[$key])) {
											$error->add($key, __('Please ensure that the ', 'membership') . "<strong>" . $message . "</strong>" . __(' information is completed.','membership'));
										}
									}

									if($_POST['signup_password'] != $_POST['signup_password_confirm']) {
										$error->add('passmatch', __('Please ensure the passwords match.','membership'));
									}

									if(username_exists(sanitize_user($_POST['signup_username']))) {
										$error->add('usernameexists', __('That username is already taken, sorry.','membership'));
									}

									if(email_exists($_POST['signup_email'])) {
										$error->add('emailexists', __('That email address is already taken, sorry.','membership'));
									}

									// Initial fix provided by user: cmurtagh - modified to add extra checks and rejigged a bit
									// Run the buddypress validation
									do_action( 'bp_signup_validate' );

							        // Add any errors to the action for the field in the template for display.
        							if ( !empty( $bp->signup->errors ) ) {
										foreach ( (array)$bp->signup->errors as $fieldname => $error_message ) {
												$error->add($fieldname, $error_message);
										}
									}

									$meta_array = array();

									// xprofile required fields
									/* Now we've checked account details, we can check profile information */
									//if ( function_exists( 'xprofile_check_is_required_field' ) ) {
									if ( function_exists('bp_is_active') && bp_is_active( 'xprofile' ) ) {

										/* Make sure hidden field is passed and populated */
										if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {

											/* Let's compact any profile field info into an array */
											$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

											/* Loop through the posted fields formatting any datebox values then validate the field */
											foreach ( (array) $profile_field_ids as $field_id ) {
												if ( !isset( $_POST['field_' . $field_id] ) ) {
													if ( isset( $_POST['field_' . $field_id . '_day'] ) )
														$_POST['field_' . $field_id] = strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] );
												}

												/* Create errors for required fields without values */
												if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) ) {
													$field = new BP_Xprofile_Field( $field_id );
													$error->add($field->name, __('Please ensure that the ', 'membership') . "<strong>" . $field->name . "</strong>" . __(' information is completed.','membership'));
												}

												$meta_array[ $field_id ] = $_POST['field_' . $field_id];
											}

										}
									}

									$error = apply_filters( 'membership_subscription_form_before_registration_process', $error );

									// Hack for now - eeek
									$anyerrors = $error->get_error_code();
									if(is_wp_error($error) && empty($anyerrors)) {
										// No errors so far - error reporting check for final add user *note $error should always be an error object becuase we created it as such.
										$user_id = wp_create_user( sanitize_user($_POST['signup_username']), $_POST['signup_password'], $_POST['signup_email'] );

										if(is_wp_error($user_id) && method_exists($userid, 'get_error_message')) {
											$error->add('userid', $userid->get_error_message());
										} else {
											$member = new M_Membership( $user_id );
											if(empty($M_options['enableincompletesignups']) || $M_options['enableincompletesignups'] != 'yes') {
												$member->deactivate();
											}
											$creds = array(
												'user_login' => $_POST['signup_username'],
												'user_password' => $_POST['signup_password'],
												'remember' => true
											);
											$is_ssl = ($_SERVER['https'] == 'on' ? true : false);
											$user = wp_signon( $creds, $is_ssl );

											if( has_action('membership_susbcription_form_registration_notification') ) {
												do_action('membership_susbcription_form_registration_notification', $user_id, $_POST['signup_password']);
											} else {
												wp_new_user_notification($user_id, $_POST['signup_password']);
											}

											// Add the bp filter for usermeta signup
											$meta_array = apply_filters( 'bp_signup_usermeta', $meta_array );

											foreach((array) $meta_array as $field_id => $field_content) {
												if(function_exists('xprofile_set_field_data')) {
													xprofile_set_field_data( $field_id, $user_id, $field_content );
												}
											}

										}
									}

									do_action( 'membership_subscription_form_registration_process', $error, $user_id );

									// Hack for now - eeek
									$anyerrors = $error->get_error_code();
									if(is_wp_error($error) && !empty($anyerrors)) {
										$messages = $error->get_error_messages();
										$content .= "<div class='alert alert-error'>";
										$content .= implode('<br/>', $messages);
										$content .= "</div>";
										// Show the page so that it can display the errors
										$content = $this->output_registeruser( $content, $_POST );
									} else {
										// everything seems fine (so far), so we have our queued user so let's
										// run the bp complete signup action
										do_action( 'bp_complete_signup' );
										// display the payment forms
										if(!defined('MEMBERSHIP_NOLOGINONREGISTRATION')) {
											if(!headers_sent()) {
												wp_set_current_user($user_id);
												wp_set_auth_cookie($user_id);
											}
										}

										$content = $this->output_paymentpage( $user_id );
									}

									break;



			}

			return $content;

		}

		function do_subscription_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			return $this->do_subscription_form();

		}


		function do_subscriptiontitle_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$defaults = array(	"holder"				=>	'',
								"holderclass"			=>	'',
								"item"					=>	'',
								"itemclass"				=>	'',
								"postfix"				=>	'',
								"prefix"				=>	'',
								"wrapwith"				=>	'',
								"wrapwithclass"			=>	'',
								"subscription"			=>	''
							);

			extract(shortcode_atts($defaults, $atts));

			if(empty($subscription)) {
				return '';
			}

			if(!empty($holder)) {
				$html .= "<{$holder} class='{$holderclass}'>";
			}
			if(!empty($item)) {
				$html .= "<{$item} class='{$itemclass}'>";
			}
			$html .= $prefix;

			// The title
			if(!empty($wrapwith)) {
				$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
			}

			$sub = new M_Subscription( (int) $subscription );
			$html .= $sub->sub_name();

			if(!empty($wrapwith)) {
				$html .= "</{$wrapwith}>";
			}

			$html .= $postfix;
			if(!empty($item)) {
				$html .= "</{$item}>";
			}
			if(!empty($holder)) {
				$html .= "</{$holder}>";
			}


			return $html;
		}

		function do_subscriptiondetails_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$defaults = array(	"holder"				=>	'',
								"holderclass"			=>	'',
								"item"					=>	'',
								"itemclass"				=>	'',
								"postfix"				=>	'',
								"prefix"				=>	'',
								"wrapwith"				=>	'',
								"wrapwithclass"			=>	'',
								"subscription"			=>	''
							);

			extract(shortcode_atts($defaults, $atts));

			if(empty($subscription)) {
				return '';
			}

			if(!empty($holder)) {
				$html .= "<{$holder} class='{$holderclass}'>";
			}
			if(!empty($item)) {
				$html .= "<{$item} class='{$itemclass}'>";
			}
			$html .= $prefix;

			// The title
			if(!empty($wrapwith)) {
				$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
			}

			$sub = new M_Subscription( (int) $subscription );
			$html .= stripslashes($sub->sub_description());

			if(!empty($wrapwith)) {
				$html .= "</{$wrapwith}>";
			}

			$html .= $postfix;
			if(!empty($item)) {
				$html .= "</{$item}>";
			}
			if(!empty($holder)) {
				$html .= "</{$holder}>";
			}

			return $html;
		}

		function do_subscriptionprice_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$defaults = array(	"holder"				=>	'',
								"holderclass"			=>	'',
								"item"					=>	'',
								"itemclass"				=>	'',
								"postfix"				=>	'',
								"prefix"				=>	'',
								"wrapwith"				=>	'',
								"wrapwithclass"			=>	'',
								"subscription"			=>	''
							);

			extract(shortcode_atts($defaults, $atts));

			if(empty($subscription)) {
				return '';
			}

			if(!empty($holder)) {
				$html .= "<{$holder} class='{$holderclass}'>";
			}
			if(!empty($item)) {
				$html .= "<{$item} class='{$itemclass}'>";
			}
			$html .= $prefix;

			// The title
			if(!empty($wrapwith)) {
				$html .= "<{$wrapwith} class='{$wrapwithclass}'>";
			}

			$sub = new M_Subscription( (int) $subscription );
			$first = $sub->get_level_at_position(1);

			if(!empty($first)) {
				$price = $first->level_price;
				if($price == 0) {
					$price = "Free";
				} else {

					$M_options = get_option('membership_options', array());

					switch( $M_options['paymentcurrency'] ) {
						case "USD": $price = "$" . $price;
									break;

						case "GBP":	$price = "&pound;" . $price;
									break;

						case "EUR":	$price = "&euro;" . $price;
									break;

						default:	$price = apply_filters('membership_currency_symbol_' . $M_options['paymentcurrency'], $M_options['paymentcurrency']) . $price;
					}
				}
			}


			$html .= $price;

			if(!empty($wrapwith)) {
				$html .= "</{$wrapwith}>";
			}

			$html .= $postfix;
			if(!empty($item)) {
				$html .= "</{$item}>";
			}
			if(!empty($holder)) {
				$html .= "</{$holder}>";
			}

			return $html;
		}

		function do_subscriptionbutton_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$defaults = array(	"holder"				=>	'',
								"holderclass"			=>	'',
								"item"					=>	'',
								"itemclass"				=>	'',
								"postfix"				=>	'',
								"prefix"				=>	'',
								"wrapwith"				=>	'',
								"wrapwithclass"			=>	'',
								"subscription"			=>	'',
								"color"					=>	'blue'
							);

			extract(shortcode_atts($defaults, $atts));

			$link = admin_url( 'admin-ajax.php' );
			$link .= '?action=buynow&amp;subscription=' . (int) $subscription;

			if(empty($content)) {
				$content = __('Subscribe', 'membership');
			}

			$html = "<a href='" . $link . "' class='popover button " . $color . "'>" . $content . "</a>";

			//$html = do_shortcode("[button class='popover' link='{$link}']Buy Now[/button]");


			return $html;
		}

		function create_the_user_and_notify() {
			//$user_id = wp_create_user(sanitize_user($_POST['user_login']), $_POST['password'], $_POST['user_email']);
			//wp_new_user_notification( $user_id, $_POST['password'] );
		}

		function check_for_membership_pages($posts) {

			global $M_options;

			if(count($posts) == 1) {
				// We have only the one post, so check if it's one of our pages
				$post = $posts[0];
				if($post->post_type == 'page') {
					if($post->ID == $M_options['registration_page']) {
						// check if page contains a shortcode
						if(strstr($post->post_content, '[subscriptionform]') !== false) {
							// There is content in there with the shortcode so just return it
							return $posts;
						} else {
							// registration page found - add in the styles
							if(!current_theme_supports('membership_subscription_form')) {
								wp_enqueue_style('subscriptionformcss', membership_url('membershipincludes/css/subscriptionform.css'));
								wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
								wp_enqueue_style('buttoncss', membership_url('membershipincludes/css/buttons.css'));

								if($M_options['formtype'] == 'new') {
									// pop up registration form
									wp_enqueue_style('fancyboxcss', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.css'));
									wp_enqueue_script('fancyboxjs', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.pack.js'), array('jquery'), false, true);

									wp_enqueue_script('popupmemjs', membership_url('membershipincludes/js/popupregistration.js'), array('jquery'), false, true);
									wp_enqueue_style('popupmemcss', membership_url('membershipincludes/css/popupregistration.css'));

									wp_localize_script('popupmemjs', 'membership', array(	'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
									 														'registernonce'	=>	wp_create_nonce('membership_register'),
																							'loginnonce'	=>	wp_create_nonce('membership_login'),
																							'regproblem'	=>	__('Problem with registration.', 'membership'),
																							'logpropblem'	=>	__('Problem with Login.', 'membership'),
																							'regmissing'	=>	__('Please ensure you have completed all the fields','membership'),
																							'regnomatch'	=>	__('Please ensure passwords match', 'membership'),
																							'logmissing'	=>	__('Please ensure you have entered an username or password','membership')
																						));
								}
							}

							do_action('membership_subscriptionbutton_onpage');
							// There is no shortcode content in there, so override
							remove_filter( 'the_content', 'wpautop' );
							$post->post_content = $this->do_subscription_form();
						}
					}
					if($post->ID == $M_options['account_page']) {
						// account page - check if page contains a shortcode
						if(strstr($post->post_content, '[accountform]') !== false || strstr($post->post_content, '[upgradeform]') !== false || strstr($post->post_content, '[renewform]') !== false) {
							// There is content in there with the shortcode so just return it
							return $posts;
						} else {
							// account page found - add in the styles
							if(!current_theme_supports('membership_account_form')) {
								wp_enqueue_style('accountformcss', membership_url('membershipincludes/css/accountform.css'));
								wp_enqueue_script('accountformjs', membership_url('membershipincludes/js/accountform.js'), array('jquery'));

								wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
								wp_enqueue_style('buttoncss', membership_url('membershipincludes/css/buttons.css'));
							}
							// There is no shortcode in there, so override
							remove_filter( 'the_content', 'wpautop' );
							$post->post_content = $this->do_account_form();
						}
					}
					if($post->ID == $M_options['subscriptions_page']) {

						// Handle any updates passed
						$page = isset($_REQUEST['action']) ? addslashes($_REQUEST['action']) : '';
						if(empty($page)) {
							$page = 'renewform';
						}

						switch($page) {
							case 'subscriptionsignup':	if(is_user_logged_in()) {
															$member = current_member();
															list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode(':', $_POST['custom']);

															if( wp_verify_nonce($_REQUEST['_wpnonce'], 'free-sub_' . $sub_id) ) {
																$gateway = $_POST['gateway'];
																// Join the new subscription
																$member->create_subscription($sub_id, $gateway);
																do_action('membership_payment_subscr_signup', $user_id, $sub_id);
																// Timestamp the update
																update_user_meta( $user_id, '_membership_last_upgraded', time());

																// Added another redirect to the same url because the show_no_access filters
																// have already run on the "parse_request" action (Cole)
																wp_redirect(M_get_subscription_permalink());
																exit;
															}
														} else {
															// check if a custom is posted and of so then process the user
															if(isset($_POST['custom'])) {
																list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode(':', $_POST['custom']);

																if( wp_verify_nonce($_REQUEST['_wpnonce'], 'free-sub_' . $sub_id) ) {
																	$gateway = $_POST['gateway'];
																	// Join the new subscription
																	$member = new M_Membership( $user_id );
																	$member->create_subscription($sub_id, $gateway);
																	do_action('membership_payment_subscr_signup', $user_id, $sub_id);
																	// Timestamp the update
																	update_user_meta( $user_id, '_membership_last_upgraded', time());

																	// Added another redirect to the same url because the show_no_access filters
																	// have already run on the "parse_request" action (Cole)
																	wp_redirect(M_get_subscription_permalink());
																	exit;
																}
															}
														}
														break;

							default:
														break;
						}

						// account page - check if page contains a shortcode
						if(strstr($post->post_content, '[upgradeform]') !== false || strstr($post->post_content, '[renewform]') !== false) {
							// There is content in there with the shortcode so just return it
							return $posts;
						} else {
							// account page found - add in the styles
							if(!current_theme_supports('membership_account_form')) {
								wp_enqueue_style('subscriptionformcss', membership_url('membershipincludes/css/subscriptionform.css'));
								wp_enqueue_style('upgradeformcss', membership_url('membershipincludes/css/upgradeform.css'));
								wp_enqueue_style('renewformcss', membership_url('membershipincludes/css/renewform.css'));
								wp_enqueue_script('renewformjs', membership_url('membershipincludes/js/renewform.js'), array('jquery'));
								wp_localize_script( 'renewformjs', 'membership', array( 'unsubscribe' => __('Are you sure you want to unsubscribe from this subscription?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );

								wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
								wp_enqueue_style('buttoncss', membership_url('membershipincludes/css/buttons.css'));

								if($M_options['formtype'] == 'new') {
									// pop up registration form
									wp_enqueue_style('fancyboxcss', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.css'));
									wp_enqueue_script('fancyboxjs', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.pack.js'), array('jquery'), false, true);

									wp_enqueue_script('popupmemjs', membership_url('membershipincludes/js/popupregistration.js'), array('jquery'), false, true);
									wp_enqueue_style('popupmemcss', membership_url('membershipincludes/css/popupregistration.css'));

									wp_localize_script('popupmemjs', 'membership', array(	'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
									 														'registernonce'	=>	wp_create_nonce('membership_register'),
																							'loginnonce'	=>	wp_create_nonce('membership_login'),
																							'regproblem'	=>	__('Problem with registration.', 'membership'),
																							'logpropblem'	=>	__('Problem with Login.', 'membership'),
																							'regmissing'	=>	__('Please ensure you have completed all the fields','membership'),
																							'regnomatch'	=>	__('Please ensure passwords match', 'membership'),
																							'logmissing'	=>	__('Please ensure you have entered an username or password','membership')
																						));
								}

							}
							// There is no shortcode in there, so override
							remove_filter( 'the_content', 'wpautop' );
							$post->post_content = $this->do_renew_form();
						}
					}
					if($post->ID == $M_options['nocontent_page']) {
						// no access page - we must return the content entered by the user so just return it
						return $posts;
					}
					// Registration complete page
					if($post->ID == $M_options['registrationcompleted_page']) {

						// Handle any updates passed
						if(isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
							$page = addslashes($_REQUEST['action']);
						} else {
							$page = 'renewform';
						}

						switch($page) {
							case 'subscriptionsignup':
														if(is_user_logged_in()) {
															list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode(':', $_POST['custom']);

															if( wp_verify_nonce($_REQUEST['_wpnonce'], 'free-sub_' . $sub_id) ) {

																$member = current_member();

																$gateway = $_POST['gateway'];
																// Join the new subscription
																$member->create_subscription($sub_id, $gateway);
																do_action('membership_payment_subscr_signup', $user_id, $sub_id);
																// Timestamp the update
																update_user_meta( $user_id, '_membership_last_upgraded', time());

																// Added another redirect to the same url because the show_no_access filters
																// have already run on the "parse_request" action (Cole)
																wp_redirect(M_get_returnurl_permalink());
																exit;
															}

														} else {
															// check if a custom is posted and of so then process the user
															if(isset($_POST['custom'])) {
																list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode(':', $_POST['custom']);
																if( wp_verify_nonce($_REQUEST['_wpnonce'], 'free-sub_' . $sub_id) ) {
																	$gateway = $_POST['gateway'];
																	// Join the new subscription
																	$member = new M_Membership( $user_id );
																	$member->create_subscription($sub_id, $gateway);
																	do_action('membership_payment_subscr_signup', $user_id, $sub_id);
																	// Timestamp the update
																	update_user_meta( $user_id, '_membership_last_upgraded', time());

																	// Added another redirect to the same url because the show_no_access filters
																	// have already run on the "parse_request" action (Cole)
																	wp_redirect(M_get_returnurl_permalink());
																	exit;
																}
															}
														}
														break;
						}

						return $posts;
					}

				}
			}
			// If nothing else is hit, just return the content
			return $posts;
		}

		function add_subscription_styles($posts) {

			foreach($posts as $key => $post) {
				if(strstr($post->post_content, '[subscriptionform]') !== false) {
					// The shortcode is in a post on this page, add the header
					if(!current_theme_supports('membership_subscription_form')) {
						wp_enqueue_style('subscriptionformcss', membership_url('membershipincludes/css/subscriptionform.css'));
						wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
						wp_enqueue_style('fancyboxcss', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.css'));
						wp_enqueue_script('fancyboxjs', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.pack.js'), array('jquery'), false, true);

						wp_enqueue_script('popupmemjs', membership_url('membershipincludes/js/popupregistration.js'), array('jquery'), false, true);
						wp_enqueue_style('popupmemcss', membership_url('membershipincludes/css/popupregistration.css'));

						wp_enqueue_style('buttoncss', membership_url('membershipincludes/css/buttons.css'));

						wp_localize_script('popupmemjs', 'membership', array(	'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
						 														'registernonce'	=>	wp_create_nonce('membership_register'),
																				'loginnonce'	=>	wp_create_nonce('membership_login'),
																				'regproblem'	=>	__('Problem with registration.', 'membership'),
																				'logpropblem'	=>	__('Problem with Login.', 'membership'),
																				'regmissing'	=>	__('Please ensure you have completed all the fields','membership'),
																				'regnomatch'	=>	__('Please ensure passwords match', 'membership'),
																				'logmissing'	=>	__('Please ensure you have entered an username or password','membership')
																			));

					}
				}
				if(strstr($post->post_content, '[accountform]') !== false) {
					// The shortcode is in a post on this page, add the header
					if(!current_theme_supports('membership_account_form')) {
						wp_enqueue_style('accountformcss', membership_url('membershipincludes/css/accountform.css'));
						wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
						wp_enqueue_script('accountformjs', membership_url('membershipincludes/js/accountform.js'), array('jquery'));
					}
				}
				if(strstr($post->post_content, '[upgradeform]') !== false) {
					// The shortcode is in a post on this page, add the header
					if(!current_theme_supports('membership_account_form')) {
						wp_enqueue_style('upgradeformcss', membership_url('membershipincludes/css/upgradeform.css'));
						wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
					}
				}
				if(strstr($post->post_content, '[renewform]') !== false) {
					// The shortcode is in a post on this page, add the header
					if(!current_theme_supports('membership_account_form')) {
						wp_enqueue_style('renewformcss', membership_url('membershipincludes/css/renewform.css'));
						wp_enqueue_style('publicformscss', membership_url('membershipincludes/css/publicforms.css'));
						wp_enqueue_script('renewformjs', membership_url('membershipincludes/js/renewform.js'), array('jquery'));
						wp_localize_script( 'renewformjs', 'membership', array( 'unsubscribe' => __('Are you sure you want to unsubscribe from this subscription?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );
					}
				}

				// New subscription styles
				if(strstr($post->post_content, '[subscriptiontitle') !== false) {
					do_action('membership_subscriptiontitle_onpage');
				}

				if(strstr($post->post_content, '[subscriptiondetails') !== false) {
					do_action('membership_subscriptiondetails_onpage');
				}

				if(strstr($post->post_content, '[subscriptionbutton') !== false) {
					// The shortcode is in a post on this page, add the header
					if(!current_theme_supports('membership_subscription_form')) {
						wp_enqueue_style('fancyboxcss', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.css'));
						wp_enqueue_script('fancyboxjs', membership_url('membershipincludes/js/fancybox/jquery.fancybox-1.3.4.pack.js'), array('jquery'), false, true);

						wp_enqueue_script('popupmemjs', membership_url('membershipincludes/js/popupregistration.js'), array('jquery'), false, true);
						wp_enqueue_style('popupmemcss', membership_url('membershipincludes/css/popupregistration.css'));

						wp_enqueue_style('buttoncss', membership_url('membershipincludes/css/buttons.css'));

						wp_localize_script('popupmemjs', 'membership', array(	'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
						 														'registernonce'	=>	wp_create_nonce('membership_register'),
																				'loginnonce'	=>	wp_create_nonce('membership_login'),
																				'regproblem'	=>	__('Problem with registration.', 'membership'),
																				'logpropblem'	=>	__('Problem with Login.', 'membership'),
																				'regmissing'	=>	__('Please ensure you have completed all the fields','membership'),
																				'regnomatch'	=>	__('Please ensure passwords match', 'membership'),
																				'logmissing'	=>	__('Please ensure you have entered an username or password','membership')
																			));
					}
					do_action('membership_subscriptionbutton_onpage');

					//wp_enqueue_style('upgradeformcss', membership_url('membershipincludes/css/upgradeform.css'));
				}

				if(strstr($post->post_content, '[subscriptionprice') !== false) {
					do_action('membership_subscriptionprice_onpage');
				}
			}

			return $posts;

		}

		function pending_username_exists( $username, $email ) {

			// Initial delete of pending subscriptions
			$sql = $this->db->prepare( "DELETE FROM {$this->user_queue} WHERE user_timestamp < %d", strtotime('-3 hours') );
			$this->db->query( $sql );

			// Now check for a pending username that doesn't have the same email address
			$sql = $this->db->prepare( "SELECT id FROM {$this->user_queue} WHERE user_login = %s AND user_email != %s LIMIT 0,1", $username, $email );

			$res = $this->db->get_var( $sql );
			if(!empty($res)) {
				return true;
			} else {
				// because even though the username could exist - if the email address is the same it could just be that they hit the back button.
				return false;
			}

		}

		function queue_user( $user_login, $user_pass, $user_email, $user_meta = '' ) {

			$sql = "INSERT INTO {$this->user_queue} (user_login, user_pass, user_email, user_timestamp, user_meta) VALUES ";
			$sql .= $this->db->prepare( "( %s, %s, %s, %d, %s )", $user_login, wp_hash_password( $user_pass ), $user_email, time(), serialize($user_meta) );
			$sql .= $this->db->prepare( " ON DUPLICATE KEY UPDATE user_timestamp = %d", time());

			if( $this->db->query( $sql ) ) {
				return $this->db->insert_id;
			} else {
				return new WP_Error('queueerror', __('Could not create your user account.', 'membership'));
			}


		}

		//db stuff
		function get_subscriptions() {

			$where = array();
			$orderby = array();

			$where[] = "sub_public = 1";
			$where[] = "sub_active = 1";

			$orderby[] = 'id ASC';

			$sql = "SELECT * FROM {$this->subscriptions}";

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);

		}

		function get_levels() {

			$where = array();
			$orderby = array();

			$where[] = "level_active = 1";

			$orderby[] = 'id ASC';

			$sql = "SELECT * FROM {$this->membership_levels}";

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);

		}

		// Level shortcodes function
		function build_level_shortcode_list( $shortcodes = array() ) {

			if(!is_array($shortcodes)) {
				$shortcodes = array();
			}

			$levels = $this->get_levels();

			if(!empty($levels)) {
				foreach($levels as $level) {
					$shortcodes[$level->id] = M_normalize_shortcode($level->level_title);
				}
			}

			return $shortcodes;

		}

		function build_not_level_shortcode_list( $shortcodes = array() ) {

			if(!is_array($shortcodes)) {
				$shortcodes = array();
			}

			$levels = $this->get_levels();

			if(!empty($levels)) {
				foreach($levels as $level) {
					$shortcodes[$level->id] = 'not-' . M_normalize_shortcode($level->level_title);
				}
			}

			return $shortcodes;

		}

	}

}
?>