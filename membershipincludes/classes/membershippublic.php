<?php
if(!class_exists('membershippublic')) {

	class membershippublic {

		var $build = 1;

		var $db;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'user_queue');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $user_queue;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

			// Set up Actions
			add_action( 'init', array(&$this, 'initialise_plugin') );
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

		}

		function membershippublic() {
			$this->__construct();
		}

		function load_textdomain() {

			$locale = apply_filters( 'membership_locale', get_locale() );
			$mofile = membership_dir( "membershipincludes/locale/membership-$locale.mo" );

			if ( file_exists( $mofile ) )
				load_textdomain( 'membership', $mofile );

		}

		function initialise_plugin() {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite, $M_active;

			$M_options = get_option('membership_options', array());

			// Check if the membership plugin is active
			$M_active = get_option('membership_active', 'no');

			// Create our subscription page shortcode
			add_shortcode('subscriptionform', array(&$this, 'do_subscription_shortcode') );
			add_filter('the_posts', array(&$this, 'add_subscription_styles'));

			$user = wp_get_current_user();
			if(!method_exists($user, 'has_cap') || $user->has_cap('administrator') || $M_active == 'no') {
				// Admins can see everything
				return;
			}

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
				// add in a no posts thing
				add_filter('the_posts', array(&$this, 'check_for_posts_existance'), 99, 2);
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

			if($user->has_cap('administrator') || $M_active == 'no') {
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
						$member->assign_level($M_options['strangerlevel'], true );
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
				do_action( 'membership_handle_payment_return_' . $wp_query->query_vars['paymentgateway']);
				exit();
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
						if( $member->has_level_rule('downloads') && $member->pass_thru( 'downloads', array( 'can_view_download' => $protected ) ) ) {
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
				if($key != 'subscriptionform') {
					$shortcode_tags[$key] = array(&$this, 'do_protected_shortcode');
				}
			}

			return $content;
		}

		function check_for_posts_existance($posts, $wp_query) {
			if(empty($posts)) {
				// we have nothing to see because it either doesn't exist or it's protected - move to no access page.
				$this->show_noaccess_page($wp_query);
			} else {
				return $posts;
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

			// This function should remove the no access page from any menus
			$wp_query->query_vars['post__not_in'][] = $M_options['nocontent_page'];
			$wp_query->query_vars['post__not_in'] = array_unique($wp_query->query_vars['post__not_in']);


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

			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				return;
			}

			if(!empty($M_options['nocontent_page'])) {
				// grab the content form the no content page
				$post = get_post( $M_options['nocontent_page'] );
			} else {
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

				// Reset $wp_query
				$wp_query->posts[] = $post;
				$wp_query->post_count = 1;
				$wp_query->is_home = false;

				/**
				 * And load up the template file.
				 */
				status_header('404');
				ob_start('template');
				load_template(TEMPLATEPATH . '/' . 'page.php');
				ob_end_flush();

				/**
				 * YOU MUST DIE AT THE END.  BAD THINGS HAPPEN IF YOU DONT
				 */
				die();
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

		function show_subpage_one($error = false) {

			$content = '';

			$content .= '<form id="reg-form" action="' . get_permalink() . '" method="post">';
			$content .= '<div class="formleft">';

			$content .= "<h2>" . __('Step 1. Create a New Account','membership') . "</h2>";

			$content .= '<a title="Login »" href="' . wp_login_url( add_query_arg('action', 'page2', get_permalink()) ) . '" class="alignright" id="login_right">' . __('Already have a user account?' ,'membership') . '</a>';

			$content .= '<p><label>' . __('Choose a Username','membership') . ' <span>*</span></label>';
			$content .= '<input type="text" value="' . esc_attr($_POST['user_login']) . '" class="regtext" name="user_login"></p>';

			$content .= '<div class="alignleft">';
            $content .= '<label>' . __('Email Address','membership') . ' <span>*</span></label>';
            $content .= '<input type="text" value="' . esc_attr($_POST['user_email']) . '" class="regtext" name="user_email">';
            $content .= '</div>';

			$content .= '<div class="alignleft">';
            $content .= '<label>' . __('Confirm Email Address','membership') . ' <span>*</span></label>';
            $content .= '<input type="text" value="' . esc_attr($_POST['user_email2']) . '" class="regtext" name="user_email2">';
            $content .= '</div>';

			$content .= '<div class="alignleft">';
            $content .= '<label>' . __('Password','membership') . ' <span>*</span></label>';
            $content .= '<input type="password" autocomplete="off" class="regtext" name="password">';
            $content .= '</div>';

			$content .= '<div class="alignleft">';
            $content .= '<label>' . __('Confirm Password','membership') . ' <span>*</span></label>';
            $content .= '<input type="password" autocomplete="off" class="regtext" name="password2">';
            $content .= '</div>';

			$content .= '<p class="pass_hint">' . __('Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).','membership') . '</p>';

			$content = apply_filters('membership_subscriptionform_registration', $content);

			if(function_exists('get_site_option')) {
				$terms = get_site_option('signup_tos_data');
			} else {
				$terms = '';
			}

			if(!empty($terms)) {
				$content .= '<h2>' . __('Terms and Conditions','membership') . '</h2>';

				$content .= '<div id="reg_tos">';
				$content .= stripslashes($terms);
				$content .= '</div>';
				$content .= '<p><label><input type="checkbox" value="1" name="tosagree">';
				$content .= '<strong>' . __('I agree to the Terms of Use','membership') . '</strong></label></p>';
			}

			$content .= '<p><input type="submit" value="' . __('Register My Account »','membership') . '" class="regbutton" name="register"></p>';

			$content .= '<input type="hidden" name="action" value="validatepage1" />';

			$content .= '</div>';

			$content .= "</form>";

			$content = apply_filters('membership_subscriptionform_registrationfull', $content);

			return $content;

		}

		function show_subpage_two($user_id) {

			$content = '';

			$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part

			$content .= '<div class="formleft">';

			$content .= "<h2>" . __('Step 2. Select a subscription','membership') . "</h2>";

			$content .= "<p>";
			$content .= __('Please select a subscription from the options below.','membership');
			$content .= "</p>";

			$content = apply_filters( 'membership_subscriptionform_presubscriptions', $content, $user_id );

			$subs = $this->get_subscriptions();

			$content = apply_filters( 'membership_subscriptionform_beforepaidsubscriptions', $content, $user_id );

			foreach((array) $subs as $key => $sub) {

				$subscription = new M_Subscription($sub->id);

				$content .= '<div class="subscription">';
				$content .= '<div class="description">';
				$content .= '<h3>' . $subscription->sub_name() . '</h3>';
				$content .= '<p>' . $subscription->sub_description() . '</p>';
				$content .= "</div>";

				// Add the purchase button
				$pricing = $subscription->get_pricingarray();

				if($pricing) {
					$content .= "<div class='priceforms'>";
					$content = apply_filters('membership_purchase_button', $content, $subscription, $pricing, $user_id);
					$content .= "</div>";
				}

				$content .= '</div>';

			}

			$content = apply_filters( 'membership_subscriptionform_afterpaidsubscriptions', $content, $user_id );

			$content .= '</div>';

			$content .= "</div>";

			// Adding in the following form element (and form :) ) will allow the second page validation to be fired
			//<input type="hidden" name="action" value="validatepage2" />
			$content = apply_filters('membership_subscriptionform_postsubscriptions', $content, $user_id );

			return $content;

		}

		function show_subpage_member() {

			$content = '';

			$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part

			$content .= '<div class="formleft">';

			$inner = "<h2>" . __('Completed: Thank you for joining','membership') . "</h2>";
			$inner .= '<p>';
			$inner .= __('It looks like you are already a member of our site. Thank you very much for your support.','membership');
			$inner .= '</p>';
			$inner .= '<p>';
			$inner .= __('If you are at this page because you would like to create another account, then please log out first.','membership');
			$inner .= '</p>';

			$content .= apply_filters('membership_subscriptionform_membercontent', $inner);

			$content .= '</div>';

			$content .= "</div>";

			$content = apply_filters('membership_subscriptionform_memberfull', $content);

			return $content;

		}

		function do_subscription_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			$content = '';
			$error = array();

			$page = addslashes($_REQUEST['action']);

			$content .= '<div id="subscriptionwrap">';

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
									} elseif( $this->pending_username_exists( sanitize_user($_POST['user_login']) ) ) {
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

									$error = apply_filters( 'membership_subscriptionform_preregistration_process', $error );

									if(empty($error)) {
										// Pre - error reporting check for final add user
										$user_id = wp_create_user(sanitize_user($_POST['user_login']), $_POST['password'], $_POST['user_email']);

										if(is_wp_error($user_id) && method_exists($userid, 'get_error_message')) {
											$error[] = $userid->get_error_message();
										}
									}

									do_action( 'membership_subscriptionform_registration_process', $error );

									if(!empty($error)) {
										$content .= "<div class='error'>";
										$content .= implode('<br/>', $error);
										$content .= "</div>";
										$content .= $this->show_subpage_one(true);
									} else {
										// everything seems fine (so far), so lets move to page 2
										wp_new_user_notification( $user_id, $_POST['password'] );
										$content .= $this->show_subpage_two($user_id);
									}

									break;

				case 'validatepage2':
									$content = apply_filters( 'membership_subscriptionform_subscription_process', $content, $error );
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

			$content .= '</div> <!-- subscriptionwrap -->';

			$content = apply_filters('membership_subscriptionform', $content);

			return $content;
		}

		function add_subscription_styles($posts) {

			foreach($posts as $key => $post) {
				if(strstr($post->post_content, '[subscriptionform]') !== false) {
					// The shortcode is in a post on this page, add the header
					wp_enqueue_style('subscriptionformcss', membership_url('membershipincludes/css/subscriptionform.css'));
				}
			}

			return $posts;

		}

		function pending_username_exists( $username ) {

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