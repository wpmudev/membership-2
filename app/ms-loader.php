<?php
/**
 * Class-Loader code.
 * Initialises the autoloader and required plugin hooks.
 *
 * @since  1.0.0
 */
class MS_Loader {

	/**
	 * Plugin constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		add_filter(
			'ms_class_path_overrides',
			array( $this, 'ms_class_path_overrides' )
		);

		// Creates the class autoloader.
		// Special: Method `class_loader` can be private and it will work here!
		spl_autoload_register( array( $this, 'class_loader' ) );
	}

	/**
	 * Hooks 'ms_class_path_overrides'.
	 *
	 * Overrides plugin class paths to adhere to naming conventions
	 * where object names are separated by underscores or for special cases.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $overrides Array passed in by filter.
	 * @return array(class=>path) Classes with new file paths.
	 */
	public function ms_class_path_overrides( $overrides ) {
        $core_base = 'app/core/';
		$core = array(
			'MS_Addon' 		=> 'class-ms-addon.php',
            'MS_Auth' 		=> 'class-ms-auth.php',
			'MS_Controller' => 'class-ms-controller.php',
			'MS_Dialog' 	=> 'class-ms-dialog.php',
			'MS_Factory' 	=> 'class-ms-factory.php',
			'MS_Gateway' 	=> 'class-ms-gateway.php',
			'MS_Helper' 	=> 'class-ms-helper.php',
			'MS_Hooker' 	=> 'class-ms-hooker.php',
			'MS_Model' 		=> 'class-ms-model.php',
			'MS_Plugin' 	=> 'class-ms-plugin.php',
			'MS_Rule' 		=> 'class-ms-rule.php',
			'MS_View' 		=> 'class-ms-view.php',
		);


		$models_base = 'app/model/';
		$models = array(
			'MS_Model_Communication_After_Finishes'         => 'communication/class-ms-model-communication-after-finishes.php',
			'MS_Model_Communication_After_Payment_Due'      => 'communication/class-ms-model-communication-after-payment-due.php',
			'MS_Model_Communication_Before_Finishes'        => 'communication/class-ms-model-communication-before-finishes.php',
			'MS_Model_Communication_Before_Payment_Due'     => 'communication/class-ms-model-communication-before-payment-due.php',
			'MS_Model_Communication_Before_Trial_Finishes'  => 'communication/class-ms-model-communication-before-trial-finishes.php',
			'MS_Model_Communication_Credit_Card_Expire'     => 'communication/class-ms-model-communication-credit-card-expire.php',
			'MS_Model_Communication_Failed_Payment'         => 'communication/class-ms-model-communication-failed-payment.php',
			'MS_Model_Communication_Info_Update'            => 'communication/class-ms-model-communication-info-update.php',
			'MS_Model_Communication_Registration_Free'      => 'communication/class-ms-model-communication-registration-free.php',
		);

        foreach ( $core as $key => $path ) {
			$overrides[ $key ] = $core_base . $path;
		}

		foreach ( $models as $key => $path ) {
			$overrides[ $key ] = $models_base . $path;
		}

		return $overrides;
	}

	/**
	 * Class autoloading callback function.
	 *
	 * Uses the **MS_** namespace to autoload classes when called.
	 * Avoids creating include functions for each file in the MVC structure.
	 * **MS_** namespace ONLY will be based on folder structure in /app/
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class Uses PHP autoloader function.
	 * @return boolean
	 */
	private function class_loader( $class ) {
		static $Path_overrides = null;

		/**
		 * Actions to execute before the autoloader loads a class.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_class_loader_pre_processing', $this );

		$class = trim( $class );

		if ( null === $Path_overrides ) {
			/**
			 * Adds and Filters class path overrides.
			 *
			 * @since  1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$Path_overrides = apply_filters( 'ms_class_path_overrides', array(), $this );
		}

		if ( array_key_exists( $class, $Path_overrides ) ) {
			/**
			 * Case 1: The class-path is explicitly defined in $Path_overrides.
			 * Simply use the defined path to load the class.
			 */
			$file_path = MS_PLUGIN_BASE_DIR . '/' . $Path_overrides[ $class ];

			/**
			 * Overrides the filename and path.
			 *
			 * @since  1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$file_path = apply_filters( 'ms_class_file_override', $file_path, $this );

			if ( is_file( $file_path ) ) {
				include_once $file_path;
			}

			return true;
		} elseif ( 'MS_' == substr( $class, 0, 3 ) ) {
			/**
			 * Case 2: The class-path is not explicitely defined in $Path_overrides.
			 * Use /app/ path and class-name to build the file-name.
			 */

			$path_array = explode( '_', $class );
			array_shift( $path_array ); // Remove the 'MS' prefix from path.
			$alt_dir 	= array_pop( $path_array );
			$sub_path 	= implode( '/', $path_array );

			$filename 		= str_replace( '_', '-', 'class-' . $class . '.php' );
			$file_path 		= trim( strtolower( $sub_path . '/' . $filename ), '/' );
			$file_path_alt 	= trim( strtolower( $sub_path . '/' . $alt_dir . '/' . $filename ), '/' );
			$candidates 	= array();

			$paths 			= self::load_paths();

			foreach( $paths as $type => $path ) {
				$candidates[] = MS_PLUGIN_BASE_DIR . '/' . $path . '/' . $file_path;
				$candidates[] = MS_PLUGIN_BASE_DIR . '/' . $path . '/' . $file_path_alt;
			}

			foreach ( $candidates as $path ) {
                $current_file = basename( $path ); 
				if ( is_file( $path ) && $current_file != 'ms-loader.php' ) {
					include_once $path;
					return true;
				}
			}
		}

		return false;
    }

	/**
	 * Load plugin paths
	 *
	 * @since 1.0.4
	 *
	 * @return Array
	 */
	public static function load_paths() {
		$paths = array( 'app' );
		return apply_filters( 'ms_plugin_loader_paths', $paths );
	}
};
?>