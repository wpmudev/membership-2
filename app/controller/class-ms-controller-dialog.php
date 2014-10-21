<?php
/**
 * This file defines the MS_Controller_Dialog class.
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
 * Controller to manage Membership popup dialogs.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 *
 * @return object
 */
class MS_Controller_Dialog extends MS_Controller {

	/**
	 * Prepare the Dialog manager.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		// Listen to Ajax requests that want to display a popup.
		$this->add_action( 'wp_ajax_ms_dialog', 'ajax_dialog' );

		// Listen to Ajax requests that submit form data.
		$this->add_action( 'wp_ajax_ms_submit', 'ajax_submit' );
	}

	/**
	 * Ajax handler. Returns the HTML code of an popup dialog.
	 * The process is terminated after this handler.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function ajax_dialog() {
		$dialog = @$_REQUEST['dialog'];
		$dlg = MS_Factory::create( 'MS_View_' . $dialog );

		$dlg->prepare();

		$data = array(
			'id' => $dialog,
			'title' => $dlg->title,
			'content' => $dlg->content,
			'height' => $dlg->height,
		);

		echo json_encode( $data );
		exit();
	}

	/**
	 * Ajax handler. Handles incoming form data that was submitted via ajax.
	 * Typically this form is displayed inside a popup.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function ajax_submit() {
		$dialog = @$_REQUEST['dialog'];
		$dlg = MS_Factory::create( 'MS_View_' . $dialog );

		echo json_encode( $dlg->submit() );
		exit();
	}

}