<?php
/**
 * This file defines the MS_Controller_Membership class.
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Controller for managing Memberships and Membership Rules.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Membership extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_MEMBERSHIP = 'toggle_membership';
	const AJAX_ACTION_UPDATE_MEMBERSHIP = 'update_membership';

	/**
	 * Membership page step constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const STEP_MS_LIST = 'ms_list';
	const STEP_OVERVIEW = 'ms_overview';
	const STEP_NEWS = 'ms_news';
	const STEP_WELCOME_SCREEN = 'welcome';
	const STEP_PROTECTED_CONTENT = 'protected_content';
	const STEP_ADD_NEW = 'add';
	const STEP_PAYMENT = 'payment';

	// Actions
	const ACTION_SAVE = 'save_membership';

	/**
	 * The model to use for loading/saving Membership data.
	 *
	 * @since 4.0.0
	 * @var MS_Model_Membership
	 */
	private $model = null;

	/**
	 * The active page tab.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $active_tab;

	/**
	 * Prepare the Membership manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$protected_content_menu_hook = 'toplevel_page_protected-content';
		$protected_content_setup_hook = 'protect-content_page_protected-content-setup';

		$this->add_action( 'load-' . $protected_content_menu_hook, 'membership_admin_page_process' );
		$this->add_action( 'load-' . $protected_content_setup_hook, 'membership_admin_page_process' );

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_MEMBERSHIP, 'ajax_action_toggle_membership' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_MEMBERSHIP, 'ajax_action_update_membership' );

		$this->add_action( 'admin_print_scripts-' . $protected_content_setup_hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $protected_content_setup_hook, 'enqueue_styles' );

		$this->add_action( 'admin_print_scripts-' . $protected_content_menu_hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $protected_content_menu_hook, 'enqueue_styles' );
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_toggle_membership
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_membership() {
		$msg = 0;

		$required = array( 'membership_id', 'field' );

		if ( $this->verify_nonce()
			&& self::validate_required( $required, 'POST', false )
			&& $this->is_admin_user()
		) {
			$msg = $this->membership_list_do_action(
				'toggle_' . $_POST['field'],
				array( $_POST['membership_id'] )
			);
		}

		do_action(
			'ms_controller_membership_ajax_action_toggle_membership',
			$msg,
			$this
		);

		wp_die( $msg );
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_membership
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_membership() {
		$msg = 0;

		$required = array( 'membership_id', 'field', 'value' );

		if ( $this->verify_nonce()
			&& self::validate_required( $required, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib2()->array->strip_slashes( $_POST, 'value' );

			$msg = $this->save_membership(
				array( $_POST['field'] => $_POST['value'] )
			);
		}

		do_action(
			'ms_controller_membership_ajax_action_update_membership',
			$msg,
			$this
		);

		wp_die( $msg );
	}

	/**
	 * Load membership from request.
	 *
	 * @since 1.0.0
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
						remove_query_arg( array( 'membership_id' ) )
					);
				}
			} elseif ( isset( $_REQUEST['page'] ) && 'protected-content-setup' == $_REQUEST['page'] ) {
				$membership_id = MS_Model_Membership::get_base()->id;
			}

			$this->model = MS_Factory::load(
				'MS_Model_Membership',
				$membership_id
			);

			$this->model = apply_filters(
				'ms_controller_membership_load_membership',
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
	 * @since 1.0.0
	 */
	public function membership_admin_page_process() {
		$msg = 0;
		$next_step = null;
		$step = $this->get_step();
		$goto_url = null;
		$membership = $this->load_membership();
		$membership_id = $membership->id;
		$completed = false;
		$is_wizard = MS_Plugin::is_wizard();
		$save_data = array();

		// Check if user came from WPMU Dashboard plugin
		if ( ! MS_Plugin::is_wizard() ) {
			$referer = $_SERVER['HTTP_REFERER'];
			$params = parse_url( $referer, PHP_URL_QUERY );
			$fields = array();
			parse_str( $params, $fields );
			if ( 'wpmudev-plugins' == $fields['page'] ) {
				$url = admin_url(
					'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-settings'
				);

				wp_safe_redirect( $url );
				exit;
			}
		}

		// MS_Controller_Rule is executed using this action.
		do_action(
			'ms_controller_membership_admin_page_process_' . $step,
			$this->get_active_tab()
		);

		// Only accessible to admin users
		if ( ! $this->is_admin_user() ) { return false; }

		if ( $this->verify_nonce( null, 'any' ) ) {
			// Take next actions based in current step.

			// Check if we are editing a membership.
			$save_membership = $this->verify_nonce( self::ACTION_SAVE );

			if ( $save_membership ) {
				// Save the current Setup-Process.
				$save_data = $_POST;
				unset( $save_data['_wpnonce'] );
				unset( $save_data['action'] );

				if ( isset( $_POST['set_private_flag'] ) ) {
					lib2()->array->equip_post( 'public' );
					$save_data['public'] = ! lib2()->is_true( $_POST['public'] );
				}
				if ( isset( $_POST['set_paid_flag'] ) ) {
					lib2()->array->equip_post( 'paid' );
					$save_data['is_free'] = ! lib2()->is_true( $_POST['paid'] );
				}

				$msg = $this->save_membership( $save_data );

				// Refresh the $membership variable.
				$membership = $this->load_membership();
				$membership_id = $membership->id;
			}

			switch ( $step ) {
				case self::STEP_MS_LIST:
					// Display a list of all memberships

					$fields = array( 'action', 'membership_id' );
					if ( self::validate_required( $fields, 'GET' ) ) {
						$msg = $this->membership_list_do_action(
							$_GET['action'],
							array( absint( $_GET['membership_id'] ) )
						);
						$next_step = self::STEP_MS_LIST;
					}
					break;

				case self::STEP_ADD_NEW:
					// Create Membership: Select the Membership-Type

					$paid = isset( $_POST['set_paid_flag'] )
						&& isset( $_POST['paid'] )
						&& lib2()->is_true( $_POST['paid'] );

					if ( $paid ) {
						$next_step = self::STEP_PAYMENT;
					} else {
						$next_step = self::STEP_MS_LIST;
						$msg = $this->mark_setup_completed();
						$completed = true;
					}

					if ( $is_wizard ) {
						// End the wizard!
						$this->wizard_tracker( $next_step, true );
					}
					break;

				case self::STEP_PAYMENT:
					// Setup payment options

					$next_step = self::STEP_MS_LIST;
					$msg = $this->mark_setup_completed();
					$completed = true;
					break;
			}

			if ( ! empty( $next_step ) ) {
				$args = array(
					'step' => $next_step,
					'membership_id' => $membership_id,
				);
				if ( ! empty( $msg ) ) {
					$args['msg'] = $msg;
				}

				$goto_url = add_query_arg(
					$args,
					MS_Controller_Plugin::get_admin_url()
				);

				$goto_url = apply_filters(
					'ms_controller_membership_membership_admin_page_process_goto_url',
					$goto_url,
					$next_step
				);

				if ( $completed ) {
					MS_Plugin::flush_rewrite_rules( $goto_url );
				} else {
					wp_safe_redirect( $goto_url );
				}
				exit;
			}
		} elseif ( $this->verify_nonce( 'bulk' ) ) {
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
	 * Route page request to handling method.
	 *
	 * @since 1.0.0
	 */
	public function membership_admin_page_router() {
		$this->wizard_tracker();
		$step = $this->get_step();

		if ( self::is_valid_step( $step ) ) {
			$method = 'page_' . $step;

			if ( method_exists( $this, $method ) ) {
				$callback = apply_filters(
					'ms_controller_membership_admin_page_router_callback',
					array( $this, $method ),
					$this
				);
				call_user_func( $callback );
			} else {
				do_action(
					'ms_controller_membership_admin_page_router_' . $step,
					$this
				);
				MS_Helper_Debug::log( "Method $method not found for step $step" );
			}
		} else {
			MS_Helper_Debug::log( "Invalid step: $step" );
		}

		do_action(
			'ms_controller_membership_admin_page_router',
			$step,
			$this
		);
	}

	/**
	 * Mark membership setup as complete.
	 *
	 * @since 1.0.0
	 *
	 * @return int $msg The action status message code.
	 */
	private function mark_setup_completed() {
		$msg = 0;
		$membership = $this->load_membership();

		if ( $membership->setup_completed() ) {
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_ADDED;
			do_action(
				'ms_controller_membership_setup_completed',
				$membership
			);
		}

		return apply_filters(
			'ms_controller_membership_mark_setup_completed',
			$msg,
			$this
		);
	}

	/**
	 * Display Setup Protected Content page.
	 *
	 * @since 1.0.0
	 */
	public function page_protected_content() {
		$data = array();
		$data['tabs'] = $this->get_available_tabs();
		$data['active_tab'] = $this->get_active_tab();

		$view = MS_Factory::create( 'MS_View_Membership_ProtectedContent' );
		$view->data = apply_filters( 'ms_view_membership_protectedcontent_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Choose Membership Type page.
	 *
	 * @since 1.0.0
	 */
	public function page_add() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = self::ACTION_SAVE;
		$data['membership'] = $this->load_membership();

		$view = MS_Factory::create( 'MS_View_Membership_Add' );
		$view->data = apply_filters( 'ms_view_membership_add_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Membership List page.
	 *
	 * @since 1.0.0
	 */
	public function page_ms_list() {
		$membership = $this->load_membership();

		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = self::ACTION_SAVE;
		$data['membership'] = $membership;
		$data['create_new_url'] = add_query_arg(
			array( 'step' => self::STEP_ADD_NEW ),
			MS_Controller_Plugin::get_admin_url()
		);

		$view = MS_Factory::create( 'MS_View_Membership_List' );
		$view->data = apply_filters( 'ms_view_membership_list_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Setup Payment page.
	 *
	 * @since 1.0.0
	 */
	public function page_payment() {
		$membership = $this->load_membership();

		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'save_payment_settings';
		$data['membership'] = $membership;
		$data['is_global_payments_set'] = MS_Plugin::instance()->settings->is_global_payments_set;
		$data['bread_crumbs'] = $this->get_bread_crumbs();

		if ( isset( $_GET['edit'] ) ) {
			$data['show_next_button'] = false;
		} else {
			$data['show_next_button'] = array(
				'id' => 'next',
				'value' => __( 'Finish', MS_TEXT_DOMAIN ),
				'action' => 'next',
			);
		}

		$view = MS_Factory::create( 'MS_View_Membership_Payment' );
		$view->data = apply_filters( 'ms_view_membership_payment_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Membership Overview page.
	 *
	 * @since 1.0.0
	 */
	public function page_ms_overview() {
		$membership = $this->load_membership();
		$membership_id = $membership->id;

		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = self::ACTION_SAVE;
		$data['membership'] = $membership;
		$data['bread_crumbs'] = $this->get_bread_crumbs();

		$data['members'] = array();
		$ms_relationships = MS_Model_Relationship::get_subscriptions(
			array( 'membership_id' => $membership->id )
		);

		foreach ( $ms_relationships as $ms_relationship ) {
			$data['members'][] = $ms_relationship->get_member();
		}

		switch ( $membership->type ) {
			case MS_Model_Membership::TYPE_DRIPPED:
				$view = MS_Factory::create( 'MS_View_Membership_Overview_Dripped' );
				break;

			default:
			case MS_Model_Membership::TYPE_STANDARD:
				$view = MS_Factory::create( 'MS_View_Membership_Overview_Simple' );
				break;
		}

		// Select Events args
		$args = array();
		$args['meta_query']['membership_id'] = array(
			'key'     => 'membership_id',
			'value'   => array( $membership_id, 0 ),
			'compare' => 'IN',
		);
		$data['events'] = MS_Model_Event::get_events( $args );

		$view = apply_filters( 'ms_view_membership_ms_overview', $view );
		$view->data = apply_filters( 'ms_view_membership_ms_overview_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Membership News page.
	 *
	 * @since 1.0.0
	 */
	public function page_ms_news() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = '';
		$data['membership'] = $this->load_membership();

		$args = apply_filters(
			'ms_controller_membership_page_ms_news_event_args',
			array( 'posts_per_page' => -1 )
		);
		$data['events'] = MS_Model_Event::get_events( $args );

		$view = MS_Factory::create( 'MS_View_Membership_News' );
		$view->data = apply_filters( 'ms_view_membership_news_data', $data, $this );
		$view->render();
	}

	/**
	 * Display a welcome screen.
	 *
	 * @since 1.1.0
	 */
	public function page_welcome() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = 'start';

		$view = MS_Factory::create( 'MS_View_Welcome' );
		$view->data = apply_filters( 'ms_view_welcome_data', $data, $this );
		$view->render();
	}

	/**
	 * Get Membership setup steps.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] The existing steps.
	 */
	public static function get_steps() {
		static $steps;

		if ( empty( $steps ) ) {
			$steps = array(
				self::STEP_MS_LIST,
				self::STEP_OVERVIEW,
				self::STEP_NEWS,
				self::STEP_PROTECTED_CONTENT,
				self::STEP_ADD_NEW,
				self::STEP_PAYMENT,
			);

			if ( MS_Plugin::is_wizard() ) {
				$steps[] = self::STEP_WELCOME_SCREEN;
			}
		}

		return apply_filters(
			'ms_controller_membership_get_steps',
			$steps
		);
	}

	/**
	 * Validate Membership setup step.
	 *
	 * @since 1.0.0
	 *
	 * @param string $step The step name to validate.
	 * @return boolean True if valid step.
	 */
	public static function is_valid_step( $step ) {
		$valid = false;

		$steps = self::get_steps();
		if ( in_array( $step, $steps ) ) {
			$valid = true;
		}

		return apply_filters(
			'ms_controller_membership_is_valid_step',
			$valid,
			$step
		);
	}

	/**
	 * Get current step.
	 *
	 * Try to retrieve step from request.
	 * Validate the step, returning a default if not valid.
	 *
	 * since 1.0.0
	 *
	 * @return string The current step.
	 */
	public function get_step() {
		// Initial step
		$step = self::STEP_MS_LIST;
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$membership = $this->load_membership();

		// Get current step from request
		if ( ! empty( $_REQUEST['step'] ) && self::is_valid_step( $_REQUEST['step'] ) ) {
			$step = $_REQUEST['step'];
		}

		// If user has left before completing the wizard, try to recover last wizard step.
		elseif ( MS_Plugin::is_wizard() ) {
			$wizard_steps = apply_filters(
				'ms_controller_membership_wizard_steps',
				array(
					self::STEP_WELCOME_SCREEN,
					self::STEP_ADD_NEW,
				)
			);

			if ( $settings->wizard_step
				&& in_array( $settings->wizard_step, $wizard_steps )
			) {
				$step = $settings->wizard_step;
			} else {
				$step = self::STEP_WELCOME_SCREEN;
			}
		}

		// Hack to use same page in two different menus
		$the_page = sanitize_html_class( @$_GET['page'] );
		if ( MS_Controller_Plugin::MENU_SLUG . '-setup' === $the_page ) {
			$step = self::STEP_PROTECTED_CONTENT;
		}

		// Can't modify membership type
		if ( self::STEP_ADD_NEW == $step && $membership->is_valid() ) {
			$step = self::STEP_OVERVIEW;
		}

		return apply_filters(
			'ms_controller_membership_get_next_step',
			$step,
			$this
		);
	}

	/**
	 * Track wizard step.
	 *
	 * Save current step.
	 *
	 * since 1.0.0
	 *
	 * @param string $step Optional. The step to save. Default to current step.
	 * @param boolean $end_wizard Optional. Whether end the wizard mode.
	 * @return string The current step.
	 */
	public function wizard_tracker( $step = null, $end_wizard = false ) {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		if ( empty( $step ) ) {
			$step = $this->get_step();
		}

		if ( MS_Plugin::is_wizard() ) {
			$settings->wizard_step = $step;

			if ( $end_wizard ) {
				$settings->initial_setup = false;
			}
			$settings->save();
		}

		do_action(
			'ms_controller_membership_wizard_tracker',
			$step,
			$end_wizard,
			$settings,
			$this
		);
	}

	/**
	 * Get available tabs for Protected Content page.
	 *
	 * @since 1.0.0
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
				'ms_controller_membership_tabs',
				$tabs,
				$membership_id
			);

			$url = admin_url( 'admin.php' );
			$page = sanitize_html_class( $_GET['page'], 'protected-content-memberships' );
			$rule_titles = MS_Model_Rule::get_rule_type_titles();

			$result = array();
			foreach ( $tabs as $rule_type => $state ) {
				if ( ! $state ) { continue; }

				$url = admin_url(
					sprintf(
						'admin.php?page=%s&tab=%s',
						$page,
						$rule_type
					)
				);

				// Try to keep the selected Membership and Status filter.
				if ( ! empty( $_REQUEST['membership_id'] ) ) {
					$url = add_query_arg(
						array( 'membership_id' => $_REQUEST['membership_id'] ),
						$url
					);
				}
				if ( ! empty( $_REQUEST['status'] ) ) {
					$url = add_query_arg(
						array( 'status' => $_REQUEST['status'] ),
						$url
					);
				}

				$result[ $rule_type ] = array(
					'title' => $rule_titles[ $rule_type ],
					'url' => $url,
				);
			}

			$Tabs = apply_filters(
				'ms_controller_membership_get_protection_tabs',
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
	 * @since 1.0.0
	 *
	 * @return string The active tab.
	 */
	public function get_active_tab() {
		$step = $this->get_step();
		$tabs = array();

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
			'ms_controller_membership_get_active_tab',
			$active_tab
		);

		return $this->active_tab;
	}

	/**
	 * Execute action in Membership model.
	 *
	 * @since 1.0.0
	 *
	 * @todo There is no more bulk actions. Deprecate this method and create a specific one.
	 *
	 * @param string $action The action to execute.
	 * @param int[] $membership_ids The membership ids which action will be taken.
	 * @return number Resulting message id.
	 */
	private function membership_list_do_action( $action, $membership_ids ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$msg = 0;
		foreach ( $membership_ids as $membership_id ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );

			switch ( $action ) {
				case 'toggle_active':
				case 'toggle_activation':
					$membership->active = ! $membership->active;
					$membership->save();
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_ACTIVATION_TOGGLED;
					break;

				case 'toggle_public':
					$membership->private = ! $membership->private;
					$membership->save();
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_STATUS_TOGGLED;
					break;

				case 'delete':
					try {
						$membership->delete();
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DELETED;
					}
					catch( Exception $e ) {
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_DELETED;
					}
					break;
			}
		}

		return $msg;
	}

	/**
	 * Get Membership page bread crumbs.
	 *
	 * @since 1.0.0
	 *
	 * @return array The bread crumbs array.
	 */
	public function get_bread_crumbs() {
		$step = $this->get_step();
		$membership = $this->load_membership();

		$bread_crumbs = array();

		switch ( $step ) {
			case self::STEP_OVERVIEW:
				$bread_crumbs['prev'] = array(
					'title' => __( 'Memberships', MS_TEXT_DOMAIN ),
					'url' => admin_url(
						sprintf(
							'admin.php?page=%s&step=%s',
							MS_Controller_Plugin::MENU_SLUG,
							self::STEP_MS_LIST
						)
					),
				);
				$bread_crumbs['current'] = array(
					'title' => $membership->name,
				);
				break;

			case self::STEP_PAYMENT:
				$bread_crumbs['prev'] = array(
					'title' => $membership->name,
					'url' => admin_url(
						sprintf(
							'admin.php?page=%s&step=%s&membership_id=%s',
							MS_Controller_Plugin::MENU_SLUG,
							self::STEP_OVERVIEW,
							$membership->id
						)
					),
				);
				$bread_crumbs['current'] = array(
					'title' => __( 'Payment', MS_TEXT_DOMAIN ),
				);
				break;
		}

		// Add the "edit" param if it is set.
		if ( isset( $_GET['edit'] ) ) {
			foreach ( $bread_crumbs as $key => $data ) {
				if ( isset( $bread_crumbs[$key]['url'] ) ) {
					$bread_crumbs[$key]['url'] .= '&edit=1';
				}
			}
		}

		return apply_filters(
			'ms_controller_membership_get_bread_crumbs',
			$bread_crumbs,
			$this
		);
	}

	/**
	 * Save membership general tab fields
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $fields
	 */
	private function save_membership( $fields ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;

		if ( $this->is_admin_user() ) {
			$membership = $this->load_membership();

			if ( is_array( $fields ) ) {
				$updated = 0;
				$failed = 0;
				foreach ( $fields as $field => $value ) {
					try {
						$membership->$field = $value;
						$updated += 1;
					}
					catch ( Exception $e ) {
						$failed += 1;
					}
				}
				$membership->save();

				if ( $updated > 0 ) {
					if ( ! $failed ) {
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
					} else {
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_PARTIALLY_UPDATED;
					}
				}
			}
		}

		return apply_filters(
			'ms_controller_membership_save_membership_msg',
			$msg,
			$fields,
			$this
		);
	}

	/**
	 * Load Membership manager specific styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		switch ( $this->get_active_tab() ) {
			default:
				lib2()->ui->add( 'jquery-ui' );
				break;
		}

		do_action( 'ms_controller_membership_enqueue_styles', $this );
	}

	/**
	 * Load Membership manager specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		/*
		 * Get a list of the dripped memberships:
		 * We need this info in the javascript
		 */
		$dripped = array();
		foreach ( MS_Model_Membership::get_dripped_memberships() as $item ) {
			$dripped[ $item->id ] = $item->name;
		}

		$data = array(
			'ms_init' => array(),
			'lang' => array(
				'msg_delete' => __( 'Do you want to completely delete the membership <strong>%s</strong> including all subscriptions?', MS_TEXT_DOMAIN ),
				'btn_delete' => __( 'Delete', MS_TEXT_DOMAIN ),
				'btn_cancel' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'quickedit_error' => __( 'Error while saving changes.', MS_TEXT_DOMAIN ),
			),
			'dripped' => $dripped,
		);

		$step = $this->get_step();

		switch ( $step ) {
			case self::STEP_WELCOME_SCREEN:
				break;

			case self::STEP_ADD_NEW:
				$data['ms_init'][] = 'view_membership_add';
				$data['initial_url'] = admin_url( 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG );
				break;

			case self::STEP_OVERVIEW:
				$data['ms_init'][] = 'view_membership_overview';
				break;

			case self::STEP_PROTECTED_CONTENT:
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
				break;

			case self::STEP_PAYMENT:
				$data['ms_init'][] = 'view_membership_payment';
				$data['ms_init'][] = 'view_settings_payment';
				wp_enqueue_script( 'jquery-validate' );
				break;

			case self::STEP_MS_LIST:
				$data['ms_init'][] = 'view_membership_list';
				$data['ms_init'][] = 'view_settings_setup';
				break;
		}

		lib2()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );

		do_action( 'ms_controller_membership_enqueue_scripts', $this );
	}

}