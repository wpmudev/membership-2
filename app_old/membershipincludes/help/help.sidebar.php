<h4><?php _e('Membership','membership'); ?></h4>
<?php
if ( !defined('WPMUDEV_REMOVE_BRANDING') ) {
	echo '<ul>' .
		'<li><a href="http://premium.wpmudev.org/project/membership" target="_blank">' . __('Project page', 'membership') . '</a></li>' .
		'<li><a href="http://premium.wpmudev.org/project/membership/installation/" target="_blank">' . __('Installation and instructions page', 'membership') . '</a></li>' .
		'<li><a href="http://premium.wpmudev.org/forums/tags/membership" target="_blank">' . __('Support forum', 'membership') . '</a></li>' .
		'<li><a href="' . wp_nonce_url('admin.php?page=membership&amp;restarttutorial=yes', 'restarttutorial') . '">' . __('Restart the Tutorial', 'membership') . '</a></li>' .
	'</ul>';
	?>
	<?php
} else {
	echo "<p>" . __('The most powerful, easy to use and flexible membership plugin for WordPress, Multisite and BuddyPress sites available. Offer downloads, posts, pages, forums and more to paid members.', 'membership') . "</p>";
	echo '<ul>' .
		'<li><a href="' . wp_nonce_url('admin.php?page=membership&amp;restarttutorial=yes', 'restarttutorial') . '">' . __('Restart the Tutorial', 'membership') . '</a></li>' .
	'</ul>';
	?>
	<?php
}
?>
