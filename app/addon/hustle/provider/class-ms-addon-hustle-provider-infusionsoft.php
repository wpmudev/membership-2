<?php

/**
 * Infusionsoft Hustle addon provider.
 *
 * @since  1.1.2
 *
 * @uses MS_Addon_Hustle_Provider
 *
 * @package Membership2
 */
class MS_Addon_Hustle_Provider_Infusionsoft extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'infusionsoft';

	/**
     * @var string $_api_key
     */
	private $_api_key;
	 
	/**
	* @var string $_app_name
	*/
	private $_app_name;

	/**
	* @var object $xml SimpleXMLElement class instance
	**/
	var $xml;

	/**
	* @var object $params SimpleXMLElement params node.
	**/
	var $params;

	/**
	* @var object $struct SimpleXMLElement struct node.
	**/
	var $struct;

	protected function init() {
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		$app_name 	= $this->get_provider_detail( 'optin_account_name' );
		if ( !empty( $api_key ) && !empty( $app_name ) ) {
			$this->_api_key	 = $api_key;
			$this->_app_name = $app_name;
		}
	}

	/**
     * Dispatches the request to the Infusionsoft server
     *
     * @param $query_str
     * @return Opt_In_Infusionsoft_XML_Res|WP_Error
     */
	private function _request( $query_str ){
        $url 	= esc_url_raw( 'https://' . $this->_app_name . '.infusionsoft.com/api/xmlrpc' );

        $headers = array(
            "Content-Type" =>  "text/xml",
            "Accept-Charset" => "UTF-8,ISO-8859-1,US-ASCII",
        );

        $res = wp_remote_post( $url, array(
            'sslverify'  	=> false,
            "headers" 		=> $headers,
            "body" 			=> $query_str
        ));

        $code 		= wp_remote_retrieve_response_code( $res );
        $message 	= wp_remote_retrieve_response_message( $res );
        $err 		= new WP_Error();

        if( $code < 204 ){
            $xml = simplexml_load_string( wp_remote_retrieve_body( $res ), "Opt_In_Infusionsoft_XML_Res" );

            if( empty( $xml ) ){
				$error = __( "Invalid app name, please check app name and try again", "membership2" );
				$this->log( $error );
                $err->add( "invalid_app_name", $error );
                return $err;
            }

            if( $xml->is_faulty() ) {
				$this->log( $xml->get_fault() );
				$err->add( "something_went_wrong", $xml->get_fault() );
				return $err;
			}

            return $xml;
        }

        $err->add( $code, $message );
        return $err;
    }

	function set_method( $method_name ) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?><methodCall></methodCall>';
		$this->xml = new SimpleXMLElement( $xml );
		$this->xml->addChild( 'methodName', $method_name );
		$this->params = $this->xml->addChild( 'params' );
		$this->set_param( $this->_api_key );
		$this->struct = false;
	}

	function set_param( $value, $type = 'string' ) {
		$param = $this->params->addChild( 'param' );
		return $param->addChild( 'value' )->addChild( $type, $value );
	}

	function set_member( $name, $value = '', $type = 'string' ) {
		if ( ! $this->struct ) {
			$this->struct = $this->params->addChild( 'param' )->addChild( 'value' )->addChild( 'struct' );
		}

		$member = $this->struct->addChild( 'member' );
		$member->addChild( 'name', $name );
		if ( ! empty( $value ) ) {
			$member->addChild( 'value' )->addChild( $type, $value );
		}
	}


	public function subscribe_user( $member, $list_id  ) {
		$contact = array(
			'Email' 		=> $member->email,
			'FirstName' 	=> $member->first_name,
			'LastName' 		=> $member->last_name
		);
		$this->opt_in_email( $member->email );
		$this->set_method( 'ContactService.add' );
		
		foreach ( $contact as $key => $value ) {
			$this->set_member( $key, $value );
		}

		$res = $this->_request( $this->xml->asXML() );

		if ( is_wp_error( $res ) ) {
			return $res;
		} else {
			$contact_id = $res->get_value( 'i4' );
			$this->add_tag_to_contact( $contact_id, $list_id );
			return true;
		}
	}

	public function unsubscribe_user( $member, $list_id ) {
		
	}

	public function is_user_subscribed( $user_email, $list_id ) {
		$this->set_method( 'ContactService.findByEmail' );
		$this->set_param( $user_email );
		$data = $this->params->addChild( 'param' )->addChild( 'value' )->addChild( 'array' )->addChild( 'data' );
		$data->addChild( 'value' )->addChild( 'string', 'Id' );

		$res = $this->_request( $this->xml->asXML() );

		if ( ! is_wp_error( $res ) ) {
			$subscriber_id = $res->get_value( 'array.data.value.struct.member.value.i4' );

			return (int) $subscriber_id > 0;
		}

		return false;
	}

	/**
	 * Opt-in email
	 * This allows the email to be marketable
	 *
	 * @param String $email
	 *
	 * @return WP_Error|Xml
	 */
	private function opt_in_email( $email ) {
		$site_name = get_bloginfo( 'name' );
		$this->set_method( 'ContactService.findByEmail' );
		$this->set_param( $email );
		$this->set_param( $site_name );
		$res = $this->_request( $this->xml->asXML() );
		return $res;
	}

	/**
     * Adds contact with $contact_id to group with $group_id
     *
     * @param $contact_id
     * @param $tag_id
     * @return Opt_In_Infusionsoft_XML_Res|WP_Error
     */
	private function add_tag_to_contact( $contact_id, $tag_id ){
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <methodCall>
                  <methodName>ContactService.addToGroup</methodName>
                  <params>
                    <param>
                      <value>
                        <string>{$this->_api_key}</string>
                      </value>
                    </param>
                    <param>
                      <value>
                        <int>$contact_id</int>
                      </value>
                    </param>
                    <param>
                      <value>
                        <int>$tag_id</int>
                      </value>
                    </param>
                  </params>
                </methodCall>";

        $res = $this->_request( $xml );

        if( is_wp_error( $res ) )
            return $res;

        return $res->get_value();

    }
}
?>