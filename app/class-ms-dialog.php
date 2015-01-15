<?php
/**
 * This file defines the MS_View object.
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
 * Abstract class for all Dialog-Views.
 *
 * Dialogs are loaded via Ajax by using the HTML structure
 *  <a href="#" data-ms-dialog="Name_Of_Dialog">Dialog</a>
 *
 * "Name_Of_Dialog" is translated to classname "MS_View_Name_Of_Dialog"
 * All dialogs that are loaded using the above logic must define the specified
 * class and inherit from this base class
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_Dialog extends MS_Controller {

	/**
	 * The Dialog title
	 *
	 * @since 1.0.0
	 * @type string
	 */
	public $title = '';

	/**
	 * Height of the dialog contents
	 *
	 * @since 1.0.0
	 * @type int
	 */
	public $height = 100;

	/**
	 * The dialog contents (HTML Code)
	 *
	 * @since 1.0.0
	 * @type string
	 */
	public $content = '';

	/**
	 * If the dialog is modal
	 *
	 * @since 1.1.0
	 * @type bool
	 */
	public $modal = true;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->title = '';
		$this->height = 100;
		$this->content = '';

		/**
		 * Actions to execute when constructing the parent View.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Dialog object.
		 */
		do_action( 'ms_dialog_construct', $this );
	}

	/**
	 * Must be overwritten in each dialog.
	 * Prepare and populate the members:
	 *    $this->title
	 *    $this->height
	 *    $this->content
	 *
	 * @since 1.0.0
	 * @abstract
	 */
	public function prepare() {
		/* This function is implemented different in each child class. */
	}

	/**
	 * Must be overwritten in each dialog.
	 * Saves form data that was displayed in the dialog.
	 *
	 * @since 1.0.0
	 * @abstract
	 */
	public function submit() {
		/* This function is implemented different in each child class. */
	}

}