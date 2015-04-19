<?php
/**
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
 * Invoice model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Invoice extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $POST_TYPE = 'ms_invoice';
	public $post_type = 'ms_invoice';

	/**
	 * Invoice status constants.
	 *
	 * @since 1.0.0
	 *
	 * @see $status property.
	 * @var string
	 */
	// Invoice was created but user did not make any attempt to pay
	const STATUS_BILLED = 'billed';

	// User confirmed payment and it was successful
	const STATUS_PAID = 'paid';

	// User confirmed payment but gateway returned a "pending" notification
	const STATUS_PENDING = 'pending';

	// User confirmed payment but gateway returned some error (dispute, wrong amount, etc.)
	const STATUS_DENIED = 'denied';

	/**
	 * External transaction ID.
	 *
	 * Used to link 3rd party transaction ID to $this->id
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $external_id;

	/**
	 * Gateway ID.
	 *
	 * Gateway used to pay this invoice.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $gateway_id;

	/**
	 * Membership ID.
	 *
	 * Invoice for membership.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $membership_id;

	/**
	 * User ID.
	 *
	 * Invoice for this user/member.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $user_id;

	/**
	 * Membership Relationship ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $ms_relationship_id;

	/**
	 * Coupon ID.
	 *
	 * Used coupon ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $coupon_id;

	/**
	 * Currency of this invoice.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $currency;

	/**
	 * Amount value not including discounts.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	protected $amount;

	/**
	 * Discount value.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	protected $discount;

	/**
	 * Pro rate value.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	protected $pro_rate;

	/**
	 * Total value.
	 *
	 * Includes discount, pro-rating.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	protected $total;

	/**
	 * Inovoice status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $status;

	/**
	 * Invoice for trial period.
	 *
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $uses_trial;

	/**
	 * The trial period price.
	 *
	 * @since 1.1.1.4
	 * @var numeric
	 */
	protected $trial_price;

	/**
	 * This is the last day of the trial period. The next day is paid.
	 *
	 * @since 1.1.1.4
	 * @var date
	 */
	protected $trial_ends;

	/**
	 * Invoice due date.
	 * When invoice uses_trial is true then this is the first day that is paid.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $due_date;

	/**
	 * Invoice notes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $notes;

	/**
	 * Invoice number.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $invoice_number;

	/**
	 * Tax rate value.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	protected $tax_rate;

	/**
	 * Tax name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $tax_name;

	/**
	 * Short, compact version of the payment description
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $short_description = '';

	/**
	 * Where the data came from. Can only be changed by data import tool
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $source = '';

	/**
	 * Timestamp of price calculation.
	 * This information is used when price-options of the memberhsip is changed.
	 *
	 * @since 1.1.1.3
	 * @var int
	 */
	protected $price_date = 0;

	//
	//
	//
	// -------------------------------------------------------------- COLLECTION

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Protected Content Invoices', MS_TEXT_DOMAIN ),
			'description' => __( 'Member Invoices', MS_TEXT_DOMAIN ),
			'public' => true,
			'show_ui' => false,
			'show_in_menu' => false,
			'has_archive' => false,
			'publicly_queryable' => true,
			'supports' => false,
			'hierarchical' => false,
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::$POST_TYPE
		);
	}

	/**
	 * Get invoice status types.
	 *
	 * @since 1.0.0
	 */
	public static function get_status_types() {
		return apply_filters(
			'ms_model_invoice_get_status_types',
			array(
				self::STATUS_PAID => __( 'Paid', MS_TEXT_DOMAIN ),
				self::STATUS_BILLED => __( 'Billed', MS_TEXT_DOMAIN ),
				self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
				self::STATUS_DENIED => __( 'Denied', MS_TEXT_DOMAIN ),
			)
		);
	}

	/**
	 * Returns the default query-arg array
	 *
	 * @since  1.0.4.5
	 * @return array
	 */
	public static function get_query_args() {
		$args = array();

		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		} else {
			$args['orderby'] = 'ID';
			$args['order'] = 'DESC';
		}

		// Prepare order by statement.
		$orderby = $args['orderby'];
		if ( ! empty( $orderby )
			&& ! in_array( $orderby, array( 'ID', 'author' ) )
			&& property_exists( 'MS_Model_Invoice', $orderby )
		) {
			$args['meta_key'] = $orderby;
			if ( in_array( $orderby, array( 'amount', 'total' ) ) ) {
				$args['orderby'] = 'meta_value_num';
			} else {
				$args['orderby'] = 'meta_value';
			}
		}

		// Search string.
		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['author_name'] = $_REQUEST['s'];
		}

		$args['meta_query'] = array();

		// Gateway filter.
		if ( ! empty( $_REQUEST['gateway_id'] ) ) {
			$args['meta_query']['gateway_id'] = array(
				'key' => 'gateway_id',
				'value' => $_REQUEST['gateway_id'],
			);
		}

		// Payment status filter.
		if ( ! empty( $_REQUEST['status'] ) ) {
			if ( 'open' === $_REQUEST['status'] ) {
				$args['meta_query']['status'] = array(
					'key' => 'status',
					'value' => array(
						self::STATUS_BILLED,
						self::STATUS_PENDING,
					),
					'compare' => 'IN',
				);
			} else {
				$args['meta_query']['status'] = array(
					'key' => 'status',
					'value' => $_REQUEST['status'],
				);
			}
		}

		return $args;
	}

	/**
	 * Get transaction count.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 */
	public static function get_invoice_count( $args = null ) {
		$defaults = array(
			'post_type' => self::$POST_TYPE,
			'post_status' => 'any',
		);
		$args = apply_filters(
			'ms_model_invoice_get_invoice_count_args',
			wp_parse_args( $args, $defaults )
		);

		$query = new WP_Query( $args );

		return apply_filters(
			'ms_model_invoice_get_invoice_count',
			$query->found_posts,
			$args
		);
	}

	/**
	 * Get invoices.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args The arguments to select data.
	 * @return array $invoices
	 */
	public static function get_invoices( $args = null ) {
		$defaults = array(
			'post_type' => self::$POST_TYPE,
			'posts_per_page' => 10,
			'post_status' => 'any',
			'fields' => 'ids',
			'order' => 'DESC',
			'orderby' => 'ID',
		);
		$args = apply_filters(
			'ms_model_invoice_get_invoices_args',
			wp_parse_args( $args, $defaults )
		);

		$query = new WP_Query( $args );
		$items = $query->get_posts();
		$invoices = array();

		foreach ( $items as $item ) {
			$invoices[] = MS_Factory::load( 'MS_Model_Invoice', $item );
		}

		return apply_filters(
			'ms_model_invoice_get_invoices',
			$invoices,
			$args
		);
	}

	/**
	 * Get specific invoice.
	 *
	 * Get invoice of a user and membership.
	 *
	 * @since 1.0.0
	 *
	 * @param int $subscription_id The membership relationship id.
	 * @param int $invoice_number Optional. The invoice number. Get the current number if null.
	 * @param string $status Optional. The invoice status.
	 * @return MS_Model_Invoice The found invoice or null if not found.
	 */
	public static function get_invoice( $subscription_id, $invoice_number = null, $status = null ) {
		$args = array(
			'post_type' => self::$POST_TYPE,
			'post_status' => 'any',
			'fields' => 'ids',
			'order' => 'DESC',
		);

		$args['meta_query']['ms_relationship_id'] = array(
			'key'     => 'ms_relationship_id',
			'value'   => $subscription_id,
		);
		if ( ! empty( $status ) ) {
			$args['meta_query']['status'] = array(
				'key'     => 'status',
				'value'   => $status,
			);
		}
		if ( ! empty( $invoice_number ) ) {
			$args['meta_query']['invoice_number'] = array(
				'key'     => 'invoice_number',
				'value'   => $invoice_number,
			);
		}

		$args = apply_filters( 'ms_model_invoice_get_invoice_args', $args );
		$query = new WP_Query( $args );
		$item = $query->get_posts();

		$invoice = null;
		if ( ! empty( $item[0] ) ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $item[0] );
		}

		return apply_filters(
			'ms_model_invoice_get_invoice',
			$invoice,
			$subscription_id,
			$invoice_number,
			$status
		);
	}

	/**
	 * Get current member membership invoice.
	 *
	 * The current invoice is the not paid one. Every time a invoice is paid,
	 * the current invoice number is incremented.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 * @param boolean $create_missing Optional. True to overwrite existing invoice or false to create a new one if doesn't exist.
	 * @return MS_Model_Invoice
	 */
	public static function get_current_invoice( $subscription, $create_missing = true ) {
		$invoice = self::get_invoice(
			$subscription->id,
			$subscription->current_invoice_number
		);

		if ( empty( $invoice ) && $create_missing ) {
			$invoice = self::create_invoice(
				$subscription,
				$subscription->current_invoice_number
			);
		}

		return apply_filters(
			'ms_model_invoice_get_current_invoice',
			$invoice,
			$subscription,
			$create_missing
		);
	}

	/**
	 * Get next invoice for the membership.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 * @param boolean $create_missing Optional. True to overwrite existing invoice or false to create a new one if doesn't exist.
	 * @return MS_Model_Invoice
	 */
	public static function get_next_invoice( $subscription, $create_missing = true ) {
		$invoice = self::get_invoice(
			$subscription->id,
			$subscription->current_invoice_number + 1
		);

		if ( empty( $invoice ) && $create_missing ) {
			$invoice = self::create_invoice(
				$subscription,
				$subscription->current_invoice_number + 1
			);
		}

		$invoice->discount = 0;
		$invoice->pro_rate = 0;
		$invoice->notes = array();

		return apply_filters(
			'ms_model_invoice_get_next_invoice',
			$invoice,
			$subscription,
			$create_missing
		);
	}

	/**
	 * Get previous invoice for the membership.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 * @param string $status The invoice status to find. Optional
	 * @return MS_Model_Invoice
	 */
	public static function get_previous_invoice( $ms_relationship, $status = null ) {
		$invoice = self::get_invoice(
			$ms_relationship->id,
			$ms_relationship->current_invoice_number - 1,
			$status
		);

		return apply_filters(
			'ms_model_invoice_get_previous_invoice',
			$invoice,
			$ms_relationship,
			$status
		);
	}

	/**
	 * Create invoice.
	 *
	 * Create a new invoice using the membership information.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship The membership to create invoice for.
	 * @param int $invoice_number Optional. The invoice number.
	 *
	 * @return object $invoice
	 */
	public static function create_invoice( $ms_relationship, $invoice_number = false ) {
		$membership = $ms_relationship->get_membership();

		if ( ! MS_Model_Membership::is_valid_membership( $membership->id ) ) {
			throw new Exception( 'Invalid Membership.' );
		}

		$invoice = null;
		$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
		$invoice_status = self::STATUS_BILLED;
		$notes = null;
		$due_date = null;

		if ( empty( $invoice_number ) ) {
			$invoice_number = $ms_relationship->current_invoice_number;
		}

		// No existing invoice, create a new one.
		if ( empty( $invoice ) ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice' );
			$invoice = apply_filters( 'ms_model_invoice', $invoice );
		}

		// Update invoice info.
		$invoice->ms_relationship_id = $ms_relationship->id;
		$invoice->gateway_id = $ms_relationship->gateway_id;
		$invoice->status = $invoice_status;
		$invoice->membership_id = $membership->id;
		$invoice->currency = MS_Plugin::instance()->settings->currency;
		$invoice->user_id = $member->id;
		$invoice->name = apply_filters(
			'ms_model_invoice_name',
			sprintf(
				__( 'Invoice for %s - %s', MS_TEXT_DOMAIN ),
				$membership->name,
				$member->username
			)
		);
		$invoice->invoice_number = $invoice_number;
		$invoice->discount = 0;

		// Calc pro rate discount if moving from another membership.
		if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_PRO_RATE )
			&& $ms_relationship->move_from_id
		) {
			$move_from = MS_Model_Relationship::get_subscription(
				$ms_relationship->user_id,
				$ms_relationship->move_from_id
			);

			if ( ! empty( $move_from->id )
				&& ! empty( $gateway )
				&& $gateway->pro_rate
			) {
				$pro_rate = self::calculate_pro_rate( $move_from );

				if ( $pro_rate ) {
					$invoice->pro_rate = $pro_rate;
					$notes[] = sprintf(
						__( 'Pro rate discount: %s %s. ', MS_TEXT_DOMAIN ),
						$invoice->currency,
						$pro_rate
					);
				}
			}
		}

		$invoice->notes = $notes;

		// Due date calculation.
		switch ( $ms_relationship->status ) {
			default:
			case MS_Model_Relationship::STATUS_PENDING:
			case MS_Model_Relationship::STATUS_EXPIRED:
			case MS_Model_Relationship::STATUS_DEACTIVATED:
				$due_date = MS_Helper_Period::current_date();
				break;

			case MS_Model_Relationship::STATUS_TRIAL:
				$due_date = $ms_relationship->trial_expire_date;
				break;

			case MS_Model_Relationship::STATUS_ACTIVE:
			case MS_Model_Relationship::STATUS_CANCELED:
				$due_date = $ms_relationship->expire_date;
				break;
		}
		$invoice->due_date = $due_date;

		$invoice = apply_filters(
			'ms_model_invoice_create_before_save',
			$invoice,
			$ms_relationship
		);

		$invoice->amount = $membership->price; // Without taxes!

		// Check for trial period in the first period.
		if ( $ms_relationship->is_trial_eligible()
			&& $invoice_number === $ms_relationship->current_invoice_number
		) {
			$invoice->trial_price = $membership->trial_price; // Without taxes!
			$invoice->uses_trial = true;
			$invoice->trial_ends = $ms_relationship->trial_expire_date;
		}

		$invoice->save();

		$invoice->total_amount_changed();
		$invoice->save();

		return apply_filters(
			'ms_model_relationship_create_invoice',
			$invoice,
			$ms_relationship,
			$invoice_number
		);
	}

	/**
	 * Calculate pro rate value.
	 *
	 * Pro rate using remaining membership days. For further versions.
	 *
	 * @since 1.0.0
	 *
	 * @return float The pro rate value.
	 */
	public static function calculate_pro_rate( $ms_relationship ) {
		$value = 0;
		$membership = $ms_relationship->get_membership();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS )
			&& MS_Model_Membership::PAYMENT_TYPE_PERMANENT !== $membership->payment_type
		) {
			$invoice = self::get_previous_invoice( $ms_relationship );

			if ( ! empty( $invoice ) && self::STATUS_PAID === $invoice->status ) {
				switch ( $ms_relationship->get_status() ) {
					case MS_Model_Relationship::STATUS_TRIAL:
						if ( $invoice->trial_period ) {
							$remaining_days = $ms_relationship->get_remaining_trial_period();
							$total_days = MS_Helper_Period::subtract_dates(
								$ms_relationship->trial_expire_date,
								$ms_relationship->start_date
							);
							$value = $remaining_days / $total_days;
							$value *= $invoice->total;
						}
						break;

					case MS_Model_Relationship::STATUS_ACTIVE:
					case MS_Model_Relationship::STATUS_CANCELED:
						if ( ! $invoice->trial_period ) {
							$remaining_days = $ms_relationship->get_remaining_period();
							$total_days = MS_Helper_Period::subtract_dates(
								$ms_relationship->expire_date,
								$ms_relationship->start_date
							);
							$value = $remaining_days / $total_days;
							$value *= $invoice->total;
						}
						break;

					default:
						$value = 0;
						break;
				}
			}
		}

		return apply_filters(
			'ms_model_invoice_calculate_pro_rate_value',
			$value,
			$ms_relationship
		);
	}

	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM

	/**
	 * Registers the payment and marks the invoice as paid.
	 *
	 * This should be the only place that sets an invoice status to PAID.
	 *
	 * @since  1.1.0
	 * @param  string $gateway_id The payment gateway.
	 * @param  string $external_id Payment-ID provided by the gateway
	 */
	public function pay_it( $gateway_id, $external_id ) {
		$this->gateway_id = $gateway_id;
		$this->external_id = $external_id;
		$this->status = self::STATUS_PAID;
		$this->save();

		if ( $this->total > 0 ) {
			// Save details on the payment.
			$subscription = $this->get_subscription();
			$subscription->add_payment( $this->total, $gateway_id );
		}

		// Process the
		$this->changed();

		/**
		 * Notify Add-ons that an invoice was paid.
		 *
		 * @since 1.1.0
		 */
		do_action( 'ms_invoice_paid', $this );
	}

	/**
	 * Returns true if the invoice was paid.
	 *
	 * @since  1.1.1.4
	 * @return bool Payment status.
	 */
	public function is_paid() {
		return $this->status == self::STATUS_PAID;
	}

	/**
	 * Update the subscription details after the invoice has changed.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Invoice $invoice The invoice to process.
	 * @return MS_Model_Invoice The processed invoice.
	 */
	public function changed() {
		do_action(
			'ms_model_invoice_changed_before',
			$this
		);

		if ( ! $this->ms_relationship_id ) {
			MS_Helper_Debug::log( 'Cannot process transaction: No relationship defined (inv #' . $this->id  .')' );
		} else {
			$subscription = $this->get_subscription();
			$member = MS_Factory::load( 'MS_Model_Member', $this->user_id );
			$membership = $subscription->get_membership();

			// Free invoices skip the BILLED/PENDING status
			if ( 0 == $this->total ) {
				switch ( $this->status ) {
					case self::STATUS_PENDING:
						$this->pay_it( MS_Gateway_Free::ID, '' );
						break;
				}
			}

			switch ( $this->status ) {
				case self::STATUS_BILLED:
					break;

				case self::STATUS_PAID:
					if ( $this->total > 0 ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_PAID, $subscription );
					}

					do_action(
						'ms_model_invoice_changed-paid',
						$this,
						$member
					);

					// Check for moving memberships
					if ( MS_Model_Relationship::STATUS_PENDING == $subscription->status
						&& $subscription->move_from_id
						&& ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS )
					) {
						$move_from = MS_Model_Relationship::get_subscription(
							$subscription->user_id,
							$subscription->move_from_id
						);

						if ( $move_from->is_valid() ) {
							$move_from->set_status( MS_Model_Relationship::STATUS_CANCELED );
							$move_from->save();
						}
					}

					// The trial period info gets updated after MS_Model_Relationship::config_period()
					$trial_period = $subscription->is_trial_eligible();
					$subscription->current_invoice_number = max(
						$subscription->current_invoice_number,
						$this->invoice_number + 1
					);
					$member->is_member = true;
					$member->active = true;
					$subscription->config_period();
					$subscription->set_status( MS_Model_Relationship::STATUS_ACTIVE );
					break;

				case self::STATUS_DENIED:
					MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_DENIED, $subscription );
					break;

				case self::STATUS_PENDING:
					MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_PENDING, $subscription );
					break;

				default:
					do_action( 'ms_model_invoice_changed-unknown', $this );
					break;
			}

			$member->save();
			$subscription->gateway_id = $this->gateway_id;
			$subscription->save();
			$this->gateway_id = $this->gateway_id;
			$this->save();
		}

		return apply_filters(
			'ms_model_invoice_changed',
			$this,
			$this
		);
	}

	/**
	 * Add invoice notes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $notes
	 */
	public function add_notes( $notes ) {
		$this->notes[] = apply_filters(
			'ms_model_invoice_add_notes',
			$notes,
			$this
		);
	}

	/**
	 * Get notes array as string.
	 *
	 * @since 1.0.0
	 *
	 * @return string The notes as text description.
	 */
	public function get_notes_desc() {
		$desc = $this->notes;
		if ( is_array( $desc ) ) {
			$desc = implode( "\n", $desc );
		}

		return apply_filters(
			'ms_model_invoice_get_notes_desc',
			$desc,
			$this
		);
	}

	/**
	 * Returns a translated version of the invoice status
	 *
	 * @since  1.1.1.4
	 * @return string
	 */
	public function status_text() {
		static $Status = null;

		if ( null === $Status ) {
			$Status = self::get_status_types();
		}

		$result = $this->status;

		if ( isset( $Status[$this->status] ) ) {
			$result = $Status[$this->status];
		}

		return apply_filters(
			'ms_invoice_status_text',
			$result,
			$this->status
		);
	}

	/**
	 * Updates various fields that display/depend on the invoice total amount.
	 *
	 * @since  1.1.0
	 */
	public function total_amount_changed() {
		$subscription = $this->get_subscription();

		// Allow add-ons or other plugins to set the tax infos for this invoice.
		$this->tax_rate = apply_filters(
			'ms_invoice_tax_rate',
			0,
			$this
		);
		$this->tax_name = apply_filters(
			'ms_invoice_tax_name',
			'',
			$this
		);

		// Update the invoice descriptions that are displayed to the user.
		$this->description = apply_filters(
			'ms_model_invoice_description',
			$subscription->get_payment_description( $this )
		);
		$this->short_description = apply_filters(
			'ms_model_invoice_short_description',
			$subscription->get_payment_description( $this, true )
		);
	}

	/**
	 * Sets the invoice amount to the price defined by the membership settings.
	 *
	 * Provides the filter `ms_model_invoice_price_timeout` which can be used to
	 * define the price-timeout value. The price will not be updated before the
	 * timeout is reached.
	 *
	 * Only unpaid invoices are updated!
	 *
	 * @since  1.1.1.3
	 */
	private function refresh_amount() {
		// Never change the amount of paid invoices.
		if ( 'paid' == $this->status ) { return; }

		/**
		 * Define a timeout for the price in unpaid invoices.
		 * The price will not change before the timeout expires, after this
		 * it is updated again based on the current membership settings.
		 *
		 * @var int
		 */
		$timeout = apply_filters(
			'ms_model_invoice_price_timeout',
			604800, // 604800 = 7 days
			$this
		);

		$expire_timestamp = absint( $this->price_date ) + absint( $timeout );

		// Do not change price before timeout is reached.
		if ( $expire_timestamp > time() ) { return; }

		// Store the current timestamp, so we don't refresh the price until
		// the timeout expires again.
		$this->price_date = time();
		$membership = $this->get_membership();

		// The invoice always has the real membership price as amount, never
		// the trial amount.
		$this->amount = $membership->price; // Without taxes!

		$this->save();
	}

	/**
	 * Get invoice net amount: Amount excluding taxes.
	 *
	 * Discounting coupon and pro-rating.
	 * Add taxes.
	 *
	 * @since 1.0.0
	 */
	private function get_net_amount() {
		if ( 'paid' != $this->status ) {
			$this->refresh_amount();
		}

		$net_amount = $this->amount; // Net amount
		$net_amount -= $this->discount; // Remove discount
		$net_amount -= $this->pro_rate; // Remove Pro-Rate

		if ( $net_amount < 0 ) {
			$net_amount = 0;
		}

		// Set precission to 2 decimal points.
		$net_amount = round( $net_amount, 2 );

		return apply_filters(
			'ms_model_invoice_get_net_amount',
			$net_amount,
			$this
		);
	}

	/**
	 * Get invoice total.
	 *
	 * Discounting coupon and pro-rating.
	 * Add taxes.
	 *
	 * @since 1.0.0
	 */
	private function get_total() {
		$this->total = $this->get_net_amount(); // Net amount
		$this->total += $this->tax; // Tax-Rate was defined in `create_invoice()`

		if ( $this->total < 0 ) {
			$this->total = 0;
		}

		// Set precission to 2 decimal points.
		$this->total = round( $this->total, 2 );

		return apply_filters(
			'ms_model_invoice_get_total',
			$this->total,
			$this
		);
	}

	/**
	 * Returns the membership model that is linked to this invoice.
	 *
	 * @since  1.1.0
	 * @return MS_Model_Membership
	 */
	public function get_membership() {
		return MS_Factory::load( 'MS_Model_Membership', $this->membership_id );
	}

	/**
	 * Returns the membership model that is linked to this invoice.
	 *
	 * @since  1.1.0
	 * @return MS_Model_Membership
	 */
	public function get_member() {
		return MS_Factory::load( 'MS_Model_Member', $this->user_id );
	}

	/**
	 * Returns the subscription model that is linked to this invoice.
	 *
	 * @since  1.1.0
	 * @return MS_Model_Relationship
	 */
	public function get_subscription() {
		return MS_Factory::load( 'MS_Model_Relationship', $this->ms_relationship_id );
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'total':
					$value = $this->get_total();
					break;

				case 'invoice':
					$value = $this->id;
					break;

				case 'short_description':
					if ( empty( $this->short_description ) ) {
						$value = $this->description;
					} else {
						$value = $this->short_description;
					}
					break;

				default:
					$value = $this->$property;
					break;
			}
		} else {
			switch ( $property ) {
				case 'tax':
					$tax_rate = $this->tax_rate;
					if ( ! is_numeric( $tax_rate ) ) { $tax_rate = 0; }
					$value = $this->get_net_amount() * ( $tax_rate / 100 );
					break;
			}
		}

		return apply_filters(
			'ms_model_invoice__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'name':
				case 'currency':
					$this->$property = sanitize_text_field( $value );
					break;

				case 'notes':
					if ( is_array( $value ) ) {
						$this->notes = array_map( 'sanitize_text_field', $value );
					} else {
						$this->notes = array( sanitize_text_field( $value ) );
					}
					break;

				case 'status':
					if ( array_key_exists( $value, self::get_status_types() ) ) {
						$this->$property = $value;
					}
					break;

				case 'due_date':
					$this->$property = $this->validate_date( $value );
					break;

				case 'amount':
				case 'discount':
				case 'pro_rate':
					$this->$property = floatval( $value );
					$this->get_total();
					$this->total_amount_changed();
					break;

				default:
					$this->$property = $value;
					break;
			}
		}

		do_action(
			'ms_model_invoice__set_after',
			$property,
			$value,
			$this
		);
	}

}