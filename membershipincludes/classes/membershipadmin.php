<?php
if(!class_exists('membershipadmin')) {

	class membershipadmin {

		var $build = 1;
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
				$this->$table = $wpdb->prefix . $table;
			}

			// Add administration actions
			add_action('init', array(&$this, 'initialiseplugin'));

			add_action('admin_menu', array(&$this, 'add_admin_menu'));

			// Header actions
			add_action('load-toplevel_page_membership', array(&$this, 'add_admin_header_membership'));
			add_action('load-membership_page_members', array(&$this, 'add_admin_header_members'));
			add_action('load-membership_page_membershiplevels', array(&$this, 'add_admin_header_membershiplevels'));
			add_action('load-membership_page_membershipsubs', array(&$this, 'add_admin_header_membershipsubs'));
			add_action('load-membership_page_membershipgateways', array(&$this, 'add_admin_header_membershipgateways'));
			add_action('load-membership_page_membershipoptions', array(&$this, 'add_admin_header_membershipoptions'));

			add_filter('membership_level_sections', array(&$this, 'default_membership_sections'));

		}

		function membershipadmin() {
			$this->__construct();
		}

		function initialiseplugin() {

		}

		function add_admin_menu() {

			global $menu;

			// Add the menu page
			add_menu_page(__('Membership','membership'), __('Membership','membership'), 'manage_options',  'membership', array(&$this,'handle_membership_panel'), plugins_url('membership/membershipincludes/images/members.png'));

			// Add the sub menu
			add_submenu_page('membership', __('Members','membership'), __('Edit Members','membership'), 'manage_options', "members", array(&$this,'handle_members_panel'));

			add_submenu_page('membership', __('Membership Levels','membership'), __('Edit Levels','membership'), 'manage_options', "membershiplevels", array(&$this,'handle_levels_panel'));
			add_submenu_page('membership', __('Membership Subscriptions','membership'), __('Edit Subscriptions','membership'), 'manage_options', "membershipsubs", array(&$this,'handle_subs_panel'));
			add_submenu_page('membership', __('Membership Gateways','membership'), __('Edit Gateways','membership'), 'manage_options', "membershipgateways", array(&$this,'handle_gateways_panel'));

			add_submenu_page('membership', __('Membership Options','membership'), __('Edit Options','membership'), 'manage_options', "membershipoptions", array(&$this,'handle_options_panel'));

			// Move the menu to the top of the page
			foreach($menu as $key => $value) {
				if($value[2] == 'membership') {
					if(!isset($menu[-10])) {
						$menu[-10] = $menu[$key];
						$menu[-11] = $menu[1];

						// CSS style for the menu
						$menu[-10][4] .= ' menu-top-first menu-top-last';

						unset($menu[$key]);
						break;
					}

				}
			}

		}

		// Add admin headers

		function add_admin_header_core() {

		}

		function add_admin_header_membership() {

			$this->add_admin_header_core();
		}

		function add_admin_header_membershiplevels() {

			$this->add_admin_header_core();

			wp_enqueue_script('levelsjs', plugins_url('/membership/membershipincludes/js/levels.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);
			wp_enqueue_style('levelscss', plugins_url('/membership/membershipincludes/css/levels.css'), array('widgets'), $this->build);

			wp_localize_script( 'levelsjs', 'membership', array( 'deletelevel' => __('Are you sure you want to delete this level?','membership'), 'deactivatelevel' => __('Are you sure you want to deactivate this level?','membership') ) );

			$this->handle_levels_updates();
		}

		function add_admin_header_membershipsubs() {
			// Run the core header
			$this->add_admin_header_core();

			// Queue scripts and localise
			wp_enqueue_script('subsjs', plugins_url('/membership/membershipincludes/js/subscriptions.js'), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->build);
			wp_enqueue_style('subscss', plugins_url('/membership/membershipincludes/css/subscriptions.css'), array('widgets'), $this->build);

			wp_localize_script( 'subsjs', 'membership', array( 'deletesub' => __('Are you sure you want to delete this subscription?','membership'), 'deactivatesub' => __('Are you sure you want to deactivate this subscription?','membership') ) );

			$this->handle_subscriptions_updates();

		}

		function add_admin_header_members() {
			// Run the core header
			$this->add_admin_header_core();

			wp_enqueue_script('membersjs', plugins_url('/membership/membershipincludes/js/members.js'), array(), $this->build);

			wp_localize_script( 'membersjs', 'membership', array( 'deactivatemember' => __('Are you sure you want to deactivate this member?','membership') ) );


			$this->handle_members_updates();

		}

		function add_admin_header_membershipgateways() {
			$this->add_admin_header_core();

			$this->handle_gateways_panel_updates();
		}

		function add_admin_header_membershipoptions() {
			$this->add_admin_header_core();

		}

		// Panel handling functions

		function handle_membership_panel() {

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Membership dashboard','membership'); ?></h2>


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

				case 'toggle':	if(isset($_GET['user_id'])) {
									$user_id = (int) $_GET['user_id'];

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

			}

		}

		function handle_members_panel() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			require_once('class.membersearch.php');

			switch(addslashes($action)) {

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
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
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
						<option value="addsub"><?php _e('Add subscription','membership'); ?></option>
						<option value="movesub"><?php _e('Move subscription','membership'); ?></option>
						<option value="dropsub"><?php _e('Drop subscription','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Levels','membership'); ?>">
						<option value="addlevel"><?php _e('Add level','membership'); ?></option>
						<option value="movelevel"><?php _e('Move level','membership'); ?></option>
						<option value="droplevel"><?php _e('Drop level','membership'); ?></option>
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
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-members');

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
									<strong><a href=''><?php echo $user_object->user_login; ?></a></strong>
									<?php
										$actions = array();
										$actions['edit'] = "<span class='edit'><a href='?page={$page}&amp;action=edit&amp;member_id={$user_object->ID}'>" . __('Edit', 'membership') . "</a></span>";
										if($user_object->active_member()) {
											$actions['activate'] = "<span class='edit deactivate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;user_id=" . $user_object->ID . "", 'toggle-member_' . $user_object->ID) . "'>" . __('Deactivate', 'membership') . "</a></span>";
										} else {
											$actions['activate'] = "<span class='edit activate'><a href='" . wp_nonce_url("?page=" . $page. "&amp;action=toggle&amp;user_id=" . $user_object->ID . "", 'toggle-member_' . $user_object->ID) . "'>" . __('Activate', 'membership') . "</a></span>";
										}

										//$actions['delete'] = "<span class='delete'><a href=''>" . __('Delete', 'membership') . "</a></span>";
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
									//get_subscriptions
									?>
								</td>
								<td <?php echo $style; ?>>
									<?php
									$levels = $user_object->get_level_ids();
									if(!empty($levels)) {
										$rows = array();
										foreach((array) $levels as $key) {
											$level = new M_Level ( $key );
											if(!empty($level)) {
												$rows[] = $level->level_title();
											}
										}
										echo implode(", ", $rows);
									}
									?>
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
						<option value="addsub"><?php _e('Add subscription','membership'); ?></option>
						<option value="movesub"><?php _e('Move subscription','membership'); ?></option>
						<option value="dropsub"><?php _e('Drop subscription','membership'); ?></option>
					</optgroup>

					<optgroup label="<?php _e('Levels','membership'); ?>">
						<option value="addlevel"><?php _e('Add level','membership'); ?></option>
						<option value="movelevel"><?php _e('Move level','membership'); ?></option>
						<option value="droplevel"><?php _e('Drop level','membership'); ?></option>
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

		}

		function handle_options_panel() {

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

				<h3><?php _e('Stranger settings','membership'); ?></h3>
				<p><?php _e('A &quot;stranger&quot; is a visitor to your website who is either not logged in, or does not have an active membership or subscription to your website.','membership'); ?></p>

				<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e('Use membership level','membership'); ?></th>
						<td>
							<select name='strangerlevel' id='strangerlevel'>
								<option value="0"><?php _e('None','membership'); ?></option>
							<?php
								$levels = $this->get_membership_levels();
								if($levels) {
									foreach($levels as $key => $level) {
										?>
										<option value="<?php echo $level->id; ?>"><?php echo esc_html($level->level_title); ?></option>
										<?php
									}
								}
							?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e('Access level','membership'); ?></th>
						<td>
							<select name='strangerlevel' id='strangerlevel'>
								<option value="0"><?php _e('None','membership'); ?></option>
							<?php
								$levels = $this->get_membership_levels();
								if($levels) {
									foreach($levels as $key => $level) {
										?>
										<option value="<?php echo $level->id; ?>"><?php echo esc_html($level->level_title); ?></option>
										<?php
									}
								}
							?>
							</select>
						</td>
					</tr>

				</tbody>
				</table>

				<h3><?php _e('Stranger settings','membership'); ?></h3>
				<p><?php _e('A &quot;stranger&quot; is a visitor to your website who is either not logged in, or does not have an active membership or subscription to your website.','membership'); ?></p>


			</div> <!-- wrap -->
			<?php
		}

		function default_membership_sections($sections) {

			$sections['main'] = array(	"title" => __('Main rules','membership') );

			$sections['feed'] = array(	"title" => __('Feed rules','membership') );

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
							<div class='sidebar-name'>
								<h3><?php echo esc_html($level->level_title); ?></h3>
							</div>
							<div class='level-holder'>
								<div class='level-details'>
								<label for='level_title'><?php _e('Level title','management'); ?></label><br/>
								<input class='wide' type='text' name='level_title' id='level_title' value='<?php echo esc_attr($level->level_title); ?>' />
								</div>

								<h3><?php _e('Positive rules','membership'); ?></h3>
								<p class='description'><?php _e('These are the areas / elements that a member of this level can access.','membership'); ?></p>
								<div id='positive-rules-holder'>
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
								</div>
								<div id='positive-rules' class='droppable-rules levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<h3><?php _e('Negative rules','membership'); ?></h3>
								<p class='description'><?php _e('These are the areas / elements that a member of this level doesn\'t have access to.','membership'); ?></p>
								<div id='negative-rules-holder'>
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
								</div>
								<div id='negative-rules' class='droppable-rules levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

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

								<div class="sidebar-name">
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
											<?php echo $level->level_count; ?>
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
							<div class='sidebar-name'>
								<h3><?php echo esc_html($sub->sub_name); ?></h3>
							</div>
							<div class='sub-holder'>
								<div class='sub-details'>
								<label for='sub_name'><?php _e('Subscription name','management'); ?></label>
								<input class='wide' type='text' name='sub_name' id='sub_name' value='<?php echo esc_attr($sub->sub_name); ?>' />
								</div>

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
									<?php
										$msub->sub_details();
									?>
								</ul>
								<div id='membership-levels' class='droppable-levels levels-sortable'>
									<?php _e('Drop here','membership'); ?>
								</div>

								<?php
									// Hidden fields
								?>
								<input type='hidden' name='beingdragged' id='beingdragged' value='' />
								<input type='hidden' name='level-order' id='level-order' value=',<?php echo implode(',', $msub->levelorder); ?>' />

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
								<div class="sidebar-name">
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

			if(isset($_GET['sub_id'])) {
				$filter['sub_id'] = stripslashes($_GET['sub_id']);
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

				<select name="sub_id">
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'all') echo "selected='selected'"; ?> value="all"><?php _e('View all subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'active') echo "selected='selected'"; ?> value="active"><?php _e('View active subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'inactive') echo "selected='selected'"; ?> value="inactive"><?php _e('View inactive subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'public') echo "selected='selected'"; ?> value="public"><?php _e('View public subscriptions','membership'); ?></option>
				<option <?php if(isset($_GET['sub_id']) && addslashes($_GET['sub_id']) == 'private') echo "selected='selected'"; ?> value="private"><?php _e('View private subscriptions','membership'); ?></option>
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
											<?php echo $sub->sub_count; ?>
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

			}

		}

		function handle_gateways_panel() {

			global $action, $page, $M_Gateways;

			wp_reset_vars( array('action', 'page') );

			switch(addslashes($action)) {

				case 'edit':
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
										"active"	=>	__('Active','membership')
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

		// Database actions

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

				if(isset($filter['sub_id'])) {
					switch($filter['sub_id']) {

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

	}

}

?>