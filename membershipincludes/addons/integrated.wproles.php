<?php
/*
Addon Name: Integrated WP Roles
Description: Allows members to be assigned different roles based on their levels
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

function M_Roles_show_information( $level_id ) {

	global $wp_roles;

	// Get the currentlt set ping for each level
	$level = Membership_Plugin::factory()->get_level( $level_id );

	$levelrole = $level->get_meta( 'associated_wp_role', '' );

	?>
		<h3><?php _e('Associated Role','membership'); ?></h3>
		<p class='description'><?php _e('If you want a specific WP role to be assigned to users on this level select it below.','membership'); ?></p>

		<div class='level-details'>

		<label for='levelrole'><?php _e('Associated Role','membership'); ?></label>

		<select name='levelrole'>
			<option value='none'><?php _e('No associated role','membership'); ?></option>
		<?php
		$all_roles = $wp_roles->roles;

		// Preset variables
		$p = ''; $r = '';

		foreach ( $all_roles as $role => $details ) {
			$name = translate_user_role($details['name'] );
			if ( $levelrole == $role ) { // preselect specified role
				$p .= "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
			} else {
				$r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
			}
		}
		echo $p . $r;
		?>
		</select>

		</div>
	<?php
}

function M_Roles_update_level_information( $level_id ) {

	$level = Membership_Plugin::factory()->get_level( $level_id );

	$level->update_meta( 'associated_wp_role', $_POST['levelrole'] );

}

add_action( 'membership_level_form_after_rules', 'M_Roles_show_information' );
add_action( 'membership_level_add', 'M_Roles_update_level_information' );
add_action( 'membership_level_update', 'M_Roles_update_level_information' );

// All legacy hooks removed as role updates are now integrated into Membership_Model_Member. (20140411)
