<?php
if(!class_exists('M_Wizard')) {

	class M_Wizard {

		function __construct( ) {
			// if logged in:
			add_action( 'wp_ajax_processwizard', array(&$this, 'ajax_process_wizard') );
		}

		function M_Wizard(  ) {
			$this->__construct();
		}



		function ajax_process_wizard() {

			switch( $_POST['from']) {
				case 'stepone':		if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {
										switch($_POST['option']) {
											case 'normal':		echo $this->show_normal_wizard_step(wp_nonce_url("admin.php?page=" . $page. "&amp;step=2", 'step-two'));
																break;

											case 'dripped':		echo $this->show_dripped_wizard_step(wp_nonce_url("admin.php?page=" . $page. "&amp;step=2", 'step-two'));
																break;

											case 'advanced':	// Skips wizard and goes to pointer tutorial
																$this->hide_wizard();
																echo "clear";
																break;
										}
									}
									break;

				case 'steptwo':		if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {

									}
									break;
			}
			exit;
		}

		function process_visibility() {

			if(isset($_GET['action']) && $_GET['action'] == 'deactivatewelcome') {

				check_admin_referer('deactivate-welcome');

				$this->hide_wizard();
			}

		}

		function wizard_visible() {
			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					if(function_exists('switch_to_blog')) {
						switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
					}
					$wizard_visible = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_wizard_visible', 'yes');
					if(function_exists('restore_current_blog')) {
						restore_current_blog();
					}
				} else {
					$wizard_visible = get_option('membership_wizard_visible', 'yes');
				}
			} else {
				$wizard_visible = get_option('membership_wizard_visible', 'yes');
			}

			return $wizard_visible;
		}

		function hide_wizard() {
			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('update_blog_option')) {
					update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_wizard_visible', 'no');
				} else {
					update_option('membership_wizard_visible', 'no');
				}
			} else {
				update_option('membership_wizard_visible', 'no');
			}
		}

		function conditional_show() {

			global $page, $action, $step;

			wp_reset_vars( array('action', 'page', 'step') );

			// process any deactivate calls
			$this->process_visibility();

			// carry on and see if we should display the wizard and then what we should display
			if($this->wizard_visible() != 'no') {

				$current_step = (int) $_GET['step'];
				if(empty($current_step)) $current_step = 1;

				switch($current_step) {

					case 1:		$step2 = wp_nonce_url("admin.php?page=" . $page. "&amp;step=2", 'step-two');
								$this->show_with_wrap( $this->page_one( $step2 ) );
								break;

					case 2:		if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {
									switch($_POST['option']) {
										case 'normal':		echo $this->show_normal_wizard_step();
															break;

										case 'dripped':		echo $this->show_dripped_wizard_step();
															break;

										case 'advanced':	// Skips wizard and goes to pointer tutorial
															$this->hide_wizard();
															echo "clear";
															break;
									}
								}
								break;



				}


			}

		}

		function show_with_wrap( $content ) {

			global $page, $action, $step;

			$deactivateurl = wp_nonce_url("admin.php?page=" . $page. "&amp;action=deactivatewelcome", 'deactivate-welcome');
			?>
				<div class="welcome-panel" id="welcome-panel">
					<a href="<?php echo $deactivateurl;  ?>" class="welcome-panel-close">Dismiss</a>

					<div class="welcome-panel-content">
					<?php
						echo $content;
					?>
					<p class="welcome-panel-dismiss"><?php _e('Already know what you’re doing?', 'membership'); ?> <a href="<?php echo $deactivateurl;  ?>"><?php _e('Dismiss this message', 'membership'); ?></a>.</p>
					</div>

					</div>
					<?php
		}

		function page_one( $nextsteplink = false ) {

			ob_start();
			?>
				<h3><?php _e('Welcome to Membership', 'membership'); ?></h3>
				<p class="about-description">
					<?php
						if ( !defined('WPMUDEV_REMOVE_BRANDING') ) {
							_e('If you need help getting started, check out our documentation over on <a href="http://premium.wpmudev.org/project/membership">WPMUDEV</a>. ','membership');
						}
						_e('You can use the Help tabs in the upper right corner to get information on how to use your current screen. ','membership');
						_e('If you would like us to set up some basic things for you then choose an option below.','membership');
					?>
				</p>
				<p class="about-description createsteps">
					<ul class='wizardoptions'>
						<li><input type='radio' name='wizardtype' value='normal' checked='checked' />&nbsp;<?php _e('Standard membership site.','membership'); ?></li>
						<li><input type='radio' name='wizardtype' value='dripped' />&nbsp;<?php _e('Dripped content site.','membership'); ?></li>
						<li><input type='radio' name='wizardtype' value='advanced' />&nbsp;<?php _e('Advanced.','membership'); ?></li>
					</ul>
				</p>
				<p class="about-description">
					<?php if($nextsteplink) { ?>
					<a href='<?php echo $nextsteplink; ?>' class='button-primary alignright' id='wizardsteponebutton'><?php _e('Next Step &raquo;', 'membership'); ?></a>
					<?php } ?>
				</p>

			<?php
			return ob_get_clean();
		}

		function show_normal_wizard_step( $nextsteplink = false ) {

			ob_start();
			?>
				<h3><?php _e('Create your levels', 'membership'); ?></h3>
				<p class="about-description">
					<?php
						_e('A level controls what parts of your website a user has access to, so we will need to set some initial ones up. ','membership');
						_e('Select the number of levels you think you will need to get started (you can add or remove them later).','membership');
					?>
				</p>
				<p class="about-description createsteps">
					<?php _e('Create ','membership'); ?>
					<select name='numberoflevels'>
					<?php
						for($n=1; $n <= 99; $n++) {
							if($n == 2) {
								?>
									<option value='<?php echo $n; ?>' selected='selected'><?php echo $n; ?></option>
								<?php
							} else {
								?>
									<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
								<?php
							}
						}
					?>
					</select>
					<?php _e(' levels.','membership'); ?>
					<br/><br/>
					<input type='checkbox' name='creatavisitorlevel' value='yes' checked='checked' />&nbsp;<?php _e('Create a stranger level?', 'membership'); ?>
				</p>
				<p class="about-description">
					<?php if($nextsteplink) { ?>
					<a href='<?php echo $nextsteplink; ?>' class='button-primary alignright' id='wizardsteponebutton'><?php _e('Next Step &raquo;', 'membership'); ?></a>
					<?php } ?>
				</p>

			<?php
			return ob_get_clean();

		}

		function show_dripped_wizard_step( $nextsteplink = false ) {

			ob_start();
			?>
				<h3><?php _e('Create your levels', 'membership'); ?></h3>
				<p class="about-description">
					<?php
						_e('A level controls what parts of your website a user has access to, so we will need to set some initial ones up. ','membership');
						_e('Select the number of levels you think you will need to get started (you can add or remove them later).','membership');
					?>
					<br/><br/>
					<?php _e('Create ','membership'); ?>
					<select name='numberoflevels'>
					<?php
						for($n=1; $n <= 99; $n++) {
							?>
								<option value='<?php echo $n; ?>'><?php echo $n; ?></option>
							<?php
						}
					?>
					</select>
					<?php _e(' levels.','membership'); ?>
					<br/>
					<?php if($nextsteplink) { ?>
					<a href='<?php echo $nextsteplink; ?>' class='button-primary alignright' id='wizardsteponebutton'><?php _e('Next Step &raquo;', 'membership'); ?></a>
					<?php } ?>
				</p>

			<?php
			return ob_get_clean();

		}

		function create_step_two_pages() {

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					if(function_exists('switch_to_blog')) {
						switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
					}

					$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
				} else {
					$M_options = get_option('membership_options', array());
				}
			} else {
				$M_options = get_option('membership_options', array());
			}

			$pagedetails = array('post_title' => __('Register', 'membership'), 'post_name' => 'register', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
			$id = wp_insert_post( $pagedetails );
			$M_options['registration_page'] = $id;

			$pagedetails = array('post_title' => __('Account', 'membership'), 'post_name' => 'account', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '');
			$id = wp_insert_post( $pagedetails );
			$M_options['account_page'] = $id;

			$content = '<p>' . __('The content you are trying to access is only available to members. Sorry.','membership') . '</p>';
			$pagedetails = array('post_title' => __('Protected Content', 'membership'), 'post_name' => 'protected', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $content);
			$id = wp_insert_post( $pagedetails );
			$M_options['nocontent_page'] = $id;

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('update_blog_option')) {
					update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', $M_options);
				} else {
					update_option('membership_options', $M_options);
				}
			} else {
				update_option('membership_options', $M_options);
			}


		}

		function page_two( $nextsteplink = false ) {

			global $M_options, $page, $action, $step;

			ob_start();
			?>
				<h3><?php _e('Create some pages', 'membership'); ?></h3>
				<p class="about-description">
					<?php
						if(empty($M_options['registration_page'])) {
							// No pages set up - ask if they want any set up
								$step3 = wp_nonce_url("admin.php?page=" . $page. "&amp;step=3", 'step-three');
								$step3skip = wp_nonce_url("admin.php?page=" . $page. "&amp;step=3&amp;skip=yes", 'step-three');

								_e('You need to create some pages for Membership to use for the registration and account forms and the no access message. ','membership');
								_e('If you want to create these yourself later then click on the <strong>Skip to next step</strong> button, or click the <strong>Create Pages</strong> button below to have them created now. ','membership');
								?>
								<br/>
								<a href='<?php echo $step3; ?>' class='button-primary alignright'><?php _e('Create Pages &raquo;', 'membership'); ?></a>
								<a href='<?php echo $step3skip; ?>' class='button alignright' style='margin-right: 10px;'><?php _e('Skip to next step &raquo;', 'membership'); ?></a>
								<?php
						} else {
							// We have pages set up so display message ready for next page
							$step3skip = wp_nonce_url("admin.php?page=" . $page. "&amp;step=3&amp;skip=yes", 'step-three');

							_e('It looks like you have already created some pages for your Membership system. If you need to change them then you can do so in the <a href="admin.php?page=membershipoptions&tab=pages">options page</a>. ','membership');
							_e('Click on the <strong>Next Step</strong> button to carry on.','membership');
							?>
								<br/>
								<a href='<?php echo $step3skip; ?>' class='button-primary alignright'><?php _e('Next Step &raquo;', 'membership'); ?></a>
								<?php
						}
					?>

				</p>

			<?php
			return ob_get_clean();
		}

		function activate_buddypress_addon() {

			do_action( 'membership_activate_addon', 'default.bprules.php' );

		}

		function page_three( $nextsteplink = false ) {

			global $M_options, $page, $action, $step;

			ob_start();
			?>
				<h3><?php _e('Enable BuddyPress rules', 'membership'); ?></h3>
				<p class="about-description">
					<?php

						$step4 = wp_nonce_url("admin.php?page=" . $page. "&amp;step=4", 'step-four');
						$step4skip = wp_nonce_url("admin.php?page=" . $page. "&amp;step=4&amp;skip=yes", 'step-four');

						_e('It looks like you have BuddyPress enabled. Did you know that Membership has extra rules for use with BuddyPress? ','membership');
						_e('If you would like to enable the BuddyPress then click on the <strong>Activate BuddyPress Rules</strong> button. If you would rather do this yourself later then click on the <strong>Skip to next step</strong> button. ','membership');

						?>
						<br/>
						<a href='<?php echo $step4; ?>' class='button-primary alignright'><?php _e('Activate BuddyPress Rules &raquo;', 'membership'); ?></a>
						<a href='<?php echo $step4skip; ?>' class='button alignright' style='margin-right: 10px;'><?php _e('Skip to next step &raquo;', 'membership'); ?></a>

				</p>

			<?php
			return ob_get_clean();
		}

		function activate_marketpress_addon() {

			do_action( 'membership_activate_addon', 'marketpress.rules.php' );

		}

		function page_four( $nextsteplink = false ) {

			global $M_options, $page, $action, $step;

			ob_start();
			?>
				<h3><?php _e('Enable MarketPress rules', 'membership'); ?></h3>
				<p class="about-description">
					<?php

						$step5 = wp_nonce_url("admin.php?page=" . $page. "&amp;step=5", 'step-five');
						$step5skip = wp_nonce_url("admin.php?page=" . $page. "&amp;step=5&amp;skip=yes", 'step-five');

						_e('It looks like you have MarketPress enabled. Did you know that Membership has extra rules for use with MarketPress? ','membership');
						_e('If you would like to enable the MarketPress then click on the <strong>Activate MarketPress Rules</strong> button. If you would rather do this yourself later then click on the <strong>Skip to next step</strong> button. ','membership');

						?>
						<br/>
						<a href='<?php echo $step5; ?>' class='button-primary alignright'><?php _e('Activate MarketPress Rules &raquo;', 'membership'); ?></a>
						<a href='<?php echo $step5skip; ?>' class='button alignright' style='margin-right: 10px;'><?php _e('Skip to next step &raquo;', 'membership'); ?></a>

				</p>

			<?php
			return ob_get_clean();
		}

		function activate_admin_shortcodes() {

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('get_blog_option')) {
					if(function_exists('switch_to_blog')) {
						switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
					}

					$M_options = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', array());
				} else {
					$M_options = get_option('membership_options', array());
				}
			} else {
				$M_options = get_option('membership_options', array());
			}

			if(!is_array($M_options['membershipadminshortcodes'])) {
				$M_options['membershipadminshortcodes'] = array();
			}

			if(class_exists('RGForms')) {
				// Gravity Forms exists
				$M_options['membershipadminshortcodes'][] = 'gravityform';
			}

			if(defined('WPCF7_VERSION')) {
				// Contact Form 7 exists
				$M_options['membershipadminshortcodes'][] = 'contact-form';
			}

			if(defined('WPAUDIO_URL')) {
				// WPAudio exists
				$M_options['membershipadminshortcodes'][] = 'wpaudio';
			}

			if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
				if(function_exists('update_blog_option')) {
					update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_options', $M_options);
				} else {
					update_option('membership_options', $M_options);
				}
			} else {
				update_option('membership_options', $M_options);
			}

		}

		function page_five( $nextsteplink = false ) {

			global $M_options, $page, $action, $step;

			ob_start();
			?>
				<h3><?php _e('Plugin shortcodes', 'membership'); ?></h3>
				<p class="about-description">
					<?php

						$step6 = wp_nonce_url("admin.php?page=" . $page. "&amp;step=6", 'step-six');
						$step6skip = wp_nonce_url("admin.php?page=" . $page. "&amp;step=6&amp;skip=yes", 'step-six');

						_e('Some plugins, such as GravityForms and WP Audio create shortcodes for their content in a way that Membership can not protect. ','membership');
						_e('If you would like Membership to check for these plugins and set up special shortcodes for them then click on the <strong>Create Plugin Shortcodes</strong> button. If you would rather do this yourself later then you can use the <a href="admin.php?page=membershipoptions&tab=posts">Options area</a> settings and click on the <strong>Skip to next step</strong> button for now. ','membership');

						?>
						<br/>
						<a href='<?php echo $step6; ?>' class='button-primary alignright'><?php _e('Create Plugin Shortcodes &raquo;', 'membership'); ?></a>
						<a href='<?php echo $step6skip; ?>' class='button alignright' style='margin-right: 10px;'><?php _e('Skip to next step &raquo;', 'membership'); ?></a>

				</p>

			<?php
			return ob_get_clean();
		}

		function page_end( $nextsteplink = false ) {

			global $M_options, $page, $action, $step;

			ob_start();
			?>
				<h3><?php _e('Thank you', 'membership'); ?></h3>
				<p class="about-description">
					<?php

						$deactivateurl = wp_nonce_url("admin.php?page=" . $page. "&amp;action=deactivatewelcome", 'deactivate-welcome');

						_e('Thank you, we have now set up some of the initial Membership options. ','membership');
						_e('You can now carry on and set up your levels and subscriptions and get ready for your visitors. ','membership');

						?>
						<br/>
						<a href='<?php echo $deactivateurl; ?>' class='button-primary alignright'><?php _e('Finish', 'membership'); ?></a>
				</p>

			<?php
			return ob_get_clean();
		}

		function temp_show_with_wrap( $content ) {
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