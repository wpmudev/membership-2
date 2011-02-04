<?php
// Membership dashboard - help window

function M_help_widget() {
	?>
	<div class="postbox " id="dashboard_quick_press">
		<h3 class="hndle"><span><?php _e('Quick Guide','membership'); ?></span></h3>
		<div class="inside">
			<?php echo "hello"; ?>
			<br class="clear">
		</div>
	</div>
	<?php
}

add_action( 'membership_dashboard_right_top', 'M_help_widget', 1 );
?>