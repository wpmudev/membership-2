<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Renders Authorize.net transactions list.
 *
 * @category Membership
 * @package Render
 * @subpackage Gateway
 *
 * @since 3.5
 */
class Membership_Render_Gateway_Authorize_Transactions extends Membership_Render {

	/**
	 * Renders transactions template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	protected function _to_html() {
		?><div class="wrap">
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php esc_html_e( 'Authorize.Net transactions', 'membership' ) ?></h2>

			<?php $this->table->views() ?>

			<form id="membership-authorize-transactions">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( $_REQUEST['action'] ) ?>">
				<input type="hidden" name="gateway" value="<?php echo esc_attr( $_REQUEST['gateway'] ) ?>">
				<?php $this->table->display() ?>
			</form>
		</div><?php
	}

}