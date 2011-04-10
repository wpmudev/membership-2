<?php
	global $profileuser, $user_id, $user;

	if(isset($_POST['action']) && $_POST['action'] == 'update') {
		check_admin_referer('update-user_' . $user_id);

		wp_update_user( get_object_vars( $user ) );

		do_action('edit_user_profile_update', $user_id);
	}


?>
<div id="account-form">
	<div class="formleft">

		<div id='message'>Hello</div>

		<form action='' method='POST'>

		<?php wp_nonce_field('update-user_' . $user_id); ?>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />

		<table class="form-table">
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Username', 'membership'); ?></label></th>
				<td>
					<input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($profileuser->user_login); ?>" disabled="disabled" class="regular-text" /><br/><span class="description"><?php _e('Usernames cannot be changed.'); ?></span>
				</td>
			</tr>
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('First Name', 'membership'); ?></label></th>
				<td>
					<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($profileuser->first_name) ?>" class="regular-text" />
				</td>
			</tr>
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Last Name', 'membership'); ?></label></th>
				<td>
					<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($profileuser->last_name) ?>" class="regular-text" />
				</td>
			</tr>
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Nickname', 'membership'); ?></label></th>
				<td>
					<input type="text" name="nickname" id="nickname" value="<?php echo esc_attr($profileuser->nickname) ?>" class="regular-text" />
				</td>
			</tr>
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Display name publicly as', 'membership'); ?></label></th>
				<td>
					<select name="display_name" id="display_name">
					<?php
						$public_display = array();
						$public_display['display_username']  = $profileuser->user_login;
						$public_display['display_nickname']  = $profileuser->nickname;
						if ( !empty($profileuser->first_name) )
							$public_display['display_firstname'] = $profileuser->first_name;
						if ( !empty($profileuser->last_name) )
							$public_display['display_lastname'] = $profileuser->last_name;
						if ( !empty($profileuser->first_name) && !empty($profileuser->last_name) ) {
							$public_display['display_firstlast'] = $profileuser->first_name . ' ' . $profileuser->last_name;
							$public_display['display_lastfirst'] = $profileuser->last_name . ' ' . $profileuser->first_name;
						}
						if ( !in_array( $profileuser->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
							$public_display = array( 'display_displayname' => $profileuser->display_name ) + $public_display;
						$public_display = array_map( 'trim', $public_display );
						$public_display = array_unique( $public_display );
						foreach ( $public_display as $id => $item ) {
					?>
						<option id="<?php echo $id; ?>" value="<?php echo esc_attr($item); ?>"<?php selected( $profileuser->display_name, $item ); ?>><?php echo $item; ?></option>
					<?php
						}
					?>
					</select>
				</td>
			</tr>
		</table>

		<table class="form-table">
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Email', 'membership'); ?></label></th>
				<td>
					<input type="text" name="email" id="email" value="<?php echo esc_attr($profileuser->user_email) ?>" class="regular-text" />
				</td>
			</tr>
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Website', 'membership'); ?></label></th>
				<td>
					<input type="text" name="url" id="url" value="<?php echo esc_attr($profileuser->user_url) ?>" class="regular-text code" />
				</td>
			</tr>
		</table>

		<table class="form-table">
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('New Password', 'membership'); ?></label></th>
				<td>
					<input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" />
				</td>
			</tr>
			<tr style='background: transparent;'>
				<th></th>
				<td>
					<input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" />
				</td>
			</tr>
		</table>

		<table class="form-table">
			<tr style='background: transparent;'>
				<th></th>
				<td>
					<input type="submit" value="<?php _e('Update Account','membership'); ?>" class="button-primary" id="submit" name="submit">
				</td>
			</tr>
		</table>

		</form>
	</div>
</div>
<?php
?>