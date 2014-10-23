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
 * Event model.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Event extends MS_Model_Custom_Post_Type {
	
	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * 
	 * @var string 
	 */
	public static $POST_TYPE = 'ms_event';
	public $post_type = 'ms_event';
	
	
	/**
	 * Event topic constants.
	 *
	 * @see $topic
	 *
	 * @since 1.0.0
	 * 
	 * @var string 
	 */
	const TOPIC_MEMBERSHIP = 'membership';
	const TOPIC_PAYMENT = 'payment';
	const TOPIC_USER = 'user';
	const TOPIC_WARNING = 'warning';
	
	/**
	 * Event type constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const TYPE_UPDATED_INFO = 'updated_info';
	const TYPE_MS_SIGNED_UP = 'signed_up';
	const TYPE_MS_MOVED = 'moved';
	const TYPE_MS_EXPIRED = 'expired';
	const TYPE_MS_TRIAL_EXPIRED = 'trial_expired';
	const TYPE_MS_DROPPED = 'dropped';
	const TYPE_MS_RENEWED = 'renewed';
	const TYPE_MS_DEACTIVATED = 'deactivated';
	const TYPE_MS_CANCELED = 'canceled';
	const TYPE_MS_REGISTERED = 'registered';
	const TYPE_MS_BEFORE_FINISHES = 'before_finishes';
	const TYPE_MS_AFTER_FINISHES = 'after_finishes';
	const TYPE_MS_BEFORE_TRIAL_FINISHES = 'before_trial_finishes';
	const TYPE_MS_TRIAL_FINISHED = 'trial_finished';
	const TYPE_CREDIT_CARD_EXPIRE = 'credit_card_expire';
	const TYPE_PAID = 'paid';
	const TYPE_PAYMENT_FAILED = 'payment_failed';
	const TYPE_PAYMENT_PENDING = 'payment_pending';
	const TYPE_PAYMENT_DENIED = 'payment_denied';
	const TYPE_PAYMENT_BEFORE_DUE = 'payment_before_due';
	const TYPE_PAYMENT_AFTER_DUE = 'payment_after_made';
	
	/**
	 * Event's user ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $user_id;
	
	/**
	 * Event's membership ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $membership_id;

	/**
	 * Event's ms relationship ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $ms_relationship_id;
	
	/**
	 * Event description.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $description;
	
	/**
	 * Event topic.
	 *
	 * Events are grouped by topic.
	 * 
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $topic;
	
	/**
	 * Event type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $type;
	
	/**
	 * Event date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $date;
	
	/**
	 * Get Event types.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 * 		array{ 
	 * 			@type string $topic The topic name.
	 * 			@type string $desc The topic description.
	 * 		} 
	 * } 
	 */
	public static function get_event_types() {
		return apply_filters( 'ms_model_news_get_event_types', array(
				
				/*
				 * User topic. 
				 */
				self::TYPE_MS_REGISTERED => array( 
						'topic' => self::TOPIC_USER,
						'desc' => __( 'Has registered.', MS_TEXT_DOMAIN ), 
				),
				self::TYPE_UPDATED_INFO => array( 
						'topic' => self::TOPIC_USER,
						'desc' => __( 'Has updated billing information.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_CREDIT_CARD_EXPIRE => array(
						'topic' => self::TOPIC_USER,
						'desc' => __( "Member's credit card expire warning date.", MS_TEXT_DOMAIN ),
				),
				
				/*
				 * Membership topic.
				 */
				self::TYPE_MS_SIGNED_UP => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Has signed up to membership %s.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_MOVED => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Has moved to membership %s.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_EXPIRED => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Membership %s has expired.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_DROPPED => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Membership %s dropped.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_RENEWED => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Membership %s renewed', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_DEACTIVATED => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Membership %s deactivated', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_CANCELED => array( 
						'topic' => self::TOPIC_MEMBERSHIP,
						'desc' => __( 'Membership %s cancelled.', MS_TEXT_DOMAIN ),
				),
				
				/* 
				 * Warning topic. 
				 */
				self::TYPE_MS_BEFORE_FINISHES => array( 
						'topic' => self::TOPIC_WARNING,
						'desc' => __( 'Membership %s about to finish warning date.', MS_TEXT_DOMAIN ),
				),
				
				self::TYPE_MS_AFTER_FINISHES => array( 
						'topic' => self::TOPIC_WARNING,
						'desc' => __( 'Membership %s finished warning date.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_MS_BEFORE_TRIAL_FINISHES => array( 
						'topic' => self::TOPIC_WARNING,
						'desc' => __( 'Membership % s trial about to finish warning date.', MS_TEXT_DOMAIN ),
				),
				
				/* 
				 * Payment topic. 
				 */
				self::TYPE_PAID => array( 
						'topic' => self::TOPIC_PAYMENT,
						'desc' => __( 'Invoice #%2$s for membership %1$s - Paid.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_PAYMENT_FAILED => array( 
						'topic' => self::TOPIC_PAYMENT,
						'desc' => __( 'Invoice #%2$s for membership %1$s - Payment Failed.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_PAYMENT_PENDING => array( 
						'topic' => self::TOPIC_PAYMENT,
						'desc' => __( 'Invoice #%2$s for membership %1$s - Payment Pending.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_PAYMENT_DENIED => array( 
						'topic' => self::TOPIC_PAYMENT,
						'desc' => __( 'Invoice #%2$s for membership %1$s - Payment Denied.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_PAYMENT_BEFORE_DUE => array( 
						'topic' => self::TOPIC_PAYMENT,
						'desc' => __( 'Invoice #%2$s before due date for membership %1$s warning.', MS_TEXT_DOMAIN ),
				),
				self::TYPE_PAYMENT_AFTER_DUE => array( 
						'topic' => self::TOPIC_PAYMENT,
						'desc' => __( 'Invoice #%2$s after due date for membership %1$s warning.', MS_TEXT_DOMAIN ),
				),
		) );
	}
	
	/**
	 * Get last event of specified type.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Event The $event to search.
	 * @return null|MS_Model_Event The found event, or null.
	 */
	public static function get_last_event_of_type( $event ) {
		
		$found = null;
		
		$args['posts_per_page'] = 1;
		$args['meta_query']['type'] = array(
				'key'     => 'type',
				'value'   => $event->type,
		);
		$args['meta_query']['user_id'] = array(
				'key'     => 'user_id',
				'value'   => $event->user_id,
		);
		if( ! empty( $event->ms_relationship_id ) ) {
			$args['meta_query']['ms_relationship_id'] = array(
					'key'     => 'ms_relationship_id',
					'value'   => $event->ms_relationship_id,
			);
		}

		$events = self::get_events( apply_filters( 'ms_model_events_get_events_args', $args ) );

		if( ! empty( $events[0] ) ) {
			$found = $events[0];
		}
		
		return apply_filters( 'ms_model_event_get_last_event_of_type', $found, $event );
	}
	
	/**
	 * Verify if is a valid event type
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The event type to validate.
	 * @return boolean True if valid.
	 */
	public static function is_valid_type( $type ) {
		
		$valid = array_key_exists( $type, self::get_event_types() );
		
		return apply_filters( 'ms_model_event_is_valid_type', $valid, $type );
	}
	
	/**
	 * Get topic from event.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The event type.
	 * @return string the topic description.
	 */
	public static function get_topic( $type ) {
		
		$topic = null;
		$types = self::get_event_types();
		
		if( ! empty( $types[ $type ]['topic'] ) ) {
			$topic = $types[ $type ]['topic'];
		}
		
		return apply_filters( 'ms_model_event_get_topic', $topic, $type );
	}
	
	/**
	 * Get event description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The event type.
	 * @return string the event description.
	 */
	public static function get_description( $type ) {
		
		$desc = '';
	
		$types = self::get_event_types();
		if( ! empty( $types[ $type ]['desc'] ) ) {
			$desc = $types[ $type ]['desc'];
		}
	
		return apply_filters( 'ms_model_event_get_description', $desc, $type );
	}
	
	/**
	 * Get the total event count.
	 * For list table pagination.
	 * 
	 * @since 1.0.0
	 * 
	 * @param array $args The default query event args.
	 * @return int The total count.
	 */
	public static function get_event_count( $args = null ) {

		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );
		
		return apply_filters( 'ms_model_event_get_event_count', $query->found_posts, $args );
	}
	
	/**
	 * Get events.
	 * 
	 * @since 1.0.0
	 * 
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Event[] The events found.
	 */
	public static function get_events( $args = null ) {
		
		$args = self::get_query_args( $args );

		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$events = array();
		foreach ( $items as $item ) {
			$events[] = MS_Factory::load( 'MS_Model_Event', $item );
		}
		
		return apply_filters( 'ms_model_event_get_events', $events, $args );
	}
	
	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'fields' => 'ids',
				'post_status' => 'any',
				'order' => 'DESC',
		);
		if( ! empty( $args['topic'] ) ) {
			$args['meta_query']['topic'] = array(
					'key'     => 'topic',
					'value'   => $args['topic'],
			);
			unset( $args['topic'] );
		}
		
		$args = wp_parse_args( $args, $defaults );
		
		return apply_filters( 'ms_model_event_get_query_args', $args );
	}
	
	/**
	 * Create and Save event.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The event type.
	 * @return mixed $data The additional data to create an event. 
	 */
	public static function save_event( $type, $data ) {
		
		$event = null;
		
		if( self::is_valid_type( $type ) ) {
			
			$event = MS_Factory::create( 'MS_Model_Event' );
			$event->type = $type;
			$event->topic = self::get_topic( $type );
			
			switch( $event->topic ) {
				case self::TOPIC_PAYMENT:
				case self::TOPIC_WARNING:
				case self::TOPIC_MEMBERSHIP:
					$ms_relationship = $data;
					if( $ms_relationship->id > 0 ) { 
						$membership = $ms_relationship->get_membership();
						$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
						$event->user_id = $ms_relationship->user_id;
						$event->membership_id = $ms_relationship->membership_id;
						$event->ms_relationship_id = $ms_relationship->id;
						$event->name = sprintf( 'user: %s, membership: %s, type: %s', $member->name, $membership->name, $type );
						
						if( self::TOPIC_PAYMENT == $event->topic ) {
							$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
							$description = sprintf( self::get_description( $type ),
									$membership->name,
									$invoice->id
							);
						}
						else {
							$description = sprintf( self::get_description( $type ),
									$membership->name
							);
						}
					}
					else {
						throw new Exception( __( 'Invalid Membership Relationship', MS_TEXT_DOMAIN ) );
					}
					break;
				case self::TOPIC_USER:
					if( $data instanceof MS_Model_Member ) {
						$member = $data;
						$event->user_id = $member->id;
						$event->name = sprintf( 'user: %s, type: %s', $member->name, $type );
					}
					elseif( $data instanceof MS_Model_Membership_Relationship ) {
						$ms_relationship = $data;
						$membership = $ms_relationship->get_membership();
						$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
						$event->user_id = $ms_relationship->user_id;
						$event->membership_id = $ms_relationship->membership_id;
						$event->ms_relationship_id = $ms_relationship->id;
						$event->name = sprintf( 'user: %s, membership: %s, type: %s', $member->name, $membership->name, $type );
					}						
					$description = self::get_description( $type );
					break;
				default:
					MS_Helper_Debug::log(" event topic not implemented $event->topic");
					break;	
			}
			$event->description = apply_filters( 'ms_model_event_description', $description, $type, $data );
			$event->date = MS_Helper_Period::current_date();
				
			$event = apply_filters( 'ms_model_news_record_user_signup_object', $event );
			
			if( ! self::is_duplicate( $event, $data ) ) {
				$event->save();
				
				/*
				 *  Hook to these actions to handle event notifications. e.g. auto communication. 
				 */
				do_action( "ms_model_event_$type", $event, $data );
			}
			else {
				$event = null;
			}
			
		}
		
		return apply_filters( 'ms_model_event_save_event', $event, $type, $data );
		
	}
	
	/**
	 * Verify if a event was already created in the same day.
	 * 
	 * @since 1.0.0
	 * 
	 * @param MS_Model_Event $event The event to verify.
	 * @param mixed $data The additional data.
	 */
	public static function is_duplicate( $event, $data ) {
		
		$is_duplicate = false;
		
		$check_events = apply_filters( 'ms_model_event_is_duplicate_check_events', array(
				self::TYPE_MS_BEFORE_TRIAL_FINISHES,
				self::TYPE_MS_BEFORE_FINISHES,
				self::TYPE_MS_AFTER_FINISHES,
				self::TYPE_CREDIT_CARD_EXPIRE,
				self::TYPE_PAYMENT_BEFORE_DUE,
				self::TYPE_PAYMENT_AFTER_DUE,
		) );
		if( in_array( $event->type, $check_events ) && $last_event = self::get_last_event_of_type( $event ) ) {
			if( gmdate( MS_Helper_Period::PERIOD_FORMAT, strtotime( $last_event->date ) ) == MS_Helper_Period::current_date() ) {
				$is_duplicate = true;
			}
		}
		
		return apply_filters( 'ms_model_event_is_duplicate', $is_duplicate, $event, $data );
	}
}