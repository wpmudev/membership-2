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
 * Rule class responsible for admin side sub menu protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Admin
 */
class Membership_Model_Rule_Admin_Submenus extends Membership_Model_Rule {

	private function _get_mainmenu_for_file( $file ) {
		global $menu;

		foreach ( $menu as $m ) {
			if ( $m[2] == $file ) {
				return $m[0];
			}
		}
	}

	public function admin_main( $data ) {
		global $menu, $submenu;

		if ( !$data ) {
			$data = array();
		}

		?><div class="level-operation" id="main-submenus">
			<h2 class="sidebar-name">
				<?php _e( 'Sub Menus', 'membership' ) ?>
				<span>
					<a href="#remove" class="removelink" id="remove-submenus" title="<?php _e( "Remove Sub Menus from this rules area.", 'membership' ) ?>">
						<?php _e( 'Remove', 'membership' ) ?>
					</a>
				</span>
			</h2>

			<div class="inner-operation">
				<p><?php _e( 'Select the Sub menu items to be covered by this rule by checking the box next to the relevant pages title.', 'membership' ) ?></p>

				<?php if ( !empty( $submenu ) ) : ?>
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
						<?php foreach ( $submenu as $key => $m ) : ?>
							<?php if ( !empty( $m ) ) : ?>
								<tr valign="middle" class="alternate">
									<th class="check-column" scope="row" style="background: #efefef;">&nbsp;</th>
									<td class="column-name" style="background: #efefef;">
										<strong><?php echo esc_html( strip_tags( $this->_get_mainmenu_for_file( $key ) ) ) ?></strong>
									</td>
							    </tr>
								<?php foreach( $m as $skey => $s ) : ?>
									<tr valign="middle" class="alternate" id="submenus-<?php echo esc_attr( $key ) ?>-<?php echo esc_attr( $skey ) ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo esc_attr( $s[2] ) ?>" name="submenus[]"<?php checked( in_array( $s[2], $data ) ) ?>>
										</th>
										<td class="column-name">
											<?php echo esc_html( strip_tags( $s[0] ) ) ?>
										</td>
								    </tr>
								<?php endforeach; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div><?php
	}

	public function on_creation() {
		parent::on_creation();

		$this->name = 'submenus';
		$this->label = __( 'Sub Menus', 'membership' );
		$this->description = __( 'Allows admin side sub menus to be protected.', 'membership' );
		$this->rulearea = 'admin';
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
		global $submenu;

		foreach ( $submenu as $key => $m ) {
			foreach ( $m as $skey => $s ) {
				if ( !in_array( $s[2], (array) $this->data ) ) {
					unset( $submenu[$key][$skey] );
				}
			}
		}

		$this->_clear_menu();
	}

	public function neg_admin_menu() {
		global $submenu;

		foreach ( $submenu as $key => $m ) {
			foreach ( $m as $skey => $s ) {
				if ( in_array( $s[2], (array) $this->data ) ) {
					unset( $submenu[$key][$skey] );
				}
			}
		}

		$this->_clear_menu();
	}

	private function _clear_menu() {
		global $menu, $submenu;

		foreach ( $submenu as $key => $items ) {
			if ( empty( $items ) ) {
				foreach ( $menu as $index => $item ) {
					if ( isset( $item[2] ) && $item[2] == $key ) {
						unset( $menu[$index] );
						break;
					}
				}
				unset( $submenu[$key] );
			}
		}
	}

}