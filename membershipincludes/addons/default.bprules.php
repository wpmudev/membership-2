<?php
/*
Addon Name: Default BuddyPress Rules
Description: Main BuddyPress rules
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

// Buddypress pages - $directory_pages = apply_filters( 'bp_directory_pages', $directory_pages );
class M_BPPages extends M_Rule {

	var $name = 'bppages';
	var $label = 'BuddyPress Pages';

	var $rulearea = 'public';

	function get_pages() {

	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bppages'>
			<h2 class='sidebar-name'><?php _e('BuddyPress Pages', 'membership');?><span><a href='#remove' id='remove-bppages' class='removelink' title='<?php _e("Remove BuddyPress Pages from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Please use the URL Groups for a more reliable method of restricting access to BuddyPress pages.','membership'); ?></p>
			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		$this->data = $data;

	}

	function on_negative($data) {

		$this->data = $data;

	}
}
M_register_rule('bppages', 'M_BPPages', 'bp');

class M_BPGroups extends M_Rule {

	var $name = 'bpgroups';
	var $label = 'Groups';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpgroups'>
			<h2 class='sidebar-name'><?php _e('Groups', 'membership');?><span><a href='#remove' id='remove-bpgroups' class='removelink' title='<?php _e("Remove Groups from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the groups to be covered by this rule by checking the box next to the relevant groups title.','membership'); ?></p>
				<?php

					if(function_exists('groups_get_groups')) {
						$groups = groups_get_groups(array('per_page' => 50));
					}

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

class M_BPGroupcreation extends M_Rule {

	var $name = 'bpgroupcreation';
	var $label = 'Group Creation';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpgroupcreation'>
			<h2 class='sidebar-name'><?php _e('Group Creation', 'membership');?><span><a href='#remove' id='remove-bpgroupcreation' class='removelink' title='<?php _e("Remove Group Creation from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can create ','membership'); ?><input type='text' name='bpgroupcreation[number]' value='<?php echo esc_attr($data['number']); ?>' /><?php _e(' groups.','membership'); ?><br/><em><?php _e('Leave blanks for unlimited groups.','membership'); ?></em></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to create any groups.','membership'); ?></p>
				<input type='hidden' name='bpgroupcreation[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		$this->data = $data;

		add_filter('groups_template_create_group', array(&$this, 'pos_bp_groups_template'));
	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('groups_template_create_group', array(&$this, 'neg_bp_groups_template'));

	}

	function pos_bp_groups_template($template) {
	  	global $bp;

	  	// Positive - check if the count is set
	  	if(empty($this->data['number'])) {
			return $template;
		} else {
			if(is_numeric($this->data['number']) && (int) $this->data['number'] > $this->users_group_count() ) {
				return $template;
			} else {
				return $this->neg_bp_groups_template($template);
			}
		}
	}

	function users_group_count() {
		global $member, $wpdb, $bp;

		if(!empty($member) && method_exists($member, 'has_cap')) {
			// We have a member and it is a correct object
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM {$bp->activity->table_name} WHERE component = 'groups' AND type = 'created_group' AND user_id = %d", $member->ID) );

			return (int) $count;
		} else {
			return 0;
		}
	}

	function neg_bp_groups_template($template) {
	  global $bp;

	  //hack template steps to hide creation form elements
	  $bp->action_variables[1] = 'disabled'; //nonsensical value, hide all group steps
	  $bp->avatar_admin->step = 'crop-image'; //hides submit button
	  add_action( 'template_notices', array(&$this, 'neg_bp_message') );

	  return $template;
	}

	function neg_bp_message() {

		$MBP_options = get_option('membership_bp_options', array());

	 	echo '<div id="message" class="error"><p>' . stripslashes($MBP_options['buddypressmessage']) . '</p></div>';

	}



}
M_register_rule('bpgroupcreation', 'M_BPGroupcreation', 'bp');

class M_BPBlogs extends M_Rule {

	var $name = 'bpblogs';
	var $label = 'Blogs';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpblogs'>
			<h2 class='sidebar-name'><?php _e('Blogs', 'membership');?><span><a href='#remove' id='remove-bpblogs' class='removelink' title='<?php _e("Remove Blogs from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the blogs to be covered by this rule by checking the box next to the relevant blogs name.','membership'); ?></p>
				<?php

					if(function_exists('bp_blogs_get_blogs')) {
						$blogs = bp_blogs_get_blogs(array('per_page' => 50));
					}

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

		add_filter( 'bp_get_total_blog_count', array(&$this, 'fix_blog_count'));

	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('bp_blogs_get_blogs', array(&$this, 'add_unviewable_blogs'), 10, 2 );
		add_filter( 'bp_has_blogs', array(&$this, 'add_unhas_blogs'), 10, 2);

		add_filter( 'bp_activity_get', array(&$this, 'add_unhas_activity'), 10, 2 );

		add_filter( 'bp_get_total_blog_count', array(&$this, 'fix_unblog_count'));
	}

	function fix_blog_count($count) {

		$count = count($this->data);

		return $count;
	}

	function fix_unblog_count($count) {

		$count -= count($this->data);

		return $count;
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

class M_BPPrivatemessage extends M_Rule {

	var $name = 'bpprivatemessage';
	var $label = 'Private Messaging';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpprivatemessage'>
			<h2 class='sidebar-name'><?php _e('Private Messaging', 'membership');?><span><a href='#remove' id='remove-bpprivatemessage' class='removelink' title='<?php _e("Remove Private Messaging from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can send messages.','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to send messages.','membership'); ?></p>
				<input type='hidden' name='bpprivatemessage[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		$this->data = $data;

		add_filter('messages_template_compose', array(&$this, 'pos_bp_messages_template') );
	}

	function on_negative($data) {

		$this->data = $data;

		add_filter('messages_template_compose', array(&$this, 'neg_bp_messages_template') );

	}

	function pos_bp_messages_template($template) {

	  return $template;
	}

	function neg_bp_messages_template($template) {

	  add_action( 'bp_template_content', array(&$this, 'neg_bp_message') );

	  return 'members/single/plugins';
	}

	function neg_bp_message() {
	  $MBP_options = get_option('membership_bp_options', array());

	  echo '<div id="message" class="error"><p>' . stripslashes($MBP_options['buddypressmessage']) . '</p></div>';

	}



}
M_register_rule('bpprivatemessage', 'M_BPPrivatemessage', 'bp');


// Pass thru function
function MBP_can_access_page( $page ) {

	global $user, $member;

	if(!empty($member) && method_exists($member, 'pass_thru')) {
		return $member->pass_thru( 'bppages', array( 'can_access_page' => $page ) );
	}

}


function M_AddBuddyPressSection($sections) {
	$sections['bp'] = array(	"title" => __('BuddyPress','membership') );

	return $sections;
}

add_filter('membership_level_sections', 'M_AddBuddyPressSection');

// BuddyPress options
function M_AddBuddyPressOptions() {

	$MBP_options = get_option('membership_bp_options', array());
	?>
	<h3><?php _e('BuddyPress protected content message','membership'); ?></h3>
	<p><?php _e('This is the message that is displayed when a BuddyPress related operation is restricted. Depending on your theme this is displayed in a red bar, and so should be short and concise.','membership'); ?></p>

	<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php _e('BuddyPress No access message','membership'); ?><br/>
			<em style='font-size:smaller;'><?php _e("This is the message that is displayed when a BuddyPress related operation is restricted.",'membership'); ?><br/>
			<?php _e("Leave blank for no message.",'membership'); ?><br/>
			<?php _e("HTML allowed.",'membership'); ?>
			</em>
			</th>
			<td>
				<textarea name='buddypressmessage' id='buddypressmessage' rows='5' cols='40'><?php esc_html_e(stripslashes($MBP_options['buddypressmessage'])); ?></textarea>
			</td>
		</tr>
	</tbody>
	</table>
	<?php
}
add_action( 'membership_options_page', 'M_AddBuddyPressOptions' );

function M_AddBuddyPressOptionsProcess() {

	$MBP_options = get_option('membership_bp_options', array());

	$MBP_options['buddypressmessage'] = $_POST['buddypressmessage'];

	update_option('membership_bp_options', $MBP_options);

}
add_action( 'membership_options_page_process', 'M_AddBuddyPressOptionsProcess' );


function M_HideBuddyPressPages( $pages ) {

	if(function_exists('bp_core_get_directory_page_ids')) {
		$existing_pages = bp_core_get_directory_page_ids();
	}

	foreach( $pages as $key => $page ) {
		if( in_array( $page->ID, (array) $existing_pages ) ) {
			unset( $pages[$key] );
		}
	}

	return $pages;

}
add_filter( 'staypress_hide_protectable_pages', 'M_HideBuddyPressPages' );

?>