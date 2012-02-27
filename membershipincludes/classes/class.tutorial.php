<?php

class M_Tutorial {

	private $_edit_tutorial;
	private $_setup_tutorial;
	private $_insert_tutorial;

	private $_edit_steps = array(
		'welcome',
		'title',
		'body',
		'options',
		'share_url',
		'button_text',
		'type',
		'share_text',
		'services',
	);

	private $_setup_steps = array(
		'settings',
		//'popup',
		'javascript',
		'appearance',
		'styles',
	);

	private $_insert_steps = array(
		'insert',
	);

	private function __construct () {
		if (!class_exists('')) require_once WDSM_PLUGIN_BASE_DIR . '/lib/external/pointers_tutorial.php';
		$this->_edit_tutorial = new Pointer_Tutorial('wdsm-edit', __('Social Marketing tutorial', 'wdsm'), false, false);
		$this->_setup_tutorial = new Pointer_Tutorial('wdsm-setup', __('Setup tutorial', 'wdsm'), false, false);
		$this->_insert_tutorial = new Pointer_Tutorial('wdsm-insert', __('Insert tutorial', 'wdsm'), false, false);
		$this->_edit_tutorial->add_icon(WDSM_PLUGIN_URL . '/img/pointer_icon.png');
		$this->_setup_tutorial->add_icon(WDSM_PLUGIN_URL . '/img/pointer_icon.png');
		$this->_insert_tutorial->add_icon(WDSM_PLUGIN_URL . '/img/pointer_icon.png');
	}

	function M_Tutorial() {
		$this->__construct();
	}

	public static function serve () {
		$me = new Wdsm_Tutorial;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('admin_init', array($this, 'process_tutorial'));
		add_action('wp_ajax_wdsm_restart_tutorial', array($this, 'json_restart_tutorial'));
	}

	function process_tutorial () {
		global $pagenow;
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
	}

	function json_restart_tutorial () {
		$tutorial = @$_POST['tutorial'];
		$this->restart($tutorial);
		die;
	}

	public function restart ($part=false) {
		$tutorial = "_{$part}_tutorial";
		if ($part && isset($this->$tutorial)) return $this->$tutorial->restart();
		else if (!$part) {
			$this->_edit_tutorial->restart();
			$this->_setup_tutorial->restart();
		}
	}

	private function _init_tutorial ($steps) {
		$this->_edit_tutorial->set_textdomain('wdsm');
		$this->_setup_tutorial->set_capability('manage_options');
		$this->_edit_tutorial->set_textdomain('wdsm');
		$this->_setup_tutorial->set_capability('manage_options');

		foreach ($steps as $step) {
			$call_step = "add_{$step}_step";
			if (method_exists($this, $call_step)) $this->$call_step();
		}
	}

/* ----- Edit Steps ----- */

	function add_welcome_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#icon-edit',
			__('New Advert', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Here is where you&#8217;ll create your first social marketing advert!', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_title_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#title',
			__('Title', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Give your advert a title.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);

	}

	function add_body_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#postdivrich',
			__('Sell Your Product', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Tell your visitors why they should click on your advert!', 'wdsm')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_options_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#wdsm_services',
			__('Social Marketing Options', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('You can tweak most of your options here.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);

	}

	function add_share_url_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#wdsm_url',
			__('URL', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('This is the URL that will be shared by your visitors with their friends.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_button_text_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#wdsm_button_text',
			__('Button text', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Add a call to action to get your visitors to click!', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_type_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#wdsm_type',
			__('Offer Type', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Choose whether you are offering a free download or a coupon code.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_share_text_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#wdsm_share_text',
			__('Thank You Text', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Thank your users for clicking on your link.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_services_step () {
		$this->_edit_tutorial->add_step(
			admin_url('post-new.php?post_type=social_marketing_ad'), 'post-new.php',
			'#wdsm-services_box',
			__('Social Media', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Select one or more social media service.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'bottom', 'align' => 'left'),
			)
		);
	}

/* ----- Setup Steps ----- */

	function add_settings_step () {
		$this->_setup_tutorial->add_step(
			admin_url('edit.php?post_type=social_marketing_ad&page=wdsm'), 'social_marketing_ad_page_wdsm',
			'#wdsm-settings_start',
			__('Welcome!', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('This is where you&#8217;ll create your first social marketing advert.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}
/*
	function add_popup_step () {
		$this->_setup_tutorial->add_step(
			admin_url('edit.php?post_type=social_marketing_ad&page=wdsm'), 'social_marketing_ad_page_wdsm',
			'#settings-pop-up-box',
			__('Pop-up style', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Choose how your pop-up advert will be displayed. ', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}
*/
	function add_javascript_step () {
		$this->_setup_tutorial->add_step(
			admin_url('edit.php?post_type=social_marketing_ad&page=wdsm'), 'social_marketing_ad_page_wdsm',
			'#settings-javascript',
			__('Javascript', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Select any services which already provide javascript to your website.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_appearance_step () {
		$this->_setup_tutorial->add_step(
			admin_url('edit.php?post_type=social_marketing_ad&page=wdsm'), 'social_marketing_ad_page_wdsm',
			'#wdsm-theme',
			__('Appearance', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Select how your button will look.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

	function add_styles_step () {
		$this->_setup_tutorial->add_step(
			admin_url('edit.php?post_type=social_marketing_ad&page=wdsm'), 'social_marketing_ad_page_wdsm',
			'#wdsm-no-theme',
			__('Styles', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('If you want to style the button yourself check this box.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'top', 'align' => 'left'),
			)
		);
	}

/* ----- Insert ----- */

	function add_insert_step () {
		$this->_insert_tutorial->add_step(
			admin_url('post-new.php'), 'post-new.php',
			'#add_advert',
			__('Insert Advert', 'wdsm'),
			array(
				'content' => '<p>' . esc_js(__('Click this icon to insert your Social Marketing Advert.', 'wdsm')) . '</p>',
				'position' => array('edge' => 'left', 'align' => 'left'),
			)
		);
	}


}