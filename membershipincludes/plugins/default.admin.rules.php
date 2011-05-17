<?php
class M_Mainmenus extends M_Rule {

	var $name = 'mainmenus';
	var $adminside = true;
	var $label = 'Main Menus';

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

M_register_rule('mainmenus', 'M_Mainmenus', 'admin');

class M_Submenus extends M_Rule {

	var $name = 'submenus';
	var $adminside = true;
	var $label = 'Sub Menus';

	var $rulearea = 'admin';

	function get_mainmenu_for_file($file) {

		global $menu;

		foreach($menu as $key => $m) {

			if($m[2] == $file) {
				return $m[0];
			}

		}

	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-submenus'>
			<h2 class='sidebar-name'><?php _e('Sub Menus', 'membership');?><span><a href='#remove' class='removelink' id='remove-submenus' title='<?php _e("Remove Sub Menus from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Sub menu items to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php

					global $menu, $submenu;

					if(!empty($submenu)) {
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
						foreach($submenu as $key => $m) {
							if(!empty($m)) {
								?>
								<tr valign="middle" class="alternate">
									<th class="check-column" scope="row" style="background: #efefef;">
										&nbsp;
									</th>
									<td class="column-name" style="background: #efefef;">
										<strong><?php echo esc_html(strip_tags($this->get_mainmenu_for_file($key))); ?></strong>
									</td>
							    </tr>
								<?php
								foreach($m as $skey => $s) {
									?>
									<tr valign="middle" class="alternate" id="submenus-<?php echo $key . '-' . $skey; ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo $s[2]; ?>" name="submenus[]" <?php if(in_array($s[2], $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<?php echo esc_html(strip_tags($s[0])); ?>
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

		add_action('admin_menu', array(&$this, 'pos_admin_menu'), 999);


	}

	function on_negative($data) {

		$this->data = $data;

		add_action('admin_menu', array(&$this, 'neg_admin_menu'), 999);

	}

	function pos_admin_menu() {

		global $submenu;

		foreach($submenu as $key => $m) {
			foreach($m as $skey => $s) {
				if(!in_array($s[2], (array) $this->data)) {
					unset($submenu[$key][$skey]);
				}
			}
		}

	}

	function neg_admin_menu() {

		global $submenu;

		foreach($submenu as $key => $m) {
			foreach($m as $skey => $s) {
				if(in_array($s[2], (array) $this->data)) {
					unset($submenu[$key][$skey]);
				}
			}
		}

	}

}

M_register_rule('submenus', 'M_Submenus', 'admin');

class M_Dashboardwidgets extends M_Rule {

	var $name = 'dashboard';
	var $adminside = true;
	var $label = 'Dashboard Widgets';

	var $rulearea = 'admin';

	function admin_main($data) {

		global $wp_meta_boxes, $wp_dashboard_control_callbacks;

		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-dashboard'>
			<h2 class='sidebar-name'><?php _e('Dashboard Widgets', 'membership');?><span><a href='#remove' class='removelink' id='remove-dashboard' title='<?php _e("Remove Dashboard Widgets from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Dashboard widgets to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php

					include_once(ABSPATH . '/wp-admin/includes/dashboard.php');
					wp_dashboard_setup();

					if(!empty($wp_meta_boxes['membership_page_membershiplevels']['normal']['core']) || !empty($wp_meta_boxes['membership_page_membershiplevels']['side']['core'])) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Dashboard Widget', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Dashboard Widget', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($wp_meta_boxes['membership_page_membershiplevels']['normal']['core'] as $key => $m) {
							if(!empty($m['title'])) {
							?>
							<tr valign="middle" class="alternate" id="dashboard-<?php echo $key; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $key; ?>" name="dashboard[]" <?php if(in_array($key, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html(strip_tags($m['title'])); ?></strong>
								</td>
						    </tr>
							<?php
							}
						}
						?>
						<?php
						foreach($wp_meta_boxes['membership_page_membershiplevels']['side']['core'] as $key => $m) {
							if(!empty($m['title'])) {
							?>
							<tr valign="middle" class="alternate" id="dashboard-<?php echo $key; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $key; ?>" name="dashboard[]" <?php if(in_array($key, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html(strip_tags($m['title'])); ?></strong>
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

		add_action('wp_dashboard_setup', array(&$this, 'pos_dashboard'), 999);


	}

	function on_negative($data) {

		global $menu;

		$this->data = $data;

		add_action('wp_dashboard_setup', array(&$this, 'neg_dashboard'), 999);

	}

	function pos_dashboard() {

		global $wp_meta_boxes;

		foreach($wp_meta_boxes['dashboard']['normal']['core'] as $key => $m) {
			if(!in_array($key, (array) $this->data)) {
				unset($wp_meta_boxes['dashboard']['normal']['core'][$key]);
			}
		}

		foreach($wp_meta_boxes['dashboard']['side']['core'] as $key => $m) {
			if(!in_array($key, (array) $this->data)) {
				unset($wp_meta_boxes['dashboard']['side']['core'][$key]);
			}
		}

	}

	function neg_dashboard() {

		global $wp_meta_boxes;

		foreach($wp_meta_boxes['dashboard']['normal']['core'] as $key => $m) {
			if(in_array($key, (array) $this->data)) {
				unset($wp_meta_boxes['dashboard']['normal']['core'][$key]);
			}
		}

		foreach($wp_meta_boxes['dashboard']['side']['core'] as $key => $m) {
			if(in_array($key, (array) $this->data)) {
				unset($wp_meta_boxes['dashboard']['side']['core'][$key]);
			}
		}

	}

}

M_register_rule('dashboard', 'M_Dashboardwidgets', 'admin');

class M_Plugins extends M_Rule {

	var $name = 'plugins';
	var $adminside = true;
	var $label = 'Plugins';

	var $rulearea = 'admin';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-plugins'>
			<h2 class='sidebar-name'><?php _e('Plugins', 'membership');?><span><a href='#remove' class='removelink' id='remove-plugins' title='<?php _e("Remove Main Menus from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Plugins to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php

					$plugins = get_plugins();

					if(!empty($plugins)) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Plugin', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Plugin', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($plugins as $key => $plugin) {
							if(!empty($plugin['Name'])) {
							?>
							<tr valign="middle" class="alternate" id="mainmenus-<?php echo $key; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $key; ?>" name="plugins[]" <?php if(in_array($key, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html(strip_tags($plugin['Name'])); ?></strong><br/>
									<?php echo esc_html(strip_tags($plugin['Version'])); ?>
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

		$this->data = $data;

		add_filter('all_plugins', array(&$this, 'pos_all_plugins'), 999);


	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('all_plugins', array(&$this, 'neg_all_plugins'), 999);

	}

	function pos_all_plugins( $plugins ) {

		foreach($plugins as $key => $plugin) {
			if(!in_array($key, (array) $this->data)) {
				unset($plugins[$key]);
			}
		}

		return $plugins;

	}

	function neg_all_plugins( $plugins ) {

		foreach($plugins as $key => $plugin) {
			if(in_array($key, (array) $this->data)) {
				unset($plugins[$key]);
			}
		}

		return $plugins;

	}

}

M_register_rule('plugins', 'M_Plugins', 'admin');

class M_Favouriteactions extends M_Rule {

	var $name = 'favactions';
	var $adminside = true;
	var $label = 'Favorite Actions';

	var $rulearea = 'admin';

	var $favouriteactions = array();

	function on_creation() {

	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-favactions'>
			<h2 class='sidebar-name'><?php _e('Favorite Actions', 'membership');?><span><a href='#remove' class='removelink' id='remove-favactions' title='<?php _e("Remove Favorite Actions from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Favourite Actions to be covered by this rule by checking the box next to the relevant title.','membership'); ?></p>
				<?php

					$actions = M_cache_favourite_actions();

					if(!empty($actions)) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Actions', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Actions', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($actions as $key => $m) {
							if(!empty($m[0])) {
							?>
							<tr valign="middle" class="alternate" id="mainmenus-<?php echo $key; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $key; ?>" name="favactions[]" <?php if(in_array($key, $data)) echo 'checked="checked"'; ?>>
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

		add_filter('favorite_actions', array(&$this, 'pos_fav_actions'), 1000 );

	}

	function on_negative($data) {

		global $menu;

		$this->data = $data;

		add_filter('favorite_actions', array(&$this, 'neg_fav_actions'), 1000 );

	}

	function pos_fav_actions( $actions ) {

		foreach($actions as $key => $m) {
			if(!in_array($key, (array) $this->data)) {
				unset($actions[$key]);
			}
		}

		return $actions;

	}

	function neg_fav_actions( $actions ) {

		foreach($actions as $key => $m) {
			if(in_array($key, (array) $this->data)) {
				unset($actions[$key]);
			}
		}

		return $actions;

	}

}
M_register_rule('favactions', 'M_Favouriteactions', 'admin');

function M_cache_favourite_actions( $actions = false ) {

	static $M_actions;

	if( $actions !== false ){
		$M_actions = $actions;
	} else {
		$actions = $M_actions;
	}

	return $actions;

}
add_filter('favorite_actions', 'M_cache_favourite_actions', 999 );


function M_AddAdminSection($sections) {
	$sections['admin'] = array(	"title" => __('Administration','membership') );

	return $sections;
}

add_filter('membership_level_sections', 'M_AddAdminSection', 99);

?>