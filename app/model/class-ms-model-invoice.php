<?php
/**
 * Invoice model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Invoice extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected static $POST_TYPE = 'ms_invoice';

	/**
	 * Invoice status constants.
	 *
	 * @since  1.0.0
	 *
	 * @see $status property.
	 * @var string
	 */
	// Invoice was created but user did not yet confirm that he wants to sign up/pay.
	const STATUS_NEW = 'new';

	// Invoice was created but user did not make any attempt to pay.
	const STATUS_BILLED = 'billed';

	// User confirmed payment and it was successful.
	const STATUS_PAID = 'paid';

	// User confirmed payment but gateway returned a "pending" notification.
	const STATUS_PENDING = 'pending';

	// User confirmed payment but gateway returned some error (dispute, wrong amount, etc).
	const STATUS_DENIED = 'denied';

	// Archived invoices are hidden from invoice lists, i.e. "deleted"
	const STATUS_ARCHIVED = 'archived';

	/**
	 * External transaction ID.
	 *
	 * Used to link 3rd party transaction ID to $this->id
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $external_id = '';

	/**
	 * Gateway ID.
	 *
	 * Gateway used to pay this invoice.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $gateway_id = '';

	/**
	 * Membership ID.
	 *
	 * Invoice for membership.
	 *
	 * @since  1.0.0
	 * @var int
	 */
	protected $membership_id = 0;

	/**
	 * User ID.
	 *
	 * Invoice for this user/member.
	 *
	 * @since  1.0.0
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * Log the users IP address once he visits the checkout page.
	 * This way we can also see if the user visited the checkout page to pay the
	 * invoice.
	 *
	 * @since  1.0.2.0
	 * @var string
	 */
	protected $checkout_ip = '';

	/**
	 * Log the timestamp when the user visits the checkout page.
	 *
	 * @since  1.0.2.0
	 * @var string
	 */
	protected $checkout_date = '';

	/**
	 * Membership Relationship ID.
	 *
	 * @since  1.0.0
	 * @var int
	 */
	protected $ms_relationship_id = 0;

	/**
	 * Coupon ID.
	 *
	 * Used coupon ID.
	 *
	 * @since  1.0.0
	 * @var int
	 */
	protected $coupon_id = 0;

	/**
	 * Currency of this invoice.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $currency = '';

	/**
	 * Amount value not including discounts.
	 *
	 * @since  1.0.0
	 * @var float
	 */
	protected $amount = 0;

	/**
	 * Discount value.
	 *
	 * @since  1.0.0
	 * @var float
	 */
	protected $discount = 0;

	/**
	 * Pro rate value.
	 *
	 * @since  1.0.0
	 * @var float
	 */
	protected $pro_rate = 0;

	/**
	 * READ-ONLY. Invoice amount including all discounts but no taxes.
	 *
	 * To modify this value change any of these properties:
	 * amount, discount, pro_rate
	 *
	 * @since  1.0.0
	 * @var float
	 */
	protected $subtotal = 0;

	/**
	 * READ-ONLY. Total value (= subtotal + taxes).
	 *
	 * To modify this value change any of these properties:
	 * amount, discount, pro_rate, tax_rate
	 *
	 * @since  1.0.0
	 * @var float
	 */
	protected $total = 0;

	/**
	 * Inovoice status.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $status = '';

	/**
	 * Invoice for trial period.
	 *
	 * @since  1.0.0
	 * @var boolean
	 */
	protected $uses_trial = false;

	/**
	 * The trial period price.
	 *
	 * @since  1.0.0
	 * @var numeric
	 */
	protected $trial_price = 0;

	/**
	 * This is the last day of the trial period. The next day is paid.
	 *
	 * @since  1.0.0
	 * @var date
	 */
	protected $trial_ends = '';

	/**
	 * Invoice date.
	 *
	 * This is the date when the INVOICE WAS CREATED. It may be differe than the
	 * due date if the subscription uses a trial period.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $invoice_date = '';

	/**
	 * Defines date WHEN PAYMENT IS DUE.
	 * When invoice uses_trial is true then this is the first day that is paid.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $due_date = '';

	/**
	 * Date when the invoice was MARKED AS PAID.
	 *
	 * Note that free invoices do not have a pay-date! The pay-date is only set
	 * when something was actually paid ;)
	 *
	 * @since  1.0.2.0
	 * @var string
	 */
	protected $pay_date = '';

	/**
	 * Invoice notes.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $notes = '';

	/**
	 * Invoice number.
	 *
	 * @since  1.0.0
	 * @var int
	 */
	protected $invoice_number = 0;

	/**
	 * Tax rate value.
	 *
	 * @since  1.0.0
	 * @var float
	 */
	protected $tax_rate = 0;

	/**
	 * Tax name.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $tax_name = '';

	/**
	 * Short, compact version of the payment description
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $short_description = '';

	/**
	 * Where the data came from. Can only be changed by data import tool
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $source = '';

	/**
	 * Timestamp of price calculation.
	 * This information is used when price-options of the memberhsip is changed.
	 *
	 * @since  1.0.0
	 * @var int
	 */
	protected $price_date = 0;

	//
	//
	//
	// -------------------------------------------------------------- COLLECTION

	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  1.0.0
	 * @return string The post-type name.
	 */
	public static function get_post_type() {
		return parent::_post_type( self::$POST_TYPE );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since  1.0.0
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Membership2 Invoices', 'membership2' ),
			'description' => __( 'Member Invoices', 'membership2' ),
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
			self::get_post_type()
		);
	}

	/**
	 * Get invoice status types.
	 *
	 * @since  1.0.0
	 * @param  bool $extended Optional. If true, additional details will be
	 *         returned, not only the status name.
	 * @return array A list of status IDs with status name/description.
	 */
	public static function get_status_types( $extended = false ) {
		if ( $extended ) {
			$result = array(
				self::STATUS_NEW => __( 'Draft - Invoice is prepared but user cannot see it yet', 'membership2' ),
				self::STATUS_BILLED => __( 'Billed - User can see the invoice and needs to pay', 'membership2' ),
				self::STATUS_PENDING => __( 'Pending - Waiting for confirmation from payment gateway', 'membership2' ),
				self::STATUS_PAID => __( 'Paid - Payment arrived on our account!', 'membership2' ),
				self::STATUS_DENIED => __( 'Denied - Payment was denied', 'membership2' ),
			);
		} else {
			$result = array(
				self::STATUS_NEW => __( 'Draft', 'membership2' ),
				self::STATUS_BILLED => __( 'Billed', 'membership2' ),
				self::STATUS_PENDING => __( 'Pending', 'membership2' ),
				self::STATUS_PAID => __( 'Paid', 'membership2' ),
				self::STATUS_DENIED => __( 'Denied', 'membership2' ),
			);
		}

		return apply_filters(
			'ms_model_invoice_get_status_types',
			$result,
			$extended
		);
	}

	/**
	 * Returns the default query-arg array
	 *
	 * @since  1.0.0
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
			$user_args = array(
				'search' => '*' . $_REQUEST['s'] . '*',
			);
			$user_list = new WP_User_Query( $user_args );
			$user_ids = array();
			foreach ( $user_list->results as $user ) {
				$user_ids[] = $user->ID;
			}
			$args['author__in'] = $user_ids;
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
			if ( 'default' === $_REQUEST['status'] ) {
				$args['meta_query']['status'] = array(
					'key' => 'status',
					'value' => array(
						self::STATUS_BILLED,
						self::STATUS_PENDING,
						self::STATUS_PAID,
						self::STATUS_DENIED,
					),
					'compare' => 'IN',
				);
			} elseif ( 'open' === $_REQUEST['status'] ) {
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
	 * Get the number of invoices.
	 *
	 * @since  1.0.0
	 * @param  array $args The query post args
	 *         @see http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int
	 */
	public static function get_invoice_count( $args = null ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
			'post_status' => 'any',
		);
		$args = apply_filters(
			'ms_model_invoice_get_invoice_count_args',
			wp_parse_args( $args, $defaults )
		);

		MS_Factory::select_blog();
		$query = new WP_Query( $args );
		MS_Factory::revert_blog();

		return apply_filters(
			'ms_model_invoice_get_invoice_count',
			$query->found_posts,
			$args
		);
	}

	/**
	 * Count the number of unpaid invoices. Unpaid is any invoice with status
	 * BILLED or PENDING.
	 *
	 * @since  1.0.0
	 * @param  array $args The query post args
	 *         @see http://codex.wordpress.org/Class_Reference/WP_Query
	 * @param  bool $for_badge if true then return value is a string
	 * @return int|string
	 */
	public static function get_unpaid_invoice_count( $args = null, $for_badge = false ) {
		$defaults = self::get_query_args();

		$args = apply_filters(
			'ms_model_invoice_get_unpaid_invoice_count_args',
			wp_parse_args( $args, $defaults )
		);

		$args['meta_query']['status']['value'] = array(
			self::STATUS_BILLED,
			self::STATUS_PENDING,
		);
		$args['meta_query']['status']['compare'] = 'IN';

		$bill_count = self::get_invoice_count( $args );

		$res = $bill_count;
		if ( $for_badge ) {
			if ( $bill_count > 99 ) {
				$res = '99+';
			} elseif ( ! $bill_count ) {
				$res = '';
			}
		}

		return apply_filters(
			'ms_model_invoice_get_unpaid_invoice_count',
			$res,
			$bill_count,
			$args,
			$for_badge
		);
	}

	/**
	 * Get invoices.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $args The arguments to select data.
	 * @return array $invoices
	 */
	public static function get_invoices( $args = null ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
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

		MS_Factory::select_blog();
		$query = new WP_Query( $args );
		$items = $query->posts;
		$invoices = array();
		MS_Factory::revert_blog();

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
	 * Returns all invoices of the specified user that are "public" for the
	 * user. This means that some internal invoices will not be displayed:
	 * - Invoices with 0.00 total amount are not displayed
	 * - Invoices with status New are not displayed
	 *
	 * @since  1.0.0
	 * @param  int $user_id
	 * @param  int $limit
	 * @return array List of MS_Model_Invoice objects.
	 */
	public static function get_public_invoices( $user_id, $limit = -1 ) {
		$list = self::get_invoices(
			array(
				'author' => $user_id,
				'posts_per_page' => $limit,
				'meta_query' => array(
					'relation' => 'AND',
					// Do not display invoices for free memberships.
					array(
						'key' => 'amount',
						'value' => '0',
						'compare' => '!=',
					),
					// Do not display and Invoice with status "New".
					array(
						'key' => 'status',
						'value' => MS_Model_Invoice::STATUS_NEW,
						'compare' => '!=',
					),
				)
			)
		);

		return $list;
	}

	/**
	 * Get specific invoice.
	 *
	 * Get invoice of a user and membership.
	 *
	 * @since  1.0.0
	 *
	 * @param int $subscription_id The membership relationship id.
	 * @param int $invoice_number Optional. The invoice number. Get the current number if null.
	 * @param string $status Optional. The invoice status.
	 * @return MS_Model_Invoice The found invoice or null if not found.
	 */
	public static function get_invoice( $subscription_id, $invoice_number = null, $status = null ) {
		$args = array(
			'post_type' => self::get_post_type(),
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

		MS_Factory::select_blog();
		$args = apply_filters( 'ms_model_invoice_get_invoice_args', $args );
		$query = new WP_Query( $args );
		$item = $query->posts;
		MS_Factory::revert_blog();

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
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Relationship $subscription The membership relationship.
	 * @param  bool $create_missing Optional. True to overwrite existing
	 *         invoice or false to create a new one if doesn't exist.
	 * @return MS_Model_Invoice
	 */
	public static function get_current_invoice( $subscription, $create_missing = true ) {
		$invoice = self::get_invoice(
			$subscription->id,
			$subscription->current_invoice_number
		);

		if ( ! $invoice && $create_missing ) {
			// Create a new invoice.
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
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Relationship $subscription The membership relationship.
	 * @param  bool $create_missing Optional. True to overwrite existing
	 *         invoice or false to create a new one if doesn't exist.
	 * @return MS_Model_Invoice
	 */
	public static function get_next_invoice( $subscription, $create_missing = true ) {
		$invoice = self::get_invoice(
			$subscription->id,
			$subscription->current_invoice_number + 1
		);

		if ( ! $invoice && $create_missing ) {
			// Create a new invoice.
			$invoice = self::create_invoice(
				$subscription,
				$subscription->current_invoice_number + 1
			);
		}

		/*
		 * Since only the *first* invoice can have discount/pro-rating we
		 * manually set those values to 0.
		 */
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
	 * @since  1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 * @param string $status The invoice status to find. Optional
	 * @return MS_Model_Invoice
	 */
	public static function get_previous_invoice( $subscription, $status = null ) {
		$invoice = self::get_invoice(
			$subscription->id,
			$subscription->current_invoice_number - 1,
			$status
		);

		return apply_filters(
			'ms_model_invoice_get_previous_invoice',
			$invoice,
			$subscription,
			$status
		);
	}

	/**
	 * Create invoice.
	 *
	 * Create a new invoice using the membership information.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The membership to create invoice for.
	 * @param int $invoice_number Optional. The invoice number.
	 *
	 * @return object $invoice
	 */
	public static function create_invoice( $subscription, $invoice_number = false ) {
		$membership = $subscription->get_membership();

		if ( ! MS_Model_Membership::is_valid_membership( $membership->id ) ) {
			throw new Exception( 'Invalid Membership.' );
		}

		$invoice = null;
		$member = MS_Factory::load( 'MS_Model_Member', $subscription->user_id );
                
                if( isset( $_SESSION['m2_status_check'] ) && $_SESSION['m2_status_check'] == 'inv' )
                {
                    $invoice_status = self::STATUS_BILLED;
                }
                else
                {
                    $invoice_status = self::STATUS_NEW;
                }
                unset( $_SESSION['m2_status_check'] );
                
		$notes = null;

		if ( empty( $invoice_number ) ) {
			$invoice_number = $subscription->current_invoice_number;
		}

		$invoice = self::get_invoice( $subscription->id, $invoice_number );

		// No existing invoice, create a new one.
		if ( ! $invoice || ! $invoice->id ) {
			$invoice = MS_Factory::create( 'MS_Model_Invoice' );
			$invoice = apply_filters( 'ms_model_invoice', $invoice );
		}

		// Update invoice info.
		$invoice->ms_relationship_id = $subscription->id;
		$invoice->gateway_id = $subscription->gateway_id;
		$invoice->status = $invoice_status;
		$invoice->invoice_date = MS_Helper_Period::current_date();
		$invoice->membership_id = $membership->id;
		$invoice->currency = MS_Plugin::instance()->settings->currency;
		$invoice->user_id = $member->id;
		$invoice->name = apply_filters(
			'ms_model_invoice_name',
			sprintf(
				__( 'Invoice for %s - %s', 'membership2' ),
				$membership->name,
				$member->username
			)
		);
		$invoice->invoice_number = $invoice_number;
		$invoice->discount = 0;
		$invoice->notes = $notes;
		$invoice->amount = $membership->price; // Without taxes!

		// Check for trial period in the first period.
		if ( $subscription->is_trial_eligible()
			&& $invoice_number === $subscription->current_invoice_number
		) {
			$invoice->trial_price = $membership->trial_price; // Without taxes!
			$invoice->uses_trial = true;
			$invoice->trial_ends = $subscription->trial_expire_date;
		}

		$invoice->set_due_date();

		$invoice = apply_filters(
			'ms_model_invoice_create_before_save',
			$invoice,
			$subscription
		);

		$invoice->save();

		// Refresh the tax-rate and payment description.
		$invoice->total_amount_changed();

		$invoice->save();

		return apply_filters(
			'ms_model_relationship_create_invoice',
			$invoice,
			$subscription,
			$invoice_number
		);
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Save model.
	 *
	 * @since  1.0.0
	 */
	public function save() {
		// Validate the pay_date attribute of the invoice.
		$this->validate_pay_date();

		parent::save();
		parent::store_singleton();
	}

	/**
	 * Move an invoice to tha archive - i.e. hide it from the user.
	 *
	 * @since  1.0.2.0
	 */
	public function archive() {
		if ( $this->id ) {
			$this->add_notes( '----------' );
			$this->add_notes(
				sprintf(
					__( 'Archived on: %s', 'membership2' ),
					MS_Helper_Period::current_date()
				)
			);
			$this->add_notes(
				sprintf(
					__( 'Former status: %s', 'membership2' ),
					$this->status
				)
			);

			$this->status = self::STATUS_ARCHIVED;
			$this->save();
		}
	}

	/**
	 * Registers the payment and marks the invoice as paid.
	 *
	 * This should be the only place that sets an invoice status to PAID.
	 *
	 * @since  1.0.0
	 * @param  string $gateway_id The payment gateway.
	 * @param  string $external_id Payment-ID provided by the gateway
	 */
	public function pay_it( $gateway_id = null, $external_id = null ) {
		if ( $gateway_id ) {
			$this->gateway_id = $gateway_id;
		}
		if ( $external_id ) {
			$this->external_id = $external_id;
		}
		$is_paid = false;

		$subscription = $this->get_subscription();

		// Save details on the payment.
		if ( 0 == $this->total || MS_Gateway_Free::ID == $gateway_id ) {
			$is_paid = $subscription->add_payment(
				0,
				MS_Gateway_Free::ID,
				'free'
			);
		} else {
			$is_paid = $subscription->add_payment(
				$this->total,
				$gateway_id,
				$external_id
			);
		}

		if ( $is_paid ) {
			$this->status = self::STATUS_PAID;
			$this->pay_date = MS_Helper_Period::current_date();
		} else {
			$this->status = self::STATUS_BILLED;
		}

		// Manual gateway works differently. This conditon avoids infinite loop.
		if ( MS_Gateway_Manual::ID != $gateway_id ) {
			/*
			 * Process the payment and update the subscription.
			 * This function will call the config_period() function to calculate
			 * the new expire date of the subscription.
			 *
			 * All changes above are also saved at the end of changed()
			 */
			$this->changed();
		}

		/**
		 * Notify Add-ons that an invoice was paid.
		 *
		 * @since  1.0.0
		 */
		do_action( 'ms_invoice_paid', $this, $subscription );
	}

	/**
	 * Returns true if the invoice was paid.
	 *
	 * @since  1.0.0
	 * @return bool Payment status.
	 */
	public function is_paid() {
		return $this->status == self::STATUS_PAID;
	}

	/**
	 * Makes sure that the pay_date attribtue has a valid value.
	 *
	 * @since  1.0.2.0
	 */
	protected function validate_pay_date() {
		if ( $this->is_paid() && $this->amount ) {
			if ( ! $this->pay_date ) {
				$subscription = $this->get_subscription();
				$payments = $subscription->get_payments();
				$last_payment = end( $payments );
				$this->pay_date = $last_payment['date'];
				if ( ! $this->pay_date ) {
					$this->pay_date = $this->due_date;
				}
			}
		} elseif ( $this->pay_date ) {
			$this->pay_date = '';
		}
	}

	/**
	 * Update the subscription details after the invoice has changed.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @since  1.0.0
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

			switch ( $this->status ) {
				case self::STATUS_NEW:
				case self::STATUS_BILLED:
					break;

				case self::STATUS_PAID:
					if ( $this->total > 0 ) {
						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_PAID,
							$subscription
						);
					}

					do_action(
						'ms_model_invoice_changed-paid',
						$this,
						$member
					);

					// Check for moving memberships
					if ( $subscription->move_from_id ) {
						$ids = explode( ',', $subscription->move_from_id );
						foreach ( $ids as $id ) {
							$move_from = MS_Model_Relationship::get_subscription(
								$subscription->user_id,
								$id
							);

							if ( $move_from->is_valid() ) {
								/**
								 * @since 1.0.1.2 The old subscription will be
								 * deactivated instantly, and not cancelled.
								 * When the subscription is cancelled the user
								 * still has full access to the membership
								 * contents. When it is deactivated he cannot
								 * access protected content anymore (instantly).
								 */
								$move_from->deactivate_membership();
							}
						}

						$subscription->cancelled_memberships = $subscription->move_from_id;
						$subscription->move_from_id = '';
					}

					/*
					 * Memberships with those payment types can have multiple
					 * invoices for a single subscription.
					 */
					$multi_invoice = array(
						MS_Model_Membership::PAYMENT_TYPE_RECURRING,
						MS_Model_Membership::PAYMENT_TYPE_FINITE,
					);

					if ( in_array( $membership->payment_type, $multi_invoice ) ) {
						// Update the current_invoice_number counter.
						$subscription->current_invoice_number = max(
							$subscription->current_invoice_number,
							$this->invoice_number + 1
						);
					}

					if ( MS_Gateway_Manual::ID == $this->gateway_id ) {
						$this->pay_it( $this->gateway_id );
					}
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
			$this->save();

			$subscription->set_gateway( $this->gateway_id );
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	private function refresh_amount() {
		// Never change the amount of paid invoices.
		if ( $this->is_paid() ) { return; }

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

		// Re-Calculate the subscription dates
		$this->set_due_date();
	}

	/**
	 * Refreshes the due-date of the invoice.
	 *
	 * @since  1.0.0
	 */
	public function set_due_date() {
		// Never change due-date of paid invoices.
		if ( $this->is_paid() ) { return; }

		$subscription = $this->get_subscription();

		$due_date = false;

		// Handle special cases in due date calculation.
		switch ( $subscription->status ) {
			case MS_Model_Relationship::STATUS_TRIAL:
				$due_date = $subscription->trial_expire_date;
				break;

			case MS_Model_Relationship::STATUS_ACTIVE:
			case MS_Model_Relationship::STATUS_CANCELED:
				$due_date = $subscription->expire_date;
				break;
		}

		// Default due date is today.
		if ( empty( $due_date ) ) {
			if ( $subscription->is_trial_eligible() ) {
				/*
				 * This invoice includes a trial period.
				 * Payment is due on last day of trial
				 */
				$due_date = $subscription->trial_expire_date;
			} else {
				// No trial period is used for this invoice. Due now.
				$due_date = MS_Helper_Period::current_date();
			}
		}

		// Update the trial expiration date.
		$this->trial_ends = $subscription->trial_expire_date;

		$this->due_date = $due_date;
	}

	/**
	 * Get invoice net amount: Amount excluding taxes.
	 *
	 * Discounting coupon and pro-rating.
	 * Add taxes.
	 *
	 * @since  1.0.0
	 */
	private function get_net_amount() {
		if ( ! $this->is_paid() ) {
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
	 * Returns the tax-value in currency (opposed to the percentage value)
	 *
	 * @since  1.0.0
	 * @return float Total tax amount
	 */
	private function get_tax() {
		$tax_rate = $this->tax_rate;

		if ( ! is_numeric( $tax_rate ) ) {
			$tax_rate = 0;
		}

		$value = $this->get_net_amount() * ( $tax_rate / 100 );
		if ( $value < 0 ) {
			$value = 0;
		}

		return $value;
	}

	/**
	 * Returns the tax-value in currency for the trial membership (opposed to
	 * the percentage value)
	 *
	 * @since  1.0.0
	 * @return float Total tax amount (trial membership)
	 */
	private function get_trial_tax() {
		$tax_rate = $this->tax_rate;

		if ( ! is_numeric( $tax_rate ) ) {
			$tax_rate = 0;
		}

		$value = floatval( $this->trial_price ) * ( $tax_rate / 100 );
		if ( $value < 0 ) {
			$value = 0;
		}

		return $value;
	}

	/**
	 * Get invoice total.
	 *
	 * Discounting coupon and pro-rating.
	 * Add taxes.
	 *
	 * @since  1.0.0
	 */
	private function get_total() {
		$total = $this->get_net_amount(); // Net amount
		$total += $this->get_tax(); // Tax-Rate was defined in `create_invoice()`

		if ( $total < 0 ) {
			$total = 0;
		}

		// Set precission to 2 decimal points.
		$total = round( $total, 2 );

		$this->total = apply_filters(
			'ms_model_invoice_get_total',
			$total,
			$this
		);

		return $this->total;
	}

	/**
	 * Get invoice trial price.
	 *
	 * @since  1.0.0
	 */
	private function get_trial_price() {
		$membership = $this->get_membership();
		$trial_price = $membership->trial_price; // Net amount
		$trial_price += $this->get_trial_tax(); // Tax-Rate was defined in `create_invoice()`

		if ( $trial_price < 0 ) {
			$trial_price = 0;
		}

		// Set precission to 2 decimal points.
		$trial_price = round( $trial_price, 2 );

		$this->trial_price = apply_filters(
			'ms_model_invoice_get_trial_price',
			$trial_price,
			$this
		);

		return $this->trial_price;
	}

	/**
	 * Returns the public invoice number for this invoice.
	 * The public invoice number is the official identifier that is displayed
	 * to the end user that refers to an invoice
	 *
	 * @since  1.0.0
	 * @return string The public invoice number.
	 */
	public function get_invoice_number() {
		$identifier = '#' . $this->id . '-' . $this->invoice_number;

		return apply_filters(
			'ms_model_invoice_the_number',
			$identifier,
			$this
		);
	}

	/**
	 * Returns the membership model that is linked to this invoice.
	 *
	 * @since  1.0.0
	 * @return MS_Model_Membership
	 */
	public function get_membership() {
		return MS_Factory::load( 'MS_Model_Membership', $this->membership_id );
	}

	/**
	 * Returns the membership model that is linked to this invoice.
	 *
	 * @since  1.0.0
	 * @return MS_Model_Membership
	 */
	public function get_member() {
		return MS_Factory::load( 'MS_Model_Member', $this->user_id );
	}

	/**
	 * Returns the subscription model that is linked to this invoice.
	 *
	 * @since  1.0.0
	 * @return MS_Model_Relationship
	 */
	public function get_subscription() {
		return MS_Factory::load( 'MS_Model_Relationship', $this->ms_relationship_id );
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since  1.0.0
	 * @internal
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {
			case 'total':
				$value = $this->get_total();
				break;

			case 'trial_price':
				$value = $this->get_trial_price();
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

			case 'invoice_date':
				$value = $this->invoice_date;

				if ( empty( $value ) ) {
					$value = get_the_date( 'Y-m-d', $this->id );
				}
				break;

			case 'pay_date':
				$this->validate_pay_date();
				$value = $this->pay_date;
				break;

			case 'tax':
				$value = $this->get_tax();
				break;

			case 'trial_tax':
				$value = $this->get_trial_tax();
				break;

			case 'subtotal':
				$value = $this->get_net_amount();
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
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
	 * @since  1.0.0
	 * @internal
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
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
			case 'trial_price':
				$this->$property = floatval( $value );
				$this->total_amount_changed();
				$this->get_total();
				$this->get_trial_price();
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$this->$property = $value;
				}
				break;
		}

		do_action(
			'ms_model_invoice__set_after',
			$property,
			$value,
			$this
		);
	}

}