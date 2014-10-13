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


class MS_Model_Addon extends MS_Model_Option {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public static $instance;
	
	const ADDON_MULTI_MEMBERSHIPS = 'multi_memberships';
	const ADDON_POST_BY_POST = 'post_by_post';
	const ADDON_URL_GROUPS = 'url_groups';
	const ADDON_CPT_POST_BY_POST = 'cpt_post_by_post';
	const ADDON_COUPON = 'coupon';
	const ADDON_TRIAL = 'trial';
	const ADDON_MEDIA = 'media';
	const ADDON_MEDIA_PLUS = 'media_plus';
	const ADDON_PRIVATE_MEMBERSHIPS = 'private_memberships';
	const ADDON_PRO_RATE = 'pro_rate';
	const ADDON_SHORTCODE = 'shortcode';
	const ADDON_AUTO_MSGS_PLUS = 'auto_msgs_plus';
	
	protected $id =  'addon_options';
	
	protected $name = 'Add-on Options';
	
	protected $addons = array();
	
	public static function get_addon_types() {
		return apply_filters( 'ms_model_addon_get_addon_types', array( 
				self::ADDON_MULTI_MEMBERSHIPS,
				self::ADDON_TRIAL,
				self::ADDON_COUPON,
				self::ADDON_PRIVATE_MEMBERSHIPS,
				self::ADDON_POST_BY_POST,
				self::ADDON_CPT_POST_BY_POST,
				self::ADDON_MEDIA,
				self::ADDON_MEDIA_PLUS,
				self::ADDON_SHORTCODE,
				self::ADDON_URL_GROUPS,
				self::ADDON_AUTO_MSGS_PLUS,
		) );
	}

	public static function is_enabled( $addon ) {
	
		$model = MS_Factory::load( 'MS_Model_Addon' );
		$enabled = false;
	
		if( in_array( $addon, self::get_addon_types() ) ) {
			$enabled = ! empty( $model->addons[ $addon ] );
		}

		return apply_filters( 'ms_model_addon_is_enabled_' . $addon, $enabled );
	}
	
	public function enable( $addon ) {
		if( in_array( $addon, self::get_addon_types() ) ) {
			$this->addons[ $addon ] = true;
		}
	}

	public function disable( $addon ) {
		if( in_array( $addon, self::get_addon_types() ) ) {
			$this->addons[ $addon ] = false;
		}
	}
	
	public function toggle_activation( $addon ) {
		if( in_array( $addon, self::get_addon_types() ) ) {
			$this->addons[ $addon ] = empty( $this->addons[ $addon ] );
		}
	}
	
	public function auto_config( $membership ) {
		if( MS_Model_Membership::TYPE_CONTENT_TYPE == $membership->type ) {
			$this->enable( self::ADDON_MULTI_MEMBERSHIPS );	
		}
		if( $membership->trial_period_enabled ) {
			$this->enable( self::ADDON_TRIAL );	
		}
	}
	
	public function get_addon_list() {
		return apply_filters( 'ms_model_addon_get_addon_list', array( 
// 				self::ADDON_MULTI_MEMBERSHIPS => (object) array(
// 					'id' => self::ADDON_MULTI_MEMBERSHIPS,
// 					'name' => __( 'Multiple Memberships', MS_TEXT_DOMAIN ), 	
// 					'description' => __( 'Allow members to join multiple membership levels.', MS_TEXT_DOMAIN ),
// 					'active' => self::is_enabled( self::ADDON_MULTI_MEMBERSHIPS ), 	
// 				),
				self::ADDON_TRIAL => (object) array(
						'id' => self::ADDON_TRIAL,
						'name' => __( 'Trial Period', MS_TEXT_DOMAIN ),
						'description' => __( 'Enable trial period in membership levels.', MS_TEXT_DOMAIN ),
						'active' => self::is_enabled( self::ADDON_TRIAL ),
				),
				self::ADDON_COUPON => (object) array(
						'id' => self::ADDON_COUPON,
						'name' => __( 'Coupon', MS_TEXT_DOMAIN ),
						'description' => __( 'Enable discount coupons.', MS_TEXT_DOMAIN ),
						'active' => self::is_enabled( self::ADDON_COUPON ),
				),
// 				self::ADDON_PRIVATE_MEMBERSHIPS => (object) array(
// 						'id' => self::ADDON_PRIVATE_MEMBERSHIPS,
// 						'name' => __( 'Private Memberships', MS_TEXT_DOMAIN ),
// 						'description' => __( 'Enable private membership levels.', MS_TEXT_DOMAIN ),
// 						'active' => self::is_enabled( self::ADDON_PRIVATE_MEMBERSHIPS ),
// 				),
				self::ADDON_POST_BY_POST => (object) array(
					'id' => self::ADDON_POST_BY_POST,
					'name' => __( 'Post by Post Protection', MS_TEXT_DOMAIN ),
					'description' => __( 'Protect content post by post instead of post categories.', MS_TEXT_DOMAIN ),
					'active' => self::is_enabled( self::ADDON_POST_BY_POST ),
				),
				self::ADDON_CPT_POST_BY_POST => (object) array(
					'id' => self::ADDON_CPT_POST_BY_POST,
					'name' => __( 'Custom Post Type Protection - Post by Post ', MS_TEXT_DOMAIN ),
					'description' => __( 'Protect custom post type post by post instead of post type groups.', MS_TEXT_DOMAIN ),
					'active' => self::is_enabled( self::ADDON_CPT_POST_BY_POST ),
				),
				self::ADDON_MEDIA => (object) array(
					'id' => self::ADDON_MEDIA,
					'name' => __( 'Media Protection', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable protected post and page media protection.', MS_TEXT_DOMAIN ),
					'active' => self::is_enabled( self::ADDON_MEDIA ),
				),
				self::ADDON_MEDIA_PLUS => (object) array(
					'id' => self::ADDON_MEDIA_PLUS,
					'name' => __( 'Media Protection - additional protection methods', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable additional protection method (Basic, Complete, Hybrid).', MS_TEXT_DOMAIN ),
					'active' => self::is_enabled( self::ADDON_MEDIA_PLUS ),
				),
				self::ADDON_SHORTCODE => (object) array(
					'id' => self::ADDON_SHORTCODE,
					'name' => __( 'Shortcode Protection', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable shortcode protection.', MS_TEXT_DOMAIN ),
					'active' => self::is_enabled( self::ADDON_SHORTCODE ),
				),
				self::ADDON_URL_GROUPS => (object) array(
						'id' => self::ADDON_URL_GROUPS,
						'name' => __( 'Url Groups Protection', MS_TEXT_DOMAIN ),
						'description' => __( 'Enable Url Groups protection. This protection will override all other rules. Use it carefully.', MS_TEXT_DOMAIN ),
						'active' => self::is_enabled( self::ADDON_URL_GROUPS ),
				),
				self::ADDON_AUTO_MSGS_PLUS => (object) array(
						'id' => self::ADDON_AUTO_MSGS_PLUS,
						'name' => __( 'Additional Automated Messages', MS_TEXT_DOMAIN ),
						'description' => __( 'Enable additional automated emails.', MS_TEXT_DOMAIN ),
						'active' => self::is_enabled( self::ADDON_AUTO_MSGS_PLUS ),
				),
			)
		);
	}
	
	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}