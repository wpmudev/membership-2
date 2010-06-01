<?php
class M_Groups extends M_Rule {

	function __construct() {

	}

	function M_Groups() {

	}

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='groups' <?php if($data === true) echo "style='display:none;'"; ?>>
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
		<div class='level-operation' id='main-groups'>
			<h2 class='sidebar-name'><?php _e('Groups', 'membership');?><span><a href='#remove' id='remove-groups' class='removelink' title='<?php _e("Remove Groups from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
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

		//add_action('pre_get_posts', array(&$this, 'add_viewable_posts'), 1 );
	}

	function on_negative($data) {

		$this->data = $data;

		//add_action('pre_get_posts', array(&$this, 'add_unviewable_posts'), 1 );
	}

}
M_register_rule('groups', 'M_Groups', 'bp');

// Add the buddypress section
function M_AddBuddyPressSection($sections) {
	$sections['bp'] = array(	"title" => __('BuddyPress','membership') );

	return $sections;
}

add_filter('membership_level_sections', 'M_AddBuddyPressSection');

?>