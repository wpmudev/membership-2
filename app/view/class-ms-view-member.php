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
 * Renders Members.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 4.0.0
 *
 * @return object
 */
/* // NOT USED??
class MS_View_Member extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 * /
	public function to_html() {
		ob_start();
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'><?php  _e( 'Membership Members', MS_TEXT_DOMAIN ) ; ?></h2>

		<?php
			$this->render_members();
		?>

		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function render_members() {
		// $rule_list = new MS_Helper_List_Table_Member( $this->model );
		// $rule_list->prepare_items();

		?>
		MEMBERS GO HERE
		<?php
	}


}
*/