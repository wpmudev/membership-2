<?php
/**
 * Invitation model.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Invitation_Model extends MS_Model_CustomPostType {

	/**
	 * Time in seconds to use the invitation after its been applied.
	 * This prevents users from applying an invitation code and keeping the
	 * invoice on "pending" status for too long.
	 *
	 * Default value 3600 means 1 hour (60 sec * 60 min)
	 *
	 * @since 1.0.1.0
	 *
	 * @var int
	 */
	const INVITATION_REDEMPTION_TIME = 3600;

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since  1.0.0
	 * @var string $POST_TYPE
	 */
	protected static $POST_TYPE = 'ms_invitation';

	/**
	 * invitation code text.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * invitation validation start date.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $start_date = '';

	/**
	 * invitation validation expiry date.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $expire_date = '';

	/**
	 * Invitation only valid for this membership.
	 *
	 * Zero value indicates that invitation is valid for any membership.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected $membership_id = 0;

	/**
	 * Maximun times this invitation could be used.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected $max_uses = 0;

	/**
	 * Number of times invitation was already used.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected $used = 0;

	/**
	 * Information on invitation use details.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $use_details = array();

	/**
	 * invitation applied/error message.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $invitation_message = '';

	/**
	 * Not persisted fields.
	 *
	 * @since  1.0.0
	 *
	 * @var string[]
	 */
	static public $ignore_fields = array(
		'invitation_message'
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
	 * Get the count of all existing invitations.
	 *
	 * For list table count.
	 * Include expired invitation too.
	 *
	 * @since  1.0.0
	 *
	 * @param $args The query post args
	 * @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The discount types array
	 */
	public static function get_invitation_count( $args = null ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
			'post_status' => 'any',
		);

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		return apply_filters(
			'ms_addon_invitation_model_get_invitation_count',
			$query->found_posts,
			$args
		);
	}

	/**
	 * Get invitations.
	 *
	 * @since  1.0.0
	 *
	 * @param $args The query post args
	 *        @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Invitation[] The found invitation objects.
	 */
	public static function get_invitations( $args = null ) {
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

		$invitations = array();

		foreach ( $items as $item ) {
			$invitations[] = MS_Factory::load( 'MS_Addon_Invitation_Model', $item->ID );
		}

		return apply_filters(
			'ms_addon_invitation_model_get_invitations',
			$invitations,
			$args
		);
	}

	/**
	 * Load invitation using invitation code.
	 *
	 * @since  1.0.0
	 *
	 * @param string $code The invitation code used to load model
	 * @return MS_Model_Invitation The invitation model, or null if not found.
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
					'key'   => 'code',
					'value' => $code,
				),
			)
		);

		$query = new WP_Query( $args );
		$item = $query->posts;

		$invitation_id = 0;
		if ( ! empty( $item[0] ) ) {
			$invitation_id = $item[0];
		}

		$model = MS_Factory::load( 'MS_Addon_Invitation_Model', $invitation_id );

		// If the model is not valid it means that the WP_Query returned no
		// results. So the code was not found.
		if ( ! $model->is_valid() ) {
			$model->invitation_message = __( 'Invitation code not found.', 'membership2' );
		}

		return apply_filters(
			'ms_addon_invitation_model_load_by_code',
			$model,
			$code
		);
	}

	/**
	 * Returns the name of the transient value where the current users
	 * invitation details are stored.
	 *
	 * @since  1.0.1.0
	 * @param  int $user_id
	 * @param  int $membership_id
	 * @return string The transient name.
	 */
	protected static function get_transient_name( $user_id, $membership_id ) {
		global $blog_id;

		$key = apply_filters(
			'ms_addon_invitation_model_transient_name',
			"ms_invitation_{$blog_id}_{$user_id}_{$membership_id}"
		);

		return substr( $key, 0, 40 );
	}

	/**
	 * Save invitation application.
	 *
	 * Saving the application to keep track of the application in gateway return.
	 * Using INVITATION_REDEMPTION_TIME to expire invitation application.
	 *
	 * This is a non-static function, as it saves the current object!
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The subscription to apply the invitation.
	 */
	public function save_application( $subscription ) {
		// Don't save empty invitations.
		if ( empty( $this->code ) ) { return false; }

		$membership = $subscription->get_membership();

		$time = apply_filters(
			'ms_addon_invitation_model_save_application_redemption_time',
			self::INVITATION_REDEMPTION_TIME
		);

		// Grab the user account as we should be logged in by now.
		$user = MS_Model_Member::get_current_member();

		$key = self::get_transient_name( $user->id, $membership->id );

		$transient = apply_filters(
			'ms_addon_invitation_model_transient_value',
			array(
				'id' => $this->id,
				'user_id' => $user->id,
				'membership_id'	=> $membership->id,
				'message' => $this->invitation_message,
			)
		);

		MS_Factory::set_transient( $key, $transient, $time );
		$this->save();

		do_action(
			'ms_addon_invitation_model_save_application',
			$subscription,
			$this
		);
	}

	/**
	 * Get user's invitation application.
	 *
	 * @since  1.0.0
	 *
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 * @return MS_Addon_Invitation_Model The invitation model object.
	 */
	public static function get_application( $user_id, $membership_id ) {
		$key = self::get_transient_name( $user_id, $membership_id );

		$transient = MS_Factory::get_transient( $key );

		$invitation = null;
		if ( is_array( $transient ) && ! empty( $transient['id'] ) ) {
			$the_id = intval( $transient['id'] );
			$invitation = MS_Factory::load( 'MS_Addon_Invitation_Model', $the_id );
			$invitation->invitation_message = $transient['message'];
		} else {
			$invitation = MS_Factory::load( 'MS_Addon_Invitation_Model' );
		}

		if ( $invitation->is_valid() ) {
			$invitation->invitation_message = __( 'Invitation code is correct.', 'membership2' );
		}

		return apply_filters(
			'ms_addon_invitation_model_get_application',
			$invitation,
			$user_id,
			$membership_id
		);
	}

	/**
	 * Remove user application for this invitation.
	 *
	 * @since  1.0.0
	 *
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 */
	public function remove_application( $user_id, $membership_id ) {
		$key = self::get_transient_name( $user_id, $membership_id );

		MS_Factory::delete_transient( $key );
                
                $this->remove_invitation_check();

		do_action(
			'ms_addon_invitation_model_remove_application',
			$user_id,
			$membership_id,
			$this
		);
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Verify if invitation is valid.
	 *
	 * Checks for maximun number of uses, date range, if the user has used it
	 * and if it exists.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $membership_id
	 * @return bool True if the invitation can be used for the membership.
	 */
	public function is_valid( $membership_id = 0 ) {
		$valid = true;

		if ( empty( $this->code ) ) {
			$valid = false;
		}

		$timestamp = MS_Helper_Period::current_time( 'timestamp' );

		if ( $this->max_uses && $this->used >= $this->max_uses ) {
			$this->invitation_message = __( 'This invitation code is no longer valid.', 'membership2' );
			$valid = false;
		} elseif ( ! empty( $this->start_date ) && strtotime( $this->start_date ) > $timestamp ) {
			$this->invitation_message = __( 'This invitation is not valid yet.', 'membership2' );
			$valid = false;
		} elseif ( ! empty( $this->expire_date ) && strtotime( $this->expire_date ) < $timestamp ) {
			$this->invitation_message = __( 'This invitation has expired.', 'membership2' );
			$valid = false;
		} elseif ( ! $this->check_invitation_user_usage() ) {
			$this->invitation_message = __( 'You have already used this invitation code.', 'membership2' );
			$valid = false;
		} elseif ( $membership_id ) {
			$membership_allowed = false;

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
				$this->invitation_message = __( 'This Invitation is not valid for this membership.', 'membership2' );
				$valid = false;
			}else{
                            $this->add_invitation_check();
                        }
		}

		return apply_filters(
			'ms_invitation_model_is_valid',
			$valid,
			$this
		);
	}
        
        /**
         * Generate the identifier pair with user id or ip and membership ID
         *
         * @since 1.0.2.6
         * @param $ip bool
         */
        public function get_user_membership_pair( $ip = false ) {
            
            $user = MS_Model_Member::get_current_member();
            
            if( ! $ip ) {
                $membership_id = isset( $_POST['membership_id'] ) ? $_POST['membership_id'] : 0;
                return $user->id . '_' . $membership_id;
            }else{
                $ip	= lib3()->net->current_ip()->ip;
                return $user->id . '_' . $ip;
            }
        }

	/**
	 * Checks to see if the user ID or IP is associated with the invitation code.
	 *
	 * @since  1.0.0
	 */
	public function check_invitation_user_usage() {
		$user = MS_Model_Member::get_current_member();
                
		if ( $user->is_member ) {
			if ( in_array( $this->get_user_membership_pair(), $this->use_details ) ) {
				return false;
			}
		}

		$ip	= lib3()->net->current_ip()->ip;
		if ( in_array( $this->get_user_membership_pair( true ), $this->use_details ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Retrieves either the current user ID (if logged in)
	 * or the user IP (if not logged in)
	 *
	 * @since  1.0.0
	 */
	public function get_invitation_user_id() {
		$user = MS_Model_Member::get_current_member();
		$user_id = $user->id;

		if ( ! $user->is_member ) {
			$user_id = lib3()->net->current_ip()->ip;
		}

		return $user_id;
	}

	/**
	 * Apply use of invitation code.
	 *
	 * @since  1.0.0
	 */
	public function add_invitation_check() {
		// get the user ID
		$user_id = $this->get_invitation_user_id();
                
		// if the user ID hasn't used this invitation already, increment.
		if ( ! in_array( $this->get_user_membership_pair(), $this->use_details ) ) {
			$this->used += 1;
		}

		// save the user ID to the usage field
		$user = array( $this->get_user_membership_pair() );
		$this->use_details = array_merge( $this->use_details, $user );
                if( ! empty( $this->id ) )
                    $this->save();
	}

	/**
	 * Remove user application for this invitation.
	 *
	 * @since  1.0.0
	 */
	public function remove_invitation_check() {
		// get the user ID
		$user_id = $this->get_invitation_user_id();

		// if the user ID exists in the usage array, remove it and decrement.
		if ( in_array( $user_id, $this->use_details ) ) {
			$this->used -= 1;
			$key = array_search( $user_id, $this->use_details );
			unset( $this->use_details[$key] );
                        if( ! empty( $this->id ) )
                            $this->save();
		}
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
			case 'remaining_uses':
				if ( $this->max_uses > 0 ) {
					$value = $this->max_uses - $this->used;
				} else {
					$value = __( 'Unlimited', 'membership2' );
				}
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_addon_invitation_model__get',
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
			'ms_addon_invitation_model__set_after',
			$property,
			$value,
			$this
		);
	}
}