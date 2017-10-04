<?php

class MS_Addon_Hustle_Provider_Sendinblue extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'sendinblue';

	public $api_key;
	public $base_url = 'https://api.sendinblue.com/v2.0';

	protected function init() {
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		if ( !empty( $api_key ) ) {
			$this->api_key	 = $api_key;
		}
	}


	/**
	* Do CURL request with authorization
	*/
	private function do_request( $resource, $method, $input ){
		$called_url = $this->base_url . "/" . $resource;
		$ssl_verify = true;
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			// Windows only over-ride
			$ssl_verify = false;
		}

		$args = array(
			'method'       => $method,
			'sslverify'    => apply_filters( 'mc_hustle_sendinblue_sslverify', $ssl_verify ),
			'headers'      => array(
				'api-key'      => $this->api_key,
				'Content-Type' => 'application/json'),
		);
		$args['body'] = $input;
		
		$response   = wp_remote_request( $called_url, $args );
		if ( !is_wp_error( $response ) ) {
			$data    = wp_remote_retrieve_body( $response );
			
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			return json_decode( $data,true );
		}
		
		return $response;
	}

	public function get( $resource, $input = "" ) {
		return $this->do_request( $resource, "GET", $input );
	}

	public function put( $resource, $input ) {
		return $this->do_request( $resource, "PUT", json_encode( $input ) );
	}

	public function post( $resource, $input ) {
		return $this->do_request( $resource,"POST", json_encode( $input ) );
	}

	public function delete( $resource, $input = " ") {
		return $this->do_request( $resource, "DELETE", $input );
	}

	public function subscribe_user( $member, $list_id ) { 
		$data = array(
			'email' 		=> $member->email,
			'first_name' 	=> $member->first_name,
			'last_name' 	=> $member->last_name,
			'listid'		=> $list_id 
		);
		$resp = $this->post( "user/createdituser", $data );
		if ( is_wp_error( $res ) ) {
			$this->log( $res->get_error_message() );
		}
	}

	public function unsubscribe_user( $member, $list_id ) {
		$this->delete( "user/" . $member->email );
	}

	public function is_user_subscribed( $user_email, $list_id ) {
		$contact = $this->get( "user/". $user_email );
		if ( !is_wp_error( $contact ) ) {
			if ( $contact['code'] != 'failure' || ( isset( $contact['data'] ) && isset( $contact['data']['listid'] ) ) ) {
				if ( in_array( $list_id, $contact['data']['listid'] ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
?>