<?php
/**
 * An Addon controller.
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
 * Add-On controller for: Redirect control
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Addon_Redirect extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'addon_redirect';

	// Ajax Actions
	const AJAX_SAVE_SETTING = 'addon_redirect_save';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		if ( self::is_active() ) {
			// Add new settings tab
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10, 2
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10, 3
			);

			// Save settings via ajax
			$this->add_action(
				'wp_ajax_' . self::AJAX_SAVE_SETTING,
				'ajax_save_setting'
			);

			// Add filter to replace the default plugin URLs with custom URLs
			$this->add_action(
				'ms_url_after_login',
				'filter_url_after_login',
				10, 2
			);

			$this->add_action(
				'ms_url_after_logout',
				'filter_url_after_logout',
				10, 2
			);
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Redirect Control', MS_TEXT_DOMAIN ),
			'description' => __( 'Define your individual URL to display after a user is logged-in or logged-out.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-share',
			'details' => array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => __( 'Settings', MS_TEXT_DOMAIN ),
					'desc' => __( 'When this Add-on is enabled you will see a new section in the "Settings" page with additional options.', MS_TEXT_DOMAIN ),
				),
			),
		);

		return $list;
	}

	/**
	 * Returns the Redirect-Settings model.
	 *
	 * @since  1.1.0
	 * @return MS_Addon_Redirect_Model
	 */
	static public function model() {
		static $Model = null;

		if ( null === $Model ) {
			$Model = MS_Factory::load( 'MS_Addon_Redirect_Model' );
		}

		return $Model;
	}

	/**
	 * Add redirect settings tab in settings page.
	 *
	 * @since 1.1.0
	 *
	 * @param array $tabs The current tabs.
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'Redirect', MS_TEXT_DOMAIN ),
			'url' => 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-settings&tab=' . self::ID,
		);

		return $tabs;
	}

	/**
	 * Add redirect settings-view callback.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $callback The current function callback.
	 * @param  string $tab The current membership rule tab.
	 * @param  array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Redirect_View' );
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * @since 1.1.0
	 */
	public function ajax_save_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$model = self::model();

			$model->set( $_POST['field'], $_POST['value'] );
			$model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg );
	}

	/**
	 * Replaces the default "After Login" URL
	 *
	 * @since  1.1.0
	 *
	 * @param  string $url
	 * @return string
	 */
	public function filter_url_after_login( $url, $enforce ) {
		if ( ! $enforce ) {
			$model = self::model();
			$new_url = $model->get( 'redirect_login' );

			if ( ! empty( $new_url ) ) {
				$url = lib2()->net->expand_url( $new_url );
			}
		}

		return $url;
	}

	/**
	 * Replaces the default "After Logout" URL
	 *
	 * @since  1.1.0
	 *
	 * @param  string $url
	 * @return string
	 */
	public function filter_url_after_logout( $url, $enforce ) {
		if ( ! $enforce ) {
			$model = self::model();
			$new_url = $model->get( 'redirect_logout' );

			if ( ! empty( $new_url ) ) {
				$url = lib2()->net->expand_url( $new_url );
			}
		}

		return $url;
	}

}