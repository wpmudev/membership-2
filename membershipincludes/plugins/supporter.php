<?php

add_action( 'membership_subscription_form_after_levels', 'supporter_membership_subscription_settings' );
add_action( 'membership_subscription_update', 'supporter_membership_subscription_update');
add_action( 'membership_subscription_add', 'supporter_membership_subscription_update');

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

?>