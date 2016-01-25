<?php
/**
 * Controller for managing Memberships and Membership Rules.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Membership extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_MEMBERSHIP = 'toggle_membership';
	const AJAX_ACTION_UPDATE_MEMBERSHIP = 'update_membership';
	const AJAX_ACTION_SET_CUSTOM_FIELD = 'membership_set_custom_field';

	/**
	 * Membership page step constants.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const STEP_MS_LIST = 'list';
	const STEP_EDIT = 'edit';
	const STEP_OVERVIEW = 'overview';
	const STEP_NEWS = 'news';
	const STEP_WELCOME_SCREEN = 'welcome';
	const STEP_ADD_NEW = 'add';
	const STEP_PAYMENT = 'payment';

	/**
	 * Membership Editor tabs.
	 *
	 * @since 1.0.1.0
	 *
	 * @var   string
	 */
	const TAB_DETAILS = 'details';
	const TAB_TYPE = 'type';
	const TAB_PAYMENT = 'payment';
	const TAB_UPGRADE = 'upgrade';
	const TAB_PAGES = 'pages';
	const TAB_MESSAGES = 'messages';
	const TAB_EMAILS = 'emails';

	// Actions
	const ACTION_SAVE = 'save_membership';

	/**
	 * The model to use for loading/saving Membership data.
	 *
	 * @since  1.0.0
	 * @var MS_Model_Membership
	 */
	private $model = null;

	/**
	 * The current active tab in the vertical navigation.
	 *
	 * @since  1.0.1.0
	 *
	 * @var string
	 */
	private $active_tab = null;

	/**
	 * Prepare the Membership manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_ajax_action(
			self::AJAX_ACTION_TOGGLE_MEMBERSHIP,
			'ajax_action_toggle_membership'
		);
		$this->add_ajax_action(
			self::AJAX_ACTION_UPDATE_MEMBERSHIP,
			'ajax_action_update_membership'
		);
		$this->add_ajax_action(
			self::AJAX_ACTION_SET_CUSTOM_FIELD,
			'ajax_action_set_custom_field'
		);

		// Tries to auto-detect the currently displayed membership-ID.
		$this->add_filter(
			'ms_detect_membership_id',
			'autodetect_membership'
		);
                
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hooks = array(
			MS_Controller_Plugin::admin_page_hook(),
			MS_Controller_Plugin::admin_page_hook( 'setup' ),
		);

		foreach ( $hooks as $hook ) {
			$this->run_action( 'load-' . $hook, 'process_admin_page' );
			$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
			$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );

			if ( self::TAB_EMAILS == $this->get_active_edit_tab() ) {
				$this->run_action(
					'admin_head-' . $hook,
					array( 'MS_Controller_Communication', 'add_mce_buttons' )
				);
			}
		}
                
                $this->add_action(
                        'admin_action_membership_bulk_delete',
                        'membership_bulk_delete'
                );
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_toggle_membership
	 *
	 * @since  1.0.0
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
         * Bulk delete memberships
         *
         * @since 1.0.2.7
         */
        public function membership_bulk_delete() {
            
            if( ! isset( $_REQUEST['membership_ids'] ) ) {
                wp_redirect( MS_Controller_Plugin::get_admin_url() );
                exit;
            }
            
            $membership_ids = explode( '-', $_REQUEST['membership_ids'] );
            
            foreach( $membership_ids as $membership_id ) {
                $membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
                try {
                    $membership->delete();
                }
                catch( Exception $e ) {
                    
                }
            }
            
            wp_redirect( MS_Controller_Plugin::get_admin_url() );
            exit;
            
        }

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_membership
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_update_membership() {
		$msg = 0;

		$required = array( 'membership_id', 'field', 'value' );

		if ( $this->verify_nonce()
			&& self::validate_required( $required, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib3()->array->strip_slashes( $_POST, 'value', 'field' );

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
	 * Ajax handler to update a custom field of the membership.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_membership_set_custom_field
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_action_set_custom_field() {
		$msg = 0;

		$required = array( 'membership_id', 'field', 'value' );

		if ( $this->verify_nonce()
			&& self::validate_required( $required, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib3()->array->strip_slashes( $_POST, 'value', 'field' );
			$membership = MS_Factory::load(
				'MS_Model_Membership',
				intval( $_POST['membership_id'] )
			);
			$membership->set_custom_data( $_POST['field'], $_POST['value'] );
			$membership->save();

			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
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
	 * Tries to auto-detect the currently displayed membership-ID.
	 *
	 * Use this function by calling the filter `ms_detect_membership_id`
	 *
	 * Detection logic:
	 * 1. If a valid preferred value was specified then this value is used.
	 * 2. Examine REQUEST data and look for membership/subscription/invoice.
	 * 3. Check currently logged in user and use the top-priority subscription.
	 *
	 * @since  1.0.1.0
	 * @param  int $preferred The preferred ID is only used if it is a valid ID.
	 * @param  bool $no_member_check If set to true the member subscriptions are
	 *         not checked, which means only REQUEST data is examined.
	 * @param  bool $ignore_system If set to true, then the return value will
	 *         never be a system-membership-ID (no auto-assigned membership).
	 * @return int A valid Membership ID or 0 if all tests fail.
	 */
	public function autodetect_membership( $preferred = 0, $no_member_check = false, $ignore_system = false ) {
		$membership_id = 0;

		// Check 1: If the preferred value is correct use it.
		if ( $preferred ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $preferred );

			// Only use the membership_id if it's valid and not filtered by ignore_system.
			if ( $membership->is_valid() && $membership->id == $preferred ) {
				if ( ! $ignore_system || ! $membership->is_system() ) {
					$membership_id = $membership->id;
				}
			}
		}

		// Check 2: Examine the REQUEST parameters to find a valid ID.
		if ( ! $membership_id ) {
			if ( ! $membership_id ) {
				if ( isset( $_REQUEST['membership_id'] ) ) {
					$membership_id = $_REQUEST['membership_id'];
				} elseif ( isset( $_REQUEST['subscription_id'] ) ) {
					$sub_id = $_REQUEST['subscription_id'];
					$subscription = MS_Factory::load( 'MS_Model_Relationship', $sub_id );
					$membership_id = $subscription->membership_id;
				} elseif ( isset( $_REQUEST['ms_relationship_id'] ) ) {
					$sub_id = $_REQUEST['ms_relationship_id'];
					$subscription = MS_Factory::load( 'MS_Model_Relationship', $sub_id );
					$membership_id = $subscription->membership_id;
				} elseif ( isset( $_REQUEST['invoice_id'] ) ) {
					$inv_id = $_REQUEST['invoice_id'];
					$invoice = MS_Factory::load( 'MS_Model_Invoice', $inv_id );
					$membership_id = $invoice->membership_id;
				}
				$membership_id = intval( $membership_id );
			}

			// Reset the membership_id if it's invalid or filtered by ignore_system.
			if ( $membership_id ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
				if ( ! $membership->is_valid() ) {
					$membership_id = 0;
				} elseif ( $membership->id != $membership_id ) {
					$membership_id = 0;
				} elseif ( $ignore_system && $membership->is_system ) {
					$membership_id = 0;
				}
			}
		}

		// Check 3: Check subscriptions of the current user.
		if ( ! $no_member_check && ! $membership_id && is_user_logged_in() ) {
			$member = MS_Model_Member::get_current_member();
			$subscription = $member->get_subscription( 'priority' );
			if ( $subscription ) {
				$membership_id = $subscription->membership_id;
			}

			// Reset the membership_id if it's invalid or filtered by ignore_system.
			if ( $membership_id ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
				if ( ! $membership->is_valid() ) {
					$membership_id = 0;
				} elseif ( $membership->id != $membership_id ) {
					$membership_id = 0;
				} elseif ( $ignore_system && $membership->is_system ) {
					$membership_id = 0;
				}
			}
		}

		return apply_filters(
			'ms_controller_membership_autodetect_membership',
			$membership_id,
			$preferred,
			$no_member_check
		);
	}

	/**
	 * Process membership pages requests
	 *
	 * Verifies GET and POST requests to manage memberships.
	 * Redirect to next step after processing.
	 *
	 * @since  1.0.0
	 */
	public function process_admin_page() {
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
		if ( ! $is_wizard && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = $_SERVER['HTTP_REFERER'];
			$params = parse_url( $referer, PHP_URL_QUERY );
			$fields = array();
			parse_str( $params, $fields );
			if ( isset( $fields['page'] ) && 'wpmudev-plugins' == $fields['page'] ) {
				$url = MS_Controller_Plugin::get_admin_url( 'settings' );

				wp_safe_redirect( $url );
				exit;
			}
		}

		// MS_Controller_Rule is executed using this action.
		do_action( 'ms_controller_membership_admin_page_process_' . $step );

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
					lib3()->array->equip_post( 'public' );
					$save_data['public'] = ! lib3()->is_true( $_POST['public'] );
				}
				if ( isset( $_POST['set_paid_flag'] ) ) {
					lib3()->array->equip_post( 'paid' );
					$save_data['is_free'] = ! lib3()->is_true( $_POST['paid'] );
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
						&& lib3()->is_true( $_POST['paid'] );

					if ( $paid ) {
						$next_step = self::STEP_PAYMENT;
					} else {
						$next_step = self::STEP_MS_LIST;
					}
					$msg = $this->mark_setup_completed();
					$completed = true;
					break;

				case self::STEP_PAYMENT:
					// Setup payment options

					$next_step = self::STEP_MS_LIST;
					break;

				case self::STEP_EDIT:
					$this->process_edit_page();
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

				/*
				 * Param 'MENU_SLUG' forces the admin-URL to use the top-level
				 * menu slug instead of the current base_slug.
				 * During Setup-wizard the base_slug ends in '-setup', but when
				 * we reach this point the wizard is completed.
				 */
				$slug = 'MENU_SLUG';

				$goto_url = esc_url_raw(
					add_query_arg(
						$args,
						MS_Controller_Plugin::get_admin_url( $slug )
					)
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
		} else {
			// No action request found.
		}
	}

	/**
	 * Process form data submitted via the edit page.
	 *
	 * When this function is called the nonce is already confirmed.
	 *
	 * @since  1.0.1.0
	 */
	protected function process_edit_page() {
		$redirect = false;

		switch ( $this->get_active_edit_tab() ) {
			case self::TAB_TYPE:
				$fields_type = array( 'membership_id', 'type' );

				if ( self::validate_required( $fields_type ) ) {
					$id = intval( $_POST['membership_id'] );
					$membership = MS_Factory::load( 'MS_Model_Membership', $id );

					if ( $membership->id == $id && ! $membership->is_system() ) {
						$membership->type = $_POST['type'];
						$membership->save();
					}
				}
				break;

			case self::TAB_MESSAGES:
				break;
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	/**
	 * Route page request to handling method.
	 *
	 * @since  1.0.0
	 */
	public function admin_page_router() {
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
	 * @since  1.0.0
	 *
	 * @return int $msg The action status message code.
	 */
	private function mark_setup_completed() {
		$msg = 0;
		$membership = $this->load_membership();

		if ( $membership->setup_completed() ) {
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_ADDED;

			/**
			 * Action run after a new membership was created.
			 *
			 * @since  1.0.1.0
			 * @param  MS_Model_Membership $membership The new membership.
			 */
			do_action(
				'ms_controller_membership_created',
				$membership
			);

			if ( MS_Plugin::is_wizard() ) {
				$this->wizard_tracker( null, true );

				/**
				 * Action run after the first membership was created and the
				 * setup wizard is completed.
				 *
				 * This hook is used by M2 to auto-setup some settings according
				 * to the first membership that was created (e.g. create menu
				 * items, enable Automatic Email Responses, etc.)
				 *
				 * This filter is only executed ONCE! To perform actions always
				 * when a membership was created use the '_created' action above.
				 *
				 * @since  1.0.0
				 * @param  MS_Model_Membership $membership The new membership.
				 */
				do_action(
					'ms_controller_membership_setup_completed',
					$membership
				);
			}
		}

		return apply_filters(
			'ms_controller_membership_mark_setup_completed',
			$msg,
			$this
		);
	}

	/**
	 * Display Choose Membership Type page.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public function page_list() {
		$membership = $this->load_membership();

		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = self::ACTION_SAVE;
		$data['membership'] = $membership;
		$data['create_new_url'] = MS_Controller_Plugin::get_admin_url(
			false,
			array( 'step' => self::STEP_ADD_NEW )
		);
                $data['delete_url'] = admin_url( 'admin.php?action=membership_bulk_delete' );

		$view = MS_Factory::create( 'MS_View_Membership_List' );
		$view->data = apply_filters( 'ms_view_membership_list_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Membership Edit page.
	 *
	 * @since  1.0.0
	 */
	public function page_edit() {
		$membership = $this->load_membership();

		$data = array();
		$data['tabs'] = $this->get_edit_tabs();
		$data['settings'] = MS_Plugin::instance()->settings;
		$data['membership'] = $membership;

		switch ( $this->get_active_edit_tab() ) {
			case self::TAB_EMAILS:
				$default_type = MS_Model_Communication::COMM_TYPE_REGISTRATION;
				if ( ! empty( $_REQUEST['membership_id'] ) ) {
					$membership_id = intval( $_REQUEST['membership_id'] );
					$comm_types = array_keys(
						MS_Model_Communication::get_communication_type_titles(
							$membership_id
						)
					);
					$default_type = reset( $comm_types );
				}

				$temp_type = isset( $_GET['comm_type'] ) ? $_GET['comm_type'] : '';
				if ( MS_Model_Communication::is_valid_communication_type( $temp_type ) ) {
					$type = $temp_type;
				} else {
					$type = $default_type;
				}

				$comm = MS_Model_Communication::get_communication(
					$type,
					$membership,
					true
				);

				$data['comm'] = $comm;
				break;
		}

		$view = MS_Factory::create( 'MS_View_Membership_Edit' );
		$view->data = apply_filters( 'ms_view_membership_edit_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Setup Payment page.
	 *
	 * @since  1.0.0
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
				'value' => __( 'Finish', 'membership2' ),
				'action' => 'next',
			);
		}

		$view = MS_Factory::create( 'MS_View_Membership_PaymentSetup' );
		$view->data = apply_filters( 'ms_view_membership_payment_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Membership Overview page.
	 *
	 * @since  1.0.0
	 */
	public function page_overview() {
		$membership = $this->load_membership();
		$membership_id = $membership->id;

		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = self::ACTION_SAVE;
		$data['membership'] = $membership;
		$data['bread_crumbs'] = $this->get_bread_crumbs();

		$data['members'] = array();
		$subscriptions = MS_Model_Relationship::get_subscriptions(
			array( 'membership_id' => $membership->id )
		);

		foreach ( $subscriptions as $subscription ) {
			$data['members'][] = $subscription->get_member();
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

		$view = apply_filters( 'ms_view_membership_overview', $view );
		$view->data = apply_filters( 'ms_view_membership_overview_data', $data, $this );
		$view->render();
	}

	/**
	 * Display Membership News page.
	 *
	 * @since  1.0.0
	 */
	public function page_news() {
		$data = array();
		$data['step'] = $this->get_step();
		$data['action'] = '';
		$data['membership'] = $this->load_membership();

		$args = apply_filters(
			'ms_controller_membership_page_news_event_args',
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @return string[] The existing steps.
	 */
	public static function get_steps() {
		static $Steps;

		if ( empty( $Steps ) ) {
			$Steps = array(
				self::STEP_MS_LIST,
				self::STEP_OVERVIEW,
				self::STEP_EDIT,
				self::STEP_NEWS,
				self::STEP_ADD_NEW,
				self::STEP_PAYMENT,
			);

			if ( MS_Plugin::is_wizard() ) {
				$Steps[] = self::STEP_WELCOME_SCREEN;
			}
		}

		return apply_filters(
			'ms_controller_membership_get_steps',
			$Steps
		);
	}

	/**
	 * Validate Membership setup step.
	 *
	 * @since  1.0.0
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
	 * Get available tabs for Membership2 page.
	 *
	 * @since  1.0.0
	 *
	 * @return array The tabs configuration.
	 */
	public function get_edit_tabs() {
		static $Tabs = null;

		if ( null === $Tabs ) {
			$membership = $this->load_membership();

			$args = array( 'include_guest' => false );
			$count = MS_Model_Membership::get_membership_count( $args );

			$Tabs = array(
				self::TAB_DETAILS => array(
					'title' => __( 'Details', 'membership2' ),
				),
				self::TAB_TYPE => array(
					'title' => __( 'Membership Type', 'membership2' ),
				),
				self::TAB_PAYMENT => array(
					'title' => __( 'Payment options', 'membership2' ),
				),
				self::TAB_UPGRADE => array(
					'title' => __( 'Upgrade paths', 'membership2' ),
				),
				/* Not yet finished... will be added soon.
				self::TAB_PAGES => array(
					'title' => __( 'Membership Pages', 'membership2' ),
				),
				*/
				self::TAB_MESSAGES => array(
					'title' => __( 'Protection Messages', 'membership2' ),
				),
				self::TAB_EMAILS => array(
					'title' => __( 'Automated Email Responses', 'membership2' ),
				),
			);

			if ( $membership->is_system() ) {
				unset( $Tabs[self::TAB_TYPE] );
				unset( $Tabs[self::TAB_PAYMENT] );
				unset( $Tabs[self::TAB_EMAILS] );
				unset( $Tabs[ self::TAB_UPGRADE ] );
			} elseif ( $membership->is_free ) {
				$Tabs[self::TAB_PAYMENT]['title'] = __( 'Access options', 'membership2' );
			}

			if ( $count < 2 ) {
				unset( $Tabs[ self::TAB_UPGRADE ] );
			}

			// Allow Add-ons to add or remove rule tabs
			$Tabs = apply_filters(
				'ms_controller_membership_tabs',
				$Tabs
			);

			foreach ( $Tabs as $key => $tab ) {
				if ( ! empty( $Tabs['key']['url'] ) ) { continue; }

				$url = sprintf(
					'%1$s?page=%2$s&step=%3$s&tab=%4$s&membership_id=%5$s',
					admin_url( 'admin.php' ),
					esc_attr( $_REQUEST['page'] ),
					MS_Controller_Membership::STEP_EDIT,
					$key,
					$membership->id
				);

				$Tabs[$key]['url'] = $url;
			}
		}

		return $Tabs;
	}

	/**
	 * Get the current active settings page/tab.
	 *
	 * @since  1.0.1.0
	 */
	public function get_active_edit_tab() {
		if ( null === $this->active_tab ) {
			if ( self::STEP_EDIT != $this->get_step() ) {
				$this->active_tab = '';
			} else {
				$tabs = $this->get_edit_tabs();

				reset( $tabs );
				$first_key = key( $tabs );

				// Setup navigation tabs.
				lib3()->array->equip_get( 'tab' );
				$active_tab = sanitize_html_class( $_GET['tab'], $first_key );

				if ( ! array_key_exists( $active_tab, $tabs ) ) {
					$active_tab = $first_key;
				}
				$this->active_tab = $active_tab;
			}
		}

		return apply_filters(
			'ms_controller_membership_get_active_edit_tab',
			$this->active_tab,
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
		$settings = MS_Plugin::instance()->settings;

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
	 * Execute action in Membership model.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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
					'title' => __( 'Memberships', 'membership2' ),
					'url' => MS_Controller_Plugin::get_admin_url(
						false,
						array( 'step' => self::STEP_MS_LIST )
					),
				);
				$bread_crumbs['current'] = array(
					'title' => $membership->name,
				);
				break;

			case self::STEP_PAYMENT:
				$bread_crumbs['prev'] = array(
					'title' => $membership->name,
					'url' => MS_Controller_Plugin::get_admin_url(
						false,
						array(
							'step' => self::STEP_OVERVIEW,
							'membership_id' => $membership->id,
						)
					),
				);
				$bread_crumbs['current'] = array(
					'title' => __( 'Payment', 'membership2' ),
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
	 * @since  1.0.0
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
					$key = false;

					// Very basic support for array updates.
					// We only support updating 1-dimensional arrays with a
					// specified key value.
					if ( strpos( $field, '[' ) ) {
						$field = str_replace( ']', '', $field );
						list( $field, $key ) = explode( '[', $field, 2 );
					}

					try {
						$the_value = $membership->$field;
						if ( $key ) {
							$the_value = lib3()->array->get( $the_value );
							$the_value[$key] = $value;
						} else {
							$the_value = $value;
						}
						$membership->$field = $the_value;

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
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		lib3()->ui->add( 'jquery-ui' );

		do_action( 'ms_controller_membership_enqueue_styles', $this );
	}

	/**
	 * Load Membership manager specific scripts.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array(),
			'lang' => array(
				'msg_delete' => __( 'Do you want to completely delete the membership <strong>%s</strong> including all subscriptions?', 'membership2' ),
				'btn_delete' => __( 'Delete', 'membership2' ),
				'btn_cancel' => __( 'Cancel', 'membership2' ),
				'quickedit_error' => __( 'Error while saving changes.', 'membership2' ),
			),
		);

		$step = $this->get_step();

		switch ( $step ) {
			case self::STEP_WELCOME_SCREEN:
				break;

			case self::STEP_ADD_NEW:
				$data['ms_init'][] = 'view_membership_add';
				$data['initial_url'] = MS_Controller_Plugin::get_admin_url();
				break;

			case self::STEP_OVERVIEW:
				$data['ms_init'][] = 'view_membership_overview';
				break;

			case self::STEP_PAYMENT:
				$data['ms_init'][] = 'view_membership_payment';
				$data['ms_init'][] = 'view_settings_payment';
				break;

			case self::STEP_EDIT:
				$data['ms_init'][] = 'view_membership_payment';
				$tab = $this->get_active_edit_tab();

				switch ( $tab ) {
					case self::TAB_TYPE:
						add_thickbox();
						$data['ms_init'][] = 'view_membership_add';
						break;

					case self::TAB_UPGRADE:
						$data['ms_init'][] = 'view_membership_upgrade';
						break;

					case self::TAB_MESSAGES:
						$data['ms_init'][] = 'view_settings_protection';
						break;

					case self::TAB_EMAILS:
						$data['ms_init'][] = 'view_settings_automated_msg';
						break;
				}

				do_action(
					'ms_controller_membership_enqueue_scripts_tab-' . $tab,
					$this
				);
				break;

			case self::STEP_MS_LIST:
				$data['ms_init'][] = 'view_membership_list';
				$data['ms_init'][] = 'view_settings_setup';
				break;
		}

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
		wp_enqueue_script( 'jquery-validate' );

		do_action(
			'ms_controller_membership_enqueue_scripts',
			$this
		);
		do_action(
			'ms_controller_membership_enqueue_scripts-' . $step,
			$this
		);
	}

}