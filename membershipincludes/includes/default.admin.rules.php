<?php
class M_Admintest extends M_Rule {

	var $name = 'admintest';
	var $adminside = true;

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='admintest' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Admin Test','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-admintest'>
			<h2 class='sidebar-name'><?php _e('Admin Test', 'membership');?><span><a href='#remove' class='removelink' id='remove-admintest' title='<?php _e("Remove Admin Test tag from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can read full post content beyond the More tag.','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to read full post content beyond the More tag.','membership'); ?></p>
				<input type='hidden' name='admintest[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_positive($data) {

		global $M_options, $wp_filter;

		$this->data = $data;


	}

	function on_negative($data) {

		global $M_options;

		$this->data = $data;


	}

}
M_register_rule('admintest', 'M_Admintest', 'admin');

function M_AddAdminSection($sections) {
	$sections['admin'] = array(	"title" => __('Administration','membership') );

	return $sections;
}

add_filter('membership_level_sections', 'M_AddAdminSection', 99);

?>