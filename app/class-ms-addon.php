<?php
/**
 * The Addon-controller base class.
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
 * Add-On controller
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Controller
 */
abstract class MS_Addon extends MS_Controller {

	/**
	 * Reference to the MS_Model_Addon instance.
	 *
	 * @type MS_Model_Addon
	 */
	static protected $model = null;

	/**
	 * Reference to the MS_Model_Settings instance.
	 *
	 * @type MS_Model_Addon
	 */
	static protected $settings = null;

	/**
	 * Initialize the Add-On.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		parent::__construct();

		self::$model = MS_Factory::load( 'MS_Model_Addon' );
		self::$settings = MS_Factory::load( 'MS_Model_Settings' );

		$this->add_filter( 'ms_model_addon_register', 'register' );
		$this->add_action( 'ms_model_addon_initialize', 'init_addon' );
	}

	/**
	 * Initializes the Add-on.
	 *
	 * @since  1.1.0
	 */
	public function init_addon() {
		$this->init();
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	abstract public function init();

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	abstract public function register( $addons );

}