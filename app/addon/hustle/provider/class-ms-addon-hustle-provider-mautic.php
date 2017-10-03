<?php

class MS_Addon_Hustle_Provider_Mautic extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'mautic';

	/**
	* @var (string) Mautic installation URL
	**/
	private $base_url;
	
	/**
	* @var (string) The username use to login.
	**/
	private $username;

	/**
	* @var (string) The password use to authenticate.
	**/
	private $password;

	/**
	* @var (object) \MauticApi class instance
	**/
	private $api;
	
	/**
	* @var (object) \Mautic\Auth\ApiAuth class instance.
	**/
	private $auth;


	/**
	 * API object
	 *
	 * @var Object
	 */
	static protected $_api = null;

	/**
	 * Api class
	 *
	 * @return Object|Exception
	 */
	protected function api() {
		try {
			$base_url = $this->get_provider_detail( 'optin_url' );
			$username = $this->get_provider_detail( 'optin_username' );
			$password = $this->get_provider_detail( 'optin_password' );

			if ( ! empty( $base_url ) && ! empty( $username ) && ! empty( $password ) ) {
				$this->base_url = $base_url;
				$this->username = $username;
				$this->password = $password;

				$params = array(
					'baseUrl' 	=> $this->base_url,
					'userName' 	=> $this->username,
					'password' 	=> $this->password,
				);

				$initAuth 	= new Mautic\Auth\ApiAuth();
				$this->auth = $initAuth->newAuth( $params, 'BasicAuth' );
				$this->api 	= new Mautic\MauticApi( $this->auth, $this->base_url );

				return $this->api;
			} else {
				return new WP_Error( 'broke', __( "Could not initiate API. Please check your details", "membership2" ) );
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'broke', __( "Could not initiate API. Please check your details", "membership2" ) );
		}
	}

	protected function get_api(){
		if ( empty( self::$_api ) ) {
			$api = $this->api();
			if ( ! is_wp_error( $api ) ) {
				self::$_api = $api;
			} else {
				return false;
			}
		}
		return self::$_api;
	}

	public function subscribe_user( $member, $list_id ) { 
		$api = $this->get_api();
		$err = new WP_Error();
		if ( is_email( $member->email ) && $api ) {
			$contactApi = $api->newApi( 'contacts', $this->auth, $this->base_url );
			try {
				$geo 	= new Opt_In_Geo();
				$data 	= array(
					'firstname' => $member->first_name,
					'lastname'  => $member->last_name,
					'email'		=> $member->email,
					'ipAddress' => $geo->get_user_ip()
				);
				
				$res = $contactApi->create( $data );

				if ( $res && ! empty( $res['contact'] ) ) {
					$contact_id = $res['contact']['id'];

					// Double check custom fields
					if ( ! empty( $res['contact']['fields'] ) && ! empty( $res['contact']['fields']['core'] ) ) {
						$found_missing = 0;

						$contact_fields = array_keys( $res['contact']['fields']['core'] );
						$common_fields 	= array( 'firstname', 'lastname', 'email', 'ipAddress' );

						foreach ( $data as $key => $value ) {
							// Check only uncommon fields
							if ( ! in_array( $key, $common_fields ) && ! in_array( $key, $contact_fields ) ) {
								$found_missing++;
							}
						}
					}
					$member->set_gateway_profile( self::$PROVIDER_ID, 'contact_id', $contact_id );
					return $contact_id;
				} else {
					$err->add( 'susbscribe_error', __( 'Something went wrong. Please try again', 'membership2' ) );
				}
			} catch( Exception $e ) {
				$error = $e->getMessage();
				$err->add( 'subscribe_error', $error );
			}
		} else {
			$err->add( 'subscribe_error',  __( 'Something went wrong. Please try again', 'membership2' ) );
		}
		return $err;
	}

	public function unsubscribe_user( $member, $list_id ) {
		$api = $this->get_api();
		$contact_id = $member->get_gateway_profile( self::$PROVIDER_ID, 'contact_id' );
		if ( $contact_id && $api ) {
			$contactApi = $api->newApi( 'contacts', $this->auth, $this->base_url );
			try {
				$contactApi->delete( $contact_id );
			} catch( Exception $e ) {
			}
		}
	}

	
	public function is_user_subscribed( $user_email, $list_id ) {
		$api = $this->get_api();
		if ( $api ) {
			$contactApi = $api->newApi( 'contacts', $this->auth, $this->base_url );
			try {
				$res = $contactApi->getList( $email, 0, 1000 );

				if ( ! empty( $res ) && ! empty( $res['total'] ) ) {
					return true;
				}
			} catch( Exception $e ) {
				return false;
			}
		}
		return false;
	}
}
?>