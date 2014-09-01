<?php
/**
 * This file defines the MS_Controller_Plugin class.
 *
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
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Plugin extends MS_Controller {
	
	const MENU_SLUG = 'protected-content';
	
	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */
	private $model;
	
	/**
	 * Pointer array for other controllers.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $controllers
	 */	
	protected $controllers = array();
	
	/**
	 * Pointer array for all Admin pages.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $admin_pages
	 */
	private $admin_pages = array();	
		
	/** 
	 * Constructs the primary Plugin controller.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		/** Instantiate Plugin model - protection implementation */
		$this->model = apply_filters( 'ms_model_plugin', new MS_Model_Plugin() );
		
		/** Rewrite rules */
		$this->add_action( 'generate_rewrite_rules', 'add_rewrites', 1 );
		$this->add_filter( 'query_vars', 'add_query_vars' );
						
		/** Setup plugin admin UI */
		$this->add_action( 'admin_menu', 'add_menu_pages' );
		
		/** Register admin styles (CSS) */
		$this->add_action( 'admin_enqueue_scripts', 'register_plugin_admin_styles' );
		
		/** Register styles used in the front end (CSS) */
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_styles');
		
		/** Enque admin scripts (JS) */
		$this->add_action( 'admin_enqueue_scripts', 'register_plugin_admin_scripts' );
				
		/** Register scripts used in the front end (JS) */
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_scripts');
		
		/** Membership controller */
		$this->controllers['membership'] = apply_filters( 'ms_controller_membership', new MS_Controller_Membership() );
		$this->controllers['membership_content_type'] = apply_filters( 'ms_controller_membership_content_type', new MS_Controller_Membership_Content_Type() );
		$this->controllers['membership_dripped'] = apply_filters( 'ms_controller_membership_dripped', new MS_Controller_Membership_Dripped() );
		$this->controllers['membership_tier'] = apply_filters( 'ms_controller_membership_tier', new MS_Controller_Membership_Tier() );
		
		/** Rule controller */
		$this->controllers['rule'] = apply_filters( 'ms_controller_rule', new MS_Controller_Rule() );
		
		/** Dashboard controller */
		$this->controllers['dashboard'] = apply_filters( 'ms_controller_dashboard', new MS_Controller_Dashboard() );
		
		/** Member controller */
		$this->controllers['member'] = apply_filters( 'ms_controller_member', new MS_Controller_Member() );
		
		/** Billing controller */
		$this->controllers['billing'] = apply_filters( 'ms_controller_billing', new MS_Controller_Billing() );
		
		/** Coupon controller */
		$this->controllers['coupon'] = apply_filters( 'ms_controller_coupon', new MS_Controller_Coupon() );
		
		/** Add-on controller */
		$this->controllers['addon'] = apply_filters( 'ms_controller_addon', new MS_Controller_Addon() );
		
		/** Settings controller */
		$this->controllers['settings'] = apply_filters( 'ms_controller_settings', new MS_Controller_Settings() );
		
		/** Gateway controller */
		$this->controllers['gateway'] = apply_filters( 'ms_controller_gateway', new MS_Controller_Gateway() );
		
		/** Admin bar controller */
		$this->controllers['admin_bar'] = apply_filters( 'ms_controller_admin_bar', new MS_Controller_Admin_Bar() );
		
		/** Membership metabox controller */
		$this->controllers['membership_metabox'] = apply_filters( 'ms_controller_membership_metabox', new MS_Controller_Membership_Metabox() );
		
		/** Membership shortcode controller - front end */
		$this->controllers['membership_shortcode'] = apply_filters( 'ms_controller_shortcode', new MS_Controller_Shortcode() );

		/** Membership registration controller - front end */
		$this->controllers['frontend'] = apply_filters( 'ms_controller_frontend', new MS_Controller_Frontend() );
		
		$this->add_filter( 'single_template', 'custom_template' );
		flush_rewrite_rules(); //TODO No need to execute every time.
	}

	/**
	 * Rewrite rules for gateway payment return url.
	 * 
	 * @since 4.0.0
	 *
	 * @param object $wp_rewrite WP_Rewrite object.
	 * @return object WP_Rewrite object.
	 */
	public function add_rewrites( $wp_rewrite ) {
		
		$new_rules = array();
		
		/** Media / download rewrite rules */
		if( ! empty( MS_Plugin::instance()->settings->downloads['masked_url'] ) ) {
			$new_rules[trailingslashit( MS_Plugin::instance()->settings->downloads['masked_url'] ) . '(.*)'] = 'index.php?protectedfile=' . $wp_rewrite->preg_index( 1 );
		}
		
		/** Gateway rewrite rules */
		$new_rules['ms-payment-return/(.+)'] = 'index.php?paymentgateway=' . $wp_rewrite->preg_index( 1 );
		
		$new_rules = apply_filters('ms_rewrite_rules', $new_rules);
		
		$wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules);

		return $wp_rewrite;
	}

	/**
	 * Add custom query vars.
	 * 
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $vars
	 * @return mixed[]
	 */
	function add_query_vars( $vars ) {
		
		/** Media / download */
		if ( ! in_array( 'protectedfile', $vars ) ) {
			$vars[] = 'protectedfile';
		}
		
		/** Gateway */
		if ( ! in_array( 'paymentgateway', $vars ) ) {
			$vars[] = 'paymentgateway';
		}
		
		return $vars;
	}
	
	/**
	 * Adds Dashboard navigation menus.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */
	public function add_menu_pages() {
		
		/** Create primary menu item: Membership */
		add_menu_page( 
				__( 'Protected Content', MS_TEXT_DOMAIN ), 
				__( 'Protected Content', MS_TEXT_DOMAIN ), 
				$this->capability, 
				self::MENU_SLUG,
				null, 
				MS_Plugin::instance()->url . 'app/assets/images/members.png' 
		);
		
		/** Submenus definition */
		$pages = array(
				'memberships' => array( 
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Memberships', MS_TEXT_DOMAIN ), 
						'menu_title' => __( 'Memberships', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG,
						'function' => array( $this->controllers['membership'], 'membership_admin_page_router' ),
				),
				'members' => array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Members', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Members', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-members',
						'function' => array( $this->controllers['member'], 'admin_member_list' ),
				),
				'protected-content' => array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Protected Content', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-setup',
						'function' => array( $this->controllers['membership'], 'page_setup_protected_content' ),
				),
				'billing' => array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Billing', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Billing', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-billing',
						'function' => array( $this->controllers['billing'], 'admin_billing' ),
				),
				'coupons' => array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Coupons', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Coupons', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-coupons',
						'function' => array( $this->controllers['coupon'], 'admin_coupon' ),
				),
				'addon' => array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Add-ons', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Add-ons', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-addon',
						'function' => array( $this->controllers['addon'], 'admin_addon' ),
				),
				'settings' => array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Settings', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Settings', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-settings',
						'function' => array( $this->controllers['settings'], 'admin_settings' ),
				),
				
		);

		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_COUPON ) ) {
			unset( $pages['coupons'] );
		}
		
		if( MS_Factory::load( 'MS_Model_Settings' )->initial_setup ) {
			$pages = array(
					'setup' => array(
							'parent_slug' => self::MENU_SLUG,
							'page_title' => __( 'Set-up', MS_TEXT_DOMAIN ),
							'menu_title' => __( 'Set-up', MS_TEXT_DOMAIN ),
							'menu_slug' => self::MENU_SLUG,
							'function' => array( $this->controllers['membership'], 'membership_admin_page_router' ),
					),
			);
			if( MS_Controller_Membership::STEP_CHOOSE_MS_TYPE == MS_Plugin::instance()->settings->wizard_step ) {
				$pages['protected-content'] = array(
						'parent_slug' => self::MENU_SLUG,
						'page_title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
						'menu_title' => __( 'Protected Content', MS_TEXT_DOMAIN ),
						'menu_slug' => self::MENU_SLUG . '-setup',
						'function' => array( $this->controllers['membership'], 'setup_protected_content' ),
				);
			}
		}
		
		$pages = apply_filters( 'ms_plugin_menu_pages', $pages );
		/** Create submenus */
		foreach( $pages as $page ) {
			extract( $page );
			add_submenu_page( $parent_slug, $page_title, $menu_title, $this->capability, $menu_slug, $function );
		}
	}

	public function custom_template( $template ) {
		global $post;
		
		/* Checks for invoice single template */
		if( $post->post_type == MS_Model_Invoice::$POST_TYPE ) {
			$invoice_template = apply_filters( 'ms_controller_plugin_invoice_template', MS_Plugin::instance()->dir . 'app/template/single-invoice.php' );
			if( file_exists( $invoice_template ) ) {
				$template = $invoice_template;
			}
		}

		return $template;
	}
	/**
	 * Adds CSS for Membership settings pages.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function register_plugin_admin_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_style( 'jquery-ui', $plugin_url. 'app/assets/css/jquery-ui-smoothness/jquery-ui-1.10.4.custom.css', $version );

		wp_register_style( 'membership-admin', $plugin_url. 'app/assets/css/settings.css', $version );
		wp_enqueue_style( 'membership-admin' );
		
		wp_register_style( 'membership-tooltip', $plugin_url. 'app/assets/css/ms-tooltip.css', $version );
		wp_enqueue_style( 'membership-tooltip' );
		
		wp_register_style( 'font-awesome', $plugin_url. 'app/assets/css/font-awesome.min.css', $version );
		wp_enqueue_style( 'font-awesome' );
		
		wp_register_style( 'jquery-chosen', $plugin_url. 'app/assets/css/chosen.css', null, $version );
	}
	
	/**
	 * Adds CSS for Membership pages used in the front end.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function enqueue_plugin_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_style( 'jquery-ui', $plugin_url. 'app/assets/css/jquery-ui-smoothness/jquery-ui-1.10.4.custom.css', $version );
		
		wp_register_style( 'membership-admin', $plugin_url. 'app/assets/css/settings.css', $version );
		
		wp_register_style( 'membership-shortcode', $plugin_url. 'app/assets/css/ms-shortcode.css', $version );
		wp_enqueue_style( 'membership-shortcode' );
		
		wp_register_style( 'jquery-chosen', $plugin_url. 'app/assets/css/chosen.css', null, $version );
	}
	
	/**
	 * Register JavasSript for Membership settings pages.
	 *
	 * @since 4.0.0
	 *	
	 * @return void
	 */	
	public function register_plugin_admin_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_script( 'jquery-validate',  $plugin_url. 'app/assets/js/jquery.validate.js', array( 'jquery' ), $version );
		wp_register_script( 'ms-radio-slider', $plugin_url. 'app/assets/js/ms-radio-slider.js', null, $version );

		wp_register_script( 'ms-tooltips', $plugin_url. 'app/assets/js/ms-tooltip.js', array( 'jquery' ), $version );
		wp_enqueue_script( 'ms-tooltips' );
		
		wp_register_script( 'jquery-chosen', $plugin_url. 'app/assets/js/chosen.jquery.js', array( 'jquery' ), $version );
	}

	/**
	 * Adds JavasSript for Membership pages used in the front end.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_script( 'jquery-validate',  $plugin_url. 'app/assets/js/jquery.validate.js', array( 'jquery' ), $version );
		wp_enqueue_script( 'jquery-validate' );
		
		wp_register_script( 'ms-shortcode', $plugin_url. 'app/assets/js/ms-shortcode.js', array( 'jquery-validate' ), $version );
		wp_localize_script( 'ms-shortcode', 'ms_shortcode', array( 'cancel_msg' => __( 'Are you sure you want to cancel?', MS_TEXT_DOMAIN ) ) );
		wp_enqueue_script( 'ms-shortcode' );
		
		wp_register_script( 'ms-view-frontend-profile', $plugin_url. 'app/assets/js/ms-view-frontend-profile.js', array( 'jquery-validate' ), $version );
		
		wp_register_script( 'jquery-chosen', $plugin_url. 'app/assets/js/chosen.jquery.js', array( 'jquery' ), $version );
	}
}