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
 * Base class for Membership gateways.
 *
 * @since 3.5
 *
 * @abstract
 * @category Membership
 * @package Gateway
 */
abstract class Membership_Gateway extends Membership_Hooker {

	/**
	 * Database connection instance.
	 *
	 * @access protected
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Gateway id.
	 *
	 * @access public
	 * @var string
	 */
	public $gateway = 'Not Set';

	/**
	 * Gateway title.
	 *
	 * @access public
	 * @var string
	 */
	public $title = 'Not Set';

	/**
	 *
	 * @access public
	 * @var boolean
	 */
	public $issingle = false;

	/**
	 * Determines whether gateway has payment form or not.
	 *
	 * @access public
	 * @var boolean
	 */
	public $haspaymentform = false;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @global wpdb $wpdb Current database connection instance.
	 */
	public function __construct() {
		global $wpdb;

		$this->db = $wpdb;

		$this->_add_filter( 'M_gateways_list', 'gateways_list' );

		$this->_add_action( 'membership_process_payment_return', 'process_payment_return' );
		$this->_add_action( 'membership_record_user_gateway', 'record_user_gateway' );
	}

	/**
	 * Registers this gateway in the list of gateways.
	 *
	 * @filter M_gateways_list
	 *
	 * @access public
	 * @param array $gateways The array of gateways.
	 * @return array Updated array of gateways.
	 */
	public function gateways_list( $gateways ) {
		$gateways[$this->gateway] = $this->title;
		return $gateways;
	}

	/**
	 * Activates the gateway if it hasn't been activated yet or deactivates it
	 * if it has been.
	 *
	 * @access public
	 */
	public function toggle_activation() {
		$active = get_option( 'membership_activated_gateways', array() );

		if ( in_array( $this->gateway, $active ) ) {
			unset( $active[$this->gateway] );
		} else {
			$active[$this->gateway] = $this->gateway;
		}

		update_option( 'membership_activated_gateways', $active );
	}

	/**
	 * Activates the gateway.
	 *
	 * @access public
	 * @return boolean TRUE if the gateway was activated, otherwise FALSE.
	 */
	public function activate() {
		$active = get_option( 'membership_activated_gateways', array() );
		if ( !in_array( $this->gateway, $active ) ) {
			$active[$this->gateway] = $this->gateway;
			update_option( 'membership_activated_gateways', $active );
			return true;
		}

		return false;
	}

	/**
	 * Deactivates the gateway.
	 *
	 * @access public
	 * @return boolean TRUE if the gateway has been deactivated, otherwise FALSE.
	 */
	public function deactivate() {
		$active = get_option( 'membership_activated_gateways', array() );

		if ( in_array( $this->gateway, $active ) ) {
			unset( $active[$this->gateway] );
			update_option( 'membership_activated_gateways', $active );
			return true;
		}

		return false;
	}

	/**
	 * Determines whether current gateway is active or not.
	 *
	 * @access public
	 * @return boolean TRUE if the gateway is active, otherwise FALSE.
	 */
	public function is_active() {
		return in_array( $this->gateway, get_option( 'membership_activated_gateways', array() ) );
	}

	/**
	 * Renders gateway settings form wrapper.
	 *
	 * @access public
	 */
	public function settings() {
		?><div class="wrap nosubsub">
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php printf( __( 'Edit %s settings', 'membership' ), esc_html( $this->title ) ) ?></h2>

			<form method="post">
				<input type="hidden" name="action" value="updated">
				<input type="hidden" name="gateway" value="<?php echo $this->gateway ?>">
				<?php wp_nonce_field( 'updated-' . $this->gateway ) ?>

				<?php do_action( 'M_gateways_settings_' . $this->gateway ) ?>

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>">
				</p>
			</form>
		</div><?php
	}

	/**
	 * Updates gateway settings.
	 *
	 * @abstract
	 * @access public
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	public abstract function update();

	/**
	 * Returns transactions array.
	 *
	 * @access protected
	 * @param string $type Transactions type.
	 * @param int $startat The offset of transactions.
	 * @param int $num The amount of transactions.
	 * @param int $total The output parameter which received total amount of found transactions.
	 * @return array The array of transactions.
	 */
	protected function _get_transactions( $type, $startat, $num, &$total = null ) {
		$in = 'IN';
		$statuses = array();
		switch ( $type ) {
			case 'past':
				$in = 'NOT IN';
				$statuses[] = 'Future';
				$statuses[] = 'Pending';
				break;
			case 'pending':
				$statuses[] = 'Pending';
				break;
			case 'future':
				$statuses[] = 'Future';
				break;
		}

		$statuses = implode( "', '", $statuses );
		$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION . " WHERE transaction_status {$in} ('{$statuses}') AND transaction_gateway = %s ORDER BY transaction_ID DESC LIMIT %d, %d";
		$results = $this->db->get_results( $this->db->prepare( $sql, $this->gateway, $startat, $num ) );
		$total = $this->db->get_var( "SELECT FOUND_ROWS()" );

		return $results;
	}

	/**
	 * Checks if transaction is already exists in database.
	 *
	 * @access protected
	 * @param int $user_id The user ID.
	 * @param int $sub_id The subscription ID.
	 * @param mixed $timestamp Timestamp.
	 * @param int $paypal_ID
	 * @return boolean TRUE if transaction is already exists, otherwise FALSE.
	 */
	protected function _check_duplicate_transaction( $user_id, $sub_id, $timestamp, $paypal_ID ) {
		$sql = "SELECT transaction_ID FROM " . MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION . " WHERE transaction_subscription_ID = %d AND transaction_user_ID = %d AND transaction_paypal_ID = %s AND transaction_stamp = %d LIMIT 1";
		$sql = $this->db->prepare( $sql, $sub_id, $user_id, $paypal_ID, $timestamp );
		$trans = $this->db->get_var( $sql );
		return !empty( $trans );
	}

	/**
	 * Records transaction into database.
	 *
	 * @access protected
	 * @param type $user_id
	 * @param type $sub_id
	 * @param type $amount
	 * @param type $currency
	 * @param type $timestamp
	 * @param type $paypal_ID
	 * @param type $status
	 * @param type $note
	 */
	protected function _record_transaction( $user_id, $sub_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note ) {
		$data = array(
			'transaction_subscription_ID' => $sub_id,
			'transaction_user_ID'         => $user_id,
			'transaction_paypal_ID'       => $paypal_ID,
			'transaction_stamp'           => $timestamp,
			'transaction_currency'        => $currency,
			'transaction_status'          => $status,
			'transaction_total_amount'    => (int)round( $amount * 100 ),
			'transaction_note'            => $note,
			'transaction_gateway'         => $this->gateway,
		);

		$existing_id = $this->db->get_var( $this->db->prepare( "SELECT transaction_ID FROM " . MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION . " WHERE transaction_paypal_ID = %s LIMIT 1", $paypal_ID ) );
		if ( !empty( $existing_id ) ) {
			$this->db->update( MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION, $data, array( 'transaction_ID' => $existing_id ) );
		} else {
			$this->db->insert( MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION, $data );
		}
	}

	/**
	 * Renders gateways transaction page wrapper.
	 *
	 * @access public
	 * @global string $type Active transactions type filter.
	 */
	public function transactions() {
		global $type;

		wp_reset_vars( array( 'type' ) );
		if ( empty( $type ) ) {
			$type = 'past';
		}

		$types = array(
			'past'    => __( 'Recent transactions', 'membership' ),
			'pending' => __( 'Pending transactions', 'membership' ),
			'future'  => __( 'Future transactions', 'membership' ),
		);

		$current = 0;
		$count = count( $types );

		?><div class="wrap">
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php echo esc_html( $this->title ), ' ', esc_html__( 'transactions', 'membership' ) ?></h2>

			<ul class="subsubsub">
				<?php foreach ( $types as $key => $label ) : ?>
				<li>
					<a href="<?php echo add_query_arg( 'type', $key ) ?>" class="rbutton<?php echo $type == $key ? ' current' : '' ?>"><?php echo $label ?></a>
					<?php if ( $count > ++$current ) : ?>|<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( has_action( 'M_gateways_transactions_' . $this->gateway ) ) : ?>
				<?php do_action( 'M_gateways_transactions_' . $this->gateway, $type ) ?>
			<?php else : ?>
				<?php $this->_render_transactions( $type ) ?>
			<?php endif; ?>
		</div><?php
	}

	/**
	 * Rendres transactions.
	 *
	 * @access protected
	 * @param string $type Transaction type to render.
	 */
	protected function _render_transactions( $type = 'past' ) {
		$factory = Membership_Plugin::factory();

		$columns = array(
			'subscription' => __( 'Subscription', 'membership' ),
			'user'         => __( 'User', 'membership' ),
			'date'         => __( 'Date', 'membership' ),
			'amount'       => __( 'Amount', 'membership' ),
			'transid'      => __( 'Transaction id', 'membership' ),
			'status'       => __( 'Status', 'membership' ),
			'note'         => __( 'Notes', 'membership' ),
		);
		$columncount = count( $columns );

		$perpage = 50;
		$paged = filter_input( INPUT_GET, 'paged', FILTER_VALIDATE_INT, array( 'options' => array(
			'min_range' => 1,
			'default'   => 1,
		) ) );

		$startat = ( $paged - 1 ) * $perpage;
		$total = 0;

		$transactions = $this->_get_transactions( $type, $startat, $perpage, $total );

		$trans_navigation = paginate_links( array(
			'base'    => add_query_arg( 'paged', '%#%' ),
			'format'  => '',
			'total'   => ceil( $total / 50 ),
			'current' => $paged
		) );

		?><div class="tablenav">
			<?php if ( $trans_navigation ) : ?>
			<div class="tablenav-pages"><?php echo $trans_navigation ?></div>
			<?php endif; ?>
		</div>

		<table cellspacing="0" class="widefat fixed">
			<thead>
				<tr>
					<?php foreach ( $columns as $key => $col ) : ?>
					<th class="manage-column column-<?php echo $key ?>" id="<?php echo $key ?>" scope="col"><?php echo $col ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<?php foreach ( $columns as $key => $col ) : ?>
					<th class="manage-column column-<?php echo $key ?>" id="<?php echo $key ?>" scope="col"><?php echo $col ?></th>
					<?php endforeach; ?>
				</tr>
			</tfoot>
			<tbody>
				<?php if ( $transactions ) : ?>
					<?php foreach ( $transactions as $key => $transaction ) : ?>
						<?php $subscription = $factory->get_subscription( $transaction->transaction_subscription_ID ) ?>
						<?php $member = $factory->get_member( $transaction->transaction_user_ID ) ?>
						<tr valign="middle" class="alternate">
							<td class="column-subscription">
								<?php echo $subscription->sub_name() ?>
							</td>
							<td class="column-user">
								<?php echo $member->user_login ?>
							</td>
							<td class="column-date">
								<?php echo date( DATE_COOKIE, $transaction->transaction_stamp ) ?>
							</td>
							<td class="column-amount">
								<?php echo $transaction->transaction_currency ?> <?php echo number_format( $transaction->transaction_total_amount / 100, 2, '.', ',' ) ?>
							</td>
							<td class="column-transid">
								<?php if ( !empty( $transaction->transaction_paypal_ID ) ) : ?>
									<?php echo $transaction->transaction_paypal_ID ?>
								<?php else : ?>
									<?php _e( 'None yet', 'membership' ) ?>
								<?php endif; ?>
							</td>
							<td class="column-transid">
								<?php if ( !empty( $transaction->transaction_status ) ) : ?>
									<?php echo $transaction->transaction_status ?>
								<?php else : ?>
									<?php _e( 'None yet', 'membership' ) ?>
								<?php endif; ?>
							</td>
							<td class="column-transid">
								<?php if ( !empty( $transaction->transaction_note ) ) : ?>
									<?php echo $transaction->transaction_note ?>
								<?php else : ?>
									<?php _e( 'None', 'membership' ) ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr valign="middle" class="alternate" >
						<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e( 'No Transactions have been found, patience is a virtue.', 'membership' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table><?php
	}

	/**
	 * Processes payment return.
	 *
	 * @access public
	 * @param string $gateway The gateway name.
	 */
	public function process_payment_return( $gateway ) {
		if ( !apply_filters( 'membership_override_payment_return_' . $gateway, false ) ) {
			do_action( 'membership_handle_payment_return_' . $gateway );
		}
	}

	/**
	 * Updates user signup gateway.
	 *
	 * @access public
	 * @param int $user_id The user id.
	 */
	public function record_user_gateway( $user_id ) {
		update_user_meta( $user_id, 'membership_signup_gateway', $this->gateway );
		update_user_meta( $user_id, 'membership_signup_gateway_is_single', $this->issingle ? 'yes' : 'no' );
	}

	/**
	 * Displays upgrade from free subscription button.
	 *
	 * @access public
	 * @param type $subscription
	 * @param type $pricing
	 * @param type $user_id
	 * @param type $fromsub_id
	 */
	public function display_upgrade_from_free_button( $subscription, $pricing, $user_id, $fromsub_id = false ) {
		// By default there is no default button available
		?><input type="submit" class="button blue" value="<?php _e( 'Upgrades not available', 'membership' ) ?>" disabled><?php
	}

	/**
	 * Displays upgrade button.
	 *
	 * @access public
	 * @param type $pricing
	 * @param type $subscription
	 * @param type $user_id
	 * @param type $fromsub_id
	 */
	public function display_upgrade_button( $pricing, $subscription, $user_id, $fromsub_id = false ) {
		// By default there is no default button available
		?><input type="submit" class="button blue" value="<?php _e( 'Upgrades not available', 'membership' ) ?>" disabled><?php
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
		// By default there is no default button available
		?><input type="submit" class="button blue" value="<?php _e( 'Unsubscribe not available', 'membership' ) ?>" disabled><?php
	}

	/**
	 * Displays payment form.
	 *
	 * @access public
	 * @param type $subscription
	 * @param type $pricing
	 * @param type $user_id
	 */
	public function display_payment_form( $subscription, $pricing, $user_id ) {
		die( 'You Must Override The display_payment_form() in your gateway' );
	}

	/**
	 *
	 * @access public
	 * @global string $M_options
	 * @param type $pricing
	 * @param type $subscription
	 * @param type $user_id
	 * @param type $sublevel
	 * @return string
	 */
	public function single_free_button( $pricing, $subscription, $user_id, $sublevel = 0 ) {

		global $M_options;
		if ( empty( $M_options['paymentcurrency'] ) ) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '<form action="' . M_get_returnurl_permalink() . '" method="post">';
		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom( $user_id, $subscription->id, '0', $sublevel ) . '">';

		if ( $sublevel == 1 ) {
			$form .= '<input type="hidden" name="action" value="subscriptionsignup" />';
			$form .= wp_nonce_field( 'free-sub_' . $subscription->sub_id(), "_wpnonce", false, false );
			$form .= "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";

			$button = get_option( $this->gateway . "_payment_button", '' );
			if ( empty( $button ) ) {
				$form .= '<input type="submit" class="button ' . apply_filters( 'membership_subscription_button_color', 'blue' ) . '" value="' . __( 'Sign Up', 'membership' ) . '" />';
			} else {
				$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
			}
		} else {
			$form .= wp_nonce_field( 'renew-sub_' . $subscription->sub_id(), "_wpnonce", false, false );
			//$form .=  wp_nonce_field('free-sub_' . $subscription->sub_id(), "_wpnonce", false, false);
			$form .= "<input type='hidden' name='action' value='subscriptionsignup' />";
			$form .= "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
			$form .= "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
			$form .= "<input type='hidden' name='user' value='" . $user_id . "' />";
			$form .= "<input type='hidden' name='level' value='" . $sublevel . "' />";

			$button = get_option( $this->gateway . "_payment_button", '' );
			if ( empty( $button ) ) {
				$form .= '<input type="submit" class="button ' . apply_filters( 'membership_subscription_button_color', 'blue' ) . '" value="' . __( 'Sign Up', 'membership' ) . '" />';
			} else {
				$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
			}
		}

		$form .= '</form>';

		return $form;
	}

	/**
	 *
	 * @access public
	 * @param type $content
	 * @param type $error
	 * @return type
	 */
	public function signup_free_subscription( $content, $error ) {
		if ( isset( $_POST['custom'] ) ) {
			list($timestamp, $user_id, $sub_id, $key) = explode( ':', $_POST['custom'] );
		}

		// create_subscription
		$member = Membership_Plugin::factory()->get_member( $user_id );
		if ( $member ) {
			$member->create_subscription( $sub_id, $this->gateway );
		}

		do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

		$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part
		$content .= '<div class="formleft">';

		$message = get_option( $this->gateway . "_completed_message", $this->defaultmessage );
		$content .= stripslashes( $message );

		$content .= '</div>';
		$content .= "</div>";

		$content = apply_filters( 'membership_subscriptionform_signedup', $content, $user_id, $sub_id );

		return $content;
	}

	/**
	 * Returns gateway instance.
	 *
	 * @static
	 * @access public
	 * @global array $M_Gateways The array of registered gateways.
	 * @param string $gateway_id The gateway to return.
	 * @return Membership_Gateway The instance of gateway if it is registered, otherwise NULL.
	 */
	public static function get_gateway( $gateway_id ) {
		global $M_Gateways;
		return array_key_exists( $gateway_id, $M_Gateways ) ? $M_Gateways[$gateway_id] : null;
	}

	/**
	 * Registers gateway in the plugin.
	 *
	 * @static
	 * @access public
	 * @global array $M_Gateways The array of registered gateways.
	 * @param string $gateway_id The gateway id to register.
	 * @param string $class The gateway class name to register.
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	public static function register_gateway( $gateway_id, $class ) {
		global $M_Gateways;

		if ( !is_array( $M_Gateways ) ) {
			$M_Gateways = array();
		}

		$gateway = new $class();
		if ( is_a( $gateway, __CLASS__ ) ) {
			$M_Gateways[$gateway_id] = $gateway;
			return true;
		}

		return false;
	}

	/**
	 * Returns user IP address.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access protected
	 * @return string Remote IP address on success, otherwise FALSE.
	 */
	protected static function _get_remote_ip() {
		$flag = !WP_DEBUG ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : null;
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		$remote_ip = false;
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( array_filter( array_map( 'trim', explode( ',', $_SERVER[$key] ) ) ) as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP, $flag ) !== false ) {
						$remote_ip = $ip;
						break;
					}
				}
			}
		}

		return $remote_ip;
	}

}