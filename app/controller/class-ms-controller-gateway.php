<?php
/**
 * Gateway controller.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Gateway extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_GATEWAY = 'toggle_gateway';
	const AJAX_ACTION_UPDATE_GATEWAY = 'update_gateway';

	/**
	 * Allowed actions to execute in template_redirect hook.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	private $allowed_actions = array( 'update_card', 'purchase_button' );

	/**
	 * Prepare the gateway controller.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action( 'template_redirect', 'process_actions', 1 );

		$this->add_action( 'ms_controller_gateway_settings_render_view', 'gateway_settings_edit' );

		$this->add_action( 'ms_view_shortcode_invoice_purchase_button', 'invoice_purchase_button', 10, 2 );
		$this->add_action( 'ms_view_frontend_payment_purchase_button', 'purchase_button', 10, 2 );

		$this->add_action( 'ms_controller_frontend_signup_gateway_form', 'gateway_form_mgr', 1 );
		$this->add_action( 'ms_controller_frontend_signup_process_purchase', 'process_purchase', 1 );
		$this->add_filter( 'ms_view_shortcode_membershipsignup_cancel_button', 'cancel_button', 10, 2 );

		$this->add_action( 'ms_view_shortcode_account_card_info', 'card_info' );

		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_action( 'ms_gateway_transaction_log', 'log_transaction', 10, 8 );

		$this->add_action( 'ms_controller_frontend_enqueue_scripts', 'enqueue_scripts' );

		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_GATEWAY, 'toggle_ajax_action' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_GATEWAY, 'ajax_action_update_gateway' );
	}

	/**
	 * Handle URI actions for registration.
	 *
	 * Matches returned 'action' to method to execute.
	 *
	 * Related action hooks:
	 * - template_redirect
	 *
	 * @since  1.0.0
	 */
	public function process_actions() {
		$action = $this->get_action();

		/**
		 * If $action is set, then call relevant method.
		 *
		 * Methods:
		 * @see $allowed_actions property
		 *
		 */
		if ( ! empty( $action )
			&& method_exists( $this, $action )
			&& in_array( $action, $this->allowed_actions )
		) {
			$this->$action();
		}
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related action hooks:
	 * - wp_ajax_toggle_gateway
	 *
	 * @since  1.0.0
	 */
	public function toggle_ajax_action() {
		$msg = 0;

		$fields = array( 'gateway_id' );
		if ( $this->verify_nonce()
			&& self::validate_required( $fields )
			&& $this->is_admin_user()
		) {
			$msg = $this->gateway_list_do_action(
				'toggle_activation',
				array( $_POST['gateway_id'] )
			);
		}

		wp_die( $msg );
	}

	/**
	 * Handle Ajax update gateway action.
	 *
	 * Related action hooks:
	 * - wp_ajax_update_gateway
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_update_gateway() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$fields = array( 'action', 'gateway_id', 'field', 'value' );
                
		if ( $this->verify_nonce()
			&& ( self::validate_required( $fields ) || $_POST['field'] == 'pay_button_url' )
			&& $this->is_admin_user()
		) {
			lib3()->array->strip_slashes( $_POST, 'value' );

			$msg = $this->gateway_list_do_action(
				$_POST['action'],
				array( $_POST['gateway_id'] ),
				array( $_POST['field'] => $_POST['value'] )
			);
		}

		wp_die( $msg );
	}

	/**
	 * Show gateway settings page.
	 *
	 *
	 * Related action hooks:
	 * - ms_controller_gateway_settings_render_view
	 *
	 * @since  1.0.0
	 */
	public function gateway_settings_edit( $gateway_id ) {
		if ( ! empty( $gateway_id )
			&& MS_Model_Gateway::is_valid_gateway( $gateway_id )
		) {
			switch ( $gateway_id ) {
				case MS_Gateway_Manual::ID:
					$view = MS_Factory::create( 'MS_Gateway_Manual_View_Settings' );
					break;

				case MS_Gateway_Paypalsingle::ID:
					$view = MS_Factory::create( 'MS_Gateway_Paypalsingle_View_Settings' );
					break;

				case MS_Gateway_Paypalstandard::ID:
					$view = MS_Factory::create( 'MS_Gateway_Paypalstandard_View_Settings' );
					break;

				case MS_Gateway_Authorize::ID:
					$view = MS_Factory::create( 'MS_Gateway_Authorize_View_Settings' );
					break;

				case MS_Gateway_Stripe::ID:
					$view = MS_Factory::create( 'MS_Gateway_Stripe_View_Settings' );
					break;

				default:
					// Empty form...
					$view = MS_Factory::create( 'MS_View' );
					break;
			}

			$data = array(
				'model' => MS_Model_Gateway::factory( $gateway_id ),
				'action' => 'edit',
			);

			$view->data = apply_filters(
				'ms_gateway_view_settings_edit_data',
				$data
			);
			$view = apply_filters(
				'ms_gateway_view_settings_edit',
				$view,
				$gateway_id
			);

			$view->render();
		}
	}

	/**
	 * Handle Payment Gateway list actions.
	 *
	 * @since  1.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $gateways The gateways IDs to process.
	 * @param mixed[] $fields The data to process.
	 */
	public function gateway_list_do_action( $action, $gateways, $fields = null ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		foreach ( $gateways as $gateway_id ) {
			$gateway = MS_Model_Gateway::factory( $gateway_id );

			switch ( $action ) {
				case 'toggle_activation':
					$gateway->active = ! $gateway->active;
					$gateway->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;

					/**
					 * Hook called after a gateway-status was toggled.
					 *
					 * @since  1.0.0
					 */
					do_action( 'ms_gateway_toggle_' . $gateway_id, $gateway );
					break;

				case 'edit':
				case 'update_gateway':
					foreach ( $fields as $field => $value ) {
						$gateway->$field = trim( $value );
					}
					$gateway->save();

					/*
					 * $settings->is_global_payments_set is used to hide global
					 * payment settings in the membership setup payment step
					 */
					if ( $gateway->is_configured() ) {
						$settings = MS_Factory::load( 'MS_Model_Settings' );
						$settings->is_global_payments_set = true;
						$settings->save();
						$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					} else {
						$msg = MS_Helper_Settings::SETTINGS_MSG_UNCONFIGURED;
					}

					/**
					 * Hook called after a gateway-settings were modified.
					 *
					 * @since  1.0.0
					 */
					do_action( 'ms_gateway_changed_' . $gateway_id, $gateway );
					break;
			}
		}

		return apply_filters(
			'ms_controller_gateway_gateway_list_do_action',
			$msg,
			$action,
			$gateways,
			$fields,
			$this
		);
	}

	/**
	 * Show gateway purchase button.
	 *
	 * Related action hooks:
	 * - ms_view_frontend_payment_purchase_button
	 * - ms_view_shortcode_invoice_purchase_button
	 *
	 * @since  1.0.0
	 */
	public function purchase_button( $subscription, $invoice ) {
		// Get only active gateways
		$gateways = MS_Model_Gateway::get_gateways( true );
		$data = array();

		$membership = $subscription->get_membership();
		$is_free = false;

		if ( $membership->is_free() ) {
			$is_free = true;
		} elseif ( 0 == $invoice->total ) {
			$is_free = true;
		} elseif ( $invoice->uses_trial ) {
			if ( defined( 'MS_PAYPAL_TRIAL_SUBSCRIPTION' ) && MS_PAYPAL_TRIAL_SUBSCRIPTION ) {
				$is_free = false;
			} else {
				$is_free = true;
			}
		}

		// show gateway purchase button for every active gateway
		foreach ( $gateways as $gateway ) {
			$view = null;

			// Skip gateways that are not configured.
			if ( ! $gateway->is_configured() ) { continue; }
			if ( ! $membership->can_use_gateway( $gateway->id ) ) { continue; }

			$data['ms_relationship'] = $subscription;
			$data['gateway'] = $gateway;
			$data['step'] = MS_Controller_Frontend::STEP_PROCESS_PURCHASE;

			// Free membership, show only free gateway
			if ( $is_free ) {
				if ( MS_Gateway_Free::ID !== $gateway->id ) {
					continue;
				}
			} elseif ( MS_Gateway_Free::ID === $gateway->id ) {
				// Skip free gateway
				continue;
			}

			$view_class = get_class( $gateway ) . '_View_Button';
			$view = MS_Factory::create( $view_class );

			if ( MS_Gateway_Authorize::ID == $gateway->id ) {
				/**
				 *  set additional step for authorize.net (gateway specific form)
				 *  @todo change to use popup, instead of another step (like stripe)
				 */
				$data['step'] = 'gateway_form';
			}

			if ( ! empty( $view ) ) {
				$view = apply_filters(
					'ms_gateway_view_button',
					$view,
					$gateway->id
				);

				$view->data = apply_filters(
					'ms_gateway_view_button_data',
					$data,
					$gateway->id
				);

				$html = apply_filters(
					'ms_controller_gateway_purchase_button_' . $gateway->id,
					$view->to_html(),
					$subscription,
					$this
				);

				echo $html;
			}
		}

	}


		/**
	 * Show gateway purchase button in invoice
	 *
	 * Related action hooks:
	 * - ms_view_frontend_payment_purchase_button
	 * - ms_view_shortcode_invoice_purchase_button
	 *
	 * @since  1.0.2.7
	 */
	public function invoice_purchase_button( $subscription, $invoice ) {
		// Get only active gateways
		$gateways = MS_Model_Gateway::get_gateways( true );
		$data = array();

		$membership = $subscription->get_membership();
		$is_free = false;
		$is_trial = false;

		if ( $membership->is_free() ) {
			$is_free = true;
		} elseif ( 0 == $invoice->total ) {
			$is_free = true;
		} elseif ( $invoice->uses_trial ) {
			if ( defined( 'MS_PAYPAL_TRIAL_SUBSCRIPTION' ) && MS_PAYPAL_TRIAL_SUBSCRIPTION ) {
				$is_free = false;
			} else {
				$is_free = true;
				$is_trial = true;
			}
		}

		// show gateway purchase button for every active gateway
		foreach ( $gateways as $gateway ) {
			$view = null;

			// Skip gateways that are not configured.
			if ( ! $gateway->is_configured() ) { continue; }
			if ( ! $membership->can_use_gateway( $gateway->id ) ) { continue; }

			$data['ms_relationship'] = $subscription;
			$data['gateway'] = $gateway;
			$data['step'] = MS_Controller_Frontend::STEP_PROCESS_PURCHASE;

			// Free membership, show only free gateway
			if ( $is_free && ! $is_trial ) {
				if ( MS_Gateway_Free::ID !== $gateway->id ) {
					continue;
				}
			} elseif ( MS_Gateway_Free::ID === $gateway->id ) {
				// Skip free gateway
				continue;
			}

			$view_class = get_class( $gateway ) . '_View_Button';
			$view = MS_Factory::create( $view_class );

			if ( MS_Gateway_Authorize::ID == $gateway->id ) {
				/**
				 *  set additional step for authorize.net (gateway specific form)
				 *  @todo change to use popup, instead of another step (like stripe)
				 */
				$data['step'] = 'gateway_form';
			}

			if ( ! empty( $view ) ) {
				$view = apply_filters(
					'ms_gateway_view_button',
					$view,
					$gateway->id
				);

				$view->data = apply_filters(
					'ms_gateway_view_button_data',
					$data,
					$gateway->id
				);

				$html = apply_filters(
					'ms_controller_gateway_purchase_button_'. $gateway->id,
					$view->to_html(),
					$subscription,
					$this
				);

				echo $html;
			}
		}

	}

	/**
	 * Show gateway purchase button.
	 *
	 * Related action hooks:
	 * - ms_view_shortcode_membershipsignup_cancel_button
	 *
	 * @since  1.0.0
	 */
	public function cancel_button( $button, $subscription ) {
		$view = null;
		$data = array();
		$data['ms_relationship'] = $subscription;
		$new_button = null;

		switch ( $subscription->gateway_id ) {
			case MS_Gateway_Paypalstandard::ID:
				$view = MS_Factory::create( 'MS_Gateway_Paypalstandard_View_Cancel' );
				$data['gateway'] = $subscription->get_gateway();
				break;

			case MS_Gateway_Authorize::ID:
			case MS_Gateway_Paypalsingle::ID:
			case MS_Gateway_Stripe::ID:
			case MS_Gateway_Free::ID:
			case MS_Gateway_Manual::ID:
			default:
				break;
		}
		$view = apply_filters( 'ms_gateway_view_cancel_button', $view );

		if ( $view && is_a( $view, 'MS_View' ) ) {
			$view->data = apply_filters(
				'ms_gateway_view_cancel_button_data',
				$data
			);

			$new_button = $view->get_button();
		}

		if ( ! $new_button ) {
			$new_button = $button;
		}

		return apply_filters(
			'ms_controller_gateway_cancel_button',
			$new_button,
			$subscription,
			$this
		);
	}

	/**
	 * Set hook to handle gateway extra form to commit payments.
	 *
	 * Related action hooks:
	 * - ms_controller_frontend_signup_gateway_form
	 *
	 * @since  1.0.0
	 */
	public function gateway_form_mgr() {
		// Display gateway form
		$this->add_filter( 'the_content', 'gateway_form', 10 );

		// Enqueue styles and scripts used
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
	}

	/**
	 * Handles gateway extra form to commit payments.
	 *
	 * Related filter hooks:
	 * - the_content
	 *
	 * @since  1.0.0
	 *
	 * @param  string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function gateway_form( $content ) {
		$data = array();
		$html = '';

		// Do not parse the form when building the excerpt.
		global $wp_current_filter;
		if ( in_array( 'get_the_excerpt', $wp_current_filter ) ) {
			return '';
		}

		$fields = array( 'gateway', 'ms_relationship_id' );
		if ( self::validate_required( $fields )
			&& MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] )
		) {
			$data['gateway'] = $_POST['gateway'];
			$data['ms_relationship_id'] = $_POST['ms_relationship_id'];
			$view = null;

			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$_POST['ms_relationship_id']
			);

			switch ( $_POST['gateway'] ) {
				case MS_Gateway_Authorize::ID:
					$member = $subscription->get_member();
					$view = MS_Factory::create( 'MS_Gateway_Authorize_View_Form' );
					$gateway = MS_Model_Gateway::factory( MS_Gateway_Authorize::ID );
					$data['countries'] = $gateway->get_country_codes();

					$data['action'] = $this->get_action();

					if ( 'update_card' == $this->get_action() ) {
						// Only new card option available on update card action.
						$data['cim_profiles'] = array();
					} else {
						// show existing credit card.
						$data['cim_profiles'] = $gateway->get_cim_profile( $member );
					}

					lib3()->array->strip_slashes( $_POST, 'auth_error' );

					$data['cim_payment_profile_id'] = $gateway->get_cim_payment_profile_id( $member );
					$data['auth_error'] = ! empty( $_POST['auth_error'] ) ? $_POST['auth_error'] : '';
					break;

				default:
					break;
			}

			$view = apply_filters(
				'ms_gateway_view_form',
				$view,
				$_POST['gateway'],
				$subscription
			);

			if ( $view && is_a( $view, 'MS_View' ) ) {
				$view->data = apply_filters(
					'ms_gateway_view_form_data',
					$data
				);

				$html = $view->to_html();
			}

			return apply_filters(
				'ms_controller_gateway_form',
				$html,
				$this
			);
		}
	}

	/**
	 * Process purchase using gateway.
	 *
	 * Related Action Hooks:
	 * - ms_controller_frontend_signup_process_purchase
	 *
	 * @since  1.0.0
	 */
	public function process_purchase() {
		$fields = array( 'gateway', 'ms_relationship_id' );

		lib3()->array->equip_request( 'gateway', 'ms_relationship_id' );

		$valid = true;
		$nonce_name = $_REQUEST['gateway'] . '_' . $_REQUEST['ms_relationship_id'];

		if ( ! self::validate_required( $fields, 'any' ) ) {
			$valid = false;
			$err = 'GAT-01 (invalid fields)';
		} elseif ( ! MS_Model_Gateway::is_valid_gateway( $_REQUEST['gateway'] ) ) {
			$valid = false;
			$err = 'GAT-02 (invalid gateway)';
		} elseif ( ! $this->verify_nonce( $nonce_name, 'any' ) ) {
			$valid = false;
			$err = 'GAT-03 (invalid nonce)';
		}

		if ( $valid ) {
			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$_REQUEST['ms_relationship_id']
			);

			$gateway_id = $_REQUEST['gateway'];
			$gateway = MS_Model_Gateway::factory( $gateway_id );

			try {
				$invoice = $gateway->process_purchase( $subscription );

				// If invoice is successfully paid, redirect to welcome page.
				if ( $invoice->is_paid()
					|| ( $invoice->uses_trial
						&& MS_Model_Invoice::STATUS_BILLED == $invoice->status
					)
				) {
					// Make sure to respect the single-membership rule
					$this->validate_membership_states( $subscription );

					// Redirect user to the Payment-Completed page.
					if ( ! defined( 'IS_UNIT_TEST' ) ) {
						MS_Model_Pages::redirect_to(
							MS_Model_Pages::MS_PAGE_REG_COMPLETE,
							array( 'ms_relationship_id' => $subscription->id )
						);
					}
				} elseif ( MS_Gateway_Manual::ID == $gateway_id ) {
					// For manual gateway payments.
					$this->add_action( 'the_content', 'purchase_info_content' );
				} else {
					// Something went wrong, the payment was not successful.
					$this->add_action( 'the_content', 'purchase_error_content' );
				}
			} catch ( Exception $e ) {
				MS_Helper_Debug::debug_log( $e->getMessage() );

				switch ( $gateway_id ) {
					case MS_Gateway_Authorize::ID:
						$_POST['auth_error'] = $e->getMessage();
						// call action to step back
						do_action( 'ms_controller_frontend_signup_gateway_form' );
						break;

					case MS_Gateway_Stripe::ID:
					case MS_Gateway_Stripeplan::ID:
						$_POST['error'] = sprintf(
							__( 'Error: %s', 'membership2' ),
							$e->getMessage()
						);

						// Hack to send the error message back to the payment_table.
						MS_Plugin::instance()->controller->controllers['frontend']->add_action(
							'the_content',
							'payment_table', 1
						);
						break;

					default:
						do_action( 'ms_controller_gateway_form_error', $e );
						$this->add_action( 'the_content', 'purchase_error_content' );
						break;
				}
			}
		} else {
			MS_Helper_Debug::debug_log( 'Error Code ' . $err );

			$this->add_action( 'the_content', 'purchase_error_content' );
		}

		// Hack to show signup page in case of errors
		$ms_page = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_REGISTER );

		if ( $ms_page ) {
			// During unit-testing the $ms_page object might be empty.
			global $wp_query;
			$wp_query->query_vars['page_id'] = $ms_page->ID;
			$wp_query->query_vars['post_type'] = 'page';
		}

		do_action(
			'ms_controller_gateway_process_purchase_after',
			$this
		);
	}

	/**
	 * Make sure that we respect the Single-Membership rule.
	 * This rule is active when the "Multiple-Memberships" Add-on is DISABLED.
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Relationship $new_relationship
	 */
	protected function validate_membership_states( $new_relationship ) {
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
			// Multiple memberships allowed. No need to check anything.
			return;
		}

		$cancel_these = array(
			MS_Model_Relationship::STATUS_TRIAL,
			MS_Model_Relationship::STATUS_ACTIVE,
			MS_Model_Relationship::STATUS_PENDING,
		);

		$member = $new_relationship->get_member();
		foreach ( $member->subscriptions as $subscription ) {
			if ( $subscription->id === $new_relationship->id ) { continue; }
			if ( in_array( $subscription->status, $cancel_these ) ) {
				$subscription->cancel_membership();
			}
		}
	}

	/**
	 * Show signup page with custom content.
	 *
	 * This is used by manual gateway (overridden) to show payment info.
	 *
	 * Related action hooks:
	 *
	 * @since  1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function purchase_info_content( $content ) {
		return apply_filters(
			'ms_controller_gateway_purchase_info_content',
			$content,
			$this
		);
	}

	/**
	 * Show error message in the signup page.
	 *
	 * Related action hooks:
	 *
	 * @since  1.0.0
	 */
	public function purchase_error_content( $content ) {
		return apply_filters(
			'ms_controller_gateway_purchase_error_content',
			__( 'Sorry, your signup request has failed. Try again.', 'membership2' ),
			$content,
			$this
		);
	}


	/**
	 * Handle payment gateway return IPNs.
	 *
	 * Used by Paypal gateways.
	 * A redirection rule is set up in the main MS_Plugin object
	 * (protected_content.php):
	 * /ms-payment-return/XYZ becomes index.php?paymentgateway=XYZ
	 *
	 * Related action hooks:
	 * - pre_get_posts
	 *
	 * @todo Review how this works when we use OAuth API's with gateways.
	 *
	 * @since  1.0.0
	 *
	 * @param WP_Query $wp_query The WordPress query object
	 */
	public function handle_payment_return( $wp_query ) {
		// Do not check custom loops.
		if ( ! $wp_query->is_main_query() ) { return; }

		if ( ! empty( $wp_query->query_vars['paymentgateway'] ) ) {
			$gateway = $wp_query->query_vars['paymentgateway'];

			/**
			 * In 1.1.0 the underscore in payment gateway names was removed.
			 * To compensate for this we need to continue listen to these old
			 * gateway-names.
			 */
			switch ( $gateway ) {
				case 'paypal_single': $gateway = 'paypalsingle'; break;
				case 'paypal_standard': $gateway = 'paypalstandard'; break;
				case 'paypal-single': $gateway = 'paypalsingle'; break;
				case 'paypal-standard': $gateway = 'paypalstandard'; break;
				case 'paypalsolo': $gateway = 'paypalsingle'; break; // M1
				case 'paypalexpress': $gateway = 'paypalstandard'; break; //M1
			}

			if ( MS_Model_Gateway::is_active( $gateway ) ) {
				$action = 'ms_gateway_handle_payment_return_' . $gateway;
				do_action( $action );
			} else {
				// Log the payment attempt when the gateway is not active.
				if ( MS_Model_Gateway::is_valid_gateway( $gateway ) ) {
					$note = __( 'Gateway is inactive', 'membership2' );
				} else {
					$note = sprintf(
						__( 'Unknown Gateway: %s', 'membership2' ),
						$gateway
					);
				}

				do_action(
					'ms_gateway_transaction_log',
					$gateway, // gateway ID
					'handle', // request|process|handle
					false, // success flag
					0, // subscription ID
					0, // invoice ID
					0, // charged amount
					$note, // Descriptive text
					'' // External ID
				);
			}
		}
	}

	/**
	 * Show gateway credit card information.
	 *
	 * If a card is used, show it in account's page.
	 *
	 * Related action hooks:
	 * - ms_view_shortcode_account_card_info
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $data The data passed to hooked view.
	 */
	public function card_info( $data = null ) {
		if ( ! empty( $data['gateway'] ) && is_array( $data['gateway'] ) ) {
			$gateways = array();

			foreach ( $data['gateway'] as $ms_relationship_id => $gateway ) {
				// avoid duplicates
				if ( ! in_array( $gateway->id, $gateways ) ) {
					$gateways[] = $gateway->id;
				} else {
					continue;
				}
				$view = null;

				switch ( $gateway->id ) {
					case MS_Gateway_Stripe::ID:
						$member = MS_Model_Member::get_current_member();
						$data['stripe'] = $member->get_gateway_profile(
							$gateway->id
						);

						if ( empty( $data['stripe']['card_exp'] ) ) {
							continue 2;
						}

						$view = MS_Factory::create( 'MS_Gateway_Stripe_View_Card' );
						$data['member'] = $member;
						$data['publishable_key'] = $gateway->get_publishable_key();
						$data['ms_relationship_id'] = $ms_relationship_id;
						$data['gateway'] = $gateway;
						break;

					case MS_Gateway_Authorize::ID:
						$member = MS_Model_Member::get_current_member();
						$data['authorize'] = $member->get_gateway_profile(
							$gateway->id
						);

						if ( empty( $data['authorize']['card_exp'] ) ) {
							continue 2;
						}

						$view = MS_Factory::create( 'MS_Gateway_Authorize_View_Card' );
						$data['member'] = $member;
						$data['ms_relationship_id'] = $ms_relationship_id;
						$data['gateway'] = $gateway;
						break;

					default:
						break;
				}

				if ( ! empty( $view ) ) {
					$view = apply_filters(
						'ms_gateway_view_change_card',
						$view,
						$gateway->id
					);
					$view->data = apply_filters(
						'ms_gateway_view_change_card_data',
						$data,
						$gateway->id
					);

					$html = $view->to_html();
					echo '' . $html;
				}
			}
		}
	}

	/**
	 * Handle update credit card information in gateway.
	 *
	 * Used to change credit card info in account's page.
	 *
	 * Related action hooks:
	 * - template_redirect
	 *
	 * @since  1.0.0
	 */
	public function update_card() {
		if ( ! empty( $_POST['gateway'] ) ) {
			$gateway = MS_Model_Gateway::factory( $_POST['gateway'] );
			$member = MS_Model_Member::get_current_member();

			switch ( $gateway->id ) {
				case MS_Gateway_Stripe::ID:
					if ( ! empty( $_POST['stripeToken'] ) && $this->verify_nonce() ) {
						lib3()->array->strip_slashes( $_POST, 'stripeToken' );

						$gateway->add_card( $member, $_POST['stripeToken'] );
						if ( ! empty( $_POST['ms_relationship_id'] ) ) {
							$ms_relationship = MS_Factory::load(
								'MS_Model_Relationship',
								$_POST['ms_relationship_id']
							);
							MS_Model_Event::save_event(
								MS_Model_Event::TYPE_UPDATED_INFO,
								$ms_relationship
							);
						}

						wp_safe_redirect(
							esc_url_raw( add_query_arg( array( 'msg' => 1 ) ) )
						);
						exit;
					}
					break;

				case MS_Gateway_Authorize::ID:
					if ( $this->verify_nonce() ) {
						do_action(
							'ms_controller_frontend_signup_gateway_form',
							$this
						);
					} elseif ( ! empty( $_POST['ms_relationship_id'] )
						&& $this->verify_nonce( $_POST['gateway'] .'_' . $_POST['ms_relationship_id'] )
					) {
						$gateway->update_cim_profile( $member );
						$gateway->save_card_info( $member );
						if ( ! empty( $_POST['ms_relationship_id'] ) ) {
							$ms_relationship = MS_Factory::load(
								'MS_Model_Relationship',
								$_POST['ms_relationship_id']
							);
							MS_Model_Event::save_event(
								MS_Model_Event::TYPE_UPDATED_INFO,
								$ms_relationship
							);
						}

						wp_safe_redirect(
							esc_url_raw( add_query_arg( array( 'msg' => 1 ) ) )
						);
						exit;
					}
					break;

				default:
					break;
			}
		}

		do_action(
			'ms_controller_gateway_update_card',
			$this
		);
	}

	/**
	 * Saves transaction details to the database. The transaction logs can later
	 * be displayed in the Billings section.
	 *
	 * @since  1.0.0
	 * @internal Action handler for 'ms_gateway_transaction_log'
	 *
	 *
	 * @param string $gateway_id The gateway ID.
	 * @param string $method Following values:
	 *        "handle": IPN response
	 *        "process": Process order (i.e. user comes from Payment screen)
	 *        "request": Automatically request recurring payment
	 * @param bool $success True means that the transaction was paid/successful.
	 *        False indicates an error.
	 *        NULL indicates a message that was intentionally skipped.
	 * @param int $subscription_id
	 * @param int $invoice_id
	 * @param float $amount Payment amount.
	 * @param string $notes Additional text to describe the transaction or error.
	 * @param string $external_id The gateways transaction ID.
	 */
	public function log_transaction( $gateway_id, $method, $success, $subscription_id, $invoice_id, $amount, $notes, $external_id ) {
		$log = MS_Factory::create( 'MS_Model_Transactionlog' );
		$log->description = $notes;
		$log->gateway_id = $gateway_id;
		$log->method = $method;
		$log->success = $success;
		$log->subscription_id = $subscription_id;
		$log->invoice_id = $invoice_id;
		$log->amount = $amount;
		$log->external_id = $external_id;
		$log->save();
	}

	/**
	 * Adds CSS and javascript
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts( $step = null ) {
		if ( empty( $step ) && ! empty( $_POST['step'] ) ) {
			$step = $_POST['step'];
		}

		$gateway_id = isset( $_POST['gateway'] ) ? $_POST['gateway'] : 0 ;

		switch ( $step ) {
			case MS_Controller_Frontend::STEP_GATEWAY_FORM:
			case MS_Controller_Frontend::STEP_PROCESS_PURCHASE:
				if ( MS_Gateway_Authorize::ID == $gateway_id ) {
					wp_enqueue_script( 'jquery-validate' );

					$data = array(
						'ms_init' => array( 'gateway_authorize' ),
					);

					lib3()->ui->add( 'core' );
					lib3()->ui->add( 'select' );
					lib3()->ui->data( 'ms_data', $data );
					wp_enqueue_script( 'ms-public' );
				}
				break;
		}
	}
}
