<?php
?>
<h2><?php _e( 'Create an Account', 'membership' ) ?></h2>

<form id="reg-form" action="<?php echo get_permalink(); ?>" method="post">

	<?php do_action( "signup_hidden_fields" ); ?>

	<input type='hidden' name='subscription' value='<?php echo esc_attr($_REQUEST['subscription']); ?>' />

	<div class="formleft">

		<?php
			if(!empty($errormessages)) {
				echo $errormessages;
			}
		?>

		<a title="Login »" href="<?php echo wp_login_url( add_query_arg('action', 'page2', get_permalink()) ); ?>" class="alignright" id="login_right"><?php _e('Already have a user account?' ,'membership'); ?></a>

		<p><label><?php _e('Choose a Username','membership'); ?> <span>*</span></label>
		<input type="text" value="<?php echo esc_attr($_POST['user_login']); ?>" class="regtext" name="user_login"></p>

		<div class="clear">
			<label><?php _e('Email Address','membership'); ?> <span>*</span></label>
			<input type="text" value="<?php echo esc_attr($_POST['user_email']); ?>" class="regtext" name="user_email">
		</div>

		<div class="alignleft">
			<label><?php _e('Password','membership'); ?> <span>*</span></label>
			<input type="password" autocomplete="off" class="regtext" name="password">
		</div>

		<div class="alignleft">
			<label><?php _e('Confirm Password','membership'); ?> <span>*</span></label>
			<input type="password" autocomplete="off" class="regtext" name="password2">
		</div>

		<p class="pass_hint"><?php _e('Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).','membership'); ?></p>

		<?php
			do_action('membership_subscription_form_registration_presubmit_content');

			do_action( 'signup_extra_fields', $errors );
		?>

		<p><input type="submit" value="<?php _e('Register My Account &raquo;','membership'); ?>" class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" name="register"></p>
		<input type="hidden" name="action" value="validatepage1" />
	</div>
</form>
<?php
?>