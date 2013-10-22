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
 * Renders Authorize.net settings page.
 *
 * @category Membership
 * @package Render
 * @subpackage Gateway
 *
 * @since 3.5
 */
class Membership_Render_Gateway_Authorize_Settings extends Membership_Render {

	/**
	 * Renders button template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	protected function _to_html() {
		?><table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Mode', 'membership' ) ?></th>
					<td>
						<select name="mode">
							<?php foreach ( $this->modes as $mode => $label ) : ?>
								<option value="<?php echo esc_attr( $mode ) ?>"<?php selected( $mode, $this->mode ) ?>><?php
									echo esc_html( $label )
								?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Login ID', 'membership' ) ?></th>
					<td><input type="text" name="api_user" value="<?php echo esc_attr( $this->api_user ) ?>"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Transaction key', 'membership' ) ?></th>
					<td><input type="text" name="api_key" value="<?php echo esc_attr( $this->api_key ) ?>"></td>
				</tr>
			</tbody>
		</table><?php
	}

}
