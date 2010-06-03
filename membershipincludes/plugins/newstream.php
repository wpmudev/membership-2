<?php
// This plugin generates a news stream for the membership plugin dashboard

function membership_news_stream() {
	?>

	<div class="postbox " id="dashboard_news">
		<h3 class="hndle"><span><?php _e('News','membership'); ?></span></h3>
		<div class="inside">
			<?php
			echo "<p>" . __('There will be some interesting news here.','membership') . "</p>";
			?>
			<br class="clear">
		</div>
	</div>

	<?php
}

add_action( 'membership_dashboard_left', 'membership_news_stream');

?>