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
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Plugin extends MS_Controller {

	/**
	 * Plugin Menu slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const MENU_SLUG = 'protected-content';

	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var $model
	 */
	private $model;

	/**
	 * Pointer array for other controllers.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var $controllers
	 */
	protected $controllers = array();

	/**
	 * Constructs the primary Plugin controller.
	 *
	 * Created by the MS_Plugin object during the setup_theme action.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// Instantiate Plugin model - protection implementation.
		$this->model = MS_Factory::create( 'MS_Model_Plugin' );

		// Instantiate dialog controller for ajax dialogs.
		$this->dialogs = MS_Factory::create( 'MS_Controller_Dialog' );

		// Register all available styles and scripts. Nothing is enqueued.
		$this->add_action( 'wp_loaded', 'wp_loaded' );

		// Setup plugin admin UI.
		$this->add_action( 'admin_menu', 'add_menu_pages' );

		$this->add_action( 'ms_register_admin_scripts', 'register_admin_scripts' );
		$this->add_action( 'ms_register_admin_scripts', 'register_admin_styles' );
		$this->add_action( 'ms_register_public_scripts', 'register_public_scripts' );
		$this->add_action( 'ms_register_public_scripts', 'register_public_styles' );

		// Initialize core controllers that are available on every page.
		$this->controllers['membership'] = MS_Factory::create( 'MS_Controller_Membership' );
		$this->controllers['rule'] = MS_Factory::create( 'MS_Controller_Rule' );
		$this->controllers['member'] = MS_Factory::create( 'MS_Controller_Member' );
		$this->controllers['billing'] = MS_Factory::create( 'MS_Controller_Billing' );
		$this->controllers['coupon'] = MS_Factory::create( 'MS_Controller_Coupon' );
		$this->controllers['addon'] = MS_Factory::create( 'MS_Controller_Addon' );
		$this->controllers['settings'] = MS_Factory::create( 'MS_Controller_Settings' );
		$this->controllers['page'] = MS_Factory::create( 'MS_Controller_Page' );
		$this->controllers['communication'] = MS_Factory::create( 'MS_Controller_Communication' );
		$this->controllers['gateway'] = MS_Factory::create( 'MS_Controller_Gateway' );
		$this->controllers['admin_bar'] = MS_Factory::create( 'MS_Controller_Admin_Bar' );
		$this->controllers['membership_metabox'] = MS_Factory::create( 'MS_Controller_Membership_Metabox' );
		$this->controllers['membership_shortcode'] = MS_Factory::create( 'MS_Controller_Shortcode' );
		$this->controllers['frontend'] = MS_Factory::create( 'MS_Controller_Frontend' );

		// Changes the current themes "single" template to the invoice form when an invoice is displayed.
		$this->add_filter( 'single_template', 'custom_template' );

		// TODO: Review these hooks; possibly we can enqueue stuff in more specific files

		// Register admin styles (CSS)
		$this->add_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_styles' );

		// Register styles used in the front end (CSS)
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_styles' );

		// Enqueue admin scripts (JS)
		$this->add_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_scripts' );

		// Register scripts used in the front end (JS)
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_scripts' );

		// TODO END ------------------------------------------------------------
	}

	/**
	 * Register scripts and styles
	 *
	 * @since  1.0.0
	 */
	public function wp_loaded() {
		if ( is_admin() ) {
			do_action( 'ms_register_admin_scripts' );
		} else {
			do_action( 'ms_register_public_scripts' );
		}
	}

	/**
	 * Adds Dashboard navigation menus.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_pages() {
		// Create primary menu item: Membership.
		add_menu_page(
			__( 'Protect Content', MS_TEXT_DOMAIN ),
			__( 'Protect Content', MS_TEXT_DOMAIN ),
			$this->capability,
			self::MENU_SLUG,
			null,
			'dashicons-lock'
		);

		if ( MS_Plugin::is_wizard() ) {
			// Submenus definition: Wizard mode
			$pages = array(
				'setup' => array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Set-up', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Set-up', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG,
					'function' => array( $this->controllers['membership'], 'membership_admin_page_router' ),
				),
			);
			if ( MS_Controller_Membership::STEP_CHOOSE_MS_TYPE == MS_Plugin::instance()->settings->wizard_step ) {
				$pages['protected-content'] = array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Protected Content', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG . '-setup',
					'function' => array( $this->controllers['membership'], 'page_setup_protected_content' ),
				);
			}
		}
		else {
			// Submenus definition: Normal mode
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

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_COUPON ) ) {
				unset( $pages['coupons'] );
			}
		}

		$pages = apply_filters( 'ms_plugin_menu_pages', $pages );

		// Create submenus
		foreach ( $pages as $page ) {
			add_submenu_page(
				$page['parent_slug'],
				$page['page_title'],
				$page['menu_title'],
				$this->capability,
				$page['menu_slug'],
				$page['function']
			);
		}

		do_action( 'ms_controller_plugin_add_menu_pages', $this );
	}

	/**
	 * Get admin url.
	 *
	 * @since 1.0.0
	 *
	 */
	public static function get_admin_url() {
		return apply_filters(
			'ms_controller_plugin_get_admin_url',
			admin_url( 'admin.php?page=' . self::MENU_SLUG )
		);
	}

	/**
	 * Get admin settings url.
	 *
	 * @since 1.0.0
	 *
	 */
	public static function get_admin_settings_url() {
		return apply_filters(
			'ms_controller_plugin_get_admin_url',
			admin_url( 'admin.php?page=' . self::MENU_SLUG . '-settings' )
		);
	}

	/**
	 * Add custom template for invoice cpt.
	 * This replaces the themes "Single" template with our invoice form when
	 * an invoice is displayed.
	 *
	 * ** Hooks Actions/Filters: *
	 * * single_template
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The template path to filter.
	 * @return string The template path.
	 */
	public function custom_template( $template ) {
		global $post;

		// Checks for invoice single template.
		if ( $post->post_type == MS_Model_Invoice::$POST_TYPE ) {
			$invoice_template = apply_filters(
				'ms_controller_plugin_invoice_template',
				MS_Plugin::instance()->dir . 'app/template/single-invoice.php'
			);

			if ( file_exists( $invoice_template ) ) {
				$template = $invoice_template;
			}
		}

		return $template;
	}

	/**
	 * Returns information on current memberships and access to current page.
	 *
	 * Wrapper for MS_Model_Plugin->get_access_info()
	 *
	 * @since  1.0.2
	 * @return array
	 */
	public function get_access_info() {
		return $this->model->get_access_info();
	}

	/**
	 * Returns a list with complete admin menu items.
	 *
	 * Wrapper for MS_Model_Plugin->get_admin_menu()
	 *
	 * @since  1.1
	 * @return array
	 */
	public function get_admin_menu() {
		return $this->model->get_admin_menu();
	}

	/**
	 * Register scripts that are used on the dashboard.
	 *
	 * @since  1.0.0
	 */
	public function register_admin_scripts() {

		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		// The main plugin script.
		wp_register_script(
			'ms-admin',
			$plugin_url . 'app/assets/js/ms-admin.js',
			array( 'jquery', 'jquery-chosen', 'jquery-validate', 'jquery-plugins' ), $version
		);

		wp_register_script(
			'jquery-chosen',
			$plugin_url . 'app/assets/js/select2.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'jquery-plugins',
			$plugin_url . 'app/assets/js/jquery.plugins.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'jquery-validate',
			$plugin_url . 'app/assets/js/jquery.validate.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-controller-admin-bar',
			$plugin_url . 'app/assets/js/ms-controller-admin-bar.js',
			array( 'jquery', 'ms-admin' ), $version
		);

		// View specific
		wp_register_script(
			'ms-view-membership-setup-protected-content',
			$plugin_url . 'app/assets/js/ms-view-membership-setup-protected-content.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-membership-render-url-group',
			$plugin_url . 'app/assets/js/ms-view-membership-render-url-group.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-membership-create-child',
			$plugin_url . 'app/assets/js/ms-view-membership-create-child.js',
			array( 'jquery', 'jquery-validate' ), $version
		);
		wp_register_script(
			'ms-view-membership-setup-dripped',
			$plugin_url. 'app/assets/js/ms-view-membership-setup-dripped.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'membership-metabox',
			$plugin_url. 'app/assets/js/ms-view-membership-metabox.js',
			array( 'jquery', 'ms-admin' ), $version
		);
		wp_register_script(
			'ms-view-coupon-edit',
			$plugin_url . 'app/assets/js/ms-view-coupon-edit.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-billing-edit',
			$plugin_url . 'app/assets/js/ms-view-billing-edit.js',
			array( 'jquery', 'ms-admin' ), $version
		);
	}

	/**
	 * Register styles that are used on the dashboard.
	 *
	 * @since  1.0.0
	 */
	public function register_admin_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		// The main plugin style.
		wp_register_style(
			'ms-admin-styles',
			$plugin_url . 'app/assets/css/ms-admin.css',
			null, $version
		);

		wp_register_style(
			'jquery-ui',
			$plugin_url . 'app/assets/css/jquery-ui.custom.css',
			null, $version
		);
		wp_register_style(
			'font-awesome',
			$plugin_url . 'app/assets/css/font-awesome.css',
			null, $version
		);
		wp_register_style(
			'jquery-chosen',
			$plugin_url . 'app/assets/css/select2.css',
			null, $version
		);
		wp_register_style(
			'ms_view_membership',
			$plugin_url . 'app/assets/css/ms-view-membership.css',
			null, $version
		);
		wp_register_style(
			'ms-admin-bar',
			$plugin_url . 'app/assets/css/ms-admin-bar.css',
			null, $version
		);
	}

	/**
	 * Register scripts that are used on the front-end.
	 *
	 * @since  1.0.0
	 */
	public function register_public_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;


		// @todo REVIEW this block start
		// The main plugin script.
		wp_register_script(
			'ms-admin',
			$plugin_url . 'app/assets/js/ms-admin.js',
			array( 'jquery', 'jquery-chosen', 'jquery-validate', 'jquery-plugins' ), $version
		);
		wp_register_script(
			'ms-ajax-login',
			$plugin_url . 'app/assets/js/ms-public-ajax.js',
			array( 'jquery' ), $version, true // last param forces script to load in footer
		);

		wp_register_script(
			'jquery-chosen',
			$plugin_url . 'app/assets/js/select2.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'jquery-plugins',
			$plugin_url . 'app/assets/js/jquery.plugins.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'jquery-validate',
			$plugin_url . 'app/assets/js/jquery.validate.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-controller-admin-bar',
			$plugin_url . 'app/assets/js/ms-controller-admin-bar.js',
			array( 'jquery', 'ms-admin' ), $version
		);
		// @todo REVIEW this block end

		wp_register_script(
			'jquery-validate',
			$plugin_url . 'app/assets/js/jquery.validate.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-shortcode',
			$plugin_url . 'app/assets/js/ms-shortcode.js',
			array( 'jquery-validate' ), $version
		);
		wp_register_script(
			'ms-view-frontend-profile',
			$plugin_url . 'app/assets/js/ms-view-frontend-profile.js',
			array( 'jquery-validate' ), $version
		);
		wp_register_script(
			'jquery-chosen',
			$plugin_url . 'app/assets/js/select2.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-gateway-authorize',
			$plugin_url . 'app/assets/js/ms-view-gateway-authorize.js',
			array( 'jquery' ), $version
		);
		wp_register_script(
			'ms-view-gateway-stripe',
			$plugin_url . 'app/assets/js/ms-view-gateway-stripe.js',
			array( 'jquery' ), $version
		);
	}

	/**
	 * Register styles that are used on the front-end.
	 *
	 * @since  1.0.0
	 */
	public function register_public_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		// The main plugin style.
		wp_register_style(
			'ms-styles',
			$plugin_url . 'app/assets/css/ms-public.css',
			array( 'jquery-ui', 'jquery-chosen' ),
			$version
		);

		wp_register_style(
			'jquery-ui',
			$plugin_url . 'app/assets/css/jquery-ui.custom.css',
			null, $version
		);
		wp_register_style(
			'jquery-chosen',
			$plugin_url . 'app/assets/css/select2.css',
			null, $version
		);
	}

	/**
	 * Adds CSS for Membership settings pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_plugin_admin_styles() {
		wp_enqueue_style( 'ms-admin-styles' );
		wp_enqueue_style( 'font-awesome' );
		wp_enqueue_style( 'jquery-chosen' );

		WDev()->add_ui();
	}

	/**
	 * Adds CSS for Membership pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_styles() {
		wp_enqueue_style( 'ms-styles' );
	}

	/**
	 * Register JavasSript for Membership settings pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_admin_scripts() {
		wp_enqueue_script( 'jquery-chosen' );
	}

	/**
	 * Adds JavasSript for Membership pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_scripts() {
		wp_localize_script(
			'ms-shortcode',
			'ms_shortcode',
			array(
				'cancel_msg' => __( 'Are you sure you want to cancel?', MS_TEXT_DOMAIN ),
			)
		);

		wp_enqueue_script( 'jquery-validate' );
		wp_enqueue_script( 'ms-shortcode' );
	}
}