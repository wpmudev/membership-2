<?php
if ( !class_exists( 'membershippublic', false ) ) :
	class membershippublic {

		var $build = 2;

		var $db;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;

		var $_register_errors;

		// For url redirects - bit of a hack, but need to ensure they are only set once for now.
		var $redirect_defaults_set = false;

		function __construct() {
			global $wpdb;

			$this->db = $wpdb;
			foreach ( $this->tables as $table ) {
				$this->$table = membership_db_prefix( $this->db, $table );
			}

			// Set up Actions
			add_action( 'init', array( &$this, 'initialise_plugin' ), 1 );
			add_filter( 'query_vars', array( &$this, 'add_queryvars' ) );
			add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrites' ) );
			add_action( 'membership-add-shortcodes', array( $this, 'register_shortcodes' ), 1 );

			// Payment return
			add_action( 'pre_get_posts', array( &$this, 'handle_paymentgateways' ), 1 );

			// Download protection
			add_action( 'pre_get_posts', array( &$this, 'handle_download_protection' ), 3 );

			// add feed protection
			add_filter( 'feed_link', array( &$this, 'add_feed_key' ), 99, 2 );

			// Register
			add_filter( 'register', array( &$this, 'override_register' ) );

			// Ultimate Facebook Compatibility
			add_filter( 'wdfb_registration_redirect_url', array( &$this, 'wdfb_registration_redirect_url' ) );

			// Level shortcodes filters
			add_filter( 'membership_level_shortcodes', array( &$this, 'build_level_shortcode_list' ) );
			add_filter( 'membership_not_level_shortcodes', array( &$this, 'build_not_level_shortcode_list' ) );
		}

		function register_shortcodes() {
			global $member;
			$member = Membership_Plugin::current_member();

			foreach ( array( 'membership_level_shortcodes', 'membership_not_level_shortcodes' ) as $index => $filter ) {
				$shortcodes = apply_filters( $filter, array() );
				if ( !empty( $shortcodes ) ) {

					// $key is the level_id
					foreach ( $shortcodes as $key => $value ) {
						if ( !empty( $value ) ) {
							
							$valid = $index ? !$member->has_level( $key ) : $member->has_level( $key );

							// If member has admin capabilities then do the valid shortcode.	
							if ($member->has_cap('membershipadmin') || $member->has_cap('manage_options') || is_super_admin($member->ID)) {
								
								// Override admin access when using "View site as:"
								if ( !empty( $_COOKIE['membershipuselevel'] ) ) {
									if ( $key != $_COOKIE['membershipuselevel'] ) {
										$valid = $index ? 1 : 0;
									}
								} else {
									$valid = true;
								}
								
							}
							
							add_shortcode( stripslashes( trim( $value ) ), array( $this, $valid ? 'do_level_shortcode' : 'do_levelprotected_shortcode' ) );
						}
					}
				}
			}
		}

		function wdfb_registration_redirect_url($url) {
			global $M_options;
			$url = get_permalink($M_options['registration_page']);
			return $url;
		}

		function initialise_plugin() {
			global $user, $M_options;

			$M_options = defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && MEMBERSHIP_GLOBAL_TABLES === true && function_exists( 'get_blog_option' )
				? get_blog_option( MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array() )
				: get_option( 'membership_options', array() );

			// Create our subscription page shortcode
			add_shortcode( 'subscriptionform', array( $this, 'do_subscription_shortcode' ) );
			add_shortcode( 'accountform', array( $this, 'do_account_shortcode' ) );
			add_shortcode( 'upgradeform', array( $this, 'do_upgrade_shortcode' ) );
			add_shortcode( 'renewform', array( $this, 'do_renew_shortcode' ) );

			do_action( 'membership_register_shortcodes' );

			// Check if we are on a membership specific page
			add_filter( 'the_posts', array( $this, 'check_for_membership_pages' ), 99 );
			add_filter( 'the_content', array( $this, 'check_for_membership_pages_content' ), 1 );

			// Check for subscription shortcodes - and if needed queue styles
			add_filter( 'the_posts', array( $this, 'add_subscription_styles' ) );

			$user = wp_get_current_user();

			if ( M_get_membership_active() == 'no' ) {
				// The plugin isn't active so just return
				return;
			}

			if ( $user->has_cap('membershipadmin') || $user->has_cap('manage_options') || is_super_admin($user->ID) ) {
				// Admins can see everything - unless we have a cookie set to limit viewing
				if ( empty( $_COOKIE['membershipuselevel'] ) || $_COOKIE['membershipuselevel'] == '0' ) {
					return;
				}
			}

			// More tags
			if ( isset( $M_options['moretagdefault'] ) && $M_options['moretagdefault'] == 'no' ) {
				// More tag content is not visible by default - works for both web and rss content - unfortunately
				add_filter( 'the_content_more_link', array( $this, 'show_moretag_protection' ), 99, 2 );
				add_filter( 'the_content', array( $this, 'replace_moretag_content' ), 1 );
				add_filter( 'the_content_feed', array( $this, 'replace_moretag_content' ), 1 );
			}

			// Shortcodes setup
			if ( !empty( $M_options['membershipshortcodes'] ) ) {
				foreach ( $M_options['membershipshortcodes'] as $value ) {
					if ( !empty( $value ) ) {
						add_shortcode( stripslashes( trim( $value ) ), array( $this, 'do_membership_shortcode' ) );
					}
				}

				// Shortcodes now default to protected for those entered by the user (which will be none for new users / installs)
				$this->override_shortcodes();
			}

			// Downloads protection
			if ( !empty( $M_options['membershipdownloadgroups'] ) ) {
				add_filter( 'the_content', array( $this, 'protect_download_content' ) );
			}

			// check for a no-access page and always filter it if needed
			if ( !empty( $M_options['nocontent_page'] ) && $M_options['nocontent_page'] != $M_options['registration_page'] ) {
				add_filter( 'get_pages', array( $this, 'hide_nocontent_page_from_menu' ), 99 );
			}

			// New registration form settings
			if ( (isset( $M_options['formtype'] ) && $M_options['formtype'] == 'new' ) ) {
				add_action( 'wp_ajax_nopriv_buynow', array( $this, 'popover_signup_form' ) );

				//login and register are no-priv only because, well they aren't logged in or registered
				add_action( 'wp_ajax_nopriv_register_user', array( $this, 'popover_register_process' ) );
				add_action( 'wp_ajax_nopriv_login_user', array( $this, 'popover_login_process' ) );

				// if logged in:
				add_action( 'wp_ajax_buynow', array( $this, 'popover_sendpayment_form' ) );
				add_action( 'wp_ajax_register_user', array( $this, 'popover_register_process' ) );
				add_action( 'wp_ajax_login_user', array( $this, 'popover_login_process' ) );
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

			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}

			if($user->ID > 0) {

				$member = Membership_Plugin::factory()->get_member($user->ID);

				if($member->is_member()) {
					$key = get_user_meta($user->ID, '_membership_key', true);

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

		function handle_paymentgateways($wp_query) {
			if(!empty($wp_query->query_vars['paymentgateway'])) {
				do_action( 'membership_process_payment_return', $wp_query->query_vars['paymentgateway'] );
				// exit();
			}
		}

		function handle_download_protection( $wp_query ) {
			global $user, $member, $wpdb, $M_options;

			if ( !empty( $wp_query->query_vars['protectedfile'] ) ) {
				$protected = explode( "/", $wp_query->query_vars['protectedfile'] );
				$protected = array_pop( $protected );
			}

			if ( empty( $protected ) && !empty( $_GET['ms_file'] ) && 'hybrid' == $M_options['protection_type'] ) {
				$protected = $_GET['ms_file'];
			}

			if ( !empty( $protected ) ) {
				// See if the filename has a size extension and if so, strip it out
				$filename_exp = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
				$filematch = array( );
				if ( preg_match( $filename_exp, $protected, $filematch ) ) {
					// We have an image with an image size attached
					$newfile = $filematch[1] . "." . $filematch[3];
					$size_extension = "-" . $filematch[2];
				} else {
					$newfile = $protected;
					$size_extension = '';
				}
				// Process based on the protection type
				switch ( $M_options['protection_type'] ) {
					case 'complete' :
						// Work out the post_id again
						$post_id = preg_replace( '/^' . MEMBERSHIP_FILE_NAME_PREFIX . '/', '', $newfile );
						$post_id -= (INT) MEMBERSHIP_FILE_NAME_INCREMENT;

						if ( is_numeric( $post_id ) && $post_id > 0 ) {
							$image = get_post_meta( $post_id, '_wp_attached_file', true );
							if ( !empty( $size_extension ) ) {
								// Add back in a size extension if we need to
								$image = str_replace( '.' . pathinfo( $image, PATHINFO_EXTENSION ), $size_extension . '.' . pathinfo( $image, PATHINFO_EXTENSION ), $image );
								// hack to remove any double extensions :/ need to change when work out a neater way
								$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
							}
						}
						break;

					case 'hybrid' :
						// Work out the post_id again
						$post_id = preg_replace( '/^' . MEMBERSHIP_FILE_NAME_PREFIX . '/', '', $newfile );
						$post_id -= (INT) MEMBERSHIP_FILE_NAME_INCREMENT;

						if ( is_numeric( $post_id ) && $post_id > 0 ) {
							$image = get_post_meta( $post_id, '_wp_attached_file', true );
							if ( !empty( $size_extension ) ) {
								// Add back in a size extension if we need to
								$image = str_replace( '.' . pathinfo( $image, PATHINFO_EXTENSION ), $size_extension . '.' . pathinfo( $image, PATHINFO_EXTENSION ), $image );
								// hack to remove any double extensions :/ need to change when work out a neater way
								$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
							}
						}
						break;

					case 'basic' :
					default:
						// The basic protection - need to change this
						$sql = $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s", '%' . $newfile . '%' );
						$post_id = $wpdb->get_var( $sql );

						if ( empty( $post_id ) ) {
							// Can't find the file in the first pass, try the second pass.
							$sql = $this->db->prepare( "SELECT post_id FROM {$this->db->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s", '%' . $protected . '%' );
							$post_id = $this->db->get_var( $sql );
						}

						if ( is_numeric( $post_id ) && $post_id > 0 ) {
							$image = get_post_meta( $post_id, '_wp_attached_file', true );
							if ( !empty( $size_extension ) ) {
								// Add back in a size extension if we need to
								$image = str_replace( '.' . pathinfo( $image, PATHINFO_EXTENSION ), $size_extension . '.' . pathinfo( $image, PATHINFO_EXTENSION ), $image );
								// hack to remove any double extensions :/ need to change when work out a neater way
								$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
							}
						}
						break;
				}


				if ( !empty( $image ) && !empty( $post_id ) && is_numeric( $post_id ) ) {
					// check for protection
					$group = get_post_meta( $post_id, '_membership_protected_content_group', true );

					if ( empty( $group ) || $group == 'no' ) {
						// it's not protected so grab and display it
						//$file = $wp_query->query_vars['protectedfile'];
						$this->output_file( $image );
					} else {
						// check we can see it
						if ( empty( $member ) || !method_exists( $member, 'has_level_rule' ) ) {
							$user = wp_get_current_user();
							$member = Membership_Plugin::factory()->get_member( $user->ID );
						}

						if ( method_exists( $member, 'has_level_rule' ) && $member->has_level_rule( 'downloads' ) && $member->pass_thru( 'downloads', array( 'can_view_download' => $group ) ) ) {
							//$file = $wp_query->query_vars['protectedfile'];
							$this->output_file( $image );
						} else {
							$this->show_noaccess_image( $wp_query );
						}
					}
				} else {
					// We haven't found anything so default to the no access image
					$this->show_noaccess_image( $wp_query );
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

		function find_user_from_key( $key = false ) {
			global $wpdb;

			if ( !$key ) {
				return 0;
			}

			//$key = get_usermeta($user->ID, '_membership_key');
			$sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 0,1", '_membership_key', $key );
			$user_id = $wpdb->get_var( $sql );

			return $user_id;
		}

		// loop and page overrides

		function show_moretag_protection( $more_tag_link, $more_tag ) {
			global $M_options;
			return stripslashes( $M_options['moretagmessage'] );
		}

		function replace_moretag_content( $the_content ) {
			global $M_options;

			$morestartsat = strpos( $the_content, '<span id="more-' );
			if ( $morestartsat !== false ) {
				$the_content = substr( $the_content, 0, $morestartsat );
				$the_content .= stripslashes( $M_options['moretagmessage'] );
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
		function do_levelprotected_shortcode( $atts, $content = null, $code = "" ) {
			global $M_options;

			$factory = Membership_Plugin::factory();

			$code = strtolower( $code );
			if ( substr( $code, 0, 4 ) !== "not-" ) {
				$shortcodes = apply_filters( 'membership_level_shortcodes', array() );
				if ( !empty( $shortcodes ) ) {
					// search positive shortcodes first
					$id = array_search( $code, $shortcodes );
					if ( $id !== false ) {
						// we have found a level so we need to check if it has a custom protected message, otherwise we'll just output the default main on
						$level = $factory->get_level( $id );
						$message = $level->get_meta( 'level_protectedcontent' );
						if ( !empty( $message ) ) {
							return do_shortcode( stripslashes( $message ) );
						}
					}
				}
			} else {
				$notshortcodes = apply_filters( 'membership_not_level_shortcodes', array() );
				if ( !empty( $notshortcodes ) ) {
					// search positive shortcodes first
					$id = array_search( $code, $notshortcodes );
					if ( $id !== false ) {
						// we have found a level so we need to check if it has a custom protected message, otherwise we'll just output the default main on
						$level = $factory->get_level( $id );
						$message = $level->get_meta( 'level_protectedcontent' );
						if ( !empty( $message ) ) {
							return do_shortcode( stripslashes( $message ) );
						}
					}
				}
			}

			// If we are here then we have no custom message, or the shortcode wasn't found so just output the standard message
			return isset( $M_options['shortcodemessage'] )
				? do_shortcode( stripslashes( $M_options['shortcodemessage'] ) )
				: '';
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

		function hide_nocontent_page_from_menu( $pages ) {
			global $M_options;

			foreach ( (array)$pages as $key => $page ) {
				if ( ( isset( $M_options['nocontent_page'] ) && $page->ID == $M_options['nocontent_page'] )
					|| ( isset( $M_options['registrationcompleted_page'] ) && $page->ID == $M_options['registrationcompleted_page'] ) ) {
					unset( $pages[$key] );
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
					foreach((array) $matches[0] as $key => $domain) {
						if(strpos($domain, untrailingslashit($home)) === 0) {
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

															$the_content = str_replace( $matches[0][$foundlocal], $newpath . "?ms_file=" . $protectedfilename, $the_content );
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
			global $bp, $profileuser;

			if ( !is_user_logged_in() ) {
				return apply_filters( 'membership_account_form_not_logged_in', $content );
			}

			require_once ABSPATH . 'wp-admin/includes/user.php';

			$user_id = get_current_user_id();
			$profileuser = get_user_to_edit( $user_id );

			$content = apply_filters( 'membership_account_form_before_content', '' );

			ob_start();
			if ( defined( 'MEMBERSHIP_ACCOUNT_FORM' ) && file_exists( MEMBERSHIP_ACCOUNT_FORM ) ) {
				include( MEMBERSHIP_ACCOUNT_FORM );
			} else {
				$bp_account_form = apply_filters( 'membership_override_bpaccount_form', membership_dir( 'membershipincludes/includes/bp.account.form.php' ), $user_id );
				if ( !empty( $bp ) && file_exists( $bp_account_form ) ) {
					include $bp_account_form;
				} else {
					$account_form = apply_filters( 'membership_override_account_form', membership_dir( 'membershipincludes/includes/account.form.php' ), $user_id );
					if ( file_exists( $account_form ) ) {
						include $account_form;
					}
				}
			}
			$content .= ob_get_clean();
			$content = apply_filters( 'membership_account_form_after_content', $content, $user_id );

			return $content;
		}

		function show_subpage_two($user_id) {

			$content = '';

			$content = apply_filters('membership_subscription_form_before_content', $content, $user_id);

			ob_start();
			if( defined('MEMBERSHIP_SUBSCRIPTION_FORM') && file_exists( MEMBERSHIP_SUBSCRIPTION_FORM ) ) {
				include( MEMBERSHIP_SUBSCRIPTION_FORM );
			} elseif(file_exists( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) ) ) {
				include( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) );
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
				include( MEMBERSHIP_MEMBER_FORM );
			} elseif(file_exists( apply_filters('membership_override_member_form', membership_dir('membershipincludes/includes/member.form.php')) )) {
				include( apply_filters('membership_override_member_form', membership_dir('membershipincludes/includes/member.form.php')) );
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
				include( MEMBERSHIP_UPGRADE_FORM );
			} elseif(file_exists( apply_filters('membership_override_upgrade_form', membership_dir('membershipincludes/includes/upgrade.form.php')) )) {
				include( apply_filters('membership_override_upgrade_form', membership_dir('membershipincludes/includes/upgrade.form.php')) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_upgrade_form_member_after_content', $content, $user_id );

			return $content;

		}

		function show_renew_page( $user_id = false ) {
			$content = apply_filters( 'membership_renew_form_member_before_content', '', $user_id );
			$template = new Membership_Render_Page_Subscription_Renew();
			return apply_filters( 'membership_renew_form_member_after_content', $content . $template->to_html(), $user_id );
		}

		function do_renew_shortcode() {
			return apply_filters( 'membership_renew_form', $this->show_renew_page() );
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

		function do_account_shortcode( $atts, $content = null, $code = "" ) {
			return apply_filters( 'membership_account_form', $this->show_account_page( $content ) );
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
														$member = Membership_Plugin::factory()->get_member( $user_id );
														$member->create_subscription($sub_id, $gateway);
														// Timestamp the update
														update_user_meta( $user_id, '_membership_last_upgraded', time());
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
				include( MEMBERSHIP_SUBSCRIPTION_FORM );
			} elseif(file_exists( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) ) ) {
				include( apply_filters('membership_override_subscription_form', membership_dir('membershipincludes/includes/subscription.form.php'), $user_id) );
			}
			$content .= ob_get_contents();
			ob_end_clean();

			$content = apply_filters('membership_subscription_form_after_content', $content, $user_id );

			return $content;
		}

		function output_registeruser( $error = false ) {
			$template = new Membership_Render_Page_Registration_Standard();

			$template->error = $error;
			$template->subscription = isset( $_REQUEST['subscription'] ) ? absint( $_REQUEST['subscription'] ) : 0;

			$content = apply_filters('membership_subscription_form_registration_before_content', '', $error);
			$content .= $template->to_html();
			$content = apply_filters('membership_subscription_form_registration_after_content', $content, $error);

			return $content;
		}

		function output_paymentpage( $user_id = false ) {
			global $wp_query, $M_options;

			$subscription = (int) $_REQUEST['subscription'];

			if ( !$user_id ) {
				$user = wp_get_current_user();
				if ( !empty( $user->ID ) && is_numeric( $user->ID ) ) {
					$member = Membership_Plugin::factory()->get_member( $user->ID );
				} else {
					$member = current_member();
				}
			} else {
				$member = Membership_Plugin::factory()->get_member( $user_id );
			}

			$error = '';
			$content = apply_filters( 'membership_subscription_form_payment_before_content', '', $error );
			ob_start();
			if ( defined( 'MEMBERSHIP_PAYMENT_FORM' ) && file_exists( MEMBERSHIP_PAYMENT_FORM ) ) {
				include( MEMBERSHIP_PAYMENT_FORM );
			} else {
				$filename = apply_filters( 'membership_override_payment_form', membership_dir( 'membershipincludes/includes/payment.form.php' ), $error );
				if ( file_exists( $filename ) ) {
					include $filename;
				}
			}
			$content .= ob_get_clean();
			$content = apply_filters( 'membership_subscription_form_payment_after_content', $content, $error );

			return $content;
		}

		function process_subscription_form() {
			global $M_options, $bp;

			$logged_in = is_user_logged_in();
			$subscription = isset( $_REQUEST['subscription'] ) ? $_REQUEST['subscription'] : 0;
			$page = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'subscriptionform';

			switch ( $page ) {
				case 'validatepage1':
					if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
						return;
					}

					$required = array(
						'user_login' => __( 'Username', 'membership' ),
						'user_email' => __( 'Email address', 'membership' ),
						'password'   => __( 'Password', 'membership' ),
						'password2'  => __( 'Password confirmation', 'membership' ),
					);

					$this->_register_errors = new WP_Error();
					foreach ( $required as $key => $message ) {
						if ( empty( $_POST[$key] ) ) {
							$this->_register_errors->add( $key, __( 'Please ensure that the ', 'membership' ) . "<strong>" . $message . "</strong>" . __( ' information is completed.', 'membership' ) );
						}
					}

					if ( $_POST['password'] != $_POST['password2'] ) {
						$this->_register_errors->add( 'passmatch', __( 'Please ensure the passwords match.', 'membership' ) );
					}

					if ( !validate_username( $_POST['user_login'] ) ) {
						$this->_register_errors->add( 'usernamenotvalid', __( 'The username is not valid, sorry.', 'membership' ) );
					}

					if ( username_exists( sanitize_user( $_POST['user_login'] ) ) ) {
						$this->_register_errors->add( 'usernameexists', __( 'That username is already taken, sorry.', 'membership' ) );
					}

					if ( !is_email( $_POST['user_email'] ) ) {
						$this->_register_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', 'membership' ) );
					}

					if ( email_exists( $_POST['user_email'] ) ) {
						$this->_register_errors->add( 'emailexists', __( 'That email address is already taken, sorry.', 'membership' ) );
					}

					$this->_register_errors = apply_filters( 'membership_subscription_form_before_registration_process', $this->_register_errors );

					$result = apply_filters( 'wpmu_validate_user_signup', array(
						'user_name' => $_POST['user_login'],
						'orig_username' => $_POST['user_login'],
						'user_email' => $_POST['user_email'],
						'errors' => $this->_register_errors
					) );

					$this->_register_errors = $result['errors'];

					// Hack for now - eeek
					$anyerrors = $this->_register_errors->get_error_code();
					if ( empty( $anyerrors ) ) {
						// No errors so far - error reporting check for final add user *note $error should always be an error object becuase we created it as such.
						$user_id = wp_create_user( sanitize_user( $_POST['user_login'] ), $_POST['password'], $_POST['user_email'] );

						if ( is_wp_error( $user_id ) ) {
							$this->_register_errors->add( 'userid', $user_id->get_error_message() );
						} else {
							$member = Membership_Plugin::factory()->get_member( $user_id );
							if ( !headers_sent() ) {
								$user = @wp_signon( array(
									'user_login'    => $_POST['user_login'],
									'user_password' => $_POST['password'],
									'remember'      => true,
								) );

								if ( is_wp_error( $user ) && method_exists( $user, 'get_error_message' ) ) {
									$this->_register_errors->add( 'userlogin', $user->get_error_message() );
								} else {
									// Set the current user up
									wp_set_current_user( $user_id );
								}
							} else {
								// Set the current user up
								wp_set_current_user( $user_id );
							}

							if ( has_action( 'membership_susbcription_form_registration_notification' ) ) {
								do_action( 'membership_susbcription_form_registration_notification', $user_id, $_POST['password'] );
							} else {
								wp_new_user_notification( $user_id, $_POST['password'] );
							}
							
							if ( ! empty($M_options['freeusersubscription']) ) {
								$level = ! empty($M_options['strangerlevel']) ? $M_options['strangerlevel'] : 0;
								//free subscription is active - do 'membership_add_subscription' action so pings are triggered, etc
								do_action('membership_add_subscription', $M_options['freeusersubscription'], $level, false, $user_id);
							}
						}

						do_action( 'membership_subscription_form_registration_process', $this->_register_errors, $user_id );
					} else {
						do_action( 'membership_subscription_form_registration_process', $this->_register_errors, 0 );
					}

					// Hack for now - eeek
					$anyerrors = $this->_register_errors->get_error_code();
					if ( empty( $anyerrors ) ) {
						// redirect to payments page
						wp_redirect( add_query_arg( array(
							'action'       => 'subscriptionsignup',
							'subscription' => $subscription,
						) ) );
						exit;
					}

					break;

				case 'validatepage1bp':
					if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
						return;
					}

					$required = array(
						'signup_username'         => __( 'Username', 'membership' ),
						'signup_email'            => __( 'Email address', 'membership' ),
						'signup_password'         => __( 'Password', 'membership' ),
						'signup_password_confirm' => __( 'Password confirmation', 'membership' ),
					);

					$this->_register_errors = new WP_Error();

					foreach ( $required as $key => $message ) {
						if ( empty( $_POST[$key] ) ) {
							$this->_register_errors->add( $key, __( 'Please ensure that the ', 'membership' ) . "<strong>" . $message . "</strong>" . __( ' information is completed.', 'membership' ) );
						}
					}

					if ( $_POST['signup_password'] != $_POST['signup_password_confirm'] ) {
						$this->_register_errors->add( 'passmatch', __( 'Please ensure the passwords match.', 'membership' ) );
					}

					if ( !validate_username( $_POST['signup_username'] ) ) {
						$this->_register_errors->add( 'usernamenotvalid', __( 'The username is not valid, sorry.', 'membership' ) );
					}

					if ( username_exists( sanitize_user( $_POST['signup_username'] ) ) ) {
						$this->_register_errors->add( 'usernameexists', __( 'That username is already taken, sorry.', 'membership' ) );
					}

					if ( !is_email( $_POST['signup_email'] ) ) {
						$this->_register_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', 'membership' ) );
					}

					if ( email_exists( $_POST['signup_email'] ) ) {
						$this->_register_errors->add( 'emailexists', __( 'That email address is already taken, sorry.', 'membership' ) );
					}

					// Initial fix provided by user: cmurtagh - modified to add extra checks and rejigged a bit
					// Run the buddypress validation
					do_action( 'bp_signup_validate' );

					// Add any errors to the action for the field in the template for display.
					if ( !empty( $bp->signup->errors ) ) {
						foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
							$this->_register_errors->add( $fieldname, $error_message );
						}
					}

					$meta_array = array();

					// xprofile required fields
					/* Now we've checked account details, we can check profile information */
					//if ( function_exists( 'xprofile_check_is_required_field' ) ) {
					if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {

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
									$this->_register_errors->add( $field->name, __( 'Please ensure that the ', 'membership' ) . "<strong>" . $field->name . "</strong>" . __( ' information is completed.', 'membership' ) );
								}

								$meta_array[$field_id] = $_POST['field_' . $field_id];
							}
						}
					}

					$this->_register_errors = apply_filters( 'membership_subscription_form_before_registration_process', $this->_register_errors );

					// Hack for now - eeek
					$anyerrors = $this->_register_errors->get_error_code();
					if ( empty( $anyerrors ) ) {
						// No errors so far - error reporting check for final add user *note $error should always be an error object becuase we created it as such.
						$user_id = wp_create_user( sanitize_user( $_POST['signup_username'] ), $_POST['signup_password'], $_POST['signup_email'] );

						if ( is_wp_error( $user_id ) ) {
							$this->_register_errors->add( 'userid', $user_id->get_error_message() );
						} else {
							$member = Membership_Plugin::factory()->get_member( $user_id );
							if ( !headers_sent() ) {
								$user = @wp_signon( array(
									'user_login'    => $_POST['signup_username'],
									'user_password' => $_POST['signup_password'],
									'remember'      => true
								) );

								if ( is_wp_error( $user ) && method_exists( $user, 'get_error_message' ) ) {
									$this->_register_errors->add( 'userlogin', $user->get_error_message() );
								} else {
									// Set the current user up
									wp_set_current_user( $user_id );
								}
							} else {
								// Set the current user up
								wp_set_current_user( $user_id );
							}

							if ( has_action( 'membership_susbcription_form_registration_notification' ) ) {
								do_action( 'membership_susbcription_form_registration_notification', $user_id, $_POST['signup_password'] );
							} else {
								wp_new_user_notification( $user_id, $_POST['signup_password'] );
							}

							if ( function_exists( 'xprofile_set_field_data' ) ) {
								// Add the bp filter for usermeta signup
								$meta_array = apply_filters( 'bp_signup_usermeta', $meta_array );
								foreach ( (array)$meta_array as $field_id => $field_content ) {
									xprofile_set_field_data( $field_id, $user_id, $field_content );
									$visibility_level = !empty( $_POST['field_' . $field_id . '_visibility'] ) ? $_POST['field_' . $field_id . '_visibility'] : 'public';
									xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
								}
								
								// Make sure the User Meta is updated with the xprofile name
							 	$data = explode( ' ', xprofile_get_field_data( 'Name', $user_id, 'array' ) );
								$firstname = array_shift( $data );
								$lastname = implode( ' ', $data );
								update_user_meta( $user_id, 'first_name', $firstname );
								update_user_meta( $user_id, 'last_name', $lastname );				
							}
						}

						do_action( 'membership_subscription_form_registration_process', $this->_register_errors, $user_id );

						// Hack for now - eeek
						$anyerrors = $this->_register_errors->get_error_code();
						if ( empty( $anyerrors ) ) {
							// everything seems fine (so far), so we have our queued user so let's
							// run the bp complete signup action
							do_action( 'bp_complete_signup' );

							// redirect to payments page
							wp_redirect( add_query_arg( array(
								'action'       => 'subscriptionsignup',
								'subscription' => $subscription,
							) ) );
							exit;
						}
					} else {
						do_action( 'membership_subscription_form_registration_process', $this->_register_errors, 0 );
					}
					break;

				case 'registeruser':
				case 'subscriptionsignup':
					$to_sub_id = false;

					// free subscription processing
					if ( $logged_in && $subscription ) {
						$sub = Membership_Plugin::factory()->get_subscription( $subscription );
						if ( $sub->is_free() ) {
							$to_sub_id = $subscription;
						}
					}

					// coupon processing
					$coupon = filter_input( INPUT_POST, 'coupon_code' );
					$sub_id = filter_input( INPUT_POST, 'coupon_sub_id', FILTER_VALIDATE_INT );
					if ( $logged_in && $coupon && $sub_id ) {
						$coupon = new M_Coupon( $coupon );
						$coupon_obj = $coupon->get_coupon();

						if ( $coupon->valid_coupon() && $coupon_obj->discount >= 100 && $coupon_obj->discount_type == 'pct' ) {
							$to_sub_id = $sub_id;
							$coupon->increment_coupon_used();
						}
					}

					if ( $to_sub_id ) {
						$member = Membership_Plugin::factory()->get_member( get_current_user_id() );

						$from_sub_id = isset( $_REQUEST['from_subscription'] ) ? absint( $_REQUEST['from_subscription'] ) : 0;
						if ( $from_sub_id ) {
							$member->drop_subscription( $from_sub_id );
						}

						$member->create_subscription( $to_sub_id );

						if ( isset( $M_options['registrationcompleted_page'] ) && absint( $M_options['registrationcompleted_page'] ) ) {
							wp_redirect( get_permalink( $M_options['registrationcompleted_page'] ) );
							exit;
						}
					}
					break;
			}
		}

		function do_subscription_form() {
			$content = '';
			$page = isset( $_REQUEST['action'] )
				? $_REQUEST['action']
				: 'subscriptionform';

			switch( $page ) {
				case 'subscriptionform':
					$content = $this->output_subscriptionform();
					break;

				case 'registeruser':
				case 'subscriptionsignup':
					if ( !is_user_logged_in() ) {
						$content = $this->output_registeruser();
					} else {
						$content = $this->output_paymentpage();
					}
					break;

				case 'validatepage1':
				case 'validatepage1bp':
					$content = $this->output_registeruser( $this->_register_errors );
					break;
			}

			return $content;

		}

		function do_subscription_shortcode($atts, $content = null, $code = "") {

			global $wp_query;

			return $this->do_subscription_form();

		}

		function create_the_user_and_notify() {
			//$user_id = wp_create_user(sanitize_user($_POST['user_login']), $_POST['password'], $_POST['user_email']);
			//wp_new_user_notification( $user_id, $_POST['password'] );
		}

		function enqueue_public_form_styles() {
			wp_enqueue_style( 'membership-publicformscss', MEMBERSHIP_ABSURL . 'css/publicforms.css', null, Membership_Plugin::VERSION );
			wp_enqueue_style( 'membership-buttoncss', MEMBERSHIP_ABSURL . 'css/buttons.css', null, Membership_Plugin::VERSION );
		}

		function check_for_membership_pages_content($content) {
			global $M_options, $post;

			if ( empty( $post ) || $post->post_type != 'page' ) {
				return $content;
			}

			if ( membership_is_registration_page( $post->ID, false ) ) {

				// check if page contains a shortcode
				if ( strpos( $content, '[subscriptionform]' ) === false ) {
					// There is no shortcode content in there, so override
					remove_filter( 'the_content', 'wpautop' );
					$content .= $this->do_subscription_form();
				}
			} elseif ( membership_is_account_page( $post->ID, false ) ) {
				// account page - check if page contains a shortcode
				if ( strpos( $content, '[accountform]' ) !== false || strpos( $content, '[upgradeform]' ) !== false || strpos( $content, '[renewform]' ) !== false ) {
					// There is content in there with the shortcode so just return it
					return $content;
				}

				// There is no shortcode in there, so override
				remove_filter( 'the_content', 'wpautop' );
				$content .= $this->show_account_page();
			} elseif ( membership_is_subscription_page( $post->ID, false ) ) {

				// account page - check if page contains a shortcode
				if ( strpos( $content, '[upgradeform]' ) !== false || strpos( $content, '[renewform]' ) !== false ) {
					// There is content in there with the shortcode so just return it
					return $content;
				}

				// There is no shortcode in there, so override
				remove_filter( 'the_content', 'wpautop' );
				$content .= $this->do_renew_form();
			}

			return $content;
		}

		function check_for_membership_pages( $posts ) {
			global $M_options;

			if ( count( $posts ) != 1 ) {
				return $posts;
			}

			// We have only the one post, so check if it's one of our pages
			$post = $posts[0];
			if ( $post->post_type != 'page' ) {
				return $posts;
			}

			if ( membership_is_registration_page( $post->ID, false ) ) {

				// Redirect members with subscriptions to the subscriptions page if it exists, else account page.
				global $member;
				$member = Membership_Plugin::current_member();
				
				if ( !empty( $member ) ) {
					if ( $member->has_subscription() && $member->ID != 0 && ! isset( $_GET['from_subscription'] ) ) {
						if( ! empty( $M_options['subscriptions_page'] ) ) {
							wp_redirect( get_permalink( $M_options['subscriptions_page'] ) );
							exit;						
						} else {
							wp_redirect( get_permalink( $M_options['account_page'] ) );
							exit;						
						}
					}				
				}			
				
				add_action( 'template_redirect', array( $this, 'process_subscription_form' ), 1 );

				if ( strpos( $post->post_content, '[subscriptionform]' ) !== false || strpos( $post->post_content, '[renewform]' ) !== false) {
					// bail - shortcode found
					return $posts;
				}

				// registration page found - add in the styles
				if ( !current_theme_supports( 'membership_subscription_form' ) ) {
					wp_enqueue_style( 'membership-subscriptionformcss', MEMBERSHIP_ABSURL . 'css/subscriptionform.css', null, Membership_Plugin::VERSION );

					add_action( 'wp_head', array( $this, 'enqueue_public_form_styles' ), 99 );
					$this->enqueue_fancybox_scripts();
				}

				do_action( 'membership_subscriptionbutton_onpage' );

				// There is no shortcode content in there, so override
				remove_filter( 'the_content', 'wpautop' );

			} elseif ( membership_is_account_page( $post->ID, false ) ) {
				// account page - check if page contains a shortcode
				if ( strpos( $post->post_content, '[accountform]' ) !== false || strpos( $post->post_content, '[upgradeform]' ) !== false || strpos( $post->post_content, '[renewform]' ) !== false ) {
					// There is content in there with the shortcode so just return it
					return $posts;
				}

				// account page found - add in the styles
				if ( !current_theme_supports( 'membership_account_form' ) ) {
					wp_enqueue_style( 'membership-accountformcss', MEMBERSHIP_ABSURL . 'css/accountform.css', null, Membership_Plugin::VERSION );
					wp_enqueue_script( 'membership-accountformjs', MEMBERSHIP_ABSURL . 'js/accountform.js', array( 'jquery' ), Membership_Plugin::VERSION );

					add_action( 'wp_head', array( $this, 'enqueue_public_form_styles' ), 99 );
				}

				// There is no shortcode in there, so override
				remove_filter( 'the_content', 'wpautop' );

			} elseif ( membership_is_subscription_page( $post->ID, false ) ) {
				// Handle any updates passed
				$page = isset( $_REQUEST['action'] ) ? addslashes( $_REQUEST['action'] ) : '';
				if ( empty( $page ) ) {
					$page = 'renewform';
				}

				if ( $page == 'subscriptionsignup' ) {
					if ( is_user_logged_in() ) {
						$member = current_member();
						list($timestamp, $user_id, $sub_id, $key, $sublevel) = explode( ':', $_POST['custom'] );

						if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'free-sub_' . $sub_id ) ) {
							$gateway = $_POST['gateway'];
							// Join the new subscription
							$member->create_subscription( $sub_id, $gateway );
							do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

							// Timestamp the update
							update_user_meta( $user_id, '_membership_last_upgraded', time() );

							// Added another redirect to the same url because the show_no_access filters
							// have already run on the "parse_request" action (Cole)
							wp_redirect( M_get_subscription_permalink() );
							exit;
						}
					} else {
						// check if a custom is posted and of so then process the user
						if ( isset( $_POST['custom'] ) ) {
							list( $timestamp, $user_id, $sub_id, $key, $sublevel ) = explode( ':', $_POST['custom'] );
							if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'free-sub_' . $sub_id ) ) {
								$gateway = $_POST['gateway'];
								// Join the new subscription
								$member = Membership_Plugin::factory()->get_member( $user_id );
								$member->create_subscription( $sub_id, $gateway );
								do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

								// Timestamp the update
								update_user_meta( $user_id, '_membership_last_upgraded', time() );

								// Added another redirect to the same url because the show_no_access filters
								// have already run on the "parse_request" action (Cole)
								wp_redirect( M_get_subscription_permalink() );
								exit;
							}
						}
					}
				}

				// account page - check if page contains a shortcode
				if ( strstr( $post->post_content, '[upgradeform]' ) !== false || strstr( $post->post_content, '[renewform]' ) !== false ) {
					// There is content in there with the shortcode so just return it
					if ( !current_theme_supports( 'membership_subscription_form' ) ) {
						$this->enqueue_subscription_scripts();
					}
					return $posts;
				}

				// account page found - add in the styles
				if ( !current_theme_supports( 'membership_account_form' ) ) {
					$this->enqueue_subscription_scripts();
				}
				// There is no shortcode in there, so override
				remove_filter( 'the_content', 'wpautop' );

			} elseif ( membership_is_protected_page( $post->ID, false ) ) {
				// no access page - we must return the content entered by the user so just return it
				return $posts;

			} elseif ( membership_is_welcome_page( $post->ID, false ) ) {
				// Registration complete page

				// Handle any updates passed
				$page = isset( $_REQUEST['action'] ) && !empty( $_REQUEST['action'] ) ? addslashes( $_REQUEST['action'] ) : 'renewform';

				if ( $page == 'subscriptionsignup' ) {
					if ( is_user_logged_in() && isset( $_POST['custom'] ) ) {
						list( $timestamp, $user_id, $sub_id, $key, $sublevel ) = explode( ':', $_POST['custom'] );
						if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'free-sub_' . $sub_id ) ) {
							$member = Membership_Plugin::factory()->get_member( $user_id );
							$member->create_subscription( $sub_id, $_POST['gateway'] );
							do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

							// Timestamp the update
							update_user_meta( $user_id, '_membership_last_upgraded', time() );

							// Added another redirect to the same url because the show_no_access filters
							// have already run on the "parse_request" action (Cole)
							wp_redirect( M_get_returnurl_permalink() );
							exit;
						}
					} else {
						// check if a custom is posted and of so then process the user
						if ( isset( $_POST['custom'] ) ) {
							list( $timestamp, $user_id, $sub_id, $key, $sublevel ) = explode( ':', $_POST['custom'] );
							if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'free-sub_' . $sub_id ) ) {
								$member = Membership_Plugin::factory()->get_member( $user_id );
								$member->create_subscription( $sub_id, $_POST['gateway'] );
								do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

								// Timestamp the update
								update_user_meta( $user_id, '_membership_last_upgraded', time() );

								// Added another redirect to the same url because the show_no_access filters
								// have already run on the "parse_request" action (Cole)
								wp_redirect( M_get_returnurl_permalink() );
								exit;
							}
						}
					}
				}

				return $posts;
			}

			// If nothing else is hit, just return the content
			return $posts;
		}

		function enqueue_subscription_scripts() {
			wp_enqueue_style( 'membership-subscriptionformcss', MEMBERSHIP_ABSURL . 'css/subscriptionform.css', null, Membership_Plugin::VERSION );
			wp_enqueue_style( 'membership-upgradeformcss', MEMBERSHIP_ABSURL . 'css/upgradeform.css', null, Membership_Plugin::VERSION );

			wp_enqueue_style( 'membership-renewformcss', MEMBERSHIP_ABSURL . 'css/renewform.css', null, Membership_Plugin::VERSION );
			wp_enqueue_script( 'membership-renewformjs', MEMBERSHIP_ABSURL . 'js/renewform.js', array( 'jquery' ), Membership_Plugin::VERSION );
			wp_localize_script( 'membership-renewformjs', 'membership', array(
				'unsubscribe'     => __( 'Are you sure you want to unsubscribe from this subscription?', 'membership' ),
				'deactivatelevel' => __( 'Are you sure you want to deactivate this level?', 'membership' ),
			) );

			add_action( 'wp_head', array( $this, 'enqueue_public_form_styles' ), 99 );
			$this->enqueue_fancybox_scripts();
		}

		function enqueue_fancybox_scripts() {
			global $M_options;
			if ( isset($M_options['formtype']) && $M_options['formtype'] == 'new' ) {
				wp_enqueue_style( 'membership-fancyboxcss', MEMBERSHIP_ABSURL . 'js/fancybox/jquery.fancybox-1.3.4.css', null, Membership_Plugin::VERSION );
				wp_enqueue_script( 'membership-fancyboxjs', MEMBERSHIP_ABSURL . 'js/fancybox/jquery.fancybox-1.3.4.pack.js', array( 'jquery' ), null, true );

				wp_enqueue_script( 'membership-popupmemjs', MEMBERSHIP_ABSURL . 'js/popupregistration.js', array( 'jquery' ), null, true );
				wp_enqueue_style( 'membership-popupmemcss', MEMBERSHIP_ABSURL . 'css/popupregistration.css', null, Membership_Plugin::VERSION );
				wp_localize_script( 'membership-popupmemjs', 'membership', array(
					'ajaxurl'       => admin_url( 'admin-ajax.php' ),
					'registernonce' => wp_create_nonce( 'membership_register' ),
					'loginnonce'    => wp_create_nonce( 'membership_login' ),
					'regproblem'    => __( 'Problem with registration.', 'membership' ),
					'logpropblem'   => __( 'Problem with Login.', 'membership' ),
					'regmissing'    => __( 'Please ensure you have completed all the fields', 'membership' ),
					'regnomatch'    => __( 'Please ensure passwords match', 'membership' ),
					'logmissing'    => __( 'Please ensure you have entered an username or password', 'membership' )
				) );
			}
		}

		function add_subscription_styles( $posts ) {
			if ( !is_array( $posts ) && !is_a( $posts, 'WP_Post' ) ) {
				return $posts;
			}

			foreach ( (array) $posts as $post ) {
				if ( strstr( $post->post_content, '[subscriptionform]' ) !== false ) {
					// The shortcode is in a post on this page, add the header
					if ( !current_theme_supports( 'membership_subscription_form' ) ) {
						$this->enqueue_subscription_scripts();
					}
				}

				if ( strstr( $post->post_content, '[accountform]' ) !== false ) {
					// The shortcode is in a post on this page, add the header
					if ( !current_theme_supports( 'membership_account_form' ) ) {
						wp_enqueue_style( 'membership-accountformcss', MEMBERSHIP_ABSURL . 'css/accountform.css', null, Membership_Plugin::VERSION );
						wp_enqueue_script( 'membership-accountformjs', MEMBERSHIP_ABSURL . 'js/accountform.js', array( 'jquery' ), Membership_Plugin::VERSION );
						add_action( 'wp_head', array( $this, 'enqueue_public_form_styles' ), 99 );
					}
				}

				if ( strstr( $post->post_content, '[upgradeform]' ) !== false ) {
					// The shortcode is in a post on this page, add the header
					if ( !current_theme_supports( 'membership_account_form' ) ) {
						wp_enqueue_style( 'membership-upgradeformcss', MEMBERSHIP_ABSURL . 'css/upgradeform.css', null, Membership_Plugin::VERSION );
						add_action( 'wp_head', array( $this, 'enqueue_public_form_styles' ), 99 );
					}
				}

				if ( strstr( $post->post_content, '[renewform]' ) !== false ) {
					// The shortcode is in a post on this page, add the header
					if ( !current_theme_supports( 'membership_account_form' ) ) {
						wp_enqueue_style( 'membership-renewformcss', MEMBERSHIP_ABSURL . 'css/renewform.css', null, Membership_Plugin::VERSION );

						wp_enqueue_script( 'membership-renewformjs', MEMBERSHIP_ABSURL . 'js/renewform.js', array( 'jquery' ), Membership_Plugin::VERSION );
						wp_localize_script( 'membership-renewformjs', 'membership', array(
							'unsubscribe'     => __( 'Are you sure you want to unsubscribe from this subscription?', 'membership' ),
							'deactivatelevel' => __( 'Are you sure you want to deactivate this level?', 'membership' )
						) );

						add_action( 'wp_head', array( $this, 'enqueue_public_form_styles' ), 99 );
					}
				}

				// New subscription styles
				if ( strstr( $post->post_content, '[subscriptiontitle' ) !== false ) {
					do_action( 'membership_subscriptiontitle_onpage' );
				}

				if ( strstr( $post->post_content, '[subscriptiondetails' ) !== false ) {
					do_action( 'membership_subscriptiondetails_onpage' );
				}

				if ( strstr( $post->post_content, '[subscriptionbutton' ) !== false ) {
					// The shortcode is in a post on this page, add the header
					if ( !current_theme_supports( 'membership_subscription_form' ) ) {
						$this->enqueue_fancybox_scripts();
					}
					$this->enqueue_public_form_styles();
					do_action( 'membership_subscriptionbutton_onpage' );
				}

				if ( strstr( $post->post_content, '[subscriptionprice' ) !== false ) {
					do_action( 'membership_subscriptionprice_onpage' );
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

			$orderby[] = 'order_num ASC';

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
endif;