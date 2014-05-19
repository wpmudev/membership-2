<?php

$msg = '';
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
/* Are extended profiles activated? */
$xprofile_active = function_exists('bp_is_active') ? bp_is_active( 'xprofile' ) : false;

if ( isset( $_POST['action'] ) && $_POST['action'] == 'update' ) {
	
	if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-user_' . $user_id ) ) {
		
		$msg = '<div class="alert alert-success">' . __( 'Your details have been updated.', 'membership' ) . '</div>';
		
		/* Error detection */
		$errors = new WP_Error();

		/* Parse BuddyPress field ID's if present. */
		$bp_field_ids = ( isset( $_POST['bp_field_ids'] ) && ! empty( $_POST['bp_field_ids'] ) ) ? wp_parse_id_list( $_POST['bp_field_ids'] ) : false;
		
		/* Check for required BuddyPress fields. */
		if ( $bp_field_ids && $xprofile_active ) {
			
			/* Index required fields */
			$is_required = array();
			
			/* Check for required fields */
			foreach( (array) $bp_field_ids as $field_id ) {
				
				/* Special case for date fields. */
				if ( ! isset ( $_POST['field_' . $field_id] ) ) {
					if ( ! empty ( $_POST['field_'. $field_id . '_day'] ) && ! empty ( $_POST['field_'. $field_id . '_month'] ) && ! empty ( $_POST['field_'. $field_id . '_year'] ) ) {
						$date_value =   $_POST['field_' . $field_id . '_day'] . ' ' . $_POST['field_' . $field_id . '_month'] . ' ' . $_POST['field_' . $field_id . '_year'];
						/* Merge date fields */
						$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $date_value ) );
					}
				}
				
				/* Mark field as required */
				$is_required[ $field_id ] = xprofile_check_is_required_field( $field_id );
				
				if ( $is_required[ $field_id ] && empty ( $_POST['field_' . $field_id] ) ) {
					$field = xprofile_get_field( $field_id );
					$errors->add( 'field_' . $field_id, sprintf( __( '%s is required.', 'membership'), $field->name ) );
					unset( $field );
				}
			}
		}
		
		if ( ! empty ( $_POST['pass1'] ) && $_POST['pass1'] != $_POST['pass2'] ) {
			$errors->add( 'pass1', __( 'Your password settings do not match', 'membership' ) );
		}
		
		/* Update the user. */
		if ( ! $errors->get_error_code() ) {
			
			/* Update user with fields from $_POST and get response. */
			$response = edit_user( $user_id );
			
			/* If there are no errors and Extended Profiles are active... */
			if ( ! is_wp_error( $response ) && $xprofile_active ) {

				/* Update BuddyPress fields. */
				if ( isset( $bp_field_ids ) ) {
					
					/* Replace errors with BuddyPress update errors. */
					$errors = new WP_Error();
					
					/* Update each field or record error. */
					foreach( (array) $bp_field_ids as $field_id ) {
						$value = isset( $_POST['field_' . $field_id] ) ? $_POST['field_' . $field_id] : '';	 

					    if( ! xprofile_set_field_data( $field_id, $user_id, $value, $is_required[$field_id] ) ) {
							$field = xprofile_get_field( $field_id );
							$errors->add( 'field_' . $field_id, sprintf( __( 'Error updating \'%s\'.', 'membership'), $field->name ) );
							unset($field);
						}
					}	
				}			
			} else {
				/* Replace field errors with update errors. */
				$errors = $response;
			}
		}		
		
		if ( isset( $errors ) && is_object( $errors ) && $errors->get_error_code() ){
			$msg = '<div class="alert alert-error">' . implode( '<br>', $errors->get_error_messages() ) . '</div>';
		} 
	}

	do_action( 'edit_user_profile_update', $user_id );
}

$profileuser = get_user_to_edit( $user_id );

?>
<div id="account-form">
	<div class="formleft">

	<?php if(!empty($msg)) {
	?>
		<div id='message'><p><?php echo $msg; ?></p></div>
	<?php
	} ?>
		<p class="membership-shortcode-accountpage-toggle"><?php echo sprintf(__('<strong>Hello %s</strong>, to edit your account details click on the edit link.','membership'),$profileuser->display_name) ; ?>
		<span>
		<a href='#edit' id='membershipaccounttoggle'><?php _e('edit','membership'); ?></a>
		</span>
		</p>

		<form action='' method='POST'>

		<?php wp_nonce_field('update-user_' . $user_id); ?>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />
		
		<?php if ( $xprofile_active ) : ?>
			<h4><?php _e( 'Standard Settings', 'membership' ); ?></h4>
		<?php endif; ?>

		<table class="form-table">
			<tr style='background: transparent;'>
				<th><label for="enable_affiliate"><?php _e('Username', 'membership'); ?></label></th>
				<td>
					<input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($profileuser->user_login); ?>" disabled="disabled" class="regular-text" /><br/><span class="description"><?php _e('Usernames cannot be changed.','membership'); ?></span>
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
				<th><?php _e('Confirm Password', 'membership'); ?></th>
				<td>
					<input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" />
				</td>
			</tr>
		</table>

        <?php 
		
		  $args = array(
		    'user_id' => get_current_user_id( ),
		     'hide_empty_fields' => false
		  );
		  if ( $xprofile_active && bp_has_profile( $args ) ) :
	      while ( bp_profile_groups() ) : bp_the_profile_group(); 
		   
		?>
		<h4><?php echo bp_get_the_profile_group_name(); ?></h4>
		
		<table class="form-table">
		    <?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>
			<?php
				/* Setup field classes. */
				$bp_classes = array();
				$bp_classes[] = 'field_' . bp_get_the_profile_field_id();

				$bp_classes[] = 'field_' . sanitize_title( bp_get_the_profile_field_name() );
				if ( bp_get_the_profile_field_is_required() ) {
					$bp_classes[] = 'bp-field-required';
					$bp_classes[] = 'field_' . bp_get_the_profile_field_id() . '_required';
					$bp_classes[] = 'field_' . sanitize_title( bp_get_the_profile_field_name() ) . '_required';
				}
				$css_classes = ' class="' . implode( ' ', $bp_classes ) . '"';
			?>

            <tr style='background: transparent;' <?php echo $css_classes ?>>

                <th><label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label></th>
				<td>
				
				<?php if ( 'textbox' == bp_get_the_profile_field_type() ) : ?>

					<input type="text" name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" value="<?php bp_the_profile_field_edit_value(); ?>" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>/>

				<?php endif; ?>
				
				<?php if ( 'number' == bp_get_the_profile_field_type() ) : ?>

					<input type="text" name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" value="<?php bp_the_profile_field_edit_value(); ?>" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>/>

				<?php endif; ?>

				<?php if ( 'textarea' == bp_get_the_profile_field_type() ) : ?>

					<textarea rows="5" cols="40" name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>><?php bp_the_profile_field_edit_value(); ?></textarea>

				<?php endif; ?>

				<?php if ( 'selectbox' == bp_get_the_profile_field_type() ) : ?>

					<select name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>>
						<?php bp_the_profile_field_options( array( 'user_id' => $user_id )  ); ?>
					</select>

				<?php endif; ?>

				<?php if ( 'multiselectbox' == bp_get_the_profile_field_type() ) : ?>

					<select name="<?php bp_the_profile_field_input_name(); ?>" id="<?php bp_the_profile_field_input_name(); ?>" multiple="multiple" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>>

						<?php bp_the_profile_field_options( array( 'user_id' => $user_id )  ); ?>

					</select>

					<?php if ( !bp_get_the_profile_field_is_required() ) : ?>

						<a class="clear-value" href="javascript:clear( '<?php bp_the_profile_field_input_name(); ?>' );"><?php _e( 'Clear', 'buddypress' ); ?></a>

					<?php endif; ?>

				<?php endif; ?>

				<?php if ( 'radio' == bp_get_the_profile_field_type() ) : ?>

					<div class="radio">

						<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) ); ?>

						<?php if ( !bp_get_the_profile_field_is_required() ) : ?>

							<a class="clear-value" href="javascript:clear( '<?php bp_the_profile_field_input_name(); ?>' );"><?php _e( 'Clear', 'buddypress' ); ?></a>

						<?php endif; ?>
					</div>

				<?php endif; ?>

				<?php if ( 'checkbox' == bp_get_the_profile_field_type() ) : ?>

					<div class="checkbox">

						<?php bp_the_profile_field_options(  array( 'user_id' => $user_id ) ); ?>
					</div>

				<?php endif; ?>

				<?php if ( 'datebox' == bp_get_the_profile_field_type() ) : ?>

					<div class="datebox">

						<select name="<?php bp_the_profile_field_input_name(); ?>_day" id="<?php bp_the_profile_field_input_name(); ?>_day" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>>

							<?php bp_the_profile_field_options(  array( 'type' => 'day', 'user_id' => $user_id ) ); ?>

						</select>

						<select name="<?php bp_the_profile_field_input_name(); ?>_month" id="<?php bp_the_profile_field_input_name(); ?>_month" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>>

							<?php bp_the_profile_field_options(  array( 'type' => 'month', 'user_id' => $user_id ) ); ?>

						</select>

						<select name="<?php bp_the_profile_field_input_name(); ?>_year" id="<?php bp_the_profile_field_input_name(); ?>_year" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>>

							<?php bp_the_profile_field_options( array( 'type' => 'year', 'user_id' => $user_id ) ); ?>

						</select>
					</div>

				<?php endif; ?>


				<p class="description"><?php bp_the_profile_field_description(); ?></p>
			</td>
         </tr>
		<?php endwhile; ?>	
		</table>
		 <input type="hidden" name="bp_field_ids" id="bp_field_ids" value="<?php bp_the_profile_group_field_ids(); ?>" />
		<?php endwhile; endif; ?>

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
