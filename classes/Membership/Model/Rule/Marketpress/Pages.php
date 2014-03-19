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
 * Rule class responsible for MarketPress pages protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Marketpress
 */
class Membership_Model_Rule_Marketpress_Pages extends Membership_Model_Rule {

	var $pages = array();

	public function on_creation() {
		$this->name = 'marketpress';
		$this->label = __( 'MarketPress Pages', 'membership' );
		$this->description = __( 'Allows MarketPress pages to be protected.', 'membership' );
		$this->rulearea = 'public';

		$this->pages = array(
			'mp_global_products'   => __( 'Global Products', 'membership' ),
			'mp_global_categories' => __( 'Global Categories', 'membership' ),
			'mp_global_tags'       => __( 'Global Tags', 'membership' ),
			'product_list'         => __( 'Product List', 'membership' ),
			'cart'                 => __( 'Cart', 'membership' ),
			'orderstatus'          => __( 'Order Status', 'membership' )
		);
	}

	public function admin_main( $data ) {
		global $wpdb, $M_options;

		if ( !$data ) {
			$data = array();
		}

		?><div class="level-operation" id="main-marketpress">
			<h2 class="sidebar-name">
				<?php _e( 'MarketPress Pages', 'membership' ) ?>
				<span>
					<a href="#remove" id="remove-marketpress" class="removelink" title="<?php _e( "Remove MarketPress Pages from this rules area.", 'membership' ); ?>">
						<?php _e( 'Remove', 'membership' ); ?>
					</a>
				</span>
			</h2>

			<div class="inner-operation">
				<p><?php _e( 'Select the MarketPress pages to be covered by this rule by checking the box next to the relevant page name.', 'membership' ); ?></p>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
						<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Page type', 'membership' ); ?></th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Page type', 'membership' ); ?></th>
					</tr>
					</tfoot>

					<tbody>
					<?php if ( !empty( $this->pages ) ) : ?>
						<?php foreach ( $this->pages as $key => $value ) : ?>
							<?php if ( !empty( $value ) ) : ?>
							<tr valign="middle" class="alternate" id="page-<?php echo esc_attr( stripslashes( trim( $key ) ) ); ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo esc_attr( stripslashes( trim( $key ) ) ); ?>" name="marketpress[]" <?php if ( in_array( esc_attr( stripslashes( trim( $key ) ) ), $data ) ) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html( stripslashes( trim( $value ) ) ); ?></strong>
								</td>
							</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php else : ?>
						<tr valign="middle" class="alternate" id="page-<?php echo $key; ?>">
							<td class="column-name" colspan='2'>
								<?php _e( 'You have no pages available, please ensure you have MarketPress installed.', 'membership' ) ?>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div><?php
	}

	public function on_positive( $data ) {
		$this->data = $data;
		add_filter( 'membership_notallowed_pagenames', array( $this, 'get_notallowed_pages' ) );
	}

	public function on_negative( $data ) {
		$this->data = $data;
		add_filter( 'membership_notallowed_pagenames', array( $this, 'get_notallowed_pages' ) );
	}

	public function get_notallowed_pages( $pages ) {
		if ( !is_array( $pages ) ) {
			$pages = array();
		}

		foreach ( array_keys( $this->pages ) as $key ) {
			if ( in_array( $key, (array)$this->data ) ) {
				// it's not in the list so it's a not allowed page
				$pages[] = $key;
			}
		}

		return array_unique( $pages );
	}

}