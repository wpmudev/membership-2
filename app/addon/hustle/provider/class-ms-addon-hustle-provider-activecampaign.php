<?php

class MS_Addon_Hustle_Provider_Activecampaign extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'activecampaign';

	private $_url;

    private $_key;

	/**
	 * Api class
	 *
	 * @return Object|Exception
	 */
	protected function api() {
		$base_url 	= $this->get_provider_detail( 'optin_url' );
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		if ( ! empty( $base_url ) && ! empty( $api_key ) && function_exists( 'curl_init' ) ) {
			$this->_url = trailingslashit( $base_url ) . 'admin/api.php';
			$this->_key = $api_key;
		} else {
			return new WP_Error( 'broke', __( "Could not initiate API. Please check your details", "membership2" ) );
		}
	}

	/**
     * Sends request to the endpoint url with the provided $action
     *
     * @param string $verb
     * @param string $action rest action
     * @param array $args
     * @return object|WP_Error
     */
	private function _request( $verb = "GET", $action, $args = array() ){
        $url = $this->_url;

        $apidata = array(
            'api_action' 	=> $action,
            'api_key' 		=> $this->_key,
            'api_output' 	=> 'serialize',
        );

        $url 		= add_query_arg( $apidata, $url );
        $request 	= curl_init( $url ); // initiate curl object
        curl_setopt( $request, CURLOPT_HEADER, false ); // set to 0 to eliminate header info from response
        curl_setopt( $request, CURLOPT_RETURNTRANSFER, true ); // Returns response data instead of TRUE(1)
        curl_setopt( $request, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, FALSE );


        if ( array() !== $args ) {
            if ( "POST" === $verb ) {
                curl_setopt( $request, CURLOPT_POSTFIELDS, http_build_query( array_merge( $apidata, $args ) ) );
                curl_setopt( $request, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded'
                ));
            } else {
                $url = add_query_arg( $args, $url );
                curl_setopt( $request, CURLOPT_URL, $url );
            }
        }

        $response = ( string )curl_exec( $request ); //execute curl fetch and store results in $response

        curl_close( $request );

        return unserialize( $response );

	}
	

	/**
     * Sends rest GET request
     *
     * @param $action
     * @param array $args
     * @return array|mixed|object|WP_Error
     */
	private function _get( $action, $args = array() ){
        return $this->_request( "GET", $action, $args );
    }

    /**
     * Sends rest POST request
     *
     * @param $action
     * @param array $args
     * @return array|mixed|object|WP_Error
     */
    private function _post( $action, $args = array()  ){
        return $this->_request( "POST", $action, $args );
	}
	


	public function subscribe_user( $member, $list_id ){
		$data = array(
			'email' 		=> $member->email,
			'first_name' 	=> $member->first_name,
			'last_name' 	=> $member->last_name
		);
		if ( (int) $list_id > 0 ) {
			$data['p'] 		= array( $list_id => $list_id );
			$data['status'] = array( $list_id => 1 );
			$res 			= $this->_post( 'contact_sync', $data );
		} else {
			$res 			= $this->_post( 'contact_add', $data );
		}

		if ( is_array( $res ) && isset( $res['result_code'] ) && $res['result_code'] == 'SUCCESS' ){
			return __( 'Successful subscription', 'membership2' );
		} else if ( empty( $res ) ) {
			return __( 'Successful subscription', 'membership2' );
		}

		if ( is_array( $res ) && isset( $res['result_code'] ) ){
			if( $res['result_code'] == 'FAILED' ){
				$origData['error'] = ! empty( $res['result_message'] ) ? $res['result_message'] : __( 'Unexpected error occurred.', 'membership2' );
				$this->log( $origData );
			}
		}

		return $res;
	}
	

	public function unsubscribe_user( $member, $list_id ) {
		$data = array(
			'email' 		=> $member->email,
			'first_name' 	=> $member->first_name,
			'last_name' 	=> $member->last_name
		);
		
		$data['p'] 		= array( $list_id => $list_id );
		$data['status'] = array( $list_id => 2 );
		$res 			= $this->_post( 'contact_sync', $data );

		if ( is_array( $res ) && isset( $res['result_code'] ) && $res['result_code'] == 'SUCCESS' ){
			return __( 'Successful subscription', 'membership2' );
		} else if ( empty( $res ) ) {
			return __( 'Successful subscription', 'membership2' );
		}

		if ( is_array( $res ) && isset( $res['result_code'] ) ){
			if( $res['result_code'] == 'FAILED' ){
				$origData['error'] = ! empty( $res['result_message'] ) ? $res['result_message'] : __( 'Unexpected error occurred.', 'membership2' );
				$this->log( $origData );
			}
		}

		return $res;
	}

	function is_user_subscribed( $user_email, $list_id ) {
		$res = $this->_post( 'contact_view_email', array( 'email' => $user_email ) );

		return ! empty( $res ) && ! empty( $res['id'] ) ? true : false;
	}
}
?>