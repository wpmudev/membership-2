<?php
/**
 * Controller for managing Protecion Rules.
 *
 * @since  1.0.1.0
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Protection extends MS_Controller {

	/**
	 * The model to use for loading/saving Membership data.
	 *
	 * Access this value via $this->load_membership()
	 *
	 * @since  1.0.0
	 * @var MS_Model_Membership
	 */
	private $model = null;

	/**
	 * The active page tab.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $active_tab;

	/**
	 * Prepare the Membership manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hook = MS_Controller_Plugin::admin_page_hook( 'protection' );

		$this->run_action( 'load-' . $hook, 'admin_page_process' );
		$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}

	/**
	 * Load membership from request.
	 *
	 * @since  1.0.0
	 *
	 * @return MS_Model_Membership The membership model object.
	 */
	public function load_membership() {
		$membership_id = 0;

		if ( empty( $this->model ) ) {
			if ( ! empty( $_REQUEST['membership_id'] ) ) {
				$membership_id = absint( $_REQUEST['membership_id'] );

				if ( $membership_id == MS_Model_Membership::get_base()->id ) {
					wp_safe_redirect(
						esc_url_raw(
							remove_query_arg( array( 'membership_id' ) )
						)
					);
				}
			} else {
				$membership_id = MS_Model_Membership::get_base()->id;
			}

			$this->model = MS_Factory::load(
				'MS_Model_Membership',
				$membership_id
			);

			$this->model = apply_filters(
				'ms_controller_protection_load_membership',
				$this->model,
				$this
			);
		}

		return $this->model;
	}

	/**
	 * Process membership pages requests
	 *
	 * Verifies GET and POST requests to manage memberships.
	 * Redirect to next step after processing.
	 *
	 * @since  1.0.0
	 */
	public function admin_page_process() {
		$membership = $this->load_membership();

		// MS_Controller_Rule is executed using this action.
		do_action(
			'ms_controller_protection_admin_page_process_' . $step,
			$this->get_active_tab()
		);

		// Only accessible to admin users
		if ( ! $this->is_admin_user() ) { return false; }

		if ( $this->verify_nonce( 'bulk' ) ) {
			// Bulk-edit

			lib2()->array->equip_post( 'action', 'action2', 'item', 'rule_type' );
			$action = $_POST['action'];
			if ( empty( $action ) || $action == '-1' ) {
				$action = $_POST['action2'];
			}
			$items = $_POST['item'];
			$rule_type = $_POST['rule_type'];

			/*
			 * The Bulk-Edit action is built like 'cmd-id'
			 * e.g. 'add-123' will add membership 123 to the selected items.
			 */
			if ( empty( $action ) ) {
				$cmd = array();
			} elseif ( empty( $items ) ) {
				$cmd = array();
			} elseif ( empty( $rule_type ) ) {
				$cmd = array();
			} elseif ( '-1' == $action ) {
				$cmd = array();
			} else {
				$cmd = explode( '-', $action );
			}

			if ( 2 == count( $cmd ) ) {
				$action = $cmd[0];
				$action_id = $cmd[1];

				// Get a list of specified memberships...
				if ( is_numeric( $action_id ) ) {
					// ... either a single membership.
					$memberships = array(
						MS_Factory::load( 'MS_Model_Membership', $action_id ),
					);
				} elseif ( 'all' == $action_id ) {
					// ... or all memberships.
					$memberships = MS_Model_Membership::get_memberships();
				}

				// Loop specified memberships and add the selected items.
				foreach ( $memberships as $membership ) {
					$rule = $membership->get_rule( $rule_type );
					foreach ( $items as $item ) {
						switch ( $action ) {
							case 'add':
								$rule->give_access( $item );
								break;

							case 'rem':
								$rule->remove_access( $item );
								break;
						}
					}
					$membership->set_rule( $rule_type, $rule );
					$membership->save();
				}
			}
		} else {
			// No action request found.
		}
	}

	/**
	 * Display Setup Membership2 page.
	 *
	 * @since  1.0.0
	 */
	public function admin_page() {
		do_action( 'ms_controller_protection_admin_page' );

		$data = array();
		$data['tabs'] = $this->get_available_tabs();
		$data['active_tab'] = $this->get_active_tab();

		$view = MS_Factory::create( 'MS_View_Protection' );
		$view->data = apply_filters( 'ms_view_protection_data', $data, $this );
		$view->render();
	}

	/**
	 * Get available tabs for Membership2 page.
	 *
	 * @since  1.0.0
	 *
	 * @return array The tabs configuration.
	 */
	public function get_available_tabs() {
		static $Tabs = null;

		if ( null === $Tabs ) {
			$membership = $this->load_membership();
			$membership_id = $membership->id;
			$is_base = $membership->is_base();
			$settings = MS_Factory::load( 'MS_Model_Settings' );

			// First create a list including all possible tabs.
			$tabs = array(
				MS_Rule_Page::RULE_ID => true,
				MS_Rule_Post::RULE_ID => true,
				MS_Rule_Category::RULE_ID => true,
				MS_Rule_CptItem::RULE_ID => true,
				MS_Rule_CptGroup::RULE_ID => true,
				MS_Rule_Content::RULE_ID => true,
				MS_Rule_Media::RULE_ID => true,
				MS_Rule_MenuItem::RULE_ID => true,
				MS_Rule_ReplaceMenu::RULE_ID => true,
				MS_Rule_ReplaceLocation::RULE_ID => true,
				MS_Rule_Shortcode::RULE_ID => true,
				MS_Rule_Url::RULE_ID => true,
				MS_Rule_Special::RULE_ID => true,
				MS_Rule_Adminside::RULE_ID => true,
				MS_Rule_MemberCaps::RULE_ID => true,
				MS_Rule_MemberRoles::RULE_ID => true,
			);

			// Now remove items from the list that are not available.

			// Optionally show "Posts"
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				$tabs[MS_Rule_Post::RULE_ID] = false;
			}

			// Optionally show "Category"
			if ( ! MS_Model_Addon::is_enabled( MS_Addon_Category::ID ) ) {
				$tabs[MS_Rule_Category::RULE_ID] = false;
			}

			// Optionally show "Media"
			if ( ! MS_Model_Addon::is_enabled( MS_Addon_Mediafiles::ID ) ) {
				$tabs[MS_Rule_Media::RULE_ID] = false;
			}

			// Either "CPT Group" or "CPT Posts"
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
				$tabs[MS_Rule_CptGroup::RULE_ID] = false;
			} else {
				$tabs[MS_Rule_CptItem::RULE_ID] = false;
			}

			// Either "Menu Item" or "Menus" or "Menu Location"
			switch ( $settings->menu_protection ) {
				case 'menu':
					$tabs[MS_Rule_MenuItem::RULE_ID] = false;
					$tabs[MS_Rule_ReplaceLocation::RULE_ID] = false;
					break;

				case 'location':
					$tabs[MS_Rule_MenuItem::RULE_ID] = false;
					$tabs[MS_Rule_ReplaceMenu::RULE_ID] = false;
					break;

				case 'item':
				default:
					$tabs[MS_Rule_ReplaceMenu::RULE_ID] = false;
					$tabs[MS_Rule_ReplaceLocation::RULE_ID] = false;
					break;
			}

			// Maybe "Special Pages".
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SPECIAL_PAGES ) ) {
				$tabs[MS_Rule_Special::RULE_ID] = false;
			}

			// Maybe "URLs"
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
				$tabs[MS_Rule_Url::RULE_ID] = false;
			}

			// Maybe "Shortcodes"
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
				$tabs[MS_Rule_Shortcode::RULE_ID] = false;
			}

			// Maybe "Admin-Side"
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_ADMINSIDE ) ) {
				$tabs[MS_Rule_Adminside::RULE_ID] = false;
			}

			// Maybe "Membercaps"
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS ) ) {
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
					$tabs[MS_Rule_MemberRoles::RULE_ID] = false;
				} else {
					$tabs[MS_Rule_MemberCaps::RULE_ID] = false;
				}
			} else {
				$tabs[MS_Rule_MemberRoles::RULE_ID] = false;
				$tabs[MS_Rule_MemberCaps::RULE_ID] = false;
			}

			lib2()->array->equip( $_GET, 'page' );

			// Allow Add-ons to add or remove rule tabs
			$tabs = apply_filters(
				'ms_controller_protection_tabs',
				$tabs,
				$membership_id
			);

			$page = sanitize_html_class( $_GET['page'], MS_Controller_Plugin::MENU_SLUG . '-memberships' );
			$rule_titles = MS_Model_Rule::get_rule_type_titles();

			$result = array();
			foreach ( $tabs as $rule_type => $state ) {
				if ( ! $state ) { continue; }

				$url = sprintf(
					'%s?page=%s&tab=%s',
					admin_url( 'admin.php' ),
					$page,
					$rule_type
				);

				// Try to keep the selected Membership and Status filter.
				if ( ! empty( $_REQUEST['membership_id'] ) ) {
					$url = esc_url_raw(
						add_query_arg(
							array( 'membership_id' => $_REQUEST['membership_id'] ),
							$url
						)
					);
				}
				if ( ! empty( $_REQUEST['status'] ) ) {
					$url = esc_url_raw(
						add_query_arg(
							array( 'status' => $_REQUEST['status'] ),
							$url
						)
					);
				}

				$result[ $rule_type ] = array(
					'title' => $rule_titles[ $rule_type ],
					'url' => $url,
				);
			}

			$Tabs = apply_filters(
				'ms_controller_protection_get_available_tabs',
				$result,
				$membership_id,
				$this
			);
		}

		return $Tabs;
	}

	/**
	 * Get the current membership page's active tab.
	 *
	 * @since  1.0.0
	 *
	 * @return string The active tab.
	 */
	public function get_active_tab() {
		$tabs = $this->get_available_tabs();

		reset( $tabs );
		$first_key = key( $tabs );

		// Setup navigation tabs.
		$active_tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : '';
		$active_tab = sanitize_html_class( $active_tab, $first_key );

		if ( ! array_key_exists( $active_tab, $tabs ) ) {
			$active_tab = $first_key;
		}

		$this->active_tab = apply_filters(
			'ms_controller_protection_get_active_tab',
			$active_tab
		);

		return $this->active_tab;
	}

	/**
	 * Load Membership manager specific styles.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		lib2()->ui->add( 'jquery-ui' );

		do_action( 'ms_controller_protection_enqueue_styles', $this );
	}

	/**
	 * Load Membership manager specific scripts.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		/*
		 * Get a list of the dripped memberships:
		 * We need this info in the javascript.
		 */
		$dripped = array();
		foreach ( MS_Model_Membership::get_dripped_memberships() as $item ) {
			$dripped[ $item->id ] = $item->name;
		}

		$data = array(
			'ms_init' => array(),
			'lang' => array(
				'quickedit_error' => __( 'Error while saving changes.', MS_TEXT_DOMAIN ),
			),
			'dripped' => $dripped,
		);

		$data['ms_init'][] = 'view_protected_content';

		switch ( $this->get_active_tab() ) {
			case 'url':
				$data['valid_rule_msg'] = __( 'Valid', MS_TEXT_DOMAIN );
				$data['invalid_rule_msg'] = __( 'Invalid', MS_TEXT_DOMAIN );
				$data['empty_msg'] = __( 'Before testing you have to first enter one or more Page URLs above.', MS_TEXT_DOMAIN );
				$data['ms_init'][] = 'view_membership_urlgroup';
				break;

			default:
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'jquery-validate' );
				break;
		}

		lib2()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );

		do_action( 'ms_controller_protection_enqueue_scripts', $this );
	}

}