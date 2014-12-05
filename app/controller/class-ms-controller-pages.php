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
	const AJAX_ACTION_UPDATE_PAGES = 'update_pages';

	/**
	 * Construct Settings manager.
	 *
	 * @since 1.0.4.5
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_PAGES, 'ajax_action_update_pages' );
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
			&& $this->validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$ms_pages = $this->get_model();
			$ms_pages->set_setting( $_POST['field'], $_POST['value'] );
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo '' . $msg;
		exit;
	}

}
