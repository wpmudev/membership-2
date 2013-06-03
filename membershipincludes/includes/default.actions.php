<?php

function M_create_subscription( $user_id, $sub_id, $gateway ) {

	$member = new M_Membership( $user_id );

	if( !empty($member) && method_exists( $member, 'create_subscription') ) {
		$member->create_subscription($sub_id, $gateway);
	}

}

add_action( 'membership_create_subscription', 'M_create_subscription', 10, 3 );

function M_drop_subscription( $user_id, $sub_id ) {

	$member = new M_Membership( $user_id );

	if( !empty($member) && method_exists( $member, 'drop_subscription') ) {
		$member->drop_subscription($sub_id);
	}

}

add_action( 'membership_drop_subscription', 'M_drop_subscription', 10, 2 );

?>