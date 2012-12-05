<?php

class M_Posts extends M_Rule {

	var $name = 'posts';
	var $label = 'Posts';
	var $description = 'Allows specific posts to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-posts'>
			<h2 class='sidebar-name'><?php _e('Posts', 'membership');?><span><a href='#remove' id='remove-posts' class='removelink' title='<?php _e("Remove Posts from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the posts to be covered by this rule by checking the box next to the relevant posts title.','membership'); ?></p>
				<?php
					$args = array(
						'numberposts' => MEMBERSHIP_POST_COUNT,
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
				<p class='description'><?php echo sprintf(__("Only the most recent %d posts are shown above, if you have more than that then you should consider using categories instead.",'membership'), MEMBERSHIP_POST_COUNT); ?></p>
			</div>
		</div>
		<?php
	}

	function redirect() {

		global $M_options;

		if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true ) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
		}

		$url = get_permalink( (int) $M_options['nocontent_page'] );

		wp_safe_redirect( $url );
		exit;

	}

	function get_group() {

		global $wpdb;

		$sql = $wpdb->prepare( "SELECT id FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname = %s", '_posts-' . $this->level_id );

		$results = $wpdb->get_var( $sql );

		if(!empty($results)) {
			return $results;
		} else {
			return false;
		}
	}

	function on_positive($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_viewable_posts'), 1 );

		add_filter( 'the_posts', array(&$this, 'check_positive_posts'));
	}

	function on_negative($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_unviewable_posts'), 1 );

		add_filter( 'the_posts', array(&$this, 'check_negative_posts'));
	}

	function add_viewable_posts($wp_query) {

		global $M_options;

		if(!$wp_query->is_singlular && empty($wp_query->query_vars['pagename'])) {
			// We are in a list rather than on a single post
			foreach( (array) $this->data as $key => $value ) {
				$wp_query->query_vars['post__in'][] = $value;
			}

			$wp_query->query_vars['post__in'] = array_unique($wp_query->query_vars['post__in']);
		} else {
			// We are on a single post - wait until we get to the_posts
		}



	}

	function add_unviewable_posts($wp_query) {

		global $M_options;

		if(!$wp_query->is_singlular && empty($wp_query->query_vars['pagename'])) {
			// We are on a list rather than on a single post
			foreach( (array) $this->data as $key => $value ) {
				$wp_query->query_vars['post__not_in'][] = $value;
			}

			$wp_query->query_vars['post__not_in'] = array_unique($wp_query->query_vars['post__not_in']);

		} else {
			// We are on a single post - wait until we get to the_posts
		}


	}

	function check_negative_posts( $posts ) {

		global $wp_query, $M_options;


		if(!$wp_query->is_singlular || count($posts) > 1) {
			return $posts;
		}

		if(!empty($posts) && count($posts) == 1) {
			// we may be on a restricted post so check the URL and redirect if needed

			$redirect = false;
			$url = '';

			$exclude = array();
			if(!empty($M_options['registration_page'])) {
				$exclude[] = get_permalink( (int) $M_options['registration_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
			}

			if(!empty($M_options['account_page'])) {
				$exclude[] = get_permalink( (int) $M_options['account_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
			}

			if(!empty($M_options['nocontent_page'])) {
				$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
			}

			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				$exclude[] = $host;
				$exclude[] = untrailingslashit($host);
			}

			foreach($posts as $post) {
				if($post->post_type != 'post') {
					continue;
				}

				if(!in_array(strtolower( get_permalink($post->ID) ), $exclude)) {
					$url = get_permalink($post->ID);
				}
			}

			// Check if we have a url available to check
			if(empty($url)) {
				return $posts;
			}

			// we have the current page / url - get the groups selected
			$group_id = $this->get_group();

			if($group_id) {
				$group = new M_Urlgroup( $group_id );

				if( !empty($url) && $group->url_matches( $url ) ) {
					$redirect = true;
				}
			}

			if($redirect === true && !empty($M_options['nocontent_page'])) {
				// we need to redirect
				$this->redirect();
			} else {
				return $posts;
			}
		}

		return $posts;

	}

	function check_positive_posts( $posts ) {

		global $wp_query, $M_options;

		if(!$wp_query->is_singlular || count($posts) > 1) {
			return $posts;
		}

		if(!empty($posts) && count($posts) == 1) {
			// we may be on a restricted post so check the URL and redirect if needed

			$redirect = false;
			$found = false;
			$url = '';

			$exclude = array();
			if(!empty($M_options['registration_page'])) {
				$exclude[] = get_permalink( (int) $M_options['registration_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
			}

			if(!empty($M_options['account_page'])) {
				$exclude[] = get_permalink( (int) $M_options['account_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
			}

			if(!empty($M_options['nocontent_page'])) {
				$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
			}

			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				$exclude[] = $host;
				$exclude[] = untrailingslashit($host);
			}

			foreach($posts as $post) {
				if($post->post_type != 'post') {
					continue;
				}

				if(!in_array(strtolower( get_permalink($post->ID) ), $exclude)) {
					$url = get_permalink($post->ID);
				}
			}

			// Check if we have a url available to check
			if(empty($url)) {
				return $posts;
			}

			// we have the current page / url - get the groups selected
			$group_id = $this->get_group();

			if($group_id) {
				$group = new M_Urlgroup( $group_id );

				if( $group->url_matches( $url ) ) {
					$found = true;
				}
			}

			if($found !== true && !empty($M_options['nocontent_page'])) {
				// we need to redirect
				$this->redirect();
			} else {
				return $posts;
			}

		}

		return $posts;

	}

}

class M_Pages extends M_Rule {

	var $name = 'pages';
	var $label = 'Pages';
	var $description = 'Allows specific pages to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();

		?>
		<div class='level-operation' id='main-pages'>
			<h2 class='sidebar-name'><?php _e('Pages', 'membership');?><span><a href='#remove' id='remove-pages' class='removelink' title='<?php _e("Remove Pages from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Pages to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php
					$args = array(
						'numberposts' => MEMBERSHIP_PAGE_COUNT,
						'offset' => 0,
						'orderby' => 'post_date',
						'order' => 'DESC',
						'post_type' => 'page',
						'post_status' => 'publish'
					);

					$posts = get_posts($args);

					// to remove bp specified pages - should be listed on the bp pages group
					$posts = apply_filters( 'staypress_hide_protectable_pages', $posts );

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
				<p class='description'><?php echo sprintf(__("Only the most recent %d pages are shown above.",'membership'), MEMBERSHIP_PAGE_COUNT); ?></p>
			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_viewable_pages'), 2 );
		add_filter('get_pages', array(&$this, 'add_viewable_pages_menu'), 1);

		add_filter( 'the_posts', array(&$this, 'check_positive_pages'));

	}

	function on_negative($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_unviewable_pages'), 2 );
		add_filter('get_pages', array(&$this, 'add_unviewable_pages_menu'), 1);

		add_filter( 'the_posts', array(&$this, 'check_negative_pages'));

	}

	function redirect() {

		global $M_options;

		if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true ) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
		}

		$url = get_permalink( (int) $M_options['nocontent_page'] );

		wp_safe_redirect( $url );
		exit;

	}

	function get_group() {

		global $wpdb;

		$sql = $wpdb->prepare( "SELECT id FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname = %s", '_pages-' . $this->level_id );

		$results = $wpdb->get_var( $sql );

		if(!empty($results)) {
			return $results;
		} else {
			return false;
		}
	}

	function add_viewable_pages($wp_query) {

		global $M_options;

		if(!$wp_query->is_single && !empty($wp_query->query_vars['post__in'])) {
			// We are not on a single page - so just limit the viewing
			foreach( (array) $this->data as $key => $value ) {
				$wp_query->query_vars['post__in'][] = $value;
			}

			$wp_query->query_vars['post__in'] = array_unique($wp_query->query_vars['post__in']);
		} else {
			// We are on a single page - so check for restriction on the_posts
		}

	}

	function add_viewable_pages_menu($pages) {

		$override_pages = apply_filters( 'membership_override_viewable_pages_menu', array() );

		foreach( (array) $pages as $key => $page ) {
			if(!in_array($page->ID, (array) $this->data) && !in_array($page->ID, (array) $override_pages)) {
				unset($pages[$key]);
			}
		}

		return $pages;

	}

	function add_unviewable_pages($wp_query) {

		global $M_options;

		return;

	}

	function add_unviewable_pages_menu($pages) {
		foreach( (array) $pages as $key => $page ) {
			if(in_array($page->ID, (array) $this->data)) {
				unset($pages[$key]);
			}
		}

		return $pages;
	}

	function check_negative_pages( $posts ) {

		global $wp_query, $M_options;

		if(!$wp_query->is_singular || count($posts) > 1) {
			return $posts;
		}

		//print_r($wp_query);

		if(!empty($posts) && count($posts) == 1) {
			// we may be on a restricted post so check the URL and redirect if needed

			$redirect = false;
			$url = '';

			$exclude = array();
			if(!empty($M_options['registration_page'])) {
				$exclude[] = get_permalink( (int) $M_options['registration_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
			}

			if(!empty($M_options['account_page'])) {
				$exclude[] = get_permalink( (int) $M_options['account_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
			}

			if(!empty($M_options['nocontent_page'])) {
				$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
			}

			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				$exclude[] = $host;
				$exclude[] = untrailingslashit($host);
			}

			foreach($posts as $post) {
				if($post->post_type != 'page') {
					continue;
				}

				if(!in_array(strtolower( get_permalink($post->ID) ), $exclude)) {
					$url = get_permalink($post->ID);
				}
			}

			// Check if we have a url available to check
			if(empty($url)) {
				return $posts;
			}

			// we have the current page / url - get the groups selected
			$group_id = $this->get_group();

			if($group_id) {
				$group = new M_Urlgroup( $group_id );

				if( $group->url_matches( $url ) ) {
					$redirect = true;
				}
			}

			if($redirect === true && !empty($M_options['nocontent_page'])) {
				// we need to redirect
				$this->redirect();
			} else {
				return $posts;
			}

		}

		return $posts;

	}

	function check_positive_pages( $posts ) {

		global $wp_query, $M_options;

		if(!$wp_query->is_singular || count($posts) > 1) {
			return $posts;
		}

		if(!empty($posts) && count($posts) == 1) {
			// we may be on a restricted post so check the URL and redirect if needed

			$redirect = false;
			$found = false;
			$url = '';

			$exclude = array();
			if(!empty($M_options['registration_page'])) {
				$exclude[] = get_permalink( (int) $M_options['registration_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
			}

			if(!empty($M_options['account_page'])) {
				$exclude[] = get_permalink( (int) $M_options['account_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
			}

			if(!empty($M_options['nocontent_page'])) {
				$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
				$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
			}

			if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
				$exclude[] = $host;
				$exclude[] = untrailingslashit($host);
			}

			foreach($posts as $post) {
				if($post->post_type != 'page') {
					continue;
				}

				if(!in_array(strtolower( get_permalink($post->ID) ), $exclude)) {
					$url = get_permalink($post->ID);
				}
			}

			// Check if we have a url available to check
			if(empty($url)) {
				return $posts;
			}

			// we have the current page / url - get the groups selected
			$group_id = $this->get_group();

			if($group_id) {
				$group = new M_Urlgroup( $group_id );

				if( $group->url_matches( $url ) ) {
					$found = true;
				}
			}

			if($found !== true && !empty($M_options['nocontent_page'])) {
				// we need to redirect
				$this->redirect();
			} else {
				return $posts;
			}

		}

		return $posts;

	}


}

class M_Categories extends M_Rule {

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

	function on_positive($data) {

		$this->data = $data;

		add_action( 'pre_get_posts', array(&$this, 'add_viewable_posts'), 1 );
		add_filter( 'get_terms', array(&$this, 'add_viewable_categories'), 1, 3 );

		add_filter( 'the_posts', array(&$this, 'check_positive_posts'));
	}

	function on_negative($data) {

		$this->data = $data;

		add_action('pre_get_posts', array(&$this, 'add_unviewable_posts'), 1 );
		add_filter( 'get_terms', array(&$this, 'add_unviewable_categories'), 1, 3 );

		add_filter( 'the_posts', array(&$this, 'check_negative_posts'));
	}

	function redirect() {

		global $M_options;

		if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true ) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
		}

		$url = get_permalink( (int) $M_options['nocontent_page'] );

		wp_safe_redirect( $url );
		exit;

	}

	function check_negative_posts( $posts ) {

		global $wp_query, $M_options;

		$redirect = false;

		if(is_category() && count($posts) == 0 && MEMBERSHIP_REDIRECT_ON_EMPTY_CATEGORYPAGE === true) {
			$redirect = true;
		}

		if((!$wp_query->is_singular || count($posts) > 1) && $redirect != true) {
			return $posts;
		}

		foreach($posts as $post) {
			// should only be one as otherwise the single check above didn't work very well.
			if($post->post_type != 'post') {
				// Not a post so ignore
				return $posts;
			} else {
				// Check the categories
				if(has_category( $this->data, $post )) {
					$redirect = true;
				}
			}
		}

		if($redirect === true && !empty($M_options['nocontent_page'])) {
			// we need to redirect
			$this->redirect();
		} else {
			return $posts;
		}

	}

	function check_positive_posts( $posts ) {

		global $wp_query, $M_options;

		$redirect = false;

		if(is_category() && count($posts) == 0 && MEMBERSHIP_REDIRECT_ON_EMPTY_CATEGORYPAGE === true) {
			$redirect = true;
		}

		if((!$wp_query->is_singular || count($posts) > 1) && $redirect != true) {
			return $posts;
		}

		foreach($posts as $post) {
			// should only be one as otherwise the single check above didn't work very well.
			if($post->post_type != 'post') {
				// Not a post so ignore
				return $posts;
			} else {
				// Check the categories
				if(!has_category( $this->data, $post )) {
					$redirect = true;
				}

			}
		}

		if($redirect === true && !empty($M_options['nocontent_page'])) {
			// we need to redirect
			$this->redirect();
		} else {
			return $posts;
		}

	}

	function add_viewable_posts($wp_query) {

		//print_r($wp_query);

		if(!in_array($wp_query->query_vars['post_type'], array('post','')) || !empty($wp_query->query_vars['pagename'])) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['category__in'][] = $value;
		}

		$wp_query->query_vars['category__in'] = array_unique($wp_query->query_vars['category__in']);

	}

	function add_unviewable_posts($wp_query) {

		if(in_array($wp_query->query_vars['post_type'], array('page')) || !empty($wp_query->query_vars['pagename'])) {
			return;
		}

		foreach( (array) $this->data as $key => $value ) {
			$wp_query->query_vars['category__not_in'][] = $value;
		}

		$wp_query->query_vars['category__not_in'] = array_unique($wp_query->query_vars['category__not_in']);

	}

	function add_viewable_categories($terms, $taxonomies, $args) {

		foreach( (array) $terms as $key => $value ) {
			if($value->taxonomy == 'category') {
				if(!in_array($value->term_id, $this->data)) {
					unset($terms[$key]);
				}
			}
		}

		return $terms;
	}

	function add_unviewable_categories($terms, $taxonomies, $args) {

		foreach( (array) $terms as $key => $value ) {
			if($value->taxonomy == 'category') {
				if(in_array($value->term_id, $this->data)) {
					unset($terms[$key]);
				}
			}
		}

		return $terms;
	}

}

class M_More extends M_Rule {

	var $name = 'more';
	var $label = 'More tag';
	var $description = 'Allows content placed after the More tag to be protected.';

	var $rulearea = 'public';

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

class M_Comments extends M_Rule {

	var $name = 'comments';
	var $label = 'Comments';
	var $description = 'Allows the display of, or ability to comment on posts to be protected.';

	var $rulearea = 'public';

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

	function on_positive($data) {

		$this->data = $data;

		add_filter('comments_open', array(&$this, 'open_comments'), 99, 2);

	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('comments_open', array(&$this, 'close_comments'), 99, 2);

		if(defined('MEMBERSHIP_VIEW_COMMENTS') && MEMBERSHIP_VIEW_COMMENTS == true) {
			// We want users to be able to see the comments but not add to them
		} else {
			add_filter( 'comments_array', array(&$this, 'hide_comments'), 99, 2 );
		}

	}

	function hide_comments($comments, $post_id) {

		return array();

	}

	function close_comments($open, $postid) {

		return false;

	}

	function open_comments($open, $postid) {

		return $open;

	}

}


class M_Downloads extends M_Rule {

	var $name = 'downloads';
	var $label = 'Downloads';
	var $description = 'Allows media uploaded to the WordPress media library to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {

		global $wpdb, $M_options;

		if(!$data) $data = array();

		?>
		<div class='level-operation' id='main-downloads'>
			<h2 class='sidebar-name'><?php _e('Downloads', 'membership');?><span><a href='#remove' id='remove-downloads' class='removelink' title='<?php _e("Remove Downloads from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the Downloads / Media to be covered by this rule by checking the box next to the relevant group name.','membership'); ?></p>
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
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Download / Group name', 'membership'); ?></th>
						</tr>
						</thead>
						<tfoot>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Download / Group name', 'membership'); ?></th>
						</tr>
						</tfoot>

						<tbody>
						<?php
						if(!empty($M_options['membershipdownloadgroups'])) {

							foreach($M_options['membershipdownloadgroups'] as $key => $value) {
								if(!empty($value)) {
									?>
									<tr valign="middle" class="alternate" id="group-<?php echo esc_attr(stripslashes(trim($value))); ?>">
										<th class="check-column" scope="row">
											<input type="checkbox" value="<?php echo esc_attr(stripslashes(trim($value))); ?>" name="downloads[]" <?php if(in_array(esc_attr(stripslashes(trim($value))), $data)) echo 'checked="checked"'; ?>>
										</th>
										<td class="column-name">
											<strong><?php echo esc_html(stripslashes(trim($value))); ?></strong>
										</td>
								    </tr>
									<?php
								}
							}

						} else {
							?>
							<tr valign="middle" class="alternate" id="group-nogroup">
								<td class="column-name" colspan='2'>
									<?php echo __('You have no download groups set, please visit the membership options page to set them up.','membership'); ?>
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

	function can_view_download($area, $group) {

		switch($area) {

			case 'positive':	if(in_array($group, (array) $this->data)) {
									return true;
								}
								break;

			case 'negative':	if(in_array($group, (array) $this->data)) {
									return false;
								}
								break;

			default:			return false;

		}

	}

}

//shortcode_tags
class M_Shortcodes extends M_Rule {

	var $name = 'shortcodes';
	var $label = 'Shortcodes';
	var $description = 'Allows specific shortcodes and contained content to be protected.';

	var $rulearea = 'public';

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

	function on_creation() {
		//add_filter('the_content', array(&$this, 'override_shortcodes'), 1);
	}

	function override_shortcodes() {

		global $M_shortcode_tags, $shortcode_tags;

		$M_shortcode_tags = $shortcode_tags;

		foreach($shortcode_tags as $key => $function) {
			if($key != 'subscriptionform') {
				$shortcode_tags[$key] = array(&$this, 'do_protected_shortcode');
			}
		}

		return $content;
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
					if($code != 'subscriptionform') {
						$shortcode_tags[$code] = array(&$this, 'do_protected_shortcode');
					}
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

class M_Menu extends M_Rule {

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

class M_Blogcreation extends M_Rule {

	var $name = 'blogcreation';
	var $label = 'Blog Creation';
	var $description = 'Allows the creation of blogs to be limited to members.';

	var $rulearea = 'core';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-blogcreation'>
			<h2 class='sidebar-name'><?php _e('Blog Creation', 'membership');?><span><a href='#remove' id='remove-blogcreation' class='removelink' title='<?php _e("Remove Blog Creation from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<?php
					if(!isset($data['number'])) {
						$data['number'] = '';
					}
				?>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can create ','membership'); ?><input type='text' name='blogcreation[number]' value='<?php echo esc_attr($data['number']); ?>' /><?php _e(' blogs.','membership'); ?><br/><em><?php _e('Leave blank for unlimited blogs.','membership'); ?></em></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to create any blogs.','membership'); ?></p>
				<input type='hidden' name='blogcreation[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_creation() {

	}

	function on_positive($data) {

		$this->data = $data;

		add_filter( 'site_option_registration', array(&$this, 'pos_blog_creation'));
		add_filter( 'wpmu_active_signup', array(&$this, 'pos_blog_creation') );
	}

	function on_negative($data) {

		$this->data = $data;

		add_filter( 'site_option_registration', array(&$this, 'neg_blog_creation'));
		add_filter( 'wpmu_active_signup', array(&$this, 'neg_blog_creation') );

	}

	function neg_blog_creation( $active = 'all' ) {

		if($active == 'user' || $active == 'none') {
			return $active;
		} else {
			return 'none';
		}

	}

	function pos_blog_creation( $active = 'all' ) {

		if($active == 'user' || $active == 'none') {
			return $active;
		} else {
			// Check our count
			if(empty($this->data['number'])) {
				//  unlimited
				return $active;
			} else {
				$thelimit = (int) $this->data['number'];

				if( $thelimit > (int) $this->current_blog_count() ) {
					return $active;
				} else {
					return $this->neg_blog_creation( $active );
				}
			}

		}

	}

	function current_blog_count() {

		global $member, $wpdb;

		if(!empty($member) && method_exists($member, 'has_cap')) {
			// We have a member and it is a correct object
			$count = 0;
			$blogs = get_blogs_of_user( $member->ID );
			foreach( $blogs as $blog ) {
				if( $this->is_user_blog_admin( $member->ID, $blog->userblog_id ) ) {
					$count++;
	         	}
			}

			return (int) $count;
		} else {
			return 0;
		}

	}

	function is_user_blog_admin( $user_id, $blog_id ) {
		global $wpdb;

	    $meta_key = $wpdb->base_prefix . $blog_id . "_capabilities";

		$role_sql = $wpdb->prepare( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key );

		$role = $wpdb->get_results( $role_sql );

		//clean the role
		foreach($role as $key => $r) {
			$role[$key]->meta_value = maybe_unserialize($r->meta_value);
		}

		foreach($role as $key => $r) {
			if( $r->meta_value['administrator'] == 1 && $r->user_id == $user_id ) {
				return true;
			}
		}

		return false;

	}

}

class M_URLGroups extends M_Rule {

	var $name = 'urlgroups';
	var $label = 'URL Groups';
	var $description = "Allows specific URL's to be protected (includes ability to protect using regular expressions).";

	var $rulearea = 'core';

	function get_groups() {

		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM " . membership_db_prefix($wpdb, 'urlgroups') . " WHERE groupname NOT LIKE (%s) ORDER BY id ASC", '\_%' );

		$results = $wpdb->get_results( $sql );

		if(!empty($results)) {
			return $results;
		} else {
			return false;
		}
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-urlgroups'>
			<h2 class='sidebar-name'><?php _e('URL Groups', 'membership');?><span><a href='#remove' id='remove-urlgroups' class='removelink' title='<?php _e("Remove URL Groups from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the URL Groups to be covered by this rule by checking the box next to the relevant URL Group name.','membership'); ?></p>
				<?php
					$urlgroups = $this->get_groups();

					if(!empty($urlgroups)) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('URL Group', 'membership'); ?></th>
								</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('URL Group', 'membership'); ?></th>
								</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($urlgroups as $key => $urlgroup) {
							?>
							<tr valign="middle" class="alternate" id="urlgroup-<?php echo $urlgroup->id; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $urlgroup->id; ?>" name="urlgroups[]" <?php if(in_array($urlgroup->id, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($urlgroup->groupname); ?></strong>
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

		add_action( 'pre_get_posts', array(&$this, 'positive_check_request'), 1 );


	}

	function on_negative($data) {

		$this->data = $data;

		add_action( 'pre_get_posts', array(&$this, 'negative_check_request'), 1 );
	}

	function positive_check_request($wp) {

		global $M_options, $wp_query;

		$redirect = false;
		$found = false;
		$host = '';
		if(is_ssl()) {
			$host = "https://";
		} else {
			$host = "http://";
		}
		$host .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$exclude = array();
		if(!empty($M_options['registration_page'])) {
			$exclude[] = get_permalink( (int) $M_options['registration_page'] );
			$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
		}

		if(!empty($M_options['account_page'])) {
			$exclude[] = get_permalink( (int) $M_options['account_page'] );
			$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
		}

		if(!empty($M_options['nocontent_page'])) {
			$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
			$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
		}

		if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
			$exclude[] = $host;
			$exclude[] = untrailingslashit($host);
		}

		// we have the current page / url - get the groups selected
		foreach((array) $this->data as $group_id) {
			$group = new M_Urlgroup( $group_id );

			if($group->url_matches( $host ) && !in_array(strtolower($host), $exclude)) {
				// We've found a pge in the positive rules so can let the user see it
				$found = true;
			}
		}

		if($found !== true) {
			// we need to redirect
			$this->redirect();
		}

	}

	function negative_check_request($wp) {

		$redirect = false;
		$host = '';
		if(is_ssl()) {
			$host = "https://";
		} else {
			$host = "http://";
		}
		$host .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$exclude = array();
		if(!empty($M_options['registration_page'])) {
			$exclude[] = get_permalink( (int) $M_options['registration_page'] );
			$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
		}

		if(!empty($M_options['account_page'])) {
			$exclude[] = get_permalink( (int) $M_options['account_page'] );
			$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
		}

		if(!empty($M_options['nocontent_page'])) {
			$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
			$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
		}

		if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
			$exclude[] = $host;
			$exclude[] = untrailingslashit($host);
		}

		// we have the current page / url - get the groups selected
		foreach((array) $this->data as $group_id) {
			$group = new M_Urlgroup( $group_id );

			if($group->url_matches( $host ) && !in_array(strtolower($host), $exclude)) {
				$redirect = true;
			}
		}

		if($redirect === true) {
			// we need to redirect
			$this->redirect();
		}

	}

	function redirect() {

		global $M_options;

		if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true ) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
		}

		$url = get_permalink( (int) $M_options['nocontent_page'] );

		wp_safe_redirect( $url );
		exit;

	}

}

function M_setup_default_rules() {

	M_register_rule('downloads', 'M_Downloads', 'content');
	M_register_rule('comments', 'M_Comments', 'main');
	M_register_rule('more', 'M_More', 'main');
	M_register_rule('categories', 'M_Categories', 'main');
	M_register_rule('pages', 'M_Pages', 'main');
	M_register_rule('posts', 'M_Posts', 'main');
	M_register_rule('shortcodes', 'M_Shortcodes', 'content');
	M_register_rule('menu', 'M_Menu', 'main');
	M_register_rule('urlgroups', 'M_URLGroups', 'main');

	if(is_multisite()) {
		M_register_rule('blogcreation', 'M_Blogcreation', 'admin');
	}

}
add_action('plugins_loaded', 'M_setup_default_rules', 99);

?>