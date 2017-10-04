<?php
/**
 * Convert kit Hustle addon provider.
 *
 * @since  1.1.2
 *
 * @uses MS_Addon_Hustle_Provider
 *
 * @package Membership2
 */
class MS_Addon_Hustle_Provider_Convertkit extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'convertkit';

	private $_api_key;
	private $_api_secret;
	private $_endpoint = 'https://api.convertkit.com/v3/';

	protected function init(){
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		$api_secret = $this->get_provider_detail( 'optin_api_secret' );
		if ( ! empty( $api_key ) && ! empty( $api_secret ) ) {
			$this->_api_key 	= $api_key;
			$this->_api_secret 	= $api_secret;
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
		$url = trailingslashit( $this->_endpoint )  . $action;
		
        $_args = array(
            "method" 	=> $verb,
            "headers" 	=>  array(
				'X-Auth-Token' => 'api-key '. $this->_api_key,
                'Content-Type' => 'application/json;charset=utf-8'
            )
        );

        if ( "GET" === $verb ) {
            $url .= ( "?" . http_build_query( $args ) );
        } else {
            $_args['body'] = json_encode( $args['body'] );
        }

		$res = wp_remote_request( $url, $_args );
		
		if ( !is_wp_error( $res ) && is_array( $res ) ) {

			if ( $res['response']['code'] <= 204 ) {
				return json_decode(  wp_remote_retrieve_body( $res ) );
			}
			$err = new WP_Error();
			$err->add( $res['response']['code'], $res['response']['message'] );
			return  $err;
		}

		return  $res;
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


	/**
     * Sends rest PUT request
     *
     * @param $action
     * @param array $args
     * @return array|mixed|object|WP_Error
     */
	private function _put( $action, $args = array()  ){
        return $this->_request( "PUT", $action, $args );
	}
	

	public function subscribe_user( $member, $list_id ) { 
		$geo = new Opt_In_Geo();
		$subscribe_data = array(
			"api_key" 	=> $this->_api_key,
			"name" 		=> $member->first_name . ' ' . $member->last_name,
			"email" 	=> $member->email,
			"fields" 	=> array(
				"ip_address" => $geo->get_user_ip()
			)
		);
		$res =  $this->_post( "forms/". $list_id ."/subscribe", array(
            "body" =>  $data
        ));
	}

	public function unsubscribe_user( $member, $list_id ) {
		$geo = new Opt_In_Geo();
		$subscribe_data = array(
			"api_key" 	=> $this->_api_key,
			"email" 	=> $member->email
		);
		$res =  $this->_put( "unsubscribe", array(
            "body" =>  $data
        ));
	}

	public function is_user_subscribed( $user_email, $list_id ) {
		$url = 'subscribers';
		$args = array(
			'api_key' 		=> $this->_api_key,
			'api_secret' 	=> $this->_api_secret,
			'email_address' => $user_email,
		);

		$res = $this->_get( $url, $args );

		return ! is_wp_error( $res ) && ! empty( $res->subscribers ) ? array_shift( $res->subscribers ) : false;
	}
}
?>