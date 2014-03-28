<?php
/*
Addon Name: Dashboard News Stream
Description: Members newstream dashboard widget
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

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
	
	require_once membership_dir('membershipincludes/classes/upgrade.php');
	
	$charset_collate = M_get_charset_collate();
	
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
		) $charset_collate;";

		$wpdb->query($sql);
	}

}

function membership_record_user_subscribe($tosub_id, $tolevel_id, $to_order, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');
	$factory = Membership_Plugin::factory();

	// Get the information
	$user = new WP_User( $user_id );
	$sub = $factory->get_subscription( $tosub_id );
	$level = $factory->get_level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has joined level <strong>%s</strong> on subscription <strong>%s</strong>','membership' ), $user->display_name, $level->level_title(), $sub->sub_name() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );

function membership_record_user_level($tolevel_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$level = Membership_Plugin::factory()->get_level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has joined level <strong>%s</strong>','membership' ), $user->display_name, $level->level_title() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_add_level', 'membership_record_user_level' , 10, 2 );

function membership_record_user_expire($sub_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$sub = Membership_Plugin::factory()->get_subscription( $sub_id );

	$message = sprintf(__( '<strong>%s</strong> has left subscription <strong>%s</strong>','membership' ), $user->display_name, $sub->sub_name() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );

function membership_record_sub_drop($sub_id, $level_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');
	$factory = Membership_Plugin::factory();

	// Get the information
	$user = new WP_User( $user_id );
	$sub = $factory->get_subscription( $sub_id );
	$level = $factory->get_level( $level_id );

	$message = sprintf(__( '<strong>%s</strong> has left level <strong>%s</strong> on subscription <strong>%s</strong>','membership' ), $user->display_name, $level->level_title(), $sub->sub_name() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_drop_subscription', 'membership_record_sub_drop', 10, 3 );

function membership_record_level_drop($level_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$user = new WP_User( $user_id );
	$level = Membership_Plugin::factory()->get_level( $level_id );

	$message = sprintf(__( '<strong>%s</strong> has left level <strong>%s</strong>','membership' ), $user->display_name, $level->level_title() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_drop_level', 'membership_record_level_drop', 10, 2 );

function membership_record_sub_move($fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	// Get the information
	$factory = Membership_Plugin::factory();
	$user = new WP_User( $user_id );
	$fromsub = $factory->get_subscription( $fromsub_id );
	$tosub = $factory->get_subscription( $tosub_id );
	$fromlevel = $factory->get_level( $fromlevel_id );
	$level = $factory->get_level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has moved from level <strong>%s</strong> on subscription <strong>%s</strong> to level <strong>%s</strong> on subscription <strong>%s</strong>','membership' ), $user->display_name, $fromlevel->level_title(), $fromsub->sub_name(), $level->level_title(), $tosub->sub_name()  );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_move_subscription', 'membership_record_sub_move', 10, 6 );

function membership_record_level_move($fromlevel_id, $tolevel_id, $user_id) {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');
	$factory = Membership_Plugin::factory();

	// Get the information
	$user = new WP_User( $user_id );
	$fromlevel = $factory->get_level( $fromlevel_id );
	$tolevel = $factory->get_level( $tolevel_id );

	$message = sprintf(__( '<strong>%s</strong> has moved from level <strong>%s</strong> to level <strong>%s</strong>','membership' ), $user->display_name, $fromlevel->level_title(), $tolevel->level_title() );

	$wpdb->insert( $table, array( 'newsitem' => $message, 'newsdate' => current_time('mysql') ) );

}
add_action( 'membership_move_level', 'membership_record_level_move', 10, 3 );


function membership_news_stream() {

	global $wpdb;

	$table = membership_db_prefix($wpdb, 'membership_news');

	$news = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY newsdate DESC LIMIT 0, %d", 50) );

	?>

	<div class="postbox " id="dashboard_news">
		<h3 class="hndle"><span><?php _e('News','membership'); ?></span></h3>
		<div class="inside">
			<?php

			if(!empty($news)) {

				foreach($news as $key => $newsitem) {
					echo "<p id='newsitem-" . $newsitem->id . "'>";
					echo "[ ";
					echo date("Y-m-d : H:i", strtotime($newsitem->newsdate));
					echo " ] ";
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