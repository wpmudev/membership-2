<?php
/**
 * Coupon model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Coupon_Model extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since  1.0.0
	 * @var string $POST_TYPE
	 */
	protected static $POST_TYPE = 'ms_coupon';

	/**
	 * Coupon type constant: Discount by a fixed amount from membership price.
	 *
	 * @since  1.0.0
	 *
	 * @see $discount_type
	 * @var string
	 */
	const TYPE_VALUE = 'value';

	/**
	 * Coupon type constant: Discount a percentage of the membership price.
	 *
	 * @since  1.0.0
	 *
	 * @see $discount_type
	 * @var string
	 */
	const TYPE_PERCENT = 'percent';

	/**
	 * Coupon duration constant: Coupon is only applied to the first invoice.
	 *
	 * @since  1.0.0
	 *
	 * @see $duration
	 * @var string
	 */
	const DURATION_ONCE = 'once';

	/**
	 * Coupon duration constant: Coupon is only applied to all invoice.
	 *
	 * Note: NOT IMPLEMENTED YET
	 *
	 * @since  1.0.0
	 *
	 * @see $duration
	 * @var string
	 */
	const DURATION_ALWAYS = 'always';

	/**
	 * Time in seconds to redeem the coupon after its been applied.
	 * This prevents users from applying a coupon code and keeping the invoice
	 * on "pending" status for too long.
	 *
	 * Default value 3600 means 1 hour (60 sec * 60 min)
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	const COUPON_REDEMPTION_TIME = 3600;

	/**
	 * Is set to true once the coupon is loaded from DB.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @var string
	 */
	protected $_empty = true;

	/**
	 * The code that the user can enter to apply the coupon to a payment.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * Type of discount, either 'value' or 'percent'.
	 *
	 * Defines, how the $discount property is interpreted.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $discount_type = self::TYPE_VALUE;

	/**
	 * Discount value. Depending on the $discount_type property this is either
	 * a static amount or a percentage.
	 *
	 * @since  1.0.0
	 *
	 * @var number
	 */
	protected $discount = 0.0;

	/**
	 * Duration is relevant for recurring payments. It defines if the coupon is
	 * applied to one invoice or to all invoices.
	 *
	 * Note: THIS IS NOT IMPLEMENTED YET. CURRENTLY ALL COUPONS ARE 'once'
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $duration = self::DURATION_ONCE;

	/**
	 * Defines the earliest date when a coupon code can be used.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $start_date = '';

	/**
	 * Defines the last date when a coupon code can be used.
	 * This is optional and can be left empty for no end date.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $expire_date = '';

	/**
	 * Coupon only valid for this membership.
	 *
	 * Zero value indicates that coupon is valid for any membership.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $membership_id = array();

	/**
	 * Maximun times this coupon could be used.
	 * Note that a "usage" is counted when a member pays for a discounted
	 * invoice and NOT when he enters the coupon code. So there is a chance that
	 * this limit is not always 100% accurate, example:
	 *
	 *   max_uses is 15. Now 15 users visit the payment page and enter the
	 *   coupon. But before the last user pays the discounted invoice a 16th
	 *   user enters the coupon code.
	 *
	 * So max_uses means: Lock the code once max_uses payments were made with
	 * the coupon for new invoices.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected $max_uses = 0;

	/**
	 * Number of times coupon was already used in a paid invoice.
	 *
	 * See notes of $max_uses for more details.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected $used = 0;

	/**
	 * Coupon applied/error message.
	 *
	 * This message is set by the Coupon model when the coupon is applied.
	 * It can be a success or error message (e.g. coupon expired, etc.)
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @var string
	 */
	protected $coupon_message = '';

	/**
	 * Stores the flag of the is_valid() test.
	 *
	 * @since  1.0.0
	 *
	 * @var   bool
	 */
	protected $_valid = false;

	/**
	 * Not persisted fields.
	 *
	 * @since  1.0.0
	 *
	 * @var string[]
	 */
	static public $ignore_fields = array(
		'coupon_message',
	);


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
			'label' => __( 'Membership2 Coupons', 'membership2' ),
                        'exclude_from_search' => true
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::get_post_type()
		);
	}

	/**
	 * Defines and return discount types.
	 *
	 * @since  1.0.0
	 *
	 * @return array {
	 *     The discount types array.
	 *     @type string $discount_type The discount type.
	 *     @type string $discount_simbol The discount simbol.
	 * }
	 */
	public static function get_discount_types() {
		static $types;

		if ( empty( $types ) ) {
			$settings = MS_Factory::load( 'MS_Model_Settings' );

			$types = array(
				self::TYPE_VALUE => $settings->currency,
				self::TYPE_PERCENT => '%',
			);
		}

		return apply_filters(
			'ms_addon_coupon_model_get_discount_types',
			$types
		);
	}

	/**
	 * Verify if is a valid coupon type
	 *
	 * @since  1.0.0
	 *
	 * @param string $type The discount type to validate.
	 *
	 * @return boolean True if valid.
	 */
	public static function is_valid_discount_type( $type ) {
		$valid = false;

		if ( array_key_exists( $type, self::get_discount_types() ) ) {
			$valid = true;
		}

		return apply_filters( 'ms_addon_coupon_model_is_valid_discount_type', $valid, $type );
	}

	/**
	 * Defines and return discount types descriptions.
	 *
	 * @since  1.0.0
	 *
	 * @return array The discount types description array
	 */
	public static function get_discount_type_desc( $type ) {
		$desc = null;
		$types = self::get_discount_types();

		if ( array_key_exists( $type, $types ) ) {
			$desc = $types[ $type ];
		}

		return apply_filters( 'ms_addon_coupon_model_get_discount_type', $desc, $type );
	}

	/**
	 * Get the count of all existing coupons.
	 *
	 * For list table count.
	 * Include expired coupon too.
	 *
	 * @since  1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The discount types array
	 */
	public static function get_coupon_count( $args = null ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
			'post_status' => 'any',
		);

		MS_Factory::select_blog();
		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );
		MS_Factory::revert_blog();

		return apply_filters(
			'ms_addon_coupon_model_get_coupon_count',
			$query->found_posts,
			$args
		);
	}

	/**
	 * Get Coupons.
	 *
	 * By default all available Coupons are returned. The result can be filtered
	 * via the $args parameter that takes any WP_Query options.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  array $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Addon_Coupon_Model[] The found coupon objects.
	 */
	public static function get_coupons( $args = null ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
			'posts_per_page' => 10,
			'post_status' => 'any',
			'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		MS_Factory::select_blog();
		$query = new WP_Query( $args );
		$items = $query->posts;
		MS_Factory::revert_blog();

		$coupons = array();

		foreach ( $items as $item ) {
			$coupons[] = MS_Factory::load( 'MS_Addon_Coupon_Model', $item->ID );
		}

		return apply_filters(
			'ms_addon_coupon_model_get_coupons',
			$coupons,
			$args
		);
	}

	/**
	 * Load coupon using coupon code.
	 *
	 * @since  1.0.0
	 *
	 * @param string $code The coupon code used to load model
	 * @return MS_Addon_Coupon_Model The coupon model, or null if not found.
	 */
	public static function load_by_code( $code ) {
		$code = sanitize_text_field( $code );

		$args = array(
			'post_type' => self::get_post_type(),
			'posts_per_page' => 1,
			'post_status' => 'any',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key'     => 'code',
					'value'   => $code,
				),
			)
		);

		MS_Factory::select_blog();
		$query = new WP_Query( $args );
		$item = $query->posts;
		$coupon_id = 0;
		MS_Factory::revert_blog();

		if ( ! empty( $item[0] ) ) {
			$coupon_id = $item[0];
		}

		$coupon = MS_Factory::load( 'MS_Addon_Coupon_Model', $coupon_id );
		$coupon->_empty = false;

		return apply_filters(
			'ms_addon_coupon_model_load_by_code',
			$coupon,
			$code
		);
	}

	/**
	 * Returns the name of the transient value where the current users
	 * coupon details are stored.
	 *
	 * @since  1.0.1.0
	 * @param  int $user_id
	 * @param  int $membership_id
	 * @return string The transient name.
	 */
	protected static function get_transient_name( $user_id, $membership_id ) {
		global $blog_id;

		$key = apply_filters(
			'ms_addon_coupon_model_transient_name',
			"ms_coupon_{$blog_id}_{$user_id}_{$membership_id}"
		);

		return substr( $key, 0, 40 );
	}

	/**
	 * Save coupon application.
	 *
	 * Saving the application to keep track of the application in gateway return.
	 * Using COUPON_REDEMPTION_TIME to expire coupon application.
	 *
	 * This is a non-static function, as it saves the current object!
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The subscription to apply the coupon.
	 */
	public function save_application( $subscription ) {
		// Don't save empty invitations.
		if ( empty( $this->code ) ) { return false; }

		$membership = $subscription->get_membership();
		$discount = $this->get_discount_value( $subscription );

		$time = apply_filters(
			'ms_addon_coupon_model_save_application_redemption_time',
			self::COUPON_REDEMPTION_TIME
		);

		// Grab the user account as we should be logged in by now.
		$user = MS_Model_Member::get_current_member();

		$key = self::get_transient_name( $user->id, $membership->id );

		$transient = apply_filters(
			'ms_addon_coupon_model_transient_value',
			array(
				'id' => $this->id,
				'user_id' => $user->id,
				'membership_id'	=> $membership->id,
				'discount' => $discount,
				'message' => $this->coupon_message,
			)
		);

		MS_Factory::set_transient( $key, $transient, $time );
		$this->save();

		do_action(
			'ms_addon_coupon_model_save_application',
			$subscription,
			$this
		);
	}

	/**
	 * Get user's coupon application.
	 *
	 * @since  1.0.0
	 *
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 * @return MS_Addon_Coupon_Model The coupon model object.
	 */
	public static function get_application( $user_id, $membership_id ) {
		$key = self::get_transient_name( $user_id, $membership_id );

		$transient = MS_Factory::get_transient( $key );

		$coupon = null;
		if ( is_array( $transient ) && ! empty( $transient['id'] ) ) {
			$the_id = intval( $transient['id'] );
			$coupon = MS_Factory::load( 'MS_Addon_Coupon_Model', $the_id );
			$coupon->coupon_message = $transient['message'];
		} else {
			$coupon = MS_Factory::load( 'MS_Addon_Coupon_Model' );
		}

		return apply_filters(
			'ms_addon_coupon_model_get_application',
			$coupon,
			$user_id,
			$membership_id
		);
	}

	/**
	 * Remove user application for this coupon.
	 *
	 * @since  1.0.0
	 *
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 */
	public function remove_application( $user_id, $membership_id ) {
		$key = self::get_transient_name( $user->id, $membership->id );

		MS_Factory::delete_transient( $key );
                
		do_action(
			'ms_addon_coupon_model_remove_application',
			$user_id,
			$membership_id
		);
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Verify if coupon is valid.
	 *
	 * Checks for maximun number of uses, date range and membership_id restriction.
	 *
	 * @since  1.0.0
	 *
	 * @param int $membership_id The membership id for which coupon is applied
	 * @return boolean True if valid coupon.
	 */
	public function is_valid( $membership_id = 0 ) {
		$valid = true;
		$this->coupon_message = null;

		if ( $this->_empty ) {
			// No coupon-code entered, so don't do anything
			return;
		}

		$timestamp = MS_Helper_Period::current_time( 'timestamp' );

		if ( empty( $this->code ) ) {
			$this->coupon_message = __( 'Coupon code not found.', 'membership2' );
			$valid = false;
		} elseif ( $this->max_uses && $this->used >= $this->max_uses ) {
			$this->coupon_message = __( 'No Coupons remaining for this code.', 'membership2' );
			$valid = false;
		} elseif ( ! empty( $this->start_date ) && strtotime( $this->start_date ) > $timestamp ) {
			$this->coupon_message = __( 'This Coupon is not valid yet.', 'membership2' );
			$valid = false;
		} elseif ( ! empty( $this->expire_date ) && strtotime( $this->expire_date ) < $timestamp ) {
			$this->coupon_message = __( 'This Coupon has expired.', 'membership2' );
			$valid = false;
		} else {
			if ( is_array( $this->membership_id ) ) {
				foreach ( $this->membership_id as $valid_id ) {
					if ( 0 == $valid_id || $valid_id == $membership_id ) {
						$membership_allowed = true;
						break;
					}
				}
			} elseif ( '0' == $this->membership_id ) {
				$membership_allowed = true;
			}
			if ( ! $membership_allowed ) {
				$this->coupon_message = __( 'This Coupon is not valid for this membership.', 'membership2' );
				$valid = false;
			}
		}

		$this->_valid = $valid;

		return apply_filters(
			'ms_coupon_model_is_valid',
			$valid,
			$membership_id,
			$this
		);
	}

	/**
	 * Returns the result of the last is_valid() function call
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function was_valid() {
		return $this->_valid;
	}

	/**
	 * Apply coupon to get discount.
	 *
	 * If trial period is enabled, the discount will be applied in the trial price (even if it is free).
	 * If the membership price is free, the discount will be zero.
	 * If discount is bigger than the price, the discount will be equal to the price.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The membership relationship to apply coupon.
	 * @return float The discount value.
	 */
	public function get_discount_value( $subscription ) {
		$membership = $subscription->get_membership();
		$price = $membership->price; // Excluding Tax
		$original_price = $price;
		$discount = 0;

		if ( $this->is_valid( $membership->id ) ) {
			$discount = $this->discount;

			if ( self::TYPE_PERCENT == $this->discount_type ) {
				$discount = $price * $discount / 100;
			}
			$price -= $discount;

			if ( $price < 0 ) {
				$price = 0;
			}
			$discount = $original_price - $price;
			$this->coupon_message = sprintf(
				__( 'Coupon applied: %1$s', 'membership2' ),
				$this->code
			);
		}

		return apply_filters(
			'ms_addon_coupon_model_apply_discount',
			$discount,
			$membership,
			$this
		);
	}

	/**
	 * Returns property.
	 *
	 * @since  1.0.0
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		switch ( $property ) {
			case 'membership_id':
				if ( ! is_array( $this->membership_id ) ) {
					$this->membership_id = array( $this->membership_id );
				}
				$value = $this->membership_id;
				break;

			case 'remaining_uses':
				if ( $this->max_uses > 0 ) {
					$value = $this->max_uses - $this->used;
				} else {
					$value = __( 'Unlimited', 'membership2' );
				}
				break;

			case 'discount':
				$value = $this->discount;
				if ( $value < 0 ) {
					$value = 0;
				}

				if ( self::TYPE_PERCENT == $this->discount_type ) {
					if ( $value > 100 ) {
						$value = 100;
					}
				}

				if ( $value != $this->discount ) {
					$this->discount = $value;
					$this->save();
				}
				break;

			default:
				$value = $this->$property;
				break;
		}

		return apply_filters(
			'ms_addon_coupon_model__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Set specific property.
	 *
	 * @since  1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'code':
					$value = sanitize_text_field(
						preg_replace( '/[^a-zA-Z0-9\s]/', '', $value )
					);
					$this->$property = strtoupper( $value );
					$this->name = $this->$property;
					break;

				case 'discount':
					$this->$property = floatval( $value );
					break;

				case 'discount_type':
					if ( self::is_valid_discount_type( $value ) ) {
						$this->$property = $value;
					}
					break;

				case 'start_date':
					$this->$property = $this->validate_date( $value );
					break;

				case 'expire_date':
					$this->$property = $this->validate_date( $value );
					if ( strtotime( $this->$property ) < strtotime( $this->start_date ) ) {
						$this->$property = null;
					}
					break;

				case 'membership_id':
					$value = lib3()->array->get( $value );
					foreach ( $value as $ind => $id ) {
						if ( ! MS_Model_Membership::is_valid_membership( $id ) ) {
							unset( $value[ $ind ] );
						}
					}
					if ( empty( $value ) ) {
						$this->$property = array( 0 );
					} else {
						$this->$property = array_values( $value );
					}
					break;

				case 'max_uses':
				case 'used':
					$this->$property = absint( $value );
					break;

				default:
					if ( property_exists( $this, $property ) ) {
						$this->$property = $value;
					}
					break;
			}
		}

		do_action(
			'ms_addon_coupon_model__set_after',
			$property,
			$value,
			$this
		);
	}
}