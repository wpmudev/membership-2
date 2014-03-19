<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Renders standard registration form template.
 *
 * @category Membership
 * @package Render
 * @subpackage Page
 * @subpackage Registration
 *
 * @since 3.5
 */
class Membership_Render_Page_Registration_Standard extends Membership_Render {

	/**
	 * Renders registration template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @global BuddyPress $bp The instance of BuddyPress class.
	 */
	protected function _to_html() {
		global $bp;

		$error = $this->error;
		$subscription = $this->subscription;

		if ( defined( 'MEMBERSHIP_REGISTRATION_FORM' ) && is_readable( MEMBERSHIP_REGISTRATION_FORM ) ) {
			include MEMBERSHIP_REGISTRATION_FORM;
		} elseif ( !is_null( $bp ) && is_a( $bp, 'BuddyPress' ) ) {
			$filename = apply_filters( 'membership_override_bpregistration_form', '', $error );
			if ( !empty( $filename ) && is_readable( $filename ) ) {
				include $filename;
			} else {
				$this->_render_buddypress_form();
			}
		} else {
			$filename = apply_filters( 'membership_override_registration_form', '', $error );
			if ( !empty( $filename ) && is_readable( $filename ) ) {
				include $filename;
			} else {
				$this->_render_standard_form();
			}
		}
	}

	/**
	 * Renders error messages.
	 *
	 * @sicne 3.5
	 *
	 * @access private
	 */
	private function _render_errors() {
		if ( is_wp_error( $this->error ) ) {
			$messages = $this->error->get_error_messages();
			if ( !empty( $messages ) ) {
				?><div class="alert alert-error">
					<?php echo implode( '<br>', $messages ) ?>
				</div><?php
			}
		}
	}

	/**
	 * Renders standard registration form.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function _render_standard_form() {
		$permalink = get_permalink();
		$login_url = wp_login_url( add_query_arg( array( 'action' => 'registeruser', 'subscription' => $this->subscription ), $permalink ) );

		?><div id="membership-wrapper">
			<?php $this->_render_errors() ?>

			<form class="form-membership" action="<?php echo add_query_arg( 'action', 'validatepage1', $permalink ) ?>" method="post">
				<?php do_action( "signup_hidden_fields" ) ?>
				<input type="hidden" name="subscription" value="<?php echo esc_attr( $this->subscription ) ?>">

				<fieldset>
					<legend><?php _e( 'Create an Account', 'membership' ) ?></legend>

					<div class="form-element">
						<label class="control-label" for="first_name"><?php _e( 'First Name', 'membership' ) ?></label>
						<div class="element">
							<input type="text" class="input-xlarge" id="first_name" name="first_name" x-autocompletetype="given-name" value="<?php echo esc_attr( filter_input( INPUT_POST, 'first_name' ) ) ?>">
						</div>
					</div>

					<div class="form-element">
						<label class="control-label" for="last_name"><?php _e( 'Last Name', 'membership' ) ?></label>
						<div class="element">
							<input type="text" class="input-xlarge" id="last_name" name="last_name" x-autocompletetype="family-name" value="<?php echo esc_attr( filter_input( INPUT_POST, 'last_name' ) ) ?>">
						</div>

						<p class="help-block"><?php _e( 'Please enter desired username and your email address.', 'membership' ) ?></p>
					</div>

					<div class="form-element">
						<label class="control-label" for="user_login"><?php _e( 'Choose a Username', 'membership' ) ?></label>
						<div class="element">
							<input type="text" class="input-xlarge" id="user_login" name="user_login" required x-autocompletetype="nickname" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_login' ) ) ?>">
						</div>
					</div>

					<div class="form-element">
						<label class="control-label" for="user_email"><?php _e( 'Email Address', 'membership' ) ?></label>
						<div class="element">
							<input type="text" class="input-xlarge" id="user_email" name="user_email" required x-autocompletetype="email" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_email' ) ) ?>">
						</div>

						<p class="help-block"><?php _e( 'Please enter a new password, and then verify your new password by entering it again.', 'membership' ) ?></p>
					</div>

					<div class="form-element">
						<label class="control-label" for="user_email"><?php _e( 'Password', 'membership' ) ?></label>
						<div class="element">
							<input type="password" class="input-xlarge" id="password" name="password" required autocomplete="off">
						</div>
					</div>

					<div class="form-element">
						<label class="control-label" for="user_email"><?php _e( 'Confirm Password', 'membership' ) ?></label>
						<div class="element">
							<input type="password" class="input-xlarge" id="password2" name="password2" required autocomplete="off">
						</div>

						<p class="help-block"><?php _e( 'Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'membership' ) ?></p>
					</div>

					<?php do_action( 'membership_subscription_form_registration_presubmit_content' ) ?>
					<?php do_action( 'signup_extra_fields', $this->error ) ?>

					<p><input type="submit" value="<?php _e( 'Register My Account', 'membership' ) ?> &raquo;" class="alignright button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>" name="register"></p>

					<a title="<?php _e( 'Login', 'membership' ) ?> &raquo;" href="<?php echo esc_url( $login_url ) ?>" class="alignleft" id="login_right"><?php _e( 'Already have a user account?', 'membership' ) ?></a>
				</fieldset>
			</form>
		</div><?php
	}

	/**
	 * Renders BuddyPress account details section.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function _render_buddypress_account_details() {
		?><div class="register-section" id="basic-details-section">
			<h4><?php _e( 'Account Details', 'membership' ) ?></h4>

			<label for="signup_username"><?php _e( 'Username (required)', 'membership' ) ?></label>
			<?php do_action( 'bp_signup_username_errors' ) ?>
			<input type="text" name="signup_username" id="signup_username" required x-autocompletetype="nickname" value="<?php bp_signup_username_value() ?>">

			<label for="signup_email"><?php _e( 'Email Address (required)', 'membership' ) ?></label>
			<?php do_action( 'bp_signup_email_errors' ) ?>
			<input type="text" name="signup_email" id="signup_email" required x-autocompletetype="email" value="<?php bp_signup_email_value() ?>">

			<label for="signup_password"><?php _e( 'Choose a Password (required)', 'membership' ) ?></label>
			<?php do_action( 'bp_signup_password_errors' ) ?>
			<input type="password" name="signup_password" required id="signup_password" autocomplete="off">

			<label for="signup_password_confirm"><?php _e( 'Confirm Password (required)', 'membership' ) ?></label>
			<?php do_action( 'bp_signup_password_confirm_errors' ) ?>
			<input type="password" name="signup_password_confirm" required id="signup_password_confirm" autocomplete="off">

			<?php do_action( 'bp_before_account_details_end' ) ?>
		</div><!-- #basic-details-section --><?php
	}

	/**
	 * Renders BuddyPress account extra fields.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function _render_buddypress_account_extra_fields() {
		if ( !bp_is_active( 'xprofile' ) )  {
			return;
		}

		do_action( 'bp_before_signup_profile_fields' );

		?><div class="register-section" id="profile-details-section">
			<h4><?php _e( 'Profile Details', 'membership' ) ?></h4>

			<?php if ( bp_has_profile( 'profile_group_id=1&hide_empty_fields=0' ) ) : ?>
				<?php while ( bp_profile_groups() ) : bp_the_profile_group(); ?>
					<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

						<?php $field_name = bp_get_the_profile_field_input_name() ?>
						<?php $field_name_esc = esc_attr( $field_name ) ?>
						<?php $field_type = bp_get_the_profile_field_type() ?>

						<div class="editfield">
							<?php if ( 'textbox' == $field_type ) : ?>
								<label for="<?php echo $field_name_esc ?>">
									<?php if ( bp_get_the_profile_field_is_required() ) : ?>
										<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
									<?php else : ?>
										<?php bp_the_profile_field_name() ?>
									<?php endif; ?>
								</label>
								<?php do_action( "bp_{$field_name}_errors" ) ?>
								<input type="text" name="<?php echo $field_name_esc ?>" id="<?php echo $field_name_esc ?>" value="<?php bp_the_profile_field_edit_value() ?>" />
							<?php endif; ?>

							<?php if ( 'textarea' == $field_type ) : ?>
								<label for="<?php echo $field_name_esc ?>">
									<?php if ( bp_get_the_profile_field_is_required() ) : ?>
										<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
									<?php else : ?>
										<?php bp_the_profile_field_name() ?>
									<?php endif; ?>
								</label>
								<?php do_action( "bp_{$field_name}_errors" ) ?>
								<textarea rows="5" cols="40" name="<?php echo $field_name_esc ?>" id="<?php echo $field_name_esc ?>"><?php bp_the_profile_field_edit_value() ?></textarea>
							<?php endif; ?>

							<?php if ( 'selectbox' == $field_type ) : ?>
								<label for="<?php echo $field_name_esc ?>">
									<?php if ( bp_get_the_profile_field_is_required() ) : ?>
										<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
									<?php else : ?>
										<?php bp_the_profile_field_name() ?>
									<?php endif; ?>
								</label>
								<?php do_action( "bp_{$field_name}_errors" ) ?>
								<select name="<?php echo $field_name_esc ?>" id="<?php echo $field_name_esc ?>">
									<?php bp_the_profile_field_options() ?>
								</select>
							<?php endif; ?>

							<?php if ( 'multiselectbox' == $field_type ) : ?>
								<label for="<?php echo $field_name_esc ?>">
									<?php if ( bp_get_the_profile_field_is_required() ) : ?>
										<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
									<?php else : ?>
										<?php bp_the_profile_field_name() ?>
									<?php endif; ?>
								</label>
								<?php do_action( "bp_{$field_name}_errors" ) ?>
								<select name="<?php echo $field_name_esc ?>" id="<?php echo $field_name_esc ?>" multiple="multiple">
									<?php bp_the_profile_field_options() ?>
								</select>
							<?php endif; ?>

							<?php if ( 'radio' == $field_type ) : ?>
								<div class="radio">
									<span class="label">
										<?php if ( bp_get_the_profile_field_is_required() ) : ?>
											<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
										<?php else : ?>
											<?php bp_the_profile_field_name() ?>
										<?php endif; ?>
									</span>

									<?php do_action( "bp_{$field_name}_errors" ) ?>
									<?php bp_the_profile_field_options() ?>

									<?php if ( !bp_get_the_profile_field_is_required() ) : ?>
										<a class="clear-value" href="javascript:clear( '<?php echo $field_name_esc ?>' );"><?php _e( 'Clear', 'membership' ) ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( 'checkbox' == $field_type ) : ?>
								<div class="checkbox">
									<span class="label">
										<?php if ( bp_get_the_profile_field_is_required() ) : ?>
											<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
										<?php else : ?>
											<?php bp_the_profile_field_name() ?>
										<?php endif; ?>
									</span>

									<?php do_action( "bp_{$field_name}_errors" ) ?>
									<?php bp_the_profile_field_options() ?>
								</div>
							<?php endif; ?>

							<?php if ( 'datebox' == $field_type ) : ?>
								<div class="datebox">
									<label for="<?php echo $field_name_esc ?>_day">
										<?php if ( bp_get_the_profile_field_is_required() ) : ?>
											<?php printf( _x( '%s (required)', '{Profile field} (required)', 'membership' ), bp_get_the_profile_field_name() ) ?>
										<?php else : ?>
											<?php bp_the_profile_field_name() ?>
										<?php endif; ?>
									</label>

									<?php do_action( "bp_{$field_name}_errors" ) ?>

									<select name="<?php echo $field_name_esc ?>_day" id="<?php echo $field_name_esc ?>_day">
										<?php bp_the_profile_field_options( 'type=day' ) ?>
									</select>

									<select name="<?php echo $field_name_esc ?>_month" id="<?php echo $field_name_esc ?>_month">
										<?php bp_the_profile_field_options( 'type=month' ) ?>
									</select>

									<select name="<?php echo $field_name_esc ?>_year" id="<?php echo $field_name_esc ?>_year">
										<?php bp_the_profile_field_options( 'type=year' ) ?>
									</select>
								</div>
							<?php endif; ?>

							<?php do_action( 'bp_custom_profile_edit_fields_pre_visibility' ); ?>

							<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>
								<p class="field-visibility-settings-toggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
									<?php printf(
										_x( 'This field can be seen by: %s', 'This field can be seen by: {Administrator}', 'membership' ),
										'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
									) ?> <a href="#" class="visibility-toggle-link"><?php _ex( 'Change', 'Change profile field visibility level', 'membership' ) ?></a>
								</p>

								<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id() ?>">
									<fieldset>
										<legend><?php _e( 'Who can see this field?', 'membership' ) ?></legend>
										<?php bp_profile_visibility_radio_buttons() ?>
									</fieldset>
									<a class="field-visibility-settings-close" href="#"><?php _e( 'Close', 'membership' ) ?></a>
								</div>
							<?php else : ?>
								<p class="field-visibility-settings-notoggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
									<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', 'membership' ), bp_get_the_profile_field_visibility_level_label() ) ?>
								</p>
							<?php endif ?>

							<?php do_action( 'bp_custom_profile_edit_fields' ) ?>
							<p class="description"><?php bp_the_profile_field_description() ?></p>
						</div>
					<?php endwhile; ?>
					<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bp_the_profile_group_field_ids() ?>">
				<?php endwhile; ?>
			<?php endif; ?>
		</div><!-- #profile-details-section --><?php

		do_action( 'bp_after_signup_profile_fields' );
	}

	/**
	 * Renders BuddyPress registration form.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function _render_buddypress_form() {
		$permalink = get_permalink();

		?><div id="buddypress">
			<?php do_action( 'bp_before_register_page' ) ?>

			<div class="page" id="register-page">
				<form action="<?php echo add_query_arg( 'action', 'validatepage1bp', $permalink ) ?>" name="signup_form" id="signup_form" class="standard-form" method="post" enctype="multipart/form-data">
					<input type="hidden" name="subscription" value="<?php echo esc_attr( $this->subscription ) ?>">

					<h2><?php _e( 'Create an Account', 'membership' ) ?></h2>

					<?php do_action( 'template_notices' ) ?>
					<?php $this->_render_errors() ?>

					<p><?php _e( 'Registering for this site is easy, just fill in the fields below and we\'ll get a new account set up for you in no time.', 'membership' ) ?></p>

					<?php do_action( 'bp_before_account_details_fields' ) ?>
					<?php $this->_render_buddypress_account_details() ?>
					<?php do_action( 'bp_after_account_details_fields' ) ?>

					<?php $this->_render_buddypress_account_extra_fields() ?>

					<?php do_action( 'bp_before_registration_submit_buttons' ) ?>
					<div class="submit">
						<input type="submit"name="signup_submit" id="signup_submit" value="<?php _e( 'Sign Up', 'membership' ) ?> &rarr;" />
					</div>
					<?php do_action( 'bp_after_registration_submit_buttons' ) ?>

					<?php wp_nonce_field( 'bp_new_signup' ) ?>
					<?php do_action( 'bp_custom_signup_steps' ) ?>
				</form>
			</div>

			<?php do_action( 'bp_after_register_page' ); ?>
		</div><?php
	}

}