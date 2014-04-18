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

/**
 * Primary controller for Membership Plugin.
 *
 * Responsible for flow control, navigation and invoking other controllers.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Controller_Plugin extends MS_Controller {

	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var model
	 */
	private $model;
	
	/**
	 * Instance of MS_View_Plugin.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var view
	 */
	private $view;	

	/**
	 * Pointer array for other controllers.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var controllers
	 */	
	private $controllers = array();
	
	/**
	 * Pointer array for all Admin pages.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var admin_pages
	 */
	private $admin_pages = array();	
		
	/** 
	 * Constructs the primary Plugin controller.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		/** Instantiate Plugin model */
		$this->model = apply_filters( 'membership_plugin_model', new MS_Model_Plugin() );
		/** Instantiate Plugin view */
		$this->view = apply_filters( 'membership_plugin_view', new MS_View_Plugin( array( 'test'=>'two' )) );

		/** Setup plugin admin UI */
		$this->add_action( 'admin_menu', 'add_menu_pages' );
		
		/** Enque admin styles (CSS) */
		$this->add_action( 'admin_enqueue_scripts', 'register_plugin_admin_styles' );
		
		/** Enque admin scripts (JS) */
		$this->add_action( 'admin_enqueue_scripts', 'register_plugin_admin_scripts' );
				
		//FJ: it is breaking add_menu_pages, commented for now.
		/** ONLY load controllers when we are going to need them. */
// 		if( ! empty( $_GET['page'] ) ) {
// 			switch( $_GET['page'] ) {
				
// 				/** Membership controller */
// 				case 'all-memberships':
// 				case 'membership-edit':
// 					$this->controllers['membership'] = apply_filters( 'membership_membership_controller', new MS_Controller_Membership() );		
// 					break;
			
// 				/** Dashboard controller */
// 				case 'membership-dashboard':
// 					$this->controllers['dashboard'] = apply_filters( 'membership_dashboard_controller', new MS_Controller_Dashboard() );		
// 					break;
	
// 				/** Member controller */
// 				case 'membership-members':
// 					$this->controllers['member'] = apply_filters( 'membership_member_controller', new MS_Controller_Member() );				
// 					break;
			
// 				/** Billing controller */
// 				case 'membership-billing':
// 					$this->controllers['billing'] = apply_filters( 'membership_billing_controller', new MS_Controller_Billing() );				
// 					break;
	
// 				/** Coupon controller */
// 				case 'membership-coupons':
// 					$this->controllers['coupon'] = apply_filters( 'membership_coupon_controller', new MS_Controller_Coupon() );				
// 					break;
	
// 				/** Add-on controller */
// 				case 'membership-addons':
// 					$this->controllers['addon'] = apply_filters( 'membership_addon_controller', new MS_Controller_Addon() );				
// 					break;
			
// 			} /** End switch( $_GET['page'] ) */
// 		}
		
		/** Membership controller */
		$this->controllers['membership'] = apply_filters( 'membership_membership_controller', new MS_Controller_Membership() );
		
		/** Dashboard controller */
		$this->controllers['dashboard'] = apply_filters( 'membership_dashboard_controller', new MS_Controller_Dashboard() );
		
		/** Member controller */
		$this->controllers['member'] = apply_filters( 'membership_member_controller', new MS_Controller_Member() );
		
		/** Billing controller */
		$this->controllers['billing'] = apply_filters( 'membership_billing_controller', new MS_Controller_Billing() );
		
		/** Coupon controller */
		$this->controllers['coupon'] = apply_filters( 'membership_coupon_controller', new MS_Controller_Coupon() );
		
		/** Add-on controller */
		$this->controllers['addon'] = apply_filters( 'membership_addon_controller', new MS_Controller_Addon() );
		
		/** Settings controller */
		$this->controllers['settings'] = apply_filters( 'membership_settings_controller', new MS_Controller_Settings() );
		
		
		
	}

	
	/**
	 * Adds Dashboard navigation menus.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */
	public function add_menu_pages() {
		$pages = array();

		/** Create primary menu item: Membership */
		$pages[] = add_menu_page( __( 'Membership', MS_TEXT_DOMAIN ), __( 'Membership', MS_TEXT_DOMAIN ), 'membershipadmindashboard', 'membership');

		/** Create Membership Dashboard */
		$pages[] = add_submenu_page( 'membership', __( 'Dashboard', MS_TEXT_DOMAIN ), __( 'Dashboard', MS_TEXT_DOMAIN ), 'manage_options', 'membership-dashboard', array( $this->controllers['dashboard'], 'admin_dashboard' ) );
		
		//RK: Perhaps as addon? Core will only have 1 or 2 memberships.
		/** Lists all memberships. */
		$pages[] = add_submenu_page( 'membership', __( 'Memberships', MS_TEXT_DOMAIN ), __( 'Memberships', MS_TEXT_DOMAIN ), 'manage_options', 'all-memberships', array( $this->controllers['membership'], 'admin_membership_list' ) );
		
		/** Manage membership */
		$pages[] = add_submenu_page( 'all-memberships', __( 'New Membership', MS_TEXT_DOMAIN ), __( 'New Membership', MS_TEXT_DOMAIN ), 'manage_options', 'membership-edit', array( $this->controllers['membership'], 'membership_edit' ) );

		/** Create Members Page */
		$pages[] = add_submenu_page( 'membership', __( 'Members', MS_TEXT_DOMAIN ), __( 'Members', MS_TEXT_DOMAIN ), 'manage_options', 'membership-members', array( $this->controllers['member'], 'admin_members' ) );

		/** Create Billings Page */
		$pages[] = add_submenu_page( 'membership', __( 'Billing', MS_TEXT_DOMAIN ), __( 'Billing', MS_TEXT_DOMAIN ), 'manage_options', 'membership-billing', array( $this->controllers['billing'], 'admin_billing' ) );

		/** Create Coupons Page */
		$pages[] = add_submenu_page( 'membership', __( 'Coupons', MS_TEXT_DOMAIN ), __( 'Coupons', MS_TEXT_DOMAIN ), 'manage_options', 'membership-coupons', array( $this->controllers['coupon'], 'admin_coupon' ) );
		
		/** Filter to hook in other addon pages. */
		$pages = apply_filters( 'membership_submenu_pages', $pages );

		/** Create Add-ons Page */
		$pages[] = add_submenu_page( 'membership', __( 'Add-ons', MS_TEXT_DOMAIN ), __( 'Add-ons', MS_TEXT_DOMAIN ), 'manage_options', 'membership-addons', array( $this->controllers['addon'], 'admin_addon' ) );

		/** Manage membership rules */
		// $pages[] = add_submenu_page( 'all-memberships', __( 'Edit Membership Rules', MS_TEXT_DOMAIN ), __( 'Edit Membership Rules', MS_TEXT_DOMAIN ), 'manage_options', 'membership-edit-rules', array( $this->controllers['rule'], 'membership_edit_rules' ) );
		
		//FJ: think it might be better to create a MS_Controller_Settings and MS_Model_Settings (see class diagram). 
		//	My understanding of this MS_Controller_PLugin is something like Front Controller pattern
		/** Global Membership Plugin settings. */
		// $pages[] = add_submenu_page( 'membership', __( 'Members', MS_TEXT_DOMAIN ), __( 'Membership Settings', MS_TEXT_DOMAIN ), 'manage_options', 'membership-settings', array( $this->view, 'render' ) );
		
		//RK: Can make it happen... extracting Settings from Plugin
		$pages[] = add_submenu_page( 'membership', __( 'Settings', MS_TEXT_DOMAIN ), __( 'Settings', MS_TEXT_DOMAIN ), 'manage_options', 'membership-settings', array( $this->controllers['settings'], 'admin_settings' ) );
		
		
	}

	/**
	 * Adds CSS for Membership settings pages.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function register_plugin_admin_styles() {
		wp_register_style( 'membership_admin_css', MS_Plugin::instance()->url. 'app/assets/css/settings.css' );
		wp_enqueue_style( 'membership_admin_css' );
	}
	
	/**
	 * Adds JavasSript for Membership settings pages.
	 *
	 * @todo Perhaps remove this method if we don't have global JS to apply to plugin
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function register_plugin_admin_scripts() {
		// wp_register_script( 'membership_admin_js', MS_Plugin::instance()->url . 'app/assets/js/settings.js' );
		// wp_enqueue_script( 'membership_admin_js' );
	}
	
	
}