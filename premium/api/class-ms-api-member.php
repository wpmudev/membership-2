<?php
/**
 * Member API hook
 *
 * Manages all Member API actions
 *
 * @since  1.0.4
 *
 * @package Membership2
 * @subpackage Api
 */
class MS_Api_Member extends MS_Api {

	const BASE_API_ROUTE = "/member/";

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
			self::$instance = new MS_Api_Member();
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

        register_rest_route( $namepace, self::BASE_API_ROUTE . 'list', array(
			'method' 				=> WP_REST_Server::READABLE,
			'callback' 				=> array( $this, 'list' ),
			'permission_callback' 	=> array( $this, 'validate_request' ),
            'args' 					=> array(
				'per_page' 		=> array(
					'required' 			=> false,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'int',
                    'validate_callback' => 'is_numeric',
					'description' 		=> __( 'Results per page. Defaults to 10' ),
				),
                'page' 		    => array(
					'required' 			=> true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'int',
                    'validate_callback' => 'is_numeric',
					'description' 		=> __( 'Current page' ),
				),
                'status' 	=> array(
					'required' 			=> false,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'String',
                    'validate_callback' => 'is_string',
					'description' 		=> __( 'Membership status. Eg pending, waiting, active, trial, canceled, trial_expired, expired, deactivated' ),
				),
			)
		));


        register_rest_route( $namepace, self::BASE_API_ROUTE . 'count', array(
			'method' 				=> WP_REST_Server::READABLE,
			'callback' 				=> array( $this, 'count' ),
			'permission_callback' 	=> array( $this, 'validate_request' ),
            'args' 					=> array(
                'status' 	=> array(
					'required' 			=> false,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'String',
                    'validate_callback' => 'is_string',
					'description' 		=> __( 'Membership status. Eg pending, waiting, active, trial, canceled, trial_expired, expired, deactivated' ),
				),
			)
		));

		register_rest_route( $namepace, self::BASE_API_ROUTE .  'get', array(
			'method' 				=> WP_REST_Server::READABLE,
			'callback' 				=> array( $this, 'get_member' ),
			'permission_callback' 	=> array( $this, 'validate_request' ),
			'args' 					=> array(
				'user_id' 		=> array(
					'required' 			=> true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' 				=> 'int',
                    'validate_callback' => 'is_numeric',
					'description' 		=> __( 'The user id' ),
				),
			)
		));

        register_rest_route( $namepace, self::BASE_API_ROUTE . 'subscription', array(
			array(
				'methods' 				=> WP_REST_Server::CREATABLE,
				'callback' 				=> array( $this, 'subscribe' ),
				'permission_callback' 	=> array( $this, 'validate_request' ),
				'args' 					=> array(
					'user_id' 		=> array(
						'required' 			=> true,
						'sanitize_callback' => 'sanitize_text_field',
						'type' 				=> 'int',
                        'validate_callback' => 'is_numeric',
						'description' 		=> __( 'The user id' ),
					),
					'membership_id' => array(
						'required' 			=> true,
						'sanitize_callback' => 'sanitize_text_field',
						'type' 				=> 'int',
                        'validate_callback' => 'is_numeric',
						'description' 		=> __( 'The Membership ID' ),
					),
				)
			),
			array(
				'methods' 				=> WP_REST_Server::READABLE,
				'callback' 				=> array( $this, 'get_subscription' ),
				'permission_callback' 	=> array( $this, 'validate_request' ),
				'args' 					=> array(
					'user_id' 		=> array(
						'required' 			=> true,
						'sanitize_callback' => 'sanitize_text_field',
						'type' 				=> 'int',
                        'validate_callback' => 'is_numeric',
						'description' 		=> __( 'The user id' ),
					),
					'membership_id' => array(
						'required' 			=> true,
						'sanitize_callback' => 'sanitize_text_field',
						'type' 				=> 'int',
                        'validate_callback' => 'is_numeric',
						'description' 		=> __( 'The Membership ID' ),
					),
				)
			)
		));
	}

	/**
	 * List Memberships
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return MS_Model_Member[] List of all available Memberships.
	 */
    function list( $request ) {
        $per_page 	= $request->get_param( 'per_page' );
        $page 	    = $request->get_param( 'page' );
        $status 	= $request->get_param( 'status' );
        if ( empty( $per_page ) ) {
            $per_page = 10;
        }

        if ( empty( $status ) ) {
            $status = MS_Model_Relationship::STATUS_ACTIVE;
        }

        $args = array(
			'number'                => $per_page,
			'offset'                => ( $page - 1 ) * $per_page,
            'subscription_status'   => $status
		);
        return MS_Model_Member::get_members( $args );;
    }

    /**
     * Count Members
     *
     * @param WP_REST_Request $request
     *
     * @return Long count of all members
     */
    function count( $request ) {
        $status = $request->get_param( 'status' );
        if ( empty( $status ) ) {
            $status = MS_Model_Relationship::STATUS_ACTIVE;
        }
        $args = array(
            'subscription_status'   => $status
		);
        return MS_Model_Member::get_members_count( $args );
    }

	/**
	 * Get Member
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return MS_Model_Member|WP_Error The Member model.
	 */
	function get_member( $request ) {
		$user_id 	= $request->get_param( 'user_id' );
		$user_id 	= absint( $user_id );
		$member 	= MS_Factory::load( 'MS_Model_Member', $user_id );

		if ( ! $member->is_valid() ) {
			return new WP_Error( 'member_not_found',  __( "User is not a member", "membership2" ), array( 'status' => 404 ) );
		}
		return $member;
	}

	/**
	 * Add subscription
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return MS_Model_Relationship|WP_Error The subscription object.
	 */
    function subscribe( $request ) {
        $user_id 		= $request->get_param( 'user_id' );
		$membership_id 	= $request->get_param( 'membership_id' );
		$membership 	= MS_Factory::load( 'MS_Model_Membership', intval( $membership_id ) );
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		if ( $member ) {
			$subscription = $member->add_membership( $membership->id, '' );

			// Activate free memberships instantly.
			if ( $membership->is_free() ) {
				$subscription->add_payment( 0, MS_Gateway_Free::ID, 'free' );
			}

			return $subscription;
		} else {
			return new WP_Error( 'subscription_error',  __( "User is not a member", "membership2" ), array( 'status' => 404 ) );
		}
    }

	/**
	 * Get subscription
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return MS_Model_Relationship|WP_Error The subscription object.
	 */
	function get_subscription( $request ) {
		$user_id 		= $request->get_param( 'user_id' );
		$membership_id 	= $request->get_param( 'membership_id' );
		$membership 	= MS_Factory::load( 'MS_Model_Membership', intval( $membership_id ) );

		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		if ( $member && $member->has_membership( $membership->id ) ) {
			$subscription = $member->get_subscription( $membership->id );
			return $subcription;
		} else {
			return new WP_Error( 'user_not_member',  __( "User is not a member or does not belong to the membership", "membership2" ), array( 'status' => 404 ) );
		}
	}
}
?>