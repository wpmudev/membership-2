<?php
/**
 * This file defines the MS_Helper_Shortcode class.
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
 */

/**
 * This Helper creates utility functions for working with shortcodes.
 *
 * @since 4.0.0
 * @package Membership2
 * @subpackage Controller
 */
class MS_Helper_Shortcode extends MS_Helper {

	const SCODE_REGISTER_USER = 'ms-membership-register-user';

	const SCODE_SIGNUP = 'ms-membership-signup';

	const SCODE_MS_TITLE = 'ms-membership-title';

	const SCODE_MS_DETAILS = 'ms-membership-details';

	const SCODE_MS_PRICE = 'ms-membership-price';

	const SCODE_MS_BUY = 'ms-membership-buy';

	const SCODE_PROTECTED = 'ms-protection-message';

	const SCODE_LOGIN = 'ms-membership-login';

	const SCODE_LOGOUT = 'ms-membership-logout';

	const SCODE_MS_ACCOUNT = 'ms-membership-account';

	const SCODE_MS_ACCOUNT_LINK = 'ms-membership-account-link';

	const SCODE_MS_INVOICE = 'ms-invoice';

	const SCODE_NOTE = 'ms-note';

	const SCODE_USER = 'ms-user';

	const SCODE_MEMBER_INFO = 'ms-member-info';

	// deprecated, replaced by SCODE_NOTE
	const SCODE_GREEN_NOTE = 'ms-green-note';

	// deprecated, replaced by SCODE_NOTE
	const SCODE_RED_NOTE = 'ms-red-note';

	/**
	 * Holds an array of all replaced Membership2 shortcodes.
	 *
	 * @since  1.0.4.5
	 *
	 * @var array
	 */
	protected static $did_shortcodes = array();


	/**
	 * This function searches content for the presence of a given short code.
	 *
	 * Returns 'true' if shortcode is found or 'false' if the shortcode is not found.
	 *
	 * @since 4.0.0
	 * @param  string $shortcode The shortcode to find.
	 * @param  string $content The string to search.
	 * @return boolean
	 */
	public static function has_shortcode( $shortcode, $content ) {
		$result = false;

		if ( isset( self::$did_shortcodes[$shortcode] ) ) {
			$result = self::$did_shortcodes[$shortcode];
		} else {
			// Use WP core function `has_shortcode` instead of own logic.
			$result = has_shortcode( $content, $shortcode );
		}

		return $result;
	}

	/**
	 * Resets the shortcode-memory.
	 *
	 * This is required when a page has multiple calls to the_content - if the
	 * usage would not be reset, then only the first call to the_content would
	 * actually add certain shortcodes.
	 *
	 * @since  1.0.4.6
	 */
	public static function reset_shortcode_usage() {
		self::$did_shortcodes = array();
	}

	/**
	 * Remembers that a shortcode was inserted already.
	 *
	 * @since  1.0.4.5
	 * @param  string $shortcode The Membership2 shortcode.
	 */
	public static function did_shortcode( $shortcode ) {
		self::$did_shortcodes[$shortcode] = true;
	}

	/**
	 * Get all membership plugin shortcodes
	 *
	 * @since 4.0.0
	 * @return string[]
	 */
	public static function get_membership_shortcodes() {
		return apply_filters(
			'ms_helper_shortcode_get_membership_shortcodes',
			array(
				self::SCODE_REGISTER_USER,
				self::SCODE_SIGNUP,
				self::SCODE_MS_TITLE,
				self::SCODE_MS_DETAILS,
				self::SCODE_MS_PRICE,
				self::SCODE_MS_BUY,
				self::SCODE_PROTECTED,
				self::SCODE_LOGIN,
				self::SCODE_LOGOUT,
				self::SCODE_MS_ACCOUNT,
				self::SCODE_MS_ACCOUNT_LINK,
				self::SCODE_MS_INVOICE,
				self::SCODE_NOTE,
				self::SCODE_USER,
				self::SCODE_GREEN_NOTE /* deprecated, replaced by SCODE_NOTE */,
				self::SCODE_RED_NOTE /* deprecated, replaced by SCODE_NOTE */,
			)
		);
	}
}
