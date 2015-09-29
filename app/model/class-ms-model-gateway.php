<?php
/**
 * Register valid gateways.
 *
 * Gateways are stored in the directory /app/gateway/<gateway_name>/
 * Each Add-on must provide a file called `gateway-<gateway_name>.php`
 * This file must define class MS_Gateway_<gateway_name>.
 * This object is reponsible to initialize the the gateway logic.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Gateway extends MS_Model_Option {

	/**
	 * List of gateway files to load when plugin is initialized.
	 *
	 * @since  1.0.0
	 *
	 * @var array of file-paths
	 */
	protected $gateway_files = array();

	/*
	 *
	 * @since  1.0.0
	 * @var string $gateways
	 */
	protected static $_gateways = null;

	/**
	 * Load and get all registered gateways.
	 *
	 * @since  1.0.0
	 * @param bool $only_active Optional. When to return only activated gateways.
	 */
	public static function get_gateways( $only_active = false ) {
		static $Done = false;
		$res = null;

		if ( ! $Done ) {
			self::$_gateways = array();
			$gateways = array();
			$Done = true;
			self::load_core_gateways();

			/**
			 * Register new gateways.
			 *
			 * @since  1.0.0
			 */
			$gateways = apply_filters(
				'ms_model_gateway_register',
				$gateways
			);

			foreach ( $gateways as $key => $class ) {
				self::$_gateways
				[$key] = MS_Factory::load( $class );
			}
		}

		$res = self::$_gateways;

		if ( $only_active ) {
			foreach ( $res as $id => $gateway ) {
				if ( ! $gateway->active ) {
					unset( $res[ $id ] );
				}
			}
		}

		return apply_filters(
			'ms_model_gateway_get_gateways',
			$res,
			$only_active
		);
	}

	/**
	 * Checks if the specified gateway is active.
	 *
	 * @since  1.0.0
	 * @param  string $gateway_id The gateway ID.
	 * @return bool True if the gateway is active.
	 */
	static public function is_active( $gateway_id ) {
		$result = false;
		$active_gateways = self::get_gateways( true );

		if ( isset( $active_gateways[ $gateway_id ] ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Checks the /app/gateway directory for a list of all gateways and loads
	 * these files.
	 *
	 * @since  1.0.0
	 */
	static protected function load_core_gateways() {
		$model = MS_Factory::load( 'MS_Model_Gateway' );
		$root_path = trailingslashit( dirname( dirname( MS_Plugin::instance()->dir ) ) );
		$plugin_dir = substr( MS_Plugin::instance()->dir, strlen( $root_path ) );
		$gateway_dir = $plugin_dir . 'app/gateway/';

		if ( empty( $model->gateway_files ) || is_admin() ) {
			// In Admin dashboard we always refresh the gateway-list...

			$mask = $root_path . $gateway_dir . '*/class-ms-gateway-*.php';
			$gateways = glob( $mask );

			$model->gateway_files = array();
			foreach ( $gateways as $file ) {
				$model->gateway_files[] = substr( $file, strlen( $root_path ) );
			}

			/**
			 * Allow other plugins/themes to register custom gateways
			 *
			 * @since  1.0.0
			 *
			 * @var array
			 */
			$model->gateway_files = apply_filters(
				'ms_model_gateway_files',
				$model->gateway_files
			);

			$model->save();
		}

		// Loop all recignized Gateways and initialize them.
		foreach ( $model->gateway_files as $file ) {
			$gateway_file = $root_path . $file;

			// Get class-name from file-name
			$class = basename( $file );
			$class = str_replace( '.php', '', $class );
			$class = implode( '_', array_map( 'ucfirst', explode( '-', $class ) ) );
			$class = substr( $class, 6 ); // remove 'Class_' prefix

			if ( file_exists( $gateway_file ) ) {
				if ( ! class_exists( $class ) ) {
					try {
						include_once $gateway_file;
					} catch ( Exception $ex ) {
					}
				}

				if ( class_exists( $class ) ) {
					MS_Factory::load( $class );
				}
			}
		}

		/**
		 * Allow custom gateway-initialization code to run
		 *
		 * @since  1.0.0
		 */
		do_action( 'ms_model_gateway_load' );
	}

	/**
	 * Get all registered gateway names.
	 *
	 * @since  1.0.0
	 * @param bool $only_active Optional. False (default) returns only activated gateways.
	 * @param bool $include_gateway_free Optional. True (default) includes Gateway Free.
	 */
	public static function get_gateway_names( $only_active = false, $include_gateway_free = false ) {
		$gateways = self::get_gateways( $only_active );
		$names = array();

		foreach ( $gateways as $gateway ) {
			$names[ $gateway->id ] = $gateway->name;
		}

		if ( ! $include_gateway_free ) {
			unset( $names[ MS_Gateway_Free::ID ] );
		} else {
			$names['admin'] = __( 'None (Admin)', 'membership2' );
		}

		return apply_filters(
			'ms_model_gateway_get_gateway_names',
			$names
		);
	}

	/**
	 * Returns the gateway name for the specified gateway ID
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $gateway_id The gateway ID.
	 * @param  bool $get_short If set to true the word "Gateway" will be removed.
	 * @return string The gateway Name.
	 */
	public static function get_name( $gateway_id, $get_short = false ) {
		static $Short_names = array();
		$known_names = self::get_gateway_names();
		$the_name = '-';

		if ( isset( $known_names[$gateway_id] ) ) {
			$the_name = $known_names[$gateway_id];
		}

		if ( $get_short ) {
			if ( ! isset( $Short_names[$gateway_id] ) ) {
				$Short_names[$gateway_id] = trim(
					str_replace(
						__( 'Gateway', 'membership2' ),
						'',
						$the_name
					)
				);
			}
			$the_name = $Short_names[$gateway_id];
		}

		return $the_name;
	}

	/**
	 * Validate gateway.
	 *
	 * @since  1.0.0
	 * @param string $gateway_id The gateway ID to validate.
	 */
	public static function is_valid_gateway( $gateway_id ) {
		$valid = array_key_exists( $gateway_id, self::get_gateways() );

		return apply_filters(
			'ms_model_gateway_is_valid_gateway',
			$valid
		);
	}

	/**
	 * Gateway factory.
	 *
	 * @since  1.0.0
	 * @param string $gateway_id The gateway ID to create.
	 */
	public static function factory( $gateway_id ) {
		$gateway = null;

		if ( 'admin' == $gateway_id || empty( $gateway_id ) || 'gateway' == $gateway_id ) {
			$gateway = MS_Factory::create( 'MS_Gateway' );
		} elseif ( self::is_valid_gateway( $gateway_id ) ) {
			$gateways = self::get_gateways();
			$gateway = $gateways[ $gateway_id ];
		}

		return apply_filters(
			'ms_model_gateway_factory',
			$gateway,
			$gateway_id
		);
	}

}
