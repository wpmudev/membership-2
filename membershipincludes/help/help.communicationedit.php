<h2><?php _e('Adding / Editing a message', 'membership'); ?></h2>
<h3><?php _e('Message to be sent', 'membership'); ?></h3>
<p><?php _e('The first field in the message add / edit screen is the time period in the lifetime of a membership that the message should be sent. You can set the message to be sent x number of days / months or even years after a member has joined, or before a members subscription is due to expire. You can also choose to send the message immediately on signup (same behaviour as setting 0 days and \'after subscription is paid\') OR send the message immediately on expiry (same behaviour as setting 0 days and \'before a subscription expires\')', 'membership'); ?></p>
<h3><?php _e('Message subject', 'membership'); ?></h3>
<p><?php _e('The message subject is simply the subject that is set on the email for this message.', 'membership'); ?></p>
<h3><?php _e('Message', 'membership'); ?></h3>
<p><?php _e('The message is the main content of the email to be sent. It can contain a number of tags that will be automatically filled in before the email is sent. This allows the message to be personalised to a certain degree. The available tags are:', 'membership'); ?></p>
<ul>
<li><strong>%blogname%</strong> - <?php _e('Displays your Blog Name.', 'membership'); ?></li>
<li><strong>%blogurl%</strong> - <?php _e('Displays your Blog URL.', 'membership'); ?></li>
<li><strong>%username%</strong> - <?php _e('Displays the Username of the member.', 'membership'); ?></li>
<li><strong>%usernicename%</strong> - <?php _e('Displays their chosen Nice Name.', 'membership'); ?></li>
<li><strong>%networkname%</strong> - <?php _e('Displays your Network Name (Multisite Install).', 'membership'); ?></li>
<li><strong>%networkurl%</strong> - <?php _e('Displays the Network URL (Multisite Install).', 'membership'); ?></li>
<li><strong>%subscriptionname%</strong> - <?php _e('Displays the Subscription Name they are currently on.', 'membership'); ?></li>
<li><strong>%levelname%</strong> - <?php _e('Displays the Level Name they are currently on.', 'membership'); ?></li>
<li><strong>%accounturl%</strong> - <?php _e('Displays their Account URL.', 'membership'); ?></li>
</ul>