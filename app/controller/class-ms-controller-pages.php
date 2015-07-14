<?php
/**
 * Controller for managing Membership Pages.
 *
 * @since 1.0.4.5
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Pages extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.4.5
	 *
	 * @var string
	 */
	const AJAX_ACTION_UPDATE_PAGES = 'pages_update';
	const AJAX_ACTION_TOGGLE_MENU = 'pages_toggle_menu';
	const AJAX_ACTION_CREATE_MENU = 'pages_create_menu';

	/**
	 * Construct Settings manager.
	 *
	 * @since 1.0.4.5
	 */
	public function __construct() {
		parent::__construct();

		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_PAGES, 'ajax_action_update_pages' );
		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_MENU, 'ajax_action_toggle_menu' );
		$this->add_ajax_action( self::AJAX_ACTION_CREATE_MENU, 'ajax_action_create_menu' );
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since 2.0.0
	 */
	public function admin_init() {
		// Add a special "state" to membership pages in the WP Page-list
		$this->add_filter( 'display_post_states', 'post_states', 10, 2 );
	}

	/**
	 * Get MS_Model_Pages model
	 *
	 * @since 1.0.4.5
	 *
	 * @return MS_Model_Settings
	 */
	public function get_model() {
		return MS_Factory::load( 'MS_Model_Pages' );
	}

	/**
	 * Handle Ajax update pages setting.
	 *
	 * Related action hooks:
	 * - wp_ajax_update_pages
	 *
	 * @since 1.0.4.5
	 */
	public function ajax_action_update_pages() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$ms_pages = $this->get_model();
			$ms_pages->set_setting( $_POST['field'], $_POST['value'] );
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo $msg;
		exit;
	}

	/**
	 * Handle Ajax toggle menu items.
	 *
	 * Related action hooks:
	 * - wp_ajax_toggle_menu
	 *
	 * @since 1.1.0
	 */
	public function ajax_action_toggle_menu() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'item', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$item = $_POST['item'];
			$res = false;

			if ( lib2()->is_true( $_POST['value'] ) ) {
				$res = MS_Model_Pages::create_menu( $item );
			} else {
				$res = MS_Model_Pages::drop_menu( $item );
			}

			if ( $res ) {
				$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
			}
		}

		echo '' . $msg;
		exit;
	}

	/**
	 * Creates a new menu (not menu-item but whole menu).
	 * All top-level pages are added automatically to this new menu.
	 *
	 * Related action hooks:
	 * - wp_ajax_create_menu
	 *
	 * @since 1.1.0
	 */
	public function ajax_action_create_menu() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		$content = '';

		if ( $this->verify_nonce() && $this->is_admin_user() ) {
			MS_Model_Pages::create_default_menu();

			$setup = MS_Factory::create( 'MS_View_Settings_Page_Setup' );
			$content = $setup->show_menu_controls();

			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo '' . $msg . ':' . $content;
		exit;
	}

	/**
	 * This filter is called by WordPress when the page-listtable is created to
	 * display all available Posts/Pages. We use this filter to add a note
	 * to all pages that are special membership pages.
	 *
	 * @since  1.1.0
	 * @param  array $states
	 * @param  WP_Post $post
	 * @return array
	 */
	public function post_states( $states, $post ) {
		if ( 'page' == $post->post_type ) {
			if ( MS_Model_Pages::is_membership_page( $post->ID ) ) {
				$url = MS_Controller_Plugin::get_admin_url( 'settings' );
				$states['membership2'] = sprintf(
					'<a style="%2$s" href="%3$s">%1$s</a>',
					__( 'Membership2 Page', MS_TEXT_DOMAIN ),
					'background:#aaa;color:#fff;padding:1px 4px;border-radius:4px;font-size:0.8em',
					$url
				);
			}
		}

		return $states;
	}

}
