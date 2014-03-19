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
		if ( $this->is_popup ) : ?>
			<div class="header">
				<h1><?php printf( esc_html_x( 'Sign up for %s', 'Sing up for SUBSCRIPTION_NAME', 'membership' ), $this->subscription_name ) ?></h1>
			</div>
			<div class="fullwidth">
		<?php else: ?>
			<div>
				<legend class="membership-page-header"><?php printf( esc_html_x( 'Sign up for %s', 'Sing up for SUBSCRIPTION_NAME', 'membership' ), $this->subscription_name ) ?></legend>
		<?php endif; ?>
				<form class="membership_payment_form authorizenet single" method="post">
					<input type="hidden" name="gateway" value="<?php echo esc_attr( $this->gateway ) ?>">
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $this->user_id ) ?>">
					<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $this->subscription_id ) ?>">
					<input type="hidden" name="from_subscription" value="<?php echo esc_attr( $this->from_subscription ) ?>">
					<input type="hidden" name="coupon_code" value="<?php echo esc_attr( $this->coupon ) ?>">

					<div class="membership_cart_billing<?php echo !empty( $this->cim_profiles ) ? ' auth-has-cim' : '' ?>">
						<?php $this->_render_cim_profiles() ?>

						<div id="authorize_errors"></div>

						<div id="auth-new-cc-body">
							<?php $this->_render_card_fields() ?>
							<?php $this->_render_billing_fields() ?>
						</div>
						<div class="authorize-form-submit">
							<input type="submit" class="button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>" value="<?php esc_attr_e( 'Process Payment', 'membership' ) ?>">
						</div>
					</div>
				</form>
		<?php if ( $this->is_popup ) : ?>
			</div>
		<?php endif;
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

		?><div id="auth-cim-profiles" class="authorize-form-block">
			<div class="authorize-form-block-title"><?php esc_html_e( 'Payment Profile', 'membership' ) ?></div>

			<ul>
				<?php foreach ( $this->cim_profiles as $index => $profile ) : ?>
					<?php if ( is_array( $profile ) && !empty( $profile['customerPaymentProfileId'] ) ) : ?>
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
					<?php endif; ?>
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
		?><div class="authorize-form-block">
			<div class="authorize-form-block-title"><?php esc_html_e( 'Billing Information', 'membership' ) ?></div>

			<div class="authorize-form-field">
				<label for="first_name"><?php esc_html_e( 'First Name:', 'membership' ) ?></label>
				<input id="first_name" name="first_name" x-autocompletetype="given-name" class="authorize-form-input" type="text" maxlength="50">
			</div>

			<div class="authorize-form-field">
				<label for="last_name"><?php esc_html_e( 'Last Name:', 'membership' ) ?></label>
				<input id="last_name" name="last_name" x-autocompletetype="family-name" class="authorize-form-input" type="text" maxlength="50">
			</div>

			<div class="authorize-form-field">
				<label for="company"><?php esc_html_e( 'Company:', 'membership' ) ?></label>
				<input id="company" name="company" x-autocompletetype="org" class="authorize-form-input" type="text" maxlength="50">
			</div>

			<div class="authorize-form-field">
				<label for="address"><?php esc_html_e( 'Address:', 'membership' ) ?></label>
				<input id="address" name="address" class="authorize-form-input" type="text" x-autocompletetype="address-line1" maxlength="60">
			</div>

			<div class="authorize-form-field">
				<label for="city"><?php esc_html_e( 'City:', 'membership' ) ?></label>
				<input id="city" name="city" class="authorize-form-input" type="text" x-autocompletetype="city" maxlength="40">
			</div>

			<div class="authorize-form-field">
				<label for="state"><?php esc_html_e( 'State:', 'membership' ) ?></label>
				<input id="state" name="state" class="authorize-form-input" x-autocompletetype="administrative-area" type="text" maxlength="40">
			</div>

			<div class="authorize-form-field">
				<label for="zip"><?php esc_html_e( 'Zip Code:', 'membership' ) ?></label>
				<input id="zip" name="zip" class="authorize-form-input" x-autocompletetype="postal-code" type="text" maxlength="20">
			</div>

			<div class="authorize-form-field">
				<label for="country"><?php esc_html_e( 'Country:', 'membership' ) ?></label>
				<select id="country" x-autocompletetype="country-name" class="authorize-form-input" name="country">
					<option></option>
					<?php foreach( self::get_countries() as $country ) : ?>
					<option><?php echo esc_html( $country ) ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="authorize-form-field">
				<label for="phone"><?php esc_html_e( 'Phone:', 'membership' ) ?></label>
				<input id="phone" name="phone" x-autocompletetype="tel" class="authorize-form-input" type="text" maxlength="25">
			</div>

			<div class="authorize-form-field">
				<label for="fax"><?php esc_html_e( 'Fax:', 'membership' ) ?></label>
				<input id="fax" name="fax" x-autocompletetype="fax" class="authorize-form-input" type="text" maxlength="25">
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
		?><div class="authorize-form-block">
			<div class="authorize-form-block-title"><?php esc_html_e( 'Credit Card Information', 'membership' ) ?></div>

			<div class="authorize-form-field">
				<label for="card_num"><?php esc_html_e( 'Number:', 'membership' ) ?></label>
				<input type="text" id="card_num" class="auth-cc-cardnum credit_card_number authorize-form-input" name="card_num" x-autocompletetype="cc-number" maxlength="22">
			</div>

			<div class="authorize-form-field">
				<label for="card_code"><?php esc_html_e( 'Security Code:', 'membership' ) ?></label>
				<input id="card_code" name="card_code" class="authorize-form-input" type="text" maxlength="4" autocomplete="off">
			</div>

			<div class="authorize-form-field">
				<label for="exp_month"><?php esc_html_e( 'Expiration Date:', 'membership' ) ?></label>
				<div class="authorize-form-input">
					<select name="exp_month" x-autocompletetype="cc-exp-month" id="exp_month">
						<option value=""><?php esc_html_e( 'Month', 'membership' ) ?></option>
						<?php for ( $i = 1, $date = new DateTime( '01-01-1970' ); $i <= 12; $date->setDate( 2013, ++$i, 1 ) ) : ?>
							<option value="<?php echo $i ?>"><?php echo $date->format( 'm - M' ) ?></option>
						<?php endfor; ?>
					</select>
					<select name="exp_year" x-autocompletetype="cc-exp-year" id="exp_year">
						<option value=""><?php esc_html_e( 'Year', 'membership' ) ?></option>
						<?php for ( $i = date( 'Y' ), $maxYear = $i + 15; $i <= $maxYear; $i++ ) : ?>
							<option><?php echo $i ?></option>
						<?php endfor; ?>
					</select>
				</div>
			</div>
		</div><?php
	}

	/**
	 * Returns the associated array of country codes and country names.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 * @return array The associated array of country codes and country names.
	 */
	public static function get_countries() {
		return array(
			"Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra",
			"Angola", "Anguilla", "Antarctica", "Antigua and Barbuda",
			"Argentina", "Armenia", "Aruba", "Australia", "Austria",
			"Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados",
			"Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan",
			"Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil",
			"British Indian Ocean Territory", "Brunei", "Bulgaria",
			"Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada",
			"Cape Verde", "Cayman Islands", "Central African Republic", "Chad",
			"Chile", "China", "Christmas Islands", "Cocos (Keeling) Islands",
			"Colombia", "Comoros", "Congo", "Congo, Democratic Republic of",
			"Cook Island", "Costa Rica", "Cote d'lvoire", "Croatia", "Curacao",
			"Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica",
			"Dominican Republic", "East Timor", "Egypt", "El Salvador",
			"Ecuador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia",
			"Falkland Islands", "Faroe Islands",
			"Federated States of Micronesia", "Fiji", "Finland", "France",
			"French Guiana", "French Polynesia", "French Southern Territories",
			"Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar",
			"Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala",
			"Guinea", "Guinea-Bissau", "Guyana", "Haiti",
			"Heard and Macdonald Islands", "Honduras", "Hong Kong", "Hungary",
			"Iceland", "India", "Indonesia", "Iraq", "Ireland", "Israel",
			"Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya",
			"Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan",
			"Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya",
			"Liechtenstein", "Lithuania", "Luxembourg", "Macau",
			"Macedonia (Rep. of Fmr Yugoslav)", "Madagascar", "Malawi",
			"Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands",
			"Martinique", "Mauritania", "Mauritius", "Mayotte",
			"Metropolitan France", "Mexico", "Moldova", "Monaco", "Mongolia",
			"Montenegro", "Montserrat", "Morocco", "Mozambique", "Myanmar",
			"Namibia", "Nauru", "Nepal", "Netherlands", "New Caledonia",
			"New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue",
			"Norfolk Island", "Northern Mariana Islands", "Norway", "Oman",
			"Pakistan", "Palau", "Palestinian Territory, Occupied", "Panama",
			"Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn",
			"Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania",
			"Russia", "Rwanda", "S. Georgia and S. Sandwich Islands", "Samoa",
			"San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal",
			"Serbia, Republic of", "Seychelles", "Sierra Leone", "Singapore",
			"Sint Maarten", "Slovakia", "Slovenia", "Solomon Islands", "Somalia",
			"South Africa", "Spain", "Sri Lanka", "St Helena",
			"St Kitts and Nevis", "St Lucia", "St Pierre and Miquelon",
			"St Vincent and the Grenadines", "Suriname",
			"Svalbard and Jan Mayen Islands", "Swaziland", "Sweden",
			"Switzerland", "Syria", "Taiwan", "Tajikistan", "Tanzania",
			"Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago",
			"Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands",
			"Tuvalu", "Uganda", "Ukraine", "United Arab Emirates",
			"United Kingdom", "United States",
			"United States Minor Outlying Islands", "Uruguay", "Uzbekistan",
			"Vanuatu", "Vatican City", "Venezuela", "Vietnam",
			"Virgin Islands - British", "Virgin Islands - US",
			"Wallis and Futuna Islands", "Western Sahara", "Yemen", "Zaire",
			"Zambia", "Zimbabwe",
		);
	}

}