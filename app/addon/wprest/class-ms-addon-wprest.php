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
    const API_VERSION = '1';

    /**
     * Rest API Namespace
     *
     * @since  1.0.4
     */
	const API_NAMESPACE = 'membership2';

    /**
	 * The Add-on ID
	 *
	 * @since  1.0.4
	 */
	const ID = 'addon_wprest';

	/**
	 * Membership API Object
	 *
	 * @since  1.0.4
	 */
	protected $api = null;


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
			$this->api = ms_api();
            $this->add_action( 'rest_api_init', 'register_routes' );
        }
	}

    function register_routes() {
        //Action to register route
		register_rest_route( $this->get_namespace(), '/memberships', array(
			'method' 	=> WP_REST_Server::READABLE,
			'callback' 	=> array( $this, 'list_memberships' )
		));

        do_action( 'ms_addon_wprest_register_route', $this->get_namespace() );
    }

	/**
	 * Current namespace
	 *
	 * @return String
	 */
    protected function get_namespace() {
		return apply_filters( 'ms_membership_rest_namespace', self::API_NAMESPACE . '/v' . self::API_VERSION );
	}

	/**
	 * List Memberships
	 */
	function list_memberships() {
		return $this->api->list_memberships();
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