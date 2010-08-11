<?php
class M_Ignorehome extends M_Rule {

	var $name = 'ignorehome';

	function admin_sidebar($data) {
		?>
		<li class='level-draggable' id='ignorehome' <?php if($data === true) echo "style='display:none;'"; ?>>
			<div class='action action-draggable'>
				<div class='action-top'>
				<?php _e('Ignore Home','membership'); ?>
				</div>
			</div>
		</li>
		<?php
	}

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-ignorehome'>
			<h2 class='sidebar-name'><?php _e('Ignore Home', 'membership');?><span><a href='#remove' class='removelink' id='remove-ignorehome' title='<?php _e("Remove Capital P filter from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('Removes all page and post rule processing for the home page','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('Leaves all page and post rule processing in place for the home page.','membership'); ?></p>
				<input type='hidden' name='ignorehome[]' value='yes' />
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
//M_register_rule('ignorehome', 'M_Ignorehome', 'content');
?>