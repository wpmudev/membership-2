<?php
// This plugin generates a news stream for the membership plugin dashboard

function membership_install() {

}

function membership_record_user_subscribe($tosub_id, $tolevel_id, $to_order) {

}
add_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 3 );

function membership_record_user_level($tolevel_id, $this->ID) {

}
add_action( 'membership_add_level', 'membership_record_user_level' , 10, 2 );

function membership_record_user_expire($sub_id, $user_id) {

}
add_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );

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