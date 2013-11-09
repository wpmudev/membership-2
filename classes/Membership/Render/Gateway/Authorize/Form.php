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
 * Renders Authorize.net payment form.
 *
 * @category Membership
 * @package Render
 * @subpackage Gateway
 *
 * @since 3.5
 */
class Membership_Render_Gateway_Authorize_Form extends Membership_Render {

	/**
	 * Renders payment form template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	protected function _to_html() {
		// let 3rd party themes/plugins use their own form
		if ( !apply_filters( 'membership_authorize_render_payment_form', true, $this ) ) {
			return;
		}

		// if we have custom implementation of Authorize.net payment form, then use it
		if ( defined( 'MEMBERSHIP_GATEWAY_AUTHORIZE_FORM' ) && is_readable( MEMBERSHIP_GATEWAY_AUTHORIZE_FORM ) ) {
			include MEMBERSHIP_GATEWAY_AUTHORIZE_FORM;
			return;
		}

		// render form
		$cim_class = !empty( $this->cim_profiles ) ? ' auth-has-cim' : '';

		?><form class="membership_payment_form authorizenet single" method="post">
			<input type="hidden" name="gateway" value="<?php echo esc_attr( $this->gateway ) ?>">
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $this->user_id ) ?>">
			<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $this->subscription_id ) ?>">
			<input type="hidden" name="from_subscription" value="<?php echo esc_attr( $this->from_subscription ) ?>">
			<input type="hidden" name="coupon_code" value="<?php echo esc_attr( $this->coupon ) ?>">

			<div id="authorize_errors"></div>

			<div class="membership_cart_billing<?php echo $cim_class ?>">
				<div class="auth-body">
					<?php $this->_render_cim_profiles() ?>
					<div id="auth-new-cc-body">
						<?php $this->_render_billing_fields() ?>
						<?php $this->_render_card_fields() ?>
					</div>
					<div class="auth-submit">
						<div class="auth-submit-button auth-field">
							<input type="image" src="<?php echo MEMBERSHIP_ABSURL ?>membershipincludes/images/cc_process_payment.png" alt="<?php esc_html_e( 'Pay with Credit Card', 'membership' ) ?>">
						</div>
					</div>
				</div>
			</div>
		</form><?php
	}

	/**
	 * Renders Authorize.net CIM profiles.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _render_cim_profiles() {
		// if profile is empty, then return
		if ( empty( $this->cim_profiles ) ) {
			return;
		}

		// if we have one record in profile, then wrap it into array to make it
		// compatible with case when we have more then one payment methods added
		if ( isset( $this->cim_profiles['billTo'] ) ) {
			$this->cim_profiles = array( $this->cim_profiles );
		}

		?><div id="auth-cim-profiles">
			<ul>
				<?php foreach ( $this->cim_profiles as $index => $profile ) : ?>
				<li>
					<label>
						<input type="radio" name="profile" value="<?php echo esc_attr( $profile['customerPaymentProfileId'] ) ?>"<?php checked( $index, 0 ) ?>>
						<?php echo esc_html( sprintf(
							"%s %s's - XXXXXXX%s - %s, %s, %s",
							$profile['billTo']['firstName'],
							$profile['billTo']['lastName'],
							$profile['payment']['creditCard']['cardNumber'],
							$profile['billTo']['address'],
							$profile['billTo']['city'],
							$profile['billTo']['country']
						) ) ?>
					</label>
				</li>
				<?php endforeach; ?>
				<li id="auth-new-cc">
					<label>
						<input type="radio" name="profile" value="">
						<?php esc_html_e( 'Enter a new credit card', 'membership' ) ?>
					</label>
				</li>
			</ul>
		</div><?php
	}

	/**
	 * Renders billing fields.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _render_billing_fields() {
		?><div class="auth-billing">
			<div class="auth-billing-name auth-field"><?php esc_html_e( 'Billing Information:', 'membership' ) ?>*</div>
			<div class="auth-billing-fname-label auth-field">
				<label class="inputLabel" for="first_name"><?php esc_html_e( 'First Name:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-fname auth-field">
				<input id="first_name" name="first_name" x-autocompletetype="given-name" class="input_field" type="text" maxlength="50">
			</div>
			<div class="auth-billing-lname-label auth-field">
				<label class="inputLabel" for="last_name"><?php esc_html_e( 'Last Name:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-lname auth-field">
				<input id="last_name" name="last_name" x-autocompletetype="family-name" class="input_field" type="text" maxlength="50">
			</div>
			<div class="auth-billing-address-label auth-field">
				<label class="inputLabel" for="address"><?php esc_html_e( 'Address:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-address auth-field">
				<input id="address" name="address" class="input_field" type="text" x-autocompletetype="address-line1" maxlength="60">
			</div>
			<div class="auth-billing-city-label auth-field">
				<label class="inputLabel" for="city"><?php esc_html_e( 'City:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-city auth-field">
				<input id="city" name="city" class="input_field" type="text" x-autocompletetype="city" maxlength="40">
			</div>
			<div class="auth-billing-state-label auth-field">
				<label class="inputLabel" for="state"><?php esc_html_e( 'State:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-state auth-field">
				<input id="state" name="state" class="input_field" x-autocompletetype="administrative-area" type="text" maxlength="40">
			</div>
			<div class="auth-billing-zip-label auth-field">
				<label class="inputLabel" for="zip"><?php esc_html_e( 'Zip Code:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-zip auth-field">
				<input id="zip" name="zip" class="input_field" x-autocompletetype="postal-code" type="text" maxlength="20">
			</div>
			<div class="auth-billing-country-label auth-field">
				<label class="inputLabel" for="country"><?php esc_html_e( 'Country:', 'membership' ) ?></label>
			</div>
			<div class="auth-billing-country auth-field">
				<input id="country" name="country" x-autocompletetype="country-name" class="input_field" type="text" maxlength="60">
			</div>
		</div><?php
	}

	/**
	 * Renders card fields.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _render_card_fields() {
		?><div class="auth-cc">
			<div class="auth-block">
				<div class="auth-cc-label auth-field"><?php esc_html_e( 'Credit Card Number:', 'membership' ) ?>*</div>
				<div class="auth-cc-input auth-field">
					<input type="text" id="card_num" class="auth-cc-cardnum credit_card_number input_field" name="card_num" x-autocompletetype="cc-number" onkeyup="cc_card_pick('#cardimage', '#card_num')" size="22" maxlength="22">
					<div class="hide_after_success nocard cardimage" id="cardimage" style="background: url(<?php echo MEMBERSHIP_ABSURL ?>membershipincludes/images/card_array.png) no-repeat;"></div>
				</div>
			</div>
			<div class="auth-block">
				<div class="auth-exp-label auth-field"><?php esc_html_e( 'Expiration Date:', 'membership' ) ?>*</div>
				<div class="auth-exp-input auth-field">
					<select name="exp_month" x-autocompletetype="cc-exp-month" id="exp_month"><?php echo $this->_render_months() ?></select>
					<select name="exp_year" x-autocompletetype="cc-exp-year" id="exp_year"><?php echo $this->_render_years() ?></select>
				</div>
			</div>
			<div>
				<div class="auth-sec-label auth-field"><?php esc_html_e( 'Security Code:', 'membership' ) ?></div>
				<div class="auth-sec-input auth-field">
					<input id="card_code" name="card_code" class="input_field" type="text" size="4" maxlength="4" autocomplete="off">
				</div>
			</div>
		</div><?php
	}

	/**
	 * Renders years options.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _render_years() {
		$minYear = date( 'Y' );
		$maxYear = $minYear + 15;

		echo '<option value="">', esc_html__( 'Year', 'membership' ), '</option>';
		for ( $i = $minYear; $i < $maxYear; $i++ ) {
			?><option><?php echo $i ?></option><?php
		}
	}

	/**
	 * Renders months options.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _render_months() {
		echo '<option value="">', esc_html__( 'Month', 'membership' ), '</option>';
		$date = new DateTime();
		for	( $i = 1; $i <= 12; $i++ ) {
			$date->setDate( 2013, $i, 1 );
			echo '<option value="', $i, '">', $date->format( 'm - M' ), '</option>';
		}
	}

}