<?php
/*
Addon Name: Simple Invites Codes
Description: Force invite codes for membership signups
Author: Incsub
Author URI: http://premium.wpmudev.org
*/

add_action( 'membership_extrasoptions_page', 'M_AddSimpleInviteOptions', 11 );
function M_AddSimpleInviteOptions() {

    $Msi_options = M_get_option('membership_simpleinvite_options', array());
    ?>
    <div class="postbox">
        <h3 class="hndle" style='cursor:auto;'><span><?php _e('Simple Invite Codes', 'membership'); ?></span></h3>
        <div class="inside">
            <p class='description'><?php _e('Use the section below to enable and require invitation codes on the registration panel.', 'membership'); ?></p>

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><?php _e('Require Invite Codes', 'membership'); ?>
                            </em>
                        </th>
                        <td>
                            <?php
                            if (!isset($Msi_options['inviterequired'])) {
                                $Msi_options['inviterequired'] = '';
                            }
                            ?>
                            <input type='checkbox' name='inviterequired' id='inviterequired' value='yes' <?php checked('yes', $Msi_options['inviterequired']); ?> />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Invite Codes', 'membership'); ?><br/>
                            <em style='font-size:smaller;'><?php _e("Place each available code on a new line.", 'membership'); ?>
                            </em>
                        </th>
                        <td>
                            <?php
                            if (!isset($Msi_options['invitecodes'])) {
                                $Msi_options['invitecodes'] = '';
                            }
                            ?>
                            <textarea name='invitecodes' id='invitecodes' rows='15' cols='40'><?php esc_html_e(stripslashes($Msi_options['invitecodes'])); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Remove Code once used', 'membership'); ?>
                            </em>
                        </th>
                        <td>
                            <?php
                            if (!isset($Msi_options['inviteremove'])) {
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

add_action( 'membership_option_menu_process_extras', 'M_AddSimpleInviteOptionsProcess', 11 );
function M_AddSimpleInviteOptionsProcess() {

    $Msi_options = M_get_option('membership_simpleinvite_options', array());

    $Msi_options['invitecodes'] = (isset($_POST['invitecodes'])) ? $_POST['invitecodes'] : '';
    $Msi_options['inviterequired'] = (isset($_POST['inviterequired'])) ? $_POST['inviterequired'] : '';
    $Msi_options['inviteremove'] = (isset($_POST['inviteremove'])) ? $_POST['inviteremove'] : '';

    M_update_option('membership_simpleinvite_options', $Msi_options);
}

add_action( 'membership_subscription_form_registration_presubmit_content', 'M_AddSimpleInviteField' );
function M_AddSimpleInviteField() {
    $Msi_options = M_get_option( 'membership_simpleinvite_options', array( ) );
	if ( empty( $Msi_options['inviterequired'] ) || $Msi_options['inviterequired'] != 'yes' ) {
		return;
	}

    ?><div class="form-element">
        <label class="control-label" for="invitecode"><?php _e('Invite Code', 'membership'); ?></label>
        <div class="element">
            <input type="text" autocomplete="off" class="input-xlarge" id="invitecode" name="invitecode">
        </div>
    </div><?php
}

add_action( 'bp_before_registration_submit_buttons', 'M_AddSimpleInviteField_BP' );
function M_AddSimpleInviteField_BP() {
    $Msi_options = M_get_option( 'membership_simpleinvite_options', array( ) );
	if ( empty( $Msi_options['inviterequired'] ) || $Msi_options['inviterequired'] != 'yes' ) {
		return;
	}

    ?><style type="text/css"> #invitecode-section { clear: both; } </style>
	<div id="invitecode-section" class="register-section">
		<div class="editfield">
			<label for="invitecode"><?php _e( 'Invite Code', 'membership' ); ?></label>
			<?php do_action( 'bp_invitecode_errors' ) ?>
			<input type="text" autocomplete="off" class="input-xlarge" id="invitecode" name="invitecode">
		</div>
	</div><?php
}

add_filter( 'membership_subscription_form_before_registration_process', 'M_AddSimpleInviteFieldProcess' );
function M_AddSimpleInviteFieldProcess( $error ) {
	$message = M_SimpleInviteFieldValidate();
	if ( $message !== true ) {
		$error->add( 'enterinvitecode', $message );
	}

	return $error;
}

add_action( 'bp_signup_validate', 'M_AddSimpleInviteFieldProcess_BP' );
function M_AddSimpleInviteFieldProcess_BP() {
	global $bp;

	if ( !$bp || !is_a( $bp, 'BuddyPress' ) ) {
		return;
	}

	$message = M_SimpleInviteFieldValidate();
	if ( $message !== true ) {
		$bp->signup->errors['invitecode'] = $message;
	}
}

function M_SimpleInviteFieldValidate() {
	$Msi_options = M_get_option( 'membership_simpleinvite_options', array() );
	if ( empty( $Msi_options['inviterequired'] ) || $Msi_options['inviterequired'] != 'yes' ) {
		return true;
	}

	$thekey = filter_input( INPUT_POST, 'invitecode' );
	if ( empty( $thekey ) ) {
		return __( 'You need to enter an invite code in order to register.', 'membership' );
	} else {
		$codes = array_map( 'trim', explode( PHP_EOL, $Msi_options['invitecodes'] ) );
		if ( !in_array( $thekey, $codes ) ) {
			return __( 'Sorry, but we do not seem to have that code on file, please try another.', 'membership' );
		}
	}

	return true;
}

add_action( 'membership_popover_extend_registration_form', 'M_AddSimpleRegistrationInviteField' );
function M_AddSimpleRegistrationInviteField() {
	$Msi_options = M_get_option( 'membership_simpleinvite_options', array() );
	if ( !empty( $Msi_options['inviterequired'] ) && $Msi_options['inviterequired'] == 'yes' ) {
		?><div>
			<label><?php _e( 'Invite Code', 'membership' ); ?> <span>*</span></label>
			<input type="text" autocomplete="off" class="regtext" name="invitecode" id="invitecode">
		</div><?php
	}
}

add_action( 'membership_subscription_form_registration_process', 'M_RemoveInviteCode', 10, 2 );
function M_RemoveInviteCode( WP_Error $error, $user_id ) {
	if ( ( is_wp_error( $error ) && !empty( $error->errors ) ) || !$user_id ) {
		return;
	}

	M_RemoveSimpleInviteCode();
}

add_action( 'bp_core_signup_user', 'M_RemoveSimpleInviteCode' );
add_action( 'bp_core_signup_blog', 'M_RemoveSimpleInviteCode' );
function M_RemoveSimpleInviteCode() {
    $Msi_options = M_get_option( 'membership_simpleinvite_options', array() );
	if ( !isset( $Msi_options['inviteremove'] ) || !filter_var( $Msi_options['inviteremove'], FILTER_VALIDATE_BOOLEAN ) || !isset( $Msi_options['invitecodes'] ) ) {
		return;
	}

	$thekey = filter_input( INPUT_POST, 'invitecode' );
	$codes = array_map( 'trim', explode( PHP_EOL, $Msi_options['invitecodes'] ) );

	$key = array_search( $thekey, $codes );
	if ( $key !== false ) {
		unset( $codes[$key] );
		$Msi_options['invitecodes'] = implode( PHP_EOL, $codes );
		M_update_option( 'membership_simpleinvite_options', $Msi_options );
	}
}