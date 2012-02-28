<?php

class M_Tutorial {

	private $_membership_tutorial;
	private $_wizard_tutorial;

	private $_membership_steps = array(
		'welcome',
		'members',
		'membersfilter',
		'memberssubs',
		'levels',
		'levelsaddnew',
		'subscriptions',
		'gateways',
		'options',
		'optionspages',
		'optionsprotection',
		'optionsdownloads',
		'optionsadmins',
		'optionsextras',
		'communications',
		'urlgroups',
		'pings',
	);

	private $_wizard_steps = array(
		'wizardwelcome',
		'wizardshow',
		'wizarddismiss'
	);

	function __construct () {
		if (!class_exists('Pointer_Tutorial')) require_once(membership_dir('membershipincludes/includes/pointer-tutorials.php'));

		$this->_membership_tutorial = new Pointer_Tutorial('membership', __('Membership tutorial', 'membership'), false, false);
		$this->_membership_tutorial->add_icon(membership_url('membershipincludes/images/pointer-icon.png'));

		$this->_wizard_tutorial = new Pointer_Tutorial('membershipwizard', __('Membership tutorial', 'membership'), false, false);
		$this->_wizard_tutorial->add_icon(membership_url('membershipincludes/images/pointer-icon.png'));

	}

	function M_Tutorial() {
		$this->__construct();
	}

	function serve () {
		$this->_add_hooks();
	}

	private function _add_hooks () {
		add_action('admin_init', array($this, 'process_tutorial'));
		add_action('wp_ajax_membership_restart_tutorial', array($this, 'json_restart_tutorial'));
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

	function process_tutorial () {
		global $pagenow;

		//if($_GET['page'] == )
		$visible = $this->wizard_visible();
		if($visible == 'no') {
			// Show after the wizard is dissmissed
			$this->_init_tutorial($this->_membership_steps);
			$this->_membership_tutorial->initialize();
		} else {
			$this->_init_wizard_tutorial($this->_wizard_steps);
			$this->_wizard_tutorial->initialize();
		}


		/*
		if ('wdsm' == wdsm_getval($_GET, 'page')) $this->_init_tutorial($this->_setup_steps);
		if ('social_marketing_ad' == wdsm_getval($_GET, 'post_type') && 'post-new.php' == $pagenow) $this->_init_tutorial($this->_edit_steps);
		if ('first' == wdsm_getval($_GET, 'wdsm') && 'post-new.php' == $pagenow) $this->_init_tutorial($this->_insert_steps);
		if (defined('DOING_AJAX')) {
			$this->_init_tutorial($this->_setup_steps);
			$this->_init_tutorial($this->_edit_steps);
		}
		$this->_edit_tutorial->initialize();
		$this->_setup_tutorial->initialize();
		$this->_insert_tutorial->initialize();
		*/
	}

	function json_restart_tutorial () {
		$tutorial = @$_POST['tutorial'];
		$this->restart($tutorial);
		die;
	}

	public function restart ($part=false) {
		$this->_membership_tutorial->restart();
	}

	private function _init_tutorial ($steps) {
		$this->_membership_tutorial->set_textdomain('membership');
		$this->_membership_tutorial->set_capability('manage_options');

		foreach ($steps as $step) {
			$call_step = "add_{$step}_step";
			if (method_exists($this, $call_step)) $this->$call_step();
		}
	}

	private function _init_wizard_tutorial ($steps) {
		$this->_wizard_tutorial->set_textdomain('membership');
		$this->_wizard_tutorial->set_capability('manage_options');

		foreach ($steps as $step) {
			$call_step = "add_{$step}_step";
			if (method_exists($this, $call_step)) $this->$call_step();
		}
	}

/* ----- Wizard steps ---- */

	function add_wizardwelcome_step () {
		$this->_wizard_tutorial->add_step(
			admin_url('admin.php?page=membership'), 'toplevel_page_membership',
			'#icon-index',
			__('Welcome to Membership', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This is the membership dashboard panel where you can keep track of your sites statistics and information.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_wizardshow_step () {
		$this->_wizard_tutorial->add_step(
			admin_url('admin.php?page=membership'), 'toplevel_page_membership',
			'div.welcome-panel-content h3',
			__('Getting started wizard', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('We have built a short (very short) wizard to help you get started with the plugin.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_wizarddismiss_step () {
		$this->_wizard_tutorial->add_step(
			admin_url('admin.php?page=membership'), 'toplevel_page_membership',
			'p.welcome-panel-dismiss',
			__('Dismissing the wizard', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('If you do not want to use the wizard then you can dismiss it here.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

/* ----- Edit Steps ----- */

	function add_welcome_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membership'), 'toplevel_page_membership',
			'a.toplevel_page_membership',
			__('Membership Menu', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This is your main membership menu, you have direct access to all the areas of the plugin from here.', 'membership')) . '</p>',
				'position' => array('edge' => 'left', 'align' => 'top'),
			)
		);

	}

	function add_members_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipmembers'), 'membership_page_membershipmembers',
			'#icon-users',
			__('Members screen', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This is the list of members for your site, you can control their subscriptions from here.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'right'),
			)
		);

	}

	function add_membersfilter_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipmembers'), 'membership_page_membershipmembers',
			'#doaction',
			__('Member filtering', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('You can use the filters at the top of the list to find members with specific criteria.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_memberssubs_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipmembers'), 'membership_page_membershipmembers',
			'#sub',
			__('Member subscriptions', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('You can Add, move or drop members from a subscription or level by using the links on a members row.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_levels_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels'), 'membership_page_membershiplevels',
			'#icon-link-manager',
			__('Access Levels', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Access levels allow you to control the amount of access to content members are entitled to see.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'right'),
			)
		);

	}

	function add_levelsaddnew_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels'), 'membership_page_membershiplevels',
			'.add-new-h2',
			__('Adding Levels', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Click on the Add New link to create a new level.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewform_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels Form', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_subscriptions_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipsubs'), 'membership_page_membershipsubs',
			'#icon-link-manager',
			__('Subscriptions', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Subscriptions control a members passage through your site and the length of time / amount of money they spend on each level.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_gateways_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipgateways'), 'membership_page_membershipgateways',
			'#icon-plugins',
			__('Gateways', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('A gateway controls the interface between your website and a payment provider. You should activate the gateways you want to use on your site.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_options_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipoptions'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('Options General Page', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This options page allows you to set the level that you wish unregistered visitors to your site to belong to, as well as the subscription that you want new subscribers to be automatically added to.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionspages_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershipoptions&amp;tab=pages'), 'membership_page_membershipoptions',
			'#level_title',
			__('Options Membership Pages', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This options page allows you to control and create the pages that display specific membership information.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionsprotection_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_optionsdownloads_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_optionsadmins_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_optionsextras_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_communications_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_urlgroups_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_pings_step () {
		$this->_membership_tutorial->add_step(
			admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Adding Levels From', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	/*
	'subscriptions',
	'gateways',
	'options',
	'optionspages',
	'optionsprotection',
	'optionsdownloads',
	'optionsadmins',
	'optionsextras',
	'communications',
	'urlgroups',
	'pings',
	*/



}
