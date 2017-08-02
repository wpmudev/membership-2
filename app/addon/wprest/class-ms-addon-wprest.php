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
     * Rest API Namespace
     *
     * @since  1.0.4
     */
	const API_NAMESPACE = 'membership2/v1';
	

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
	 * Plugin Settings
	 *
	 * @since  1.0.4
	 */
	protected $plugin_settings = null;


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
			$this->plugin_settings = MS_Factory::load( 'MS_Model_Settings' );
            $this->add_action( 'rest_api_init', 'register_routes' );
        }
	}
	
	/**
	 * Register API routes
	 *
	 * @since  1.0.4
	 */
    function register_routes() {

        //Action to register route
		register_rest_route( $this->get_namespace(), '/membership/list', array(
			'method' 	=> WP_REST_Server::READABLE,
			'callback' 	=> array( $this, 'list_memberships' ),
			'args' => array(
				'pass_key' 	=> array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' => 'string',
					'description' => __( 'API Access Code' ),
				)
			)
		));

		register_rest_route( $this->get_namespace(), '/membership/assign', array(
			'method' 	=> WP_REST_Server::READABLE,
			'callback' 	=> array( $this, 'add_subscription' ),
			'args' => array(
				'pass_key' 	=> array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' => 'string',
					'description' => __( 'API Access Code' ),
				),
				'user_id' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' => 'int',
					'description' => __( 'The user id' ),
				),
				'membership_id' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
					'type' => 'int',
					'description' => __( 'The Membership ID' ),
				),
			)
		));

        do_action( 'ms_addon_wprest_register_route', $this->get_namespace() );
    }

	/**
	 * API namespace
	 *
	 * @since  1.0.4
	 *
	 * @return String
	 */
    protected function get_namespace() {
		return $this->plugin_settings->wprest['api_namespace'];
	}

	/**
	 * List Memberships
	 *
	 * @since  1.0.4
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	function list_memberships( $request ) {
		$this->validate_passkey( $request );
		return $this->api->list_memberships();
	}

	/**
	 * Add Subscription
	 *
	 * @param WP_REST_Request $request
	 */
	function add_subscription( $request ){
		$this->validate_passkey( $request );
		$user_id 		= $request->get_param( 'user_id' );
		$membership_id 	= $request->get_param( 'membership_id' );
		return $this->api->add_subscription( $user_id, $membership_id );
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.4
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
	
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Rest API', 'membership2' ),
			'description' 	=> __( 'Enable WordPress REST API', 'membership2' ),
			'footer' 		=> sprintf( '<i class="dashicons dashicons dashicons-admin-settings"></i> %s', __( 'Options available', 'membership2' ) ),
			'icon' 			=> 'wpmui-fa wpmui-fa-angle-double-up',
			'class' 		=> 'ms-options',
			'details' 		=> array(
				array(
					'id' 		=> 'api_namespace',
					'before' 	=> get_rest_url(),
					'type' 		=> MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' 	=> __( 'API namespace:', 'membership2' ),
					'value' 	=> $settings->wprest['api_namespace'],
					'data_ms' 	=> array(
						'field' 	=> 'api_namespace',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					),
				),
				array(
					'id' 		=> 'api_passkey',
					'type' 		=> MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' 	=> __( 'API passkey:', 'membership2' ),
					'value' 	=> $settings->wprest['api_passkey'],
					'data_ms' 	=> array(
						'field' 	=> 'api_passkey',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					),
				),
			)
		);

		return $list;
	}

	/**
	 * @param string $client_id
	 *
	 * @return bool
	 */
	function validate_passkey( $request, $param = 'pass_key' ) {
		$pass_key = $request->get_param( $param );
		if ( $pass_key != $this->plugin_settings->wprest['api_passkey'] ) {
			wp_send_json_error( __( "Invalid request, you are not allowed to make this request", "membership2" ) );
		}
	}
}
?>