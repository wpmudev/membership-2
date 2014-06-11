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

class MS_Model_News extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_news';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const TYPE_MS_SIGNUP = 'ms_signup';
	
	const TYPE_MS_MOVE = 'ms_move';
	
	const TYPE_MS_EXPIRED = 'ms_expired';
	
	const TYPE_MS_DROP = 'ms_drop';
	
	const TYPE_MS_RENEW = 'ms_renew';
	
	const TYPE_MS_DEACTIVATE = 'ms_deactivate';
	
	const TYPE_MS_CANCEL = 'ms_cancel';
	
	protected $user_id;
	
	protected $description;
	
	protected $type;
	
	protected $membership_id;
	
	protected $gateway_id;
	
	protected $modified;
	
	public static function get_news_types() {
		return apply_filters( 'ms_model_news_get_news_types', array(
				self::TYPE_MS_SIGNUP,
				self::TYPE_MS_MOVE,
				self::TYPE_MS_EXPIRED,
				self::TYPE_MS_DROP,
				self::TYPE_MS_RENEW,
				self::TYPE_MS_DEACTIVATE,
				self::TYPE_MS_CANCEL,
		) );
	}
	
	public static function is_valid_type( $type ) {
		return in_array( $type, self::get_news_types() );
	}
	
	public static function get_news( $args = null ) {
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
	
	public static function save_news( $membership_relationship, $type ) {
		
		if( self::is_valid_type( $type ) && $membership_relationship->id > 0 ) {
			$news = new self();
			$news->user_id = $membership_relationship->user_id;
			$member = MS_Model_Member::load( $membership_relationship->user_id );
			$news->membership_id = $membership_relationship->membership_id;
			$news->gateway_id  = $membership_relationship->gateway_id;
			$membership = $membership_relationship->get_membership();
			switch( $type ) {
				case self::TYPE_MS_SIGNUP:
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has joined membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
							$member->username,
							$membership->name
					);
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
					$description = sprintf( __( '<span class="ms-news-bold">%s</span> has deactivated to membership <span class="ms-news-bold">%s</span>', MS_TEXT_DOMAIN ),
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