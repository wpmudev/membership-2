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

			add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

			// Set up Actions
			add_action( 'init', array(&$this, 'initialise_plugin'), 1 );
			add_filter( 'query_vars', array(&$this, 'add_queryvars') );
			add_action( 'generate_rewrite_rules', array(&$this, 'add_rewrites') );

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

			$M_options = get_option('membership_options', array());

			// Check if the membership plugin is active
			$M_active = get_option('membership_active', 'no');

			// Create our subscription page shortcode
			add_shortcode('subscriptionform', array(&$this, 'do_subscription_shortcode') );
			add_shortcode('accountform', array(&$this, 'do_account_shortcode') );
			add_shortcode('upgradeform', array(&$this, 'do_upgrade_shortcode') );
			add_shortcode('renewform', array(&$this, 'do_renew_shortcode') );
			add_filter('the_posts', array(&$this, 'add_subscription_styles'));

			$user = wp_get_current_user();
			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin') || $M_active == 'no') {
				// Admins can see everything
				return;
			}

			//print_r($bp);

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

			// check for a no-access page and always filter it if needed
			if(!empty($M_options['nocontent_page']) && $M_options['nocontent_page'] != $M_options['registration_page']) {
				add_action('pre_get_posts', array(&$this, 'hide_nocontent_page'), 99 );
				add_filter('get_pages', array(&$this, 'hide_nocontent_page_from_menu'), 99);
				// add in a no posts thing - change this?
				add_filter('the_posts', array(&$this, 'check_for_posts_existance'), 999, 2);
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
				$new_rules[trailingslashit($M_options['masked_url']) . '(.+)'] = 'index.php?protectedfile=' . $wp_rewrite->preg_index(1);
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
					$key = get_usermeta($user->ID, '_membership_key');

					if(empty($key)) {
						$key = md5($user->ID . $user->user_pass . time());
						update_usermeta($user->ID, '_membership_key', $key);
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

			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin') || $M_active == 'no') {
				// Admins can see everything
				return;
			}

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
						add_action('pre_get_posts', array(&$this, 'show_noaccess_page'), 1 );
						// Hide all pages from menus - except the signup one
						add_filter('get_pages', array(&$this, 'remove_pages_menu'));
						// Hide all categories from lists
						add_filter( 'get_terms', array(&$this, 'remove_categories'), 1, 3 );
					}
				}
			}

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

				$filename = array_pop($protected);
				$fileid = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . mysql_real_escape_string($filename) . "%'" );

				if(!empty($fileid)) {
					// check for protection
					$protected = get_post_meta($fileid, '_membership_protected_content_group', true);
					if(empty($protected) || $protected == 'no') {
						// it's not protected so grab and display it
						$file = $wp_query->query_vars['protectedfile'];
						$this->output_file($file);
					} else {
						// check we can see it
						if(empty($member) || !method_exists($member, 'has_level_rule')) {
							$user = wp_get_current_user();
							$member = new M_Membership( $user->ID );
						}

						if( method_exists($member, 'has_level_rule') && $member->has_level_rule('downloads') && $member->pass_thru( 'downloads', array( 'can_view_download' => $protected ) ) ) {
							$file = $wp_query->query_vars['protectedfile'];
							$this->output_file($file);
						} else {
							$this->show_noaccess_image($wp_query);
						}
					}
				}

				exit();
			}

		}

		function output_file($pathtofile) {

			global $wpdb, $M_options;

			$uploadpath = get_option('upload_path');

			$file = trailingslashit(ABSPATH . $uploadpath) . $pathtofile;

			$trueurl = trailingslashit($M_options['original_url']) . $pathtofile;

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

		// Output the protected shortcode content
		function do_membership_shortcode($atts, $content = null, $code = "") {

			return do_shortcode($content);

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
				if(!in_array($key, array('subscriptionform','accountform', 'upgradeform', 'renewform'))) {
					$shortcode_tags[$key] = array(&$this, 'do_protected_shortcode');
				}
			}

			return $content;
		}

		function may_be_singular($wp_query) {

			if( is_archive() || is_author() || is_category() || is_tag() || is_tax() || is_search() ) {
				return false;
			} else {
				return true;
			}

		}

		function check_for_posts_existance($posts, $wp_query) {

			global $bp;

			if(!empty($bp)) {
				// BuddyPress exists so we have to handle "pretend" pages.
				$thepage = substr($wp_query->query['pagename'], 0 , strpos($wp_query->query['pagename'], '/'));
				if(empty($thepage)) $thepage = $wp_query->query['pagename'];

				$bppages = apply_filters('membership_buddypress_pages', (array) $bp->root_components );

				if(in_array($thepage, $bppages)) {
					return $posts;
				}
			}

			if(empty($posts) && $this->posts_actually_exist() && $this->may_be_singular($wp_query)) {
				// we have nothing to see because it either doesn't exist or it's protected - move to no access page.
				$this->show_noaccess_page($wp_query);
			} else {
				return $posts;
			}
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

		function hide_nocontent_page($wp_query) {

			global $M_options;

			if(!empty($M_options['nocontent_page']) && $wp_query->queried_object_id != $M_options['nocontent_page']) {
			// This function should remove the no access page from any menus
			$wp_query->query_vars['post__not_in'][] = $M_options['nocontent_page'];
			$wp_query->query_vars['post__not_in'] = array_unique($wp_query->query_vars['post__not_in']);
			}


		}

		function hide_nocontent_page_from_menu($pages) {

			global $M_options;

			foreach( (array) $pages as $key => $page ) {
				if($page->ID == $M_options['nocontent_page']) {
					unset($pages[$key]);
				}
			}

			return $pages;
		}

		function show_noaccess_page($wp_query, $forceviewing = false) {

			global $M_options;

			if(!empty($wp_query->queried_object_id) && !empty($M_options['registration_page']) && $wp_query->queried_object_id == $M_options['registration_page']) {
				// We know what we are looking at, the registration page has been set and we are trying to access it
				return;
			}

			if(!empty($wp_query->queried_object_id) && !empty($M_options['account_page']) && $wp_query->queried_object_id == $M_options['account_page']) {
				// We know what we are looking at, the registration page has been set and we are trying to access it
				return;
			}

			if(!empty($wp_query->queried_object_id) && !empty($M_options['nocontent_page']) && $wp_query->queried_object_id == $M_options['nocontent_page']) {
				return;
			}

			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				return;
			}

			//post_type] => nav_menu_item
			if($wp_query->query_vars['post_type'] == 'nav_menu_item') {
				// we've started looking at menus - implement bad bit of code until find a better method
				define('M_REACHED_MENU', 'yup');
			}

			// If still here then we need to redirect to the no-access page
			if(!empty($M_options['nocontent_page']) && $wp_query->queried_object_id != $M_options['nocontent_page'] && !defined('M_REACHED_MENU')) {
				// grab the content form the no content page
				$url = get_permalink( (int) $M_options['nocontent_page'] );

				wp_safe_redirect( $url );
				exit;

				//$post = get_post( $M_options['nocontent_page'] );
			} else {

			}

		}

		function close_comments($open, $postid) {

			return false;

		}

		// Content / downloads protection
		function protect_download_content($the_content) {

			global $M_options;

			$the_content = str_replace($M_options['original_url'], trailingslashit(get_option('home')) . $M_options['masked_url'], $the_content);

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
			} elseif(!empty($bp) && file_exists( apply_filters('membership_override_bpaccount_form', membership_dir('membershipincludes/includes/bp.account.form.php')) )) {
				include_once( apply_filters('membership_override_bpaccount_form', membership_dir('membershipincludes/includes/bp.account.form.php')) );
			} elseif( file_exists( apply_filters('membership_override_account_form', membership_dir('membershipincludes/includes/account.form.php')) ) ) {
				include_once( apply_filters('membership_override_account_form', membership_dir('membershipincludes/includes/account.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_account_form_after_content', $content);

			return $content;

		}

		function show_subpage_one($error = false) {

			global $bp;

			$content = '';

			$content = apply_filters('membership_subscription_form_registration_before_content', $content);

			ob_start();
			if( defined('MEMBERSHIP_REGISTRATION_FORM') && file_exists( MEMBERSHIP_REGISTRATION_FORM ) ) {
				include_once( MEMBERSHIP_REGISTRATION_FORM );
			} elseif(!empty($bp) && file_exists( apply_filters('membership_override_bpregistration_form', membership_dir('membershipincludes/includes/bp.registration.form.php')) )) {
				include_once( apply_filters('membership_override_bpregistration_form', membership_dir('membershipincludes/includes/bp.registration.form.php')) );
			} elseif( file_exists( apply_filters('membership_override_registration_form', membership_dir('membershipincludes/includes/registration.form.php')) ) ) {
				include_once( apply_filters('membership_override_registration_form', membership_dir('membershipincludes/includes/registration.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_registration_after_content', $content);

			return $content;

		}

		function show_subpage_two($user_id) {

			$content = '';

			$content = apply_filters('membership_subscription_form_before_content', $content, $user_id);

			ob_start();
			if( defined('MEMBERSHIP_SUBSCRIPTION_FORM') && file_exists( MEMBERSHIP_SUBSCRIPTION_FORM ) ) {
				include_once( MEMBERSHIP_SUBSCRIPTION_FORM );
			} elseif(file_exists( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php')) )) {
				include_once( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php')) );
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

		function show_renew_page() {

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

		function do_subscription_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$error = array();

			$page = addslashes($_REQUEST['action']);

			$M_options = get_option('membership_options', array());

			switch($page) {

				case 'validatepage1':	// Page 1 of the form has been submitted - validate
									include_once(ABSPATH . WPINC . '/registration.php');

									$required = array(	'user_login' => __('Username', 'membership'),
														'user_email' => __('Email address','membership'),
														'user_email2' => __('Email address confirmation','membership'),
														'password' => __('Password','membership'),
														'password2' => __('Password confirmation','membership'),
													);

									$error = array();

									foreach($required as $key => $message) {
										if(empty($_POST[$key])) {
											$error[] = __('Please ensure that the ', 'membership') . "<strong>" . $message . "</strong>" . __(' information is completed.','membership');
										}
									}

									if($_POST['user_email'] != $_POST['user_email2']) {
										$error[] = __('Please ensure the email addresses match.','membership');
									}
									if($_POST['password'] != $_POST['password2']) {
										$error[] = __('Please ensure the passwords match.','membership');
									}

									if(username_exists(sanitize_user($_POST['user_login']))) {
										$error[] = __('That username is already taken, sorry.','membership');
									}

									if(email_exists($_POST['user_email'])) {
										$error[] = __('That email address is already taken, sorry.','membership');
									}

									if(function_exists('get_site_option')) {
										$terms = get_site_option('signup_tos_data');
									} else {
										$terms = '';
									}

									if(!empty($terms)) {
										if(empty($_POST['tosagree'])) {
											$error[] = __('You need to agree to the terms of service to register.','membership');
										}
									}

									$error = apply_filters( 'membership_subscription_form_before_registration_process', $error );

									if(empty($error)) {
										// Pre - error reporting check for final add user
										$user_id = wp_create_user( sanitize_user($_POST['user_login']), $_POST['password'], $_POST['user_email'] );

										if(is_wp_error($user_id) && method_exists($userid, 'get_error_message')) {
											$error[] = $userid->get_error_message();
										} else {
											$member = new M_Membership( $user_id );
											if(empty($M_options['enableincompletesignups']) || $M_options['enableincompletesignups'] != 'yes') {
												$member->deactivate();
											}

											if( has_action('membership_susbcription_form_registration_notification') ) {
												do_action('membership_susbcription_form_registration_notification', $user_id, $_POST['password']);
											} else {
												wp_new_user_notification($user_id, $_POST['password']);
											}

										}
									}

									do_action( 'membership_subscription_form_registration_process', $error, $user_id );

									if(!empty($error)) {
										$content .= "<div class='error'>";
										$content .= implode('<br/>', $error);
										$content .= "</div>";
										$content .= $this->show_subpage_one(true);
									} else {
										// everything seems fine (so far), so we have our queued user so let's
										// look at picking a subscription.
										$content .= $this->show_subpage_two($user_id);
									}

									break;

				case 'validatepage1bp':
									global $bp;

									include_once(ABSPATH . WPINC . '/registration.php');

									$required = array(	'signup_username' => __('Username', 'membership'),
														'signup_email' => __('Email address','membership'),
														'signup_password' => __('Password','membership'),
														'signup_password_confirm' => __('Password confirmation','membership'),
													);

									$error = array();

									foreach($required as $key => $message) {
										if(empty($_POST[$key])) {
											$error[] = __('Please ensure that the ', 'membership') . "<strong>" . $message . "</strong>" . __(' information is completed.','membership');
										}
									}

									if($_POST['signup_password'] != $_POST['signup_password_confirm']) {
										$error[] = __('Please ensure the passwords match.','membership');
									}

									if(username_exists(sanitize_user($_POST['signup_username']))) {
										$error[] = __('That username is already taken, sorry.','membership');
									}

									if(email_exists($_POST['signup_email'])) {
										$error[] = __('That email address is already taken, sorry.','membership');
									}

									$meta_array = array();

									// xprofile required fields
									/* Now we've checked account details, we can check profile information */
									if ( function_exists( 'xprofile_check_is_required_field' ) ) {

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
													$error[] = __('Please ensure that the ', 'membership') . "<strong>" . $field->name . "</strong>" . __(' information is completed.','membership');
												}

												$meta_array[ $field_id ] = $_POST['field_' . $field_id];
											}

										}
									}

									$error = apply_filters( 'membership_subscription_form_before_registration_process', $error );

									if(empty($error)) {
										// Pre - error reporting check for final add user
										$user_id = wp_create_user( sanitize_user($_POST['signup_username']), $_POST['signup_password'], $_POST['signup_email'] );

										if(is_wp_error($user_id) && method_exists($userid, 'get_error_message')) {
											$error[] = $userid->get_error_message();
										} else {
											$member = new M_Membership( $user_id );
											if(empty($M_options['enableincompletesignups']) || $M_options['enableincompletesignups'] != 'yes') {
												$member->deactivate();
											}

											if( has_action('membership_susbcription_form_registration_notification') ) {
												do_action('membership_susbcription_form_registration_notification', $user_id, $_POST['password']);
											} else {
												wp_new_user_notification($user_id, $_POST['signup_password']);
											}

											foreach((array) $meta_array as $field_id => $field_content) {
												if(function_exists('xprofile_set_field_data')) {
													xprofile_set_field_data( $field_id, $user_id, $field_content );
												}
											}

										}
									}

									do_action( 'membership_subscription_form_registration_process', $error, $user_id );

									if(!empty($error)) {
										$content .= "<div class='error'>";
										$content .= implode('<br/>', $error);
										$content .= "</div>";
										$content .= $this->show_subpage_one(true);
									} else {
										// everything seems fine (so far), so we have our queued user so let's
										// look at picking a subscription.
										$content .= $this->show_subpage_two($user_id);
									}

									break;

				case 'validatepage2':
									$content = apply_filters( 'membership_subscription_form_subscription_process', $content, $error );
									break;
				case 'page2':
				case 'page1':
				default:	if(!is_user_logged_in()) {
								$content .= $this->show_subpage_one();
							} else {
								// logged in check for sub
								$user = wp_get_current_user();

								$member = new M_Membership($user->ID);

								if($member->is_member()) {
									// This person is a member - display already registered stuff
									$content .= $this->show_subpage_member();
								} else {
									// Show page two;
									$content .= $this->show_subpage_two($user->ID);
								}
							}
							break;

			}

			$content = apply_filters('membership_subscription_form', $content);

			return $content;
		}

		function create_the_user_and_notify() {
			//$user_id = wp_create_user(sanitize_user($_POST['user_login']), $_POST['password'], $_POST['user_email']);
			//wp_new_user_notification( $user_id, $_POST['password'] );
		}

		function add_subscription_styles($posts) {

			foreach($posts as $key => $post) {
				if(strstr($post->post_content, '[subscriptionform]') !== false) {
					// The shortcode is in a post on this page, add the header
					wp_enqueue_style('subscriptionformcss', membership_url('membershipincludes/css/subscriptionform.css'));
				}
				if(strstr($post->post_content, '[accountform]') !== false) {
					// The shortcode is in a post on this page, add the header
					wp_enqueue_style('accountformcss', membership_url('membershipincludes/css/accountform.css'));
					wp_enqueue_script('accountformjs', membership_url('membershipincludes/js/accountform.js'), array('jquery'));
				}
				if(strstr($post->post_content, '[upgradeform]') !== false) {
					// The shortcode is in a post on this page, add the header
					wp_enqueue_style('upgradeformcss', membership_url('membershipincludes/css/upgradeform.css'));
				}
				if(strstr($post->post_content, '[renewform]') !== false) {
					// The shortcode is in a post on this page, add the header
					wp_enqueue_style('renewformcss', membership_url('membershipincludes/css/renewform.css'));
					wp_enqueue_script('renewformjs', membership_url('membershipincludes/js/renewform.js'), array('jquery'));
					wp_localize_script( 'renewformjs', 'membership', array( 'unsubscribe' => __('Are you sure you want to unsubscribe from this subscription?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );

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

			$sql = $this->db->prepare( "INSERT INTO {$this->user_queue} (user_login, user_pass, user_email, user_timestamp, user_meta) VALUES " );
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

			$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions}");

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);

		}


	}

}
?>