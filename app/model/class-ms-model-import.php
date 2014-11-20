<?php
/**
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
 * Class that handles Import/Export functions.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Import extends MS_Model {

	// Action definitions.
	const ACTION_EXPORT = 'export';
	const ACTION_IMPORT = 'import';

	/**
	 * Main entry point: Processes the import/export action.
	 *
	 * This function is called by the settings-controller whenever the
	 * Import/Export tab provides a correct nonce. We will first find out which
	 * action to execute and then handle all the details...
	 *
	 * @since  1.1.0
	 */
	public function process() {
		WDev()->load_post_fields( 'action' );

		switch ( $_POST['action'] ) {
			case self::ACTION_EXPORT:
				$handler = MS_Factory::create( 'MS_Model_Import_Export' );
				$handler->process();
				break;

			case self::ACTION_IMPORT:
				$this->process_import();
				break;
		}
	}

	/**
	 * Processes the import data.
	 *
	 * @since  1.1.0
	 */
	protected function process_import() {
		WDev( 'debug', '- Not done yet -' );
	}

}
