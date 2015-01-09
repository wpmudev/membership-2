<?php
/**
 * This file defines the MS_Helper object.
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
 * Integrations class.
 *
 * Manage integration loading.
 *
 * @since 1.0.0
 *
 * @package Membership
 */
class MS_Integration extends MS_Hooker {

	/**
	 * Integration name constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AUTOMESSAGE = 'automessage';
	const BUDDYPRESS = 'buddypress';
	const BBPRESS = 'buddypress';
	const WPBE = 'wpbe';

	/**
	 * Parent constuctor of all integrations.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		do_action( 'ms_integration_construct', $this );
	}

	/**
	 * Load integrations.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static function load_integrations() {

		return apply_filters( 'ms_integration_load_integrations', array(
// 				self::AUTOMESSAGE => MS_Factory::create( 'MS_Integration_Automessage' ), //further versions
				self::BUDDYPRESS => MS_Factory::create( 'MS_Integration_Buddypress' ),
				self::BBPRESS => MS_Factory::create( 'MS_Integration_Bbpress' ),
// 				self::WPBE => MS_Factory::create( 'MS_Integration_Wpbe' ), //further versions
		) );
	}
}