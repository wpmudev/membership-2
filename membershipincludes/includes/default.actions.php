<?php

function create_subscription( $user_id, $sub_id, $gateway ) {

	$member = new M_Membership( $user_id );

	if( !empty($member) && method_exists( $member, 'create_subscription') ) {
		$member->create_subscription($sub_id, $gateway);
	}

}

add_action( 'membership_create_subscription', 'create_subscription', 10, 3 );

?>