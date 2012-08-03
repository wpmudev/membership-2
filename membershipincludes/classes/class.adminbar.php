<?php
if(!class_exists('M_Adminbar')) {

	class M_Adminbar {

		var $build = 12;
		var $db;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups', 'ping_history', 'pings', 'coupons');

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
		var $coupons;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			add_action( 'add_admin_bar_menus', array( &$this, 'add_admin_bar_items' ) );

			add_action('membership_dashboard_membershipuselevel', array( &$this, 'switch_membership_level' ) );

		}

		function M_Adminbar() {
			$this->__construct();
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

		// Add the admin bar menu item
		function add_admin_bar_enabled_item( $wp_admin_bar ) {

			global $M_options;

			$active = M_get_membership_active();

			$title = __('Membership', 'membership') . " : <span style='color:red; text-shadow: 1px 1px 0 #000;'>" . __('Disabled', 'membership') . "</span>";
			$metatitle = __('Click to Enable the Membership protection', 'membership');
			$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=activate"), 'toggle-plugin');
			$wp_admin_bar->add_menu( array(
				'id'        => 'membership',
				'parent'    => 'top-secondary',
				'title'     => $title,
				'href'      => $linkurl,
				'meta'      => array(
					'class'     => $class,
					'title'     => $metatitle,
				),
			) );


			$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=activate"), 'toggle-plugin');
			$wp_admin_bar->add_menu( array(
				'parent'    => 'membership',
				'id'        => 'membershipenable',
				'title'     => __('Enable Membership', 'membership'),
				'href'      => $linkurl,
			) );

		}

		function switch_membership_level() {

			if(!empty($_GET['level_id'])) {
				$level_id = (int) $_GET['level_id'];
				check_admin_referer( 'membershipuselevel-' . $level_id );

				@setcookie('membershipuselevel', $level_id, 0, COOKIEPATH, COOKIE_DOMAIN);
			}

			wp_safe_redirect( wp_get_referer() );

		}

		function add_admin_bar_view_site_as( $wp_admin_bar ) {

			global $M_options;

			$levels = $this->get_membership_levels( array( 'level_id' => 'active', 'order_by' => 'order_id' ) );

			$title = __('View site as : ', 'membership');
			if(empty($_COOKIE['membershipuselevel'])) {
				$title .= __('Membership Admin', 'membership');
			} else {
				$level_id = (int) $_COOKIE['membershipuselevel'];
				$level = new M_Level( $level_id );
				$title .= $level->level_title();
			}
			$metatitle = __('Select a level to view your site as', 'membership');
			$linkurl = ''; // No link for the main menu
			$wp_admin_bar->add_menu( array(
				'id'        => 'membershipuselevel',
				'parent'    => 'top-secondary',
				'title'     => $title,
				'href'      => $linkurl,
				'meta'      => array(
					'class'     => $class,
					'title'     => $metatitle,
				),
			) );

			if(!empty($levels)) {
				foreach( $levels as $key => $level ) {
					$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=membershipuselevel&amp;level_id=" . $level->id), 'membershipuselevel-' . $level->id);
					$wp_admin_bar->add_menu( array(
						'parent'    => 'membershipuselevel',
						'id'        => 'membershipuselevel-' . $level->id,
						'title'     => $level->level_title,
						'href'      => $linkurl
					) );
				}
			}

			$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=membershipuselevel&amp;level_id=0"), 'membershipuselevel-0');
			$wp_admin_bar->add_menu( array(
				'parent'    => 'membershipuselevel',
				'id'        => 'membershipuselevel-0',
				'title'     => __('Reset', 'membership'),
				'href'      => $linkurl
			) );

		}

		function add_admin_bar_items() {

			global $M_options;

			$active = M_get_membership_active();

			if($active == 'yes') {
				add_action( 'admin_bar_menu', array( &$this, 'add_admin_bar_view_site_as' ), 8 );
			} else {
				add_action( 'admin_bar_menu', array( &$this, 'add_admin_bar_enabled_item' ), 8 );
			}


		}


	}

}

$M_Adminbar = new M_Adminbar();
?>