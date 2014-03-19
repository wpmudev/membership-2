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
 * Rule class responsible for menu protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_Menu extends Membership_Model_Rule {

	var $name = 'menu';
	var $label = 'Menu';
	var $description = 'Allows specific menu items to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-menu'>
			<h2 class='sidebar-name'><?php _e('Menu', 'membership');?><span><a href='#remove' id='remove-menu' class='removelink' title='<?php _e("Remove Menu from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Menu items to be covered by this rule by checking the box next to the relevant menu labels.','membership'); ?></p>
				<?php

				$navs = wp_get_nav_menus( array('orderby' => 'name') );

					if(!empty($navs)) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Menu / Item title', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Menu / Item title', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($navs as $key => $nav) {
							?>
							<tr valign="middle" class="alternate" id="menu-<?php echo $nav->term_id; ?>-0">
								<td class="column-name" colspan='2'>
									<strong><?php echo __('MENU','membership') . " - " . esc_html($nav->name); ?></strong>
								</td>
						    </tr>
							<?php
							$items = wp_get_nav_menu_items($nav->term_id);
							if(!empty($items)) {
								foreach($items as $ikey => $item) {
									?>
									<tr valign="middle" class="alternate" id="menu-<?php //echo $nav->term_id . '-'; ?><?php echo $item->ID; ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php //echo $nav->term_id . '-'; ?><?php echo $item->ID; ?>" name="menu[]" <?php if(in_array($item->ID, $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">

											<strong>&nbsp;&#8211;&nbsp;<?php if($item->menu_item_parent != 0) echo "&#8211;&nbsp;"; ?><?php echo esc_html($item->title); ?></strong>
										</td>
								    </tr>
									<?php
								}
							}
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

	function on_positive($data) {

		$this->data = $data;

		add_filter( 'wp_get_nav_menu_items', array(&$this, 'filter_viewable_menus'), 10, 3 );

	}

	function on_negative($data) {

		$this->data = $data;

		add_filter( 'wp_get_nav_menu_items', array(&$this, 'filter_unviewable_menus'), 10, 3 );
	}

	function filter_viewable_menus($items, $menu, $args) {

		if(!empty($items)) {
			foreach($items as $key => $item) {
				if(!in_array($item->ID, $this->data) || ($item->menu_item_parent != 0 && !in_array($item->menu_item_parent, $this->data))) {
					unset($items[$key]);
				}

			}
		}

		return $items;

	}

	function filter_unviewable_menus($items, $menu, $args) {

		if(!empty($items)) {
			foreach($items as $key => $item) {
				if(in_array($item->ID, $this->data) || ($item->menu_item_parent != 0 && in_array($item->menu_item_parent, $this->data))) {
					unset($items[$key]);
				}

			}
		}

		return $items;

	}

}