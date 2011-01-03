<?php

// Supporter membership integration
// Verion 0.1 alpha - by Barry (barry@incsub.com)

add_action( 'membership_subscription_form_after_levels', 'supporter_membership_subscription_settings' );
add_action( 'membership_subscription_update', 'supporter_membership_subscription_update');
add_action( 'membership_subscription_add', 'supporter_membership_subscription_update');
add_action( 'membership_move_subscription', 'supporter_membership_move_subscription', 10, 5 );
add_action( 'membership_add_subscription', 'supporter_membership_add_subscription', 10, 4);

// Add in parts to allow a user to select which blog/site is their main one
add_action('show_user_profile', 'supporter_membership_show_profile_section');
add_action('edit_user_profile', 'supporter_membership_show_profile_section');
add_action('edit_user_profile_update', 'supporter_membership_update_user_section');
add_action('personal_options_update', 'supporter_membership_update_user_section');

function supporter_membership_subscription_update( $sub_id ) {
	update_option( "membership_supporter_integration_" . $sub_id, $_POST['membership_supporter_integration'] );
}

function supporter_membership_subscription_settings( $sub_id ) {
	?>
	<h3><?php _e('Supporter integration settings','membership'); ?></h3>
	<div class='sub-details'>
	<p class='description'>
	<?php _e('By enabling the membership / supporter integration below the members primary_blog (for which they are an administrator) will made a supporter and will mirror this subscriptions level periods.','membership'); ?>
	</p>
	<label for='aff_pay'><?php _e('Enable membership / supporter integration','membership'); ?></label>
	<select name="membership_supporter_integration">
	<?php
		$membership_supporter_integration = get_option( "membership_supporter_integration_" . $sub_id );
		if(empty($membership_supporter_integration)) $membership_supporter_integration = 'no';
	?>
		<option value='no' <?php selected('no', $membership_supporter_integration); ?>><?php _e('No','membership'); ?></option>
		<option value='yes' <?php selected('yes', $membership_supporter_integration); ?>><?php _e('Yes','membership'); ?></option>
	</select>
	</div>
	<?php
}

function supporter_membership_show_profile_section( $user ) {

	global $wpdb, $current_site;

	?>
	<h3><?php IS_PROFILE_PAGE ? _e('Your Main Site') : _e('Users Main Site'); ?></h3>
	<table class="form-table">
	<tr>
		<th><label for="description"><?php _e('Select Main Site'); ?></label></th>
		<td>
			<?php
				$sql = "SELECT * FROM {$wpdb->usermeta} WHERE user_id = " . $user->ID . " AND meta_key LIKE 'wp_%_capabilities' AND meta_value LIKE '%administrator%'";
				$results = $wpdb->get_results( $sql );
				$blog_ids = array();

				  foreach ((array) $results as $row) {
				      $tmp = explode('_', $row->meta_key);
				      //skip main blog
				      if ($tmp[1] != $current_site->blogid) {
							$blog_ids[] = $tmp[1];
						}

				  }

				$myblogid = get_user_meta( $user->ID, 'm_supporter_blog', true );
			?>
			<select name='supporter_blog'>
			<?php
				if(!empty($blog_ids)) {
					foreach($blog_ids as $blogid) {
						$blog = get_blog_details($blogid);
						?>
						<option value='<?php echo $blogid; ?>' <?php selected($blogid, $myblogid); ?>><?php echo $blog->blogname; ?></option>
						<?php
					}
				}
			?>
			</select>
		</td>
	</tr>
	</table><br/><br/>
	<?php
}

function supporter_membership_update_user_section( $user_id ) {
	update_user_meta( $user_id, 'm_supporter_blog', $_POST['supporter_blog'] );
}

function supporter_membership_add_subscription($tosub_id, $tolevel_id, $to_order, $user_id) {
	return supporter_membership_move_subscription(0, $tosub_id, $tolevel_id, $to_order, $user_id);
}

function supporter_membership_move_subscription( $fromsub_id, $tosub_id, $tolevel_id, $to_order, $user_id ) {

	global $wpdb;

	$myblogid = get_user_meta( $user_id, 'm_supporter_blog', true );

	if(empty($myblogid)) {
		// not set so try to find
		$primary = get_user_meta( $user_id, 'primary_blog', true );
		if(!empty($primary)) {
			if($primary == '1') {
				$roles = get_user_meta( $user_id, $wpdb->base_prefix . $primary . '_capabilities', true );
				if(empty($roles)) {
					$roles = get_user_meta( $user_id, $wpdb->base_prefix . 'capabilities', true );
				}
			} else {
				$roles = get_user_meta( $user_id, $wpdb->base_prefix . $primary . '_capabilities', true );
			}
			if(!empty($roles) && array_key_exists('administrator', $roles)) {
				$myblogid = $primary;
			} else {
				return;
			}
		} else {
			return;
		}
	}

	// get the level so we can find the amount to extend by
	$subscription = new M_Subscription( $tosub_id );
	$level = $subscription->get_level_at($tolevel_id, $to_order);

	if(!empty($level) && !empty($myblogid)) {
		$start = current_time('mysql');
		switch($level->level_period_unit) {
			case 'd': $period = 'days'; break;
			case 'w': $period = 'weeks'; break;
			case 'm': $period = 'months'; break;
			case 'y': $period = 'years'; break;
			default: $period = 'days'; break;
		}
		$extend = strtotime('+' . $level->level_period . ' ' . $period );
		$extend = $extend - time();
		$extend = $extend + 3600;

		// For debuging
		//echo $myblogid;
		//echo $extend;
		//echo '+' . $level->level_period . ' ' . $period;
		//die();

		if(function_exists('supporter_extend')) {
			supporter_extend($myblogid, $extend, 'Membership');
		}

	}

}


?>