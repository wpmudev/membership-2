<?php
if(!class_exists('M_Wizard')) {

	class M_Wizard {

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups', 'ping_history', 'pings');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $membermeta;
		var $communications;
		var $urlgroups;
		var $ping_history;
		var $pings;

		function __construct( ) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			// if logged in:
			add_action( 'wp_ajax_processwizard', array(&$this, 'ajax_process_wizard') );
			add_action( 'admin_init', array(&$this, 'process_visibility') );
		}

		function M_Wizard(  ) {
			$this->__construct();
		}

		function ajax_process_wizard() {

			global $page, $action, $step;

			wp_reset_vars( array('action', 'page', 'step') );

			switch( $_POST['from']) {
				case 'stepone':		if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {
										switch($_POST['wizardtype']) {
											case 'normal':		echo $this->show_normal_wizard_step( "admin.php?page=membership&amp;step=3" );
																break;

											case 'dripped':		echo $this->show_dripped_wizard_step( "admin.php?page=membership&amp;step=3" );
																break;

											case 'advanced':	// Skips wizard and goes to pointer tutorial
																$this->hide_wizard();
																echo "clear";
																break;
										}
									}
									break;

				case 'steptwo':
									if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {
										switch($_POST['wizardtype']) {
											case 'normal':		echo $this->process_normal_wizard_step();
																break;

											case 'dripped':		echo $this->process_dripped_wizard_step();
																break;
										}
										// Show the thank you message
										echo $this->page_end();
									}
									break;
			}
			exit;
		}

		function process_visibility() {

			if(isset($_GET['action']) && $_GET['action'] == 'deactivatewelcome') {

				check_admin_referer('deactivate-welcome');

				$this->hide_wizard();

				wp_safe_redirect( remove_query_arg( 'action', remove_query_arg( '_wpnonce') ) );
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

			// carry on and see if we should display the wizard and then what we should display
			if($this->wizard_visible() != 'no') {

				$current_step = (int) $_GET['step'];
				if(empty($current_step)) $current_step = 1;

				switch($current_step) {

					case 1:		$step2 = "admin.php?page=" . $page. "&amp;step=2";
								$this->show_with_wrap( $this->page_one( $step2 ) );
								break;

					case 2:		if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {
									switch($_POST['wizardtype']) {
										case 'normal':		$step3 = "admin.php?page=membership&amp;step=3";
															echo $this->show_normal_wizard_step( $step3 );
															break;

										case 'dripped':		$step3 = "admin.php?page=membership&amp;step=3";
															echo $this->show_dripped_wizard_step( $step3 );
															break;

										case 'advanced':	// Skips wizard and goes to pointer tutorial
															$this->hide_wizard();
															echo "";
															break;
									}
								}
								break;

					case 3:		// Do the processing and then show an end message
								if(wp_verify_nonce( $_POST['nonce'], 'membership_wizard' )) {
									switch($_POST['wizardtype']) {
										case 'normal':		echo $this->process_normal_wizard_step();
															break;

										case 'dripped':		echo $this->process_dripped_wizard_step();
															break;
									}
									// Show the thank you message
									echo $this->page_end();
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
					<p class="welcome-panel-dismiss"><?php _e('Already know what you\'re doing?', 'membership'); ?> <a href="<?php echo $deactivateurl;  ?>"><?php _e('Dismiss this message', 'membership'); ?></a>.</p>
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
				<form action='<?php echo $nextsteplink; ?>' method='post' name='wizardform' id='wizardform'>
					<input type='hidden' name='action' value='processwizard' />
					<input type='hidden' name='from' value='stepone' />
					<input type='hidden' name='nonce' value='<?php echo wp_create_nonce('membership_wizard'); ?>' />
					<ul class='wizardoptions'>
						<li><input type='radio' name='wizardtype' value='normal' checked='checked' />&nbsp;<?php _e('Standard membership site.','membership'); ?></li>
						<li><input type='radio' name='wizardtype' value='dripped' />&nbsp;<?php _e('Dripped content site.','membership'); ?></li>
						<li><input type='radio' name='wizardtype' value='advanced' />&nbsp;<?php _e('Advanced.','membership'); ?></li>
					</ul>
					<p class="about-description">
						<input type='submit' name='submit' class='button-primary alignright' value='<?php _e('Next Step &raquo;', 'membership'); ?>' />
					</p>
				</form>

			<?php
			return ob_get_clean();
		}

		function show_normal_wizard_step( $nextsteplink = false ) {

			global $page, $action, $step;

			$deactivateurl = wp_nonce_url("admin.php?page=" . $page. "&amp;action=deactivatewelcome", 'deactivate-welcome');
			ob_start();
			?>
				<h3><?php _e('Create your levels', 'membership'); ?></h3>
				<p class="about-description">
					<?php
						_e('A level controls what parts of your website a user has access to, so we will need to set some initial ones up. ','membership');
						_e('Select the number of levels you think you will need to get started (you can add or remove them later).','membership');
					?>
				</p>
				<form action='<?php echo $nextsteplink; ?>' method='post' name='wizardform' id='wizardform'>
					<input type='hidden' name='action' value='processwizard' />
					<input type='hidden' name='from' value='steptwo' />
					<input type='hidden' name='nonce' value='<?php echo wp_create_nonce('membership_wizard'); ?>' />
					<input type='hidden' name='wizardtype' value='normal' />
					<p class="about-description createsteps">
					<?php _e('Create ','membership'); ?>
					<select name='numberoflevels' id='wizardnumberoflevels'>
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
					<?php _e(' levels and give them the following names:','membership'); ?>
					</p>
						<ul class='wizardlevelnames'>
							<li><input type='text' name='levelname[]' placeholder='<?php _e('Level 1', 'membership'); ?>' class='wizardlevelname' /></li>
							<li><input type='text' name='levelname[]' placeholder='<?php _e('Level 2', 'membership'); ?>' class='wizardlevelname' /></li>
						</ul>
					<p class="about-description createsteps">
					<input type='checkbox' name='creatavisitorlevel' value='yes' checked='checked' />&nbsp;<?php _e('also create a level to control what non-members can see?', 'membership'); ?>
					<br/><br/>
					<?php _e('Finally, I would like to use the ','membership'); ?>
					<select name='wizardgateway' >
						<option value=''><?php _e('Select a gateway...', 'membership'); ?></option>
						<?php 	$gateways = get_membership_gateways();
								if(!empty($gateways)) {
									foreach($gateways as $key => $gateway) {
										$default_headers = array(
											                'Name' => 'Addon Name',
															'Author' => 'Author',
															'Description'	=>	'Description',
															'AuthorURI' => 'Author URI',
															'gateway_id' => 'Gateway ID'
											        );

										$gateway_data = get_file_data( membership_dir('membershipincludes/gateways/' . $gateway), $default_headers, 'plugin' );

										if(empty($gateway_data['Name'])) {
											continue;
										}
										?>
										<option value='<?php echo $gateway_data['gateway_id']; ?>'><?php echo $gateway_data['Name']; ?></option>
										<?php

									}
								}
						?>
					</select>
					<?php _e(' gateway to receive payments.','membership'); ?>
				</p>
				<p class="about-description">
					<input type='submit' name='submit' class='button-primary alignright' value='<?php _e('Finish', 'membership'); ?>' />
				</p>
				</form>

				<p class="welcome-panel-dismiss"><?php _e('Already know what you\'re doing?', 'membership'); ?> <a href="<?php echo $deactivateurl;  ?>"><?php _e('Dismiss this message', 'membership'); ?></a>.</p>

			<?php
			return ob_get_clean();

		}

		function show_dripped_wizard_step( $nextsteplink = false ) {

			global $page, $action, $step;

			$deactivateurl = wp_nonce_url("admin.php?page=" . $page. "&amp;action=deactivatewelcome", 'deactivate-welcome');
			ob_start();
			?>
				<h3><?php _e('Create your levels', 'membership'); ?></h3>
				<p class="about-description">
					<?php
						_e('A level controls what parts of your website a user has access to, so we will need to set some initial ones up. ','membership');
						_e('Select the number of levels you think you will need to get started (you can add or remove them later).','membership');
					?>
				</p>
				<form action='<?php echo $nextsteplink; ?>' method='post' name='wizardform' id='wizardform'>
					<input type='hidden' name='action' value='processwizard' />
					<input type='hidden' name='from' value='steptwo' />
					<input type='hidden' name='nonce' value='<?php echo wp_create_nonce('membership_wizard'); ?>' />
					<input type='hidden' name='wizardtype' value='dripped' />
					<p class="about-description createsteps">
					<?php _e('Create ','membership'); ?>
					<select name='numberoflevels' id='wizardnumberoflevels'>
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
					<?php _e(' levels and give them the following names:','membership'); ?>
					</p>
						<ul class='wizardlevelnames'>
							<li><input type='text' name='levelname[]' value='<?php _e('Level 1', 'membership'); ?>' class='wizardlevelname' /></li>
							<li><input type='text' name='levelname[]' value='<?php _e('Level 2', 'membership'); ?>' class='wizardlevelname' /></li>
						</ul>
					<p class="about-description createsteps">
					<input type='checkbox' name='creatavisitorlevel' value='yes' checked='checked' />&nbsp;<?php _e('also create a level to control what non-members can see?', 'membership'); ?>
					<br/><br/>
					<?php _e('Finally, I would like to use the ','membership'); ?>
					<select name='wizardgateway' >
						<option value=''><?php _e('Select a gateway...', 'membership'); ?></option>
						<?php 	$gateways = get_membership_gateways();
								if(!empty($gateways)) {
									foreach($gateways as $key => $gateway) {
										$default_headers = array(
											                'Name' => 'Addon Name',
															'Author' => 'Author',
															'Description'	=>	'Description',
															'AuthorURI' => 'Author URI',
															'gateway_id' => 'Gateway ID'
											        );

										$gateway_data = get_file_data( membership_dir('membershipincludes/gateways/' . $gateway), $default_headers, 'plugin' );

										if(empty($gateway_data['Name'])) {
											continue;
										}
										?>
										<option value='<?php echo $gateway_data['gateway_id']; ?>'><?php echo $gateway_data['Name']; ?></option>
										<?php

									}
								}
						?>
					</select>
					<?php _e(' gateway to receive payments.','membership'); ?>
				</p>
				<p class="about-description">
					<input type='submit' name='submit' class='button-primary alignright' value='<?php _e('Finish', 'membership'); ?>' />
				</p>
				</form>

				<p class="welcome-panel-dismiss"><?php _e('Already know what you\'re doing?', 'membership'); ?> <a href="<?php echo $deactivateurl;  ?>"><?php _e('Dismiss this message', 'membership'); ?></a>.</p>

			<?php
			return ob_get_clean();

		}

		function process_normal_wizard_step() {
			// This function sets up the normal wizard

			if(isset($_POST['levelname'])) {
				foreach($_POST['levelname'] as $key => $value) {
					if(empty($value)) {
						$value = __('Level ', 'membership') . ((int) $key + 1);
					}
					// Create a level
					$level_id = $this->create_level( $value );
					// Create a subscription with that level
					$sub_id = $this->create_subscription( $value );
					// Add the level to the subscription
					$this->add_level_to_subscription( $level_id, $sub_id );

					// Activate and make public the levels and subscriptions
					$sub = new M_Subscription( $sub_id );
					$sub->toggleactivation();
					$sub->togglepublic();
					$level = new M_Level( $level_id );
					$level->toggleactivation();
				}
			}

			// Create a visitor level and set it in the options
			if(isset($_POST['creatavisitorlevel']) && $_POST['creatavisitorlevel'] == 'yes') {
				$level_id = $this->create_level( __('Visitors','membership') );
				$level = new M_Level( $level_id );
				$level->toggleactivation();

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

				$M_options['strangerlevel'] = (int) $level_id;

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

			// Activate the relevant gateway if it's set
			if(isset($_POST['wizardgateway'])) {
				$active = get_option('membership_activated_gateways', array());
				if(!in_array($_POST['wizardgateway'], $active)) {
					$active[] = $_POST['wizardgateway'];
					update_option('membership_activated_gateways', array_unique($active));
				}
			}


		}

		function process_dripped_wizard_step() {

			if(isset($_POST['levelname'])) {
				// Create an initial subscription
				$sub_id = $this->create_subscription( __('Dripped Subscription', 'membership') );
				$sub = new M_Subscription( $sub_id );
				$sub->toggleactivation();
				$sub->togglepublic();

				foreach($_POST['levelname'] as $key => $value) {
					if(empty($value)) {
						$value = __('Level ', 'membership') . ((int) $key + 1);
					}
					// Create a level
					$level_id = $this->create_level( $value );
					// Add the level to the subscription
					$this->add_level_to_subscription( $level_id, $sub_id, 'finite' );
					// Activate and make public the levels and subscriptions
					$level = new M_Level( $level_id );
					$level->toggleactivation();
				}
			}
			// Create a visitor level and set it in the options
			if(isset($_POST['creatavisitorlevel']) && $_POST['creatavisitorlevel'] == 'yes') {
				$level_id = $this->create_level( __('Visitors','membership') );
				$level = new M_Level( $level_id );
				$level->toggleactivation();

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

				$M_options['strangerlevel'] = (int) $level_id;

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

			// Activate the relevant gateway if it's set
			if(isset($_POST['wizardgateway'])) {
				$active = get_option('membership_activated_gateways', array());
				if(!in_array($_POST['wizardgateway'], $active)) {
					$active[] = $_POST['wizardgateway'];
					update_option('membership_activated_gateways', array_unique($active));
				}
			}

		}

		function page_end( $nextsteplink = false ) {

			global $M_options, $page, $action, $step;

			ob_start();
			?>
				<h3><?php _e('Thank you', 'membership'); ?></h3>
				<p class="about-description">
					<?php

						$deactivateurl = wp_nonce_url("admin.php?page=membership&amp;action=deactivatewelcome", 'deactivate-welcome');

						_e('Thank you, we have now set up some of the initial Membership options. ','membership');
						_e('If you would like more tips on using the system then you can now follow the tutorial pointers we have included, or check out the help guides at the top of every page.','membership');

						?>
						<br/>
						<a href='<?php echo $deactivateurl; ?>' class='button-primary alignright'><?php _e('Close this wizard', 'membership'); ?></a>
				</p>

			<?php
			return ob_get_clean();
		}

		// Helper functions
		function create_level( $title = false ) {

			$return = $this->db->insert($this->membership_levels, array('level_title' => $title, 'level_slug' => sanitize_title($title)));

			return $this->db->insert_id;

		}

		function create_subscription( $title = false ) {

			$return = $this->db->insert($this->subscriptions, array('sub_name' => $title, 'sub_description' => '', 'sub_pricetext' => ''));

			return $this->db->insert_id;

		}

		function add_level_to_subscription( $level_id, $sub_id, $type = 'serial' ) {

			$max = $this->db->get_var( "SELECT max(level_order) FROM {$this->subscriptions_levels} WHERE sub_id = " . $sub_id );

			if(empty($max)) $max = 0;

			$this->db->insert($this->subscriptions_levels, array(	"sub_id" => $sub_id,
																	"level_period" => 20,
																	"sub_type" => $type,
																	"level_price" => 0,
																	"level_currency" => 'USD',
																	"level_order" => ++$max,
																	"level_id" => $level_id,
																	"level_period_unit" => 'd'
																	));
		}

		function activate_gateway( $gateway = false ) {

		}

	}

}
?>