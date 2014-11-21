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
 * @subpackage Controller
 */
class MS_Controller_Import extends MS_Controller {

	// Action definitions.
	const ACTION_EXPORT = 'export';
	const ACTION_PREVIEW = 'preview';
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
		WDev()->load_post_fields( 'action', 'import_source' );

		switch ( $_POST['action'] ) {
			case self::ACTION_EXPORT:
				$handler = MS_Factory::create( 'MS_Model_Import_Export' );
				$handler->process();
				break;

			case self::ACTION_PREVIEW:
				$model = 'MS_Model_Import_' . $_POST['import_source'];
				$handler = null;

				try {
					$handler = MS_Factory::create( $model );
				} catch( Exception $ex ) {
					self::_message(
						'error',
						__( 'Coming soon: This import source is not supported yet...', MS_TEXT_DOMAIN )
					);
				}

				if ( is_a( $handler, 'MS_Model_Import' ) ) {
					$handler->process();
				}
				break;

			case self::ACTION_IMPORT:
				self::_message(
					'error',
					__( 'Coming soon: Importing data is not supported yet...', MS_TEXT_DOMAIN )
				);
				break;
		}
	}

}
