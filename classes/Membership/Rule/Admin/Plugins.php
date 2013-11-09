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
 * Rule class responsible for plugins activation protection.
 *
 * @category Membership
 * @package Rule
 * @subpackage Admin
 */
class Membership_Rule_Admin_Plugins extends Membership_Rule {

	public function on_creation() {
		$this->name = 'plugins';
		$this->label = __( 'Plugins', 'membership' );
		$this->description = __( 'Allows activation of specific plugins to be protected.', 'membership' );
		$this->rulearea = 'admin';
	}

	public function admin_main( $data ) {
		$plugins = get_plugins();
		if ( !$data ) {
			$data = array();
		}

		?><div class="level-operation" id="main-plugins">
			<h2 class="sidebar-name">
				<?php _e( 'Plugins', 'membership' ) ?>
				<span>
					<a href="#remove" class="removelink" id="remove-plugins" title="<?php _e( "Remove Main Menus from this rules area.", 'membership' ); ?>">
						<?php _e( 'Remove', 'membership' ); ?>
					</a>
				</span>
			</h2>

			<div class="inner-operation">
				<p><?php _e( 'Select the Plugins to be covered by this rule by checking the box next to the relevant pages title.', 'membership' ); ?></p>

				<?php if ( !empty( $plugins ) ) : ?>
				<table cellspacing="0" class="widefat fixed">
					<thead>
						<tr>
							<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Plugin', 'membership' ) ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Plugin', 'membership' ) ?></th>
						</tr>
					</tfoot>

					<tbody>
					<?php foreach ( $plugins as $key => $plugin ) : ?>
						<?php if ( !empty( $plugin['Name'] ) ) : ?>
							<tr valign="middle" class="alternate" id="mainmenus-<?php echo $key; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $key; ?>" name="plugins[]" <?php if ( in_array( $key, $data ) ) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html( strip_tags( $plugin['Name'] ) ) ?></strong><br/>
									<?php echo esc_html( strip_tags( $plugin['Version'] ) ) ?>
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
		add_filter( 'all_plugins', array( $this, 'pos_all_plugins' ), 999 );
	}

	public function on_negative( $data ) {
		$this->data = $data;
		add_filter( 'all_plugins', array( $this, 'neg_all_plugins' ), 999 );
	}

	public function pos_all_plugins( $plugins ) {
		foreach ( $plugins as $key => $plugin ) {
			if ( !in_array( $key, (array) $this->data ) ) {
				unset( $plugins[$key] );
			}
		}

		return $plugins;
	}

	public function neg_all_plugins( $plugins ) {
		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $key, (array) $this->data ) ) {
				unset( $plugins[$key] );
			}
		}

		return $plugins;
	}

}