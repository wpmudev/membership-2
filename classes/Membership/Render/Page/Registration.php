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
 * Renders registration form.
 *
 * @category Membership
 * @package Render
 * @subpackage Page
 *
 * @since 3.4.5
 */
class Membership_Render_Page_Registration extends Membership_Render {

	/**
	 * Renders page template.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 * @global BuddyPress $bp The BuddyPress instance.
	 */
	protected function _to_html() {
		global $bp;

		do_action( 'membership_before_registration_form' );

		$filename = apply_filters( 'membership_registration_form_filename', defined( 'MEMBERSHIP_REGISTRATION_FORM' ) ? MEMBERSHIP_REGISTRATION_FORM : false );
		if ( is_readable( $filename ) ) {
			include $filename;
		} else {
			if( $bp ) {
				$this->_render_registration_bp_form();
			} else {
				$this->_render_registration_form();
			}
		}

		do_action( 'membership_after_registration_form' );
	}

	/**
	 * Renders registration form.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 */
	private function _render_registration_form() {
		?><div id="membership-wrapper">
			<?php $this->_render_errors() ?>
			<form class="form-membership" method="post">
				<?php do_action( 'signup_hidden_fields' ) ?>
				<input type="hidden" name="type" value="simple">
				<fieldset>
					<legend><?php _e( 'Create an Account', 'membership' ) ?></legend>

					<div class="form-element">
						<label class="control-label" for="user_login"><?php _e( 'Choose a Username', 'membership' ) ?></label>
						<div class="element">
							<input type="text" id="user_login" class="input-xlarge" name="user_login" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_login' ) ) ?>">
						</div>
					</div>

					<div class="form-element">
						<label class="control-label" for="user_email"><?php _e( 'Email Address', 'membership' ); ?></label>
						<div class="element">
							<input type="text" id="user_email" class="input-xlarge" name="user_email" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_email' ) ) ?>">
						</div>
					</div>

					<div class="form-element">
						<label class="control-label" for="password"><?php _e( 'Password', 'membership' ) ?></label>
						<div class="element">
							<input type="password" id="password" class="input-xlarge" name="password">
						</div>
					</div>

					<div class="form-element">
						<label class="control-label" for="password2"><?php _e( 'Confirm Password', 'membership' ) ?></label>
						<div class="element">
							<input type="password" id="password2" class="input-xlarge" name="password2">
						</div>
						<p class="help-block"><?php _e( 'The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'membership' ); ?></p>
					</div>

					<?php do_action( 'membership_registration_form_content' ) ?>
					<?php do_action( 'signup_extra_fields', $this->error ) ?>


					<input type="submit" value="<?php _e( 'Register My Account &raquo;', 'membership' ) ?>" class="alignright button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>">
					<a id="login_right" class="alignleft" href="<?php echo wp_login_url() ?>" title="<?php _e( 'Login', 'membership' ) ?> »"><?php _e( 'Already have a user account?', 'membership' ) ?></a>
				</fieldset>
			</form>
		</div><?php
	}

	/**
	 * Renders registration errors.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 */
	private function _render_errors() {
		if ( !empty( $this->errors ) ) :
			?><div class="alert alert-error">
				<?php echo implode( '<br>', $this->errors ) ?>
			</div><?php
		endif;
	}

	/**
	 * Renders BuddyPress registration form.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 */
	private function _render_registration_bp_form() {
		?><form id="signup_form" class="standard-form" method="post" enctype="multipart/form-data">
			<input type="hidden" name="type" value="bp">

			<h2><?php _e( 'Create an Account', 'membership' ) ?></h2>

			<?php do_action( 'template_notices' ) ?>
			<?php $this->_render_errors() ?>

			<p><?php _e( "Registering for this site is easy, just fill in the fields below and we'll get a new account set up for you in no time.", 'membership' ) ?></p>

			<?php do_action( 'bp_before_account_details_fields' ) ?>
			<div id="basic-details-section" class="register-section">
				<h4><?php _e( 'Account Details', 'membership' ) ?></h4>

				<div class="editfield">
					<label for="signup_username"><?php _e( 'Username (required)', 'membership' ) ?></label>
					<?php do_action( 'bp_signup_username_errors' ) ?>
					<input type="text" name="signup_username" id="signup_username" value="<?php bp_signup_username_value() ?>">
				</div>

				<div class="editfield">
					<label for="signup_email"><?php _e( 'Email Address (required)', 'membership' ) ?></label>
					<?php do_action( 'bp_signup_email_errors' ) ?>
					<input type="text" name="signup_email" id="signup_email" value="<?php bp_signup_email_value() ?>">
				</div>

				<div class="editfield">
					<label for="signup_password"><?php _e( 'Choose a Password (required)', 'membership' ) ?></label>
					<?php do_action( 'bp_signup_password_errors' ) ?>
					<input type="password" name="signup_password" id="signup_password">
				</div>

				<div class="editfield">
					<label for="signup_password_confirm"><?php _e( 'Confirm Password (required)', 'membership' ) ?></label>
					<?php do_action( 'bp_signup_password_confirm_errors' ) ?>
					<input type="password" name="signup_password_confirm" id="signup_password_confirm">
				</div>

				<?php do_action( 'bp_before_account_details_end' ) ?>
			</div>
			<?php do_action( 'bp_after_account_details_fields' ) ?>

			<?php $this->_render_registration_bp_xprofile() ?>

			<?php do_action( 'bp_before_registration_submit_buttons' ) ?>
			<div class="submit">
				<input type="submit"name="signup_submit" id="signup_submit" value="<?php _e( 'Sign Up', 'membership' ) ?> &rarr;">
			</div>

			<?php do_action( 'bp_after_registration_submit_buttons' ) ?>
			<?php wp_nonce_field( 'bp_new_signup' ) ?>

			<?php do_action( 'bp_custom_signup_steps' ) ?>
		</form><?php
	}

	/**
	 * Renders BuddyPress xprofile fields.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 */
	private function _render_registration_bp_xprofile() {
		if ( !function_exists( 'bp_is_active' ) || !bp_is_active( 'xprofile' ) ) {
			return;
		}

		do_action( 'bp_before_signup_profile_fields' );
		?><div class="register-section" id="profile-details-section">
			<h4><?php _e( 'Profile Details', 'membership' ) ?></h4><?php

			if ( bp_has_profile( 'profile_group_id=1&hide_empty_fields=0' ) ) :
				while ( bp_profile_groups() ) :
					bp_the_profile_group();

					while ( bp_profile_fields() ) :
						bp_the_profile_field();
						?><div class="editfield"><?php

							$method = '_render_bp_xprofile_' . bp_get_the_profile_field_type();
							if ( method_exists( $this, $method ) ) :
								call_user_func( array( $this, $method ) );
							endif;

							do_action( 'bp_custom_profile_edit_fields' );
							?><p class="description"><?php bp_the_profile_field_description() ?></p>
						</div><?php
					endwhile;

					?><input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bp_the_profile_group_field_ids() ?>"><?php
				endwhile;
			endif;
		?></div><?php
		do_action( 'bp_after_signup_profile_fields' );
	}

	/**
	 * Renders BuddyPress xprofile textbox field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_textbox() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );

		?><label for="<?php echo $field_input_name ?>"><?php
			bp_the_profile_field_name();
			if ( bp_get_the_profile_field_is_required() ) :
				echo ' ' . __( '(required)', 'membership' );
			endif;
		?></label>

		<?php do_action( "bp_{$field_input_name}_errors" ) ?>

		<input type="text" id="<?php echo $field_input_name ?>" name="<?php echo $field_input_name ?>" value="<?php bp_the_profile_field_edit_value() ?>"><?php
	}

	/**
	 * Renders BuddyPress xprofile textarea field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_textarea() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );

		?><label for="<?php echo $field_input_name ?>"><?php
			bp_the_profile_field_name();
			if ( bp_get_the_profile_field_is_required() ) :
				echo ' ' . __( '(required)', 'membership' );
			endif;
		?></label>

		<?php do_action( "bp_{$field_input_name}_errors" ) ?>

		<textarea id="<?php echo $field_input_name ?>" name="<?php echo $field_input_name ?>" rows="5" cols="40"><?php
			bp_the_profile_field_edit_value();
		?></textarea><?php
	}

	/**
	 * Renders BuddyPress xprofile selectbox field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_selectbox() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );

		?><label for="<?php echo $field_input_name ?>"><?php
			bp_the_profile_field_name();
			if ( bp_get_the_profile_field_is_required() ) :
				echo ' ' . __( '(required)', 'membership' );
			endif;
		?></label>

		<?php do_action( "bp_{$field_input_name}_errors" ) ?>

		<select id="<?php echo $field_input_name ?>" name="<?php echo $field_input_name ?>">
			<?php bp_the_profile_field_options() ?>
		</select><?php
	}

	/**
	 * Renders BuddyPress xprofile multi selectbox field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_multiselectbox() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );

		?><label for="<?php echo $field_input_name ?>"><?php
			bp_the_profile_field_name();
			if ( bp_get_the_profile_field_is_required() ) :
				echo ' ' . __( '(required)', 'membership' );
			endif;
		?></label>

		<?php do_action( "bp_{$field_input_name}_errors" ) ?>

		<select id="<?php echo $field_input_name ?>" name="<?php echo $field_input_name ?>" multiple="multiple">
			<?php bp_the_profile_field_options() ?>
		</select><?php
	}

	/**
	 * Renders BuddyPress xprofile radio field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_radio() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );
		$field_required = bp_get_the_profile_field_is_required();

		?><div class="radio">
			<span class="label"><?php
				bp_the_profile_field_name();
				if ( $field_required ) :
					echo ' ' . __( '(required)', 'membership' );
				endif;
			?></span>

			<?php do_action( "bp_{$field_input_name}_errors" ) ?>
			<?php bp_the_profile_field_options() ?>

			<?php if ( !$field_required ) : ?>
				<a class="clear-value" href="javascript:clear('<?php bp_the_profile_field_input_name() ?>');"><?php _e( 'Clear', 'membership' ) ?></a>
			<?php endif; ?>
		</div><?php
	}

	/**
	 * Renders BuddyPress xprofile checkbox field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_checkbox() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );

		?><div class="checkbox">
			<span class="label"><?php
				bp_the_profile_field_name();
				if ( bp_get_the_profile_field_is_required() ) :
					echo ' ' . __( '(required)', 'membership' );
				endif;
			?></span>

			<?php do_action( "bp_{$field_input_name}_errors" ) ?>
			<?php bp_the_profile_field_options() ?>
		</div><?php
	}

	/**
	 * Renders BuddyPress xprofile datebox field.
	 *
	 * @since 3.4.5
	 *
	 * @access protected
	 */
	protected function _render_bp_xprofile_datebox() {
		$field_input_name = esc_attr( bp_get_the_profile_field_input_name() );

		?><div class="datebox">
			<label for="<?php echo $field_input_name ?>"><?php
				bp_the_profile_field_name();
				if ( bp_get_the_profile_field_is_required() ) :
					echo ' ' . __( '(required)', 'membership' );
				endif;
			?></label>

			<?php do_action( "bp_{$field_input_name}_errors" ) ?>

			<select id="<?php echo $field_input_name ?>_day" name="<?php echo $field_input_name ?>_day">
				<?php bp_the_profile_field_options( 'type=day' ) ?>
			</select>
			<select id="<?php echo $field_input_name ?>_month" name="<?php echo $field_input_name ?>_month">
				<?php bp_the_profile_field_options( 'type=month' ) ?>
			</select>
			<select id="<?php echo $field_input_name ?>_year" name="<?php echo $field_input_name ?>_year">
				<?php bp_the_profile_field_options( 'type=year' ) ?>
			</select>
		</div><?php
	}

}