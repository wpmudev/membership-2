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
 * Renders popup registration form template.
 *
 * @category Membership
 * @package Render
 * @subpackage Page
 * @subpackage Registration
 *
 * @since 3.5
 */
class Membership_Render_Page_Registration_Popup extends Membership_Render {

	/**
	 * Renders registration template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	protected function _to_html() {
		if ( defined( 'MEMBERSHIP_POPOVER_SIGNUP_FORM' ) && is_readable( MEMBERSHIP_POPOVER_SIGNUP_FORM ) ) {
			include MEMBERSHIP_POPOVER_SIGNUP_FORM;
		} else {
			$filename = apply_filters( 'membership_override_popover_signup_form', '' );
			if ( !empty( $filename ) && is_readable( $filename ) ) {
				include $filename;
			} else {
				$this->_render_standard_form();
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
		?><div class="membership-popover-signup-wrapper">
			<div class="header">
				<h1><?php _e( 'Register or Login to purchase', 'membership' ) ?></h1>
			</div>

			<div class="leftside">
				<p><?php _e( 'Enter your details below to create a new account.', 'membership' ) ?></p>

				<p class="error" id="reg-error"></p>

				<form id="reg-form" action="<?php echo get_permalink() ?>" method="post">
					<input type="hidden" name="action" value="validatepage1">
					<input type="hidden" name="subscription" value="<?php echo absint( filter_input( INPUT_GET, 'subscription' ) ) ?>" id="reg_subscription">

					<div>
						<label for="reg_first_name"><?php _e( 'First Name', 'membership' ) ?></label>
						<input type="text" value="<?php echo esc_attr( filter_input( INPUT_POST, 'first_name' ) ) ?>" class="regtext" x-autocompletetype="given-name" name="first_name" id="reg_first_name">
					</div>

					<div>
						<label for="reg_last_name"><?php _e( 'Last Name', 'membership' ) ?></label>
						<input type="text" value="<?php echo esc_attr( filter_input( INPUT_POST, 'last_name' ) ) ?>" class="regtext" x-autocompletetype="family-name" name="last_name" id="reg_last_name">
					</div>

					<div>
						<label for="reg_user_login"><?php _e( 'Username', 'membership' ) ?> <span>*</span></label>
						<input type="text" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_login' ) ) ?>" required class="regtext" x-autocompletetype="nickname" name="user_login" id="reg_user_login">
					</div>

					<div>
						<label for="reg_user_email"><?php _e( 'Email Address', 'membership' ) ?> <span>*</span></label>
						<input type="text" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_email' ) ) ?>" required x-autocompletetype="email" class="regtext" name="user_email" id="reg_user_email">
					</div>

					<div>
						<label for="reg_password"><?php _e( 'Password', 'membership' ) ?> <span>*</span></label>
						<input type="password" autocomplete="off" class="regtext" name="password" required id="reg_password">
					</div>

					<div>
						<label for="reg_password2"><?php _e( 'Confirm Password', 'membership' ) ?> <span>*</span></label>
						<input type="password" autocomplete="off" class="regtext" name="password2" required id="reg_password2">
					</div>

					<?php if ( function_exists( 'signup_tos_shortcode' ) ) : ?>
						<div>
							<label id="tos_content"><?php _e( 'Terms Of Service', 'membership' ) ?> <span>*</span></label>
							<?php echo signup_tos_shortcode( array( 'checkbox' => 1, 'show_label' => 0 ) ) ?>
						</div>
					<?php endif; ?>

					<?php do_action( 'membership_popover_extend_registration_form' ) ?>

					<p>
						<input type="submit" value="<?php _e( 'Register My Account', 'membership' ) ?> &raquo;" class="button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>" name="register">
					</p>
				</form>
			</div>

			<div class="rightside">
				<p><?php _e( 'Login below if you are already registered.', 'membership' ) ?></p>
				<p class="error" id="login-error"><?php _e( 'This is an error', 'membership' ) ?></p>
				<form id="login-form" action="<?php echo get_permalink() ?>" method="post">
					<input type="hidden" name="action" value="loginaccount">

					<div>
						<label for="login_user_login"><?php _e( 'Username', 'membership' ) ?></label>
						<input type="text" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_login' ) ) ?>" class="regtext" name="user_login" id="login_user_login">
					</div>
					<div>
						<label for="login_password"><?php _e( 'Password', 'membership' ) ?></label>
						<input type="password" autocomplete="off" class="regtext" name="password" id="login_password">
					</div>

					<?php do_action( 'membership_popover_extend_login_form' ) ?>

					<p>
						<input type="submit" value="<?php _e( 'Login', 'membership' ); ?> &raquo;" class="button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>" name="register">
					</p>
				</form>
			</div>
		</div><?php
	}

}