<?php

class M_Posts extends M_Rule {

	function __construct() {

	}

	function M_Posts() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='posts' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Posts','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
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
	}

	function on_positive($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_viewable_posts'), 1 );
	}

	function on_negative($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_unviewable_posts'), 1 );
	}

	function add_viewable_posts($wp_query) {

		if($wp_query->is_page) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['post__in'][] = $value;
		}

		$wp_query->query_vars['post__in'] = array_unique($wp_query->query_vars['post__in']);

	}

	function add_unviewable_posts($wp_query) {

		if($wp_query->is_page) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['post__not_in'][] = $value;
		}

		$wp_query->query_vars['post__not_in'] = array_unique($wp_query->query_vars['post__not_in']);

	}

}
M_register_rule('posts', 'M_Posts', 'main');

class M_Pages extends M_Rule {

	function __construct() {

	}

	function M_Pages() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='pages' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Pages','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
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
	}

	function on_positive($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_viewable_pages'), 1 );
		add_filter('get_pages', array(&$this, 'add_viewable_pages_menu'));

	}

	function on_negative($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_unviewable_pages'), 1 );
		add_filter('get_pages', array(&$this, 'add_unviewable_pages_menu'));
	}

	function add_viewable_pages($wp_query) {

		if(!$wp_query->is_page) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['post__in'][] = $value;
		}

		$wp_query->query_vars['post__in'] = array_unique($wp_query->query_vars['post__in']);

	}

	function add_viewable_pages_menu($pages) {
		foreach( (array) $pages as $key => $page ) {
			if(!in_array($page->ID, (array) $this->data)) {
				unset($pages[$key]);
			}
		}

		return $pages;

	}

	function add_unviewable_pages($wp_query) {

		if(!$wp_query->is_page) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['post__not_in'][] = $value;
		}

		$wp_query->query_vars['post__not_in'] = array_unique($wp_query->query_vars['post__not_in']);

	}

	function add_unviewable_pages_menu($pages) {
		foreach( (array) $pages as $key => $page ) {
			if(in_array($page->ID, (array) $this->data)) {
				unset($pages[$key]);
			}
		}

		return $pages;
	}

}
M_register_rule('pages', 'M_Pages', 'main');

class M_Categories extends M_Rule {

	function __construct() {

	}

	function M_Categories() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='categories' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Categories','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

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

	function on_positive($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_viewable_posts'), 1 );
	}

	function on_negative($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_unviewable_posts'), 1 );
	}

	function add_viewable_posts($wp_query) {

		if($wp_query->is_page) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['category__in'][] = $value;
		}

		$wp_query->query_vars['category__in'] = array_unique($wp_query->query_vars['category__in']);

	}

	function add_unviewable_posts($wp_query) {

		if($wp_query->is_page) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['category__not_in'][] = $value;
		}

		$wp_query->query_vars['category__not_in'] = array_unique($wp_query->query_vars['category__not_in']);

	}

}
M_register_rule('categories', 'M_Categories', 'main');

class M_More extends M_Rule {

	function __construct() {

	}

	function M_More() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='more' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('More tag','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
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
	}

	function on_positive($data) {

		global $M_options, $wp_filter;

		$this->data = $data;

		if($M_options['moretagdefault'] == 'no' ) {

			// remove the filters - otherwise we don't need to do anything
			if(isset($wp_filter['the_content_more_link'][99])) {
				foreach($wp_filter['the_content_more_link'][99] as $key => $value) {
					if(strstr($key, 'show_moretag_protection') !== false) {
						unset($wp_filter['the_content_more_link'][99][$key]);
					}
					if(empty($wp_filter['the_content_more_link'][99])) {
						unset($wp_filter['the_content_more_link'][99]);
					}
				}
			}

			if(isset($wp_filter['the_content'][1])) {
				foreach($wp_filter['the_content'][1] as $key => $value) {
					if(strstr($key, 'replace_moretag_content') !== false) {
						unset($wp_filter['the_content'][1][$key]);
					}
					if(empty($wp_filter['the_content'][1])) {
						unset($wp_filter['the_content'][1]);
					}
				}
			}

			if(isset($wp_filter['the_content_feed'][1])) {
				foreach($wp_filter['the_content_feed'][1] as $key => $value) {
					if(strstr($key, 'replace_moretag_content') !== false) {
						unset($wp_filter['the_content_feed'][1][$key]);
					}
					if(empty($wp_filter['the_content_feed'][1])) {
						unset($wp_filter['the_content_feed'][1]);
					}
				}
			}

		}
	}

	function on_negative($data) {

		global $M_options;

		$this->data = $data;

		if($M_options['moretagdefault'] != 'no' ) {
			// add the filters - otherwise we don't need to do anything
			add_filter('the_content_more_link', array(&$this, 'show_moretag_protection'), 99, 2);
			add_filter('the_content', array(&$this, 'replace_moretag_content'), 1);
		}
	}

	function show_moretag_protection($more_tag_link, $more_tag) {

		global $M_options;

		return stripslashes($M_options['moretagmessage']);

	}

	function replace_moretag_content($the_content) {

		global $M_options;

		$morestartsat = strpos($the_content, '<span id="more-');

		if($morestartsat !== false) {
			$the_content = substr($the_content, 0, $morestartsat);
			$the_content .= stripslashes($M_options['moretagmessage']);
		}

		return $the_content;

	}

}
M_register_rule('more', 'M_More', 'main');

class M_Comments extends M_Rule {

	function __construct() {

	}

	function M_Comments() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='comments' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Comments','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
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
	}

}
M_register_rule('comments', 'M_Comments', 'main');

class M_Feedposts extends M_Rule {

	function __construct() {

	}

	function M_Feedposts() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='feedposts' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Feed Posts','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-feedposts'>
			<h2 class='sidebar-name'><?php _e('Feed Posts', 'membership');?><span><a href='#remove' id='remove-feedposts' class='removelink' title='<?php _e("Remove Feed Posts from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Feed posts to be covered by this rule by checking the box next to the relevant posts title.','membership'); ?></p>
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
									<input type="checkbox" value="<?php echo $post->ID; ?>" name="feedposts[]" <?php if(in_array($post->ID, $data)) echo 'checked="checked"'; ?>>
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
	}

}
M_register_rule('feedposts', 'M_Feedposts', 'feed');

class M_Feedcategories extends M_Rule {

	function __construct() {

	}

	function M_Feedcategories() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='feedcategories' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Feed Categories','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-feedcategories'>
			<h2 class='sidebar-name'><?php _e('Feed Categories', 'membership');?><span><a href='#remove' class='removelink' id='remove-feedcategories' title='<?php _e("Remove Feed Categories from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
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
									<input type="checkbox" value="<?php echo $category->term_id; ?>" name="feedcategories[]" <?php if(in_array($category->term_id, $data)) echo 'checked="checked"'; ?>>
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

}
M_register_rule('feedcategories', 'M_Feedcategories', 'feed');

class M_Feedmore extends M_Rule {

	function __construct() {

	}

	function M_Feedmore() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='feedmore' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Feed More tag','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-feedmore'>
			<h2 class='sidebar-name'><?php _e('Feed More tag', 'membership');?><span><a href='#remove' id='remove-feedmore' class='removelink' title='<?php _e("Remove Feed More tag from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User gets content beyond the More tag in their feed.','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User only gets content upto the More tag in their feed.','membership'); ?></p>
				<input type='hidden' name='feedmore[]' value='yes' />
			</div>
		</div>
		<?php
	}

}
M_register_rule('feedmore', 'M_Feedmore', 'feed');

class M_Downloads extends M_Rule {

	function __construct() {

	}

	function M_Downloads() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='downloads' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Downloads','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {

		global $wpdb;

		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-downloads'>
			<h2 class='sidebar-name'><?php _e('Downloads', 'membership');?><span><a href='#remove' id='remove-downloads' class='removelink' title='<?php _e("Remove Downloads from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Downloads / Media to be covered by this rule by checking the box next to the relevant items name.','membership'); ?></p>
				<?php
					$mediasql = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_membership_protected_content' );
					$mediaids = $wpdb->get_col( $mediasql );

					if(!empty($mediaids)) {
						// We have some ids so grab the information
						$attachmentsql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND ID IN(" . implode(",", $mediaids) . ")" );

						$attachments = $wpdb->get_results( $attachmentsql );
					}
					?>
					<table cellspacing="0" class="widefat fixed">
						<thead>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Download / Item name', 'membership'); ?></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('File type', 'membership'); ?></th>
						</tr>
						</thead>
						<tfoot>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Download / Item name', 'membership'); ?></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('File type', 'membership'); ?></th>
						</tr>
						</tfoot>

						<tbody>
						<?php
						if(!empty($attachments)) {
							foreach($attachments as $key => $attachment) {
								?>
								<tr valign="middle" class="alternate" id="post-<?php echo $attachment->ID; ?>">
									<th class="check-column" scope="row">
										<input type="checkbox" value="<?php echo $attachment->ID; ?>" name="downloads[]" <?php if(in_array($attachment->ID, $data)) echo 'checked="checked"'; ?>>
									</th>
									<td class="column-name">
										<strong><?php echo esc_html($attachment->post_title); ?></strong>
									</td>
									<td class="column-name">
										<?php echo esc_html($attachment->post_mime_type); ?>
									</td>
							    </tr>
								<?php
							}
						} else {
							?>
							<tr valign="middle" class="alternate" id="post-<?php echo $category->term_id; ?>">
								<td class="column-name" colspan='3'>
									<?php echo __('You have no protectable files available.','membership'); ?>
								</td>
						    </tr>
							<?php
						}

						?>
						</tbody>
					</table>

			</div>
		</div>
		<?php
	}

}
M_register_rule('downloads', 'M_Downloads', 'content');

//shortcode_tags
class M_Shortcodes extends M_Rule {

	function __construct() {

	}

	function M_Shortcodes() {

		add_filter('the_content', array(&$this, 'override_shortcodes'), 1);

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='shortcodes' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Shortcodes','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {

		global $shortcode_tags;

		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-shortcodes'>
			<h2 class='sidebar-name'><?php _e('Shortcodes', 'membership');?><span><a href='#remove' id='remove-shortcodes' class='removelink' title='<?php _e("Remove Shortcodes from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Shortcodes to be covered by this rule by checking the box next to the relevant shortcode tag.','membership'); ?></p>
				<?php
					if($shortcode_tags) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
								<tr>
									<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
									<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Shortcode tag', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
								<tr>
									<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
									<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Shortcode tag', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
								<?php
								foreach($shortcode_tags as $key => $function) {
									?>
									<tr valign="middle" class="alternate" id="post-<?php echo $key; ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo esc_attr(trim($key)); ?>" name="shortcodes[]" <?php if(in_array(trim($key), $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<strong>[<?php echo esc_html(trim($key)); ?>]</strong>
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

	function on_positive($data) {

		global $M_options, $M_shortcode_tags, $shortcode_tags;

		$this->data = $data;

		if($M_options['shortcodedefault'] == 'no' ) {
			// Need to re-enable some shortcodes
			foreach( (array) $data as $key => $code ) {
				if(isset($M_shortcode_tags[$code]) && isset($shortcode_tags[$code])) {
					$shortcode_tags[$code] = $M_shortcode_tags[$code];
				}
			}
		}

	}

	function on_negative($data) {

		global $M_options, $M_shortcode_tags, $shortcode_tags;

		$M_shortcode_tags = $shortcode_tags;

		$this->data = $data;

		if($M_options['shortcodedefault'] != 'no' ) {
			// Need to disable some shortcodes
			foreach( (array) $data as $key => $code ) {
				if(isset($M_shortcode_tags[$code]) && isset($shortcode_tags[$code])) {
					$shortcode_tags[$code] = array(&$this, 'do_protected_shortcode');
				}
			}
		}

	}

	// Show the protected shortcode message
	function do_protected_shortcode($atts, $content = null, $code = "") {

		global $M_options;

		return stripslashes($M_options['shortcodemessage']);

	}

}
M_register_rule('shortcodes', 'M_Shortcodes', 'content');


// Functions

function M_register_rule($rule_name, $class_name, $section) {

	global $M_Rules, $M_SectionRules;

	if(!is_array($M_Rules)) {
		$M_Rules = array();
	}

	if(!is_array($M_SectionRules)) {
		$M_SectionRules = array();
	}

	if(class_exists($class_name)) {
		$M_SectionRules[$section][$rule_name] = $class_name;
		$M_Rules[$rule_name] = $class_name;
	} else {
		return false;
	}

}

?>