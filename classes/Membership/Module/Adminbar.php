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
 * The module responsible for admin bar menu rendering and processing.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_Adminbar extends Membership_Module {

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

		$this->_add_action( 'add_admin_bar_menus', 'add_admin_bar_items' );
		$this->_add_action( 'membership_dashboard_membershipuselevel', 'switch_membership_level' );
	}

	/**
	 * Sets what menu to add to the admin bar.
	 *
	 * @since 3.5
	 * @action add_admin_bar_menus
	 *
	 * @access public
	 */
	public function add_admin_bar_items() {
		if ( !is_user_logged_in() ) {
			return;
		}

		if ( wp_get_current_user()->has_cap( 'membershipadmin' ) ) {
			$method = Membership_Plugin::is_enabled()
				? 'add_view_site_as_menu'
				: 'add_enabled_protection_menu';

			add_action( 'admin_bar_menu', array( $this, $method ), 8 );
		}
	}

	/**
	 * Adds "View Site As" menu to admin bar.
	 *
	 * @since 3.5
	 * @action admin_bar_menu
	 *
	 * @access public
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_view_site_as_menu( WP_Admin_Bar $wp_admin_bar ) {
		global $wpdb;

		$admin_url_func = Membership_Plugin::is_global_tables()
			? 'network_admin_url'
			: 'admin_url';

		$title = __( 'View site as: ', 'membership' );
		if ( empty( $_COOKIE['membershipuselevel'] ) || $_COOKIE['membershipuselevel'] == '0' ) {
			$title .= __( 'Membership Admin', 'membership' );
		} else {
			$level_id = (int)$_COOKIE['membershipuselevel'];
			$level = Membership_Plugin::factory()->get_level( $level_id );
			$title .= $level->level_title();
		}

		$wp_admin_bar->add_menu( array(
			'id'     => 'membershipuselevel',
			'parent' => 'top-secondary',
			'title'  => $title,
			'href'   => '',
			'meta'   => array(
				'class' => apply_filters( 'membership_adminbar_view_site_as_class', 'membership-view-site-as' ),
				'title' => __( 'Select a level to view your site as', 'membership' ),
			),
		) );

		$levels = $wpdb->get_results( sprintf( 'SELECT * FROM %s WHERE level_active = 1 ORDER BY id ASC', MEMBERSHIP_TABLE_LEVELS ) );
		if ( !empty( $levels ) ) {
			foreach ( $levels as $level ) {
				$linkurl = wp_nonce_url( $admin_url_func( "admin.php?page=membership&action=membershipuselevel&level_id=" . $level->id ), 'membershipuselevel-' . $level->id );
				$wp_admin_bar->add_menu( array(
					'parent' => 'membershipuselevel',
					'id' => 'membershipuselevel-' . $level->id,
					'title' => $level->level_title,
					'href' => $linkurl
				) );
			}
		}

		if ( !empty( $_COOKIE['membershipuselevel'] ) && $_COOKIE['membershipuselevel'] != '0' ) {
			$linkurl = wp_nonce_url( $admin_url_func( "admin.php?page=membership&action=membershipuselevel&level_id=0" ), 'membershipuselevel-0' );
			$wp_admin_bar->add_menu( array(
				'parent' => 'membershipuselevel',
				'id'     => 'membershipuselevel-0',
				'title'  => __( 'Reset', 'membership' ),
				'href'   => $linkurl
			) );
		}
	}

	/**
	 * Adds "Enable Protection" menu to admin bar.
	 *
	 * @since 3.5
	 * @action admin_bar_menu
	 *
	 * @access public
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_enabled_protection_menu( WP_Admin_Bar $wp_admin_bar ) {
		$linkurl = "admin.php?page=membership&action=activate";
		$linkurl = Membership_Plugin::is_global_tables() ? network_admin_url( $linkurl ) : admin_url( $linkurl );
		$linkurl = wp_nonce_url( $linkurl, 'toggle-plugin' );

		$wp_admin_bar->add_menu( array(
			'id'     => 'membership',
			'parent' => 'top-secondary',
			'title'  => __( 'Membership', 'membership' ) . ' : <span style="color:red;text-shadow:none">' . __( 'Disabled', 'membership' ) . "</span>",
			'href'   => $linkurl,
			'meta'   => array(
				'title' => __( 'Click to Enable the Membership protection', 'membership' ),
			),
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'membership',
			'id'     => 'membershipenable',
			'title'  => __( 'Enable Membership', 'membership' ),
			'href'   => $linkurl,
		) );
	}

	/**
	 * Switches membership protection level to view site as.
	 *
	 * @since 3.5
	 * @action membership_dashboard_membershipuselevel
	 *
	 * @access public
	 */
	public function switch_membership_level() {
		if ( isset( $_GET['level_id'] ) ) {
			$level_id = (int) $_GET['level_id'];
			check_admin_referer( 'membershipuselevel-' . $level_id );

			@setcookie( 'membershipuselevel', $level_id, 0, COOKIEPATH, COOKIE_DOMAIN );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

}