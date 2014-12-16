<?php
/**
 * This file defines the MS_Controller_Addon class.
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
 * Controller for Membership add-ons.
 *
 * Manages the activating and deactivating of Membership addons.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Addon extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_ADDON = 'toggle_addon';

	/**
	 * Prepare the Add-on manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$addon_menu_hook = 'protect-content_page_protected-content-addon';

		// Load the add-on manager model.
		$this->add_action(
			'load-' . $addon_menu_hook,
			'admin_addon_process'
		);

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'auto_setup_addons'
		);

		$this->add_action(
			'wp_ajax_' . self::AJAX_ACTION_TOGGLE_ADDON,
			'ajax_action_toggle_addon'
		);

		// Enqueue scripts and styles.
		$this->add_action(
			'admin_print_scripts-' . $addon_menu_hook,
			'enqueue_scripts'
		);
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_toggle_gateway
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_addon() {
		$msg = 0;

		if ( $this->verify_nonce()
			&& ! empty( $_POST['addon'] )
			&& $this->is_admin_user()
		) {
			$msg = $this->save_addon( 'toggle_activation', array( $_POST['addon'] ) );
		}

		echo $msg;
		exit;
	}

	/**
	 * Auto setup addons when membership setup is completed.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_setup_completed
	 *
	 * @since 1.0.0
	 */
	public function auto_setup_addons( $membership ) {
		$addon = MS_Factory::load( 'MS_Model_Addon' );

		$addon->auto_config( $membership );
		$addon->save();
	}

	/**
	 * Handles Add-on admin actions.
	 *
	 * Handles activation/deactivation toggles and bulk update actions, then saves the model.
	 *
	 * @since 1.0.0
	 */
	public function admin_addon_process() {
		/**
		 * Hook into the Addon request handler before processing.
		 *
		 * **Note:**
		 * This action uses the "raw" request objects which could lead to SQL injections / XSS.
		 * By hooking this action you need to take **responsibility** for filtering user input.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Controller_Addon object.
		 */
		do_action( 'ms_controller_addon_admin_addon_process', $this );

		$msg = 0;
		$fields = array( 'addon', 'action', 'action2' );

		if ( $this->verify_nonce( 'bulk-addons' )
			&& $this->validate_required( $fields )
		) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->save_addon( $action, $_POST['addon'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
			exit;
		}
	}


	/**
	 * Load and render the Add-on manager view.
	 *
	 * @since 1.0.0
	 */
	public function admin_addon() {
		/**
		 * Create / Filter the Addon admin view.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Controller_Addon object.
		 */
		$view = MS_Factory::create( 'MS_View_Addon' );
		$data = array(
			'addon' => MS_Factory::load( 'MS_Model_Addon' ),
		);

		$view->data = apply_filters( 'ms_view_addon_data', $data );
		$view->render();
	}

	/**
	 * Call the model to save the addon settings.
	 *
	 * Saves activation/deactivation settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The action to perform on the add-on
	 * @param object[] $addon_types The add-on or add-ons types to update.
	 */
	public function save_addon( $action, $addon_types ) {
		if ( ! $this->is_admin_user() ) {
			return;
		}

		$addon = MS_Factory::load( 'MS_Model_Addon' );

		foreach ( $addon_types as $addon_type ) {
			switch ( $action ) {
				case 'enable':
					$addon->enable( $addon_type );
					break;

				case 'disable':
					$addon->disable( $addon_type );
					break;

				case 'toggle_activation':
					$addon->toggle_activation( $addon_type );
					break;
			}
		}

		$addon->save();
		return true;
	}

	/**
	 * Load Add-on specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array(),
		);

		$data['ms_init'][] = 'view_addons';

		wp_localize_script( 'ms-admin', 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}