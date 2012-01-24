<div class='header' style='width: 750px'>
<h1><?php _e('Register or Login to purchase','membership'); ?></h1>
</div>
<div class='leftside'>
<p><?php _e('Enter your details below to create a new account.','membership'); ?></p>
<p class='error' id='reg-error'><?php _e('This is an error','membership'); ?></p>
<form id="reg-form" action="<?php echo get_permalink(); ?>" method="post">
	<div class="">
		<label><?php _e('Username','membership'); ?> <span>*</span></label>
		<input type="text" value="<?php echo esc_attr($_POST['user_login']); ?>" class="regtext" name="user_login" id='reg_user_login'>
	</div>
	<div class="">
		<label><?php _e('Email Address','membership'); ?> <span>*</span></label>
		<input type="text" value="<?php echo esc_attr($_POST['user_email']); ?>" class="regtext" name="user_email" id='reg_user_email'>
	</div>
	<div class="">
		<label><?php _e('Password','membership'); ?> <span>*</span></label>
		<input type="password" autocomplete="off" class="regtext" name="password" id='reg_password'>
	</div>

	<div class="">
		<label><?php _e('Confirm Password','membership'); ?> <span>*</span></label>
		<input type="password" autocomplete="off" class="regtext" name="password2" id='reg_password2'>
	</div>

	<?php do_action('membership_popover_extend_registration_form'); ?>

	<p><input type="submit" value="<?php _e('Register My Account &raquo;','membership'); ?>" class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" name="register"></p>
	<input type="hidden" name="action" value="validatepage1" />
	<input type="hidden" name="subscription" value="<?php echo (int) $_GET['subscription']; ?>" id='reg_subscription' />
</form>
</div>
<div class='rightside'>
<p><?php _e("Login below if you're already registered.",'membership'); ?></p>
<p class='error' id='login-error'><?php _e('This is an error','membership'); ?></p>
<form id="login-form" action="<?php echo get_permalink(); ?>" method="post">
	<div class="">
		<label><?php _e('Username','membership'); ?></label>
		<input type="text" value="<?php echo esc_attr($_POST['user_login']); ?>" class="regtext" name="user_login" id='login_user_login'>
	</div>
	<div class="">
		<label><?php _e('Password','membership'); ?></label>
		<input type="password" autocomplete="off" class="regtext" name="password" id='login_password'>
	</div>

	<?php do_action('membership_popover_extend_login_form'); ?>

	<p><input type="submit" value="<?php _e('Login &raquo;','membership'); ?>" class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" name="register"></p>
	<input type="hidden" name="action" value="loginaccount" />
	<input type="hidden" name="subscription" value="<?php echo (int) $_GET['subscription']; ?>" id='login_subscription' />
</form>
</div>