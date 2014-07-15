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
	
	const TYPE_INFO_UPDATE = 'info_update';
	
	const TYPE_CREDIT_CARD_EXPIRE = 'credit_card_expire';
	
	const TYPE_FAILED_PAYMENT = 'failed_payment';
	
	const TYPE_BEFORE_PAYMENT_DUE = 'before_payment_due';
	
	const TYPE_AFTER_PAYMENT_MADE = 'after_payment_made';
	
	const TOPIC_MEMBERSHIP = 'membership';
	
	const TOPIC_PAYMENT = 'payment';
	
	protected $user_id;
	
	protected $description;
	
	protected $type;
	
	protected $membership_id;
	
	protected $gateway_id;
	
	protected $modified;
	
	public static function get_event_types() {
		return apply_filters( 'ms_model_news_get_news_types', array(
				self::TYPE_MS_SIGNUP => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_MOVE => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_EXPIRED => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_DROP => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_RENEW => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_DEACTIVATE => array( 'topic' => self::TOPIC_MEMBERSHIP ),
				self::TYPE_MS_CANCEL => array( 'topic' => self::TOPIC_MEMBERSHIP ),
		) );
	}
	
	public static function is_valid_type( $type ) {
		return array_key_exists( $type, self::get_news_types() );
	}
	
	public static function get_events( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'post_status' => 'any',
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$news = array();
		foreach ( $items as $item ) {
			$news[] = self::load( $item->ID );
		}
		return $news;
	}
	
	public static function save_event( $type, $ms_relationship ) {
		
		if( self::is_valid_type( $type ) && $ms_relationship->id > 0 ) {
			$news = new self();
			$news->user_id = $ms_relationship->user_id;
			$member = MS_Model_Member::load( $ms_relationship->user_id );
			$news->membership_id = $ms_relationship->membership_id;
			$news->gateway_id  = $ms_relationship->gateway_id;
			$membership = $ms_relationship->get_membership();
			do_action( "ms_news_$type", $ms_relationship );
			switch( $type ) {
				case self::TYPE_MS_SIGNUP:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has joined membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					/** Registration completed automated message */
					do_action( 'ms_communications_process_' . MS_Model_Communication::COMM_TYPE_REGISTRATION , $ms_relationship );
					break;
				case self::TYPE_MS_MOVE:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has moved to membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
				case self::TYPE_MS_EXPIRED:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has left membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
				case self::TYPE_MS_DROP:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has dropped membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
				case self::TYPE_MS_RENEW:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has renewed membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
				case self::TYPE_MS_DEACTIVATE:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has deactivated membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
				case self::TYPE_MS_CANCEL:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has canceled membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
				default:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span>, membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
					break;
			}
			$news->name = sprintf( 'user: %s, membership: %s, type: %s', $member->name, $membership->name, $type );
			$news->description = $description; 
			$news->type = $type;
			
			$news = apply_filters( 'ms_model_news_record_user_signup_object', $news );
			$news->save();
		}
	}
}