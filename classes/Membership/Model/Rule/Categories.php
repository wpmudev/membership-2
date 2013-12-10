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
 * Rule class responsible for categories protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_Categories extends Membership_Model_Rule {

	var $name = 'categories';
	var $label = 'Categories';
	var $description = 'Allows posts to be protected based on their assigned categories.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
			?>
			<div class='level-operation' id='main-categories'>
				<h2 class='sidebar-name'><?php _e('Categories', 'membership');?><span><a href='#remove' class='removelink' id='remove-categories' title='<?php _e("Remove Categories from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
				<div class='inner-operation'>
					<p><?php _e('Select the Categories to be covered by this rule by checking the box next to the relevant categories name.','membership'); ?></p>
					<?php
						$categories = get_categories('get=all');

						if($categories) {
							?>
							<table cellspacing="0" class="widefat fixed">
								<thead>
									<tr>
										<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
										<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Category name', 'membership'); ?></th>
									</tr>
								</thead>

								<tfoot>
									<tr>
										<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
										<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Category name', 'membership'); ?></th>
									</tr>
								</tfoot>

								<tbody>
									<?php
									foreach($categories as $key => $category) {
										?>
										<tr valign="middle" class="alternate" id="post-<?php echo $category->term_id; ?>">
											<th class="check-column" scope="row">
												<input type="checkbox" value="<?php echo $category->term_id; ?>" name="categories[]" <?php if(in_array($category->term_id, $data)) echo 'checked="checked"'; ?>>
											</th>
											<td class="column-name">
												<strong><?php echo esc_html($category->name); ?></strong>
											</td>
									   </tr>
										<?php
										}
										?>
									</tbody>
								</table>
								<?php
								}
							?>
					</div>
				</div>
			<?php
	}

	function on_positive( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
	}

	function on_negative( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
	}

	function validate_negative() {
		if ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( get_the_ID() );
			$intersect = array_intersect( $categories, $this->data );
			return empty( $intersect );
		}

		if ( is_category() ) {
			return !in_array( get_queried_object_id(), $this->data );
		}

		return parent::validate_negative();
	}

	function validate_positive() {
		if ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( get_the_ID() );
			$intersect = array_intersect( $categories, $this->data );
			return !empty( $intersect );
		}

		if ( is_category() ) {
			return in_array( get_queried_object_id(), $this->data );
		}

		return parent::validate_positive();
	}

}