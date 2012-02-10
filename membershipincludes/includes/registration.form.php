<?php
?>

<div id='membership-wrapper'>
<?php
	if(!empty($errormessages)) {
		echo $errormessages;
	}
?>
<form class="form-membership" action="<?php echo get_permalink(); ?>" method="post">

	<?php do_action( "signup_hidden_fields" ); ?>

	<input type='hidden' name='subscription' value='<?php echo esc_attr($_REQUEST['subscription']); ?>' />

	<fieldset>
		<legend><?php _e( 'Create an Account', 'membership' ) ?></legend>

			<div class="form-element">
				<label class="control-label" for="user_login"><?php _e('Choose a Username','membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="user_login" placeholder="<?php _e('Username','membership'); ?>" value="<?php echo esc_attr($_POST['user_login']); ?>">
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="user_email"><?php _e('Email Address','membership'); ?></label>
				<div class="element">
					<input type="text" class="input-xlarge" id="user_email" placeholder="<?php _e('Email Address','membership'); ?>" value="<?php echo esc_attr($_POST['user_email']); ?>">
				</div>

				<p class="help-block"><?php _e('Please enter a new password, and then verify your new password by entering it again.','membership'); ?></p>
			</div>
			<div class="form-element">
				<label class="control-label" for="user_email"><?php _e('Password','membership'); ?></label>
				<div class="element">
					<input type="password" class="input-xlarge" id="user_email" placeholder="<?php _e('Password','membership'); ?>" autocomplete="off">
				</div>
			</div>

			<div class="form-element">
				<label class="control-label" for="user_email"><?php _e('Confirm Password','membership'); ?></label>
				<div class="element">
					<input type="password" class="input-xlarge" id="user_email" placeholder="<?php _e('Confirm Password','membership'); ?>" autocomplete="off">
				</div>

				<p class="help-block"><?php _e('Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).','membership'); ?></p>
			</div>

		<?php
			do_action('membership_subscription_form_registration_presubmit_content');

			do_action( 'signup_extra_fields', $errors );
		?>

		<p><input type="submit" value="<?php _e('Register My Account &raquo;','membership'); ?>" class="alignright button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" name="register"></p>
		<input type="hidden" name="action" value="validatepage1" />

		<a title="Login »" href="<?php echo wp_login_url( add_query_arg('action', 'page2', get_permalink()) ); ?>" class="alignleft" id="login_right"><?php _e('Already have a user account?' ,'membership'); ?></a>


		</fieldset>
</form>

</div>
<?php
?>