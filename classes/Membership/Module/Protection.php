<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * The module responsible for membership protection.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_Protection extends Membership_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin class.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_add_action( 'plugins_loaded', 'register_rules' );
		$this->_add_action( 'plugins_loaded', 'check_membership_status' );
		$this->_add_action( 'template_redirect', 'protect_current_page', 1 );
		$this->_add_action( 'parse_request', 'initialise_protection', 2 );
		$this->_add_action( 'init', 'init_current_member' );

		$this->_add_filter( 'wp_authenticate_user', 'check_membership_is_active_on_signin', 30 );
	}

	/**
	 * Initializes current member.
	 *
	 * @since 3.5
	 * @action init
	 *
	 * @global Membership_Model_Member $member Current member.
	 */
	public function init_current_member() {
		global $member;
		$member = Membership_Plugin::current_member();
	}

	/**
	 * Checks whether curren member is active or not. If member is deactivated,
	 * then he has to be logged out immediately.
	 *
	 * @since 3.5
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function check_membership_status() {
		if ( !is_user_logged_in() ) {
			return;
		}

		$member = $this->_plugin->get_factory()->get_member( get_current_user_id() );
		if ( !$member->active_member() ) {
			// member is not active, then logout and refresh page
			wp_logout();
			wp_redirect( home_url( $_SERVER['REQUEST_URI'] ) );
			exit;
		}
	}

	/**
	 * Checks whether member is active or not before user signed in. If member
	 * is deactivated, then he won't be able to sign in.
	 *
	 * @since 3.5
	 * @filter wp_authenticate_user 30
	 *
	 * @access public
	 * @param WP_User $user User object which tries to authenticate into the site.
	 * @return WP_User|WP_Error Current user if member is active, otherwise WP_Error object.
	 */
	public function check_membership_is_active_on_signin( $user ) {
		if ( is_wp_error( $user ) || !is_a( $user, 'WP_User' ) ) {
			return $user;
		}

		$member = $this->_plugin->get_factory()->get_member( $user->ID );
		if ( !$member->active_member() ) {
			return new WP_Error( 'member_inactive', __( 'Sorry, this account is deactivated.', 'membership' ) );
		}

		return $user;
	}

	/**
	 * Registers membership rules.
	 *
	 * @since 3.5
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function register_rules() {
		// general rules
		M_register_rule( 'comments',   'Membership_Model_Rule_Comments',   'main' );
		M_register_rule( 'more',       'Membership_Model_Rule_More',       'main' );
		M_register_rule( 'categories', 'Membership_Model_Rule_Categories', 'main' );
		M_register_rule( 'pages',      'Membership_Model_Rule_Pages',      'main' );
		M_register_rule( 'posts',      'Membership_Model_Rule_Posts',      'main' );
		M_register_rule( 'menu',       'Membership_Model_Rule_Menu',       'main' );
		M_register_rule( 'urlgroups',  'Membership_Model_Rule_URLGroups',  'main' );
		M_register_rule( 'downloads',  'Membership_Model_Rule_Downloads',  'content' );
		M_register_rule( 'shortcodes', 'Membership_Model_Rule_Shortcodes', 'content' );

		// multisites rules
		if ( is_multisite() ) {
			M_register_rule( 'blogcreation', 'Membership_Model_Rule_Blogcreation', 'admin' );
		}

		if ( defined( 'M_LITE' ) ) {
			M_register_rule( 'upgrade', 'Membership_Model_Rule_Upgrade',        'admin' );
			M_register_rule( 'upgrade', 'Membership_Model_Rule_Upgrade',        'bp' );
		}
		else {
				
			// admin rules
			M_register_rule( 'mainmenus', 'Membership_Model_Rule_Admin_Mainmenus',        'admin' );
			M_register_rule( 'submenus',  'Membership_Model_Rule_Admin_Submenus',         'admin' );
			M_register_rule( 'dashboard', 'Membership_Model_Rule_Admin_Dashboardwidgets', 'admin' );
			M_register_rule( 'plugins',   'Membership_Model_Rule_Admin_Plugins',          'admin' );

			// buddypress rules
			if ( defined( 'BP_VERSION' ) && version_compare( preg_replace( '/-.*$/', '', BP_VERSION ), '1.5', '>=' ) ) {
				M_register_rule( 'bppages',          'Membership_Model_Rule_Buddypress_Pages',          'bp' );
				M_register_rule( 'bpprivatemessage', 'Membership_Model_Rule_Buddypress_Privatemessage', 'bp' );
				M_register_rule( 'bpfriendship',     'Membership_Model_Rule_Buddypress_Friendship',     'bp' );
				M_register_rule( 'bpblogs',          'Membership_Model_Rule_Buddypress_Blogs',          'bp' );
				M_register_rule( 'bpgroupcreation',  'Membership_Model_Rule_Buddypress_Groupcreation',  'bp' );
				M_register_rule( 'bpgroups',         'Membership_Model_Rule_Buddypress_Groups',         'bp' );
			}
		}

		// marketpress rules
		if ( class_exists( 'MarketPress' ) ) {
			M_register_rule( 'marketpress', 'Membership_Model_Rule_Marketpress_Pages', 'content' );
		}

		do_action( 'membership_register_rules' );
	}

	/**
	 * Checks member permissions and protects current page.
	 *
	 * @since 3.5
	 * @action template_redirect 1
	 *
	 * @access public
	 */
	public function protect_current_page() {
		global $post, $M_options;

		// If welcome page then redirect.
		if ( isset( $M_options['registrationcompleted_page'] ) && $post->ID == $M_options['registrationcompleted_page'] && ( !is_user_logged_in() || !Membership_Plugin::current_member()->has_subscription() ) ) {
			membership_redirect_to_protected();
			exit;
		}

		if ( membership_is_special_page() ) {
			if ( membership_is_account_page() && !is_user_logged_in() ) {
				membership_redirect_to_protected();
			}
			return;
		}
		
		if ( !Membership_Plugin::current_member()->can_view_current_page() ) {
			membership_debug_log( __( 'Current member can not view current page.', 'membership' ) );
			membership_redirect_to_protected();
			exit;
		}

		membership_debug_log( __( 'Current member can view current page.', 'membership' ) );
	}

	/**
	 * Initializes initial protection.
	 *
	 * @since 3.5
	 * @action parse_request
	 *
	 * @access public
	 * @global Membership_Model_Member $member Current member
	 * @global array $M_options The plugin settings.
	 * @staticvar boolean $initialised Determines whether or not protection has been initialized.
	 * @param WP $wp Instance of WP class.
	 */
	public function initialise_protection( WP $wp ) {
		global $member, $M_options, $membershippublic;
		static $initialised = false;
		$member = Membership_Plugin::current_member();

		if ( $initialised ) {
			// ensure that this is only called once, so return if we've been here already.
			return;
		}

		// Set up some common defaults
		$factory = Membership_Plugin::factory();

		if ( !empty( $wp->query_vars['feed'] ) ) {
			// This is a feed access, then set the feed rules
			$user_id = (int)$membershippublic->find_user_from_key( filter_input( INPUT_GET, 'k' ) );
			if ( $user_id > 0 ) {
				// Logged in - check there settings, if they have any.
				$member = $factory->get_member( $user_id );
				// Load the levels for this member - and associated rules
				$member->load_levels( true );
			}

			if ( !$member ) {
				// not passing a key so limit based on stranger settings
				// need to grab the stranger settings
				$member = $factory->get_member( get_current_user_id() );
				if ( isset( $M_options['strangerlevel'] ) && $M_options['strangerlevel'] != 0 ) {
					$member->assign_level( $M_options['strangerlevel'], true );
				} else {
					// This user can't access anything on the site - show a blank feed.
					$this->_add_filter( 'the_posts', 'show_noaccess_feed', 1 );
				}
			}
		} else {
			$member = Membership_Plugin::current_member();
			if ( !$member->has_cap( Membership_Model_Member::CAP_MEMBERSHIP_ADMIN ) && !$member->has_cap('manage_options') && !is_super_admin($member->ID) && !$member->has_levels() ) {
				// This user can't access anything on the site - .
				add_filter( 'comments_open', '__return_false', PHP_INT_MAX );
				// Changed for this version to see if it helps to get around changed in WP 3.5
				$this->_add_action( 'the_posts', 'show_noaccess_page', 1 );
				// Hide all pages from menus - except the signup one
				$this->_add_filter( 'get_pages', 'remove_pages_menu' );
				// Hide all categories from lists
				$this->_add_filter( 'get_terms', 'remove_categories', 1 );
			}
		}

		do_action( 'membership-add-shortcodes' );

		// Set the initialisation status
		$initialised = true;
	}

	/**
	 * Protects feed from non authorized access.
	 *
	 * @since 3.5
	 * @filter the_posts
	 *
	 * @access public
	 * @global array $M_options The plugin options.
	 * @return array Array which contains only one post with protected content message.
	 */
	public function show_noaccess_feed() {
		global $M_options;

		//$wp_query->query_vars['post__in'] = array(0);
		/**
		 * What we are going to do here, is create a fake post.  A post
		 * that doesn't actually exist. We're gonna fill it up with
		 * whatever values you want.  The content of the post will be
		 * the output from your plugin.  The questions and answers.
		 */
		if ( !empty( $M_options['nocontent_page'] ) ) {
			// grab the content form the no content page
			$post = get_post( $M_options['nocontent_page'] );
		} else {
			if ( empty( $M_options['protectedmessagetitle'] ) ) {
				$M_options['protectedmessagetitle'] = __( 'No access to this content', 'membership' );
			}

			$post = new stdClass;
			$post->post_author = 1;
			$post->post_name = 'membershipnoaccess';
			add_filter( 'the_permalink', create_function( '$permalink', 'return "' . get_option( 'home' ) . '";' ) );
			$post->guid = get_bloginfo( 'wpurl' );
			$post->post_title = esc_html( stripslashes( $M_options['protectedmessagetitle'] ) );
			$post->post_content = stripslashes( $M_options['protectedmessage'] );
			$post->ID = -1;
			$post->post_status = 'publish';
			$post->post_type = 'post';
			$post->comment_status = 'closed';
			$post->ping_status = 'open';
			$post->comment_count = 0;
			$post->post_date = current_time( 'mysql' );
			$post->post_date_gmt = current_time( 'mysql', 1 );
		}

		return array( $post );
	}

	/**
	 * Removes categories from the terms list for not authorized access.
	 *
	 * @since 3.5
	 * @filter get_terms 1
	 *
	 * @access public
	 * @param array $terms The income array of terms.
	 * @return array The filtered array of terms.
	 */
	public function remove_categories( $terms ) {
		foreach ( (array)$terms as $key => $term ) {
			if ( $term->taxonomy == 'category' ) {
				unset( $terms[$key] );
			}
		}

		return $terms;
	}

	/**
	 * Removes pages from menu for not authorized access.
	 *
	 * @since 3.5
	 * @filter get_pages
	 *
	 * @access public
	 * @global array $M_options The plguins options.
	 * @param array $pages The income array of pages.
	 * @return array The fitlered array of pages.
	 */
	public function remove_pages_menu( $pages ) {
		global $M_options;

		foreach ( (array)$pages as $key => $page ) {
			if ( empty( $M_options['registration_page'] ) || $page->ID != $M_options['registration_page'] ) {
				unset( $pages[$key] );
			}
		}

		return $pages;
	}

	/**
	 * Redirects to protection page if need be.
	 *
	 * @since 3.5
	 * @action the_posts 1
	 *
	 * @access public
	 * @global array $M_options The plguins options.
	 * @param array $posts The array of posts.
	 * @return array The array of posts.
	 */
	public function show_noaccess_page( $posts ) {
		global $M_options;

		if ( empty( $posts ) ) {
			// We don't have any posts, so we should just redirect to the no content page.
			if ( !empty( $M_options['nocontent_page'] ) && !headers_sent() ) {
				// grab the content form the no content page
				wp_safe_redirect( get_permalink( absint( $M_options['nocontent_page'] ) ) );
				exit;
			} else {
				return $posts;
			}
		}

		if ( count( $posts ) == 1 && isset( $posts[0]->post_type ) && $posts[0]->post_type == 'page' ) {
			// We are on a page so get the first page and then check for ones we want to allow
			$page = $posts[0];
			if ( membership_is_special_page( $page->ID, false ) ) {
				return $posts;
			}

			// We are still here so we may be at a page that we shouldn't be able to see
			if ( !empty( $M_options['nocontent_page'] ) && $page->ID != $M_options['nocontent_page'] && !headers_sent() ) {
				// grab the content form the no content page
				wp_safe_redirect( get_permalink( absint( $M_options['nocontent_page'] ) ) );
				exit;
			}

			return $posts;
		} else {
			// We could be on a posts page / or on a single post.
			if ( count( $posts ) == 1 ) {
				// We could be on a single posts page, or only have the one post to view
				if ( isset( $posts[0]->post_type ) && $posts[0]->post_type != 'nav_menu_item' ) {
					// We'll redirect if this isn't a navigation menu item
					$post = $posts[0];

					if ( !empty( $M_options['nocontent_page'] ) && isset( $post->ID ) && $post->ID != $M_options['nocontent_page'] && !headers_sent() ) {
						// grab the content form the no content page
						wp_safe_redirect( get_permalink( absint( $M_options['nocontent_page'] ) ) );
						exit;
					}

					return $posts;
				}
			} else {
				// Check the first post in the list
				if ( isset( $posts[0]->post_type ) && $posts[0]->post_type != 'nav_menu_item' ) {
					// We'll redirect if this isn't a navigation menu item
					$post = $posts[0];

					if ( !empty( $M_options['nocontent_page'] ) && isset( $post->ID ) && $post->ID != $M_options['nocontent_page'] && !headers_sent() ) {
						// grab the content form the no content page
						wp_safe_redirect( get_permalink( absint( $M_options['nocontent_page'] ) ) );
						exit;
					}

					return $posts;
				}
			}
		}

		// If we've reached here then something weird has happened :/
		return $posts;
	}

}