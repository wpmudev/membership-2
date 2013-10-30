<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Authorize.Net gateway class.
 *
 * @since 3.5
 *
 * @category Membership
 * @package Gateway
 */
class Membership_Gateway_Authorize extends Membership_Gateway {

	const MODE_SANDBOX = 'sandbox';
	const MODE_LIVE    = 'live';

	const TRANSACTION_TYPE_AUTHORIZED        = 1;
	const TRANSACTION_TYPE_CAPTURED          = 2;
	const TRANSACTION_TYPE_RECURRING         = 3;
	const TRANSACTION_TYPE_VOIDED            = 4;
	const TRANSACTION_TYPE_CANCELED_RECURING = 5;
	const TRANSACTION_TYPE_CIM_AUTHORIZED    = 6;

	/**
	 * Gateway id.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @var string
	 */
	public $gateway = 'authorize';

	/**
	 * Gateway title.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @var string
	 */
	public $title = 'Authorize.Net';

	/**
	 * Determines whether gateway has payment form or not.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @var boolean
	 */
	public $haspaymentform = true;

	/**
	 * Array of payment result.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var array
	 */
	protected $_payment_result;

	/**
	 * Current member.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var M_Membership
	 */
	protected $_member;

	/**
	 * Current subscription.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var M_Subscription
	 */
	protected $_subscription;

	/**
	 * The array of transaction processed during payment.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var array
	 */
	protected $_transaction;

	/**
	 * User's Authorize.net CIM profile ID.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var int
	 */
	protected $_cim_profile_id;

	/**
	 * User's Authorize.net CIM payment profile ID.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var int
	 */
	protected $_cim_payment_profile_id;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->_add_action( 'M_gateways_settings_' . $this->gateway, 'render_settings' );
		$this->_add_action( 'membership_purchase_button', 'render_subscribe_button', 10, 3 );
		$this->_add_action( 'membership_payment_form_' . $this->gateway, 'render_payment_form', 10, 3 );
		$this->_add_action( 'membership_expire_subscription', 'cancel_subscription_transactions', 10, 2 );
		$this->_add_action( 'membership_move_subscription', 'capture_next_transaction', 10, 6 );
		$this->_add_filter( 'membership_unsubscribe_subscription', 'process_unsubscribe_subscription', 10, 3 );

		$this->_add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
		$this->_add_action( 'wp_login', 'propagate_ssl_cookie', 10, 2 );

		$this->_add_action( 'wpmu_delete_user', 'save_cim_profile_id' );
		$this->_add_action( 'delete_user', 'save_cim_profile_id' );
		$this->_add_action( 'deleted_user', 'delete_cim_profile' );

		$this->_add_ajax_action( 'processpurchase_' . $this->gateway, 'process_purchase', true, true );
		$this->_add_ajax_action( 'purchaseform', 'render_popover_payment_form' );
	}

	/**
	 * Saves Authorize.net CIM profile ID before delete an user.
	 *
	 * @since 3.5
	 * @action delete_user
	 *
	 * @access public
	 * @param int $user_id User's ID which will be deleted.
	 */
	public function save_cim_profile_id( $user_id ) {
		$this->_cim_profile_id = get_user_meta( $user_id, 'authorize_cim_id', true );
	}

	/**
	 * Voids all authorized payements, delete subscriptions and removes
	 * Authorize.net CIM profile when an user is deleted. And finally deletes
	 * transaction log.
	 *
	 * @since 3.5
	 * @action deleted_user
	 *
	 * @access public
	 * @param int $user_id The ID of an user which was deleted.
	 */
	public function delete_cim_profile( $user_id ) {
		$this->cancel_subscription_transactions( false, $user_id );
		$this->db->delete( MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION, array( 'transaction_user_ID' => $user_id ), array( '%d' ) );

		if ( $this->_cim_profile_id ) {
			$this->_get_cim()->deleteCustomerProfile( $this->_cim_profile_id );
		}
	}

	/**
	 * Voids authorized only payments and cancels active recuring subscriptions
	 * for specific or all subscriptions.
	 *
	 * @since 3.5
	 * @action membership_expire_subscription 10 2
	 *
	 * @access public
	 * @param int $sub_id The subscription ID.
	 * @param int $user_id The user ID.
	 */
	public function cancel_subscription_transactions( $sub_id, $user_id ) {
		$transactions = $this->db->get_results( sprintf(
			'SELECT transaction_ID AS record_id, transaction_paypal_ID AS id, transaction_status AS status FROM %s WHERE transaction_user_ID = %d AND transaction_status IN (%d, %d, %d)%s',
			MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
			$user_id,
			self::TRANSACTION_TYPE_AUTHORIZED,
			self::TRANSACTION_TYPE_RECURRING,
			self::TRANSACTION_TYPE_CIM_AUTHORIZED,
			!empty( $sub_id ) ? ' AND transaction_subscription_ID = ' . $sub_id : ''
		) );

		foreach ( $transactions as $transaction ) {
			$status = false;
			if ( $transaction->status == self::TRANSACTION_TYPE_AUTHORIZED ) {
				$this->_get_aim( false, false )->void( $transaction->id );
				$status = self::TRANSACTION_TYPE_VOIDED;
			} elseif ( $transaction->status == self::TRANSACTION_TYPE_RECURRING ) {
				$this->_get_arb()->cancelSubscription( $transaction->id );
				$status = self::TRANSACTION_TYPE_CANCELED_RECURING;
			} elseif ( $transaction->status == self::TRANSACTION_TYPE_CIM_AUTHORIZED ) {
				if ( !$this->_cim_profile_id ) {
					$this->_cim_profile_id = get_user_meta( $user_id, 'authorize_cim_id', true );
				}

				$cim_transaction = $this->_get_cim_transaction();
				$cim_transaction->transId = $transaction->id;
				$this->_get_cim()->createCustomerProfileTransaction( 'Void', $cim_transaction );
				$status = self::TRANSACTION_TYPE_VOIDED;
			}

			if ( $status && $sub_id ) {
				$this->db->update(
					MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
					array( 'transaction_status' => $status ),
					array( 'transaction_ID'     => $transaction->record_id ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Captures next transaction accordingly to subscription settings.
	 *
	 * @since 3.5
	 * @action membership_move_subscription 10 6
	 *
	 * @access public
	 */
	public function capture_next_transaction( $fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $user_id ) {
		// don't do anything if subscription has been changed
		if ( $fromsub_id != $tosub_id ) {
			return;
		}

		// fetch next authorized transaction
		$transactions = $this->db->get_results( sprintf( '
			SELECT transaction_ID AS record_id, transaction_paypal_ID AS id, transaction_status AS status, transaction_total_amount/100 AS amount, transaction_stamp AS stamp
			  FROM %s
			 WHERE transaction_user_ID = %d
			   AND transaction_subscription_ID = %d
			   AND transaction_status IN (%d, %d)
			 ORDER BY transaction_ID ASC
			 LIMIT 1',
			MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
			$user_id,
			$tosub_id,
			self::TRANSACTION_TYPE_AUTHORIZED,
			self::TRANSACTION_TYPE_CIM_AUTHORIZED
		) );

		foreach ( $transactions as $transaction ) {
			// don't capture future transactions
			if ( $transaction->stamp > time() ) {
				continue;
			}

			// capture transaction
			$status = false;
			if ( $transaction->status == self::TRANSACTION_TYPE_AUTHORIZED ) {
				$this->_get_aim( false, false )->priorAuthCapture( $transaction->id, $transaction->amount );
				$status = self::TRANSACTION_TYPE_CAPTURED;
			} elseif ( $transaction->status == self::TRANSACTION_TYPE_CIM_AUTHORIZED ) {
				if ( !$this->_cim_profile_id ) {
					$this->_cim_profile_id = get_user_meta( $user_id, 'authorize_cim_id', true );
				}

				$cim_transaction = $this->_get_cim_transaction();
				$cim_transaction->transId = $transaction->id;
				$cim_transaction->amount = $transaction->amount;
				$this->_get_cim()->createCustomerProfileTransaction( 'PriorAuthCapture', $cim_transaction );
				$status = self::TRANSACTION_TYPE_CAPTURED;
			}

			// update transaction status
			if ( $status && $tosub_id ) {
				$this->db->update(
					MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
					array( 'transaction_status' => $status ),
					array( 'transaction_ID'     => $transaction->record_id ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Cancels subscription transactions if the subscription has to be expired.
	 *
	 * @since 3.5
	 * @filter membership_unsubscribe_subscription 10 3
	 *
	 * @access public
	 * @param boolean $expire Determines whether to mark a subscription to expire or not.
	 * @param int $sub_id Current subscription to unsubscribe from.
	 * @param int $user_id The user ID.
	 * @return boolean Incoming value for $expire variable.
	 */
	public function process_unsubscribe_subscription( $expire, $sub_id, $user_id ) {
		if ( $expire ) {
			if ( get_current_user_id() == $user_id ) {
				$this->_member = new M_Membership( $user_id );
				if ( $this->_member->has_subscription() && $this->_member->on_sub( $sub_id ) ) {
					$this->cancel_subscription_transactions( $sub_id, $user_id );
				}
			}
		}

		return $expire;
	}

	/**
	 * Propagates SSL cookies when user logs in.
	 *
	 * @since 3.5
	 * @action wp_login 10 2
	 *
	 * @access public
	 * @param type $login
	 * @param WP_User $user
	 */
	public function propagate_ssl_cookie( $login, WP_User $user ) {
		if ( !is_ssl() ) {
			wp_set_auth_cookie( $user->ID, true, true );
		}
	}

	/**
	 * Renders gateway settings page.
	 *
	 * @since 3.5
	 * @action M_gateways_settings_authorize
	 *
	 * @access public
	 */
	public function render_settings() {
		$template = new Membership_Render_Gateway_Authorize_Settings();

		$template->api_user = $this->_get_option( 'api_user' );
		$template->api_key = $this->_get_option( 'api_key' );

		$template->mode = $this->_get_option( 'mode', self::MODE_SANDBOX );
		$template->modes = array(
			self::MODE_SANDBOX => __( 'Sandbox', 'membership' ),
			self::MODE_LIVE    => __( 'Live', 'membership' ),
		);

		$template->render();
	}

	/**
	 * Updates gateway options.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function update() {
		$method = defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && filter_var( MEMBERSHIP_GLOBAL_TABLES, FILTER_VALIDATE_BOOLEAN )
			? 'update_site_option'
			: 'update_option';

		$mode = filter_input( INPUT_POST, 'mode' );
		if ( in_array( $mode, array( self::MODE_LIVE, self::MODE_SANDBOX ) ) ) {
			$method( $this->gateway . "_mode", $mode );
		}

		foreach ( array( 'api_user', 'api_key' ) as $option ) {
			$key = "{$this->gateway}_{$option}";
			if ( isset( $_POST[$option] ) ) {
				$method( $key, filter_input( INPUT_POST, $option ) );
			}
		}
	}

	/**
	 * Renders payment button.
	 *
	 * @since 3.5
	 * @action membership_purchase_button 10 3
	 *
	 * @access public
	 * @global array $M_options The array of membership options.
	 * @param M_Subscription $subscription New subscription.
	 * @param array $pricing The pricing information.
	 * @param int $user_id The current user id.
	 */
	public function render_subscribe_button( M_Subscription $subscription, $pricing, $user_id ) {
		$this->_render_button( esc_attr__( 'Pay Now', 'membership' ), $subscription, $user_id, false );
	}

	/**
	 * Displays upgrade subscription button.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @global array $M_options The array of membership options.
	 * @param M_Subscription $subscription New subscription.
	 * @param array $pricing The pricing information.
	 * @param int $user_id The current user id.
	 * @param type $fromsub_id From subscription ID.
	 */
	public function display_upgrade_button( $subscription, $pricing, $user_id, $fromsub_id = false ) {
		$this->_render_button( esc_attr__( 'Upgrade', 'membership' ), $subscription, $user_id, $fromsub_id );
	}

	/**
	 * Displays unsubscribe button.
	 *
	 * @access public
	 * @param type $subscription
	 * @param type $pricing
	 * @param type $user_id
	 */
	public function display_cancel_button( $subscription, $pricing, $user_id ) {
		?><form class="unsubbutton" method="post">
			<?php wp_nonce_field( 'cancel-sub_' . $subscription->sub_id() ) ?>
			<input type="hidden" name="action" value="unsubscribe">
			<input type="hidden" name="gateway" value="<?php echo esc_attr( $this->gateway ) ?>">
			<input type="hidden" name="subscription" value="<? echo esc_attr( $subscription->sub_id() ) ?>">
			<input type="hidden" name="user" value="<?php echo esc_attr( $user_id ) ?>">
			<input type="submit" value="<?php esc_attr_e( 'Unsubscribe', 'membership' ) ?>" class="button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>">
		</form><?php
	}

	/**
	 * Renders gateway button.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @global array $M_options The array of membership options.
	 * @param string $label The button label.
	 * @param M_Subscription $subscription New subscription.
	 * @param int $user_id The current user id.
	 * @param type $fromsub_id From subscription ID.
	 */
	protected function _render_button( $label, M_Subscription $subscription, $user_id, $fromsub_id = false ) {
		global $M_options;

		$actionurl = isset( $M_options['registration_page'] ) ? str_replace('http:', 'https:', get_permalink( $M_options['registration_page'] ) ) : '';
		if ( empty( $actionurl ) ) {
			$actionurl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		$template = new Membership_Render_Gateway_Authorize_Button();

		$template->gateway = $this->gateway;
		$template->subscription_id = $subscription->id;
		$template->from_subscription_id = (int)$fromsub_id;
		$template->user_id = $user_id;
		$template->button_label = $label;

		$actionurl = add_query_arg( array( 'action' => 'registeruser', 'subscription' => $subscription->id ), $actionurl );
		$template->actionurl = $actionurl;

		$coupon = membership_get_current_coupon();
		$template->coupon_code = !empty( $coupon ) ? $coupon->get_coupon_code() : '';

		$template->render();
	}

	/**
	 * Renders payment form.
	 *
	 * @since 3.5
	 * @action membership_payment_form_authorize
	 *
	 * @access public
	 * @param M_Subscription $subscription The current subscription to subscribe to.
	 * @param array $pricing The pricing information.
	 * @param int $user_id The current user id.
	 */
	public function render_payment_form( M_Subscription $subscription, $pricing, $user_id ) {
		// check errors
		$error = false;
		if ( isset( $_GET['errors'] ) ) {
			if ( $_GET['errors'] == 1 ) {
				$error = __( 'Payment method not supported for the payment', 'membership' );
			} elseif ( $_GET['errors'] == 2 ) {
				$error = __( 'There was a problem processing your purchase. Please, try again.', 'membership' );
			}
		}

		// check API user login and transaction key
		$api_u = trim( $this->_get_option( 'api_user' ) );
		$api_k = trim( $this->_get_option( 'api_key' ) );
		if ( empty( $api_u ) || empty( $api_k ) ) {
			$error = __( 'This payment gateway has not been configured. Your transaction will not be processed.', 'membership' );
		}

		// fetch CIM profile
		$cim_profiles = array();
		// CIM can't handle recurring billing
		if ( !in_array( 'serial', wp_list_pluck( $pricing, 'type' ) ) ) {
			$cim_profile_id = get_user_meta( $user_id, 'authorize_cim_id', true );
			if ( $cim_profile_id ) {
				$response = $this->_get_cim()->getCustomerProfile( $cim_profile_id );
				if ( $response->isOk() ) {
					$cim_profiles = json_decode( json_encode( $response->xml->profile ), true );
					$cim_profiles = $cim_profiles['paymentProfiles'];
				}
			}
		}

		// fetch coupon information
		$coupon = membership_get_current_coupon();
		$coupon = !empty( $coupon ) ? $coupon->get_coupon_code() : '';

		// initialize and render form template
		$template = new Membership_Render_Gateway_Authorize_Form();

		$template->error = $error;
		$template->coupon = $coupon;
		$template->subscription_id = $subscription->id;
		$template->gateway = $this->gateway;
		$template->user_id = $user_id;
		$template->cim_profiles = $cim_profiles;
		$template->from_subscription = filter_input( INPUT_POST, 'from_subscription', FILTER_VALIDATE_INT );

		$template->render();
	}

	/**
	 * Renders popover payment form.
	 *
	 * @since 3.5
	 * @action wp_ajax_purchaseform
	 *
	 * @access public
	 * @global WP_Scripts $wp_scripts
	 */
	public function render_popover_payment_form() {
		if ( filter_input( INPUT_POST, 'gateway' ) != $this->gateway ) {
			return;
		}

		$subscription = new M_Subscription( filter_input( INPUT_POST, 'subscription', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) ) );
		$user_id = filter_input( INPUT_POST, 'user', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'default' => get_current_user_id() ) ) );
		do_action( 'membership_payment_form_' . $this->gateway, $subscription, null, $user_id );
	}

	/**
	 * Processes purchase action.
	 *
	 * @since 3.5
	 * @action wp_ajax_nopriv_processpurchase_authorize
	 * @action wp_ajax_processpurchase_authorize
	 *
	 * @access public
	 */
	public function process_purchase() {
		global $M_options;
		if ( empty( $M_options['paymentcurrency'] ) ) {
			$M_options['paymentcurrency'] = 'USD';
		}

		if ( !is_ssl() ) {
			wp_die( __( 'You must use HTTPS in order to do this', 'membership' ) );
			exit;
		}

		// fetch subscription and pricing
		$sub_id = filter_input( INPUT_POST, 'subscription_id', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		$this->_subscription = new M_Subscription( $sub_id );
		$pricing = $this->_subscription->get_pricingarray();
		if ( !$pricing ) {
			status_header( 404 );
			exit;
		}

		// apply a coupon
		$coupon = membership_get_current_coupon();
		if ( $coupon && $coupon->valid_for_subscription( $this->_subscription->id ) ) {
			$pricing = $coupon->apply_coupon_pricing( $pricing );
		}

		// fetch member
		$user_id = is_user_logged_in() ? get_current_user_id() : $_POST['user_id'];
		$this->_member = new M_Membership( $user_id );

		// fetch CIM user and payment profiles info
		// pay attention that CIM can't handle recurring transaction, so we need
		// to use standard ARB aproach and full cards details
		$has_serial = in_array( 'serial', wp_list_pluck( $pricing, 'type' ) );
		if ( !$has_serial ) {
			$this->_cim_payment_profile_id = trim( filter_input( INPUT_POST, 'profile' ) );
			if ( !empty( $this->_cim_payment_profile_id ) ) {
				$this->_cim_profile_id = get_user_meta( $this->_member->ID, 'authorize_cim_id', true );
				if ( $this->_cim_profile_id ) {
					$response = $this->_get_cim()->getCustomerPaymentProfile( $this->_cim_profile_id, $this->_cim_payment_profile_id );
					if ( $response->isError() ) {
						$this->_cim_payment_profile_id = false;
					}
				}
			}
		}

		// process payments
		$started = new DateTime();
		$this->_payment_result = array( 'status' => '', 'errors' => array() );
		$this->_transaction = array();
		for ( $i = 0, $count = count( $pricing ); $i < $count; $i++ ) {
			switch ( $pricing[$i]['type'] ) {
				case 'finite':
					$unit = false;
					switch ( $pricing[$i]['unit'] ) {
						case 'd': $unit = 'day';   break;
						case 'w': $unit = 'week';  break;
						case 'm': $unit = 'month'; break;
						case 'y': $unit = 'year';  break;
					}

					$this->_transaction[] = $this->_process_nonserial_purchase( $pricing[$i], $started );
					$started->modify( sprintf( '+%d %s', $pricing[$i]['period'], $unit ) );
					break;
				case 'indefinite':
					$this->_transaction[] = $this->_process_nonserial_purchase( $pricing[$i], $started, $i );
					break 2;
				case 'serial':
					$this->_transaction[] = $this->_process_serial_purchase( $pricing[$i], $started, $i );
					break 2;
			}

			if ( $this->_payment_result['status'] == 'error' ) {
				$this->_rollback_transactions();
				break;
			}
		}

		if ( $this->_payment_result['status'] == 'success' ) {
			// create member subscription
			if ( $this->_member->has_subscription() ) {
				$from_sub_id = filter_input( INPUT_POST, 'from_subscription', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
				if ( $this->_member->on_sub( $from_sub_id ) ) {
					$this->_member->expire_subscription( $from_sub_id );
				}

				if ( $this->_member->on_sub( $sub_id ) ) {
					$this->_member->expire_subscription( $sub_id );
				}
			}
			$this->_member->create_subscription( $sub_id, $this->gateway );

			// create CIM profile it is not exists, otherwise update it if new card was added
			$this->_cim_profile_id = get_user_meta( $this->_member->ID, 'authorize_cim_id', true );
			if ( !$this->_cim_profile_id ) {
				$this->_create_cim_profile();
			} elseif ( !$has_serial && empty( $this->_cim_payment_profile_id ) ) {
				$this->_update_cim_profile();
			}

			// process transactions
			$this->_commit_transactions();

			// process response message and redirect
			$popup = isset( $M_options['formtype'] ) && $M_options['formtype'] == 'new';
			if ( $popup && !empty( $M_options['registrationcompleted_message'] ) ) {
				$html = '<div class="header" style="width: 750px"><h1>';
				$html .= sprintf( __( 'Sign up for %s completed', 'membership' ), $this->_subscription->sub_name() );
				$html .= '</h1></div><div class="fullwidth">';
				$html .= wpautop( $M_options['registrationcompleted_message'] );
				$html .= '</div>';

				$this->_payment_result['redirect'] = 'no';
				$this->_payment_result['message'] = $html;
			} else {
				$this->_payment_result['message'] = '';
				$this->_payment_result['redirect'] = strpos( home_url(), 'https://' ) === 0
					? str_replace( 'https:', 'http:', M_get_registrationcompleted_permalink() )
					: M_get_registrationcompleted_permalink();
			}
		}

		echo json_encode( $this->_payment_result );
		exit;
	}

	/**
	 * Processes non serial level purchase.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @param array $price The array with current price information.
	 * @param DateTime $date The date when to process this transaction.
	 * @return array Returns transaction information on success, otherwise NULL.
	 */
	protected function _process_nonserial_purchase( $price, $date ) {
		if ( $price['amount'] == 0 ) {
			$this->_payment_result['status'] = 'success';
			return null;
		}

		$success = $transaction_id = $method = false;
		$amount = number_format( $price['amount'], 2, '.', '' );
		if ( !empty( $this->_cim_profile_id ) && !empty( $this->_cim_payment_profile_id ) ) {
			$transaction = $this->_get_cim_transaction();
			$transaction->amount = $amount;

			$response = $this->_get_cim()->createCustomerProfileTransaction( 'AuthOnly', $transaction );
			if ( $response->isOk() ) {
				$success = true;
				$method = 'cim';
				$transaction_id = $response->getTransactionResponse()->transaction_id;
			}
		} else {
			$response = $this->_get_aim()->authorizeOnly( $amount );
			if ( $response->approved ) {
				$success = true;
				$transaction_id = $response->transaction_id;
				$method = 'aim';
			}
		}

		if ( $success ) {
			$this->_payment_result['status'] = 'success';
			return array(
				'method'      => $method,
				'transaction' => $transaction_id,
				'date'        => $date->format( 'U' ),
				'amount'      => $amount,
			);
		}

		$this->_payment_result['status'] = 'error';
		$this->_payment_result['errors'][] = __( 'Your payment was declined. Please, check all your details or use a different card.', 'membership' );

		return null;
	}

	/**
	 * Processes serial level purchase.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @global array $M_options The array of plugin options.
	 * @param array $price The array with current price information.
	 * @param DateTime $date The date when to process this transaction.
	 * @return array Returns transaction information on success, otherwise NULL.
	 */
	protected function _process_serial_purchase( $price, $date ) {
		if ( $price['amount'] == 0 ) {
			$this->_payment_result['status'] = 'success';
			return null;
		}

		$amount = number_format( $price['amount'], 2, '.', '' );

		$level = new M_Level( $price['level_id'] );
		$name = substr( sprintf(
			$price['type'] == 'finite'
				? __( '%s / %s', 'membership' )
				: __( '%s / %s', 'membership' ),
			$level->level_title(),
			$this->_subscription->sub_name()
		), 0, 50 );

		$subscription = $this->_get_arb_subscription( $price );
		$subscription->name = $name;
		$subscription->amount = $amount;
		$subscription->startDate = $date->format( 'Y-m-d' );
		$subscription->totalOccurrences = 9999;

		if ( isset( $price['origin'] ) ) {
			// coupon is applied, so we need to add trial period
			$subscription->amount = $amount = number_format( $price['origin'], 2, '.', '' );
			$subscription->trialAmount = number_format( $price['amount'], 2, '.', '' );
			$subscription->trialOccurrences = 1;
		}

		$arb = $this->_get_arb();
		$response = $arb->createSubscription( $subscription );
		if ( $response->isOk() ) {
			$this->_payment_result['status'] = 'success';
			return array(
				'method'      => 'arb',
				'transaction' => $response->getSubscriptionId(),
				'date'        => $date->format( 'U' ),
				'amount'      => $amount,
			);
		}

		$this->_payment_result['status'] = 'error';
		$this->_payment_result['errors'][] = __( 'Your payment was declined. Please, check all your details or use a different card.', 'membership' );

		return null;
	}

	/**
	 * Processes transactions.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @global array $M_options The array of plugin options.
	 */
	protected function _commit_transactions() {
		global $M_options;

		$sub_id = $this->_subscription->sub_id();
		$notes = $this->_get_option( 'mode', self::MODE_SANDBOX ) != self::MODE_LIVE ? 'Sandbox' : '';

		// process each transaction information and save it to CIM
		foreach ( $this->_transaction as $index => $info ) {
			if ( is_null( $info ) ) {
				continue;
			}

			$status = 0;
			if ( $info['method'] == 'aim' ) {
				$status = self::TRANSACTION_TYPE_AUTHORIZED;

				// capture first transaction
				if ( $index == 0 ) {
					$this->_get_aim( true, false )->priorAuthCapture( $info['transaction'] );
					$status = self::TRANSACTION_TYPE_CAPTURED;
				}
			} elseif ( $info['method'] == 'cim' ) {
				$status = self::TRANSACTION_TYPE_CIM_AUTHORIZED;

				// capture first transaction
				if ( $index == 0 ) {
					$transaction = $this->_get_cim_transaction();
					$transaction->transId = $info['transaction'];
					$transaction->amount = $info['amount'];
					$this->_get_cim()->createCustomerProfileTransaction( 'PriorAuthCapture', $transaction );
					$status = self::TRANSACTION_TYPE_CAPTURED;
				}
			} elseif ( $info['method'] == 'arb' ) {
				$status = self::TRANSACTION_TYPE_RECURRING;
			}

			if ( $status ) {
				// save transaction information in the database
				$this->_record_transaction(
					$this->_member->ID,
					$sub_id,
					$info['amount'],
					$M_options['paymentcurrency'],
					$info['date'],
					$info['transaction'],
					$status,
					$notes
				);
			}
		}
	}

	/**
	 * Rollbacks transactions all transactions and subscriptions.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _rollback_transactions() {
		foreach ( $this->_transaction as $info ) {
			if ( $info['method'] == 'aim' ) {
				$this->_get_aim()->void( $info['transaction'] );
			} elseif ( $info['method'] == 'arb' ) {
				$this->_get_arb()->cancelSubscription( $info['transaction'] );
			}
		}
	}

	/**
	 * Creates Authorize.net CIM profile for current user.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return int Customer profile ID on success, otherwise FALSE.
	 */
	protected function _create_cim_profile() {
		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		$customer = new AuthorizeNetCustomer();
		$customer->merchantCustomerId = $this->_member->ID;
		$customer->email = $this->_member->user_email;
		$customer->paymentProfiles[] = $this->_create_cim_payment_profile();

		$response = $this->_get_cim()->createCustomerProfile( $customer );
		if ( $response->isError() ) {
			return false;
		}

		$profile_id = $response->getCustomerProfileId();
		update_user_meta( $this->_member->ID, 'authorize_cim_id', $profile_id );

		return $profile_id;
	}

	/**
	 * Updates CIM profile by adding a new credit card.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	protected function _update_cim_profile() {
		$payment = $this->_create_cim_payment_profile();
		$response = $this->_get_cim()->createCustomerPaymentProfile( $this->_cim_profile_id, $payment );
		if ( $response->isError() ) {
			return false;
		}

		return true;
	}

	/**
	 * Creates CIM payment profile and fills it with posted credit card data.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return AuthorizeNetPaymentProfile The instance of AuthorizeNetPaymentProfile class.
	 */
	protected function _create_cim_payment_profile() {
		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		$payment = new AuthorizeNetPaymentProfile();

		// billing information
		$payment->billTo->firstName = substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 );
		$payment->billTo->lastName = substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 );
		$payment->billTo->address = substr( trim( filter_input( INPUT_POST, 'address' ) ), 0, 60 );
		$payment->billTo->city = substr( trim( filter_input( INPUT_POST, 'city' ) ), 0, 40 );
		$payment->billTo->state = substr( trim( filter_input( INPUT_POST, 'state' ) ), 0, 40 );
		$payment->billTo->zip = substr( trim( filter_input( INPUT_POST, 'zip' ) ), 0, 20 );
		$payment->billTo->country = substr( trim( filter_input( INPUT_POST, 'country' ) ), 0, 60 );

		// card information
		$payment->payment->creditCard->cardNumber = preg_replace( '/\D/', '', filter_input( INPUT_POST, 'card_num' ) );
		$payment->payment->creditCard->cardCode = trim( filter_input( INPUT_POST, 'card_code' ) );
		$payment->payment->creditCard->expirationDate = sprintf( '%04d-%02d', filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT ), substr( filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ), -2 ) );

		return $payment;
	}

	/**
	 * Initializes and returns AuthorizeNetAIM object.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @staticvar AuthorizeNetAIM $aim The instance of AuthorizeNetAIM class.
	 * @param boolean $refresh Determines whether we need to refresh $aim object or not.
	 * @param boolean $pre_fill Determines whether we need to pre fill AIM object with posted data or not.
	 * @return AuthorizeNetAIM The instance of AuthorizeNetAIM class.
	 */
	protected function _get_aim( $refresh = false, $pre_fill = true ) {
		static $aim = null;

		if ( !$refresh && !is_null( $aim ) ) {
			return $aim;
		}

		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		// merchant information
		$login_id = $this->_get_option( 'api_user' );
		$transaction_key = $this->_get_option( 'api_key' );
		$mode = $this->_get_option( 'mode', self::MODE_SANDBOX );

		// create new AIM
		$aim = new AuthorizeNetAIM( $login_id, $transaction_key );
		$aim->setSandbox( $mode != self::MODE_LIVE );
		if ( defined( 'MEMBERSHIP_AUTHORIZE_LOGFILE' ) ) {
			$aim->setLogFile( MEMBERSHIP_AUTHORIZE_LOGFILE );
		}

		if ( $pre_fill ) {
			// card information
			$aim->card_num = preg_replace( '/\D/', '', filter_input( INPUT_POST, 'card_num' ) );
			$aim->card_code = trim( filter_input( INPUT_POST, 'card_code' ) );
			$aim->exp_date = sprintf( '%02d/%02d', filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ), substr( filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT ), -2 ) );
			$aim->duplicate_window = MINUTE_IN_SECONDS;

			// customer information
			$aim->cust_id = $this->_member->ID;
			$aim->customer_ip = self::_get_remote_ip();

			// billing information
			$aim->first_name = substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 );
			$aim->last_name = substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 );
			$aim->address = substr( trim( filter_input( INPUT_POST, 'address' ) ), 0, 60 );
			$aim->city = substr( trim( filter_input( INPUT_POST, 'city' ) ), 0, 40 );
			$aim->state = substr( trim( filter_input( INPUT_POST, 'state' ) ), 0, 40 );
			$aim->zip = substr( trim( filter_input( INPUT_POST, 'zip' ) ), 0, 20 );
			$aim->country = substr( trim( filter_input( INPUT_POST, 'country' ) ), 0, 60 );
		}

		return $aim;
	}

	/**
	 * Initializes and returns AuthorizeNetARB object.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @staticvar AuthorizeNetARB $arb The instance of AuthorizeNetARB class.
	 * @return AuthorizeNetARB The instance of AuthorizeNetARB class.
	 */
	protected function _get_arb() {
		static $arb = null;

		if ( !is_null( $arb ) ) {
			return $arb;
		}

		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		// merchant information
		$login_id = $this->_get_option( 'api_user' );
		$transaction_key = $this->_get_option( 'api_key' );
		$mode = $this->_get_option( 'mode', self::MODE_SANDBOX );

		$arb = new AuthorizeNetARB( $login_id, $transaction_key );
		$arb->setSandbox( $mode != self::MODE_LIVE );
		if ( defined( 'MEMBERSHIP_AUTHORIZE_LOGFILE' ) ) {
			$arb->setLogFile( MEMBERSHIP_AUTHORIZE_LOGFILE );
		}

		return $arb;
	}

	/**
	 * Initializes and returns AuthorizeNet_Subscription object.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return AuthorizeNet_Subscription The instance of AuthorizeNet_Subscription class.
	 */
	protected function _get_arb_subscription( $pricing ) {
		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		// create new subscription
		$subscription = new AuthorizeNet_Subscription();
		$subscription->customerId = $this->_member->ID;

		switch ( $pricing['unit'] ) {
			case 'd':
				$subscription->intervalLength = $pricing['period'];
				$subscription->intervalUnit = 'days';
				break;
			case 'w':
				$subscription->intervalLength = $pricing['period'] * 7;
				$subscription->intervalUnit = 'days';
				break;
			case 'm':
				$subscription->intervalLength = $pricing['period'];
				$subscription->intervalUnit = 'months';
				break;
			case 'y':
				$subscription->intervalLength = $pricing['period'] * 12;
				$subscription->intervalUnit = 'months';
				break;
		}

		// card information
		$subscription->creditCardCardNumber = preg_replace( '/\D/', '', filter_input( INPUT_POST, 'card_num' ) );
		$subscription->creditCardCardCode = trim( filter_input( INPUT_POST, 'card_code' ) );
		$subscription->creditCardExpirationDate = sprintf( '%04d-%02d', filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT ), filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ) );

		// billing information
		$subscription->billToFirstName = substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 );
		$subscription->billToLastName = substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 );
		$subscription->billToAddress = substr( trim( filter_input( INPUT_POST, 'address' ) ), 0, 60 );
		$subscription->billToCity = substr( trim( filter_input( INPUT_POST, 'city' ) ), 0, 40 );
		$subscription->billToState = substr( trim( filter_input( INPUT_POST, 'state' ) ), 0, 40 );
		$subscription->billToZip = substr( trim( filter_input( INPUT_POST, 'zip' ) ), 0, 20 );
		$subscription->billToCountry = substr( trim( filter_input( INPUT_POST, 'country' ) ), 0, 60 );

		return $subscription;
	}

	/**
	 * Returns the instance of AuthorizeNetCIM class.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @staticvar AuthorizeNetCIM $cim The instance of AuthorizeNetCIM class.
	 * @return AuthorizeNetCIM The instance of AuthorizeNetCIM class.
	 */
	protected function _get_cim() {
		static $cim = null;

		if ( !is_null( $cim ) ) {
			return $cim;
		}

		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		// merchant information
		$login_id = $this->_get_option( 'api_user' );
		$transaction_key = $this->_get_option( 'api_key' );
		$mode = $this->_get_option( 'mode', self::MODE_SANDBOX );

		$cim = new AuthorizeNetCIM( $login_id, $transaction_key );
		$cim->setSandbox( $mode != self::MODE_LIVE );
		if ( defined( 'MEMBERSHIP_AUTHORIZE_LOGFILE' ) ) {
			$cim->setLogFile( MEMBERSHIP_AUTHORIZE_LOGFILE );
		}

		return $cim;
	}

	/**
	 * Initializes and returns Authorize.net CIM transaction object.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return AuthorizeNetTransaction The instance of AuthorizeNetTransaction class.
	 */
	protected function _get_cim_transaction() {
		require_once MEMBERSHIP_ABSPATH . '/classes/Authorize.net/AuthorizeNet.php';

		$transaction = new AuthorizeNetTransaction();
		$transaction->customerProfileId = $this->_cim_profile_id;
		$transaction->customerPaymentProfileId = $this->_cim_payment_profile_id;

		return $transaction;
	}

	/**
	 * Returns gateway option.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @param string $name The option name.
	 * @param mixed $default The default value.
	 * @return mixed The option value if it exists, otherwise default value.
	 */
	protected function _get_option( $name, $default = false ) {
		$key = "{$this->gateway}_{$name}";
		return defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && filter_var( MEMBERSHIP_GLOBAL_TABLES, FILTER_VALIDATE_BOOLEAN )
			? get_site_option( $key, $default )
			: get_option( $key, $default );
	}

	/**
	 * Enqueues scripts.
	 *
	 * @since 3.5
	 * @action wp_enqueue_scripts
	 *
	 * @access public
	 */
	public function enqueue_scripts() {
		if ( membership_is_registration_page() || membership_is_subscription_page() ) {
			wp_enqueue_script( 'membership-authorize', MEMBERSHIP_ABSURL . 'membershipincludes/js/authorizenet.js', array( 'jquery' ), Membership_Plugin::VERSION, true );
			wp_localize_script( 'membership-authorize', 'membership_authorize', array(
				'return_url'        => add_query_arg( 'action', 'processpurchase_' . $this->gateway, admin_url( 'admin-ajax.php', 'https' ) ),
				'payment_error_msg' => __( 'There was an unknown error encountered with your payment. Please contact the site administrator.', 'membership' ),
				'stylesheet_url'    => MEMBERSHIP_ABSURL . 'membershipincludes/css/authorizenet.css',
			) );
		}
	}

	/**
	 * Renders gateway transactions.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function transactions() {
		// prepare table
		$table = new Membership_Table_Gateway_Transaction_Authorize( array(
			'gateway'       => $this->gateway,
			'subscriptions' => $this->db->get_results( 'SELECT * FROM ' . MEMBERSHIP_TABLE_SUBSCRIPTIONS, ARRAY_A ),
			'statuses'      => array(
				self::TRANSACTION_TYPE_AUTHORIZED        => esc_html__( 'Authorized (ARB)', 'membership' ),
				self::TRANSACTION_TYPE_CIM_AUTHORIZED    => esc_html__( 'Authorized (CIM)', 'membership' ),
				self::TRANSACTION_TYPE_CAPTURED          => esc_html__( 'Captured', 'membership' ),
				self::TRANSACTION_TYPE_VOIDED            => esc_html__( 'Voided', 'membership' ),
				self::TRANSACTION_TYPE_RECURRING         => esc_html__( 'Recurring', 'membership' ),
				self::TRANSACTION_TYPE_CANCELED_RECURING => esc_html__( 'Cancelled Recurring', 'membership' ),
			),
		) );
		$table->prepare_items();

		// render template
		$template = new Membership_Render_Gateway_Authorize_Transactions();
		$template->table = $table;
		$template->render();
	}

}