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
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-downloads'>
			<h2 class='sidebar-name'><?php _e('Downloads', 'membership');?><span><a href='#remove' id='remove-downloads' class='removelink' title='<?php _e("Remove Downloads from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Instructions here.','membership'); ?></p>
				<input type='hidden' name='donwloads[]' value='yes' />
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
											<input type="checkbox" value="<?php echo esc_attr($key); ?>" name="shortcodes[]" <?php if(in_array($key, $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<strong>[<?php echo esc_html($key); ?>]</strong>
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

	// Internal functions

	function override_shortcodes($content) {

		global $member_shortcode_tags, $shortcode_tags;

		$member_shortcode_tags = $shortcode_tags;

		foreach($shortcode_tags as $key => $function) {
			$shortcode_tags[$key] = array(&$this, 'do_shortcode');
		}

		return $content;
	}

	// Positive operation
	function positive_action($data = false) {

		global $member_shortcode_tags, $shortcode_tags;

		if(!$data) $data = array();
	}

	// Negative operation
	function negative_action($data = false) {

		global $member_shortcode_tags, $shortcode_tags;

		if(!$data) $data = array();

	}

	function do_shortcode($atts, $content = null) {

		echo "boo";

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