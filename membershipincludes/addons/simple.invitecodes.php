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
	$Msi_options = M_get_option( 'membership_simpleinvite_options', array() );

	$Msi_options['invitecodes'] = implode( PHP_EOL, array_filter( array_map( 'trim', explode( PHP_EOL, filter_input( INPUT_POST, 'invitecodes' ) ) ) ) );
	$Msi_options['inviterequired'] = filter_input( INPUT_POST, 'inviterequired', FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
	$Msi_options['inviteremove'] = filter_input( INPUT_POST, 'inviteremove', FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';

	M_update_option( 'membership_simpleinvite_options', $Msi_options );
}

add_action( 'membership_registration_form_content', 'M_AddSimpleInviteField' );
add_action( 'bp_custom_profile_edit_fields', 'M_AddSimpleInviteField' );
function M_AddSimpleInviteField() {
    $Msi_options = M_get_option( 'membership_simpleinvite_options', array( ) );
	if ( !empty( $Msi_options['inviterequired'] ) && filter_var( $Msi_options['inviterequired'], FILTER_VALIDATE_BOOLEAN ) ) {
		?><div class="form-element">
			<label class="control-label" for="user_email"><?php _e('Invite Code', 'membership'); ?></label>
			<div class="element">
				<input type="text" autocomplete="off" class="input-xlarge" name="invitecode" value="<?php echo esc_attr( filter_input( INPUT_POST, 'invitecode' ) ) ?>">
			</div>
		</div><?php
	}
}

add_action( 'membership_validate_user_registration', 'M_AddSimpleInviteFieldProcess' );
function M_AddSimpleInviteFieldProcess( $error ) {
	$Msi_options = M_get_option( 'membership_simpleinvite_options', array() );
	if ( !empty( $Msi_options['inviterequired'] ) && filter_var( $Msi_options['inviterequired'], FILTER_VALIDATE_BOOLEAN ) ) {
		$thekey = trim( filter_input( INPUT_POST, 'invitecode' ) );
		if ( empty( $thekey ) ) {
			$error->add( 'enterinvitecode', __( 'You need to enter an invite code in order to register.', 'membership' ) );
		} else {
			$codes = array_map( 'trim', explode( PHP_EOL, $Msi_options['invitecodes'] ) );
			if ( !in_array( $thekey, $codes ) ) {
				$error->add( 'incorrectinvitecode', __( 'Sorry, but we do not seem to have that code on file, please try another.', 'membership' ) );
			}
		}
	}
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

add_action( 'membership_registration_complete', 'M_RemoveInviteCode' );
function M_RemoveInviteCode() {
	$Msi_options = M_get_option( 'membership_simpleinvite_options', array( ) );
	if ( !isset( $Msi_options['inviteremove'] ) || !filter_var( $Msi_options['inviteremove'], FILTER_VALIDATE_BOOLEAN ) || !isset( $Msi_options['invitecodes'] ) ) {
		return;
	}

	$codes = array_map( 'trim', explode( PHP_EOL, $Msi_options['invitecodes'] ) );
	$key = array_search( filter_input( INPUT_POST, 'invitecode' ), $codes );
	if ( $key !== false ) {
		unset( $codes[$key] );
		$Msi_options['invitecodes'] = implode( PHP_EOL, $codes );
		M_update_option( 'membership_simpleinvite_options', $Msi_options );
	}
}