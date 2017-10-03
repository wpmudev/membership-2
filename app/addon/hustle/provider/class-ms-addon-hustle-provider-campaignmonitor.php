<?php

class MS_Addon_Hustle_Provider_Campaignmonitor extends MS_Addon_Hustle_Provider {

	protected static $PROVIDER_ID = 'campaignmonitor';

	public function subscribe_user( $member, $list_id ) { 
		$api_key 	= $this->get_provider_detail( 'api_key' );
		$api 		= new CS_REST_Subscribers( $list_id, array( 'api_key' => $api_key ) );
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
		$api_key 	= $this->get_provider_detail( 'api_key' );
		$api 		= new CS_REST_Subscribers( $list_id, array( 'api_key' => $api_key ) );
		$api->unsubscribe( $member->email );
	}

	public function is_user_subscribed( $user_email, $list_id ) {
		$api_key	= $this->get_provider_detail( 'api_key' );
		$api 		= new CS_REST_Subscribers( $list_id, array( 'api_key' => $api_key ) );
		$subscribed = $api->get( $user_email );
		return $is_subscribed->was_successful();
	}
}
?>