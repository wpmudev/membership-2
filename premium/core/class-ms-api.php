<?php
/**
 * Abstract class for all Rest Api Endpoints.
 *
 * All api classes will extend or inherit from the MS_Api class.
 * Methods of this class will control the flow and behaviour of the plugin
 * by using MS_Model objects.
 *
 * @since  1.0.4
 *
 * @uses MS_Model
 *
 * @package Membership2
 */
class MS_Api extends MS_Hooker {

	/**
	 * Pass Key
	 *
	 * @since  1.0.4
	 */
	protected $pass_key = null;

    /**
	 * MS_Model Contstuctor
	 *
	 * @since  1.0.4
	 */
	public function __construct() {

		/**
		 * Actions to execute when constructing the parent Model.
		 *
		 * @since  1.0.4
		 * @param object $this The MS_Model object.
		 */
		do_action( 'ms_api_construct', $this );

        $this->add_action( 'ms_addon_wprest_register_route', 'register_route', 10, 2 );
	}

	/**
	 * Register the route
	 *
	 * @param String $namepace - the parent namespace
	 * @param String $pass_key - the api passkey set up in the settings
	 *
	 * @since 1.0.4
	 */
    function register_route( $namepace, $pass_key ) {
		$this->pass_key = $pass_key;
		$this->set_up_route( $namepace );
    }

	/**
	 * Set up the api routes
	 *
	 * @param String $namepace - the parent namespace
	 *
	 * @since 1.0.4
	 */
	function set_up_route( $namepace ) {

	}

	/**
	 * Validate the request
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
    function validate_request( $request ) {
		$pass_key = $request->get_param( 'pass_key' );
		if ( $pass_key != $this->pass_key ) {
			return new WP_Error( 'rest_user_cannot_view',  __( "Invalid request, you are not allowed to make this request", "membership2" ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
}
?>