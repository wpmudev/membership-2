<?php
/**
 * Base class for all import handlers.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Import extends MS_Model {

	/**
	 * The sanitized import source object. The value of this property is set by
	 * the prepare() function.
	 *
	 * This is used to render the Import-Preview view.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	public $source = array();

	/**
	 * Holds a list of all errors that happen during import.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * The data source name.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	public $source_key = '';

	/**
	 * This function parses the Import source (e.g. an file-upload or settings
	 * of another plugin) and returns true in case the source data is valid.
	 * When returning true then the  $source property of the model is set to
	 * the sanitized import source data.
	 *
	 * Logic has to be implemented by child classes.
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	public function prepare() {
		throw new Exception( 'Method to be implemented in child class' );
	}

	/**
	 * Returns true if the specific import-source is present and can be used
	 * for import.
	 *
	 * Must be implemented by the child classes.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function present() {
		return false;
	}

	/**
	 * Checks if the provided data is a recognized import object.
	 * If not an import object then FALSE will be returned, otherwise the
	 * object itself.
	 *
	 * @since  1.0.0
	 * @param  object $data Import object to test.
	 * @return object|false
	 */
	protected function validate_object( $data ) {
		$data = apply_filters( 'ms_import_validate_object_before', $data );

		if ( empty( $data )
			|| ! is_object( $data )
			|| ! isset( $data->source_key )
			|| ! isset( $data->source )
			|| ! isset( $data->plugin_version )
			|| ! isset( $data->export_time )
			|| ! isset( $data->notes )
			|| ! isset( $data->memberships )
			|| ! isset( $data->members )
			|| ! isset( $data->settings )
		) {
			return false;
		} else {
			return apply_filters( 'ms_import_validate_object', $data );
		}
	}

	/**
	 * The first action of the import process. This should prepare the site for
	 * a new import.
	 *
	 * @since  1.0.0
	 * @param  bool $clear If true then existing memberships will be deleted.
	 */
	public function start( $clear ) {
		$this->clear_import_obj_cache();

		if ( $clear ) {
			$this->clear_memberships();
		}

		// Remember this import.
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$settings->import[ $this->source_key ] = date( 'Y-m-d H:i' );
		$settings->save();
	}

	/**
	 * The last action of the import process, responsible to clean up temp data.
	 *
	 * @since  1.0.0
	 */
	public function done() {
		$this->clear_import_obj_cache();
	}

	/**
	 * Returns the import cache object.
	 *
	 * @since  1.0.0
	 * @param  string $req_type The object type name that is requested.
	 * @return array The full import object cache.
	 */
	private function get_import_obj_cache( $req_type ) {
		$cache = get_option( 'MS_Import_Obj_Cache', false );
		$cache = lib3()->array->get( $cache );
		if ( ! isset( $cache[$req_type] ) ) { $cache[$req_type] = array(); }

		return $cache;
	}

	/**
	 * Stores the import cache object.
	 *
	 * @since  1.0.0
	 * @param  array The full import object cache.
	 */
	private function set_import_obj_cache( $cache ) {
		update_option( 'MS_Import_Obj_Cache', $cache );
	}

	/**
	 * Deletes the temporary import cache object.
	 *
	 * @since  1.0.0
	 */
	private function clear_import_obj_cache() {
		delete_option( 'MS_Import_Obj_Cache' );
	}

	/**
	 * Stores data about an imported object.
	 *
	 * This is a temporary map of all objects created during import and
	 * associates the real object ID with an import ID to recognize them again.
	 *
	 * @since  1.0.0
	 * @param  string $type Object type ('membership', ...)
	 * @param  string $import_id Import-ID
	 * @param  any $obj The imported object
	 */
	protected function store_import_obj( $type, $import_id, $obj ) {
		$cache = $this->get_import_obj_cache( $type );

		/*
		 * We store class-name and obj-ID in the array.
		 * The object ID will be different from the import_id!
		 */
		$cache[$type][$import_id] = array(
			'class' => get_class( $obj ),
			'id' => $obj->id,
		);

		$this->set_import_obj_cache( $cache );
	}

	/**
	 * Returns an object previously defined by store_import_obj().
	 *
	 * This is a temporary map of all objects created during import and
	 * associates the real object ID with an import ID to recognize them again.
	 *
	 * @since  1.0.0
	 * @param  string $type Object type ('membership', ...)
	 * @param  string $import_id Import-ID
	 * @return MS_Model The requested object
	 */
	protected function get_import_obj( $type, $import_id ) {
		$cache = $this->get_import_obj_cache( $type );

		$obj = null;
		if ( isset( $cache[$type][$import_id] ) ) {
			$info = $cache[$type][$import_id];
			$obj = MS_Factory::load( $info['class'], $info['id'] );
		}
		return $obj;
	}

	/**
	 * Removes all subscriptions and memberships from the current site.
	 * This is done before the import if the "Replace existing data" flag is set.
	 *
	 * @since  1.0.0
	 */
	protected function clear_memberships() {
		// Delete all Relationships.
		$subscriptions = MS_Model_Relationship::get_subscriptions(
			array( 'status' => 'all' )
		);
		foreach ( $subscriptions as $subscription ) {
			$subscription->delete();
		}

		// Delete all Memberships.
		$memberships = MS_Model_Membership::get_memberships();
		foreach ( $memberships as $membership ) {
			if ( $membership->is_base() ) { continue; }
			$membership->delete( true );
		}
	}

	/**
	 * Import specific data: A single membership
	 *
	 * @since  1.0.0
	 * @param  object $obj The import object
	 */
	public function import_membership( $obj ) {
		$membership = MS_Factory::create( 'MS_Model_Membership' );
		$this->populate_membership( $membership, $obj );
		$membership->save();

		$this->store_import_obj( 'membership', $obj->id, $membership );
	}

	/**
	 * Makes sure the specified period-type is a recognized value.
	 *
	 * @since  1.0.0
	 * @param  string $period_type An unvalidated period string
	 * @return string A valid period-type string
	 */
	protected function valid_period( $period_type ) {
		$res = 'days';

		if ( strlen( $period_type ) > 0 ) {
			switch ( $period_type[0] ) {
				case 'd': $res = 'days'; break;
				case 'w': $res = 'weeks'; break;
				case 'm': $res = 'months'; break;
				case 'y': $res = 'years'; break;
			}
		}

		return $res;
	}

	/**
	 * Helper function used by import_membership
	 * This is a separate function because it is used to populate normal
	 * memberships and also child memberships
	 *
	 * @since  1.0.0
	 */
	protected function populate_membership( &$membership, $obj ) {
		$membership->name = $obj->name;
		$membership->description = $obj->description;
		$membership->active = (bool) $obj->active;
		$membership->private = (bool) $obj->private;
		$membership->is_free = (bool) lib3()->is_true( $obj->free );
		$membership->is_setup_complete = true;

		if ( isset( $obj->period_type ) ) {
			$obj->period_type = $this->valid_period( $obj->period_type );
		}
		if ( isset( $obj->trial_period_type ) ) {
			$obj->trial_period_type = $this->valid_period( $obj->trial_period_type );
		}

		if ( empty( $obj->payment_type ) ) {
			$obj->payment_type = 'permanent';
		}

		$membership->period = array();
		$membership->pay_cycle_period = array();

		switch ( $obj->payment_type ) {
			case 'finite':
				$membership->payment_type = MS_Model_Membership::PAYMENT_TYPE_FINITE;
				if ( isset( $obj->period_unit ) ) {
					$membership->period_unit = $obj->period_unit;
				}
				if ( isset( $obj->period_type ) ) {
					$membership->period_type = $obj->period_type;
				}
				break;

			case 'recurring':
				$membership->payment_type = MS_Model_Membership::PAYMENT_TYPE_RECURRING;
				if ( isset( $obj->period_unit ) ) {
					$membership->pay_cycle_period_unit = $obj->period_unit;
				}
				if ( isset( $obj->period_type ) ) {
					$membership->pay_cycle_period_type = $obj->period_type;
				}
				if ( isset( $obj->period_repetitions ) ) {
					$membership->pay_cycle_repetitions = $obj->period_repetitions;
				}
				break;

			case 'date':
				$membership->payment_type = MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE;
				if ( isset( $obj->period_start ) ) {
					$membership->period_date_start = $obj->period_start;
				}
				if ( isset( $obj->period_end ) ) {
					$membership->period_date_end = $obj->period_end;
				}
				break;

			default:
				$membership->payment_type = MS_Model_Membership::PAYMENT_TYPE_PERMANENT;
				break;
		}

		if ( ! $membership->is_free ) {
			if ( isset( $obj->price ) ) {
				$membership->price = $obj->price;
			}
		}

		if ( isset( $obj->trial ) ) {
			$membership->trial_period_enabled = (bool) $obj->trial;
		}

		if ( $membership->trial_period_enabled ) {
			$membership->trial_period = array();
			if ( isset( $obj->trial_price ) ) {
				$membership->trial_price = $obj->trial_price;
			}
			if ( isset( $obj->trial_period_unit ) ) {
				$membership->trial_period['period_unit'] = $obj->trial_period_unit;
			}
			if ( isset( $obj->trial_period_type ) ) {
				$membership->trial_period['period_type'] = $obj->trial_period_type;
			}
		}

		// Remember where this membership comes from.
		$membership->source = $this->source_key;
		$matching = array( 'm1' => array( $obj->id ) );
		$membership->set_custom_data( 'matching', $matching );

		// We set this last because it might change some other values as well...
		$membership->type = $obj->type;
	}

	/**
	 * Import specific data: A single member
	 *
	 * @since  1.0.0
	 * @param  object $obj The import object
	 */
	public function import_member( $obj ) {
		$wpuser = get_user_by( 'email', $obj->email );
		lib3()->array->equip( $obj, 'username', 'email', 'payment', 'subscriptions' );

		if ( $wpuser ) {
			$member = MS_Factory::load( 'MS_Model_Member', $wpuser->ID );
		} else {
			$wpuser = wp_create_user( $obj->username, '', $obj->email );
			if ( is_numeric( $wpuser ) ) {
				$member = MS_Factory::load( 'MS_Model_Member', $wpuser );
			} else {
				$this->errors[] = sprintf(
					__( 'Could not import Member <strong>%1$s</strong> (%2$s)', 'membership2' ),
					esc_attr( $obj->username ),
					esc_attr( $obj->email )
				);

				// We could not find/create the user, so don't import this item.
				return;
			}
		}

		// Import the member details.
		$member->is_member = true;

		$pay = $obj->payment;
		if ( is_array( $pay ) ) { $pay = (object) $pay; }
		elseif ( ! is_object( $pay ) ) { $pay = (object) array(); }

		lib3()->array->equip(
			$pay,
			'stripe_card_exp',
			'stripe_card_num',
			'stripe_customer',
			'authorize_card_exp',
			'authorize_card_num',
			'authorize_cim_profile',
			'authorize_cim_payment_profile'
		);

		// Stripe.
		$gw_stripe = MS_Gateway_Stripe::ID;
		$member->set_gateway_profile( $gw_stripe, 'card_exp', $pay->stripe_card_exp );
		$member->set_gateway_profile( $gw_stripe, 'card_num', $pay->stripe_card_num );
		$member->set_gateway_profile( $gw_stripe, 'customer_id', $pay->stripe_customer );

		// Authorize.
		$gw_auth = MS_Gateway_Authorize::ID;
		$member->set_gateway_profile( $gw_auth, 'card_exp', $pay->authorize_card_exp );
		$member->set_gateway_profile( $gw_auth, 'card_num', $pay->authorize_card_num );
		$member->set_gateway_profile( $gw_auth, 'cim_profile_id', $pay->authorize_cim_profile );
		$member->set_gateway_profile( $gw_auth, 'cim_payment_profile_id', $pay->authorize_cim_payment_profile );

		$member->save();

		// Import all memberships of the member
		foreach ( $obj->subscriptions as $subscription ) {
			$subscription = (object) $subscription;
			$this->import_subscription( $member, $subscription );
		}
	}

	/**
	 * Import specific data: A single subscription (= relationship)
	 *
	 * @since  1.0.0
	 * @param  object $obj The import object
	 */
	protected function import_subscription( $member, $obj ) {
		$membership = $this->get_import_obj( 'membership', $obj->membership );

		if ( empty( $membership ) ) {
			$this->errors[] = sprintf(
				__( 'Could not import a Membership for User <strong>%1$s</strong> (%2$s)', 'membership2' ),
				esc_attr( $obj->username ),
				esc_attr( $obj->email )
			);
			return;
		}

		if ( $membership->is_base() ) {
			$this->errors[] = sprintf(
				__( 'Did not import the base membership %2$s for <strong>%1$s</strong>', 'membership2' ),
				esc_attr( $obj->username ),
				esc_attr( $membership->name )
			);
			return;
		}

		$subscription = $member->add_membership( $membership->id );
		$subscription->status = $obj->status;
		$subscription->gateway_id = $obj->gateway;
		$subscription->start_date = $obj->start;
		$subscription->expire_date = $obj->end;

		if ( isset( $obj->trial_finished ) ) {
			$subscription->trial_period_completed = $obj->trial_finished;
		}
		if ( isset( $obj->trial_end ) ) {
			$subscription->trial_expire_date = $obj->trial_end;
		}

		// Remember where this subscription comes from.
		$subscription->source = $this->source_key;
		$subscription->save();

		$is_paid = false;

		// Import invoices for this subscription
		if ( ! empty( $obj->invoices ) && is_array( $obj->invoices ) ) {
			foreach ( $obj->invoices as $invoice ) {
				$invoice = (object) $invoice;
				$this->import_invoice( $subscription, $invoice );
				$is_paid = true;
			}
		}

		// Add a payment for active subscriptions.
		if ( ! $is_paid && MS_Model_Relationship::STATUS_ACTIVE == $subscription->status ) {
			$subscription->add_payment(
				$membership->price,
				'admin',
				'imported'
			);
		}
	}

	/**
	 * Import specific data: A single invoice
	 *
	 * @since  1.0.0
	 * @param  object $obj The import object
	 */
	protected function import_invoice( $subscription, $obj ) {
		$ms_invoice = MS_Model_Invoice::create_invoice( $subscription );
		$ms_invoice->invoice_number = $obj->invoice_number;
		$ms_invoice->external_id = $obj->external_id;
		$ms_invoice->gateway_id = $obj->gateway;
		$ms_invoice->status = $obj->status;
		$ms_invoice->coupon_id = $obj->coupon;
		$ms_invoice->currency = $obj->currency;
		$ms_invoice->amount = $obj->amount;
		$ms_invoice->discount = $obj->discount;
		$ms_invoice->pro_rate = $obj->discount2;
		$ms_invoice->total = $obj->total;
		$ms_invoice->trial_period = $obj->for_trial;
		$ms_invoice->due_date = $obj->due;
		$ms_invoice->notes = $obj->notes;

		// Remember where this invoice comes from.
		$ms_invoice->source = $this->source_key;
		$ms_invoice->save();

		$subscription->add_payment(
			$ms_invoice->amount,
			$ms_invoice->gateway_id,
			'imported-' . $ms_invoice->id
		);
	}

	/**
	 * Import specific data: A single setting
	 *
	 * @since  1.0.0
	 * @param  object $obj The import object
	 */
	public function import_setting( $setting, $value ) {
		switch ( $setting ) {
			// Import Add-On states.
			case 'addons':
				$model = MS_Factory::load( 'MS_Model_Addon' );
				foreach ( $value as $addon => $state ) {
					if ( $state ) {
						$model->enable( $addon );
					} else {
						$model->disable( $addon );
					}
				}
				break;
		}
	}

	/**
	 * -------------------------------------------------------------------------
	 * ACCESS IMPORTED DATA
	 */

	/**
	 * Checks if the specified source/ID need matching.
	 *
	 * If the source or source_id is empty then the return value TRUE means that
	 * there is *any* transaction that needs matching.
	 *
	 * See MS_Helper_Listtable_TransactionMatching for a list of sources.
	 *
	 * @since  1.0.1.2
	 * @param  int $source_id The M1 sub_id.
	 * @param  string $source The import source.
	 * @return bool True if the transaction details need matching.
	 */
	static public function can_match( $source_id = null, $source = null ) {
		$res = false;
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		if ( empty( $source_id ) || empty( $source ) ) {
			$src = $settings->get_custom_setting( 'import_match' );
			$src = lib3()->array->get( $src );

			foreach ( $src as $lst ) {
				if ( is_array( $lst ) ) {
					if ( count( $lst ) ) {
						$res = true;
						break;
					}
				}
			}
		} else {
			$lst = $settings->get_custom_setting( 'import_match', $source );

			if ( ! is_array( $lst ) ) {
				$lst = array();
			}

			$res = in_array( $source_id, $lst );
		}

		return $res;
	}

	/**
	 * Remembers that the source_id needs to be matched with a membership_id to
	 * complete the connected transaction.
	 *
	 * See MS_Helper_Listtable_TransactionMatching for a list of sources.
	 *
	 * @since  1.0.1.2
	 * @param  int $source_id The M1 sub_id.
	 * @param  string $source The import source.
	 */
	static public function need_matching( $source_id, $source ) {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		$lst = $settings->get_custom_setting( 'import_match', $source );

		if ( ! is_array( $lst ) ) {
			$lst = array();
		}

		if ( ! in_array( $source_id, $lst ) ) {
			$lst[] = $source_id;
		}

		$settings->set_custom_setting( 'import_match', $source, $lst );
		$settings->save();
	}

	/**
	 * Remove the source_id from the missing-matching-list again.
	 *
	 * See MS_Helper_Listtable_TransactionMatching for a list of sources.
	 *
	 * @since  1.0.1.2
	 * @param  int $source_id The M1 sub_id.
	 * @param  string $source The import source.
	 */
	static public function dont_need_matching( $source_id, $source ) {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		$lst = $settings->get_custom_setting( 'import_match', $source );

		if ( ! is_array( $lst ) ) {
			$lst = array();
		}

		foreach ( $lst as $key => $id ) {
			if ( $id == $source_id ) {
				unset( $lst[$key] );
			}
		}

		$settings->set_custom_setting( 'import_match', $source, $lst );
		$settings->save();
	}

	/**
	 * Save a permanent matching between the specified membership and the
	 * transaction source.
	 *
	 * See MS_Helper_Listtable_TransactionMatching for a list of sources.
	 *
	 * Structure of the custom_data element 'matching':
	 *
	 *   'matching' => array(
	 *     'pay_btn' => array( btn1, btn2, ... ),
	 *     'm1' => array( m1_id1, m1_id2, ... ),
	 *   )
	 *
	 * @since  1.0.1.2
	 * @param  int $membership_id The M2 membership_id.
	 * @param  string $source_id The matching-ID to identify transactions.
	 * @param  string $source The matching-key to identify transactions.
	 * @return bool True if the matching was saved.
	 */
	static public function match_with_source( $membership_id, $source_id, $source ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );

		if ( ! $membership || ! $membership->is_valid() ) {
			return false;
		}

		// First make sure that no other membership is matched to the source.
		$memberships = MS_Model_Membership::get_memberships();

		foreach ( $memberships as $item ) {
			$data = $item->get_custom_data( 'matching' );
			$changed = false;

			if ( ! is_array( $data ) ) { continue; }
			if ( ! isset( $data[$source] ) ) { continue; }
			if ( ! is_array( $data[$source] ) ) {
				unset( $data[$source] );
				continue;
			}

			foreach ( $data[$source] as $key => $id ) {
				if ( $id == $source_id ) {
					unset( $data[$source][$key] );
					$data[$source] = array_values( array_unique( $data[$source] ) );
					$changed = true;
				}
			}
			if ( $changed ) {
				$item->set_custom_data( 'matching', $data );
				$item->save();
			}
		}

		// Then add the matching to the specified membership.
		$data = lib3()->array->get(
			$membership->get_custom_data( 'matching' )
		);

		if ( empty( $data[$source] ) || ! array( $data[$source] ) ) {
			$data[$source] = array();
		}

		$data[$source][] = $source_id;
		$data[$source] = array_values( array_unique( $data[$source] ) );

		$membership->set_custom_data( 'matching', $data );
		$membership->save();

		self::dont_need_matching( $source_id, $source );

		return true;
	}

	/**
	 * Tries to process a single transaction again.
	 *
	 * This function is only useful when the transaction matching was added
	 * before callig it again.
	 *
	 * @since  1.0.1.2
	 * @param  int $transaction_id The ID of the transaction log item.
	 * @return bool True means that the transaction was processed.
	 */
	static public function retry_to_process( $transaction_id ) {
		$res = false;
		$log = MS_Factory::load( 'MS_Model_Transactionlog', $transaction_id );

		if ( empty( $log ) || $log->id != $transaction_id ) {
			// Could not find the requested transaction log item.
			return $res;
		}

		if ( 'ok' == $log->state ) {
			// The transaction was already processed (automatically or manual).
			return $res;
		}

		$post_data = $log->post;
		if ( empty( $post_data ) || ! is_array( $post_data ) ) {
			// We do not have POST data available for the transaction.
			// Re-Processing is not possible.
			return $res;
		}

		$orig_post = $_POST;
		$orig_req = $_REQUEST;

		// Set up the PHP environment to process the transaction again.
		$gateway = MS_Model_Gateway::factory( $log->gateway_id );
		$_POST = $post_data;
		$_REQUEST = $post_data;

		switch ( $log->method ) {
			case 'request':
				// Intentionally not implemented:
				// Request payment needs a subscription to work.
				break;

			case 'process':
				// Intentionally not implemented:
				// Request payment needs a subscription to work.
				break;

			case 'handle':
				$log = $gateway->handle_return( $log );
				break;
		}

		if ( 'ok' == $log->state ) {
			$res = true;
		}

		$_POST = $orig_post;
		$_REQUEST = $orig_req;

		return $res;
	}

	/**
	 * Find a M2 membership by a custom matching ID.
	 *
	 * The matching key and matching ID are stored in the memberships custom
	 * data array.
	 *
	 * See MS_Helper_Listtable_TransactionMatching for a list of matching_keys.
	 *
	 * @since  1.0.1.2
	 * @param  int $matching_key The matching key.
	 * @param  int $matching_id The matching ID.
	 * @return MS_Model_Membership|null The M2 membership.
	 */
	static public function membership_by_matching( $matching_key, $matching_id ) {
		$res = null;
		$args = array( 'include_guest' => 0 );
		$memberships = MS_Model_Membership::get_memberships( $args );

		foreach ( $memberships as $membership ) {
			$data = $membership->get_custom_data( 'matching' );
			if ( empty( $data ) || ! is_array( $data ) ) { continue; }
			if ( ! isset( $data[$matching_key] ) ) { continue; }
			$ids = lib3()->array->get( $data[$matching_key] );

			foreach ( $ids as $id ) {
				if ( $matching_id == $id ) {
					$res = $membership;
					break 2;
				}
			}
		}

		return $res;
	}

	/**
	 * Tries to find a subscription based on the user-ID and M1 sub_id
	 *
	 * Matching values are looked up in the memberships custom data array.
	 *
	 * See MS_Helper_Listtable_TransactionMatching for a list of sources.
	 *
	 * @since  1.0.1.2
	 * @param  int $user_id The user-ID.
	 * @param  string|int $matching_id The matching-ID (M1 sub_id, a btn_id, etc).
	 * @param  string $type The matching type to apply. Default is 'm1'.
	 * @param  string $gateway The payment gateway.
	 * @return MS_Model_Relationship|null The subscription object.
	 */
	static public function find_subscription( $user_id, $matching_id, $type = 'm1', $gateway = 'admin' ) {
		$res = null;

		if ( ! is_numeric( $user_id ) ) {
			// Seems like we got invalid values...
			return $res;
		}

		$user_id = intval( $user_id );
		$matching_id = trim( $matching_id );

		if ( $user_id < 1 || empty( $matching_id ) ) {
			// Seems like user or sub_id are empty or invalid.
			return $res;
		}

		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		if ( $user_id != $member->id ) {
			// The user_id is invalid.
			return $res;
		}

		$membership = self::membership_by_matching( $type, $matching_id );

		if ( ! $membership || ! $membership->is_valid() ) {
			// The sub_id is invalid.
			return $res;
		}

		// Finally we have a member and a membership. Fetch the subscription!
		$res = $member->get_subscription( $membership->id );
		if ( ! $res ) {
			$res = $member->add_membership( $membership->id, $gateway );
		}

		return $res;
	}
}
