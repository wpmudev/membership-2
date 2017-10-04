<?php
/**
 * Campaign Monitor Hustle addon provider.
 *
 * @since  1.1.2
 *
 * @uses MS_Addon_Hustle_Provider
 *
 * @package Membership2
 */
class MS_Addon_Hustle_Provider_Campaignmonitor extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'campaignmonitor';

	private $_key;

	protected function init() {
		$api_key 	= $this->get_provider_detail( 'optin_api_key' );
		if ( !empty( $api_key ) ) {
			$this->_key = $api_key;
		}
	}

	public function subscribe_user( $member, $list_id ) { 
		$api 		= new CS_REST_Subscribers( $list_id, array( 'api_key' => $this->_key ) );
		$res 		= $api->add( array(
			'EmailAddress' => $member->email,
			'Name'         => $member->first_name . ' ' . $member->last_name,
			'Resubscribe'  => true
		) );
		if ( $res->was_successful() ) {
			$this->log('success');
		} else {
			$this->log('subscription error '.$member->email );
		}
	}


	public function unsubscribe_user( $member, $list_id ) {
		$api 		= new CS_REST_Subscribers( $list_id, array( 'api_key' => $this->_key ) );
		$api->unsubscribe( $member->email );
	}

	public function is_user_subscribed( $user_email, $list_id ) {
		$api 		= new CS_REST_Subscribers( $list_id, array( 'api_key' => $this->_key ) );
		$subscribed = $api->get( $user_email );
		return $is_subscribed->was_successful();
	}
}
?>