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
	 * @since  1.0.4
	 */
	public function __construct() {
		add_filter( 'ms_plugin_loader_paths', array( $this, 'add_loader_paths' ) );
        add_filter( 'ms_class_path_overrides', array( $this, 'map_premium_classes' ) );
        add_action( 'ms_model_rule_prepare_class', array( $this, 'load_premium_addons' ) );
	}

    /**
     * Add premium path to the paths used to load files
     * This ensures that the premium path is first loaded
     *
     * @since  1.0.4
     * @param Array $paths - current paths relative to the plugin. Default is app
     *
     * @return Array $paths
     */
    function add_loader_paths( $paths ) {
        array_unshift( $paths, "premium" );
		return $paths;
    }

    /**
     * Map files in the premium directory to their respective class name
     *
     * @since  1.0.4
     * @param Array $overrides - current maped classes
     *
     * @return Array $overrides
     */
    function map_premium_classes( $overrides ) {
        $overrides[ 'MS_Api' ] 			= 'premium/core/class-ms-api.php';
		$overrides[ 'MS_Model_Api' ] 	= 'premium/model/class-ms-model-api.php';
		return $overrides;
    }

    /**
     * Load Premium rules in the class MS_Model_Rule
     *
     * @since  1.0.4
     */
    function load_premium_addons() {
        MS_Factory::load( 'MS_Rule_Adminside' );
		MS_Factory::load( 'MS_Rule_CptItem' );
		MS_Factory::load( 'MS_Rule_CptGroup' );
    }
}

?>