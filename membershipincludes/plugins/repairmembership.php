<?php

class M_Membershiprepair {

	function M_Membershiprepair() {
		$this->__construct();
	}

	function __construct() {
		add_action('membership_add_menu_items_bottom', array(&$this, 'add_menu'));
	}

	function add_menu() {
		add_submenu_page('membership', __('Membership Repair','membership'), __('Repair Membership','membership'), 'membershipadmin', "membershiprepair", array(&$this,'handle_repair_panel'));
	}

	// Database repair functions
	function handle_repair_panel() {
		global $action, $page, $M_options;

		wp_reset_vars( array('action', 'page') );

		?>
		<div class='wrap nosubsub'>
			<div class="icon32" id="icon-tools"><br></div>
			<h2><?php _e('Repair Membership','membership'); ?></h2>

			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}
			?>

			<p><?php _e('If you are having problems with your membership site, or have recently upgraded and are seeing strange behaviour then try the membership check below to see if there are any issues with your table structure. Click on the repair button if you want to repair any issues found (back up your database first).','membership'); ?></p>
			<p>
			<?php echo "<a href='" . wp_nonce_url("?page=" . $page. "&amp;verify=yes", 'verify-membership') . "' class='button'>" . __('Verify Membership Tables','membership') . "</a>&nbsp;&nbsp;"; ?>
			<?php echo "<a href='" . wp_nonce_url("?page=" . $page. "&amp;repair=yes", 'repair-membership') . "' class='button'>" . __('Repair Membership Tables','membership') . "</a>"; ?>
			</p>

			<?php
				if(isset($_GET['verify'])) {
					check_admin_referer('verify-membership');
					include_once(membership_dir('membershipincludes/classes/upgrade.php') );

					?>
					<p><strong><?php _e('Verifying','membership'); ?></strong></p>
					<?php

					M_verify_tables();
				}

				if(isset($_GET['repair'])) {
					check_admin_referer('repair-membership');
					include_once(membership_dir('membershipincludes/classes/upgrade.php') );

					?>
					<p><strong><?php _e('Verifying and Repairing','membership'); ?></strong></p>
					<?php

					M_repair_tables();
				}

			?>
		</div> <!-- wrap -->
		<?php
	}

}

$membershiprepair = new M_Membershiprepair();

?>