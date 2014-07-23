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

class MS_Model_Event extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_event';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const TOPIC_MEMBERSHIP = 'membership';
	
	const TOPIC_PAYMENT = 'payment';
	
	const TOPIC_USER = 'user';
	
	const TYPE_UPDATED_INFO = 'updated_info';
	
	const TOPIC_WARNING = 'warning';
	
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
	
	const TYPE_PAYMENT_AFTER_MADE = 'payment_after_made';
	
	protected $user_id;
	
	protected $description;
	
	protected $topic;
	
	protected $type;
	
	protected $ms_relationship_id;
	
	protected $modified;
	
	public static function get_event_types() {
		return apply_filters( 'ms_model_news_get_event_types', array(
				self::TYPE_MS_REGISTERED => array( 'topic' => self::TOPIC_USER ),
				self::TYPE_UPDATED_INFO => array( 'topic' => self::TOPIC_USER ),
				
				self::TYPE_MS_SIGNED_UP => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_MOVED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_EXPIRED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_DROPPED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_RENEWED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_DEACTIVATED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_CANCELED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				
				self::TYPE_MS_BEFORE_FINISHES => array( 'topic' => self::TOPIC_WARNING ),
				self::TYPE_MS_AFTER_FINISHES => array( 'topic' => self::TOPIC_WARNING ),
				self::TYPE_MS_BEFORE_TRIAL_FINISHES => array( 'topic' => self::TOPIC_WARNING ),
				
				self::TYPE_CREDIT_CARD_EXPIRE => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_MS_BEFORE_TRIAL_FINISHES => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_PAID => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_PAYMENT_FAILED => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_PAYMENT_PENDING => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_PAYMENT_DENIED => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_PAYMENT_BEFORE_DUE => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_PAYMENT_AFTER_MADE => array( 'topic' => self::TOPIC_PAYMENT ),
		) );
	}
	
	public static function get_last_event_of_type( $type ) {
		$args['posts_per_page'] = 1;
		$args['meta_query']['type'] = array(
				'key'     => 'type',
				'value'   => $event->type,
		);
		$events = self::get_events( apply_filters( 'ms_model_events_get_events_args', $args ) );
		if( ! empty( $events[0] ) ) {
			return $events[0];
		}
		else {
			return null;
		}
	}
	
	public static function is_valid_type( $type ) {
		return array_key_exists( $type, self::get_event_types() );
	}
	
	public static function get_topic( $type ) {
		
		$topic = null;
		$types = self::get_event_types();
		if( ! empty( $types[ $type ]['topic'] ) ) {
			$topic = $types[ $type ]['topic'];
		}
		
		return apply_filters( 'ms_model_event_get_topic', $topic, $type );
	}
	
	public static function get_events( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'fields' => 'ids',
				'post_status' => 'any',
				'order' => 'DESC',
		);
		$args = apply_filters( 'ms_model_events_get_events_args', wp_parse_args( $args, $defaults ) );

		if( ! empty( $args['topic'] ) ) {
			$args['meta_query']['topic'] = array(
					'key'     => 'topic',
					'value'   => $args['topic'],
			);
			unset( $args['topic'] );
		}
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$events = array();
		foreach ( $items as $item ) {
			$events[] = self::load( $item );
		}
		return $events;
	}
	
	public static function save_event( $type, $data ) {
		
		if( self::is_valid_type( $type ) ) {
			
			$event = new self();
			$event->type = $type;
			$event->topic = self::get_topic( $type );
			
			if( self::is_duplicate( $event, $data ) ) {
				return false;
			}
			
			switch( $event->topic ) {
				case self::TOPIC_PAYMENT:
				case self::TOPIC_WARNING:
				case self::TOPIC_MEMBERSHIP:
					$ms_relationship = $data;
					if( $ms_relationship->id > 0 ) { 
						$membership = $ms_relationship->get_membership();
						$member = MS_Model_Member::load( $ms_relationship->user_id );
						$event->user_id = $ms_relationship->user_id;
						$event->ms_relationship_id = $ms_relationship->id;
						$event->name = sprintf( 'user: %s, membership: %s, type: %s', $member->name, $membership->name, $type );
						
						$description = sprintf( __( '<span class="ms-news-bold">%s</span> has %s membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
								$member->username,
								$type,
								$membership->name
						);
					}
					else {
						throw new Exception( __( 'Invalid Membership Relationship', MS_TEXT_DOMAIN ) );
					}
					break;
				case self::TOPIC_USER:
					$member = $data;
					$event->user_id = $member->id;
					$event->name = sprintf( 'user: %s, type: %s', $member->name, $type );
						
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> - event: <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$type
					);
					break;
				default:
					MS_Helper_Debug::log(" event topic not implemented $event->topic");
					break;	
			}
			$event->description = apply_filters( 'ms_model_event_description', $description, $type, $data );
				
			$event = apply_filters( 'ms_model_news_record_user_signup_object', $event );
			$event->save();
			
			/** Hook to these actions to handle event notifications. e.g. auto communication. */
			do_action( "ms_model_event_$type", $event, $data );
			
			return $event;
		}
	}
	
	public static function is_duplicate( $event, $data ) {
		
		$is_duplicate = false;
		
		$check_events = apply_filters( 'ms_model_event_is_duplicate_check_events', array(
				self::TYPE_MS_BEFORE_TRIAL_FINISHES,
				self::TYPE_MS_BEFORE_FINISHES,
				self::TYPE_MS_AFTER_FINISHES,
		) );
		
		if( in_array( $event->type, $check_events ) && $event = self::get_last_event_of_type( $event->type ) ) {
			if( date( MS_Helper_Period::PERIOD_FORMAT, strtotime( $event->modified ) ) == MS_Helper_Period::current_date() ) {
				$is_duplicate = true;
			}
		}
		
		return $is_duplicate;
	}	
}