<?php
	global $profileuser, $user_id, $user;

	if(isset($_POST['action']) && $_POST['action'] == 'update') {

		if( wp_verify_nonce($_REQUEST['_wpnonce'], 'update-user_' . $user_id) ) {
			$msg = '<div class="alert alert-success">' . __('Your details have been updated.','membership') . '</div>';

			$user = array( 	'ID'			=>	$_POST['user_id'],
							'first_name'	=>	$_POST['first_name'],
							'last_name'		=>	$_POST['last_name'],
							'nickname'		=>	$_POST['nickname'],
							'display_name'	=>	$_POST['display_name'],
							'user_email'	=>	$_POST['email'],
							'user_url'		=>	$_POST['url']
						);

			if(!empty($_POST['pass1'])) {
				if(($_POST['pass1'] == $_POST['pass2'])) {
					$user['user_pass'] = $_POST['pass1'];
				} else {
					$msg = "<div class='alert alert-error'>" . __('Your password settings do not match','membership') . "</div>";
				}
			}

			$errors = edit_user( $user['ID'] );
			$profileuser = get_user_to_edit($user_id);

			if ( isset( $errors ) && is_wp_error( $errors ) ) {
				$msg = "<div class='alert alert-error'>" . implode( "<br/>\n", $errors->get_error_messages() ) . "</div>";
			}

		} else {
			$msg = "<div class='alert alert-error'>" . __('Your details could not be updated.','membership') . "</div>";
		}

		do_action('edit_user_profile_update', $user_id);
	}


?>

<div id='membership-wrapper'>

<?php if(!empty($msg)) {
?>
	<?php echo $msg; ?>
<?php
} ?>

<form class="form-membership" action="<?php echo get_permalink(); ?>" method="post">
	<?php wp_nonce_field('update-user_' . $user_id); ?>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />

	<fieldset>
		<legend><?php _e( 'Edit your details', 'membership' ) ?></legend>

			<div class="form-element">
				<label class="control-label" for="user_login"><?php _e('Username', 'membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="user_login" nmae="user_login" placeholder="" value="<?php echo esc_attr($profileuser->user_login); ?>" disabled="disabled" >
					<p class="help-block"><?php _e('Usernames cannot be changed.','membership'); ?></p>
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="first_name"><?php _e('First Name', 'membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="first_name" name="first_name" placeholder="" value="<?php echo esc_attr($profileuser->first_name); ?>" >
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="last_name"><?php _e('Last Name', 'membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="last_name" name="last_name" placeholder="" value="<?php echo esc_attr($profileuser->last_name) ?>" >
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="nickname"><?php _e('Nickname', 'membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="nickname" name="nickname" placeholder="" value="<?php echo esc_attr($profileuser->nickname) ?>" >
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="display_name"><?php _e('Display name as', 'membership'); ?></label>
				<div class="element">
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
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="email"><?php _e('Email', 'membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" name="email" id="email" value="<?php echo esc_attr($profileuser->user_email) ?>" />
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="url"><?php _e('Website', 'membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" name="url" id="url" value="<?php echo esc_attr($profileuser->user_url) ?>" />
				</div>
			</div>

			<div class="form-element">
				<p class="help-block"><?php _e('To change your password, enter the new password below and then repeat it to confirm, otherwise leave these two fields blank.','membership'); ?></p>
			</div>

			<div class="form-element">
				<label class="control-label" for="pass1"><?php _e('New Password', 'membership'); ?></label>
				<div class="element">
					<input type="password" class="input-xlarge" name="pass1" id="pass1" value="" autocomplete="off" />
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="pass1"><?php _e('Confirm Password', 'membership'); ?></label>
				<div class="element">
					<input type="password" class="input-xlarge" name="pass2" id="pass2" value="" autocomplete="off" />
				</div>
			</div>

			<p><input type="submit" value="<?php _e('Update Account','membership'); ?>" class="alignright button <?php echo apply_filters('membership_account_button_color', 'blue'); ?>" name="submit"></p>

	</fieldset>

</form>

</div>
<?php
?>