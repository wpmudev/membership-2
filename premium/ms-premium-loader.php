<?php
/**
 * Premium Hook Loader
 * Load premium classes and paths using hooks
 *
 * @since  1.0.4
 */
class MS_Premium_Loader {

    /**
	 * Singletone instance of the plugin.
	 *
	 * @since  1.0.4
	 *
	 * @var MS_Premium_Loader
	 */
	private static $instance = null;

    /**
	 * Returns singleton instance of the plugin.
	 *
	 * @since  1.0.4
	 *
	 * @static
	 * @access public
	 *
	 * @return MS_Premium_Loader
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new MS_Premium_Loader();
		}

		return self::$instance;
	}

    /**
	 * Plugin constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		add_filter( 'ms_plugin_loader_paths', array( $this, 'add_loader_paths' ) );
        add_filter( 'ms_class_path_overrides', array( $this, 'map_premium_classes' ) );
        add_action( 'ms_model_rule_prepare_class', array( $this, 'load_premium_addons' ) );
	}

    function add_loader_paths( $paths ) {
        array_unshift( $paths, "premium" );
		return $paths;
    }

    function map_premium_classes( $overrides ) {
        $overrides[ 'MS_Api' ] 			= 'premium/core/class-ms-api.php';
		$overrides[ 'MS_Model_Api' ] 	= 'premium/model/class-ms-model-api.php';
		return $overrides;
    }

    function load_premium_addons() {
        MS_Factory::load( 'MS_Rule_Adminside' );
		MS_Factory::load( 'MS_Rule_CptItem' );
		MS_Factory::load( 'MS_Rule_CptGroup' );
    }
}

?>