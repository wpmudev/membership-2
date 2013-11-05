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
 * @package Rule
 * @subpackage Admin
 */
class Membership_Rule_Admin_Mainmenus extends Membership_Rule {

	var $name = 'mainmenus';
	var $adminside = true;
	var $label = 'Main Menus';
	var $description = 'Allows admin side main menus to be protected.';

	var $rulearea = 'admin';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-mainmenus'>
			<h2 class='sidebar-name'><?php _e('Main Menus', 'membership');?><span><a href='#remove' class='removelink' id='remove-mainmenus' title='<?php _e("Remove Main Menus from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Main menus to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php

					global $menu;

					if(!empty($menu)) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Menu title', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Menu title', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($menu as $key => $m) {
							if(!empty($m[0])) {
							?>
							<tr valign="middle" class="alternate" id="mainmenus-<?php echo $key; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $m[2]; ?>" name="mainmenus[]" <?php if(in_array($m[2], $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html(strip_tags($m[0])); ?></strong>
								</td>
						    </tr>
							<?php
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

		global $menu;

		$this->data = $data;

		add_action('admin_menu', array(&$this, 'pos_admin_menu'), 999);


	}

	function on_negative($data) {

		global $menu;

		$this->data = $data;

		add_action('admin_menu', array(&$this, 'neg_admin_menu'), 999);

	}

	function pos_admin_menu() {

		global $menu;

		foreach($menu as $key => $m) {
			if(!in_array($m[2], (array) $this->data)) {
				unset($menu[$key]);
			}
		}

	}

	function neg_admin_menu() {

		global $menu;

		foreach($menu as $key => $m) {
			if(in_array($m[2], (array) $this->data)) {
				unset($menu[$key]);
			}
		}


	}

}
