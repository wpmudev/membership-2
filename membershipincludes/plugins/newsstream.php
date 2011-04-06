<?php
// This plugin generates a news stream for the membership plugin dashboard

function membership_news_install() {

	$build = 1;

	$installed = get_option('M_Newsstream_Installed', false);

	if($installed === false || $installed != $build) {
		membership_newsstreamcreatetables($installed);

		update_option('M_Newsstream_Installed', $build);
	}
}
add_action( 'init', 'membership_news_install');

function membership_newsstreamcreatetables($installed = false) {

	global $wpdb;

	if($installed !== false) {
		$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'membership_news', false) . " TO " . membership_db_prefix($wpdb, 'membership_news') . ";";
		$wpdb->query($sql);
	} else {
		// Added for RC
		$sql = "CREATE TABLE `" . membership_db_prefix($wpdb, 'membership_news') . "` (
		  `id` bigint(11) NOT NULL auto_increment,
		  `newsitem` text,
		  `newsdate` datetime default NULL,
		  PRIMARY KEY  (`id`)
		);";

		$wpdb->query($sql);
	}

}

function membership_record_user_subscribe($tosub_id, $tolevel_id, $to_order, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$sub = new M_Subscription( $tosub_id );
	$level = new M_Level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has joined level <strong>%s</strong> on subscription <strong>%s</strong>','membership' ), $user->display_name, $level->level_title(), $sub->sub_name() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );

function membership_record_user_level($tolevel_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$level = new M_Level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has joined level <strong>%s</strong>','membership' ), $user->display_name, $level->level_title() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_add_level', 'membership_record_user_level' , 10, 2 );

function membership_record_user_expire($sub_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$sub = new M_Subscription( $tosub_id );

	$message = sprintf(__( '<strong>%s</strong> has left subscription <strong>%s</strong>','membership' ), $user->display_name, $sub->sub_name() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );

function membership_record_sub_drop($sub_id, $level_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$sub = new M_Subscription( $sub_id );
	$level = new M_Level( $level_id );

	$message = sprintf(__( '<strong>%s</strong> has left level <strong>%s</strong> on subscription <strong>%s</strong>','membership' ), $user->display_name, $level->level_title(), $sub->sub_name() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_drop_subscription', 'membership_record_sub_drop', 10, 3 );

function membership_record_level_drop($level_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$level = new M_Level( $level_id );

	$message = sprintf(__( '<strong>%s</strong> has left level <strong>%s</strong>','membership' ), $user->display_name, $level->level_title() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_drop_level', 'membership_record_level_drop', 10, 2 );

function membership_record_sub_move($fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$fromsub = new M_Subscription( $fromsub_id );
	$tosub = new M_Subscription( $tosub_id );
	$fromlevel = new M_Level( $fromlevel_id );
	$level = new M_Level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has moved from level <strong>%s</strong> on subscription <strong>%s</strong> to level <strong>%s</strong> on subscription <strong>%s</strong>','membership' ), $user->display_name, $fromlevel->level_title(), $fromsub->sub_name(), $level->level_title(), $tosub->sub_name()  );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_move_subscription', 'membership_record_sub_move', 10, 6 );

function membership_record_level_move($fromlevel_id, $tolevel_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$fromlevel = new M_Level( $fromlevel_id );
	$tolevel = new M_Level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has moved from level <strong>%s</strong> to level <strong>%s</strong>','membership' ), $user->display_name, $fromlevel->level_title(), $tolevel->level_title() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_move_level', 'membership_record_level_move', 10, 3 );


function membership_news_stream() {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	$news = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY newsdate DESC LIMIT 0, 50") );

	?>

	<div class="postbox " id="dashboard_news">
		<h3 class="hndle"><span><?php _e('News','membership'); ?></span></h3>
		<div class="inside">
			<?php

			if(!empty($news)) {

				foreach($news as $key => $newsitem) {
					echo "<p id='newsitem-" . $newsitem->id . "'>";
					echo $newsitem->newsitem;
					echo "</p>";
				}

			} else {
				echo "<p>" . __('There will be some interesting news here when your site gets going.','membership') . "</p>";
			}

			?>
			<br class="clear">
		</div>
	</div>

	<?php
}

add_action( 'membership_dashboard_left', 'membership_news_stream');

?>