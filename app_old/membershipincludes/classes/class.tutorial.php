<?php

if(!class_exists('M_Tutorial')) {

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
		'levelsaddnewformtitle',
		'levelsaddnewformrules',
		'levelsaddnewformrulesdrag',
		'levelsaddnewformrulespositive',
		'levelsaddnewformrulesnegative',
		'levelsaddnewformrulesadvanced',
		'subscriptions',
		'subscriptionsaddnew',
		'subscriptionsaddtitle',
		'subscriptionsadddescription',
		'subscriptionsaddpricetext',
		'subscriptionsaddlevels',
		'subscriptionsadddrophere',
		'gateways',
		'options',
		'optionspages',
		'optionsprotection',
		'optionsdownloads',
		'optionsadmins',
		'optionsextras',
		'optionsadvanced',
		'communications',
		'communicationsadd',
		'communicationsaddform',
		'communicationsaddformsubject',
		'urlgroups',
		'pings',
		'enablemembership'
	);

	private $_wizard_steps = array(
		'wizardwelcome',
		'wizardshow',
		'wizarddismiss'
	);

	function __construct () {
		if (!class_exists('Pointer_Tutorial')) require_once(membership_dir('membershipincludes/includes/new-pointer-tutorials.php'));

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
		add_action( 'admin_init', array(&$this, 'process_restart') );
	}

	function process_restart() {
		// Check for a reset of the tutorial
		if(isset($_GET['restarttutorial']) && $_GET['restarttutorial'] == 'yes') {
			check_admin_referer('restarttutorial');
			$this->_membership_tutorial->restart();
			wp_safe_redirect( remove_query_arg( 'restarttutorial', remove_query_arg( '_wpnonce') ) );
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
			$this->admin_url('admin.php?page=membership'), 'toplevel_page_membership',
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
			$this->admin_url('admin.php?page=membership'), 'toplevel_page_membership',
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
			$this->admin_url('admin.php?page=membership'), 'toplevel_page_membership',
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
			$this->admin_url('admin.php?page=membership'), 'toplevel_page_membership',
			'#toplevel_page_membership',
			__('Membership Menu', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This is your main membership menu, you have direct access to all the areas of the plugin from here.', 'membership')) . '</p>',
				'position' => array('edge' => 'left', 'align' => 'bottom'),
			)
		);

	}

	function add_members_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipmembers'), 'membership_page_membershipmembers',
			'#icon-users',
			__('Members screen', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This is the list of members for your site, you can control their subscriptions from here.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_membersfilter_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipmembers'), 'membership_page_membershipmembers',
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
			$this->admin_url('admin.php?page=membershipmembers'), 'membership_page_membershipmembers',
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
			$this->admin_url('admin.php?page=membershiplevels'), 'membership_page_membershiplevels',
			'#icon-link-manager',
			__('Access Levels', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Access levels allow you to control the amount of access to content members are entitled to see.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnew_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels'), 'membership_page_membershiplevels',
			'.add-new-h2',
			__('Adding Levels', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Click on the Add New link to create a new level.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewformtitle_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#level_title',
			__('Level Title', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The Level title enables you to quickly identify a level and should be as descriptive as possible.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewformrules_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#sidebar-main',
			__('Level Rules', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Each rule allows you to specify specific content to protect or allow access to.', 'membership')) . '</p>',
				'position' => array('edge' => 'right', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewformrulesdrag_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'#positive-rules',
			__('Adding Rules', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('To add a rule to your level, drag it to the Drop Here box. You can then select the content you want to protect / allow access to. To remove a rule you can click the Remove link in the rules title.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewformrulespositive_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'li.positivetab',
			__('Positive Rules', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Positive rules allow you to specify what a member on this level has access to.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewformrulesnegative_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'li.negativetab',
			__('Negative Rules', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Negative rules allow you to specify what a member does not have access to.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_levelsaddnewformrulesadvanced_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershiplevels&action=edit&level_id='), 'membership_page_membershiplevels',
			'li.advancedtab',
			__('Advanced Rules', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('If you want to use a combination of Positive and Negative rules then you can add both in the advanced area.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_subscriptions_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs'), 'membership_page_membershipsubs',
			'#icon-link-manager',
			__('Subscriptions', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Subscriptions control a members passage through your site and the length of time / amount of money they spend on each level.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_subscriptionsaddnew_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs'), 'membership_page_membershipsubs',
			'a.add-new-h2',
			__('Adding Subscriptions', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('To add a subscription click on the Add New link.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_subscriptionsaddtitle_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs&action=edit&sub_id='), 'membership_page_membershipsubs',
			'#sub_name',
			__('Subscription Name', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The subscription name helps identify the subscription and is shown on the subscriptions list on the front end of your site.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_subscriptionsadddescription_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs&action=edit&sub_id='), 'membership_page_membershipsubs',
			'#wp-sub_description-wrap',
			__('Subscription Description', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The subscription description is shown on the front end of your site and should describe the subscription and the benefits of selecting it.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_subscriptionsaddpricetext_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs&action=edit&sub_id='), 'membership_page_membershipsubs',
			'#sub_pricetext',
			__('Subscription Price Text', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The subscription price text is shown on the front end of your site and should contain a description of the pricing. E.g. $35 per month', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_subscriptionsaddlevels_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs&action=edit&sub_id='), 'membership_page_membershipsubs',
			'#sidebar-levels',
			__('Subscription Levels', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The levels that you have available are shown here, to add them to this subscription you can drag them over to the Drop Here box.', 'membership')) . '</p>',
				'position' => array('edge' => 'right', 'align' => 'left'),
			)
		);
	}

	function add_subscriptionsadddrophere_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipsubs&action=edit&sub_id='), 'membership_page_membershipsubs',
			'#membership-levels',
			__('Subscription Level Drop', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Drop any Levels you want to add to this subscription here, you can re-order levels by dragging them into the desired order. To remove a level from the subscription you can click on the Remove link.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_gateways_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipgateways'), 'membership_page_membershipgateways',
			'#icon-plugins',
			__('Gateways', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('A gateway controls the interface between your website and a payment provider. You should activate the gateways you want to use on your site by clicking the Activate link underneath each gateways name. The settings for each gateway can be accessed by click the Settings link under the gateways name once it is active.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_options_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('General Options', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This options page allows you to set the level that you wish unregistered visitors to your site to belong to, as well as the subscription that you want new subscribers to be automatically added to.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionspages_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions&tab=pages'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('Membership Page Options', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This options page allows you to control and create the pages that display specific membership information, such as the registration page and the account details page.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionsprotection_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions&tab=posts'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('Content Protection Options', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This options page allows you to create the message that is displayed when a member does not have access to shortcode and / or more tag protected content.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionsdownloads_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions&tab=downloads'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('Download / Media Options', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This options page allows you to set up a masking URL for your sites media and downloads and to create some groups that you can assign different media files to.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionsadmins_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions&tab=users'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('Membership Admin Users', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('You can add or remove membership administration rights to admin users using this page.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionsextras_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions&tab=extras'), 'membership_page_membershipoptions',
			'#icon-options-general',
			__('Extra Options', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('This page allows you to specify, amongst other things, the global currency for your site and the period of time left until a subscription expires before they can renew.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_optionsadvanced_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipoptions&tab=advanced'), 'membership_page_membershipoptions',
			'#icon-tools',
			__('Repair Membership', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('If you have any issues with the membership plugin then you can check your database integrity using the Verify button on this page, if any issues are reported then they can be fixed by pressing the Repair button.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_communications_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipcommunication'), 'membership_page_membershipcommunication',
			'#icon-edit-comments',
			__('Communications', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('The communications system enables you to set up a series of messages that are sent to your members at set daily periods after they have signed up or at set days upto the date their subscriptions are due to expire.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_communicationsadd_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipcommunication'), 'membership_page_membershipcommunication',
			'a.add-new-h2',
			__('Add Communication', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('To add a new communication, click on the Add New button at the top of the page.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_communicationsaddform_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipcommunication&action=edit&comm='), 'membership_page_membershipcommunication',
			'select[name=periodprepost]',
			__('Set period', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Specify a time period before or after a subscription starts or is due to end. Setting a period of 0 means that the message is sent immediately on signup.', 'membership')) . '</p>',
				'position' => array('edge' => 'left', 'align' => 'right'),
			)
		);
	}

	function add_communicationsaddformsubject_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipcommunication&action=edit&comm='), 'membership_page_membershipcommunication',
			'input[name=subject]',
			__('Set subject and message', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('You should enter the subject and content of the message in the areas provided. You can use the placeholders listed next to the message area to include details such as the member name, etc.', 'membership')) . '</p>',
				'position' => array('edge' => 'left', 'align' => 'right'),
			)
		);
	}

	function add_urlgroups_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershipurlgroups'), 'membership_page_membershipurlgroups',
			'#icon-edit-pages',
			__('URL Groups', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('URL Groups allow you to specify a group of URLs on your site that you want to protect and then allow / or prevent members to access them from within a level.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_pings_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membershippings'), 'membership_page_membershippings',
			'#icon-link-manager',
			__('Remote Pings', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('A Ping is a method of sending a message to an external URL when something happens within the membership plugin (such as a member starts or ends a subscription). They are useful for things like registering users on external support forums.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_enablemembership_step () {
		$this->_membership_tutorial->add_step(
			$this->admin_url('admin.php?page=membership'), 'toplevel_page_membership',
			'#enablemembership',
			__('Enable Membership', 'membership'),
			array(
				'content' => '<p>' . esc_js(__('Once you have everything set up you should enable the membership plugins protection system.', 'membership')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function admin_url( $extend = false ) {
		// ready for if site has network interface
		if( (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('membership/membership.php')) && (defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true)) {
			return network_admin_url( $extend );
		} else {
			return admin_url( $extend );
		}
	}


}

}
