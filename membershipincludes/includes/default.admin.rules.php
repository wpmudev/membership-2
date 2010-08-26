<?php
class M_Mainmenus extends M_Rule {

	var $name = 'mainmenus';
	var $adminside = true;

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='mainmenus' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Main Menus','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

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
									<input type="checkbox" value="<?php echo $key; ?>" name="mainmenus[]" <?php if(in_array($key, $data)) echo 'checked="checked"'; ?>>
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
			if(!in_array($key, (array) $this->data)) {
				unset($menu[$key]);
			}
		}

	}

	function neg_admin_menu() {

		global $menu;

		foreach($menu as $key => $m) {
			if(in_array($key, (array) $this->data)) {
				unset($menu[$key]);
			}
		}


	}

}

M_register_rule('mainmenus', 'M_Mainmenus', 'admin');

class M_Dashboardwidgets extends M_Rule {

	var $name = 'dashboard';
	var $adminside = true;

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='dashboard' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Dashboard Widgets','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-dashboard'>
			<h2 class='sidebar-name'><?php _e('Dashboard Widgets', 'membership');?><span><a href='#remove' class='removelink' id='remove-dashboard' title='<?php _e("Remove Dashboard Widgets from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Main menus to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php

					include_once(ABSPATH . '/wp-admin/includes/dashboard.php');
					wp_dashboard_setup();

					global $wp_meta_boxes;

					if(!empty($wp_meta_boxes['dashboard']['normal']['core']) || !empty($wp_meta_boxes['dashboard']['side']['core'])) {
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
						foreach($wp_meta_boxes['dashboard']['normal']['core'] as $key => $m) {
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
						foreach($wp_meta_boxes['dashboard']['side']['core'] as $key => $m) {
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

function M_AddAdminSection($sections) {
	$sections['admin'] = array(	"title" => __('Administration','membership') );

	return $sections;
}

add_filter('membership_level_sections', 'M_AddAdminSection', 99);

?>