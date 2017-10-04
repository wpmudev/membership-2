<?php

class MS_Addon_Hustle_Provider_Madmimi extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'mad_mimi';

	private $_user_name;
    private $_api_key;

	private $_endpoint = "https://api.madmimi.com/";
	
	protected function init() {
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		$username 	= $this->get_provider_detail( 'optin_username' );
		if ( !empty( $api_key ) && !empty( $username ) ) {
			$this->_api_key	 = $api_key;
			$this->_user_name = $username;
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

        $url = add_query_arg( array(
            'api_key' 	=> $this->_api_key,
            'username' 	=> $this->_user_name,
        ), $url );

        $_args = array(
            "method" => $verb
        );

        if( array() !== $args ){
            if( "GET" === $verb ){
                $url = add_query_arg( $args, $url );
            }else{
                $_args['body'] = json_encode( $args['body'] );
            }
        }

		$res = wp_remote_request( $url, $_args );
		
		if ( !is_wp_error( $res ) && is_array( $res ) ) {

			$res_code = wp_remote_retrieve_response_code( $res );
			if( $res_code <= 204 ) {
				libxml_use_internal_errors( true );
				return simplexml_load_string( wp_remote_retrieve_body( $res ) );
			}

			$err = new WP_Error();
			$err->add( $res_code, $res['response']['message'] );
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
	

	public function subscribe_user( $member, $list_id ) { 
		$data = array(
			'email' 		=> $member->email,
			'name' 			=> $member->first_name . " " . $member->last_name
		);

		$action = add_query_arg( $data, "/audience_members" );
		
		if( !empty( $list_id ) ){
			$action = "audience_lists/" . $list_id ."/add?";
			$action = add_query_arg( $data, $action );
		}

		$res =  $this->_post( $action );

		return empty( $res ) ? true : $res;
	}


	public function unsubscribe_user( $member, $list_id ) {
		
	}


	public function is_user_subscribed( $user_email, $list_id ) {
		$action = '/audience_members/search.xml?query=' . $user_email;
		$res 	= $this->_get( $action );

		if ( is_object( $res ) && ! empty( $res->member ) && $user_email == $res->member->email ) {
			return true;
		}
		return false;
	}
}
?>