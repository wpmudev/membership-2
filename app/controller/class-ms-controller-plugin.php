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
 * @package Membership2
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
	const MENU_SLUG = 'membership2';

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

		/*
		 * Remove the "&msg" attribute from the URL if it was already present in
		 * the previous request.
		 */
		if ( empty( $_POST ) ) {
			/*
			 * No form was submitted:
			 * It's save to redirect the request without losing form-data.
			 */
			if ( isset( $_GET['msg'] )
				&& MS_Helper_Utility::is_current_url( $_SERVER['HTTP_REFERER'] )
			) {
				// A msg is set AND the referer URL has the same msg flag!
				$url = esc_url_raw( remove_query_arg( array( 'msg' ) ) );
				wp_safe_redirect( $url );
				exit;
			}
		}

		// Instantiate Plugin model - protection implementation.
		$this->model = MS_Factory::load( 'MS_Model_Plugin' );

		// Instantiate dialog controller for ajax dialogs.
		$this->dialogs = MS_Factory::load( 'MS_Controller_Dialog' );

		// Register all available styles and scripts. Nothing is enqueued.
		$this->add_action( 'wp_loaded', 'wp_loaded' );

		// Setup plugin admin UI.
		$this->add_action( 'admin_menu', 'add_menu_pages' );

		$this->add_action( 'ms_register_admin_scripts', 'register_admin_scripts' );
		$this->add_action( 'ms_register_admin_scripts', 'register_admin_styles' );
		$this->add_action( 'ms_register_public_scripts', 'register_public_scripts' );
		$this->add_action( 'ms_register_public_scripts', 'register_public_styles' );

		// Initialize core controllers that are available on every page.
		$this->controllers['widget'] = MS_Factory::load( 'MS_Controller_Widget' );
		$this->controllers['membership'] = MS_Factory::load( 'MS_Controller_Membership' );
		$this->controllers['rule'] = MS_Factory::load( 'MS_Controller_Rule' );
		$this->controllers['member'] = MS_Factory::load( 'MS_Controller_Member' );
		$this->controllers['billing'] = MS_Factory::load( 'MS_Controller_Billing' );
		$this->controllers['addon'] = MS_Factory::load( 'MS_Controller_Addon' );
		$this->controllers['pages'] = MS_Factory::load( 'MS_Controller_Pages' );
		$this->controllers['settings'] = MS_Factory::load( 'MS_Controller_Settings' );
		$this->controllers['communication'] = MS_Factory::load( 'MS_Controller_Communication' );
		$this->controllers['gateway'] = MS_Factory::load( 'MS_Controller_Gateway' );
		$this->controllers['admin_bar'] = MS_Factory::load( 'MS_Controller_Adminbar' );
		$this->controllers['membership_metabox'] = MS_Factory::load( 'MS_Controller_Metabox' );
		$this->controllers['membership_shortcode'] = MS_Factory::load( 'MS_Controller_Shortcode' );
		$this->controllers['frontend'] = MS_Factory::load( 'MS_Controller_Frontend' );
		$this->controllers['import'] = MS_Factory::load( 'MS_Controller_Import' );
		$this->controllers['help'] = MS_Factory::load( 'MS_Controller_Help' );

		// API should be the last Controller to create.
		$this->controllers['api'] = MS_Factory::load( 'MS_Controller_Api' );

		// Changes the current themes "single" template to the invoice form when an invoice is displayed.
		$this->add_filter( 'single_template', 'custom_template' );

		// Register admin styles (CSS)
		$this->add_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_styles' );

		// Register styles used in the front end (CSS)
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_styles' );

		// Enqueue admin scripts (JS)
		$this->add_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_scripts' );

		// Register scripts used in the front end (JS)
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_scripts' );
	}

	/**
	 * Returns the WordPress hook that identifies a Membership2 admin page.
	 *
	 * @since  2.0.0
	 * @param  string $subpage
	 * @return string The internal hook name
	 */
	public static function admin_page_hook( $subpage = '' ) {
		if ( empty( $subpage ) ) {
			$hook = 'toplevel_page_' . self::MENU_SLUG;
		} else {
			$hook = self::MENU_SLUG . '_page_' . self::MENU_SLUG . '-' . $subpage;
		}

		return $hook;
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
		/*
		 * Create primary menu item: Membership.
		 *
		 * The menu title is not translatable because of a bug in WordPress core
		 * https://core.trac.wordpress.org/ticket/18857
		 * Until this bug is closed the title (2nd argument) can't be translated
		 */
		add_menu_page(
			__( 'Membership2', MS_TEXT_DOMAIN ),
			'Membership2', // no i18n!
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

			if ( MS_Controller_Membership::STEP_ADD_NEW == MS_Plugin::instance()->settings->wizard_step ) {
				$pages[self::MENU_SLUG] = array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Membership2', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG . '-setup',
					'function' => array( $this->controllers['membership'], 'page_protected_content' ),
				);
			}
		} else {
			$args = MS_Model_Invoice::get_query_args();
			$args['meta_query']['status']['value'] = array(
				MS_Model_Invoice::STATUS_BILLED,
				MS_Model_Invoice::STATUS_PENDING,
			);
			$args['meta_query']['status']['compare'] = 'IN';
			$bill_count = MS_Model_Invoice::get_invoice_count( $args );
			if ( $bill_count > 99 ) { $bill_count = '99+'; }
			elseif ( ! $bill_count ) { $bill_count = ''; }

			// Submenus definition: Normal mode
			$pages = array(
				'memberships' => array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Memberships', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Memberships', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG,
					'function' => array( $this->controllers['membership'], 'membership_admin_page_router' ),
				),
				'protected-content' => array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Protected Content', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG . '-setup',
					'function' => array( $this->controllers['membership'], 'page_protected_content' ),
				),
				'members' => array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Members', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Members', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG . '-members',
					'function' => array( $this->controllers['member'], 'admin_member_list' ),
				),
				'billing' => array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Billing', MS_TEXT_DOMAIN ),
					'menu_title' => sprintf(
						'%1$s <span class="awaiting-mod count-%3$s"><span class="pending-count">%2$s</span></span>',
						__( 'Billing', MS_TEXT_DOMAIN ),
						$bill_count,
						sanitize_html_class( $bill_count, '0' )
					),
					'menu_slug' => self::MENU_SLUG . '-billing',
					'function' => array( $this->controllers['billing'], 'admin_billing' ),
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
				'help' => array(
					'parent_slug' => self::MENU_SLUG,
					'page_title' => __( 'Help', MS_TEXT_DOMAIN ),
					'menu_title' => __( 'Help', MS_TEXT_DOMAIN ),
					'menu_slug' => self::MENU_SLUG . '-help',
					'function' => array( $this->controllers['help'], 'admin_help' ),
				),
			);

			if ( ! MS_Model_Membership::have_paid_membership() ) {
				unset( $pages['billing'] );
			}
		}

		$pages = apply_filters(
			'ms_plugin_menu_pages',
			$pages,
			MS_Plugin::is_wizard(),
			$this
		);

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
			array( 'jquery', 'jquery-validate', 'jquery-plugins' ), $version
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
	}

	/**
	 * Register scripts that are used on the front-end.
	 *
	 * @since  1.0.0
	 */
	public function register_public_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;

		// The main plugin script.
		wp_register_script(
			'ms-admin',
			$plugin_url . 'app/assets/js/ms-admin.js',
			array( 'jquery', 'jquery-validate', 'jquery-plugins' ), $version
		);
		wp_register_script(
			'ms-ajax-login',
			$plugin_url . 'app/assets/js/ms-public-ajax.js',
			array( 'jquery' ), $version, true // last param forces script to load in footer
		);
		wp_register_script(
			'ms-public',
			$plugin_url . 'app/assets/js/ms-public.js',
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
			array(),
			$version
		);
	}

	/**
	 * Adds CSS for Membership settings pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_plugin_admin_styles() {
		lib2()->ui->css( 'ms-admin-styles' );
		lib2()->ui->add( 'core' );
		lib2()->ui->add( 'select' );
		lib2()->ui->add( 'fontawesome' );
	}

	/**
	 * Adds CSS for Membership pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_styles() {
		// Front-End styles are enqueued by MS_Controller_Frontend.
	}

	/**
	 * Register JavasSript for Membership settings pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_admin_scripts() {
		lib2()->ui->add( 'select' );
	}

	/**
	 * Adds JavasSript for Membership pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_scripts() {
		// Front-End scripts are enqueued by MS_Controller_Frontend.
	}

	/**
	 * Adds a javascript to the page that will translate the jQuery validator
	 * messages.
	 *
	 * @since  1.1.0
	 */
	static public function translate_jquery_validator() {
		ob_start();
		?>
		jQuery.extend( jQuery.validator.messages, {
			required: "<?php _e( 'This field is required.', MS_TEXT_DOMAIN ); ?>",
			remote: "<?php _e( 'Please fix this field.', MS_TEXT_DOMAIN ); ?>",
			email: "<?php _e( 'Please enter a valid email address.', MS_TEXT_DOMAIN ); ?>",
			url: "<?php _e( 'Please enter a valid URL.', MS_TEXT_DOMAIN ); ?>",
			date: "<?php _e( 'Please enter a valid date.', MS_TEXT_DOMAIN ); ?>",
			dateISO: "<?php _e( 'Please enter a valid date ( ISO ).', MS_TEXT_DOMAIN ); ?>",
			number: "<?php _e( 'Please enter a valid number.', MS_TEXT_DOMAIN ); ?>",
			digits: "<?php _e( 'Please enter only digits.', MS_TEXT_DOMAIN ); ?>",
			creditcard: "<?php _e( 'Please enter a valid credit card number.', MS_TEXT_DOMAIN ); ?>",
			equalTo: "<?php _e( 'Please enter the same value again.', MS_TEXT_DOMAIN ); ?>",
			maxlength: jQuery.validator.format( "<?php _e( 'Please enter no more than {0} characters.', MS_TEXT_DOMAIN ); ?>" ),
			minlength: jQuery.validator.format( "<?php _e( 'Please enter at least {0} characters.', MS_TEXT_DOMAIN ); ?>" ),
			rangelength: jQuery.validator.format( "<?php _e( 'Please enter a value between {0} and {1} characters long.', MS_TEXT_DOMAIN ); ?>" ),
			range: jQuery.validator.format( "<?php _e( 'Please enter a value between {0} and {1}.', MS_TEXT_DOMAIN ); ?>" ),
			max: jQuery.validator.format( "<?php _e( 'Please enter a value less than or equal to {0}.', MS_TEXT_DOMAIN ); ?>" ),
			min: jQuery.validator.format( "<?php _e( 'Please enter a value greater than or equal to {0}.', MS_TEXT_DOMAIN ); ?>" )
		});
		<?php
		$script = ob_get_clean();
		lib2()->ui->script( $script );
	}
}