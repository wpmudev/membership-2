<?php
?>
<form id="reg-form" action="<?php echo get_permalink(); ?>" method="post">
	<div class="formleft">
		<h2><?php _e('Step 1. Create a New Account','membership'); ?></h2>
		<a title="Login »" href="<?php echo wp_login_url( add_query_arg('action', 'page2', get_permalink()) ); ?>" class="alignright" id="login_right"><?php _e('Already have a user account?' ,'membership'); ?></a>

		<p><label><?php _e('Choose a Username','membership'); ?> <span>*</span></label>
		<input type="text" value="<?php echo esc_attr($_POST['user_login']); ?>" class="regtext" name="user_login"></p>

		<div class="alignleft">
			<label><?php _e('Email Address','membership'); ?> <span>*</span></label>
			<input type="text" value="<?php echo esc_attr($_POST['user_email']); ?>" class="regtext" name="user_email">
		</div>

		<div class="alignleft">
			<label><?php _e('Confirm Email Address','membership'); ?> <span>*</span></label>
			<input type="text" value="<?php echo esc_attr($_POST['user_email2']); ?>" class="regtext" name="user_email2">
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

			if(function_exists('get_site_option')) {
				$terms = get_site_option('signup_tos_data');
			} else {
				$terms = '';
			}

			if(!empty($terms)) {
				?>
				<h2><?php _e('Terms and Conditions','membership'); ?></h2>
				<div id="reg_tos">
				<?php echo stripslashes($terms); ?>
				</div>
				<p><label><input type="checkbox" value="1" name="tosagree">
				<strong><?php _e('I agree to the Terms of Use','membership'); ?></strong></label></p>
				<?php
			}
		?>

		<p><input type="submit" value="<?php _e('Register My Account &raquo;','membership'); ?>" class="regbutton" name="register"></p>
		<input type="hidden" name="action" value="validatepage1" />
	</div>
</form>
<?php
?>