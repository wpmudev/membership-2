<?php
/**
 * Manage Apis.
 *
 * Api classes are stored in the directory /app/api/<api_name>/
 * Each Api class must provide a file called `api-<api_name>.php`
 * This file must define class MS_Api_<api_name>.
 * This object is reponsible to initialize the the api class logic.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Api extends MS_Model_Option {

    /**
	 * List of api files to load when plugin is initialized.
	 *
	 * @since  1.0.4
	 *
	 * @var array of file-paths
	 */
	protected $api_files = array();

    /**
	 * Used by function `flush_list`
	 *
	 * @since  1.0.4
	 *
	 * @var bool
	 */
	static private $_reload_files = false;

    /**
	 * Initalize Object Hooks
	 *
	 * @since  1.0.4
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action( 'ms_model_api_flush', 'flush_list' );
	}


    /**
	 * Force to reload the add-on list
	 *
	 * Related action hooks:
	 * - ms_model_addon_flush
	 *
	 * @since  1.0.4
	 */
	public function flush_list() {
		self::$_reload_files = true;
		self::load_api_routes();
	}
    

    /**
	 * Checks the /app/api directory for a list of all api classes and loads these
	 * files.
	 *
	 * @since  1.0.4
	 */
    public static function load_api_routes() {
        $model          = MS_Factory::load( 'MS_Model_Api' );
        $content_dir    = trailingslashit( dirname( dirname( MS_Plugin::instance()->dir ) ) );
		$plugin_dir     = substr( MS_Plugin::instance()->dir, strlen( $content_dir ) );

        $api_dir    	= $plugin_dir . 'premium/api/';

        if ( empty( $model->api_files ) || self::$_reload_files ) {
            self::$_reload_files = false;
			$model->api_files = array();

			$mask = $content_dir . $api_dir . '*/class-ms-api-*.php';
			$apis = glob( $mask );

			foreach ( $apis as $file ) {
				$apiclass = basename( $file );
				if ( empty( $model->api_files[ $apiclass ] ) ) {
					$api_path = substr( $file, strlen( $content_dir ) );
					$model->api_files[ $apiclass ] = $api_path;
				}
			}

			$model->save();
        }


        // Loop all recignized Add-ons and initialize them.
		foreach ( $model->api_files as $file ) {
			$api_file = $content_dir . $file;

			// Get class-name from file-name
			$class = basename( $file );
			$class = str_replace( '.php', '', $class );
			$class = implode( '_', array_map( 'ucfirst', explode( '-', $class ) ) );
			$class = substr( $class, 6 ); // remove 'Class_' prefix

			if ( file_exists( $api_file ) ) {
				if ( ! class_exists( $class ) ) {
					try {
						include_once $api_file;
					} catch ( Exception $ex ) {
					}
				}

				if ( class_exists( $class ) ) {
					MS_Factory::load( $class );
				}
			}
		}
    }
}
?>