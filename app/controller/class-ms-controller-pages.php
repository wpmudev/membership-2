<?php
/**
 * This file defines the MS_Controller_Settings class.
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
 * Controller for managing Membership Pages.
 *
 * @since 1.0.4.5
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Pages extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.4.5
	 *
	 * @var string
	 */
	const AJAX_ACTION_UPDATE_PAGES = 'pages_update';
	const AJAX_ACTION_TOGGLE_MENU = 'pages_toggle_menu';

	/**
	 * Construct Settings manager.
	 *
	 * @since 1.0.4.5
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_PAGES, 'ajax_action_update_pages' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_MENU, 'ajax_action_toggle_menu' );
	}

	/**
	 * Get MS_Model_Pages model
	 *
	 * @since 1.0.4.5
	 *
	 * @return MS_Model_Settings
	 */
	public function get_model() {
		return MS_Factory::load( 'MS_Model_Pages' );
	}

	/**
	 * Handle Ajax update pages setting.
	 *
	 * Related action hooks:
	 * - wp_ajax_update_pages
	 *
	 * @since 1.0.4.5
	 */
	public function ajax_action_update_pages() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$ms_pages = $this->get_model();
			$ms_pages->set_setting( $_POST['field'], $_POST['value'] );
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo '' . $msg;
		exit;
	}

	/**
	 * Handle Ajax toggle menu items.
	 *
	 * Related action hooks:
	 * - wp_ajax_toggle_menu
	 *
	 * @since 1.1.0
	 */
	public function ajax_action_toggle_menu() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'item', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$item = $_POST['item'];
			$res = false;

			if ( WDev()->is_true( $_POST['value'] ) ) {
				$res = MS_Model_Pages::create_menu( $item );
			} else {
				$res = MS_Model_Pages::drop_menu( $item );
			}

			if ( $res ) {
				$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
			}
		}

		echo '' . $msg;
		exit;
	}

}
