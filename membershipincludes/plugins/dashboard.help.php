<?php
// Membership dashboard - help window

function M_help_widget() {
	?>
	<div class="postbox " id="dashboard_quick_press">
		<h3 class="hndle"><span><?php _e('Quick Setup Guide','membership'); ?></span></h3>
		<div class="inside">
			<?php M_show_help_content(); ?>
		</div>
	</div>
	<?php
}

add_action( 'membership_dashboard_right_top', 'M_help_widget', 1 );

function M_show_help_content() {
	?>
		<p><?php _e('Hello, welcome to the Membership plugin. To get you up and running quickly, work your way through the simple steps below.','membership'); ?>
		</p>
		<ol>
			<li><?php _e('<strong>Plan, plan, plan</strong> - make sure you have a plan of the access levels you want to use and write them down.','membership'); ?></li>
			<li><?php _e('<strong>Create your categories</strong> - create a <a href="' . admin_url('edit-tags.php?taxonomy=category') . '">category</a> for each access level you want to use. This makes it easy to protect particular posts.','membership'); ?></li>
			<li><?php _e('<strong>Create some shortcodes</strong> - specific parts of a sites content can be protected using custom shortcodes - create an initial set in the <a href="' . admin_url('admin.php?page=membershipoptions') . '">options panel</a>. Make sure you set the shortcodes to be protected by default, and put in a helpful message (if you want one displayed) about why a non-member is not able to see the content.','membership'); ?></li>
			<li><?php _e('<strong>Create a no access page</strong> - It is important to tell a non-member why they can not see the information they arrived at your site to see. This is also the opportunity you should take to sell your site and send people to your registration and sign up page. Create a <a href="' . admin_url('edit.php?post_type=page') . '">no-access page</a>, and then select it in the membership <a href="' . admin_url('admin.php?page=membershipoptions') . '">options panel</a>.','membership'); ?></li>
			<li><?php _e('<strong>Create a registration page</strong> - The registration page allows new users to sign up for membership. Create a <a href="' . admin_url('edit.php?post_type=page') . '">registration page</a>, and then select it in the membership <a href="' . admin_url('admin.php?page=membershipoptions') . '">options panel</a>.','membership'); ?></li>
			<li><?php _e('<strong>Disable WordPress registration</strong> - The membership plugin takes over the registration process, so you need to disable the standard WordPress registration.','membership'); ?></li>
			<li><?php _e('<strong>Create your download groups</strong> - Download groups allow you to protect media uploaded you site - create some groups in the <a href="' . admin_url('admin.php?page=membershipoptions') . '">options panel</a> and be sure to set the masked url value.','membership'); ?></li>
			<li><?php _e('<strong>Create your levels</strong> - A Level controls the amount of access that a user has on your site and are the most important part of the membership system. <a href="' . admin_url('admin.php?page=membershiplevels') . '">Create</a> some levels, <a href="' . admin_url('admin.php?page=membershiplevels') . '">activate</a> them and then do not forget to set the stranger level in the <a href="' . admin_url('admin.php?page=membershipoptions') . '">options panel</a>','membership'); ?></li>
			<li><?php _e('<strong>Create your subscriptions</strong> - Subscriptions control a members flow through your system and the amount they need to pay, so <a href="' . admin_url('admin.php?page=membershipsubs') . '">create</a> some subscriptions, <a href="' . admin_url('admin.php?page=membershipsubs') . '">activate</a> them and set to <a href="' . admin_url('admin.php?page=membershipsubs') . '">"public"</a> those that you want a user to be able to sign up to.','membership'); ?></li>
			<li><?php _e('<strong>Setup your Payment gateways</strong> - <a href="' . admin_url('admin.php?page=membershipgateways') . '">Choose and activate</a> the payment gateways that you want to accept - make sure you edit any settings with your account details.','membership'); ?></li>
			<li><?php _e('<strong>Enable the Protection</strong> - Finally, scroll up to the top left of this page and click on the link labelled "Disabled" to switch on the membership protection for your site.','membership'); ?></li>
		</ol>
		<br class="clear">
	<?php
}

function M_add_help_page() {
	add_submenu_page('membership', __('Quick Start','membership'), __('Quick Start','membership'), 'membershipadmin', "membershipquickstart", 'M_show_help_page');
}

function M_show_help_page() {
	?>
	<div class='wrap nosubsub'>
		<div class="icon32" id="icon-index"><br></div>
		<h2><?php _e('Quick Start Guide','membership'); ?></h2>
	<?php
	M_show_help_content();
	?>
	</div>
	<?php
}

add_action('membership_add_menu_items_top', 'M_add_help_page', 1 );
?>