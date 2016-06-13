<?php
?>
<div id="reg-form">
	<div class="formleft">
		<h2><?php _e('Completed: Thank you for joining','membership'); ?></h2>
		<p><?php _e('It looks like you are already a member of our site. Thank you very much for your support.','membership'); ?></p>
		<p><?php _e('If you are at this page because you would like to create another account, then please log out first.','membership'); ?></p>
		<?php do_action('membership_subscription_form_member_inner_content', $user_id); ?>
	</div>
</div>
<?php
?>