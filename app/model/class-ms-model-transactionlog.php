<?php
/**
 * Transaction Log Model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.1.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Transactionlog extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since  1.0.1.0
	 *
	 * @var string
	 */
	protected static $POST_TYPE = 'ms_transaction_log';

	/**
	 * Timestamp of the transaction.
	 *
	 * @since 1.0.1.
	 * @var   string
	 */
	protected $date = '';

	/**
	 * The gateway that made the call.
	 *
	 * @since 1.0.1.
	 * @var   string
	 */
	protected $gateway_id = '';

	/**
	 * The transaction method.
	 *
	 * Possible methods are:
	 *        "handle": IPN response
	 *        "process": Process order (i.e. user comes from Payment screen)
	 *        "request": Automatically request recurring payment
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	protected $method = '';

	/**
	 * Indicator if the transaction was successfully processed by M2
	 *
	 * True means that an invoice was marked paid.
	 * False indicates an error or unknown input.
	 * NULL indicates a message that was processed but irrelevant.
	 *
	 * @since 1.0.1.0
	 * @var   bool|null
	 */
	protected $success = null;

	/**
	 * Transaction state, similar to $success but as string.
	 *
	 * 'ok' means that an invoice was marked paid.
	 * 'err' indicates an error or unknown input.
	 * 'ignore' indicates a message that was processed but irrelevant.
	 *
	 * Note: This is the state of the original transaction, not the state that
	 * is displayed to the user. {@see $manual_state}
	 *
	 * @since 1.0.1.0
	 * @var   string $state Access via $item->state (not $item->_state!)
	 */
	protected $_state = null;

	/**
	 * The subscription linked with the transaction.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $subscription_id = 0;

	/**
	 * The invoice linked with the transaction.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $invoice_id = 0;

	/**
	 * The member associated with the transaction.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $member_id = 0;

	/**
	 * The transaction amount reported by the gateway.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $amount = 0;

	/**
	 * The URL used to report the transaction.
	 *
	 * This is especially relevant for IPN messages (method "handle")
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	protected $url = '';

	/**
	 * A collection of all POST parameters passed to the $url.
	 *
	 * @since 1.0.1.0
	 * @var   array
	 */
	protected $post = null;

	/**
	 * A collection of all HTTP headers passed to the $url.
	 *
	 * @since 1.0.1.2
	 * @var   array
	 */
	protected $headers = null;

	/**
	 * The manually overwritten state value.
	 *
	 * If this is empty then $state is the effective state value, otherwise this
	 * flag indicates the state that is displayed to the user.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	protected $manual_state = '';

	/**
	 * Timestamp of setting the $manual_state value.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	protected $manual_date = '';

	/**
	 * User who changed the $manual_state value.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $manual_user = 0;


	//
	//
	//
	// -------------------------------------------------------------- COLLECTION


	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  1.0.1.0
	 * @return string The post-type name.
	 */
	public static function get_post_type() {
		return parent::_post_type( self::$POST_TYPE );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since  1.0.1.0
	 * @return array Post Type details.
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Membership2 Transaction Logs', MS_TEXT_DOMAIN ),
			'supports'            => array(),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::get_post_type()
		);
	}

	/**
	 * Get the total number of log entries.
	 * For list table pagination.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  array $args The default query args.
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total count.
	 */
	public static function get_item_count( $args = null ) {
		$args = lib2()->array->get( $args );
		$args['posts_per_page'] = -1;
		$items = self::get_items( $args );

		$count = count( $items );

		return apply_filters(
			'ms_model_transactionlog_get_item_count',
			$count,
			$args
		);
	}

	/**
	 * Get transaction log items.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  $args The query post args.
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array List of transaction log items.
	 */
	public static function get_items( $args = null ) {
		MS_Factory::select_blog();
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );
		MS_Factory::revert_blog();

		$items = array();

		foreach ( $query->posts as $post_id ) {
			if ( ! get_post_meta( $post_id, 'method', true ) ) {
				// The log entry is incomplete. Do not load it.
				continue;
			}

			$items[] = MS_Factory::load( 'MS_Model_Transactionlog', $post_id );
		}

		return apply_filters(
			'ms_model_transactionlog_get_items',
			$items,
			$args
		);
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since  1.0.1.0
	 *
	 * @param $args The query post args
	 *        @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
			'post_status' => 'any',
			'fields' => 'ids',
			'order' => 'DESC',
			'orderby' => 'ID',
			'posts_per_page' => 20,
		);

		if ( ! empty( $args['state'] ) ) {
			$ids = self::get_state_ids( $args['state'] );
			if ( ! empty( $args['post__in'] ) ) {
				$ids = array_intersect( $args['post__in'], $ids );
			}

			if ( $ids ) {
				$args['post__in'] = $ids;
			} else {
				$args['post__in'] = array( 0 );
			}
		}

		if ( ! empty( $args['source'] ) ) {
			$ids = self::get_matched_ids( $args['source'][0], $args['source'][1] );
			if ( ! empty( $args['post__in'] ) ) {
				$ids = array_intersect( $args['post__in'], $ids );
			}

			if ( $ids ) {
				$args['post__in'] = $ids;
			} else {
				$args['post__in'] = array( 0 );
			}
		}

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_transactionlog_get_item_args',
			$args
		);
	}

	/**
	 * Returns a list of post_ids that have the specified Transaction State.
	 *
	 * @since  1.0.1.0
	 * @param  string $state A valid transaction state [err|ok|ignore].
	 * @return array List of post_ids.
	 */
	static public function get_state_ids( $state ) {
		global $wpdb;

		$sql = "
		SELECT p.ID
		FROM
			{$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} state1 ON
				state1.post_id = p.ID AND state1.meta_key = 'success'
			LEFT JOIN {$wpdb->postmeta} state2 ON
				state2.post_id = p.ID AND state2.meta_key = 'manual_state'
			INNER JOIN {$wpdb->postmeta} method ON
				method.post_id = p.ID AND method.meta_key = 'method'
		WHERE
			p.post_type = %s
			AND LENGTH( method.meta_value ) > 0
		";

		switch ( $state ) {
			case 'err':
				$sql .= "
				AND (state1.meta_value IS NULL OR state1.meta_value IN ('','0','err'))
				AND (state2.meta_value IS NULL OR state2.meta_value IN (''))
				";
				break;

			case 'ok':
				$sql .= "
				AND (
					state1.meta_value IN ('1','ok')
					OR state2.meta_value IN ('1','ok')
				)
				";
				break;

			case 'ignore':
				$sql .= "
				AND (
					state1.meta_value IN ('ignore')
					OR state2.meta_value IN ('ignore')
				)
				";
				break;
		}

		$sql = $wpdb->prepare( $sql, self::get_post_type() );
		$ids = $wpdb->get_col( $sql );

		if ( ! count( $ids ) ) {
			$ids = array( 0 );
		}

		return $ids;
	}

	/**
	 * Returns a list of post_ids that have the specified source_id.
	 *
	 * This tries to find transactions for imported subscriptions.
	 *
	 * @since  1.0.1.2
	 * @param  string $source_id Subscription ID before import; i.e. original ID.
	 * @param  string $source The import source. Currently supported: 'm1'.
	 * @return array List of post_ids.
	 */
	static public function get_matched_ids( $source_id, $source ) {
		global $wpdb;

		$sql = "
		SELECT p.ID
		FROM
			{$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} form ON
				form.post_id = p.ID AND form.meta_key = 'post'
			LEFT JOIN {$wpdb->postmeta} gateway ON
				gateway.post_id = p.ID AND gateway.meta_key = 'gateway_id'
		WHERE
			p.post_type = %s
		";

		$source_int = intval( $source_id );
		$int_len = strlen( $source_int );

		switch ( $source ) {
			case 'm1':
				$sql .= "
				AND gateway.meta_value = 'paypalstandard'
				AND form.meta_value LIKE '%%s:6:\"custom\";s:%%'
				AND form.meta_value LIKE '%%:{$source_int}:%%'
				";
				break;

			case 'pay_btn':
				$sql .= "
				AND gateway.meta_value = 'paypalstandard'
				AND form.meta_value LIKE '%%s:6:\"btn_id\";s:{$int_len}:\"{$source_int}\";%%'
				AND form.meta_value LIKE '%%s:11:\"payer_email\";%%'
				";
				break;
		}

		$sql = $wpdb->prepare( $sql, self::get_post_type() );
		$ids = $wpdb->get_col( $sql );

		if ( ! count( $ids ) ) {
			$ids = array( 0 );
		}

		return $ids;
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Initializes variables right before saving the model.
	 *
	 * @since 1.0.1.0
	 */
	public function before_save() {
		// Translate a boolean success value to a string.
		if ( true === $this->success ) {
			$this->success = 'ok';
		} elseif ( null === $this->success ) {
			$this->success = 'ignore';
		} elseif ( false === $this->success ) {
			$this->success = 'err';
		}

		$this->url = lib2()->net->current_url();
		$this->post = $_POST;
		$this->headers = $this->get_headers();
		$this->user_id = get_current_user_id();
		$this->title = 'Transaction Log';
	}

	/**
	 * Prepares an object after it was loaded from database.
	 *
	 * @since  1.0.1.0
	 */
	public function prepare_obj() {
		$this->set_state( $this->success );

		if ( ! $this->member_id ) {
			if ( $this->invoice_id ) {
				$invoice = MS_Factory::load( 'MS_Model_Invoice', $this->invoice_id );
				$this->member_id = $invoice->user_id;
			} elseif ( MS_Gateway_Paypalstandard::ID == $this->gateway ) {
				/*
				 * Migration logic for M1 IPN messages:
				 * M1 did use the "custom" field to link the transaction to a
				 * subscription. The custom field contains these details:
				 * timestamp : user_id : subscription_id : key
				 */
				if ( is_array( $this->post ) && ! empty( $this->post['custom'] ) ) {
					$infos = explode( ':', $this->post['custom'] );
					if ( count( $infos ) > 2 && is_numeric( $infos[1] ) ) {
						$this->member_id = intval( $infos[1] );
					}
				}
			}
		}
	}

	/**
	 * Populate custom fields from the wp_posts table.
	 *
	 * @since  1.0.1.0
	 * @param  WP_Post $post The post object.
	 */
	public function load_post_data( $post ) {
		$this->date = $post->post_date;
	}

	/**
	 * Returns a list of all HTTP headers.
	 *
	 * @since  1.0.1.2
	 * @return array List of all incoming HTTP headers.
	 */
	protected function get_headers() {
		$headers = array();

		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
		} else {
			foreach ( $_SERVER as $key => $value ) {
				if ( 'HTTP_' == substr( $key, 0, 5 ) ) {
					$key = str_replace( '_', ' ', substr( $key, 5 ) );
					$key = str_replace( ' ', '-', ucwords( strtolower( $key ) ) );
					$headers[ $key ] = $value;
				}
			}
		}

		return $headers;
	}

	/**
	 * Sets the manual-state value of the transaction log entry.
	 *
	 * @since  1.0.0
	 * @param  string $state The new state of the item.
	 * @return bool True on success.
	 */
	public function manual_state( $state ) {
		if ( 'err' != $this->_state ) {
			// Not allowed: Only error state can be manually corrected.
			return false;
		}

		switch ( $state ) {
			case 'ignore':
				if ( $this->manual_state ) {
					// Not allowed: Manual state was defined already.
					return false;
				}
				break;

			case 'clear':
				if ( 'ignore' != $this->manual_state ) {
					// Not allowed: Only "ingored" state can be cleared.
					return false;
				}
				break;

			case 'ok':
				if ( $this->manual ) {
					// Not allowed: Manual state is already defined.
					return false;
				}
				if ( ! $this->invoice_id || ! $this->subscription_id ) {
					// Not allowed: Required data is missing for OK status.
					return false;
				}
				break;

			default:
				// Not allowed: Unknown state.
				return false;
		}

		if ( 'clear' == $state ) {
			$this->manual_state = '';
			$this->manual_date = '';
			$this->manual_user = 0;
		} else {
			$this->manual_state = $state;
			$this->manual_date = MS_Helper_Period::current_time();
			$this->manual_user = get_current_user_id();
		}
		return true;
	}

	/**
	 * Returns the WP_User object for the manual user.
	 *
	 * @since  1.0.1.0
	 * @return WP_User
	 */
	public function get_manual_user() {
		$user = new WP_User( $this->manual_user );
		return $user;
	}

	/**
	 * Returns the Member object associated with this transaction.
	 *
	 * @since  1.0.1.0
	 * @return MS_Model_Member
	 */
	public function get_member() {
		return MS_Factory::load( 'MS_Model_Member', $this->member_id );
	}

	/**
	 * Returns the Invoice model linked with the transaction.
	 *
	 * @since  1.0.1.0
	 * @return bool|MS_Model_Invoice
	 */
	public function get_invoice() {
		$result = false;

		if ( $this->invoice_id ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $this->invoice_id );
			if ( $invoice->id == $this->invoice_id ) {
				$result = $invoice;
			}
		}

		return $result;
	}

	/**
	 * Updates the subscription_id and member_id based on the specified ID.
	 *
	 * @since 1.0.1.0
	 * @param int $id A valid subscription ID.
	 */
	protected function set_subscription( $id ) {
		$subscription = MS_Factory::load( 'MS_Model_Relationship', $id );
		$this->subscription_id = $subscription->id;
		$this->member_id = $subscription->user_id;
	}

	/**
	 * Updates the invoice_id, subscription_id and member_id based on the
	 * specified ID.
	 *
	 * @since 1.0.1.0
	 * @param int $id A valid invoice ID.
	 */
	protected function set_invoice( $id ) {
		$invoice = MS_Factory::load( 'MS_Model_Invoice', $id );
		$this->invoice_id = $invoice->id;
		$this->subscription_id = $invoice->ms_relationship_id;
		$this->member_id = $invoice->user_id;
	}

	/**
	 * Updates the state and success properties of the object.
	 *
	 * @since 1.0.1.0
	 * @param mixed $value The new state value
	 */
	protected function set_state( $value ) {
		switch ( $value ) {
			case 'ok':
				$this->_state = $value;
				$this->success = true;
				break;

			case 'ignore':
				$this->_state = $value;
				$this->success = null;
				break;

			case 'err':
				$this->_state = $value;
				$this->success = false;
				break;

			case true:
				$this->_state = 'ok';
				$this->success = $value;
				break;

			case false:
				$this->_state = 'err';
				$this->success = $value;
				break;

			case null:
				$this->_state = 'ignore';
				$this->success = 'ignore';
				break;

			default:
				// Unrecognized values are not saved.
		}
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {
			case 'state':
				if ( $this->manual_state ) {
					$value = $this->manual_state;
				} else {
					$value = $this->_state;
				}
				break;

			case 'is_manual':
				$value = ! ! $this->manual_state;
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_model_transactionlog__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Set specific property.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		switch ( $property ) {
			case 'state':
			case 'success':
				$this->set_state( $value );
				break;

			case 'invoice_id':
				$this->set_invoice( $value );
				break;

			case 'subscription_id':
				$this->set_subscription( $value );
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$this->$property = $value;
				}
				break;
		}

		do_action(
			'ms_model_transactionlog__set_after',
			$property,
			$value,
			$this
		);
	}
}