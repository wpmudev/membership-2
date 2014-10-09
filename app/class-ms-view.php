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
 * Abstract class for all Views.
 *
 * All views will extend or inherit from the MS_View class.
 * Methods of this class will prepare and output views.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_View extends MS_Hooker {

	/**
	 * The storage of all data associated with this render.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The data what has to be associated with this render.
	 */
	public function __construct( $data = array() ) {
		
		$this->data = $data;

		/**
		 * Actions to execute when constructing the parent View.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_View object.
		 */
		do_action( 'ms_view_construct', $this );
	}

	/**
	 * Builds template and return it as string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function to_html() {
		/* This function is implemented different in each child class. */
		return apply_filters( 'ms_view_to_html', '' );
	}

	/**
	 * Renders the template.
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$html = $this->to_html();

		echo apply_filters( 'ms_view_render', $html );
	}
}