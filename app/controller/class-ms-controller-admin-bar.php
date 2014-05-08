<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

class MS_Controller_Admin_Bar extends MS_Controller {
	
	const MS_SIMULATE_COOKIE = 'ms_simulate';
	
	const MS_PERIOD_COOKIE = 'ms_simulate_period';
	
	const MS_DATE_COOKIE = 'ms_simulate_date';
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $views;
	
	private $simulate_period_unit = 0;
	
	private $simulate_period_type = MS_Helper_Period::PERIOD_TYPE_DAYS;
	
	private $simulate_date;
		
	public function __construct() {
		/** trying to use normal GET instead of ajax due to some users experimenting issues during ajax requests */
		if ( defined('DOING_AJAX') && DOING_AJAX ) {
// 			$this->add_action( 'wp_ajax_ms_simulate', 'simulate_membership' );
// 			$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts');
		}
		else {
			$this->add_action( 'add_admin_bar_menus', 'add_admin_bar_menus' );
			$this->add_action( 'admin_enqueue_scripts', 'enqueue_scripts');
			$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts');
		}
	}

	/**
	 * Sets what menu to add to the admin bar.
	 *
	 * @since 4.0
	 * @action add_admin_bar_menus
	 *
	 * @access public
	 */
	public function add_admin_bar_menus() {
		 
		if( MS_Model_Member::is_admin_user() ) {
			$method = MS_Plugin::is_enabled()
				? 'add_view_site_as_menu'
				: 'add_activate_plugin_menu';
			
			$this->add_action( 'admin_bar_menu', $method );
		}

		/** trying to use normal GET instead of ajax due to some users experimenting issues during ajax requests */
		if( ! empty( $_GET['action'] ) && 'ms_simulate' == $_GET['action'] && isset( $_GET['membership_id'] ) ) {
				$membership_id = (int) $_GET['membership_id'];
				check_admin_referer( 'ms_simulate-' . $membership_id );
				@setcookie( self::MS_SIMULATE_COOKIE , $membership_id, 0, COOKIEPATH, COOKIE_DOMAIN );
				if( empty( $membership_id ) ) {
					@setcookie( self::MS_PERIOD_COOKIE , '', 0, COOKIEPATH, COOKIE_DOMAIN );
					@setcookie( self::MS_DATE_COOKIE , '', 0, COOKIEPATH, COOKIE_DOMAIN );
				}
				wp_safe_redirect( wp_get_referer() );
		}
		if( ! empty( $_POST['simulate_submit'] ) ) {
			if( isset( $_POST['simulate_period_unit'] ) && in_array( $_POST['simulate_period_type'], MS_Helper_Period::get_periods() ) ) {
				$period = $_POST['simulate_period_unit'] .';'. $_POST['simulate_period_type'];
				$this->simulate_period_unit =  $_POST['simulate_period_unit'];
				$this->simulate_period_type =  $_POST['simulate_period_type'];
				@setcookie( self::MS_PERIOD_COOKIE , $period, 0, COOKIEPATH, COOKIE_DOMAIN );
				@setcookie( self::MS_DATE_COOKIE , '', 0, COOKIEPATH, COOKIE_DOMAIN );
			}
			elseif( ! empty( $_POST['simulate_date'] ) ) {
				@setcookie( self::MS_DATE_COOKIE , $_POST['simulate_date'], 0, COOKIEPATH, COOKIE_DOMAIN );
				@setcookie( self::MS_PERIOD_COOKIE , '', 0, COOKIEPATH, COOKIE_DOMAIN );
			}
			wp_safe_redirect( wp_get_referer() );
		}
		elseif( ! empty( $_COOKIE[ self::MS_PERIOD_COOKIE ] ) ) {
			$period = explode( ';', $_COOKIE[ self::MS_PERIOD_COOKIE ] );
			if( count( $period ) == 2 ) {
				$this->simulate_period_unit =  $period[0];
				$this->simulate_period_type =  $period[1];
			}
		}
		elseif( ! empty( $_COOKIE[ self::MS_DATE_COOKIE ] ) ) {
			$this->simulate_date = $_COOKIE[ self::MS_DATE_COOKIE ];
		} 
	}
	
	/**
	 * Adds "Enable Protection" menu to admin bar.
	 *
	 * @since 4.0
	 * @action admin_bar_menu
	 *
	 * @access public
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_activate_plugin_menu( WP_Admin_Bar $wp_admin_bar ) {
		$linkurl = 'admin.php?page=membership-settings&setting=plugin_enabled&action=toggle_activation';
		$linkurl = wp_nonce_url( $linkurl, 'toggle_activation' );
	
		$wp_admin_bar->add_menu( array(
				'id'     => 'membership',
				'parent' => 'top-secondary',
				'title'  => __( 'Membership', MS_TEXT_DOMAIN ) . ' : <span class="ms-admin-bar-disabled">' . __( 'Disabled', MS_TEXT_DOMAIN ) . "</span>",
				'href'   => $linkurl,
				'meta'   => array(
						'title' => __( 'Click to Enable the Membership protection', MS_TEXT_DOMAIN ),
				),
		) );
	
		$wp_admin_bar->add_menu( array(
				'parent' => 'membership',
				'id'     => 'membershipenable',
				'title'  => __( 'Enable Membership', MS_TEXT_DOMAIN ),
				'href'   => $linkurl,
		) );
	}
	
	/**
	 * Adds "View Site As" menu to admin bar.
	 *
	 * @since 4.0
	 * @action admin_bar_menu
	 *
	 * @access public
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_view_site_as_menu( WP_Admin_Bar $wp_admin_bar ) {

		//TODO if global tables
		$admin_url_func = 0
		? 'network_admin_url'
				: 'admin_url';
		
		$memberships = MS_Model_Membership::get_memberships();
		if ( ! empty( $_COOKIE[ self::MS_SIMULATE_COOKIE ] ) && ( $membership_id = absint( $_COOKIE[ self::MS_SIMULATE_COOKIE ] ) ) ) {
			$reset_simulation = (object) array(
					'id' => 0,
					'name' => __( 'Reset', MS_TEXT_DOMAIN ),
			);
			$memberships[] = $reset_simulation;
			
			$membership = MS_Model_Membership::load( $membership_id );

			$title = null;
			if( MS_Model_Membership::MEMBERSHIP_TYPE_FINITE == $membership->membership_type || $membership->has_dripped_content() ) {
				$view = apply_filters( 'membership_view_admin_bar', new MS_View_Admin_Bar() );
				$view->simulate_period_unit = $this->simulate_period_unit;
				$view->simulate_period_type = $this->simulate_period_type;
				$title = $view->to_html();
			}
			elseif( MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE == $membership->membership_type ) {
				$view = apply_filters( 'membership_view_admin_bar', new MS_View_Admin_Bar() );
				$view->simulate_date = ( $this->simulate_date ) ? $this->simulate_date : '';
				$title = $view->to_html();
			}
			if( $title ) {
				$wp_admin_bar->add_menu( 
						array(
							'id'     => 'membership-simulate-period',
							'parent' => 'top-secondary',
							'title'  => $title,
							'href'   => '',
							'meta'   => array(
									'class' => apply_filters( 'membership_controller_admin_bar_simulate_period_class', 'membership-view-site-as' ),
									'title' => __( 'Simulate period', MS_TEXT_DOMAIN ),
							),
						) 
				);
			}
		}
		
		$title = __( 'View site as: ', MS_TEXT_DOMAIN );
		if ( empty( $_COOKIE[ self::MS_SIMULATE_COOKIE ] ) || '0' == $_COOKIE[ self::MS_SIMULATE_COOKIE ] ) {
			$title .= __( 'Membership Admin', MS_TEXT_DOMAIN );
		} else {
			$membership_id = (int)$_COOKIE[ self::MS_SIMULATE_COOKIE ];
			$membership = MS_Model_Membership::load( $membership_id );
			$title .= $membership->name;
		}
		
		$wp_admin_bar->add_menu( array(
				'id'     => 'membership-simulate',
				'parent' => 'top-secondary',
				'title'  => $title,
				'href'   => '',
				'meta'   => array(
						'class' => apply_filters( 'membership_controller_admin_bar_view_site_as_class', 'membership-view-site-as' ),
						'title' => __( 'Select a membership to view your site as', MS_TEXT_DOMAIN ),
				),
		) );
		if ( !empty( $memberships ) ) {
			foreach ( $memberships as $membership ) {
				$link_url = wp_nonce_url( 
						$admin_url_func( 
								"?action=ms_simulate&membership_id={$membership->id}", 
								/** trying to use normal GET instead of ajax due to some users experimenting issues during ajax requests */
// 								"admin-ajax.php?action=ms_simulate&membership_id={$membership->id}", 
								( is_ssl() ? 'https' : 'http' ) 
						),
				 		"ms_simulate-{$membership->id}"
				);
				$wp_admin_bar->add_menu( 
						array(
							'parent' => 'membership-simulate',
							'id' => 'membership-' . $membership->id,
							'title' => $membership->name,
							'href' => $link_url
						) 
				);
			}
		}
	}
	
	/**
	 * Switches membership protection to view site as.
	 *
	 * @since 4.0
	 * @action wp_ajax_ms_simulate
	 *
	 * @access public
	 */
	public function simulate_membership() {
		if ( isset( $_GET['membership_id'] ) ) {
			$membership_id = (int) $_GET['membership_id'];
				
			check_ajax_referer( 'ms_simulate-' . $membership_id );
			@setcookie( self::MS_SIMULATE_COOKIE , $membership_id, 0, COOKIEPATH, COOKIE_DOMAIN );
			if( empty( $membership_id ) ) {
				@setcookie( self::MS_PERIOD_COOKIE , '', 0, COOKIEPATH, COOKIE_DOMAIN );
				@setcookie( self::MS_DATE_COOKIE , '', 0, COOKIEPATH, COOKIE_DOMAIN );
			}
		}
	
		exit;
	}
	
	/**
	 * Enqueues necessary scripts.
	 *
	 * @since 4.0
	 * @action wp_enqueue_scripts, admin_enqueue_scripts
	 */
	function enqueue_scripts() {
		wp_register_script( 'ms-controller-admin-bar', MS_Plugin::instance()->url. 'app/assets/js/ms-controller-admin-bar.js', array( 'jquery' ), MS_Plugin::instance()->version );
		wp_localize_script( 'ms-controller-admin-bar', 'ms', array( 'switching_text' => __( 'Switching...', MS_TEXT_DOMAIN ) ) );
		wp_enqueue_script( 'ms-controller-admin-bar' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'ms-admin-bar', MS_Plugin::instance()->url. 'app/assets/css/ms-admin-bar.css', null, MS_Plugin::instance()->version );
		wp_enqueue_style( 'jquery-ui' );
	}
}