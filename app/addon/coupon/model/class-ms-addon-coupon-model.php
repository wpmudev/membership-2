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
 * Coupon model.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Addon_Coupon_Model extends MS_Model_Custom_Post_Type {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * @var string $POST_TYPE
	 */
	public static $POST_TYPE = 'ms_coupon';
	public $post_type = 'ms_coupon';

	/**
	 * Coupon type constants.
	 *
	 * @since 1.0.0
	 *
	 * @see $discount_type
	 * @var string
	 */
	const TYPE_VALUE = 'value';
	const TYPE_PERCENT = 'percent';

	/**
	 * Time in seconds to redeem the coupon after its been applied, before it goes back into the pool.
	 *
	 * @since 1.0.0
	 *
	 * @see $discount_type
	 * @var string
	 */
	const COUPON_REDEMPTION_TIME = 3600;

	/**
	 * Is set to true once the coupon is loaded from DB
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $_empty = true;

	/**
	 * Coupon code text.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $code;

	/**
	 * Discount type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $discount_type;

	/**
	 * Discount value.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $discount;

	/**
	 * Coupon validation start date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $start_date;

	/**
	 * Coupon validation expiry date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $expire_date;

	/**
	 * Coupon only valid for this membership.
	 *
	 * Zero value indicates that coupon is valid for any membership.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $membership_id;

	/**
	 * Maximun times this coupon could be used.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $max_uses;

	/**
	 * Number of times coupon was already used.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $used = 0;

	/**
	 * Coupon applied/error message.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $coupon_message;

	/**
	 * Stores the flag of the is_valid_coupon() test
	 *
	 * @since 1.1.0
	 *
	 * @var   bool
	 */
	protected $_valid = false;

	/**
	 * Not persisted fields.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	public $ignore_fields = array(
		'coupon_message',
		'post_type',
	);

	/**
	 * Defines and return discount types.
	 *
	 * @since 1.0.0
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
			$types = array(
				self::TYPE_VALUE => __( '$', MS_TEXT_DOMAIN ),
				self::TYPE_PERCENT => __( '%', MS_TEXT_DOMAIN ),
			);
		}

		return apply_filters( 'ms_addon_coupon_model_get_discount_types', $types );
	}

	/**
	 * Verify if is a valid coupon type
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The discount types array
	 */
	public static function get_coupon_count( $args = null ) {
		$defaults = array(
			'post_type' => self::$POST_TYPE,
			'post_status' => 'any',
		);

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		return apply_filters( 'ms_addon_coupon_model_get_coupon_count', $query->found_posts, $args );
	}

	/**
	 * Get Coupons
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Addon_Coupon_Model[] The found coupon objects.
	 */
	public static function get_coupons( $args = null ) {
		$defaults = array(
			'post_type' => self::$POST_TYPE,
			'posts_per_page' => 10,
			'post_status' => 'any',
			'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );
		$items = $query->get_posts();

		$coupons = array();

		foreach ( $items as $item ) {
			$coupons[] = MS_Factory::load( 'MS_Addon_Coupon_Model', $item->ID );
		}

		return apply_filters( 'ms_addon_coupon_model_get_coupons', $coupons, $args );
	}

	/**
	 * Load coupon using coupon code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code The coupon code used to load model
	 * @return MS_Addon_Coupon_Model The coupon model, or null if not found.
	 */
	public static function load_by_coupon_code( $code ) {
		$code = sanitize_text_field( $code );

		$args = array(
			'post_type' => self::$POST_TYPE,
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

		$query = new WP_Query( $args );
		$item = $query->get_posts();
		$coupon_id = 0;

		if ( ! empty( $item[0] ) ) {
			$coupon_id = $item[0];
		}

		$coupon = MS_Factory::load( 'MS_Addon_Coupon_Model', $coupon_id );
		$coupon->_empty = false;

		return apply_filters(
			'ms_addon_coupon_model_load_by_coupon_code',
			$coupon,
			$code
		);
	}

	/**
	 * Verify if coupon is valid.
	 *
	 * Checks for maximun number of uses, date range and membership_id restriction.
	 *
	 * @since 1.0.0
	 *
	 * @param int $membership_id The membership id for which coupon is applied
	 * @return boolean True if valid coupon.
	 */
	public function is_valid_coupon( $membership_id = 0 ) {
		$valid = true;
		$this->coupon_message = null;

		if ( $this->_empty ) {
			// No coupon-code entered, so don't do anything
			return;
		}

		$timestamp = MS_Helper_Period::current_time( 'timestamp' );

		if ( empty( $this->code ) ) {
			$this->coupon_message = __( 'Coupon code not found.', MS_TEXT_DOMAIN );
			$valid = false;
		} elseif ( $this->max_uses && $this->used > $this->max_uses ) {
			$this->coupon_message = __( 'No Coupons remaining for this code.', MS_TEXT_DOMAIN );
			$valid = false;
		} elseif ( ! empty( $this->start_date ) && strtotime( $this->start_date ) > $timestamp ) {
			$this->coupon_message = __( 'This Coupon is not valid yet.', MS_TEXT_DOMAIN );
			$valid = false;
		} elseif ( ! empty( $this->expire_date ) && strtotime( $this->expire_date ) < $timestamp ) {
			$this->coupon_message = __( 'This Coupon has expired.', MS_TEXT_DOMAIN );
			$valid = false;
		} else {
			foreach ( $this->membership_id as $valid_id ) {
				if ( $valid_id == 0 || $valid_id == $membership_id ) {
					$membership_allowed = true;
					break;
				}
			}
			if ( ! $membership_allowed ) {
				$this->coupon_message = __( 'This Coupon is not valid for this membership.', MS_TEXT_DOMAIN );
				$valid = false;
			}
		}

		$this->_valid = $valid;

		return apply_filters(
			'ms_coupon_model_is_valid_coupon',
			$valid,
			$membership_id,
			$this
		);
	}

	/**
	 * Returns the result of the last is_valid_coupon() function call
	 *
	 * @since  1.1.0
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
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship to apply coupon.
	 * @return float The discount value.
	 */
	public function get_discount_value( $ms_relationship ) {
		$membership = $ms_relationship->get_membership();
		$price = $membership->price;
		$original_price = $price;
		$discount = 0;

		if ( $this->is_valid_coupon( $membership->id ) ) {
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
				__( 'Using Coupon code: %1$s. Discount applied: %2$s %3$s', MS_TEXT_DOMAIN ),
				$this->code,
				MS_Plugin::instance()->settings->currency,
				number_format( $discount, 2 )
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
	 * Save coupon application.
	 *
	 * Saving the application to keep track of the application in gateway return.
	 * Using COUPON_REDEMPTION_TIME to expire coupon application.
	 *
	 * @since 1.0.0
	 *
	 * @param int $membership_id The membership id to apply the coupon.
	 */
	public function save_coupon_application( $ms_relationship ) {
		global $blog_id;

		$membership = $ms_relationship->get_membership();

		$discount = $this->get_discount_value( $ms_relationship );

		/** @todo Handle for network/multsite mode.*/
		$global = false;

		$time = apply_filters(
			'ms_addon_coupon_model_save_coupon_application_redemption_time',
			self::COUPON_REDEMPTION_TIME
		);

		/** Grab the user account as we should be logged in by now */
		$user = MS_Model_Member::get_current_member();

		$transient_name = apply_filters(
			'ms_addon_coupon_model_transient_name',
			"ms_coupon_{$blog_id}_{$user->id}_{$membership->id}"
		);

		$transient_value = apply_filters(
			'ms_addon_coupon_model_transient_value',
			array(
				'coupon_id' => $this->id,
				'user_id' => $user->id,
				'membership_id'	=> $membership->id,
				'discount' => $discount,
				'coupon_message' => $this->coupon_message,
			)
		);

		if ( $global && function_exists( 'get_site_transient' ) ) {
			set_site_transient( $transient_name, $transient_value, $time );
		}  else {
			set_transient( $transient_name, $transient_value, $time );
		}
		$this->save();

		do_action(
			'ms_addon_coupon_model_save_coupon_application',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Get user's coupon application.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 * @return MS_Addon_Coupon_Model The coupon model object.
	 */
	public static function get_coupon_application( $user_id, $membership_id ) {
		global $blog_id;

		/** @todo Handle for network/multsite mode.*/
		$global = false;

		$transient_name = apply_filters(
			'ms_addon_coupon_model_transient_name',
			"ms_coupon_{$blog_id}_{$user_id}_{$membership_id}"
		);

		if ( $global && function_exists( 'get_site_transient' ) ) {
			$transient_value = get_site_transient( $transient_name );
		} else {
			$transient_value = get_transient( $transient_name );
		}

		$coupon = null;
		if ( ! empty( $transient_value ) ) {
			$coupon = MS_Factory::load( 'MS_Addon_Coupon_Model', $transient_value['coupon_id'] );
			$coupon->coupon_message = $transient_value['coupon_message'];
		} else {
			$coupon = MS_Factory::load( 'MS_Addon_Coupon_Model' );
		}

		return apply_filters(
			'ms_addon_coupon_model_get_coupon_application',
			$coupon,
			$user_id,
			$membership_id
		);
	}

	/**
	 * Remove user application for this coupon.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 */
	public static function remove_coupon_application( $user_id, $membership_id ) {
		global $blog_id;

		/** @todo Handle for network/multsite mode.*/
		$global = false;

		$transient_name = apply_filters(
			'ms_addon_coupon_model_transient_name',
			"ms_coupon_{$blog_id}_{$user_id}_{$membership_id}"
		);

		if ( $global && function_exists( 'delete_site_transient' ) ) {
			delete_site_transient( $transient_name );
		} else {
			delete_transient( $transient_name );
		}

		do_action(
			'ms_addon_coupon_model_remove_coupon_application',
			$user_id,
			$membership_id
		);
	}

	/**
	 * Returns property.
	 *
	 * @since 1.0.0
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
					$value = __( 'Unlimited', MS_TEXT_DOMAIN );
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
	 * @since 1.0.0
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
					$value = WDev()->get_array( $value );
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
					$this->$property = $value;
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