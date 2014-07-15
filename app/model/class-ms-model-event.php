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
	
	public static $POST_TYPE = 'ms_news';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const TOPIC_MEMBERSHIP = 'membership';
	
	const TOPIC_PAYMENT = 'payment';
	
	const TOPIC_USER = 'user';
	
	
	const TYPE_MS_SIGNED_UP = 'signed_up';
	
	const TYPE_MS_MOVED = 'moved';
	
	const TYPE_MS_EXPIRED = 'expired';
	
	const TYPE_MS_DROPPED = 'dropped';
	
	const TYPE_MS_RENEWED = 'renewed';
	
	const TYPE_MS_DEACTIVATED = 'deactivated';
	
	const TYPE_MS_CANCELED = 'canceled';
	
	const TYPE_MS_REGISTERED = 'registered';
	
	const TYPE_MS_PAID = 'paid';
	
	const TYPE_MS_BEFORE_FINISHES = 'before_finishes';
		
	const TYPE_MS_AFTER_FINISHES = 'after_finishes';
	
	const TYPE_MS_BEFORE_TRIAL_FINISHES = 'before_trial_finishes';
	
	const TYPE_UPDATED_INFO = 'updated_info';
	
	const TYPE_CREDIT_CARD_EXPIRE = 'credit_card_expire';
	
	const TYPE_FAILED_PAYMENT = 'failed_payment';
	
	const TYPE_BEFORE_PAYMENT_DUE = 'before_payment_due';
	
	const TYPE_AFTER_PAYMENT_MADE = 'after_payment_made';
	
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
				self::TYPE_MS_CANCELLED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_PAID => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_BEFORE_FINISHES => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_AFTER_FINISHES => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_BEFORE_TRIAL_FINISHES => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				
				self::TYPE_CREDIT_CARD_EXPIRE => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_FAILED_PAYMENT => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_MS_BEFORE_TRIAL_FINISHES => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_AFTER_PAYMENT_MADE => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_BEFORE_PAYMENT_DUE => array( 'topic' => self::TOPIC_PAYMENT ),
				self::TYPE_AFTER_PAYMENT_MADE => array( 'topic' => self::TOPIC_PAYMENT ),
		) );
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
				'post_status' => 'any',
				'order' => 'DESC',
		);
		$args = apply_fitlers( 'ms_model_events_get_events_args', wp_parse_args( $args, $defaults ) );

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
			$events[] = self::load( $item->ID );
		}
		return $events;
	}
	
	public static function save_event( $type, $ms_relationship ) {
		
		if( self::is_valid_type( $type ) && $ms_relationship->id > 0 ) {
			
			$membership = $ms_relationship->get_membership();
			$member = MS_Model_Member::load( $ms_relationship->user_id );
				
			$event = new self();
			$event->user_id = $ms_relationship->user_id;
			$event->ms_relationship_id = $ms_relationship->id;
			$event->type = $type;
			$event->topic = self::get_topic( $type );
			
			switch( $event->topic ) {
				case self::TOPIC_MEMBERSHIP:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has %s membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$type,
							$member->username,
							$membership->name
					);
				case self::TOPIC_USER:
				case self::TOPIC_PAYMENT:
				default:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> - event: <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$type
					);
					break;
			}
			$event->name = sprintf( 'user: %s, membership: %s, type: %s', $member->name, $membership->name, $type );
			$event->description = apply_filters( 'ms_model_event_description', $desc, $type, $ms_relationship );
			
			$event = apply_filters( 'ms_model_news_record_user_signup_object', $event );
			$event->save();
			
			/** Hook to these actions to handle event notifications. e.g. auto communication. */
			do_action( "ms_event_$type", $event, $ms_relationship );
		}
	}
	
		
}