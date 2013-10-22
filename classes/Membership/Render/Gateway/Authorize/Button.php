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
 * Renders Authorize.net button.
 *
 * @category Membership
 * @package Render
 * @subpackage Gateway
 *
 * @since 3.5
 */
class Membership_Render_Gateway_Authorize_Button extends Membership_Render {

	/**
	 * Renders button template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	protected function _to_html() {
		?><form id="signup-form" action="<?php echo esc_url( $this->actionurl )  ?>" method="post">
			<input type="hidden" name="gateway" id="subscription_gateway" value="<?php echo esc_attr( $this->gateway ) ?>">
			<input type="hidden" name="extra_form" value="1">
			<input type="hidden" name="subscription" id="subscription_id" value="<?php echo $this->subscription_id ?>">
			<input type="hidden" name="user" id="subscription_user_id" value="<?php echo $this->user_id ?>">
			<input type="hidden" name="coupon_code" id="subscription_coupon_code" value="<?php echo esc_attr( $this->coupon_code ) ?>">

			<input type="submit" class="button <?php echo esc_attr( apply_filters( 'membership_subscription_button_color', 'blue' ) ) ?>" value="<?php esc_attr_e( 'Pay Now', 'membership' ) ?>">
		</form><?php
	}

}