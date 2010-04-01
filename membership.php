<?php
/*
Plugin Name: Membership system - core plugin
Version: 0.1
Plugin URI:
Description: A Membership system plugin
Author:
Author URI:

Copyright 2010  (email: )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if(!class_exists('membershipcore')) {

	class membershipcore {

		var $build = 1;
		var $db;

		//
		var $showposts = 25;
		var $showpages = 100;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels');

		var $membership_levels;
		var $membership_rules;
		var $subscriptions;
		var $subscriptions_levels;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = $wpdb->prefix . $table;
			}

			// Add administration actions
			add_action('init', array(&$this, 'initialiseplugin'));

			add_action('admin_menu', array(&$this, 'add_admin_menu'));

			// Header actions
			add_action('load-toplevel_page_membership', array(&$this, 'add_admin_header_membership'));
			add_action('load-membership_page_membershiplevels', array(&$this, 'add_admin_header_membershiplevels'));
			add_action('load-membership_page_membershipsubs', array(&$this, 'add_admin_header_membershipsubs'));
			add_action('load-membership_page_membershipgateways', array(&$this, 'add_admin_header_membershipgateways'));

			//load-
			//toplevel_page_membership

			//membership_page_membershiplevels
			//membership_page_membershipsubs
			//membership_page_membershipgateways

			add_filter('membership_level_sections', array(&$this, 'default_membership_sections'));

			add_filter('membership_level_actions_main', array(&$this, 'default_main_membership_actions'));
			add_filter('membership_level_actions_rss', array(&$this, 'default_rss_membership_actions'));
			add_filter('membership_level_actions_content', array(&$this, 'default_content_membership_actions'));

			add_action('membership_level_action', array(&$this, 'default_membership_actions'), 10, 3);

		}

		function membershipcore() {
			$this->__construct();
		}

		function initialiseplugin() {

		}

		function add_admin_menu() {

			// Add the menu page
			add_menu_page(__('Membership','membership'), __('Membership','membership'), 'manage_options',  'membership', array(&$this,'handle_membership_panel'));

			// Add the sub menu
			add_submenu_page('membership', __('Membership Levels','membership'), __('Edit Levels','membership'), 'manage_options', "membershiplevels", array(&$this,'handle_levels_panel'));
			add_submenu_page('membership', __('Membership Subscriptions','membership'), __('Edit Subscriptions','membership'), 'manage_options', "membershipsubs", array(&$this,'handle_subs_panel'));
			add_submenu_page('membership', __('Membership Gateways','membership'), __('Edit Gateways','membership'), 'manage_options', "membershipgateways", array(&$this,'handle_gateways_panel'));

		}

		// Add admin headers

		function add_admin_header_core() {

		}

		function add_admin_header_membership() {

			$this->add_admin_header_core();
		}

		function add_admin_header_membershiplevels() {

			$this->add_admin_header_core();

			wp_enqueue_script('levelsjs', plugins_url('/membership/membershipincludes/js/levels.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);
			wp_enqueue_style('levelscss', plugins_url('/membership/membershipincludes/css/levels.css'), array('widgets'), $this->build);

			wp_localize_script( 'levelsjs', 'membership', array( 'deletelevel' => __('Are you sure you want to delete this level?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );

			$this->handle_levels_updates();
		}

		function add_admin_header_membershipsubs() {
			// Run the core header
			$this->add_admin_header_core();

			// Queue scripts and localise
			wp_enqueue_script('subsjs', plugins_url('/membership/membershipincludes/js/subscriptions.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);
			wp_enqueue_style('subscss', plugins_url('/membership/membershipincludes/css/subscriptions.css'), array('widgets'), $this->build);

			wp_localize_script( 'subsjs', 'membership', array( 'deletesub' => __('Are you sure you want to delete this subscription?','membership'), 'deactivatesub' => __('Are you sure you want to deactivate this subscription?','membership') ) );

			$this->handle_subscriptions_updates();

		}

		function add_admin_header_membershipgateways() {
			$this->add_admin_header_core();
		}

		// Panel handling functions

		function handle_membership_panel() {

		}

		function default_membership_sections($sections) {

			$sections['main'] = array(	"title" => __('Main rules','membership')

									);

			$sections['rss'] = array(	"title" => __('RSS rules','membership')

								);

			$sections['content'] = array(	"title" => __('Content rules','membership')

									);


			return $sections;
		}

		function default_membership_actions($action, $section, $data = false) {

			switch($action) {

				case 'posts':	$this->posts_action($section, $data);
								break;

				case 'pages':	$this->pages_action($section, $data);
								break;

				case 'categories':
								$this->category_action($section, $data);
								break;

				case 'more':	$this->more_action($section, $data);
								break;

				case 'comments':
								$this->comments_action($section, $data);
								break;

				case 'rssposts':
								$this->rssposts_action($section, $data);
								break;

				case 'rsscategories':
								$this->rsscategory_action($section, $data);
								break;

				case 'rssmore':	$this->rssmore_action($section, $data);
								break;

				case 'downloads':
								$this->downloads_action($section, $data);
								break;

			}


		}

		function default_main_membership_actions($actions) {

			$actions['posts'] = array();
			$actions['pages'] = array();
			$actions['categories'] = array();
			$actions['more'] = array();
			$actions['comments'] = array();

			return $actions;
		}

		function default_rss_membership_actions($actions) {

			$actions['rssposts'] = array();
			$actions['rsscategories'] = array();
			$actions['rssmore'] = array();

			return $actions;
		}

		function default_content_membership_actions($actions) {

			$actions['downloads'] = array();
			return $actions;
		}

		function posts_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='posts' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('Posts','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':
								if(!$data) $data = array();
								?>
								<div class='level-operation' id='main-posts'>
									<h2 class='sidebar-name'><?php _e('Posts', 'membership');?><span><a href='#remove' id='remove-posts' class='removelink' title='<?php _e("Remove Posts from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class='inner-operation'>
										<p><?php _e('Select the posts to be covered by this rule by checking the box next to the relevant posts title.','membership'); ?></p>
										<?php
											$shownumber = $this->showposts;

											$args = array(
												'numberposts' => $shownumber,
												'offset' => 0,
												'orderby' => 'post_date',
												'order' => 'DESC',
												'post_type' => 'post',
												'post_status' => 'publish'
											);

											$posts = get_posts($args);
											if($posts) {
												?>
												<table cellspacing="0" class="widefat fixed">
													<thead>
													<tr>
														<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
														<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Post title', 'membership'); ?></th>
														<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Post date', 'membership'); ?></th>
													</tr>
													</thead>

													<tfoot>
													<tr>
														<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
														<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Post title', 'membership'); ?></th>
														<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Post date', 'membership'); ?></th>
													</tr>
													</tfoot>

													<tbody>
												<?php
												foreach($posts as $key => $post) {
													?>
													<tr valign="middle" class="alternate" id="post-<?php echo $post->ID; ?>">
														<th class="check-column" scope="row">
															<input type="checkbox" value="<?php echo $post->ID; ?>" name="posts[]" <?php if(in_array($post->ID, $data)) echo 'checked="checked"'; ?>>
														</th>
														<td class="column-name">
															<strong><?php echo esc_html($post->post_title); ?></strong>
														</td>
														<td class="column-date">
															<?php
																echo date("Y/m/d", strtotime($post->post_date));
															?>
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
										<p class='description'><?php _e("Only the most recent {$shownumber} posts are shown above, if you have more than that then you should consider using categories instead.",'membership'); ?></p>
									</div>
								</div>
								<?php
								break;

			}

		}

		function rssposts_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='rssposts' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('RSS Posts','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':	if(!$data) $data = array();
								?>
								<div class='level-operation' id='main-rssposts'>
									<h2 class='sidebar-name'><?php _e('RSS Posts', 'membership');?><span><a href='#remove' id='remove-rssposts' class='removelink' title='<?php _e("Remove RSS Posts from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class='inner-operation'>
										<p><?php _e('Select the RSS posts to be covered by this rule by checking the box next to the relevant posts title.','membership'); ?></p>
										<?php
											$shownumber = $this->showposts;

											$args = array(
												'numberposts' => $shownumber,
												'offset' => 0,
												'orderby' => 'post_date',
												'order' => 'DESC',
												'post_type' => 'post',
												'post_status' => 'publish'
											);

											$posts = get_posts($args);
											if($posts) {
												?>
												<table cellspacing="0" class="widefat fixed">
													<thead>
													<tr>
														<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
														<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Post title', 'membership'); ?></th>
														<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Post date', 'membership'); ?></th>
													</tr>
													</thead>

													<tfoot>
													<tr>
														<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
														<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Post title', 'membership'); ?></th>
														<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Post date', 'membership'); ?></th>
													</tr>
													</tfoot>

													<tbody>
												<?php
												foreach($posts as $key => $post) {
													?>
													<tr valign="middle" class="alternate" id="post-<?php echo $post->ID; ?>">
														<th class="check-column" scope="row">
															<input type="checkbox" value="<?php echo $post->ID; ?>" name="rssposts[]" <?php if(in_array($post->ID, $data)) echo 'checked="checked"'; ?>>
														</th>
														<td class="column-name">
															<strong><?php echo esc_html($post->post_title); ?></strong>
														</td>
														<td class="column-date">
															<?php
																echo date("Y/m/d", strtotime($post->post_date));
															?>
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
										<p class='description'><?php _e("Only the most recent {$shownumber} posts are shown above, if you have more than that then you should consider using categories instead.",'membership'); ?></p>
									</div>
								</div>
								<?php
								break;

			}
		}

		function pages_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='pages' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('Pages','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':	if(!$data) $data = array();
				?>
				<div class='level-operation' id='main-pages'>
					<h2 class='sidebar-name'><?php _e('Pages', 'membership');?><span><a href='#remove' id='remove-pages' class='removelink' title='<?php _e("Remove Pages from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
					<div class='inner-operation'>
						<p><?php _e('Select the Pages to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
						<?php
							$shownumber = $this->showpages;

							$args = array(
								'numberposts' => $shownumber,
								'offset' => 0,
								'orderby' => 'post_date',
								'order' => 'DESC',
								'post_type' => 'page',
								'post_status' => 'publish'
							);

							$posts = get_posts($args);
							if($posts) {
								?>
								<table cellspacing="0" class="widefat fixed">
									<thead>
									<tr>
										<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
										<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Page title', 'membership'); ?></th>
										</tr>
									</thead>

									<tfoot>
									<tr>
										<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
										<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Page title', 'membership'); ?></th>
										</tr>
									</tfoot>

									<tbody>
								<?php
								foreach($posts as $key => $post) {
									?>
									<tr valign="middle" class="alternate" id="post-<?php echo $post->ID; ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo $post->ID; ?>" name="pages[]" <?php if(in_array($post->ID, $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<strong><?php echo esc_html($post->post_title); ?></strong>
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
						<p class='description'><?php _e("Only the most recent {$shownumber} pages are shown above.",'membership'); ?></p>
					</div>
				</div>
				<?php
				break;

			}
		}

		function category_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='categories' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('Categories','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':	if(!$data) $data = array();
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
								break;
			}

		}

		function rsscategory_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='rsscategories' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('RSS Categories','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':	if(!$data) $data = array();
							?>
							<div class='level-operation' id='main-rsscategories'>
								<h2 class='sidebar-name'><?php _e('RSS Categories', 'membership');?><span><a href='#remove' class='removelink' id='remove-rsscategories' title='<?php _e("Remove RSS Categories from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
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
														<input type="checkbox" value="<?php echo $category->term_id; ?>" name="rsscategories[]" <?php if(in_array($category->term_id, $data)) echo 'checked="checked"'; ?>>
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
							break;

			}

		}

		function more_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='more' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('More tag','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':
								?>
								<div class='level-operation' id='main-more'>
									<h2 class='sidebar-name'><?php _e('More tag', 'membership');?><span><a href='#remove' class='removelink' id='remove-more' title='<?php _e("Remove More tag from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class='inner-operation'>
										<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can read full post content beyond the More tag.','membership'); ?></p>
										<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to read full post content beyond the More tag.','membership'); ?></p>
										<input type='hidden' name='more[]' value='yes' />
									</div>
								</div>

								<?php
								break;

			}

		}

		function rssmore_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='rssmore' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('RSS More tag','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':
								?>
								<div class='level-operation' id='main-rssmore'>
									<h2 class='sidebar-name'><?php _e('RSS More tag', 'membership');?><span><a href='#remove' id='remove-rssmore' class='removelink' title='<?php _e("Remove RSS More tag from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class='inner-operation'>
										<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User gets content beyond the More tag in their RSS feed.','membership'); ?></p>
										<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User only gets content upto the More tag in their RSS feed.','membership'); ?></p>
										<input type='hidden' name='rssmore[]' value='yes' />
									</div>
								</div>

								<?php
								break;

			}

		}

		function comments_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='comments' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('Comments','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':
								?>
								<div class='level-operation' id='main-comments'>
									<h2 class='sidebar-name'><?php _e('Comments', 'membership');?><span><a href='#remove' id='remove-comments' class='removelink' title='<?php _e("Remove Comments from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class='inner-operation'>
										<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User gets read and make comments of posts.','membership'); ?></p>
										<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User can not read or comment on posts.','membership'); ?></p>
										<input type='hidden' name='comments[]' value='yes' />
									</div>
								</div>

								<?php
								break;

			}

		}

		function downloads_action($section = 'right', $data = false) {

			switch($section) {
				case 'right':	?>
								<li class='level-draggable' id='downloads' <?php if($data === true) echo "style='display:none;'"; ?>>
									<div class='action action-draggable'>
										<div class='action-top'>
										<?php _e('Downloads','membership'); ?>
										</div>
									</div>
								</li>
								<?php
								break;
				case 'main':
								?>
								<div class='level-operation' id='main-downloads'>
									<h2 class='sidebar-name'><?php _e('Downloads', 'membership');?><span><a href='#remove' id='remove-downloads' class='removelink' title='<?php _e("Remove Downloads from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class='inner-operation'>
										<p><?php _e('Instructions here.','membership'); ?></p>
										<input type='hidden' name='donwloads[]' value='yes' />
									</div>
								</div>

								<?php
								break;

			}

		}

		function handle_level_edit_form($level_id = false, $clone = false) {

			global $page;

			if($level_id && !$clone) {
				$level = $this->get_membership_level($level_id);
			} else {

				if($clone) {
					$level = $this->get_membership_level($level_id);
					$level->level_title .= __(' clone','membership');
				} else {
					$level = new stdclass;
					$level->level_title = __('new level','membership');
				}
				$level->id = time() * -1;

			}

			// Get the relevant parts
			$positives = $this->get_membership_rules($level_id, 'positive');
			$negatives = $this->get_membership_rules($level_id, 'negative');

			// Re-arrange the rules
			$rules = array(); $p = array(); $n = array();
			if(!empty($positives)) {
				foreach($positives as $positive) {
					$rules[$positive->rule_area] = maybe_unserialize($positive->rule_value);
					$p[$positive->rule_area] = maybe_unserialize($positive->rule_value);
				}
			}
			if(!empty($negatives)) {
				foreach($negatives as $negative) {
					$rules[$negative->rule_area] = maybe_unserialize($negative->rule_value);
					$n[$negative->rule_area] = maybe_unserialize($negative->rule_value);
				}
			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php echo __('Edit ','membership') . " - " . esc_html($level->level_title); ?></h2>

				<?php
				if ( isset($usemsg) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[$usemsg] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div class='level-liquid-left'>

					<div id='level-left'>
						<form action='?page=<?php echo $page; ?>' name='leveledit' method='post'>
							<input type='hidden' name='level_id' id='level_id' value='<?php echo $level->id; ?>' />

							<input type='hidden' name='beingdragged' id='beingdragged' value='' />
							<input type='hidden' name='in-positive-rules' id='in-positive-rules' value=',<?php echo implode(',', array_keys($p)); ?>' />
							<input type='hidden' name='in-negative-rules' id='in-negative-rules' value=',<?php echo implode(',', array_keys($n)); ?>' />

							<input type='hidden' name='postive-rules-order' id='postive-rules-order' value='' />
							<input type='hidden' name='negative-rules-order' id='negative-rules-order' value='' />

						<div id='edit-level' class='level-holder-wrap'>
							<div class='sidebar-name'>
								<h3><?php echo esc_html($level->level_title); ?></h3>
							</div>
							<div class='level-holder'>
								<div class='level-details'>
								<label for='level_title'><?php _e('Level title','management'); ?></label><br/>
								<input class='wide' type='text' name='level_title' id='level_title' value='<?php echo esc_attr($level->level_title); ?>' />
								</div>

								<h3><?php _e('Positive rules','membership'); ?></h3>
								<p class='description'><?php _e('These are the areas / elements that a member of this level can access.','membership'); ?></p>
								<div id='positive-rules-holder'>
									<?php
										if(!empty($p)) {
											foreach($p as $key => $value) {
												do_action('membership_level_action', $key, 'main', $value);
											}
										}
									?>
								</div>
								<div id='positive-rules' class='droppable-rules levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<h3><?php _e('Negative rules','membership'); ?></h3>
								<p class='description'><?php _e('These are the areas / elements that a member of this level doesn\'t have access to.','membership'); ?></p>
								<div id='negative-rules-holder'>
									<?php
										if(!empty($n)) {
											foreach($n as $key => $value) {
												do_action('membership_level_action', $key, 'main', $value);
											}
										}
									?>
								</div>
								<div id='negative-rules' class='droppable-rules levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<div class='buttons'>
									<?php
									if($level->id > 0) {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('update-' . $level->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel edit'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Update', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='updated' />
										<?php
									} else {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('add-' . $level->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Add', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='added' />
										<?php
									}
									?>
								</div>

							</div>
						</div>
						</form>
					</div>


					<div id='hiden-actions'>
					<?php

						$sections = apply_filters('membership_level_sections', array());

						foreach($sections as $key => $section) {
							$actions = apply_filters('membership_level_actions_' . $key, array());

							foreach( (array) $actions as $action => $value) {
								if(!array_key_exists($action, $rules)) {
									do_action('membership_level_action', $action, 'main');
								}
							}

						}

					?>
					</div> <!-- hidden-actions -->

				</div> <!-- level-liquid-left -->

				<div class='level-liquid-right'>
					<div class="level-holder-wrap">
						<?php

							$sections = apply_filters('membership_level_sections', array());

							foreach($sections as $key => $section) {
								?>

								<div class="sidebar-name">
									<h3><?php echo $section['title']; ?></h3>
								</div>
								<div class="section-holder" id="sidebar-<?php echo $key; ?>" style="min-height: 98px;">
									<ul class='levels levels-draggable'>
									<?php
										$actions = apply_filters('membership_level_actions_' . $key , array());

											foreach( (array) $actions as $action => $value) {
												if(!array_key_exists($action, $rules)) {
													do_action('membership_level_action', $action, 'right');
												} else {
													do_action('membership_level_action', $action, 'right', true);
												}
											}

									?>
									</ul>
								</div>
								<?php
							}
						?>
					</div> <!-- level-holder-wrap -->

				</div> <!-- level-liquid-left -->

			</div> <!-- wrap -->

			<?php
		}

		function handle_levels_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}

				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			switch(addslashes($action)) {

				case 'added':	$id = (int) $_POST['level_id'];
								check_admin_referer('add-' . $id);
								if($id) {
									if($this->add_membership_level($id)) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4,  'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 4,  'admin.php?page=' . $page ) );
								}

								break;
				case 'updated':	$id = (int) $_POST['level_id'];
								check_admin_referer('update-' . $id);
								if($id) {
									if($this->update_membership_level($id)) {
										wp_safe_redirect( add_query_arg( 'msg', 3,  'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5,  'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 5,  'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];

									check_admin_referer('delete-level_' . $level_id);

									if($this->delete_membership_level($level_id)) {
										wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'toggle':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];

									check_admin_referer('toggle-level_' . $level_id);

									if($this->toggle_membership_level($level_id)) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-levels');
								foreach($_GET['levelcheck'] AS $value) {
									if(is_numeric($value)) {
										$level_id = (int) $value;
										if($this->delete_membership_level($level_id)) {
											wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
										}
									}

								}
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-levels');
								foreach($_GET['levelcheck'] AS $value) {
									if(is_numeric($value)) {
										$level_id = (int) $value;
										if($this->toggle_membership_level($level_id)) {
											wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
										}
									}

								}
								break;

			}

		}

		function handle_levels_panel() {

			global $action, $page;

			switch(addslashes($action)) {

				case 'edit':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];
									$this->handle_level_edit_form($level_id);
									return; // So we don't see the rest of this page
								}
								break;

				case 'clone':	if(isset($_GET['clone_id'])) {
									$level_id = (int) $_GET['clone_id'];
									$this->handle_level_edit_form($level_id, true);
									return; // So we don't see the rest of this page
								}
								break;
			}

			$filter = array();

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			if(isset($_GET['level_id'])) {
				$filter['level_id'] = stripslashes($_GET['level_id']);
			}

			if(isset($_GET['order_by'])) {
				$filter['order_by'] = stripslashes($_GET['order_by']);
			}

			$messages = array();
			$messages[1] = __('Membership Level added.');
			$messages[2] = __('Membership Level deleted.');
			$messages[3] = __('Membership Level updated.');
			$messages[4] = __('Membership Level not added.');
			$messages[5] = __('Membership Level not updated.');
			$messages[6] = __('Membership Level not deleted.');

			$messages[7] = __('Membership Level activation toggled.');
			$messages[8] = __('Membership Level activation not toggled.');

			$messages[9] = __('Membership Levels updated.');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Edit Membership Levels','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />
					<label for="membership-search-input" class="screen-reader-text"><?php _e('Search Memberships','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="membership-search-input">
					<input type="submit" class="button" value="<?php _e('Search Memberships','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
				<option value="delete"><?php _e('Delete'); ?></option>
				<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				<select name="level_id">
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all Levels','membership'); ?></option>
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active Levels','membership'); ?></option>
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive Levels','membership'); ?></option>

				</select>

				<select name="order_by">
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_id') echo "selected='selected'"; ?> value="order_id"><?php _e('Order by Level ID','membership'); ?></option>
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_name') echo "selected='selected'"; ?> value="order_name"><?php _e('Order by Level Name','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary" value="<?php _e('Filter'); ?>" id="post-query-submit">

				</div>

				<div class="alignright actions">
					<input type="button" class="button-secondary addnewlevelbutton" value="<?php _e('Add New'); ?>" name="addnewlevel">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-levels');

					$columns = array(	"name" 		=> 	__('Level Name','membership'),
										"active"	=>	__('Active','membership'),
										"users"		=>	__('Users','membership')
									);

					$columns = apply_filters('membership_levelcolumns', $columns);

					$levels = $this->get_membership_levels($filter);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($levels) {
							foreach($levels as $key => $level) {
								?>
								<tr valign="middle" class="alternate" id="level-<?php echo $level->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $level->id; ?>" name="levelcheck[]"></th>
									<td class="column-name">
										<strong><a title="Edit “<?php echo esc_attr($level->level_title); ?>”" href="?page=<?php echo $page; ?>&amp;action=edit&amp;level_id=<?php echo $level->id; ?>" class="row-title"><?php echo esc_html($level->level_title); ?></a></strong>
										<?php
											$actions = array();
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;level_id=" . $level->id . "'>" . __('Edit') . "</a></span>";
											if($level->level_active == 0) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;level_id=" . $level->id . "", 'toggle-level_' . $level->id) . "'>" . __('Activate') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;level_id=" . $level->id . "", 'toggle-level_' . $level->id) . "'>" . __('Deactivate') . "</a></span>";
											}
											$actions['clone'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=clone&amp;clone_id=" . $level->id . "'>" . __('Clone') . "</a></span>";

											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;level_id=" . $level->id . "", 'delete-level_' . $level->id) . "'>" . __('Delete') . "</a></span>";

										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											switch($level->level_active) {
												case 0:	echo __('Inactive', 'membership');
														break;
												case 1:	echo "<strong>" . __('Active', 'membership') . "</strong>";
														break;
											}
										?>
									</td>
									<td class="column-users">
										<strong>
											<?php echo $level->level_count; ?>
										</strong>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Membership levels where found, click above to add one.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="delete"><?php _e('Delete'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
				</div>
				<div class="alignright actions">
					<input type="button" class="button-secondary addnewlevelbutton" value="<?php _e('Add New'); ?>" name="addnewlevel2">
				</div>
				<br class="clear">
				</div>



				</form>

			</div> <!-- wrap -->
			<?php
		}

		function handle_sub_edit_form($sub_id = false, $clone = false) {

			global $page;

			if($sub_id && !$clone) {
				$sub = $this->get_membership_sub($sub_id);
			} else {

				if($clone) {
					$sub = $this->get_membership_sub($sub_id);
					$sub->sub_name .= __(' clone','membership');
				} else {
					$sub = new stdclass;
					$sub->sub_name = __('new subscription','membership');
				}
				$sub->id = time() * -1;

			}

			// Get the relevant parts
			//$positives = $this->get_membership_rules($level_id, 'positive');
			//$negatives = $this->get_membership_rules($level_id, 'negative');

			// Re-arrange the rules
			/*
			$rules = array(); $p = array(); $n = array();
			if(!empty($positives)) {
				foreach($positives as $positive) {
					$rules[$positive->rule_area] = maybe_unserialize($positive->rule_value);
					$p[$positive->rule_area] = maybe_unserialize($positive->rule_value);
				}
			}
			if(!empty($negatives)) {
				foreach($negatives as $negative) {
					$rules[$negative->rule_area] = maybe_unserialize($negative->rule_value);
					$n[$negative->rule_area] = maybe_unserialize($negative->rule_value);
				}
			}
			*/
			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<?php
					if($sub->id < 0) {
						?>
						<h2><?php echo __('Add ','membership') . " - " . esc_html($sub->sub_name); ?></h2>
						<?php
					} else {
						?>
						<h2><?php echo __('Edit ','membership') . " - " . esc_html($sub->sub_name); ?></h2>
						<?php
					}
				?>

				<?php
				if ( isset($usemsg) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[$usemsg] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div class='sub-liquid-left'>

					<div id='sub-left'>
						<form action='?page=<?php echo $page; ?>' name='subedit' method='post'>
							<input type='hidden' name='sub_id' id='sub_id' value='<?php echo $sub->id; ?>' />

							<input type='hidden' name='beingdragged' id='beingdragged' value='' />
							<input type='hidden' name='level-order' id='level-order' value='' />

						<div id='edit-sub' class='sub-holder-wrap'>
							<div class='sidebar-name'>
								<h3><?php echo esc_html($sub->sub_name); ?></h3>
							</div>
							<div class='sub-holder'>
								<div class='sub-details'>
								<label for='sub_name'><?php _e('Subscription name','management'); ?></label><br/>
								<input class='wide' type='text' name='sub_name' id='sub_name' value='<?php echo esc_attr($sub->sub_name); ?>' />
								</div>

								<h3><?php _e('Membership levels','membership'); ?></h3>
								<p class='description'><?php _e('These are the levels that are part of this subscription and the order a user will travel through them.','membership'); ?></p>
								<div id='membership-levels-start'>
									<div id="main-start" class="sub-operation" style="display: block;">
											<h2 class="sidebar-name">Starting Point</h2>
											<div class="inner-operation">
												<p class='description'><?php _e('A new signup for this subscription will start here and immediately pass to the next membership level listed below.','membership'); ?></p>
											</div>
									</div>
								</div>

								<ul id='membership-levels-holder'>

									<?php
										if(!empty($p)) {
											foreach($p as $key => $value) {
												do_action('membership_level_action', $key, 'main', $value);
											}
										}
									?>
								</ul>
								<div id='membership-levels' class='droppable-levels levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<div class='buttons'>
									<?php
									if($level->id > 0) {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('update-' . $sub->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel edit'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Update', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='updated' />
										<?php
									} else {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('add-' . $sub->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Add', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='added' />
										<?php
									}
									?>
								</div>

							</div>
						</div>
						</form>
					</div>


					<div id='hiden-actions'>

						<div id='template-holder'>
							<li class='sortable-levels' id="%templateid%" >
								<div class='joiningline'>&nbsp;</div>
								<div class="sub-operation" style="display: block;">
									<h2 class="sidebar-name">%startingpoint%<span><a href='#remove' class='removelink' title='<?php _e("Remove this level from the subscription.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
									<div class="inner-operation">
										<div style='float: left;'>
										<label for='levelmode[%level%]'><?php _e('Mode : ','membership'); ?></label>
										<select name='levelmode[%level%]'>
											<option value='trial'>Trial</option>
											<option value='finite'>Finite</option>
											<option value='indefinite'>Indefinite</option>
											<option value='serial'>Serial</option>
											<option value='sequential'>Sequential</option>
										</select>
										</div>
										<div style='float: right;'>
										<label for='levelperiod[%level%]'><?php _e('Period : ','membership'); ?></label>
										<select name='levelperiod[%level%]'>
											<option value=''></option>
											<?php
												for($n = 1; $n <= 365; $n++) {
													?>
													<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
													<?php
												}
											?>
										</select>&nbsp;<?php _e('days','membership'); ?>
										</div>
									</div>
								</div>
							</li>
						</div>

					</div> <!-- hidden-actions -->

				</div> <!-- sub-liquid-left -->

				<div class='sub-liquid-right'>
					<div class="sub-holder-wrap">
								<div class="sidebar-name">
									<h3><?php _e('Membership levels','membership'); ?></h3>
								</div>
								<div class="level-holder" id="sidebar-levels" style="min-height: 98px;">
									<ul class='subs subs-draggable'>
									<?php
										$levels = $this->get_membership_levels();
										foreach( (array) $levels as $key => $level) {
										?>
											<li class='level-draggable' id='level-<?php echo $level->id; ?>'>
												<div class='action action-draggable'>
													<div class='action-top'>
														<?php echo esc_html($level->level_title); ?>
													</div>
												</div>
											</li>
										<?php
											}
									?>
									</ul>
								</div>
					</div> <!-- sub-holder-wrap -->

				</div> <!-- sub-liquid-right -->

			</div> <!-- wrap -->

			<?php
		}

		function handle_subscriptions_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}

				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			switch(addslashes($action)) {

				case 'added':	$id = (int) $_POST['level_id'];
								check_admin_referer('add-' . $id);
								if($id) {
									if($this->add_membership_level($id)) {
										wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4, wp_get_referer() ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 4, wp_get_referer() ) );
								}

								break;
				case 'updated':	$id = (int) $_POST['level_id'];
								check_admin_referer('update-' . $id);
								if($id) {
									if($this->update_membership_level($id)) {
										wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
								}
								break;

				case 'delete':	if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('delete-sub_' . $sub_id);

									if($this->delete_membership_sub($sub_id)) {
										wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'toggle':	if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('toggle-sub_' . $sub_id);

									if($this->toggle_membership_sub($sub_id)) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-subs');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;
										if($this->delete_membership_sub($sub_id)) {
											wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
										}
									}

								}
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-subs');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;
										if($this->toggle_membership_sub($sub_id)) {
											wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
										}
									}

								}
								break;

			}

		}

		function handle_subs_panel() {

			// Subscriptions panel
			global $action, $page;

			$filter = array();

			if($action == 'edit') {
				if(isset($_GET['sub_id'])) {
					$sub_id = (int) $_GET['sub_id'];
					$this->handle_sub_edit_form($sub_id);
					return; // So we don't see the rest of this page
				}
			}

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			if(isset($_GET['sub_id'])) {
				$filter['sub_id'] = stripslashes($_GET['sub_id']);
			}

			if(isset($_GET['order_by'])) {
				$filter['order_by'] = stripslashes($_GET['order_by']);
			}

			$messages = array();
			$messages[1] = __('Subscription added.');
			$messages[2] = __('Subscription deleted.');
			$messages[3] = __('Subscription updated.');
			$messages[4] = __('Subscription not added.');
			$messages[5] = __('Subscription not updated.');
			$messages[6] = __('Subscription not deleted.');

			$messages[7] = __('Subscription activation toggled.');
			$messages[8] = __('Subscription activation not toggled.');

			$messages[9] = __('Subscriptions updated.');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Edit Subscription Plans','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />
					<label for="subscription-search-input" class="screen-reader-text"><?php _e('Search Memberships','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="subscription-search-input">
					<input type="submit" class="button" value="<?php _e('Search Subscriptions','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
				<option value="delete"><?php _e('Delete'); ?></option>
				<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				<select name="sub_id">
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all Subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active Subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive Subscriptions','membership'); ?></option>

				</select>

				<select name="order_by">
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_id') echo "selected='selected'"; ?> value="order_id"><?php _e('Order by Subscription ID','membership'); ?></option>
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_name') echo "selected='selected'"; ?> value="order_name"><?php _e('Order by Subscription Name','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary" value="<?php _e('Filter'); ?>" id="post-query-submit">

				</div>

				<div class="alignright actions">
					<input type="button" class="button-secondary addnewsubbutton" value="<?php _e('Add New'); ?>" name="addnewlevel">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-subscriptions');

					$columns = array(	"name" 		=> 	__('Subscription Name','membership'),
										"active"	=>	__('Active','membership'),
										"users"		=>	__('Users','membership')
									);

					$columns = apply_filters('subscription_columns', $columns);

					$subs = $this->get_subscriptions($filter);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($subs) {
							foreach($subs as $key => $sub) {
								?>
								<tr valign="middle" class="alternate" id="sub-<?php echo $sub->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $sub->id; ?>" name="subcheck[]"></th>
									<td class="column-name">
										<strong><a title="Edit “<?php echo esc_attr($sub->sub_name); ?>”" href="?page=<?php echo $page; ?>&amp;action=edit&amp;sub_id=<?php echo $sub->id; ?>" class="row-title"><?php echo esc_html($sub->sub_name); ?></a></strong>
										<?php
											$actions = array();
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;sub_id=" . $sub->id . "'>" . __('Edit') . "</a></span>";
											if($sub->sub_active == 0) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;sub_id=" . $sub->id . "", 'toggle-sub_' . $sub->id) . "'>" . __('Activate') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;sub_id=" . $sub->id . "", 'toggle-sub_' . $sub->id) . "'>" . __('Deactivate') . "</a></span>";
											}
											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;sub_id=" . $sub->id . "", 'delete-sub_' . $sub->id) . "'>" . __('Delete') . "</a></span>";

										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											switch($sub->sub_active) {
												case 0:	echo __('Inactive', 'membership');
														break;
												case 1:	echo "<strong>" . __('Active', 'membership') . "</strong>";
														break;
											}
										?>
									</td>
									<td class="column-users">
										<strong>
											<?php echo $sub->sub_count; ?>
										</strong>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Subscriptions where found, click above to add one.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="delete"><?php _e('Delete'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
				</div>
				<div class="alignright actions">
					<input type="button" class="button-secondary addnewsubbutton" value="<?php _e('Add New'); ?>" name="addnewlevel2">
				</div>
				<br class="clear">
				</div>



				</form>

			</div> <!-- wrap -->
			<?php

		}

		function handle_gateways_panel() {

		}

		// Database actions

		function get_membership_rules($level_id, $type) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_rules} WHERE level_id = %d AND rule_ive = %s ORDER BY rule_order ASC", $level_id, $type );

			return $this->db->get_results( $sql );

		}

		function add_membership_level($level_id) {

			if($level_id > 0 ) {
				return $this->update_level($level_id);
			} else {
				$return = $this->db->insert($this->membership_levels, array('level_title' => $_POST['level_title'], 'level_slug' => sanitize_title($_POST['level_title'])));

				$level_id = $this->db->insert_id;

				// Process the new rules
				if(!empty($_POST['in-positive-rules'])) {
					$rules = explode(',', $_POST['in-positive-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $level_id, "rule_ive" => 'positive', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}

					}
				}

				if(!empty($_POST['in-negative-rules'])) {
					$rules = explode(',', $_POST['in-negative-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $level_id, "rule_ive" => 'negative', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}
					}
				}

			}

			return true; // for now

		}

		function update_membership_level($level_id) {

			if($level_id < 0 ) {
				return $this->add_level($level_id);
			} else {
				$return = $this->db->update($this->membership_levels, array('level_title' => $_POST['level_title'], 'level_slug' => sanitize_title($_POST['level_title'])), array('id' => $level_id));

				// Remove the existing rules for this membership level
				$this->db->query( $this->db->prepare( "DELETE FROM {$this->membership_rules} WHERE level_id = %d", $level_id ) );

				// Process the new rules
				if(!empty($_POST['in-positive-rules'])) {
					$rules = explode(',', $_POST['in-positive-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $level_id, "rule_ive" => 'positive', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}

					}
				}

				if(!empty($_POST['in-negative-rules'])) {
					$rules = explode(',', $_POST['in-negative-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $level_id, "rule_ive" => 'negative', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}
					}
				}

			}

			return true; // for now

		}

		function toggle_membership_level($level_id) {

			$sql = $this->db->prepare( "UPDATE {$this->membership_levels} SET level_active = NOT level_active WHERE id = %d AND level_count = 0", $level_id);

			return $this->db->query($sql);


		}

		function delete_membership_level($level_id) {

			$sql = $this->db->prepare( "DELETE FROM {$this->membership_levels} WHERE id = %d AND level_count = 0", $level_id);

			$sql2 = $this->db->prepare( "DELETE FROM {$this->membership_rules} WHERE level_id = %d", $level_id);

			$sql3 = $this->db->prepare( "DELETE FROM {$this->subscriptions_levels} WHERE level_id = %d", $level_id);

			if($this->db->query($sql)) {

				$this->db->query($sql2);
				$this->db->query($sql3);
				return true;

			} else {
				return false;
			}


		}

		function get_membership_level($level_id) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_levels} WHERE id = %d", $level_id);

			return $this->db->get_row($sql);

		}

		function get_membership_levels($filter = false) {

			if($filter) {
				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "level_title LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['level_id'])) {
					switch($filter['level_id']) {

						case 'active':		$where[] = "level_active = 1";
											break;
						case 'inactive':	$where[] = "level_active = 0";
											break;

					}
				}

				if(isset($filter['order_by'])) {
					switch($filter['order_by']) {

						case 'order_id':	$orderby[] = 'id ASC';
											break;
						case 'order_name':	$orderby[] = 'level_title ASC';
											break;

					}
				}

			}

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_levels}");

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);


		}

		//subscriptions

		function get_subscriptions($filter = false) {

			if($filter) {

				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "sub_name LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['sub_id'])) {
					switch($filter['sub_id']) {

						case 'active':		$where[] = "sub_active = 1";
											break;
						case 'inactive':	$where[] = "sub_active = 0";
											break;

					}
				}

				if(isset($filter['order_by'])) {
					switch($filter['order_by']) {

						case 'order_id':	$orderby[] = 'id ASC';
											break;
						case 'order_name':	$orderby[] = 'sub_name ASC';
											break;

					}
				}

			}

			$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions}");

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);


		}

		function toggle_membership_sub($sub_id) {

			$sql = $this->db->prepare( "UPDATE {$this->subscriptions} SET sub_active = NOT sub_active WHERE id = %d AND sub_count = 0", $sub_id);

			return $this->db->query($sql);


		}

		function delete_membership_sub($sub_id) {

			$sql = $this->db->prepare( "DELETE FROM {$this->subscriptions} WHERE id = %d AND sub_count = 0", $sub_id);

			$sql2 = $this->db->prepare( "DELETE FROM {$this->subscriptions_levels} WHERE sub_id = %d", $sub_id);

			if($this->db->query($sql)) {

				$this->db->query($sql2);
				return true;

			} else {
				return false;
			}



		}

		function get_membership_sub($sub_id) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions} WHERE id = %d", $sub_id);

			return $this->db->get_row($sql);

		}

	}

}

$membershipcore =& new membershipcore();

?>