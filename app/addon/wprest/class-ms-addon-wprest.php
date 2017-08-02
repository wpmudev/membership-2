<?php
/**
 * Add-On controller for: Add WordPress Res API
 *
 * @since 1.0.4
 *
 * @package Membership2
 * @subpackage Addon
 */
class MS_Addon_WPRest extends MS_Addon {

    /**
     * Rest API Version
     *
     * @since  1.0.4
     */
    const VERSION = '1';

    /**
     * Rest API Namespace
     *
     * @since  1.0.4
     */
	const NAMESPACE = 'membership2';

    /**
	 * The Add-on ID
	 *
	 * @since  1.0.4
	 */
	const ID = 'addon_wprest';


    /**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.4
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

    /**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.4
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}


    /**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.4
	 */
	public function init() {
		if ( self::is_active() ) {
            $this->add_action( 'rest_api_init', 'register_routes' );
        }
	}

    function register_routes() {
        //Action to register route
        do_action( 'ms_addon_wprest_register_route', $this->get_namespace() );
    }

    protected function get_namespace() {
		return self::NAMESPACE . '/v' . self::VERSION;
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.4
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Rest API', 'membership2' ),
			'description' 	=> sprintf( __( 'Enable WordPress REST API at the namespace <strong>%s</strong>', 'membership2' ), $this->get_namespace()),
			'icon' 			=> 'wpmui-fa wpmui-fa-angle-double-up'
		);

		return $list;
	}
}
?>