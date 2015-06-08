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
	 * The slug of the top-level admin page
	 *
	 * @since  2.0.0
	 *
	 * @var string
	 */
	private static $base_slug = '';

	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var $model
	 */
	private $model;

	/**
	 * Pointer array for other controllers.
	 *
	 * @since 1.0.0
	 *
	 * @var $controllers
	 */
	protected $controllers = array();

	/**
	 * Stores the callback handler for the submenu items.
	 * It is set by self::route_submenu_request() and is used by
	 * self::handle_submenu_request()
	 *
	 * @since  2.0.0
	 *
	 * @var array
	 */
	private $menu_handler = null;

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

		// Create core controllers that are available on every page.
		$this->model                               = MS_Factory::load( 'MS_Model_Plugin' );
		$this->dialogs                             = MS_Factory::load( 'MS_Controller_Dialog' );
		$this->controllers['widget']               = MS_Factory::load( 'MS_Controller_Widget' );
		$this->controllers['membership']           = MS_Factory::load( 'MS_Controller_Membership' );
		$this->controllers['rule']                 = MS_Factory::load( 'MS_Controller_Rule' );
		$this->controllers['member']               = MS_Factory::load( 'MS_Controller_Member' );
		$this->controllers['billing']              = MS_Factory::load( 'MS_Controller_Billing' );
		$this->controllers['addon']                = MS_Factory::load( 'MS_Controller_Addon' );
		$this->controllers['pages']                = MS_Factory::load( 'MS_Controller_Pages' );
		$this->controllers['settings']             = MS_Factory::load( 'MS_Controller_Settings' );
		$this->controllers['communication']        = MS_Factory::load( 'MS_Controller_Communication' );
		$this->controllers['gateway']              = MS_Factory::load( 'MS_Controller_Gateway' );
		$this->controllers['admin_bar']            = MS_Factory::load( 'MS_Controller_Adminbar' );
		$this->controllers['membership_metabox']   = MS_Factory::load( 'MS_Controller_Metabox' );
		$this->controllers['membership_shortcode'] = MS_Factory::load( 'MS_Controller_Shortcode' );
		$this->controllers['frontend']             = MS_Factory::load( 'MS_Controller_Frontend' );
		$this->controllers['import']               = MS_Factory::load( 'MS_Controller_Import' );
		$this->controllers['help']                 = MS_Factory::load( 'MS_Controller_Help' );

		// API should be the last Controller to create.
		$this->controllers['api']                  = MS_Factory::load( 'MS_Controller_Api' );

		// Register all available styles and scripts. Nothing is enqueued.
		$this->add_action( 'wp_loaded', 'wp_loaded' );

		// Setup plugin admin UI.
		$this->add_action( 'admin_menu', 'add_menu_pages' );
		if ( MS_Plugin::is_network_wide() ) {
			$this->add_action( 'network_admin_menu', 'add_menu_pages' );
		}

		// Select the right page to display.
		$this->add_action( 'admin_init', 'route_submenu_request' );

		// This will do the ADMIN-SIDE initialization of the controllers
		$this->add_action( 'ms_plugin_admin_setup', 'run_admin_init' );

		// Changes the current themes "single" template to the invoice form when an invoice is displayed.
		$this->add_filter( 'single_template', 'custom_template' );

		// Register styles and javascripts for use in front-end
		$this->add_action( 'ms_register_public_scripts', 'register_public_scripts' );
		$this->add_action( 'ms_register_public_scripts', 'register_public_styles' );
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_styles' );
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_scripts' );
	}

	/**
	 * Creates all the plugin controllers and initialize stuff.
	 *
	 * This is done after admin_menu (when in admin site) or
	 * after setup_theme (on front-end)
	 *
	 * @since  2.0.0
	 */
	public function run_admin_init() {
		if ( ! is_admin() && ! is_network_admin() ) { return; }

		/*
		 * This function is used to redirect the user to special kind of page
		 * that is not available via the menu.
		 */
		$this->check_special_view();

		foreach ( $this->controllers as $obj ) {
			$obj->admin_init();
		}

		// Register styles and javascripts for use in admin-side
		$this->run_action( 'ms_register_admin_scripts', 'register_admin_scripts' );
		$this->run_action( 'ms_register_admin_scripts', 'register_admin_styles' );
		$this->run_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_styles' );
		$this->run_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_scripts' );
	}

	/**
	 * If a special view is active then we ensure that it is displayed now.
	 *
	 * A special view is not accessible via the normal menu structure, like
	 * a Migration assistant or an overview page after updating the plugin.
	 *
	 * Special views can be set/reset/checked via these functions:
	 *   MS_Model_Settings::set_special_view( 'name' );
	 *   MS_Model_Settings::get_special_view();
	 *   MS_Model_Settings::reset_special_view();
	 *
	 * @since  1.0.0
	 */
	protected function check_special_view() {
		$view_name = MS_Model_Settings::get_special_view();

		if ( ! $view_name ) { return; }

		$view = MS_Factory::load( $view_name );
		$view->enqueue_scripts();

		// Modify the main menu to handle our special_view for default item.
		add_submenu_page(
			self::$base_slug,
			'Membership 2',
			'Membership 2',
			$this->capability,
			self::$base_slug,
			array( $this, 'handle_special_view' )
		);
	}

	/**
	 * Function is only called when a special view is defined. This function
	 * will load that view and display it.
	 *
	 * @since  1.0.0
	 */
	public function handle_special_view() {
		$view_name = MS_Model_Settings::get_special_view();
		$view = MS_Factory::load( $view_name );

		echo $view->to_html();
	}

	/**
	 * Returns the WordPress hook that identifies a Membership2 admin page.
	 *
	 * Important: In order for this function to work as expected it needs to
	 * be called *after* the admin-menu was registered!
	 *
	 * @since  2.0.0
	 * @param  string $subpage
	 * @return string The internal hook name
	 */
	public static function admin_page_hook( $subpage = '' ) {
		if ( empty( $subpage ) ) {
			$plugin_page = self::MENU_SLUG;
		} else {
			$plugin_page = self::MENU_SLUG . '-' . $subpage;
		}

		$the_parent = 'admin.php';
		$hook = get_plugin_page_hookname( $plugin_page, $the_parent );

		return $hook;
	}

	/**
	 * Register scripts and styles
	 *
	 * @since  1.0.0
	 */
	public function wp_loaded() {
		if ( is_admin() || is_network_admin() ) {
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
		global $submenu;
		$limited_mode = false;

		$view = MS_Model_Settings::get_special_view();
		if ( $view ) {
			// A special view is displayed. Do not display other menu items.
			$pages = array();

			$limited_mode = true;
		} elseif ( MS_Plugin::is_wizard() ) {
			// Submenus definition: Wizard mode
			$pages = $this->get_setup_menu_pages();

			$limited_mode = true;
		} else {
			// Submenus definition: Normal mode
			$pages = $this->get_default_menu_pages();

			if ( MS_Plugin::is_network_wide() && ! is_network_admin() ) {
				$limited_mode = true;
			}
		}

		/**
		 * Allow Add-ons and other plugins to add menu pages.
		 *
		 * A menu item is defined by an array containing the following members:
		 *   'title' => '...',
		 *   'slug' => '...',
		 *   'function' => callback
		 *
		 * @var array
		 */
		$pages = apply_filters(
			'ms_plugin_menu_pages',
			$pages,
			$limited_mode,
			$this
		);

		$page_keys = array_keys( $pages );
		$slug = '';
		if ( isset( $page_keys[0] ) && $pages[ $page_keys[0] ] ) {
			$slug = $pages[ $page_keys[0] ]['slug'];
		}
		if ( empty( $slug ) ) {
			self::$base_slug = self::MENU_SLUG;
		} else {
			self::$base_slug = self::MENU_SLUG . '-' . $slug;
		}

		/*
		 * Create primary menu item: Membership.
		 *
		 * The menu title is not translatable because of a bug in WordPress core
		 * https://core.trac.wordpress.org/ticket/18857
		 * Until this bug is closed the title (2nd argument) can't be translated
		 */
		add_menu_page(
			'Membership2', // no i18n!
			'Membership2', // no i18n!
			$this->capability,
			self::$base_slug,
			null,
			'dashicons-lock'
		);

		// Create submenus
		foreach ( $pages as $page ) {
			if ( ! is_array( $page ) ) { continue; }

			if ( empty( $page['link'] ) ) {
				$menu_link = false;
			} else {
				$menu_link = $page['link'];
			}

			$slug = self::MENU_SLUG;
			if ( ! empty( $page['slug'] ) ) {
				$slug .= '-' . $page['slug'];
			}

			add_submenu_page(
				self::$base_slug,
				strip_tags( $page['title'] ),
				$page['title'],
				$this->capability,
				$slug,
				array( $this, 'handle_submenu_request' )
			);

			/*
			 * WordPress does not support absolute URLs in the admin-menu.
			 * So we have to manny modify the menu-link href value if our slug
			 * is an absolute URL.
			 */
			if ( $menu_link ) {
				$item = end( $submenu[self::$base_slug] );
				$key = key( $submenu[self::$base_slug] );
				$submenu[self::$base_slug][$key][2] = $menu_link;
			}
		}

		do_action( 'ms_controller_plugin_add_menu_pages', $this );

		// Setup the rest of the plugin after the menu was registered.
		do_action( 'ms_plugin_admin_setup' );
	}

	/**
	 * Returns the admin menu items for setting up the plugin.
	 * Helper function used by add_menu_pages
	 *
	 * @since  2.0.0
	 * @return array
	 */
	private function get_setup_menu_pages() {
		$pages = array(
			'setup' => array(
				'title' => __( 'Set-up', MS_TEXT_DOMAIN ),
				'slug' => '',
			),
		);

		$step = $this->controllers['membership']->get_step();
		if ( MS_Controller_Membership::STEP_ADD_NEW == $step ) {
			$pages['setup']['slug'] = 'setup';

			$pages[self::MENU_SLUG] = array(
				'title' => __( 'Protected Content', MS_TEXT_DOMAIN ),
				'slug' => '',
			);
		}

		return $pages;
	}

	/**
	 * Returns the default admin menu items for Membership2.
	 * Helper function used by add_menu_pages
	 *
	 * @since  2.0.0
	 * @return array
	 */
	private function get_default_menu_pages() {
		$show_billing = false;

		$pages = array(
			'memberships' => array(
				'title' => __( 'Memberships', MS_TEXT_DOMAIN ),
				'slug' => '',
			),
			'protected-content' => array(
				'title' => __( 'Protected Content', MS_TEXT_DOMAIN ),
				'slug' => 'protection',
			),
			'members' => array(
				'title' => __( 'Members', MS_TEXT_DOMAIN ),
				'slug' => 'members',
			),
			'billing' => false,
			'addon' => array(
				'title' => __( 'Add-ons', MS_TEXT_DOMAIN ),
				'slug' => 'addon',
			),
			'settings' => array(
				'title' => __( 'Settings', MS_TEXT_DOMAIN ),
				'slug' => 'settings',
			),
			'help' => array(
				'title' => __( 'Help', MS_TEXT_DOMAIN ),
				'slug' => 'help',
			),
		);

		$show_billing = MS_Model_Membership::have_paid_membership();

		if ( $show_billing ) {
			$bill_count = MS_Model_Invoice::get_unpaid_invoice_count( null, true );

			$pages['billing'] = array(
				'title' => sprintf(
					'%1$s <span class="awaiting-mod count-%3$s"><span class="pending-count"><i class="hidden">(</i>%2$s<i class="hidden">)</i></span></span>',
					__( 'Billing', MS_TEXT_DOMAIN ),
					$bill_count,
					sanitize_html_class( $bill_count, '0' )
				),
				'slug' => 'billing',
			);
		}

		return $pages;
	}

	/**
	 * Handles all menu-items and calls the correct callback function.
	 *
	 * We introduce this routing function to monitor all menu-item calls so we
	 * can make sure that network-wide protection loads the correct blog or
	 * admin-area before displaing the page.
	 *
	 * This function will only handle submenu items of the Membership2 menu!
	 *
	 * @since  2.0.0
	 */
	public function route_submenu_request() {
		global $submenu;
		$handler = null;
		$handle_it = false;

		if ( ! isset( $_GET['page'] ) ) { return; }
		if ( $_GET['page'] === self::$base_slug ) {
			$handle_it = true;
		} elseif ( isset( $submenu[self::$base_slug] ) ) {
			foreach ( $submenu[self::$base_slug] as $item ) {
				if ( $_GET['page'] === $item[2] ) { $handle_it = true; break; }
			}
		}
		if ( ! $handle_it ) { return; }

		if ( MS_Plugin::is_wizard() ) {
			$step_add = MS_Controller_Membership::STEP_ADD_NEW == MS_Plugin::instance()->settings->wizard_step;

			if ( ! $step_add || self::is_page( 'setup' ) ) {
				$handler = array(
					'any',
					array( $this->controllers['membership'], 'membership_admin_page_router' ),
				);
			} else {
				$handler = array(
					'site',
					array( $this->controllers['membership'], 'page_protected_content' ),
				);
			}
		} else  {
			if ( self::is_page( '' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['membership'], 'membership_admin_page_router' ),
				);
			} elseif ( self::is_page( 'protection' ) ) {
				$handler = array(
					'site',
					array( $this->controllers['membership'], 'page_protected_content' ),
				);
			} elseif ( self::is_page( 'members' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['member'], 'admin_member_list' ),
				);
			} elseif ( self::is_page( 'addon' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['addon'], 'admin_addon' ),
				);
			} elseif ( self::is_page( 'settings' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['settings'], 'admin_settings' ),
				);
			} elseif ( self::is_page( 'help' ) ) {
				$handler = array(
					'any',
					array( $this->controllers['help'], 'admin_help' ),
				);
			} elseif ( self::is_page( 'billing' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['billing'], 'admin_billing' ),
				);
			}
		}

		/**
		 * Filter that allows Add-ons to add their own sub-menu handlers.
		 *
		 * @since  2.0.0
		 */
		$handler = apply_filters(
			'ms_route_submenu_request',
			$handler,
			$this
		);

		// Provide a fallback handler in case we could not identify the handler.
		if ( ! $handler ) {
			$handler = array(
				'network',
				array( $this->controllers['membership'], 'membership_admin_page_router' ),
			);
		}

		// Handle the target attribute specified in $handler[0]
		if ( MS_Plugin::is_network_wide() && 'any' != $handler[0] ) {
			$redirect = false;
			$admin_script = 'admin.php?' . $_SERVER['QUERY_STRING'];

			if ( 'network' == $handler[0] && ! is_network_admin() ) {
				$redirect = network_admin_url( $admin_script );
			} elseif ( 'site' == $handler[0] && is_network_admin() ) {
				$redirect = admin_url( $admin_script );
			}

			if ( $redirect ) {
				if ( headers_sent() ) {
					echo '<script>location.href=' . json_encode( $redirect ) . ';</script>';
				} else {
					wp_safe_redirect( $redirect );
				}

				exit;
			}
		}

		$this->menu_handler = $handler;
	}

	/**
	 * Simply calls the menu-handler callback function.
	 *
	 * This function was determined by the previous call to
	 * self::route_submenu_request() during the admin_init hook.
	 *
	 * @since  2.0.0
	 */
	public function handle_submenu_request() {
		if ( ! empty( $this->menu_handler ) ) {
			// This function will actually render the requested page!
			call_user_func( $this->menu_handler[1] );
		}
	}

	/**
	 * Checks if the current user is on the specified Membership2 admin page.
	 *
	 * @since  2.0.0
	 * @param  string $slug The membership2 slug (without the menu-slug prefix)
	 * @return bool
	 */
	public static function is_page( $slug ) {
		$curpage = false;
		if ( isset( $_REQUEST['page'] ) ) {
			$curpage = sanitize_html_class( $_REQUEST['page'] );
		}

		if ( empty( $slug ) ) {
			$slug = self::$base_slug;
		} else {
			$slug = self::MENU_SLUG . '-' . $slug;
		}

		return $curpage == $slug;
	}

	/**
	 * Get admin url.
	 *
	 * @since  1.0.0
	 * @param  string $slug Optional. Slug of the admin page, if empty the link
	 *         points to the main admin page.
	 * @return string The full URL to the admin page.
	 */
	public static function get_admin_url( $slug = '', $args = null ) {
		$base_slug = self::$base_slug;

		// These slugs are opened in network-admin for network-wide protection.
		$global_slugs = array(
			'memberships',
			'addon',
			'settings',
		);

		// Determine if the slug is opened in network-admin or site admin.
		$network_slug = MS_Plugin::is_network_wide()
			&& ( in_array( $slug, $global_slugs ) || is_network_admin() );

		if ( $network_slug ) {
			$base_slug = self::MENU_SLUG;
			if ( 'memberships' === $slug ) { $slug = ''; }
		}

		if ( 'MENU_SLUG' == $slug ) {
			$slug = self::MENU_SLUG;
		} elseif ( empty( $slug ) ) {
			$slug = self::$base_slug;
		} else {
			$slug = self::MENU_SLUG . '-' . $slug;
		}

		if ( ! $slug ) {
			$slug = self::MENU_SLUG;
		}

		if ( $network_slug ) {
			$url = network_admin_url( 'admin.php?page=' . $slug );
		} else {
			$url = admin_url( 'admin.php?page=' . $slug );
		}

		if ( $args ) {
			$url = esc_url_raw( add_query_arg( $args, $url ) );
		}

		return apply_filters(
			'ms_controller_plugin_get_admin_url',
			$url
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
		if ( $post->post_type == MS_Model_Invoice::get_post_type() ) {
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