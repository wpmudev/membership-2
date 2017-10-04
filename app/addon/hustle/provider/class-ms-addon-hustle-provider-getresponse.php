<?php
/**
 * Get Response Hustle addon provider.
 *
 * @since  1.1.2
 *
 * @uses MS_Addon_Hustle_Provider
 *
 * @package Membership2
 */
class MS_Addon_Hustle_Provider_GetResponse extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'getresponse';

	private $_api_key;
	
	private $_endpoint = "https://api.getresponse.com/v3/";
	
	protected function init() {
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		if ( !empty( $api_key ) ) {
			$this->_api_key = $api_key;
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
        $url 	= trailingslashit( $this->_endpoint )  . $action;

        $_args 	= array(
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
        if ( ! is_wp_error( $res ) && is_array( $res ) && $res['response']['code'] <= 204 ) {
            return json_decode( wp_remote_retrieve_body( $res ) );
		}

		if ( is_wp_error( $res ) ) {
			return $res;
		}

        $err = new WP_Error();
        $err->add($res['response']['code'], $res['response']['message'] );
        return  $err;
	}
	

	/**
     * Sends rest GET request
     *
     * @param $action
     * @param array $args
     * @return array|mixed|object|WP_Error
     */
	private function _get( $action, $args = array() ) {
        return $this->_request( "GET", $action, $args );
    }

    /**
     * Sends rest POST request
     *
     * @param $action
     * @param array $args
     * @return array|mixed|object|WP_Error
     */
    private function _post( $action, $args = array() ) {
        return $this->_request( "POST", $action, $args );
	}


	/**
     * Sends rest POST request
     *
     * @param $action
     * @param array $args
     * @return array|mixed|object|WP_Error
     */
	 private function _delete( $action, $args = array() ) {
        return $this->_request( "DELETE", $action, $args );
	}
	
	public function subscribe_user( $member, $list_id  ) {
		$geo 	= new Opt_In_Geo();
		$data 	= array(
			'email' 		=> $member->email,
			'first_name' 	=> $member->first_name,
			'last_name' 	=> $member->last_name,
			'dayOfCycle'    => apply_filters( 'ms_hustle_getresponse_dayofcycle', '0' ),
			'campaign'      => array(
            	'campaignId' => $list_id
            ),
            'ipAddress'     => $geo->get_user_ip()
		);
        $res =  $this->_post( "contacts", array(
            "body" =>  $data
		));

        return empty( $res ) ? true : $res;
	}
	

	public function unsubscribe_user( $member, $list_id ) {
		$geo 		= new Opt_In_Geo();
		$contact_id = $this->get_email_contact_id( $member->email );
		if ( $contact_id ) {
			$res =  $this->_delete( "contacts/" .$contact_id, array(
				"body" =>  array(
					'messageId' => $list_id,
					'ipAddress' => $geo->get_user_ip()
				)
			));
		}
	}


	public function is_user_subscribed( $user_email, $list_id ) {
		$data = array(
			'query' => $user_email,
			'fields' => 'campaign'
		);
		$res =  $this->_get( "contacts", array(
            "body" =>  $data
		));
		if ( !is_wp_error( $res ) ) {
			$response = (array) $res;
			if ( count( $response ) > 0 ) {
				if ( isset( $response[0]['contactId'] ) && isset( $response[0]['campaign'] ) ) {
					$campaign = $response[0]['campaign'];
					if ( $campaign->campaignId == $list_id ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if email exists.
	 * Subscription takes long, so we have to get the contact id after
	 *
	 * @return bool|int
	 */
	private function get_email_contact_id( $user_email ) {
		$data = array(
			'query' => $user_email,
			'fields' => 'campaign'
		);
		$res =  $this->_get( "contacts", array(
            "body" =>  $data
		));
		if ( !is_wp_error( $res ) ) {
			$response = (array) $res;
			if ( count( $response ) > 0 ) {
				if ( isset( $response[0]['contactId'] ) ) {
					return $response[0]['contactId'];
				}
			}
		}
		return false;
	}
}
?>