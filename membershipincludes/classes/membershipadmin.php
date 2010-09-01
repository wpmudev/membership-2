<?php
if(!class_exists('membershipadmin')) {

	class membershipadmin {

		var $build = 5;
		var $db;

		//
		var $showposts = 25;
		var $showpages = 100;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			// Add administration actions
			add_action('init', array(&$this, 'initialise_plugin'));

			// Add in admin area membership levels
			add_action('init', array(&$this, 'initialise_membership_protection'), 999);

			add_action('admin_menu', array(&$this, 'add_admin_menu'));

			add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

			// Header actions
			add_action('load-toplevel_page_membership', array(&$this, 'add_admin_header_membership'));
			add_action('load-membership_page_members', array(&$this, 'add_admin_header_members'));
			add_action('load-membership_page_membershiplevels', array(&$this, 'add_admin_header_membershiplevels'));
			add_action('load-membership_page_membershipsubs', array(&$this, 'add_admin_header_membershipsubs'));
			add_action('load-membership_page_membershipgateways', array(&$this, 'add_admin_header_membershipgateways'));
			add_action('load-membership_page_membershipoptions', array(&$this, 'add_admin_header_membershipoptions'));

			add_action('load-users_page_membershipuser', array(&$this, 'add_admin_header_membershipuser'));

			add_filter('membership_level_sections', array(&$this, 'default_membership_sections'));

			// Media management additional fields
			add_filter('attachment_fields_to_edit', array(&$this, 'add_media_protection_settings'), 99, 2);
			add_filter('attachment_fields_to_save', array(&$this, 'save_media_protection_settings'), 99, 2);

			// rewrites
			add_action('generate_rewrite_rules', array(&$this, 'add_rewrites'));
			add_filter( 'query_vars', array(&$this, 'add_queryvars') );

			// profile field for feeds
			add_action( 'show_user_profile', array(&$this, 'add_profile_feed_key') );

		}

		function membershipadmin() {
			$this->__construct();
		}

		function load_textdomain() {

			$locale = apply_filters( 'membership_locale', get_locale() );
			$mofile = membership_dir( "membershipincludes/membership-$locale.mo" );

			if ( file_exists( $mofile ) )
				load_textdomain( 'membership', $mofile );

		}

		function initialise_plugin() {

			global $user, $M_options;

			$installed = get_option('M_Installed', false);

			if($installed === false || $installed != $this->build) {
				include_once(membership_dir('membershipincludes/classes/upgrade.php') );

				M_Upgrade($installed);

				update_option('M_Installed', $this->build);
			}

			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}
			// Add in our new capability
			if($user->user_login == MEMBERSHIP_MASTER_ADMIN && !$user->has_cap('membershipadmin')) {
				$user->add_cap('membershipadmin');
			}

			if($user->has_cap('membershipadmin')) {
				// profile field for capabilities
				add_action( 'edit_user_profile', array(&$this, 'add_membershipadmin_capability') );
				add_action( 'edit_user_profile_update', array(&$this, 'update_membershipadmin_capability'));
			}

			$M_options = get_option('membership_options', array());

			// Short codes
			if(!empty($M_options['membershipshortcodes'])) {
				foreach($M_options['membershipshortcodes'] as $key => $value) {
					if(!empty($value)) {
						add_shortcode(stripslashes(trim($value)), array(&$this, 'do_fake_shortcode') );
					}
				}
			}

		}

		function add_admin_menu() {

			global $menu, $admin_page_hooks;

			if(current_user_can('membershipadmin')) {
				// Add the menu page
				add_menu_page(__('Membership','membership'), __('Membership','membership'), 'membershipadmin',  'membership', array(&$this,'handle_membership_panel'), membership_url('membershipincludes/images/members.png'));

				// Fix WP translation hook issue
				if(isset($admin_page_hooks['membership'])) {
					$admin_page_hooks['membership'] = 'membership';
				}

				// Add the sub menu
				add_submenu_page('membership', __('Members','membership'), __('Edit Members','membership'), 'membershipadmin', "members", array(&$this,'handle_members_panel'));

				add_submenu_page('membership', __('Membership Levels','membership'), __('Edit Levels','membership'), 'membershipadmin', "membershiplevels", array(&$this,'handle_levels_panel'));
				add_submenu_page('membership', __('Membership Subscriptions','membership'), __('Edit Subscriptions','membership'), 'membershipadmin', "membershipsubs", array(&$this,'handle_subs_panel'));
				add_submenu_page('membership', __('Membership Gateways','membership'), __('Edit Gateways','membership'), 'membershipadmin', "membershipgateways", array(&$this,'handle_gateways_panel'));

				add_submenu_page('membership', __('Membership Options','membership'), __('Edit Options','membership'), 'membershipadmin', "membershipoptions", array(&$this,'handle_options_panel'));

				// Move the menu to the top of the page
				foreach($menu as $key => $value) {
					if($value[2] == 'membership') {
						if(!isset($menu[-10])) {
							$menu[-10] = $menu[$key];
							$menu[-11] = array( '', 'read', 'separator1', '', 'wp-menu-separator' );

							// CSS style for the menu
							$menu[-10][4] .= ' menu-top-first menu-top-last';

							unset($menu[$key]);
							break;
						}

					}
				}
			}

			//add_submenu_page('users.php', __('Member details','membership'), __('Member details','membership'), 'read', "membershipuser", array(&$this,'handle_profile_member_page'));

		}

		// Admin area protection
		function initialise_membership_protection() {

			global $user, $member, $M_options, $M_Rules, $wp_query, $wp_rewrite, $M_active;
			// Set up some common defaults

			static $initialised = false;

			if($initialised) {
				// ensure that this is only called once, so return if we've been here already.
				return;
			}

			$M_options = get_option('membership_options', array());
			// Check if the membership plugin is active
			$M_active = get_option('membership_active', 'no');

			if(empty($user) || !method_exists($user, 'has_cap')) {
				$user = wp_get_current_user();
			}

			if(!method_exists($user, 'has_cap') || $user->has_cap('membershipadmin') || $M_active == 'no') {
				// Admins can see everything
				return;
			}

			// Users
			$member = new M_Membership($user->ID);

			if($user->ID > 0 && $member->has_levels()) {
				// Load the levels for this member - and associated rules
				$member->load_admin_levels( true );
			} else {
				// need to grab the stranger settings
				if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
					$member->assign_admin_level($M_options['strangerlevel'], true );
				}
			}

			// Set the initialisation status
			$initialised = true;

		}

		// Add admin headers

		function add_admin_header_core() {

		}

		function add_admin_header_membership() {
			// The dashboard - top level menu

			// Load the core first
			$this->add_admin_header_core();

			wp_enqueue_script('dashjs', membership_url('membershipincludes/js/dashboard.js'), array( 'jquery' ), $this->build);
			wp_enqueue_style('dashcss', membership_url('membershipincludes/css/dashboard.css'), array('widgets'), $this->build);

			//wp_localize_script( 'levelsjs', 'membership', array( 'deletelevel' => __('Are you sure you want to delete this level?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );

			$this->handle_membership_dashboard_updates();
		}

		function add_admin_header_membershiplevels() {

			$this->add_admin_header_core();

			wp_enqueue_script('levelsjs', membership_url('membershipincludes/js/levels.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);
			wp_enqueue_style('levelscss', membership_url('membershipincludes/css/levels.css'), array('widgets'), $this->build);

			wp_localize_script( 'levelsjs', 'membership', array( 'deletelevel' => __('Are you sure you want to delete this level?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );

			$this->handle_levels_updates();
		}

		function add_admin_header_membershipsubs() {
			// Run the core header
			$this->add_admin_header_core();

			// Queue scripts and localise
			wp_enqueue_script('subsjs', membership_url('membershipincludes/js/subscriptions.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);
			wp_enqueue_style('subscss', membership_url('membershipincludes/css/subscriptions.css'), array('widgets'), $this->build);

			wp_localize_script( 'subsjs', 'membership', array( 'deletesub' => __('Are you sure you want to delete this subscription?','membership'), 'deactivatesub' => __('Are you sure you want to deactivate this subscription?','membership') ) );

			$this->handle_subscriptions_updates();

		}

		function add_admin_header_members() {
			// Run the core header
			$this->add_admin_header_core();

			wp_enqueue_script('membersjs', membership_url('membershipincludes/js/members.js'), array(), $this->build);
			// Using the level css file for now - maybe switch to a members specific one later
			wp_enqueue_style('memberscss', membership_url('membershipincludes/css/levels.css'), array('widgets'), $this->build);

			wp_localize_script( 'membersjs', 'membership', array( 'deactivatemember' => __('Are you sure you want to deactivate this member?','membership') ) );


			$this->handle_members_updates();

		}

		function add_admin_header_membershipgateways() {
			$this->add_admin_header_core();

			$this->handle_gateways_panel_updates();
		}

		function add_admin_header_membershipoptions() {
			$this->add_admin_header_core();

			wp_enqueue_style('optionscss', membership_url('membershipincludes/css/options.css'), array(), $this->build);

			$this->handle_options_panel_updates();
		}

		function add_admin_header_membershipuser() {
			$this->add_admin_header_core();

			wp_enqueue_style('optionscss', membership_url('membershipincludes/css/options.css'), array(), $this->build);
		}

		// Panel handling functions

		function build_signup_stats() {

			$sql = $this->db->prepare( "SELECT YEAR(startdate) as year, MONTH(startdate)as month, DAY(startdate) as day, count(*) AS signedup FROM {$this->membership_relationships} WHERE startdate > DATE_SUB(CURDATE(), INTERVAL 10 DAY) GROUP BY YEAR(startdate), MONTH(startdate), DAY(startdate) ORDER BY startdate DESC" );

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {

				$stats = array();
				$ticks = array();
				$data = array();
				foreach($results as $key => $res) {

					$stats[strtotime($res->year . "-" . $res->month . "-" . $res->day)] = (int) $res->signedup;

				}

				$startat = time();
				for($n = 0; $n < 11; $n++) {
					$switch = 10 - $n;
					$rdate = strtotime('-' . $switch . ' DAYS', $startat);

					$ticks[$n] = '"' . date('n', $rdate) . "/" . date('j', $rdate) . '"';

					if(isset($stats[strtotime(date("Y", $rdate) . "-" . date("n", $rdate) . "-" . date("j", $rdate))])) {
						$data[$n] = $stats[strtotime(date("Y", $rdate) . "-" . date("n", $rdate) . "-" . date("j", $rdate))];
					} else {
						$data[$n] = 0;
					}
				}

				$stats = $data;

				return compact('stats', 'ticks');

			} else {
				return false;
			}

		}

		function build_levels_stats() {

			$sql = $this->db->prepare( "SELECT l.id, l.level_title, count(m.rel_id) as users FROM {$this->membership_levels} as l, {$this->membership_relationships} as m WHERE l.id = m.level_id GROUP BY l.id, l.level_title ORDER BY users DESC" );

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {

				$stats = array();
				$ticks = array();
				foreach($results as $key => $res) {

					$stats[] = (int) $res->users;
					$ticks[] = '"' . esc_html($res->level_title) . '"';
				}

				return compact('stats', 'ticks');

			} else {
				return false;
			}

		}

		function build_subs_stats() {

			$sql = $this->db->prepare( "SELECT s.id, s.sub_name, count(m.rel_id) as users FROM {$this->subscriptions} as s, {$this->membership_relationships} as m WHERE s.id = m.sub_id GROUP BY s.id, s.sub_name ORDER BY users DESC" );

			$results = $this->db->get_results( $sql );

			if(!empty($results)) {

				$stats = array();
				$ticks = array();
				foreach($results as $key => $res) {

					$stats[] = (int) $res->users;
					$ticks[] = '"' . esc_html($res->sub_name) . '"';
				}

				return compact('stats', 'ticks');

			} else {
				return false;
			}

		}

		function get_data($results) {

			$data = array();

			foreach( (array) $results as $key => $res) {
				$data[] = "[ " . $key . ", " . $res . " ]";
			}

			return "[ " . implode(", ", $data) . " ]";

		}

		function handle_membership_dashboard_updates() {

			global $page, $action;

			wp_reset_vars( array('action', 'page') );

			switch($action) {

				case 'activate':	check_admin_referer('toggle-plugin');
									update_option('membership_active', 'yes');
									wp_safe_redirect( wp_get_referer() );
									break;

				case 'deactivate':	check_admin_referer('toggle-plugin');
									update_option('membership_active', 'no');
									wp_safe_redirect( wp_get_referer() );
									break;
			}

			wp_enqueue_script('flot_js', membership_url('membershipincludes/js/jquery.flot.min.js'), array('jquery'));
			wp_enqueue_script('mdash_js', membership_url('membershipincludes/js/dashboard.js'), array('jquery'));

			wp_localize_script( 'mdash_js', 'membership', array( 'signups' => __('Signups','membership'), 'members' => __('Members','membership') ) );


			add_action ('admin_head', array(&$this, 'dashboard_iehead'));
			add_action ('admin_head', array(&$this, 'dashboard_chartdata'));

		}

		function dashboard_chartdata() {
			$returned = $this->build_signup_stats();
			$levels = $this->build_levels_stats();
			$subs = $this->build_subs_stats();

			echo "\n" . '<script type="text/javascript">';
			echo "\n" . '/* <![CDATA[ */ ' . "\n";

			echo "var membershipdata = {\n";
				echo "chartonestats : " . $this->get_data($returned['stats']) . ",\n";
				echo "chartoneticks : " . $this->get_data($returned['ticks']) . ",\n";

				echo "charttwostats : " . $this->get_data($levels['stats']) . ",\n";
				echo "charttwoticks : " . $this->get_data($levels['ticks']) . ",\n";

				echo "chartthreestats : " . $this->get_data($subs['stats']) . ",\n";
				echo "chartthreeticks : " . $this->get_data($subs['ticks']) . "\n";
			echo "};\n";

			echo "\n" . '/* ]]> */ ';
			echo '</script>';
		}

		function dashboard_iehead() {
			echo '<!--[if IE]><script language="javascript" type="text/javascript" src="' . membership_url('membershipincludes/js/excanvas.min.js') . '"></script><![endif]-->';
		}

		function dashboard_members() {

			global $page, $action;

			$plugin = get_plugin_data(membership_dir('membership.php'));

			$membershipactive = get_option('membership_active', 'no');

			echo __('The membership plugin version ','membership') . "<strong>" . $plugin['Version'] . '</strong>';
			echo __(' is ', 'membership');

			// Membership active toggle
			if($membershipactive == 'no') {
				echo '<a href="' . wp_nonce_url("?page=" . $page. "&amp;action=activate", 'toggle-plugin') . '" title="' . __('Click here to enable the plugin','membership') . '">' . __('Disabled','membership') . '</a>';
			} else {
				echo '<a href="' . wp_nonce_url("?page=" . $page. "&amp;action=deactivate", 'toggle-plugin') . '" title="' . __('Click here to enable the plugin','membership') . '">' . __('Enabled','membership') . '</a>';
			}

			echo '<br/><br/>';

			echo "<strong>" . __('Member counts', 'membership') . "</strong><br/>";

			$detail = $this->get_subscriptions_and_levels(array('sub_status' => 'active'));
			$subs = $this->get_subscriptions(array('sub_status' => 'active'));

			$levels = $this->get_membership_levels(array('level_id' => 'active'));

			echo "<table style='width: 100%;'>";
			echo "<tbody>";
			echo "<tr>";
			echo "<td style='width: 48%' valign='top'>";
			if($levels) {
				$levelcount = 0;
				echo "<table style='width: 100%;'>";
				echo "<tbody>";
					echo "<tr>";
					echo "<td colspan='2'><strong>" . __('Levels','membership') . "</strong></td>";
					echo "</tr>";
					foreach($levels as $key => $level) {
						echo "<tr>";
							echo "<td><a href='" . admin_url('admin.php?page=membershiplevels&action=edit&level_id=') . $level->id . "'>" . esc_html($level->level_title) . "</a></td>";
							// find out how many people are in this level
							$thiscount = $this->count_on_level( $level->id );

							echo "<td style='text-align: right;'>" . (int) $thiscount . "</td>";
							$levelcount += (int) $thiscount;
						echo "</tr>";
					}
					echo "<tr>";
						echo "<td>". __('Total', 'membership') . "</td>";
						echo "<td style='text-align: right;'><strong>" . (int) $levelcount . "</strong></td>";
					echo "</tr>";
				echo "</tbody>";
				echo "</table>";
			}
			echo "</td>";

			echo "<td style='width: 48%' valign='top'>";
			if($subs) {
				$subcount = 0;
				echo "<table style='width: 100%;'>";
				echo "<tbody>";
					echo "<tr>";
					echo "<td colspan='2'><strong>" . __('Subscriptions','membership') . "</strong></td>";
					echo "</tr>";
					foreach($subs as $key => $sub) {
						echo "<tr>";
							echo "<td><a href='" . admin_url('admin.php?page=membershipsubs&action=edit&sub_id=') . $sub->id . "'>" . $sub->sub_name . "</a></td>";
							// find out how many people are in this sub
							$thiscount = $this->count_on_sub( $sub->id );

							echo "<td style='text-align: right;'>" . (int) $thiscount . "</td>";
							$subcount += (int) $thiscount;
						echo "</tr>";
					}
					echo "<tr>";
						echo "<td>". __('Total', 'membership') . "</td>";
						echo "<td style='text-align: right;'><strong>" . (int) $subcount . "</strong></td>";
					echo "</tr>";
				echo "</tbody>";
				echo "</table>";
			}
			echo "</td>";

			echo "</tr>";
			echo "</tbody>";
			echo "</table>";

			echo "<br/><strong>" . __('User counts', 'membership') . "</strong><br/>";

			echo "<table style='width: 100%;'>";
			echo "<tbody>";
			echo "<tr>";
			echo "<td style='width: 48%' valign='top'>";

				echo "<table style='width: 100%;'>";
				echo "<tbody>";

					$usercount = $this->db->get_var( $this->db->prepare("SELECT count(*) FROM {$this->db->users} INNER JOIN {$this->db->usermeta} ON {$this->db->users}.ID = {$this->db->usermeta}.user_id WHERE {$this->db->usermeta}.meta_key = '{$this->db->prefix}capabilities'") );

					echo "<tr>";
						echo "<td>" . __('Total Users', 'membership') . "</td>";
						echo "<td style='text-align: right;'>" . $usercount . "</td>";
					echo "</tr>";

					$deactivecount = $this->db->get_var( $this->db->prepare("SELECT count(*) FROM {$this->db->usermeta} WHERE meta_key = %s AND meta_value = %s", $this->db->prefix . 'membership_active' , 'no') );

					echo "<tr>";
						echo "<td>" . __('Deactivated Users', 'membership') . "</td>";
						echo "<td style='text-align: right;'>" . $deactivecount . "</td>";
					echo "</tr>";

				echo "</tbody>";
				echo "</table>";

			echo "</td>";

			echo "<td style='width: 48%' valign='top'></td>";

			echo "</tr>";
			echo "</tbody>";
			echo "</table>";

		}

		function dashboard_statistics() {

			echo "<div id='memchartone'></div>";
			echo "<div id='memcharttwo'></div>";
			echo "<div id='memchartthree'></div>";

			do_action( 'membership_dashboard_statistics' );
		}

		function handle_membership_panel() {

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Membership dashboard','membership'); ?></h2>

				<div id="dashboard-widgets-wrap">

				<div class="metabox-holder" id="dashboard-widgets">
					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="normal-sortables">

							<div class="postbox " id="dashboard_right_now">
								<h3 class="hndle"><span><?php _e('Members','membership'); ?></span></h3>
								<div class="inside">
									<?php $this->dashboard_members(); ?>
									<br class="clear">
								</div>
							</div>

							<?php
							do_action( 'membership_dashboard_left' );
							?>
						</div>
					</div>

					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="side-sortables">

							<div class="postbox " id="dashboard_quick_press">
								<h3 class="hndle"><span><?php _e('Statistics','membership'); ?></span></h3>
								<div class="inside">
									<?php $this->dashboard_statistics(); ?>
									<br class="clear">
								</div>
							</div>

							<?php
							do_action( 'membership_dashboard_right' );
							?>

						</div>
					</div>

					<div style="display: none; width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="column3-sortables" style="">
						</div>
					</div>

					<div style="display: none; width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="column4-sortables" style="">
						</div>
					</div>
				</div>

				<div class="clear"></div>
				</div>

			</div> <!-- wrap -->
			<?php

		}

		function handle_members_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}



			switch(addslashes($action)) {

				case 'toggle':	if(isset($_GET['member_id'])) {
									$user_id = (int) $_GET['member_id'];

									check_admin_referer('toggle-member_' . $user_id);

									$member = new M_Membership($user_id);

									if( $member->toggle_activation() ) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-members');
								foreach($_GET['users'] AS $value) {
									if(is_numeric($value)) {
										$user_id = (int) $value;

										$member = new M_Membership($user_id);

										$member->toggle_activation();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

				case 'bulkaddlevel-level-complete':
				case 'addlevel-level-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$tolevel_id = (int) $_POST['tolevel_id'];
										if($tolevel_id) {
											$member->add_level($tolevel_id);
										}
									}
								}

								$this->update_levelcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkdroplevel-level-complete':
				case 'droplevel-level-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromlevel_id = (int) $_POST['fromlevel_id'];
										if($fromlevel_id) {
											$member->drop_level($fromlevel_id);
										}
									}
								}

								$this->update_levelcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkmovelevel-level-complete':
				case 'movelevel-level-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromlevel_id = (int) $_POST['fromlevel_id'];
										$tolevel_id = (int) $_POST['tolevel_id'];
										if($fromlevel_id && $tolevel_id) {
											$member->move_level($fromlevel_id, $tolevel_id);
										}
									}
								}

								$this->update_levelcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkaddsub-sub-complete':
				case 'addsub-sub-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$tosub_id = $_POST['tosub_id'];
										if($tosub_id) {
											$subs = explode('-',$tosub_id);
											if(count($subs) == 3) {
												$member->add_subscription($subs[0], $subs[1], $subs[2]);
											}
										}
									}
								}

								$this->update_levelcounts();
								$this->update_subcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkdropsub-sub-complete':
				case 'dropsub-sub-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromsub_id = (int) $_POST['fromsub_id'];
										if($fromsub_id) {
											$member->drop_subscription($fromsub_id);
										}
									}
								}

								$this->update_levelcounts();
								$this->update_subcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

				case 'bulkmovesub-sub-complete':
				case 'movesub-sub-complete':
								check_admin_referer($action);
								$members_id = $_POST['member_id'];

								$members = explode(',', $members_id);
								if($members) {
									foreach($members as $member_id) {
										$member = new M_Membership($member_id);

										$fromsub_id = (int) $_POST['fromsub_id'];
										$tosub_id = $_POST['tosub_id'];
										if($fromsub_id && $tosub_id) {
											$subs = explode('-',$tosub_id);
											if(count($subs) == 3) {
												$member->move_subscription($fromsub_id, $subs[0], $subs[1], $subs[2]);
											}
										}
									}
								}

								$this->update_levelcounts();
								$this->update_subcounts();

								wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_original_referer() ) );
								break;

			}

		}

		function handle_edit_member() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

		}

		function handle_member_level_op($operation = 'add', $member_id = false) {

			global $action, $page, $action2;

			wp_reset_vars( array('action', 'page', 'action2') );

			if(empty($action) && !empty($action2)) $action = $action2;

			switch($operation) {

				case 'add':		$title = __('Add member to a level','membership');
								$formdescription = __('A membership level controls the amount of access to the sites content this member will have.','membership') . "<br/><br/>";
								$formdescription .= __('By adding a membership level, you may actually be removing existing access to content.','membership');

								$html = "<h3>" . __('Level to add for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tolevel_id' id='tolevel_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to add.','membership') . "</option>\n";
								$levels = $this->get_membership_levels(array('level_id' => 'active'));
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'>" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Add";

								break;

				case 'move':	$title = __('Move member to another level','membership');
								$formdescription = __('A membership level controls the amount of access to the sites content this member will have.','membership') . "<br/><br/>";

								$html = "<h3>" . __('Level to move from for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromlevel_id' id='fromlevel_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to move from.','membership') . "</option>\n";
								$levels = $this->get_membership_levels(array('level_id' => 'active'));
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'>" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$html .= "<h3>" . __('Level to move to for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tolevel_id' id='tolevel_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to move to.','membership') . "</option>\n";
								reset($levels);
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'>" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Move";
								break;

				case 'drop':	$title = __('Drop member from level','membership');

								$formdescription = __('A membership level controls the amount of access to the sites content this member will have.','membership') . "<br/><br/>";
								$formdescription .= __('By removing a membership level, you may actually be increasing existing access to content.','membership');

								$html = "<h3>" . __('Level to drop for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromlevel_id' id='fromlevel_id' class='wide'>\n";
								$html .= "<option value=''>" . __('Select the level to remove.','membership') . "</option>\n";
								$levels = $this->get_membership_levels(array('level_id' => 'active'));
								if($levels) {
									foreach($levels as $key => $level) {
										$html .= "<option value='" . esc_attr($level->id) . "'>" . esc_html($level->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Drop";

								break;


			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php echo $title; ?></h2>
				<form action='admin.php?page=<?php echo $page; ?>' method='post'>

					<div class='level-liquid-left'>

						<div id='level-left'>
							<div id='edit-level' class='level-holder-wrap'>
								<div class='sidebar-name no-movecursor'>
									<h3><?php echo esc_html($title); ?></h3>
								</div>
								<div class='level-holder'>
									<br />
									<p class='description'><?php echo $formdescription;  ?></p>
									<?php
										echo $html;
									?>

									<div class='buttons'>
										<?php
											wp_original_referer_field(true, 'previous'); wp_nonce_field($action . '-level-complete');
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e($button, 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='<?php esc_attr_e($action . '-level-complete'); ?>' />
										<?php
											if(is_array($member_id)) {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e(implode(',',$member_id)); ?>' />
												<?php
											} else {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e($member_id); ?>' />
												<?php
											}

										?>
									</div>

								</div>
							</div>
						</div>

					</div> <!-- level-liquid-left -->

				</form>
			</div> <!-- wrap -->
			<?php

		}

		function handle_member_subscription_op($operation = 'add', $member_id = false) {

			global $action, $page, $action2;

			wp_reset_vars( array('action', 'page', 'action2') );

			if(empty($action) && !empty($action2)) $action = $action2;

			switch($operation) {

				case 'add':		$title = __('Add member to a subscription','membership');
								$formdescription = __('A subscription controls the levels a site member has access to / passes through.','membership') . "<br/><br/>";
								$formdescription .= __('Depending on your payment gateway, adding a subscription here may not set up a payment subscription.','membership');

								$html = "<h3>" . __('Subscription and level to add for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tosub_id' id='tosub_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to add.','membership') . "</option>\n";

								$subs = $this->get_subscriptions_and_levels( array('sub_status' => 'active') );
								if($subs) {
									$sub_id = false;
									foreach($subs as $key => $sub) {
										if($sub_id != $sub->sub_id) {
											$sub_id = $sub->sub_id;

											$html .= "<optgroup label='";
											$html .= $sub->sub_name;
											$html .= "'>";

										}
										$html .= "<option value='" . esc_attr($sub->sub_id) . "-" . esc_attr($sub->level_id) . "-" . esc_attr($sub->level_order) . "'>" . $sub->level_order . " : " . esc_html($sub->sub_name . " - " . $sub->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Add";
								break;

				case 'move':	$title = __('Move member to another subscription level','membership');
								$formdescription = __('A subscription controls the levels a site member has access to / passes through.','membership') . "<br/><br/>";
								$formdescription .= __('Depending on your payment gateway, moving a subscription here may not alter a members existing payment subscription.','membership');

								$html = "<h3>" . __('Subscription to move from for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromsub_id' id='fromsub_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the subscription to move from.','membership') . "</option>\n";
								$subs = $this->get_subscriptions( array('sub_status' => 'active'));
								if($subs) {
									foreach($subs as $key => $sub) {
										$html .= "<option value='" . esc_attr($sub->id) . "'>" . esc_html($sub->sub_name) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$html .= "<h3>" . __('Subscription and Level to move to for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='tosub_id' id='tosub_id' class='wide'>\n";
								$html .= "<option value='0'>" . __('Select the level to move to.','membership') . "</option>\n";
								$subs = $this->get_subscriptions_and_levels( array('sub_status' => 'active') );
								if($subs) {
									$sub_id = false;
									foreach($subs as $key => $sub) {
										if($sub_id != $sub->sub_id) {
											$sub_id = $sub->sub_id;

											$html .= "<optgroup label='";
											$html .= $sub->sub_name;
											$html .= "'>";

										}
										$html .= "<option value='" . esc_attr($sub->sub_id) . "-" . esc_attr($sub->level_id) . "-" . esc_attr($sub->level_order) . "'>" . $sub->level_order . " : " . esc_html($sub->sub_name . " - " . $sub->level_title) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Move";
								break;

				case 'drop':	$title = __('Drop member from subscription','membership');

								$formdescription = __('A subscription controls the levels a site member has access to / passes through.','membership') . "<br/><br/>";
								$formdescription .= __('Depending on the payment gateway, removing a subscription will not automatically cancel a payment subscription.','membership');

								$html = "<h3>" . __('Subscription to drop for this / these member(s)','management') . "</h3>";
								$html .= "<div class='level-details'>";
								$html .= "<select name='fromsub_id' id='fromsub_id' class='wide'>\n";
								$html .= "<option value=''>" . __('Select the subscription to remove.','membership') . "</option>\n";
								$subs = $this->get_subscriptions( array('sub_status' => 'active'));
								if($subs) {
									foreach($subs as $key => $sub) {
										$html .= "<option value='" . esc_attr($sub->id) . "'>" . esc_html($sub->sub_name) . "</option>\n";
									}
								}
								$html .= "</select>\n";
								$html .= "</div>";

								$button = "Drop";
								break;


			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php echo $title; ?></h2>
				<form action='admin.php?page=<?php echo $page; ?>' method='post'>

					<div class='level-liquid-left'>

						<div id='level-left'>
							<div id='edit-level' class='level-holder-wrap'>
								<div class='sidebar-name no-movecursor'>
									<h3><?php echo esc_html($title); ?></h3>
								</div>
								<div class='level-holder'>
									<br />
									<p class='description'><?php echo $formdescription;  ?></p>
									<?php
										echo $html;
									?>

									<div class='buttons'>
										<?php
											wp_original_referer_field(true, 'previous'); wp_nonce_field($action . '-sub-complete');
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e($button, 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='<?php esc_attr_e($action . '-sub-complete'); ?>' />
										<?php
											if(is_array($member_id)) {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e(implode(',',$member_id)); ?>' />
												<?php
											} else {
												?>
												<input type='hidden' name='member_id' value='<?php esc_attr_e($member_id); ?>' />
												<?php
											}

										?>
									</div>

								</div>
							</div>
						</div>

					</div> <!-- level-liquid-left -->

				</form>
			</div> <!-- wrap -->
			<?php


		}

		function handle_members_panel() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			require_once('class.membersearch.php');

			// bulk actions
			if(isset($_GET['doaction'])) {
				$action = $_GET['action'];

			} elseif(isset($_GET['doaction2'])) {
				$action = $_GET['action2'];
			}

			switch(addslashes($action)) {

				case 'addlevel':	if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										$this->handle_member_level_op('add', $member_id);
										return;
									}
									break;

				case 'movelevel':	if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('movelevel-member-' . $member_id);
										$this->handle_member_level_op('move', $member_id);
										return;
									}
									break;

				case 'droplevel':	if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('droplevel-member-' . $member_id);
										$this->handle_member_level_op('drop', $member_id);
										return;
									}
									break;

				case 'bulkaddlevel':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_level_op('add', $_GET['users']);
										return;
									}
									break;

				case 'bulkmovelevel':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_level_op('move', $_GET['users']);
										return;
									}
									break;

				case 'bulkdroplevel':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_level_op('drop', $_GET['users']);
										return;
									}
									break;

				case 'addsub':		if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										$this->handle_member_subscription_op('add', $member_id);
										return;
									}
									break;

				case 'movesub':		if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('movesub-member-' . $member_id);
										$this->handle_member_subscription_op('move', $member_id);
										return;
									}
									break;

				case 'dropsub':		if(isset($_GET['member_id'])) {
										$member_id = (int) $_GET['member_id'];
										check_admin_referer('dropsub-member-' . $member_id);
										$this->handle_member_subscription_op('drop', $member_id);
										return;
									}
									break;

				case 'bulkaddsub':	if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_subscription_op('add', $_GET['users']);
										return;
									}
									break;
				case 'bulkmovesub':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_subscription_op('move', $_GET['users']);
										return;
									}
									break;
				case 'bulkdropsub':
									if(isset($_GET['users'])) {
										check_admin_referer('bulk-members');
										$this->handle_member_subscription_op('drop', $_GET['users']);
										return;
									}
									break;

				case 'edit':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];
									$this->handle_level_edit_form($level_id);
									return; // So we don't see the rest of this page
								}
								break;

			}

			$filter = array();

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			$sub_id = null; $level_id = null;

			if(isset($_GET['doactionsub'])) {
				if(addslashes($_GET['sub_op']) != '') {
					$sub_id = addslashes($_GET['sub_op']);
				}
			}

			if(isset($_GET['doactionsub2'])) {
				if(addslashes($_GET['sub_op2']) != '') {
					$sub_id = addslashes($_GET['sub_op2']);
				}
			}

			if(isset($_GET['doactionlevel'])) {
				if(addslashes($_GET['level_op']) != '') {
					$level_id = addslashes($_GET['level_op']);
				}
			}

			if(isset($_GET['doactionlevel2'])) {
				if(addslashes($_GET['level_op2']) != '') {
					$level_id = addslashes($_GET['level_op2']);
				}
			}

			$usersearch = isset($_GET['s']) ? $_GET['s'] : null;
			$userspage = isset($_GET['userspage']) ? $_GET['userspage'] : null;
			$role = null;

			// Query the users
			$wp_user_search = new M_Member_Search($usersearch, $userspage, $sub_id, $level_id);

			$messages = array();
			$messages[1] = __('Member added.');
			$messages[2] = __('Member deleted.');
			$messages[3] = __('Member updated.');
			$messages[4] = __('Member not added.');
			$messages[5] = __('Member not updated.');
			$messages[6] = __('Member not deleted.');

			$messages[7] = __('Member activation toggled.');
			$messages[8] = __('Member activation not toggled.');

			$messages[9] = __('Members updated.');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php _e('Edit Members','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('msg'), $_SERVER['REQUEST_URI']);
				}
				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />
					<label for="membership-search-input" class="screen-reader-text"><?php _e('Search Members','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="membership-search-input">
					<input type="submit" class="button" value="<?php _e('Search Members','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="members-filter">

				<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />

				<div class="tablenav">

				<?php if ( $wp_user_search->results_are_paged() ) : ?>
					<div class="tablenav-pages"><?php $wp_user_search->page_links(); ?></div>
				<?php endif; ?>

				<div class="alignleft actions">
				<select name="action">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>

					<optgroup label="<?php _e('Subscriptions','membership'); ?>">
						<option value="bulkaddsub"><?php _e('Add subscription','membership'); ?></option>
						<option value="bulkmovesub"><?php _e('Move subscription','membership'); ?></option>
						<option value="bulkdropsub"><?php _e('Drop subscription','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Levels','membership'); ?>">
						<option value="bulkaddlevel"><?php _e('Add level','membership'); ?></option>
						<option value="bulkmovelevel"><?php _e('Move level','membership'); ?></option>
						<option value="bulkdroplevel"><?php _e('Drop level','membership'); ?></option>
					</optgroup>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				<select name="sub_op">
					<option value=""><?php _e('Filter by subscription','membership'); ?></option>
					<?php
						$subs = $this->get_subscriptions();
						if($subs) {
							foreach($subs as $key => $sub) {
								?>
								<option value="<?php echo $sub->id; ?>" <?php if($_GET['sub_op'] == $sub->id) echo 'selected="selected"'; ?>><?php echo esc_html($sub->sub_name); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionsub" name="doactionsub" value="<?php _e('Filter'); ?>">

				<select name="level_op">
					<option value=""><?php _e('Filter by level','membership'); ?></option>
					<?php
						$levels = $this->get_membership_levels();
						if($levels) {
							foreach($levels as $key => $level) {
								?>
								<option value="<?php echo $level->id; ?>" <?php if($_GET['level_op'] == $level->id) echo 'selected="selected"'; ?>><?php echo esc_html($level->level_title); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionlevel" name="doactionlevel" value="<?php _e('Filter'); ?>">

				</div>

				<div class="alignright actions">
					<!-- <input type="button" class="button-secondary addnewlevelbutton" value="<?php _e('Add New'); ?>" name="addnewlevel"> -->
				</div>

				<br class="clear">
				</div>
				<?php if ( is_wp_error( $wp_user_search->search_errors ) ) : ?>
					<div class="error">
						<ul>
						<?php
							foreach ( $wp_user_search->search_errors->get_error_messages() as $message )
								echo "<li>$message</li>";
						?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $wp_user_search->is_search() ) : ?>
					<p><a href="?page=<?php echo $page; ?>"><?php _e('&larr; Back to All Users'); ?></a></p>
				<?php endif; ?>

				<div class="clear"></div>

				<?php
					wp_nonce_field('bulk-members');

					$columns = array(	"username" 	=> 	__('Username','membership'),
										"name" 		=> 	__('Name','membership'),
										"email" 	=> 	__('E-mail','membership'),
										"active"	=>	__('Active','membership'),
										"sub"		=>	__('Subscription','membership'),
										"level"		=>	__('Membership Level','membership')
									);

					$columns = apply_filters('members_columns', $columns);

					//$levels = $this->get_membership_levels($filter);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php

						$style = '';
						foreach ( $wp_user_search->get_results() as $userid ) {
							$user_object = new M_Membership($userid);
							$roles = $user_object->roles;
							$role = array_shift($roles);

							$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
							?>
							<tr id='user-<?php echo $user_object->ID; ?>' <?php echo $style; ?>>
								<th scope='row' class='check-column'>
									<input type='checkbox' name='users[]' id='user_<?php echo $user_object->ID; ?>' class='$role' value='<?php echo $user_object->ID; ?>' />
								</th>
								<td <?php echo $style; ?>>
									<strong><a href='<?php echo admin_url('user-edit.php?user_id=' . $user_object->ID); ?>'><?php echo $user_object->user_login; ?></a></strong>
									<?php
										$actions = array();
										$actions['id'] = "<strong>" . __('ID : ', 'membership') . $user_object->ID . "</strong>";
										$actions['edit'] = "<span class='edit'><a href='" . admin_url('user-edit.php?user_id=' . $user_object->ID) . "'>" . __('Edit', 'membership') . "</a></span>";
										if($user_object->active_member()) {
											$actions['activate'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;member_id=" . $user_object->ID . "", 'toggle-member_' . $user_object->ID) . "'>" . __('Deactivate', 'membership') . "</a></span>";
										} else {
											$actions['activate'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;member_id=" . $user_object->ID . "", 'toggle-member_' . $user_object->ID) . "'>" . __('Activate', 'membership') . "</a></span>";
										}
									?>
									<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
								</td>
								<td <?php echo $style; ?>><?php echo $user_object->first_name . " " . $user_object->last_name; ?></td>
								<td <?php echo $style; ?>><a href='mailto:<?php echo $user_object->user_email; ?>' title='<?php echo sprintf( __('e-mail: %s' ), $user_object->user_email ); ?>'><?php echo $user_object->user_email; ?></a></td>
								<td <?php echo $style; ?>>
									<?php if($user_object->active_member()) {
										echo "<strong>" . __('Active', 'membership') . "</strong>";
									} else {
										echo __('Inactive', 'membership');
									}
									?>
								</td>
								<td <?php echo $style; ?>>
									<?php
									$subs = $user_object->get_subscription_ids();
									if(!empty($subs)) {
										$rows = array();
										foreach((array) $subs as $key) {
											$sub = new M_Subscription ( $key );
											if(!empty($sub)) {
												$rows[] = $sub->sub_name();
											}
										}
										echo implode(", ", $rows);
									}

									$actions = array();
									$actions['add'] = "<span class='edit'><a href='?page={$page}&amp;action=addsub&amp;member_id={$user_object->ID}'>" . __('Add', 'membership') . "</a></span>";

									if(!empty($subs)) {
										$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movesub&amp;member_id=" . $user_object->ID . "", 'movesub-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
										$actions['drop'] = "<span class='edit delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=dropsub&amp;member_id=" . $user_object->ID . "", 'dropsub-member-' . $user_object->ID) . "'>" . __('Drop', 'membership') . "</a></span>";
									}
									?>
									<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
								</td>
								<td <?php echo $style; ?>>
									<?php
									$levels = $user_object->get_level_ids();
									if(!empty($levels)) {
										$rows = array();
										foreach((array) $levels as $key => $value) {
											$level = new M_Level ( $value->level_id );
											if(!empty($level)) {
												if((int) $value->sub_id != 0) {
													$rows[] = "<strong>" . $level->level_title() . "</strong>";
												} else {
													$rows[] = $level->level_title();
												}
											}
										}
										echo implode(", ", $rows);
									}
									$actions = array();
									$actions['add'] = "<span class='edit'><a href='?page={$page}&amp;action=addlevel&amp;member_id={$user_object->ID}'>" . __('Add', 'membership') . "</a></span>";

									if(!empty($levels)) {
										$actions['move'] = "<span class='edit'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=movelevel&amp;member_id=" . $user_object->ID . "", 'movelevel-member-' . $user_object->ID) . "'>" . __('Move', 'membership') . "</a></span>";
										$actions['drop'] = "<span class='edit delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=droplevel&amp;member_id=" . $user_object->ID . "", 'droplevel-member-' . $user_object->ID) . "'>" . __('Drop', 'membership') . "</a></span>";
									}
									?>
									<div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>

					<optgroup label="<?php _e('Subscriptions','membership'); ?>">
						<option value="bulkaddsub"><?php _e('Add subscription','membership'); ?></option>
						<option value="bulkmovesub"><?php _e('Move subscription','membership'); ?></option>
						<option value="bulkdropsub"><?php _e('Drop subscription','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Levels','membership'); ?>">
						<option value="bulkaddlevel"><?php _e('Add level','membership'); ?></option>
						<option value="bulkmovelevel"><?php _e('Move level','membership'); ?></option>
						<option value="bulkdroplevel"><?php _e('Drop level','membership'); ?></option>
					</optgroup>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">

				<select name="sub_op2">
					<option value=""><?php _e('Filter by subscription','membership'); ?></option>
					<?php
						$subs = $this->get_subscriptions();
						if($subs) {
							foreach($subs as $key => $sub) {
								?>
								<option value="<?php echo $sub->id; ?>" <?php if($_GET['sub_op2'] == $sub->id) echo 'selected="selected"'; ?>><?php echo esc_html($sub->sub_name); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionsub2" name="doactionsub2" value="<?php _e('Filter'); ?>">

				<select name="level_op2">
					<option value=""><?php _e('Filter by level','membership'); ?></option>
					<?php
						$levels = $this->get_membership_levels();
						if($levels) {
							foreach($levels as $key => $level) {
								?>
								<option value="<?php echo $level->id; ?>" <?php if($_GET['level_op2'] == $level->id) echo 'selected="selected"'; ?>><?php echo esc_html($level->level_title); ?></option>
								<?php
							}
						}
					?>
				</select>
				<input type="submit" class="button-secondary action" id="doactionlevel2" name="doactionlevel2" value="<?php _e('Filter'); ?>">

				</div>
				<div class="alignright actions">

				</div>
				<br class="clear">
				</div>

				</form>

			</div> <!-- wrap -->
			<?php

		}

		function handle_options_panel_updates() {

			global $action, $page, $wp_rewrite;

			wp_reset_vars( array('action', 'page') );

			if($action == 'updateoptions') {

				check_admin_referer('update-membership-options');

				$M_options = array();

				// Split up the membership options records into to descrete related chunks
				$M_options['strangerlevel'] = (int) $_POST['strangerlevel'];
				$M_options['freeusersubscription'] = (int) $_POST['freeusersubscription'];

				$M_options['membershipshortcodes'] = explode("\n", $_POST['membershipshortcodes']);
				$M_options['shortcodemessage'] = $_POST['shortcodemessage'];

				$M_options['protectedmessagetitle'] = $_POST['protectedmessagetitle'];
				$M_options['protectedmessage'] = $_POST['protectedmessage'];

				$M_options['page_template'] = $_POST['page_template'];
				$M_options['original_url'] = $_POST['original_url'];
				$M_options['masked_url'] = $_POST['masked_url'];

				$M_options['membershipdownloadgroups'] = explode("\n", $_POST['membershipdownloadgroups']);

				$M_options['nocontent_page'] = $_POST['nocontent_page'];
				$M_options['registration_page'] = $_POST['registration_page'];
				$M_options['registration_tos'] = $_POST['registration_tos'];

				$M_options['shortcodedefault'] = $_POST['shortcodedefault'];
				$M_options['moretagdefault'] = $_POST['moretagdefault'];

				$M_options['moretagmessage'] = $_POST['moretagmessage'];

				$M_options['paymentcurrency'] = $_POST['paymentcurrency'];

				update_option('membership_options', $M_options);

				do_action( 'membership_options_page_process' );

				// Always flush the rewrite rules
				$wp_rewrite->flush_rules();

				wp_safe_redirect( add_query_arg('msg', 1, wp_get_referer()) );

			}

		}

		function handle_options_panel() {

			global $action, $page, $M_options;

			wp_reset_vars( array('action', 'page') );

			$M_options = get_option('membership_options', array());

			$messages = array();
			$messages[1] = __('Your options have been updated.','membership');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-options-general"><br></div>
				<h2><?php _e('Edit Options','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<form action='?page=<?php echo $page; ?>' method='post'>

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='updateoptions' />

					<?php
						wp_nonce_field('update-membership-options');
					?>

					<h3><?php _e('Stranger settings','membership'); ?></h3>
					<p><?php _e('A &quot;stranger&quot; is a visitor to your website who is either not logged in, or does not have an active membership or subscription to your website.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Use membership level','membership'); ?></th>
							<td>
								<select name='strangerlevel' id='strangerlevel'>
									<option value="0"><?php _e('None - No access to content','membership'); ?></option>
								<?php
									$levels = $this->get_membership_levels();
									if($levels) {
										foreach($levels as $key => $level) {
											?>
											<option value="<?php echo $level->id; ?>" <?php if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] == $level->id) echo "selected='selected'"; ?>><?php echo esc_html($level->level_title); ?></option>
											<?php
										}
									}
								?>
								</select>
							</td>
						</tr>
					</tbody>
					</table>

					<h3><?php _e('User registration','membership'); ?></h3>
					<p><?php _e('If you have free user registration enabled on your site, select the subscription they will be assigned to initially.','membership'); ?></p>
					<p><?php _e('If you are using a paid subscription model - it is probably best to set this to &quot;none&quot;.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Use subscription','membership'); ?></th>
							<td>
								<select name='freeusersubscription' id='freeusersubscription'>
									<option value="0"><?php _e('None','membership'); ?></option>
								<?php
									$subs = $this->get_subscriptions( array('sub_status' => 'active'));
									if($subs) {
										foreach($subs as $key => $sub) {
											?>
											<option value="<?php echo $sub->id; ?>" <?php if(isset($M_options['freeusersubscription']) && $M_options['freeusersubscription'] == $sub->id) echo "selected='selected'"; ?>><?php echo esc_html($sub->sub_name); ?></option>
											<?php
										}
									}
								?>
								</select>
							</td>
						</tr>
					</tbody>
					</table>

					<h3><?php _e('Registration page','membership'); ?></h3>
					<p><?php _e('This is the page a new user will be redirected to when they want to register on your site.','membership'); ?></p>
					<p><?php _e('It can contain any content you want but <strong>must</strong> contain the [subscriptionform] shortcode in some location.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Registration page','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Select a page to use for the registration form.",'membership'); ?></em>
							</th>
							<td>
								<?php
								$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['registration_page'], 'name' => 'registration_page', 'show_option_none' => __('None'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
								echo $pages;
								?>
							</td>
						</tr>
					</tbody>
					</table>

					<h3><?php _e('Shortcode protected content','membership'); ?></h3>
					<p><?php _e('You can protect parts of a post or pages content by enclosing it in WordPress shortcodes.','membership'); ?></p>
					<p><?php _e('Create as many shortcodes as you want by entering them below, each shortcode should be on a separate line.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Shortcodes','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Place each shortcode on a new line, removing used shortcodes will leave content visible to all users/members.",'membership'); ?>
								</em>
							</th>
							<td>
								<textarea name='membershipshortcodes' id='membershipshortcodes' rows='10' cols='40'><?php
								if(!empty($M_options['membershipshortcodes'])) {
									foreach($M_options['membershipshortcodes'] as $key => $value) {
										if(!empty($value)) {
											esc_html_e(stripslashes($value)) . "\n";
										}
									}
								}
								?></textarea>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Shortcode visibility default','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Should the shortcodes above be visible or protected by default.",'membership'); ?>
								</em>
							</th>
							<td>
								<select name='shortcodedefault' id='shortcodedefault'>
									<option value="yes" <?php if(isset($M_options['shortcodedefault']) && $M_options['shortcodedefault'] == 'yes') echo "selected='selected'"; ?>><?php _e('Yes - Shortcodes are visible by default','membership'); ?></option>
									<option value="no" <?php if(isset($M_options['shortcodedefault']) && $M_options['shortcodedefault'] == 'no') echo "selected='selected'"; ?>><?php _e('No - Shortcodes are protected by default','membership'); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('No access message','membership'); ?><br/>
							<em style='font-size:smaller;'><?php _e("This is the message that is displayed when the content protected by the shortcode can't be shown.",'membership'); ?><br/>
							<?php _e("Leave blank for no message.",'membership'); ?><br/>
							<?php _e("HTML allowed.",'membership'); ?>
							</em>
							</th>
							<td>
								<textarea name='shortcodemessage' id='shortcodemessage' rows='5' cols='40'><?php esc_html_e(stripslashes($M_options['shortcodemessage'])); ?></textarea>
							</td>
						</tr>
					</tbody>
					</table>

					<h3><?php _e('Downloads / Media protection','membership'); ?></h3>
					<p><?php _e('Downloads and media files can be protected by remapping their perceived location.','membership'); ?></p>
					<p><?php _e('Note: If a user determines a files actual location on your server, there is very little we can do to prevent its download, so please be careful about giving out URLs.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Actual download URL','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("This is a system generated URL, you shouldn't need to change this.",'membership'); ?>
								</em>
							</th>
							<td>
								<?php
								 	$membershipurl = $M_options['original_url'];
									if(empty($membershipurl)) $membershipurl = membership_upload_path();
								?>
								<input type='text' name='original_url' id='original_url' value='<?php esc_attr_e($membershipurl);  ?>' class='wide' />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Masked download URL','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("This is the URL that the user will see.",'membership'); ?><br/>
								<?php _e("Change the end part to something unique.",'membership'); ?>
								</em>
							</th>
							<td>
								<?php esc_html_e(trailingslashit(get_option('home')));  ?>&nbsp;<input type='text' name='masked_url' id='masked_url' value='<?php esc_attr_e($M_options['masked_url']);  ?>' />
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e('Protected groups','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Place each download group name on a new line, removing used groups will leave content visible to all users/members.",'membership'); ?>
								</em>
							</th>
							<td>
								<textarea name='membershipdownloadgroups' id='membershipdownloadgroups' rows='10' cols='40'><?php
								if(!empty($M_options['membershipdownloadgroups'])) {
									foreach($M_options['membershipdownloadgroups'] as $key => $value) {
										if(!empty($value)) {
											esc_html_e(stripslashes($value)) . "\n";
										}
									}
								}
								?></textarea>
							</td>
						</tr>

					</tbody>
					</table>

					<h3><?php _e('Protected content message','membership'); ?></h3>
					<p><?php _e('If a post / page / content is not available to a user, this is the message or page content that will be displayed in its place.','membership'); ?></p>
					<p><?php _e('This message will only be displayed if the user has tried to access the post / page / content directly or via a link.','membership'); ?></p>
					<p><?php _e('Note: This message will override the 404 page for none members.','membership'); ?></p>


					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Protected content page','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Select a page to use for the content.",'membership'); ?><br/>
								<?php _e("Alternatively complete the content below.",'membership'); ?>
								</em>
							</th>
							<td>
								<?php
								$pages = wp_dropdown_pages(array('post_type' => 'page', 'selected' => $M_options['nocontent_page'], 'name' => 'nocontent_page', 'show_option_none' => __('None (use settings below)'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
								echo $pages;
								?><br/>
								<?php _e('Note: If you have set no content access, then the settings below will be used instead.','membership'); ?>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e('Protected Message Title','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Enter the title for the message that you want displayed when the content is not available.",'membership'); ?></em>
							</th>
							<td>
								<input type='text' name='protectedmessagetitle' id='protectedmessagetitle' value='<?php esc_attr_e(stripslashes($M_options['protectedmessagetitle']));  ?>' class='wide' />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Protected Message','membership'); ?><br/>
								<em style='font-size:smaller;'><?php _e("Enter the message that you want displayed when the content is not available.",'membership'); ?><br/>
								<?php _e("HTML allowed.",'membership'); ?>
								</em>
							</th>
							<td>
								<textarea name='protectedmessage' id='protectedmessage' rows='15' cols='40'><?php esc_html_e(stripslashes($M_options['protectedmessage'])); ?></textarea>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Use page template','membership'); ?><br/>
							<em style='font-size:smaller;'>
							<?php _e("You can choose which template from your theme to use for this message.",'membership'); ?><br/>
							<?php _e("If you don't know what this means, then leave it set as default.",'membership'); ?>
							</em>
							</th>
							<td>
								<select name="page_template" id="page_template">
								<option value='default'><?php _e('Default Template'); ?></option>
								<?php
									page_template_dropdown($M_options['page_template']);
								?>
								</select>
							</td>
						</tr>
					</tbody>
					</table>

					<h3><?php _e('More tag default','membership'); ?></h3>
					<p><?php _e('Content placed after the More tag in a post or page can be protected by setting the visibility below. This setting can be overridden within each individual level.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Show content after the More tag','membership'); ?></th>
							<td>
								<select name='moretagdefault' id='moretagdefault'>
									<option value="yes" <?php if(isset($M_options['moretagdefault']) && $M_options['moretagdefault'] == 'yes') echo "selected='selected'"; ?>><?php _e('Yes - More tag content is visible','membership'); ?></option>
									<option value="no" <?php if(isset($M_options['moretagdefault']) && $M_options['moretagdefault'] == 'no') echo "selected='selected'"; ?>><?php _e('No - More tag content not visible','membership'); ?></option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e('No access message','membership'); ?><br/>
							<em style='font-size:smaller;'><?php _e("This is the message that is displayed when the content protected by the moretag can't be shown.",'membership'); ?><br/>
							<?php _e("Leave blank for no message.",'membership'); ?><br/>
							<?php _e("HTML allowed.",'membership'); ?>
							</em>
							</th>
							<td>
								<textarea name='moretagmessage' id='moretagmessage' rows='5' cols='40'><?php esc_html_e(stripslashes($M_options['moretagmessage'])); ?></textarea>
							</td>
						</tr>
					</tbody>
					</table>

					<h3><?php _e('Payments currency','membership'); ?></h3>
					<p><?php _e('This is the currency that will be used across all gateways. Note: Some gateways have a limited number of currencies available.','membership'); ?></p>

					<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Payment currencys','membership'); ?></th>
							<td>
								<select name="paymentcurrency">
								  <?php
								  	$currency = $M_options['paymentcurrency'];
								    $sel_currency = empty($currency) ? 'USD' : $currency;
								    $currencies = array(
								          'AUD' => 'AUD - Australian Dollar',
								          'BRL' => 'BRL - Brazilian Real',
								          'CAD' => 'CAD - Canadian Dollar',
								          'CHF' => 'CHF - Swiss Franc',
								          'CZK' => 'CZK - Czech Koruna',
								          'DKK' => 'DKK - Danish Krone',
								          'EUR' => 'EUR - Euro',
								          'GBP' => 'GBP - Pound Sterling',
								          'ILS' => 'ILS - Israeli Shekel',
								          'HKD' => 'HKD - Hong Kong Dollar',
								          'HUF' => 'HUF - Hungarian Forint',
								          'JPY' => 'JPY - Japanese Yen',
								          'MYR' => 'MYR - Malaysian Ringgits',
								          'MXN' => 'MXN - Mexican Peso',
								          'NOK' => 'NOK - Norwegian Krone',
								          'NZD' => 'NZD - New Zealand Dollar',
								          'PHP' => 'PHP - Philippine Pesos',
								          'PLN' => 'PLN - Polish Zloty',
								          'SEK' => 'SEK - Swedish Krona',
								          'SGD' => 'SGD - Singapore Dollar',
								          'TWD' => 'TWD - Taiwan New Dollars',
								          'THB' => 'THB - Thai Baht',
								          'USD' => 'USD - U.S. Dollar'
								      );

										$currencies = apply_filters('membership_available_currencies', $currencies);

								      foreach ($currencies as $key => $value) {
											echo '<option value="' . esc_attr($key) . '"';
											if($key == $sel_currency) echo 'selected="selected"';
											echo '>' . esc_html($value) . '</option>' . "\n";
								      }
								  ?>
								  </select>
							</td>
						</tr>
					</tbody>
					</table>

					<?php
						do_action( 'membership_options_page' );
					?>

					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>

				</form>

			</div> <!-- wrap -->
			<?php
		}

		function default_membership_sections($sections) {

			$sections['main'] = array(	"title" => __('Main rules','membership') );

			$sections['content'] = array(	"title" => __('Content rules','membership') );

			return $sections;
		}

		function handle_level_edit_form($level_id = false, $clone = false) {

			global $page, $M_Rules, $M_SectionRules;

			if($level_id && !$clone) {
				$mlevel = new M_Level( $level_id );
				$level = $mlevel->get();
			} else {

				if($clone) {
					$mlevel = new M_Level( $level_id );
					$level = $mlevel->get();

					$level->level_title .= __(' clone','membership');
				} else {
					$level = new stdclass;
					$level->level_title = __('new level','membership');
				}
				$level->id = time() * -1;

			}

			// Get the relevant parts
			if(isset($mlevel)) {
				$positives = $mlevel->get_rules('positive');
				$negatives = $mlevel->get_rules('negative');
			}

			// Re-arrange the rules
			$rules = array(); $p = array(); $n = array();
			if(!empty($positives)) {
				foreach($positives as $positive) {
					$rules[$positive->rule_area] = maybe_unserialize($positive->rule_value);
					$p[$positive->rule_area] = maybe_unserialize($positive->rule_value);
				}
			}
			if(!empty($negatives)) {
				foreach($negatives as $negative) {
					$rules[$negative->rule_area] = maybe_unserialize($negative->rule_value);
					$n[$negative->rule_area] = maybe_unserialize($negative->rule_value);
				}
			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php echo __('Edit ','membership') . " - " . esc_html($level->level_title); ?></h2>

				<?php
				if ( isset($usemsg) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[$usemsg] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div class='level-liquid-left'>

					<div id='level-left'>
						<form action='?page=<?php echo $page; ?>' name='leveledit' method='post'>
							<input type='hidden' name='level_id' id='level_id' value='<?php echo $level->id; ?>' />

							<input type='hidden' name='beingdragged' id='beingdragged' value='' />
							<input type='hidden' name='in-positive-rules' id='in-positive-rules' value=',<?php echo implode(',', array_keys($p)); ?>' />
							<input type='hidden' name='in-negative-rules' id='in-negative-rules' value=',<?php echo implode(',', array_keys($n)); ?>' />

							<input type='hidden' name='postive-rules-order' id='postive-rules-order' value='' />
							<input type='hidden' name='negative-rules-order' id='negative-rules-order' value='' />

						<div id='edit-level' class='level-holder-wrap'>
							<div class='sidebar-name no-movecursor'>
								<h3><?php echo esc_html($level->level_title); ?></h3>
							</div>
							<div class='level-holder'>
								<div class='level-details'>
								<label for='level_title'><?php _e('Level title','management'); ?></label><br/>
								<input class='wide' type='text' name='level_title' id='level_title' value='<?php echo esc_attr($level->level_title); ?>' />
								</div>

								<?php do_action('membership_level_form_before_rules', $level->id); ?>

								<h3 class='positive'><?php _e('Positive rules','membership'); ?></h3>
								<p class='description'><?php _e('These are the areas / elements that a member of this level can access.','membership'); ?></p>
								<div id='positive-rules-holder'>

									<?php do_action('membership_level_form_before_positive_rules', $level->id); ?>

									<?php
										if(!empty($p)) {
											foreach($p as $key => $value) {

												if(isset($M_Rules[$key])) {
														$rule = new $M_Rules[$key]();

														$rule->admin_main($value);
												}
											}
										}
									?>

									<?php do_action('membership_level_form_after_positive_rules', $level->id); ?>

								</div>
								<div id='positive-rules' class='droppable-rules levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<h3 class='negative'><?php _e('Negative rules','membership'); ?></h3>
								<p class='description'><?php _e('These are the areas / elements that a member of this level doesn\'t have access to.','membership'); ?></p>
								<div id='negative-rules-holder'>

									<?php do_action('membership_level_form_before_negative_rules', $level->id); ?>

									<?php
										if(!empty($n)) {
											foreach($n as $key => $value) {
												if(isset($M_Rules[$key])) {
														$rule = new $M_Rules[$key]();

														$rule->admin_main($value);
												}
											}
										}
									?>

									<?php do_action('membership_level_form_after_negative_rules', $level->id); ?>

								</div>
								<div id='negative-rules' class='droppable-rules levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<?php do_action('membership_level_form_after_rules', $level->id); ?>

								<div class='buttons'>
									<?php
									if($level->id > 0) {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('update-' . $level->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel edit'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Update', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='updated' />
										<?php
									} else {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('add-' . $level->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Add', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='added' />
										<?php
									}
									?>
								</div>

							</div>
						</div>
						</form>
					</div>


					<div id='hiden-actions'>
					<?php

						$sections = apply_filters('membership_level_sections', array());

						foreach($sections as $key => $section) {

							if(isset($M_SectionRules[$key])) {
								foreach($M_SectionRules[$key] as $mrule => $mclass) {
									$rule = new $mclass();

									if(!array_key_exists($mrule, $rules)) {
										$rule->admin_main(false);
									}
								}
							}

						}

					?>
					</div> <!-- hidden-actions -->

				</div> <!-- level-liquid-left -->

				<div class='level-liquid-right'>
					<div class="level-holder-wrap">
						<?php

							$sections = apply_filters('membership_level_sections', array());

							foreach($sections as $key => $section) {
								?>

								<div class="sidebar-name no-movecursor">
									<h3><?php echo $section['title']; ?></h3>
								</div>
								<div class="section-holder" id="sidebar-<?php echo $key; ?>" style="min-height: 98px;">
									<ul class='levels levels-draggable'>
									<?php

										if(isset($M_SectionRules[$key])) {
											foreach($M_SectionRules[$key] as $mrule => $mclass) {
												$rule = new $mclass();

												if(!array_key_exists($mrule, $rules)) {
													$rule->admin_sidebar(false);
												} else {
													$rule->admin_sidebar(true);
												}
											}
										}

									?>
									</ul>
								</div>
								<?php
							}
						?>
					</div> <!-- level-holder-wrap -->

				</div> <!-- level-liquid-left -->

			</div> <!-- wrap -->

			<?php
		}

		function handle_levels_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}

				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			switch(addslashes($action)) {

				case 'added':	$id = (int) $_POST['level_id'];
								check_admin_referer('add-' . $id);
								if($id) {

									$level = new M_Level($id);

									if($level->add()) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4,  'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 4,  'admin.php?page=' . $page ) );
								}

								break;
				case 'updated':	$id = (int) $_POST['level_id'];
								check_admin_referer('update-' . $id);
								if($id) {

									$level = new M_Level($id);

									if($level->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 3,  'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5,  'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 5,  'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];

									check_admin_referer('delete-level_' . $level_id);

									$level = new M_Level($level_id);

									if($level->delete($level_id)) {
										wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'toggle':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];

									check_admin_referer('toggle-level_' . $level_id);

									$level = new M_Level($level_id);

									if( $level->toggleactivation() ) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-levels');
								foreach($_GET['levelcheck'] AS $value) {
									if(is_numeric($value)) {
										$level_id = (int) $value;

										$level = new M_Level($level_id);

										$level->delete();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-levels');
								foreach($_GET['levelcheck'] AS $value) {
									if(is_numeric($value)) {
										$level_id = (int) $value;

										$level = new M_Level($level_id);

										$level->toggleactivation();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

			}

		}

		function handle_levels_panel() {

			global $action, $page;

			switch(addslashes($action)) {

				case 'edit':	if(isset($_GET['level_id'])) {
									$level_id = (int) $_GET['level_id'];
									$this->handle_level_edit_form($level_id);
									return; // So we don't see the rest of this page
								}
								break;

				case 'clone':	if(isset($_GET['clone_id'])) {
									$level_id = (int) $_GET['clone_id'];
									$this->handle_level_edit_form($level_id, true);
									return; // So we don't see the rest of this page
								}
								break;
			}

			$filter = array();

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			if(isset($_GET['level_id'])) {
				$filter['level_id'] = stripslashes($_GET['level_id']);
			}

			if(isset($_GET['order_by'])) {
				$filter['order_by'] = stripslashes($_GET['order_by']);
			}

			$messages = array();
			$messages[1] = __('Membership Level added.');
			$messages[2] = __('Membership Level deleted.');
			$messages[3] = __('Membership Level updated.');
			$messages[4] = __('Membership Level not added.');
			$messages[5] = __('Membership Level not updated.');
			$messages[6] = __('Membership Level not deleted.');

			$messages[7] = __('Membership Level activation toggled.');
			$messages[8] = __('Membership Level activation not toggled.');

			$messages[9] = __('Membership Levels updated.');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Edit Membership Levels','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />
					<label for="membership-search-input" class="screen-reader-text"><?php _e('Search Memberships','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="membership-search-input">
					<input type="submit" class="button" value="<?php _e('Search Memberships','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
				<option value="delete"><?php _e('Delete'); ?></option>
				<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				<select name="level_id">
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all Levels','membership'); ?></option>
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active Levels','membership'); ?></option>
				<option <?php if(isset($_GET['level_id']) && addslashes($_GET['level_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive Levels','membership'); ?></option>

				</select>

				<select name="order_by">
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_id') echo "selected='selected'"; ?> value="order_id"><?php _e('Order by Level ID','membership'); ?></option>
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_name') echo "selected='selected'"; ?> value="order_name"><?php _e('Order by Level Name','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary" value="<?php _e('Filter'); ?>" id="post-query-submit">

				</div>

				<div class="alignright actions">
					<input type="button" class="button-secondary addnewlevelbutton" value="<?php _e('Add New'); ?>" name="addnewlevel">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-levels');

					$columns = array(	"name" 		=> 	__('Level Name','membership'),
										"active"	=>	__('Active','membership'),
										"users"		=>	__('Users','membership')
									);

					$columns = apply_filters('membership_levelcolumns', $columns);

					$levels = $this->get_membership_levels($filter);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($levels) {
							foreach($levels as $key => $level) {
								?>
								<tr valign="middle" class="alternate" id="level-<?php echo $level->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $level->id; ?>" name="levelcheck[]"></th>
									<td class="column-name">
										<strong><a title="Edit “<?php echo esc_attr($level->level_title); ?>”" href="?page=<?php echo $page; ?>&amp;action=edit&amp;level_id=<?php echo $level->id; ?>" class="row-title"><?php echo esc_html($level->level_title); ?></a></strong>
										<?php
											$actions = array();
											$actions['id'] = "<strong>" . __('ID : ', 'membership') . $level->id . "</strong>";
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;level_id=" . $level->id . "'>" . __('Edit') . "</a></span>";
											if($level->level_active == 0) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;level_id=" . $level->id . "", 'toggle-level_' . $level->id) . "'>" . __('Activate') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;level_id=" . $level->id . "", 'toggle-level_' . $level->id) . "'>" . __('Deactivate') . "</a></span>";
											}
											$actions['clone'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=clone&amp;clone_id=" . $level->id . "'>" . __('Clone') . "</a></span>";

											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;level_id=" . $level->id . "", 'delete-level_' . $level->id) . "'>" . __('Delete') . "</a></span>";

										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											switch($level->level_active) {
												case 0:	echo __('Inactive', 'membership');
														break;
												case 1:	echo "<strong>" . __('Active', 'membership') . "</strong>";
														break;
											}
										?>
									</td>
									<td class="column-users">
										<strong>
											<?php echo $this->count_on_level( $level->id ); ?>
										</strong>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Membership levels where found, click above to add one.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="delete"><?php _e('Delete'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
				</div>
				<div class="alignright actions">
					<input type="button" class="button-secondary addnewlevelbutton" value="<?php _e('Add New'); ?>" name="addnewlevel2">
				</div>
				<br class="clear">
				</div>



				</form>

			</div> <!-- wrap -->
			<?php
		}

		function handle_sub_edit_form($sub_id = false, $clone = false) {

			global $page;

			$msub = new M_Subscription( $sub_id );
			if($sub_id && !$clone) {
				$sub = $msub->get();
			} else {
				if($clone) {
					$sub = $msub->get();
					$sub->sub_name .= __(' clone','membership');
				} else {
					$sub = new stdclass;
					$sub->sub_name = __('new subscription','membership');
				}
				$sub->id = time() * -1;

			}

			// Get the relevant parts
			if(isset($msub)) {
				$levels = $msub->get_levels();
			}

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<?php
					if($sub->id < 0) {
						?>
						<h2><?php echo __('Add ','membership') . " - " . esc_html($sub->sub_name); ?></h2>
						<?php
					} else {
						?>
						<h2><?php echo __('Edit ','membership') . " - " . esc_html($sub->sub_name); ?></h2>
						<?php
					}
				?>

				<?php
				if ( isset($usemsg) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[$usemsg] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<div class='sub-liquid-left'>

					<div id='sub-left'>
						<form action='?page=<?php echo $page; ?>' name='subedit' method='post'>
							<input type='hidden' name='sub_id' id='sub_id' value='<?php echo $sub->id; ?>' />

						<div id='edit-sub' class='sub-holder-wrap'>
							<div class='sidebar-name no-movecursor'>
								<h3><?php echo esc_html($sub->sub_name); ?></h3>
							</div>
							<div class='sub-holder'>
								<div class='sub-details'>
								<label for='sub_name'><?php _e('Subscription name','management'); ?></label>
								<input class='wide' type='text' name='sub_name' id='sub_name' value='<?php echo esc_attr($sub->sub_name); ?>' />

								<label for='sub_name'><?php _e('Subscription description','management'); ?></label>
								<textarea class='wide' name='sub_description' id='sub_description'><?php echo esc_html($sub->sub_description); ?></textarea>

								<?php do_action('membership_subscription_form_after_details', $sub->id); ?>

								</div>

								<?php do_action('membership_subscription_form_before_levels', $sub->id); ?>

								<h3><?php _e('Membership levels','membership'); ?></h3>
								<p class='description'><?php _e('These are the levels that are part of this subscription and the order a user will travel through them.','membership'); ?></p>
								<div id='membership-levels-start'>
									<div id="main-start" class="sub-operation" style="display: block;">
											<h2 class="sidebar-name">Starting Point</h2>
											<div class="inner-operation">
												<p class='description'><?php _e('A new signup for this subscription will start here and immediately pass to the next membership level listed below.','membership'); ?></p>
											</div>
									</div>
								</div>

								<ul id='membership-levels-holder'>
									<?php do_action('membership_subscription_form_before_level_list', $sub->id); ?>
									<?php
										$msub->sub_details();
									?>
									<?php do_action('membership_subscription_form_after_level_list', $sub->id); ?>
								</ul>
								<div id='membership-levels' class='droppable-levels levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<?php
									// Hidden fields
								?>
								<input type='hidden' name='beingdragged' id='beingdragged' value='' />
								<input type='hidden' name='level-order' id='level-order' value=',<?php echo implode(',', $msub->levelorder); ?>' />

								<?php do_action('membership_subscription_form_after_levels', $sub->id); ?>

								<div class='buttons'>
									<?php
									if($sub->id > 0) {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('update-' . $sub->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel edit'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Update', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='updated' />
										<?php
									} else {
										wp_original_referer_field(true, 'previous'); wp_nonce_field('add-' . $sub->id);
										?>
										<a href='?page=<?php echo $page; ?>' class='cancellink' title='Cancel add'><?php _e('Cancel', 'membership'); ?></a>
										<input type='submit' value='<?php _e('Add', 'membership'); ?>' class='button' />
										<input type='hidden' name='action' value='added' />
										<?php
									}
									?>
								</div>

							</div>
						</div>
						</form>
					</div>


					<div id='hiden-actions'>

						<div id='template-holder'>
							<?php
								$msub->sub_template();
							?>
						</div>

					</div> <!-- hidden-actions -->

				</div> <!-- sub-liquid-left -->

				<div class='sub-liquid-right'>
					<div class="sub-holder-wrap">
								<div class="sidebar-name no-movecursor">
									<h3><?php _e('Membership levels','membership'); ?></h3>
								</div>
								<div class="level-holder" id="sidebar-levels" style="min-height: 98px;">
									<ul class='subs subs-draggable'>
									<?php
										$levels = $this->get_membership_levels();
										foreach( (array) $levels as $key => $level) {
										?>
											<li class='level-draggable' id='level-<?php echo $level->id; ?>'>
												<div class='action action-draggable'>
													<div class='action-top'>
														<?php echo esc_html($level->level_title); ?>
													</div>
												</div>
											</li>
										<?php
											}
									?>
									</ul>
								</div>
					</div> <!-- sub-holder-wrap -->

				</div> <!-- sub-liquid-right -->

			</div> <!-- wrap -->

			<?php
		}

		function handle_subscriptions_updates() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'delete' || addslashes($_GET['action2']) == 'delete') {
					$action = 'bulk-delete';
				}

				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}

				if(addslashes($_GET['action']) == 'togglepublic' || addslashes($_GET['action2']) == 'togglepublic') {
					$action = 'bulk-togglepublic';
				}
			}

			switch(addslashes($action)) {

				case 'added':	$id = (int) $_POST['sub_id'];
								check_admin_referer('add-' . $id);

								if($id) {
									$sub = new M_Subscription( $id );

									if($sub->add()) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 4, 'admin.php?page=' . $page ) );
								}

								break;
				case 'updated':	$id = (int) $_POST['sub_id'];
								check_admin_referer('update-' . $id);
								if($id) {
									$sub = new M_Subscription( $id );

									if($sub->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 3, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5, 'admin.php?page=' . $page ) );
									}
								} else {
									wp_safe_redirect( add_query_arg( 'msg', 5, 'admin.php?page=' . $page ) );
								}
								break;

				case 'delete':	if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('delete-sub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									if($sub->delete()) {
										wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
									}

								}
								break;

				case 'toggle':	if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('toggle-sub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									if($sub->toggleactivation()) {
										wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 8, wp_get_referer() ) );
									}

								}
								break;

				case 'togglepublic':
								if(isset($_GET['sub_id'])) {
									$sub_id = (int) $_GET['sub_id'];

									check_admin_referer('toggle-pubsub_' . $sub_id);

									$sub = new M_Subscription( $sub_id );

									if($sub->togglepublic()) {
										wp_safe_redirect( add_query_arg( 'msg', 9, wp_get_referer() ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
									}

								}
								break;

				case 'bulk-delete':
								check_admin_referer('bulk-subscriptions');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;

										$sub = new M_Subscription( $sub_id );

										$sub->delete();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
								break;

				case 'bulk-toggle':
								check_admin_referer('bulk-subscriptions');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;

										$sub = new M_Subscription( $sub_id );

										$sub->toggleactivation();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
								break;

				case 'bulk-togglepublic':
								check_admin_referer('bulk-subscriptions');
								foreach($_GET['subcheck'] AS $value) {
									if(is_numeric($value)) {
										$sub_id = (int) $value;

										$sub = new M_Subscription( $sub_id );

										$sub->togglepublic();
									}
								}

								wp_safe_redirect( add_query_arg( 'msg', 9, wp_get_referer() ) );
								break;

			}

		}

		function handle_subs_panel() {

			// Subscriptions panel
			global $action, $page;

			$filter = array();

			if($action == 'edit') {
				if(isset($_GET['sub_id'])) {
					$sub_id = (int) $_GET['sub_id'];
					$this->handle_sub_edit_form($sub_id);
					return; // So we don't see the rest of this page
				}
			}

			if(isset($_GET['s'])) {
				$s = stripslashes($_GET['s']);
				$filter['s'] = $s;
			} else {
				$s = '';
			}

			if(isset($_GET['sub_status'])) {
				$filter['sub_status'] = stripslashes($_GET['sub_status']);
			}

			if(isset($_GET['order_by'])) {
				$filter['order_by'] = stripslashes($_GET['order_by']);
			}

			$messages = array();
			$messages[1] = __('Subscription added.');
			$messages[2] = __('Subscription deleted.');
			$messages[3] = __('Subscription updated.');
			$messages[4] = __('Subscription not added.');
			$messages[5] = __('Subscription not updated.');
			$messages[6] = __('Subscription not deleted.');

			$messages[7] = __('Subscription activation toggled.');
			$messages[8] = __('Subscription activation not toggled.');

			$messages[9] = __('Subscriptions updated.');

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-link-manager"><br></div>
				<h2><?php _e('Edit Subscription Plans','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" class="search-form">
				<p class="search-box">
					<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />
					<label for="subscription-search-input" class="screen-reader-text"><?php _e('Search Memberships','membership'); ?>:</label>
					<input type="text" value="<?php echo esc_attr($s); ?>" name="s" id="subscription-search-input">
					<input type="submit" class="button" value="<?php _e('Search Subscriptions','membership'); ?>">
				</p>
				</form>

				<br class='clear' />

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
				<option value="delete"><?php _e('Delete'); ?></option>
				<option value="toggle"><?php _e('Toggle activation'); ?></option>
				<option value="togglepublic"><?php _e('Toggle public status'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				<select name="sub_status">
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'public') echo "selected='selected'"; ?> value="public"><?php _e('View public subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_status']) && addslashes($_GET['sub_id']) == 'private') echo "selected='selected'"; ?> value="private"><?php _e('View private subscriptions','membership'); ?></option>
				</select>

				<select name="order_by">
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_id') echo "selected='selected'"; ?> value="order_id"><?php _e('Order by subscription ID','membership'); ?></option>
				<option <?php if(isset($_GET['order_by']) && addslashes($_GET['order_by']) == 'order_name') echo "selected='selected'"; ?> value="order_name"><?php _e('Order by subscription name','membership'); ?></option>
				</select>
				<input type="submit" class="button-secondary" value="<?php _e('Filter'); ?>" id="post-query-submit">

				</div>

				<div class="alignright actions">
					<input type="button" class="button-secondary addnewsubbutton" value="<?php _e('Add New'); ?>" name="addnewlevel">
				</div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-subscriptions');

					$columns = array(	"name" 		=> 	__('Subscription Name','membership'),
										"active"	=>	__('Active','membership'),
										"public"	=>	__('Public','membership'),
										"users"		=>	__('Users','membership')
									);

					$columns = apply_filters('subscription_columns', $columns);

					$subs = $this->get_subscriptions($filter);

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($subs) {
							foreach($subs as $key => $sub) {
								?>
								<tr valign="middle" class="alternate" id="sub-<?php echo $sub->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $sub->id; ?>" name="subcheck[]"></th>
									<td class="column-name">
										<strong><a title="Edit “<?php echo esc_attr($sub->sub_name); ?>”" href="?page=<?php echo $page; ?>&amp;action=edit&amp;sub_id=<?php echo $sub->id; ?>" class="row-title"><?php echo esc_html($sub->sub_name); ?></a></strong>
										<?php
											$actions = array();
											$actions['id'] = "<strong>" . __('ID : ', 'membership') . $sub->id . "</strong>";
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;sub_id=" . $sub->id . "'>" . __('Edit') . "</a></span>";
											if($sub->sub_active == 0) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;sub_id=" . $sub->id . "", 'toggle-sub_' . $sub->id) . "'>" . __('Activate') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;sub_id=" . $sub->id . "", 'toggle-sub_' . $sub->id) . "'>" . __('Deactivate') . "</a></span>";
											}
											if($sub->sub_public == 0) {
												$actions['public'] = "<span class='edit makeprivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=togglepublic&amp;sub_id=" . $sub->id . "", 'toggle-pubsub_' . $sub->id) . "'>" . __('Make public') . "</a></span>";
											} else {
												$actions['public'] = "<span class='edit makepublic'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=togglepublic&amp;sub_id=" . $sub->id . "", 'toggle-pubsub_' . $sub->id) . "'>" . __('Make private') . "</a></span>";
											}
											$actions['delete'] = "<span class='delete'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=delete&amp;sub_id=" . $sub->id . "", 'delete-sub_' . $sub->id) . "'>" . __('Delete') . "</a></span>";

										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											switch($sub->sub_active) {
												case 0:	echo __('Inactive', 'membership');
														break;
												case 1:	echo "<strong>" . __('Active', 'membership') . "</strong>";
														break;
											}
										?>
									</td>
									<td class="column-public">
										<?php
											switch($sub->sub_public) {
												case 0:	echo __('Private', 'membership');
														break;
												case 1:	echo "<strong>" . __('Public', 'membership') . "</strong>";
														break;
											}
										?>
									</td>
									<td class="column-users">
										<strong>
											<?php echo $this->count_on_sub( $sub->id ); ?>
										</strong>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Subscriptions where found, click above to add one.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="delete"><?php _e('Delete'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
				</div>
				<div class="alignright actions">
					<input type="button" class="button-secondary addnewsubbutton" value="<?php _e('Add New'); ?>" name="addnewlevel2">
				</div>
				<br class="clear">
				</div>



				</form>

			</div> <!-- wrap -->
			<?php

		}

		function handle_gateways_panel_updates() {

			global $action, $page, $M_Gateways;

			wp_reset_vars( array('action', 'page') );

			if(isset($_GET['doaction']) || isset($_GET['doaction2'])) {
				if(addslashes($_GET['action']) == 'toggle' || addslashes($_GET['action2']) == 'toggle') {
					$action = 'bulk-toggle';
				}
			}

			switch(addslashes($action)) {

				case 'deactivate':	$key = addslashes($_GET['gateway']);
									if(isset($M_Gateways[$key])) {
										if($M_Gateways[$key]->deactivate()) {
											wp_safe_redirect( add_query_arg( 'msg', 5, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 6, wp_get_referer() ) );
										}
									}
									break;

				case 'activate':	$key = addslashes($_GET['gateway']);
									if(isset($M_Gateways[$key])) {
										if($M_Gateways[$key]->activate()) {
											wp_safe_redirect( add_query_arg( 'msg', 3, wp_get_referer() ) );
										} else {
											wp_safe_redirect( add_query_arg( 'msg', 4, wp_get_referer() ) );
										}
									}
									break;

				case 'bulk-toggle':
									check_admin_referer('bulk-gateways');
									foreach($_GET['gatewaycheck'] AS $key) {
										if(isset($M_Gateways[$key])) {

											$M_Gateways[$key]->toggleactivation();

										}
									}

									wp_safe_redirect( add_query_arg( 'msg', 7, wp_get_referer() ) );
									break;

				case 'updated':		$gateway = addslashes($_POST['gateway']);
									check_admin_referer('updated-' . $gateway);

									if($M_Gateways[$gateway]->update()) {
										wp_safe_redirect( add_query_arg( 'msg', 1, 'admin.php?page=' . $page ) );
									} else {
										wp_safe_redirect( add_query_arg( 'msg', 2, 'admin.php?page=' . $page ) );
									}

									break;

			}

		}

		function handle_gateways_panel() {

			global $action, $page, $M_Gateways;

			wp_reset_vars( array('action', 'page') );

			switch(addslashes($action)) {

				case 'edit':	if(isset($M_Gateways[addslashes($_GET['gateway'])])) {
									$M_Gateways[addslashes($_GET['gateway'])]->settings();
								}
								return; // so we don't show the list below
								break;

				case 'transactions':
								if(isset($M_Gateways[addslashes($_GET['gateway'])])) {
									$M_Gateways[addslashes($_GET['gateway'])]->transactions();
								}
								return; // so we don't show the list below
								break;

			}


			$messages = array();
			$messages[1] = __('Gateway updated.');
			$messages[2] = __('Gateway not updated.');

			$messages[3] = __('Gateway activated.');
			$messages[4] = __('Gateway not activated.');

			$messages[5] = __('Gateway deactivated.');
			$messages[6] = __('Gateway not deactivated.');

			$messages[7] = __('Gateway activation toggled.');

			?>
			<div class='wrap'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php _e('Edit Gateways','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				?>

				<form method="get" action="?page=<?php echo esc_attr($page); ?>" id="posts-filter">

				<input type='hidden' name='page' value='<? echo esc_attr($page); ?>' />

				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
				<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
				<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">

				</div>

				<div class="alignright actions"></div>

				<br class="clear">
				</div>

				<div class="clear"></div>

				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-gateways');

					$columns = array(	"name" 		=> 	__('Gateway Name','membership'),
										"active"	=>	__('Active','membership'),
										"transactions" => __('Transactions','membership')
									);

					$columns = apply_filters('membership_levelcolumns', $columns);

					$gateways = apply_filters('M_gateways_list', array());

					$active = get_option('M_active_gateways', array());

				?>

				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
					<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</thead>

					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
					?>
					</tr>
					</tfoot>

					<tbody>
						<?php
						if($gateways) {
							foreach($gateways as $key => $gateway) {

								if(!isset($M_Gateways[$key])) {
									continue;
								}

								?>
								<tr valign="middle" class="alternate" id="gateway-<?php echo $level->id; ?>">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($key); ?>" name="gatewaycheck[]"></th>
									<td class="column-name">
										<strong><a title="Edit <?php echo esc_attr($gateway); ?>" href="?page=<?php echo $page; ?>&amp;action=edit&amp;gateway=<?php echo $key; ?>" class="row-title"><?php echo esc_html($gateway); ?></a></strong>
										<?php
											$actions = array();
											$actions['edit'] = "<span class='edit'><a href='?page=" . $page . "&amp;action=edit&amp;gateway=" . $key . "'>" . __('Settings') . "</a></span>";

											if(array_key_exists($key, $active)) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=deactivate&amp;gateway=" . $key . "", 'toggle-gateway_' . $key) . "'>" . __('Deactivate') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=activate&amp;gateway=" . $key . "", 'toggle-gateway_' . $key) . "'>" . __('Activate') . "</a></span>";
											}
										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											if(array_key_exists($key, $active)) {
												echo "<strong>" . __('Active', 'membership') . "</strong>";
											} else {
												echo __('Inactive', 'membership');
											}
										?>
									</td>
									<td class="column-transactions">
										<a href='?page=<?php echo $page; ?>&amp;action=transactions&amp;gateway=<?php echo $key; ?>'><?php _e('View transactions','membership'); ?></a>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Payment gateways where found for this install.','membership'); ?></td>
						    </tr>
							<?php
						}
						?>

					</tbody>
				</table>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action2">
					<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					<option value="toggle"><?php _e('Toggle activation'); ?></option>
				</select>
				<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
				</div>
				<div class="alignright actions"></div>
				<br class="clear">
				</div>

				</form>

			</div> <!-- wrap -->
			<?php

		}

		function handle_profile_member_page() {
			?>
			<div class='wrap'>
				<div class="icon32" id="icon-users"><br></div>
				<h2><?php _e('Membership details','membership'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}

				?>



			</div> <!-- wrap -->
			<?php
		}

		// Media extension options
		/*
		add_filter('attachment_fields_to_edit', array(&$this, 'add_media_protection_settings'), 99, 2);
		add_filter('attachment_fields_to_save', array(&$this, 'save_media_protection_settings'), 99, 2);
		*/
		function add_media_protection_settings($fields, $post) {

			global $M_options;

			$protected = get_post_meta($post->ID, '_membership_protected_content_group', true);
			if(empty($protected)) {
				$protected = 'no';
			}

			$html = "<select name='attachments[" . $post->ID . "][protected-content]'>";

			$html .= "<option value='no'";
			$html .= ">" . __('None', 'membership') . "</option>";

			if(!empty($M_options['membershipdownloadgroups'])) {
				foreach($M_options['membershipdownloadgroups'] as $key => $value) {
					if(!empty($value)) {
						$html .= "<option value='" . esc_attr(trim(stripslashes($value))) . "'";
						if($protected == esc_attr(trim(stripslashes($value)))) {
							$html .= " selected='selected'";
						}
						$html .= ">" . esc_html(trim(stripslashes($value))) . "</option>";
					}
				}
			}
			$html .= "</select>";

			$fields['media-protected-content'] = array(
				'label' 	=> __('Protected content group', 'membership'),
				'input' 	=> 'html',
				'html' 		=> $html,
				'helps'     => __('Is this an item you may want to restrict access to?', 'membership')
			);
			return $fields;
		}

		function save_media_protection_settings($post, $attachment) {
			$key = "protected-content";
			if ( empty( $attachment[$key] ) || addslashes( $attachment[$key] ) == 'no') {
				delete_post_meta($post['ID'], '_membership_protected_content_group'); // delete any residual metadata from a free-form field (as inserted below)
			} else // free-form text was entered, insert postmeta with credit
				update_post_meta($post['ID'], '_membership_protected_content_group', $attachment['protected-content']); // insert 'media-credit' metadata field for image with free-form text
			return $post;
		}

		// Fake shortcode function for administration area - public class has the proper processing function
		function do_fake_shortcode($atts, $content = null, $code = "") {

			global $M_options;

			return $M_options['shortcodemessage'];

		}
		// Database actions

		function update_levelcounts() {

			$sql = $this->db->prepare( "SELECT level_id, count(*) AS number FROM {$this->membership_relationships} WHERE level_id != 0 GROUP BY level_id" );

			$this->db->update( $this->membership_levels, array('level_count' => 0), array() );

			$levels = $this->db->get_results($sql);
			if($levels) {
				foreach($levels as $key => $level) {
					$this->db->update( $this->membership_levels, array('level_count' => $level->number), array('id' => $level->level_id) );
				}
			}

		}

		function update_subcounts() {

			$sql = $this->db->prepare( "SELECT sub_id, count(*) AS number FROM {$this->membership_relationships} WHERE sub_id != 0 GROUP BY sub_id" );

			$this->db->update( $this->subscriptions, array('sub_count' => 0), array() );

			$subs = $this->db->get_results($sql);
			if($subs) {
				foreach($subs as $key => $sub) {
					$this->db->update( $this->subscriptions, array('sub_count' => $sub->number), array('id' => $sub->sub_id) );
				}
			}
		}

		function get_membership_levels($filter = false) {

			if($filter) {
				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "level_title LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['level_id'])) {
					switch($filter['level_id']) {

						case 'active':		$where[] = "level_active = 1";
											break;
						case 'inactive':	$where[] = "level_active = 0";
											break;

					}
				}

				if(isset($filter['order_by'])) {
					switch($filter['order_by']) {

						case 'order_id':	$orderby[] = 'id ASC';
											break;
						case 'order_name':	$orderby[] = 'level_title ASC';
											break;

					}
				}

			}

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_levels}");

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);


		}

		//subscriptions

		function get_subscriptions($filter = false) {

			if($filter) {

				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "sub_name LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['sub_status'])) {
					switch($filter['sub_status']) {

						case 'active':		$where[] = "sub_active = 1";
											break;
						case 'inactive':	$where[] = "sub_active = 0";
											break;
						case 'public':		$where[] = "sub_public = 1";
											break;
						case 'private':		$where[] = "sub_public = 0";
											break;

					}
				}

				if(isset($filter['order_by'])) {
					switch($filter['order_by']) {

						case 'order_id':	$orderby[] = 'id ASC';
											break;
						case 'order_name':	$orderby[] = 'sub_name ASC';
											break;

					}
				}

			}

			$sql = $this->db->prepare( "SELECT * FROM {$this->subscriptions}");

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			if(!empty($orderby)) {
				$sql .= " ORDER BY " . implode(', ', $orderby);
			}

			return $this->db->get_results($sql);


		}

		function get_subscriptions_and_levels($filter = false) {

			if($filter) {

				$where = array();
				$orderby = array();

				if(isset($filter['s'])) {
					$where[] = "sub_name LIKE '%" . mysql_real_escape_string($filter['s']) . "%'";
				}

				if(isset($filter['sub_status'])) {
					switch($filter['sub_status']) {

						case 'active':		$where[] = "sub_active = 1";
											break;
						case 'inactive':	$where[] = "sub_active = 0";
											break;
						case 'public':		$where[] = "sub_public = 1";
											break;
						case 'private':		$where[] = "sub_public = 0";
											break;

					}
				}

			}

			$sql = $this->db->prepare( "SELECT s.id as sub_id, ml.id as level_id, s.*, ml.*, sl.level_order FROM {$this->subscriptions} AS s, {$this->subscriptions_levels} AS sl, {$this->membership_levels} AS ml");

			if(!empty($where)) {
				$sql .= " WHERE " . implode(' AND ', $where);
			}

			$sql .= $this->db->prepare( " AND s.id = sl.sub_id AND sl.level_id = ml.id ORDER BY s.id ASC, sl.level_order ASC " );

			return $this->db->get_results($sql);

		}

		function count_on_level( $level_id ) {

			$sql = $this->db->prepare( "SELECT count(*) as levelcount FROM {$this->membership_relationships} WHERE level_id = %d", $level_id );

			return $this->db->get_var( $sql );

		}

		function count_on_sub( $sub_id ) {

			$sql = $this->db->prepare( "SELECT count(*) as levelcount FROM {$this->membership_relationships} WHERE sub_id = %d", $sub_id );

			return $this->db->get_var( $sql );

		}

		// Rewrites
		function add_rewrites($wp_rewrite) {

			global $M_options;

			// This function adds in the api rewrite rules
			// Note the addition of the namespace variable so that we know these are vent based
			// calls
			$new_rules = array();

			if(!empty($M_options['masked_url'])) {
				$new_rules[trailingslashit($M_options['masked_url']) . '(.+)'] = 'index.php?protectedfile=' . $wp_rewrite->preg_index(1);
			}

			$new_rules['paymentreturn/(.+)'] = 'index.php?paymentgateway=' . $wp_rewrite->preg_index(1);

			$new_rules = apply_filters('M_rewrite_rules', $new_rules);

		  	$wp_rewrite->rules = array_merge($new_rules, $wp_rewrite->rules);

			return $wp_rewrite;
		}

		function add_queryvars($vars) {
			if(!in_array('feedkey',$vars)) $vars[] = 'feedkey';
			if(!in_array('protectedfile',$vars)) $vars[] = 'protectedfile';
			if(!in_array('paymentgateway',$vars)) $vars[] = 'paymentgateway';

			return $vars;
		}

		// Profile
		function add_profile_feed_key($profileuser) {

			$id = $profileuser->ID;

			$member = new M_Membership($id);

			if($member->is_member()) {
				$key = get_usermeta($id, '_membership_key');

				if(empty($key)) {
					$key = md5($id . $profileuser->user_pass . time());
					update_usermeta($id, '_membership_key', $key);
				}

				?>
				<h3><?php _e('Membership key'); ?></h3>

				<table class="form-table">
				<tr>
					<th><label for="description"><?php _e('Membership key'); ?></label></th>
					<td><?php esc_html_e($key); ?>
						<br />
					<span class="description"><?php _e('This key is used to give you access the the members RSS feed, keep it safe and secret.'); ?></span></td>
				</tr>
				</table>
				<?php
			}


		}

		function update_membershipadmin_capability($user_id) {

			$user = new WP_User( $user_id );

			if(!empty($_POST['membershipadmin']) && $_POST['membershipadmin'] == 'yes') {
				$user->add_cap('membershipadmin');
			} else {
				$user->remove_cap('membershipadmin');
			}

		}

		function add_membershipadmin_capability($profileuser) {

			$id = $profileuser->ID;

			?>
			<h3><?php _e('Membership Administration'); ?></h3>

			<table class="form-table">
			<tr>
				<th><label for="description"><?php _e('Membership Administration'); ?></label></th>
				<td>
				<input type='checkbox' name='membershipadmin' value='yes' <?php if($profileuser->has_cap('membershipadmin')) echo "checked='checked'"; ?>/>
				&nbsp;
				<span class="description"><?php _e('This user has access to administer the Membership system.'); ?></span></td>
			</tr>
			</table>
			<?php


		}

	}

}

?>