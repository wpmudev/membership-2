<?php
/**
 * This file defines the MS_Controller_Widget class.
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
 * Controller for Membership widgets.
 *
 * This is not doing much, since most of the widget logic is handled by
 * WordPress itself. We mainly need to register available widgets.
 *
 * @since 1.1.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Widget extends MS_Controller {

	/**
	 * Register available widgets.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		parent::__construct();

		// Load the add-on manager model.
		$this->add_action(
			'widgets_init',
			'register_widgets'
		);
	}

	/**
	 * Register available widgets.
	 *
	 * @since 1.1.0
	 */
	public function register_widgets() {
		register_widget( 'MS_Widget_Login' );
	}

}