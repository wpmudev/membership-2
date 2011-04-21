<?php

function M_Roles_show_information( $level_id ) {

	// Get the currentlt set ping for each level
	$level =& new M_Level( $level_id );

	$levelrole = $level->get_meta( 'associated_wp_role', '' );

	?>
		<h3><?php _e('Associated Role','membership'); ?></h3>
		<p class='description'><?php _e('If you want a specific WP role to be assigned to users on this level select it below.','membership'); ?></p>

		<div class='level-details'>

		<label for='levelrole'><?php _e('Associated Role','membership'); ?></label>

		<select name='levelrole'>
			<option value=''><?php _e('No associated role','membership'); ?></option>
		<?php
		$editable_roles = get_editable_roles();

		foreach ( $editable_roles as $role => $details ) {
			$name = translate_user_role($details['name'] );
			if ( $levelrole == $role ) // preselect specified role
				$p = "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
			else
				$r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
		}
		echo $p . $r;
		?>
		</select>

		</div>
	<?php
}

function M_Roles_update_level_information( $level_id ) {

	$level =& new M_Level( $level_id );

	$level->update_meta( 'associated_wp_role', $_POST['levelrole'] );

}

add_action( 'membership_level_form_after_rules', 'M_Roles_show_information' );
add_action( 'membership_level_add', 'M_Roles_update_level_information' );
add_action( 'membership_level_update', 'M_Roles_update_level_information' );

// Ping integration functions and hooks
/*
do_action( 'membership_add_level', $tolevel_id, $this->ID );
do_action( 'membership_drop_level', $fromlevel_id, $this->ID );
do_action( 'membership_move_level', $fromlevel_id, $tolevel_id, $this->ID );

do_action( 'membership_add_subscription', $tosub_id, $tolevel_id, $to_order, $this->ID);
do_action( 'membership_drop_subscription', $fromsub_id, $this->ID );
do_action( 'membership_move_subscription', $fromsub_id, $tosub_id, $tolevel_id, $to_order, $this->ID );
*/

function M_Roles_backupstart( $user_id ) {

	global $wpdb;

	$start = get_user_meta( $user_id, $wpdb->prefix . 'capabilities');
	update_user_meta( $user_id, $wpdb->prefix . 'm_backup_capabilites', $start);
}

function M_Roles_restoresart( $user_id ) {

	global $wpdb;

	$start = get_user_meta( $user_id, $wpdb->prefix . 'm_backup_capabilites');
	update_user_meta( $user_id, $wpdb->prefix . 'capabilites', $start);

}

function M_Roles_joinedlevel( $tolevel_id, $user_id ) {

	// Set up the level and find out if it has a joining ping
	$level =& new M_Level( $tolevel_id );
	$member =& new M_Membership( $user_id );

	$wprole = $level->get_meta( 'associated_wp_role' );
	if(!empty($wprole)) {
		$member->set_role( $wprole );
	}


}
add_action( 'membership_add_level', 'M_Roles_joinedlevel', 10, 2 );

function M_Roles_leftlevel( $fromlevel_id, $user_id ) {

	// Set up the level and find out if it has a leaving ping
	$level =& new M_Level( $fromlevel_id );
	$member =& new M_Membership( $user_id );

	$wprole = $level->get_meta( 'associated_wp_role' );
	if(!empty($wprole)) {
		$member->remove_role( $wprole );
	}

	if(!$member->has_levels()) {
		$member->set_role( get_option('default_role') );
	}

}
add_action( 'membership_drop_level', 'M_Roles_leftlevel', 10, 2 );

function M_Roles_movedlevel( $fromlevel_id, $tolevel_id, $user_id ) {

	M_Roles_leftlevel( $fromlevel_id, $user_id );
	M_Roles_joinedlevel( $tolevel_id, $user_id );

}
add_action( 'membership_move_level', 'M_Roles_movedlevel', 10, 3 );

function M_Roles_joinedsub( $tosub_id, $tolevel_id, $to_order, $user_id ) {

	$level =& new M_Level( $tolevel_id );
	$member =& new M_Membership( $user_id );
	$wprole = $level->get_meta( 'associated_wp_role' );

	if(!empty($wprole)) {
		$member->set_role( $wprole );
	}

}
add_action( 'membership_add_subscription', 'M_Roles_joinedsub', 10, 4 );

function M_Roles_leftsub( $fromsub_id, $fromlevel_id, $user_id ) {

	M_Roles_leftlevel( $fromlevel_id, $user_id );

	$member =& new M_Membership( $user_id );
	if(!$member->has_levels()) {
		$member->set_role( get_option('default_role') );
	}

}
add_action( 'membership_drop_subscription', 'M_Roles_leftsub', 10, 3 );

function M_Roles_movedsub( $fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $user_id ) {

	M_Roles_leftsub( $fromsub_id, $fromlevel_id, $user_id );
	M_Roles_joinedsub( $tosub_id, $tolevel_id, $to_order, $user_id );

}
add_action( 'membership_move_subscription', 'M_Roles_movedsub', 10, 6 );

?>