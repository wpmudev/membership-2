<?php
/**
 * Primary Membership plugin class.
 *
 * Control of plugin is passed to the MVC implementation found
 * inside the /app and /premium folders.
 *
 * Note: Even all properties are marked private, they are made public via the
 * magic __get() function.
 *
 * @since  1.0.0
 *
 * @return object Plugin instance.
 */
class MS_Plugin {

	/**
	 * Singletone instance of the plugin.
	 *
	 * @since  1.0.0
	 *
	 * @var MS_Plugin
	 */
	private static $instance = null;

	/**
	 * Modifier values. Modifiers are similar to wp-config constants, but can be
	 * also changed via code.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	private static $modifiers = array();

	/**
	 * The WordPress internal plugin identifier.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $id;

	/**
	 * The plugin name.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $name;

	/**
	 * The plugin version.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $version;

	/**
	 * The plugin file.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $file;

	/**
	 * The plugin path.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $dir;

	/**
	 * The plugin URL.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $url;

	/**
	 * The plugin settings.
	 *
	 * @since  1.0.0
	 * @var   MS_Model_Settings
	 */
	private $settings;

	/**
	 * The plugin add-on settings.
	 *
	 * @since  1.0.0
	 * @var   MS_Model_Addon
	 */
	private $addon;


	/**
	 * The main controller of the plugin.
	 *
	 * @since  1.0.0
	 * @var   MS_Controller_Plugin
	 */
	private $controller;

	/**
	 * The API controller (for convenience)
	 *
	 * @since  1.0.0
	 * @var    MS_Controller_Api
	 */
	public static $api = null;

	/**
	 * Plugin constructor.
	 *
	 * Set properties, registers hooks and loads the plugin.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute before the plugin construction starts.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_init', $this );

		/**
		 * Deprecated action.
		 *
		 * @since  1.0.0
		 * @deprecated since 2.0.0
		 */
		do_action( 'ms_plugin_construct_start', $this );

		/** Setup plugin properties */
		$this->id 		= MS_PLUGIN;
		$this->name 	= MS_PLUGIN_NAME;
		$this->version 	= MS_PLUGIN_VERSION;
		$this->file 	= MS_PLUGIN_FILE;
		$this->dir 		= MS_PLUGIN_DIR;
		$this->url 		= plugin_dir_url( MS_PLUGIN_FILE );

		// Might refresh the Rewrite-Rules and reloads the page.
		add_action(
			'wp_loaded',
			array( $this, 'maybe_flush_rewrite_rules' ),
			1
		);

		// Hooks init to register custom post types.
		add_action(
			'init',
			array( $this, 'register_custom_post_types' ),
			1
		);

		// Hooks init to add rewrite rules and tags (both work in conjunction).
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 1 );
		add_action( 'init', array( $this, 'add_rewrite_tags' ), 1 );

		// Plugin activation Hook.
		register_activation_hook(
			MS_PLUGIN_FILE,
			array( $this, 'plugin_activation' )
		);

		//Plugin deactivation hook
		register_deactivation_hook(
			MS_PLUGIN_FILE,
			array( $this, 'plugin_deactivation' )
		);

		/**
		 * Hooks init to create the primary plugin controller.
		 *
		 * We use the setup_theme hook because plugins_loaded is too early:
		 * wp_redirect (used by the update model) is initialized after
		 * plugins_loaded but before setup_theme.
		 */
		add_action(
			'setup_theme',
			array( $this, 'ms_plugin_constructing' )
		);

		/**
		 * Creates and Filters the Settings Model.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->settings = MS_Factory::load( 'MS_Model_Settings' );

		/**
		 * Creates and Filters the Addon Model.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->addon = MS_Factory::load( 'MS_Model_Addon' );

		add_filter(
			'plugin_action_links_' . MS_PLUGIN,
			array( $this, 'plugin_settings_link' )
		);

		add_filter(
			'network_admin_plugin_action_links_' . MS_PLUGIN,
			array( $this, 'plugin_settings_link' )
		);

		// Grab instance of self.
		self::$instance = $this;

		/**
		 * Actions to execute when the Plugin object has successfully constructed.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_construct_end', $this );
	}

	/**
	 * Loads primary plugin controllers.
	 *
	 * Related Action Hooks:
	 * - setup_theme
	 *
	 * @since  1.0.0
	 */
	public function ms_plugin_constructing() {
		/**
		 * Creates and Filters the Plugin Controller.
		 *
		 * ---> MAIN ENTRY POINT CONTROLLER FOR PLUGIN <---
		 *
		 * @uses  MS_Controller_Plugin
		 * @since  1.0.0
		 */
		$this->controller = MS_Factory::create( 'MS_Controller_Plugin' );
	}

	/**
	 * Register plugin custom post types.
	 *
	 * @since  1.0.0
	 */
	public function register_custom_post_types() {
		do_action( 'ms_plugin_register_custom_post_types_before', $this );

		$cpts = apply_filters(
			'ms_plugin_register_custom_post_types',
			array(
				MS_Model_Membership::get_post_type() 	=> MS_Model_Membership::get_register_post_type_args(),
				MS_Model_Relationship::get_post_type() 	=> MS_Model_Relationship::get_register_post_type_args(),
				MS_Model_Invoice::get_post_type() 		=> MS_Model_Invoice::get_register_post_type_args(),
				MS_Model_Communication::get_post_type() => MS_Model_Communication::get_register_post_type_args(),
				MS_Model_Event::get_post_type() 		=> MS_Model_Event::get_register_post_type_args(),
			)
		);

		foreach ( $cpts as $cpt => $args ) {
			MS_Helper_Utility::register_post_type( $cpt, $args );
		}
	}

	/**
	 * Add rewrite rules.
	 *
	 * @since  1.0.0
	 */
	public function add_rewrite_rules() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		// Gateway return - IPN.
		add_rewrite_rule(
			'ms-payment-return/(.+)/?',
			'index.php?paymentgateway=$matches[1]',
			'top'
		);

		// Alternative payment return URL: Old Membership plugin.
		$use_old_ipn = apply_filters( 'ms_legacy_paymentreturn_url', true );
		if ( $use_old_ipn && ! class_exists( 'M_Membership' ) ) {
			add_rewrite_rule(
				'paymentreturn/(.+)/?',
				'index.php?paymentgateway=$matches[1]',
				'top'
			);
		}

		/* Media / download ----- */
		$mmask = $settings->downloads['masked_url'];
		$mtype = $settings->downloads['protection_type'];

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) && $mmask ) {
			if ( MS_Rule_Media_Model::PROTECTION_TYPE_HYBRID == $mtype ) {
				add_rewrite_rule(
					sprintf( '^%1$s/?$', $mmask ),
					'index.php?protectedfile=0',
					'top'
				);
			} else {
				add_rewrite_rule(
					sprintf( '^%1$s/([^/]+)', $mmask ),
					'index.php?protectedfile=$matches[1]',
					'top'
				);
			}
		}
		/* End: Media / download ----- */


		//Web Hook
		add_rewrite_rule(
			'ms-web-hook/(.+)/?',
			'index.php?mswebhook=$matches[1]',
			'top'
		);

		do_action( 'ms_plugin_add_rewrite_rules', $this );
	}

	/**
	 * Add rewrite tags.
	 *
	 * @since  1.0.0
	 */
	public function add_rewrite_tags() {
		// Membership site pages.
		add_rewrite_tag( '%ms_page%', '(.+)' );

		// Gateway return - IPN.
		add_rewrite_tag( '%paymentgateway%', '(.+)' );

		// Media / download.
		add_rewrite_tag( '%protectedfile%', '(.+)' );

		//Gateway Web Hooks
		add_rewrite_tag( '%mswebhook%', '(.+)' );

		do_action( 'ms_plugin_add_rewrite_tags', $this );
	}

	/**
	 * Actions executed in plugin activation.
	 *
	 * @since  1.0.0
	 */
	public function plugin_activation() {
		// Prevent recursion during plugin activation.
		$refresh = lib3()->session->get( 'refresh_url_rules' );
		if ( $refresh ) { return; }

		// Update the Membership2 database entries after activation.
		MS_Model_Upgrade::update( true );

		do_action( 'ms_plugin_activation', $this );
	}

	/**
	 * Actions executed in plugin deactivation
	 *
	 * @since 1.0.3.6
	 */
	public function plugin_deactivation() {
		$jobs = MS_Model_Plugin::cron_jobs();
		foreach ( $jobs as $hook => $interval ) {
			if ( wp_next_scheduled( $hook ) ) {
				wp_clear_scheduled_hook( $hook );
			}
		}
	}

	/**
	 * Redirect page and request plugin to flush the WordPress rewrite rules
	 * on next request.
	 *
	 * @since  1.0.0
	 * @param string $url The URL to load after flushing the rewrite rules.
	 */
	static public function flush_rewrite_rules( $url = false ) {
		if ( isset( $_GET['ms_flushed'] ) && 'yes' == $_GET['ms_flushed'] ) {
			$refresh = true;
		} else {
			$refresh = lib3()->session->get( 'refresh_url_rules' );
		}

		if ( $refresh ) { return; }

		lib3()->session->add( 'refresh_url_rules', true );

		// The URL param is only to avoid cache.
		$url = esc_url_raw(
			add_query_arg( 'ms_ts', time(), $url )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Flush the WordPress rewrite rules.
	 *
	 * @since  1.0.0
	 */
	public function maybe_flush_rewrite_rules() {
		$refresh = lib3()->session->get_clear( 'refresh_url_rules' );
		if ( ! $refresh ) { return; }

		// Set up the plugin specific rewrite rules again.
		$this->add_rewrite_rules();
		$this->add_rewrite_tags();

		do_action( 'ms_plugin_flush_rewrite_rules', $this );

		$url = remove_query_arg( 'ms_ts' );
		$url = esc_url_raw( add_query_arg( 'ms_flushed', 'yes', $url ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Add link to settings page in plugins page.
	 *
	 * @since  1.0.0
	 *
	 * @param array $links WordPress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	public function plugin_settings_link( $links ) {
		if ( ! is_network_admin() ) {
			$text = __( 'Settings', 'membership2' );
			$url = MS_Controller_Plugin::get_admin_url( 'settings' );

			if ( $this->settings->initial_setup ) {
				$url = MS_Controller_Plugin::get_admin_url();
			}

			/**
			 * Filter the plugin settings link.
			 *
			 * @since  1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$settings_link = apply_filters(
				'ms_plugin_settings_link',
				sprintf( '<a href="%s">%s</a>', $url, $text ),
				$this
			);
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Returns singleton instance of the plugin.
	 *
	 * @since  1.0.0
	 *
	 * @static
	 * @access public
	 *
	 * @return MS_Plugin
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new MS_Plugin();

			self::$instance = apply_filters(
				'ms_plugin_instance',
				self::$instance
			);
		}

		return self::$instance;
	}

	/**
	 * Returns plugin enabled status.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @static
	 *
	 * @return bool The status.
	 */
	public static function is_enabled() {
		return self::instance()->settings->plugin_enabled;
	}

	/**
	 * Returns plugin wizard status.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @static
	 *
	 * @return bool The status.
	 */
	public static function is_wizard() {
		return ! ! self::instance()->settings->initial_setup;
	}

	/**
	 * Returns the network-wide protection status.
	 *
	 * This flag can be changed by setting the MS_PROTECT_NETWORK flag to true
	 * in wp-config.php
	 *
	 * @since  1.0.0
	 * @return bool False means that only the current site is protected.
	 *         True means that memberships are shared among all network sites.
	 */
	public static function is_network_wide() {
		/* start:pro */
		static $Networkwide = null;

		if ( null === $Networkwide ) {
			if ( ! defined( 'MS_PROTECT_NETWORK' ) ) {
				define( 'MS_PROTECT_NETWORK', false );
			}

			if ( MS_PROTECT_NETWORK && is_multisite() ) {
				$Networkwide = true;
			} else {
				$Networkwide = false;
			}
		}

		return $Networkwide;
		/* end:pro */

		// Free plugin always returns false (this is a pro feature).
		return false;
	}

	/**
	 * Returns a modifier option.
	 * This is similar to a setting but more "advanced" in a way that there is
	 * no UI for it. A modifier can be set by the plugin (e.g. during Import
	 * the "no_messages" modifier is enabled) or via a const in wp-config.php
	 *
	 * A modifier is never saved in the database.
	 * It can be defined ONLY via MS_Plugin::set_modifier() or via wp-config.php
	 * The set_modifier() value will always take precedence over wp-config.php
	 * definitions.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key Name of the modifier.
	 * @return mixed The modifier value or null.
	 */
	public static function get_modifier( $key ) {
		$res = null;

		if ( isset( self::$modifiers[ $key ] ) ) {
			$res = self::$modifiers[ $key ];
		} elseif ( defined( $key ) ) {
			$res = constant( $key );
		}

		return $res;
	}

	/**
	 * Changes a modifier option.
	 *
	 * @see get_modifier() for more details.
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key Name of the modifier.
	 * @param  mixed  $value Value of the modifier. `null` unsets the modifier.
	 */
	public static function set_modifier( $key, $value = null ) {
		if ( null === $value ) {
			unset( self::$modifiers[ $key ] );
		} else {
			self::$modifiers[ $key ] = $value;
		}
	}

	/**
	 * This funciton initializes the api property for easy access to the plugin
	 * API. This function is *only* called by MS_Controller_Api::__construct()!
	 *
	 * @since  1.0.0
	 * @internal
	 * @param MS_Controller_Api $controller The initialized API controller.
	 */
	public static function set_api( $controller ) {
		self::$api = $controller;
	}

	/**
	 * Returns property associated with the plugin.
	 *
	 * @since  1.0.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
	}

	/**
	 * Check if property isset.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns true/false.
	 */
	public function __isset( $property ) {
		return isset($this->$property);
	}		
};
