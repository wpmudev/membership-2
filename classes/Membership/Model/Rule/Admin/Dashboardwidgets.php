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
 * Rule class responsible for admin dashboard widgets protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Admin
 */
class Membership_Model_Rule_Admin_Dashboardwidgets extends Membership_Model_Rule {

	public function on_creation() {
		$this->name = 'dashboard';
		$this->label = __( 'Dashboard Widgets', 'membership' );
		$this->description = __( 'Allows admin side dashboard widgets to be protected.', 'membership' );
		$this->rulearea = 'admin';
	}

	public function admin_main( $data ) {
		global $wp_meta_boxes;

		include_once ABSPATH . '/wp-admin/includes/dashboard.php';

		if ( !$data ) {
			$data = array();
		}

		wp_dashboard_setup();

		?><div class="level-operation" id="main-dashboard">
			<h2 class="sidebar-name">
				<?php _e( 'Dashboard Widgets', 'membership' ) ?>
				<span>
					<a href="#remove" class="removelink" id="remove-dashboard" title="<?php _e( "Remove Dashboard Widgets from this rules area.", 'membership' ) ?>">
						<?php _e( 'Remove', 'membership' ) ?>
					</a>
				</span>
			</h2>

			<div class="inner-operation">
				<p><?php _e( 'Select the Dashboard widgets to be covered by this rule by checking the box next to the relevant pages title.', 'membership' ); ?></p>

				<?php if ( !empty( $wp_meta_boxes['membership_page_membershiplevels']['normal']['core'] ) || !empty( $wp_meta_boxes['membership_page_membershiplevels']['side']['core'] ) ) : ?>
				<table cellspacing="0" class="widefat fixed">
					<thead>
						<tr>
							<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Dashboard Widget', 'membership' ) ?></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Dashboard Widget', 'membership' ) ?></th>
						</tr>
					</tfoot>

					<tbody>
						<?php foreach ( $wp_meta_boxes['membership_page_membershiplevels']['normal']['core'] as $key => $m ) : ?>
							<?php if ( !empty( $m['title'] ) ) : ?>
								<tr valign="middle" class="alternate" id="dashboard-<?php echo esc_attr( $key ) ?>">
									<th class="check-column" scope="row">
										<input type="checkbox" value="<?php echo esc_attr( $key ) ?>" name="dashboard[]"<?php checked( in_array( $key, $data ) ) ?>>
									</th>
									<td class="column-name">
										<strong><?php echo esc_html( strip_tags( $m['title'] ) ) ?></strong>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>

						<?php foreach ( $wp_meta_boxes['membership_page_membershiplevels']['side']['core'] as $key => $m ) : ?>
							<?php if ( !empty( $m['title'] ) ) : ?>
								<tr valign="middle" class="alternate" id="dashboard-<?php echo esc_attr( $key ) ?>">
									<th class="check-column" scope="row">
										<input type="checkbox" value="<?php echo esc_attr( $key ) ?>" name="dashboard[]"<?php checked( in_array( $key, $data ) ) ?>>
									</th>
									<td class="column-name">
										<strong><?php echo esc_html( strip_tags( $m['title'] ) ) ?></strong>
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
		add_action( 'wp_dashboard_setup', array( $this, 'pos_dashboard' ), 999 );
	}

	public function on_negative( $data ) {
		$this->data = $data;
		add_action( 'wp_dashboard_setup', array( $this, 'neg_dashboard' ), 999 );
	}

	public function pos_dashboard() {
		global $wp_meta_boxes;

		foreach ( $wp_meta_boxes['dashboard']['normal']['core'] as $key => $m ) {
			if ( !in_array( $key, (array)$this->data ) ) {
				unset( $wp_meta_boxes['dashboard']['normal']['core'][$key] );
			}
		}

		foreach ( $wp_meta_boxes['dashboard']['side']['core'] as $key => $m ) {
			if ( !in_array( $key, (array)$this->data ) ) {
				unset( $wp_meta_boxes['dashboard']['side']['core'][$key] );
			}
		}
	}

	public function neg_dashboard() {
		global $wp_meta_boxes;

		foreach ( $wp_meta_boxes['dashboard']['normal']['core'] as $key => $m ) {
			if ( in_array( $key, (array)$this->data ) ) {
				unset( $wp_meta_boxes['dashboard']['normal']['core'][$key] );
			}
		}

		foreach ( $wp_meta_boxes['dashboard']['side']['core'] as $key => $m ) {
			if ( in_array( $key, (array)$this->data ) ) {
				unset( $wp_meta_boxes['dashboard']['side']['core'][$key] );
			}
		}
	}

}