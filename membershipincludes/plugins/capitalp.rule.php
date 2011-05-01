<?php
class M_Capitalp extends M_Rule {

	var $name = 'capitalp';
	var $label = 'Capital P filter';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-capitalp'>
			<h2 class='sidebar-name'><?php _e('Capital P filter', 'membership');?><span><a href='#remove' class='removelink' id='remove-capitalp' title='<?php _e("Remove Capital P filter from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('Leaves the WP3 Capital P filter in place.','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('Removes the WP3 Capital P filter so Wordpress is not automatically replaced with WordPress in your content.','membership'); ?></p>
				<input type='hidden' name='capitalp[]' value='yes' />
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

		remove_filter( 'the_content', 'capital_P_dangit' );
		remove_filter( 'the_title', 'capital_P_dangit' );
		remove_filter( 'comment_text', 'capital_P_dangit' );
	}

}
M_register_rule('capitalp', 'M_Capitalp', 'content');
?>