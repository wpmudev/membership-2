<?php
class M_BPGroups extends M_Rule {

	var $name = 'bpgroups';

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='bpgroups' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Groups','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpgroups'>
			<h2 class='sidebar-name'><?php _e('Groups', 'membership');?><span><a href='#remove' id='remove-bpgroups' class='removelink' title='<?php _e("Remove Groups from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the groups to be covered by this rule by checking the box next to the relevant groups title.','membership'); ?></p>
				<?php

					$groups = groups_get_groups(array('per_page' => 50));

					if($groups) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Group title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Group created', 'membership'); ?></th>
							</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Group title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Group created', 'membership'); ?></th>
							</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($groups['groups'] as $key => $group) {
							?>
							<tr valign="middle" class="alternate" id="bpgroup-<?php echo $group->id; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $group->id; ?>" name="bpgroups[]" <?php if(in_array($group->id, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($group->name); ?></strong>
								</td>
								<td class="column-date">
									<?php
										echo date("Y/m/d", strtotime($group->date_created));
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

					if($groups['total'] > 50) {
						?>
						<p class='description'><?php _e("Only the most recent 50 groups are shown above.",'membership'); ?></p>
						<?php
					}

				?>

			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		$this->data = $data;

		add_filter( 'groups_get_groups', array(&$this, 'add_viewable_groups'), 10, 2 );
		add_filter( 'bp_has_groups', array(&$this, 'add_has_groups'), 10, 2);

		add_filter( 'bp_activity_get', array(&$this, 'add_has_activity'), 10, 2 );

	}

	function add_has_activity($activities, $two) {

		$inneracts = $activities['activities'];

		foreach( (array) $inneracts as $key => $act ) {

			if($act->component == 'groups') {
				if(!in_array($act->item_id, $this->data)) {
					unset($inneracts[$key]);
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach( (array) $inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_has_groups( $one, $groups) {

		$innergroups = $groups->groups;

		foreach( (array) $innergroups as $key => $group ) {
			if(!in_array($group->group_id, $this->data)) {
				unset($innergroups[$key]);
				$groups->total_group_count--;
			}
		}

		$groups->groups = array();
		foreach( (array) $innergroups as $key => $group ) {
			$groups->groups[] = $group;
		}

		if(empty($groups->groups)) {
			return false;
		} else {
			return true;
		}
	}

	function add_unhas_groups( $one, $groups) {

		$innergroups = $groups->groups;

		foreach( (array) $innergroups as $key => $group ) {
			if(in_array($group->group_id, $this->data)) {
				unset($innergroups[$key]);
				$groups->total_group_count--;
			}
		}

		$groups->groups = array();
		foreach( (array) $innergroups as $key => $group ) {
			$groups->groups[] = $group;
		}

		if(empty($groups->groups)) {
			return false;
		} else {
			return true;
		}
	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('groups_get_groups', array(&$this, 'add_unviewable_groups'), 10, 2 );
		add_filter( 'bp_has_groups', array(&$this, 'add_unhas_groups'), 10, 2);

		add_filter( 'bp_activity_get', array(&$this, 'add_unhas_activity'), 10, 2 );
	}

	function add_unhas_activity($activities, $two) {

		$inneracts = $activities['activities'];

		foreach( (array) $inneracts as $key => $act ) {

			if($act->component == 'groups') {
				if(in_array($act->item_id, $this->data)) {
					unset($inneracts[$key]);
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach( (array) $inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_viewable_groups($groups, $params) {

		$innergroups = $groups['groups'];

		foreach( (array) $innergroups as $key => $group ) {
			if(!in_array($group->id, $this->data)) {
				unset($innergroups[$key]);
				$groups['total']--;
			}
		}

		$groups['groups'] = array();
		foreach( (array) $innergroups as $key => $group ) {
			$groups['groups'][] = $group;
		}

		return $groups;

	}

	function add_unviewable_groups($groups, $params) {

		$innergroups = $groups['groups'];

		foreach( (array) $innergroups as $key => $group ) {
			if(in_array($group->id, $this->data)) {
				unset($innergroups[$key]);
				$groups['total']--;
			}
		}

		$groups['groups'] = array();
		foreach( (array) $innergroups as $key => $group ) {
			$groups['groups'][] = $group;
		}

		return $groups;

	}

}
M_register_rule('bpgroups', 'M_BPGroups', 'bp');

class M_BPBlogs extends M_Rule {

	var $name = 'bpblogs';

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='bpblogs' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Blogs','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpblogs'>
			<h2 class='sidebar-name'><?php _e('Blogs', 'membership');?><span><a href='#remove' id='remove-bpblogs' class='removelink' title='<?php _e("Remove Blogs from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the blogs to be covered by this rule by checking the box next to the relevant blogs name.','membership'); ?></p>
				<?php

					$blogs = bp_blogs_get_blogs(array('per_page' => 50));

					if($blogs) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Blog title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Last activity', 'membership'); ?></th>
							</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Blog title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Last activity', 'membership'); ?></th>
							</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($blogs['blogs'] as $key => $blog) {
							?>
							<tr valign="middle" class="alternate" id="bpblog-<?php echo $blog->blog_id; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $blog->blog_id; ?>" name="bpblogs[]" <?php if(in_array($blog->blog_id, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($blog->name); ?></strong>
								</td>
								<td class="column-date">
									<?php
										echo date("Y/m/d", strtotime($blog->last_activity));
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

					if($blogs['total'] > 50) {
						?>
						<p class='description'><?php _e("Only the most recently updated 50 blogs are shown above.",'membership'); ?></p>
						<?php
					}

				?>

			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		$this->data = $data;

		add_filter( 'bp_blogs_get_blogs', array(&$this, 'add_viewable_blogs'), 10, 2 );
		add_filter( 'bp_has_blogs', array(&$this, 'add_has_blogs'), 10, 2);

		add_filter( 'bp_activity_get', array(&$this, 'add_has_activity'), 10, 2 );

	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('bp_blogs_get_blogs', array(&$this, 'add_unviewable_blogs'), 10, 2 );
		add_filter( 'bp_has_blogs', array(&$this, 'add_unhas_blogs'), 10, 2);

		add_filter( 'bp_activity_get', array(&$this, 'add_unhas_activity'), 10, 2 );
	}

	function add_has_activity($activities, $two) {

		$inneracts = $activities['activities'];

		foreach( (array) $inneracts as $key => $act ) {

			if($act->component == 'blogs') {
				if(!in_array($act->item_id, $this->data)) {
					unset($inneracts[$key]);
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach( (array) $inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_unhas_activity($activities, $two) {

		$inneracts = $activities['activities'];

		foreach( (array) $inneracts as $key => $act ) {

			if($act->component == 'blogs') {
				if(in_array($act->item_id, $this->data)) {
					unset($inneracts[$key]);
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach( (array) $inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_unhas_blogs( $one, $blogs) {

		$innerblogs = $blogs->blogs;

		foreach( (array) $innerblogs as $key => $blog ) {
			if(in_array($blog->blog_id, $this->data)) {
				unset($innerblogs[$key]);
				$blogs->total_blog_count--;
			}
		}

		$blogs->blogs = array();
		foreach( (array) $innerblogs as $key => $blog ) {
			$blogs->blogs[] = $blog;
		}

		if(empty($blogs->blogs)) {
			return false;
		} else {
			return true;
		}
	}

	function add_has_blogs( $one, $blogs) {

		$innerblogs = $blogs->blogs;

		foreach( (array) $innerblogs as $key => $blog ) {
			if(!in_array($blog->blog_id, $this->data)) {
				unset($innerblogs[$key]);
				$blogs->total_blog_count--;
			}
		}

		$blogs->blogs = array();
		foreach( (array) $innerblogs as $key => $blog ) {
			$blogs->blogs[] = $blog;
		}

		if(empty($blogs->blogs)) {
			return false;
		} else {
			return true;
		}
	}

	function add_viewable_blogs($blogs, $params) {

		$innerblogs = $blogs['blogs'];

		foreach( (array) $innerblogs as $key => $blog ) {
			if(!in_array($blog->blog_id, $this->data)) {
				unset($innerblogs[$key]);
				$blogs['total']--;
			}
		}

		$blogs['blogs'] = array();
		foreach( (array) $innerblogs as $key => $blog ) {
			$blogs['blogs'][] = $blog;
		}

		return $blogs;

	}

	function add_unviewable_blogs($blogs, $params) {

		$innerblogs = $blogs['groups'];

		foreach( (array) $innerblogs as $key => $blog ) {
			if(in_array($blog->blog_id, $this->data)) {
				unset($innerblogs[$key]);
				$blogs['total']--;
			}
		}

		$blogs['blogs'] = array();
		foreach( (array) $innerblogs as $key => $blog ) {
			$blogs['blogs'][] = $blog;
		}

		return $blogs;

	}

}
M_register_rule('bpblogs', 'M_BPBlogs', 'bp');

// Add the buddypress section
function M_AddBuddyPressSection($sections) {
	$sections['bp'] = array(	"title" => __('BuddyPress','membership') );

	return $sections;
}

add_filter('membership_level_sections', 'M_AddBuddyPressSection');

?>