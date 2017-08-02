<?php
/**
 * Membership API hook
 *
 * Manages all Membership API actions
 *
 * @since  1.0.4
 *
 * @package Membership2
 * @subpackage Api
 */
class MS_Api_Membership extends MS_Api {

    /**
	 * Membership API Object
	 *
	 * @since  1.0.4
	 */
	protected $api = null;

    /**
	 * Singletone instance of the plugin.
	 *
	 * @since  1.0.4
	 *
	 * @var MS_Plugin
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
	 * @return MS_Api
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new MS_Api_Membership();
		}

		return self::$instance;
	}

    /**
	 * Set up the api routes
	 *
	 * @param String $namepace - the parent namespace
	 *
	 * @since 1.0.4
	 */
	function set_up_route( $namepace ) {

        $this->api = ms_api();

        register_rest_route( $namepace, '/membership/list', array(
			'method' 				=> WP_REST_Server::READABLE,
			'callback' 				=> array( $this, 'list' ),
			'permission_callback' 	=> array( $this, 'validate_request' )
		));

        register_rest_route( $namepace, '/membership/subscribe', array(
			'methods' 				=> WP_REST_Server::CREATABLE,
			'callback' 				=> array( $this, 'subscribe' ),
			'permission_callback' 	=> array( $this, 'validate_request' ),
			'args' 					=> array(
				'user_id' 		=> array(
					'required' 			=> true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'int',
					'description' 		=> __( 'The user id' ),
				),
				'membership_id' => array(
					'required' 			=> true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'int',
					'description' 		=> __( 'The Membership ID' ),
				),
			)
		));
	}

    function list( $request ) {
        return $this->api->list_memberships();
    }

    function subscribe( $request ) {
        $user_id 		= $request->get_param( 'user_id' );
		$membership_id 	= $request->get_param( 'membership_id' );
		return $this->api->add_subscription( $user_id, $membership_id );
    }
}
?>