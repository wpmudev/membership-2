<?php
/*
Addon Name: Simple Invites Codes
Description: Force invite codes for membership signups
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

function M_AddSimpleInviteOptions() {

	$Msi_options = M_get_option('membership_simpleinvite_options', array());

	?>
			<div class="postbox">
				<h3 class="hndle" style='cursor:auto;'><span><?php _e('Simple Invite Codes','membership'); ?></span></h3>
				<div class="inside">
					<p class='description'><?php _e('Use the section below to enable and require invitation codes on the registration panel.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Require Invite Codes','membership'); ?>
							</em>
							</th>
							<td>
								<?php
								if(!isset($Msi_options['inviterequired'])) {
									$Msi_options['inviterequired'] = '';
								}
								?>
								<input type='checkbox' name='inviterequired' id='inviterequired' value='yes' <?php checked('yes', $Msi_options['inviterequired']); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Invite Codes','membership'); ?><br/>
							<em style='font-size:smaller;'><?php _e("Place each available code on a new line.",'membership'); ?>
							</em>
							</th>
							<td>
								<?php
								if(!isset($Msi_options['invitecodes'])) {
									$Msi_options['invitecodes'] = '';
								}
								?>
								<textarea name='invitecodes' id='invitecodes' rows='15' cols='40'><?php esc_html_e(stripslashes($Msi_options['invitecodes'])); ?></textarea>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Remove Code once used','membership'); ?>
							</em>
							</th>
							<td>
								<?php
								if(!isset($Msi_options['inviteremove'])) {
									$Msi_options['inviteremove'] = '';
								}
								?>
								<input type='checkbox' name='inviteremove' id='inviteremove' value='yes' <?php checked('yes', $Msi_options['inviteremove']); ?> />
							</td>
						</tr>
					</tbody>
					</table>
				</div>
			</div>
	<?php
}
add_action( 'membership_extrasoptions_page', 'M_AddSimpleInviteOptions', 11 );

function M_AddSimpleInviteOptionsProcess() {

	$Msi_options = M_get_option('membership_simpleinvite_options', array());

	$Msi_options['invitecodes'] = (isset($_POST['invitecodes'])) ? $_POST['invitecodes'] : '';
	$Msi_options['inviterequired'] = (isset($_POST['inviterequired'])) ? $_POST['inviterequired'] : '';
	$Msi_options['inviteremove'] = (isset($_POST['inviteremove'])) ? $_POST['inviteremove'] : '';

	M_update_option('membership_simpleinvite_options', $Msi_options);


}
add_action( 'membership_option_menu_process_extras', 'M_AddSimpleInviteOptionsProcess', 11 );

function M_AddSimpleInviteField() {

	$Msi_options = M_get_option('membership_simpleinvite_options', array());
	if(empty($Msi_options['inviterequired']) || $Msi_options['inviterequired'] != 'yes') {
		return;
	}

	?>
		<div class="form-element">
			<label class="control-label" for="user_email"><?php _e('Invite Code','membership'); ?></label>
			<div class="element">
				<input type="text" autocomplete="off" class="input-xlarge" name="invitecode">
			</div>
		</div>
	<?php
}
add_action( 'membership_subscription_form_registration_presubmit_content', 'M_AddSimpleInviteField');
// Moved on BP to Profile area
add_action( 'bp_custom_profile_edit_fields', 'M_AddSimpleInviteField');

function M_AddSimpleInviteFieldProcess( $error ) {

	$Msi_options = M_get_option('membership_simpleinvite_options', array());
	if(empty($Msi_options['inviterequired']) || $Msi_options['inviterequired'] != 'yes') {
		return $error;
	}

	$thekey = $_POST['invitecode'];

	if(empty($thekey)) {

		if(empty($error)) {
			$error = new WP_Error();
		}

		$error->add('enterinvitecode', __('You need to enter an invite code in order to register.','membership'));

	} else {

		$codes = explode("\n", $Msi_options['invitecodes']);
		$codes = array_map('trim', $codes);

		if(!in_array( $thekey, $codes )) {

			if(empty($error)) {
				$error = new WP_Error();
			}

			$error->add('incorrectinvitecode', __('Sorry, but we do not seem to have that code on file, please try another.','membership'));

		} else {
			if(empty($error)) {
				if($Msi_options['inviteremove'] == 'yes') {
					$key = array_search( $thekey, $codes);
					if($key !== false) {
						unset($codes[$key]);
						$Msi_options['invitecodes'] = implode("\n", $codes);

						M_update_option('membership_simpleinvite_options', $Msi_options);
					}
				}
			}
		}
	}

	return $error;

}
add_filter( 'membership_subscription_form_before_registration_process', 'M_AddSimpleInviteFieldProcess' );

?>