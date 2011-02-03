<?php
// A simple invitation code system for the membership plugin
// Written by: Barry Getty (Incsub)
function M_AddSimpleInviteOptions() {

	$Msi_options = get_option('membership_simpleinvite_options', array());
	?>
	<h3><?php _e('Simple Invite Codes','membership'); ?></h3>
	<p><?php _e('Use the section below to enable and require invitation codes on the registration panel.','membership'); ?></p>

	<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php _e('Require Invite Codes','membership'); ?>
			</em>
			</th>
			<td>
				<input type='checkbox' name='inviterequired' id='inviterequired' value='yes' <?php checked('yes', $Msi_options['inviterequired']); ?> />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Invite Codes','membership'); ?><br/>
			<em style='font-size:smaller;'><?php _e("Place each available code on a new line.",'membership'); ?>
			</em>
			</th>
			<td>
				<textarea name='invitecodes' id='invitecodes' rows='15' cols='40'><?php esc_html_e(stripslashes($Msi_options['invitecodes'])); ?></textarea>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Remove Code once used','membership'); ?>
			</em>
			</th>
			<td>
				<input type='checkbox' name='inviteremove' id='inviteremove' value='yes' <?php checked('yes', $Msi_options['inviteremove']); ?> />
			</td>
		</tr>
	</tbody>
	</table>
	<?php
}
add_action( 'membership_options_page', 'M_AddSimpleInviteOptions', 11 );

function M_AddSimpleInviteOptionsProcess() {

	$Msi_options = get_option('membership_simpleinvite_options', array());

	$Msi_options['invitecodes'] = $_POST['invitecodes'];
	$Msi_options['inviterequired'] = $_POST['inviterequired'];
	$Msi_options['inviteremove'] = $_POST['inviteremove'];

	update_option('membership_simpleinvite_options', $Msi_options);

}
add_action( 'membership_options_page_process', 'M_AddSimpleInviteOptionsProcess', 11 );

function M_AddSimpleInviteField() {

	$Msi_options = get_option('membership_simpleinvite_options', array());
	if(empty($Msi_options['inviterequired']) || $Msi_options['inviterequired'] != 'yes') {
		return;
	}

	?>
		<div class="alignleft">
			<label><?php _e('Invite Code','membership'); ?> <span>*</span></label>
			<input type="text" autocomplete="off" class="regtext" name="invitecode">
		</div>
	<?php
}
add_action( 'membership_subscription_form_registration_presubmit_content', 'M_AddSimpleInviteField');
add_action( 'bp_after_account_details_fields', 'M_AddSimpleInviteField');

function M_AddSimpleInviteFieldProcess($error) {

	$Msi_options = get_option('membership_simpleinvite_options', array());
	if(empty($Msi_options['inviterequired']) || $Msi_options['inviterequired'] != 'yes') {
		return $error;
	}

	$thekey = $_POST['invitecode'];

	if(empty($thekey)) {
		$error[] = __('You need to enter an invite code in order to register.','membership');
	} else {

		$codes = explode("\n", $Msi_options['invitecodes']);
		$codes = array_map('trim', $codes);

		if(!in_array( $thekey, $codes )) {
			$error[] = __('Sorry, but we do not seem to have that code on file, please try another.','membership');
		} else {
			if($Msi_options['inviteremove'] == 'yes') {
				$key = array_search( $thekey, $codes);
				if($key !== false) {
					unset($codes[$key]);
					$Msi_options['invitecodes'] = implode("\n", $codes);

					update_option('membership_simpleinvite_options', $Msi_options);
				}
			}
		}
	}

	return $error;

}
add_filter( 'membership_subscription_form_before_registration_process', 'M_AddSimpleInviteFieldProcess' );

?>