<?php
if(!class_exists('M_Wizard')) {

	class M_Wizard {

		function __construct( ) {

		}

		function M_Wizard(  ) {
			$this->__construct();
		}

		function wizard_visible() {
			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					if(function_exists('switch_to_blog')) {
						switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
					}
					$wizard_visible = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_wizard_visible', true);
					if(function_exists('restore_current_blog')) {
						restore_current_blog();
					}
				} else {
					$wizard_visible = get_option('membership_wizard_visible', true);
				}
			} else {
				$wizard_visible = get_option('membership_wizard_visible', true);
			}

			return $wizard_visible;
		}

		function hide_wizard() {
			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('update_blog_option')) {
					update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_wizard_visible', false);
				} else {
					update_option('membership_wizard_visible', false);
				}
			} else {
				update_option('membership_wizard_visible', false);
			}
		}

		function conditional_show() {

			if($this->wizard_visible()) {
				$this->show_with_wrap("Hello");
			}

		}

		function show_with_wrap( $content ) {
			?>
				<div class="welcome-panel" id="welcome-panel">
					<input type="hidden" value="ab89e0eb4e" name="membershippanelnonce" id="membershippanelnonce">	<a href="http://dev.site/wp-admin/?welcome=0" class="welcome-panel-close">Dismiss</a>

					<div class="welcome-panel-content">
					<h3><?php _e('Welcome to Membership', 'membership'); ?></h3>
					<p class="about-description">If you need help getting started, check out our documentation on <a href="http://codex.wordpress.org/First_Steps_With_WordPress">First Steps with WordPress</a>. If you’d rather dive right in, here are a few things most people do first when they set up a new WordPress site. If you need help, use the Help tabs in the upper right corner to get information on how to use your current screen and where to go for more assistance.</p>
					<?php
					/*
					?>
					<div class="welcome-panel-column-container">
					<?php
					/*
					?>
						<div class="welcome-panel-column">
							<h4><span class="icon16 icon-settings"></span> Basic Settings</h4>
							<p>Here are a few easy things you can do to get your feet wet. Make sure to click Save on each Settings screen.</p>
							<ul>
							<li><a href="http://dev.site/wp-admin/options-privacy.php">Choose your privacy setting</a></li>
							<li><a href="http://dev.site/wp-admin/options-general.php">Select your tagline and time zone</a></li>
							<li><a href="http://dev.site/wp-admin/options-discussion.php">Turn comments on or off</a></li>
							<li><a href="http://dev.site/wp-admin/profile.php">Fill in your profile</a></li>
							</ul>
						</div>
						<div class="welcome-panel-column">
							<h4><span class="icon16 icon-page"></span> Add Real Content</h4>
							<p>Check out the sample page &amp; post editors to see how it all works, then delete the default content and write your own!</p>
							<ul>
							<li>View the <a href="http://dev.site/sample-page/">sample page</a> and <a href="http://dev.site/2011/11/30/hello-world/">post</a></li>
							<li>Delete the <a href="http://dev.site/wp-admin/edit.php?post_type=page">sample page</a> and <a href="http://dev.site/wp-admin/edit.php">post</a></li>
							<li><a href="http://dev.site/wp-admin/edit.php?post_type=page">Create an About Me page</a></li>
							<li><a href="http://dev.site/wp-admin/post-new.php">Write your first post</a></li>
							</ul>
						</div>
						<div class="welcome-panel-column welcome-panel-last">
							<h4><span class="icon16 icon-appearance"></span> Customize Your Site</h4>
							<p>Use the current theme &mdash; Twenty Eleven &mdash; or <a href="http://dev.site/wp-admin/themes.php">choose a new one</a>. If you stick with Twenty Eleven, here are a few ways to make your site look unique.</p>			<ul>
													<li><a href="http://dev.site/wp-admin/themes.php?page=theme_options">Choose light or dark</a></li>
													<li><a href="http://dev.site/wp-admin/themes.php?page=custom-background">Set a background color</a></li>
													<li><a href="http://dev.site/wp-admin/themes.php?page=custom-header">Select a new header image</a></li>
													<li><a href="http://dev.site/wp-admin/widgets.php">Add some widgets</a></li>
												</ul>
									</div>
					</div>

					<?php
					*/
					?>

					<p class="welcome-panel-dismiss">Already know what you’re doing? <a href="http://dev.site/wp-admin/?welcome=0">Dismiss this message</a>.</p>
					</div>

					</div>
					<?php
		}

	}

}
?>