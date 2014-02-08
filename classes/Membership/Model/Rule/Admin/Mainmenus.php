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
 * Rule class responsible for admin side main menu protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Admin
 */
class Membership_Model_Rule_Admin_Mainmenus extends Membership_Model_Rule {

	public function on_creation() {
		$this->name = 'mainmenus';
		$this->label = __( 'Main Menus', 'membership' );
		$this->description = __( 'Allows admin side main menus to be protected.', 'membership' );
		$this->rulearea = 'admin';
	}

	public function admin_main( $data ) {
		global $menu;

		if ( !$data ) {
			$data = array();
		}

		?><div class='level-operation' id='main-mainmenus'>
			<h2 class='sidebar-name'>
				<?php _e( 'Main Menus', 'membership' ); ?>
				<span>
					<a href='#remove' class='removelink' id='remove-mainmenus' title='<?php _e( "Remove Main Menus from this rules area.", 'membership' ) ?>'>
						<?php _e( 'Remove', 'membership' ); ?>
					</a>
				</span>
			</h2>

			<div class='inner-operation'>
				<p><?php _e( 'Select the Main menus to be covered by this rule by checking the box next to the relevant pages title.', 'membership' ) ?></p>

				<?php if ( !empty( $menu ) ) : ?>
				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
						<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Menu title', 'membership' ) ?></th>
						</tr>
					</thead>

					<tfoot>
					<tr>
						<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Menu title', 'membership' ) ?></th>
						</tr>
					</tfoot>

					<tbody>
						<?php foreach ( $menu as $key => $m ) : ?>
							<?php if ( !empty( $m[0] ) ) : ?>
								<tr valign="middle" class="alternate" id="mainmenus-<?php echo esc_attr( $key ) ?>">
									<th class="check-column" scope="row">
										<input type="checkbox" value="<?php echo esc_attr( $m[2] ) ?>" name="mainmenus[]"<?php checked( in_array( $m[2], $data ) ) ?>>
									</th>
									<td class="column-name">
										<strong><?php echo esc_html( strip_tags( $m[0] ) ) ?></strong>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div><?php
	}

	public function on_positive( $data ) {
		$this->data = $data;
		add_action( 'admin_menu', array( $this, 'pos_admin_menu' ), 999 );
		add_action( 'network_admin_menu', array( $this, 'pos_admin_menu' ), 999 );
	}

	public function on_negative( $data ) {
		$this->data = $data;
		add_action( 'admin_menu', array( $this, 'neg_admin_menu' ), 999 );
		add_action( 'network_admin_menu', array( $this, 'neg_admin_menu' ), 999 );
	}

	public function pos_admin_menu() {
		global $menu;

		foreach ( $menu as $key => $m ) {
			if ( !in_array( $m[2], (array) $this->data ) ) {
				unset( $menu[$key] );
			}
		}
	}

	public function neg_admin_menu() {
		global $menu;

		foreach ( $menu as $key => $m ) {
			if ( in_array( $m[2], (array) $this->data ) ) {
				unset( $menu[$key] );
			}
		}
	}

}
