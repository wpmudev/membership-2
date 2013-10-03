<?php

if(is_wp_error($error) && method_exists($error, 'get_error_code')) {
	$anyerrors = $error->get_error_code();
	if( !empty($anyerrors) ) {
		// we have an error - output
		$messages = $error->get_error_messages();
		$errormessages = "<div class='alert alert-error'>";
		$errormessages .= implode('<br/>', $messages);
		$errormessages .= "</div>";
	} else {
		$errormessages = '';
	}
} else {
	$errormessages = '';
}

?>

<div id='membership-wrapper'>
<?php
	if(!empty($errormessages)) {
		echo $errormessages;
	}
?>
<form class="form-membership" action="<?php echo add_query_arg( 'action', 'validatepage1', get_permalink() ) ?>" method="post">

	<?php do_action( "signup_hidden_fields" ); ?>

	<input type='hidden' name='subscription' value='<?php if(isset($_REQUEST['subscription'])) echo esc_attr($_REQUEST['subscription']); ?>' />

	<fieldset>
		<legend><?php _e( 'Create an Account', 'membership' ) ?></legend>

			<div class="form-element">
				<label class="control-label" for="user_login"><?php _e('Choose a Username','membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="user_login" name="user_login" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_login' ) ) ?>">
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="user_email"><?php _e('Email Address','membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="user_email" name="user_email" value="<?php echo esc_attr( filter_input( INPUT_POST, 'user_email' ) ) ?>">
				</div>

				<p class="help-block"><?php _e('Please enter a new password, and then verify your new password by entering it again.','membership'); ?></p>
			</div>
			<div class="form-element">
				<label class="control-label" for="user_email"><?php _e('Password','membership'); ?></label>
				<div class="element">
					<input type="password" class="input-xlarge" id="password" name="password" placeholder="" autocomplete="off">
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="user_email"><?php _e('Confirm Password','membership'); ?></label>
				<div class="element">
					<input type="password" class="input-xlarge" id="password2" name="password2" placeholder="" autocomplete="off">
				</div>

				<p class="help-block"><?php _e('Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).','membership'); ?></p>
			</div>

		<?php
			do_action('membership_subscription_form_registration_presubmit_content');

			do_action( 'signup_extra_fields', $error );
		?>

		<p><input type="submit" value="<?php _e('Register My Account &raquo;','membership'); ?>" class="alignright button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" name="register"></p>

		<a title="Login »" href="<?php echo wp_login_url( add_query_arg( array('action' => 'registeruser', 'subscription' => $_REQUEST['subscription']), get_permalink()) ); ?>" class="alignleft" id="login_right"><?php _e('Already have a user account?' ,'membership'); ?></a>


		</fieldset>
</form>

</div>