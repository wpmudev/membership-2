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
	
	protected static $instance;
	
	const ADDON_MULTI_MEMBERSHIPS = 'multi_memberships';
	const ADDON_POST_BY_POST = 'post_by_post';
	const ADDON_URL_GROUPS = 'url_groups';
	const ADDON_CPT_POST_BY_POST = 'cpt_post_by_post';
	const ADDON_COUPON = 'coupon';
	const ADDON_TRIAL = 'trial';
	const ADDON_MEDIA = 'media';
	const ADDON_PRIVATE_MEMBERSHIPS = 'private_memberships';
	
	protected $id =  'addon_options';
	
	protected $name = 'Add-on Options';
	
	protected $addons = array();
	
	public static function get_addon_types() {
		return apply_filters( 'ms_model_addon_get_addon_types', array( 
				self::ADDON_MULTI_MEMBERSHIPS,
				self::ADDON_POST_BY_POST,
				self::ADDON_URL_GROUPS,
				self::ADDON_CPT_POST_BY_POST,
				self::ADDON_COUPON,
				self::ADDON_TRIAL,
				self::ADDON_MEDIA,
				self::ADDON_PRIVATE_MEMBERSHIPS,
		) );
	}

	/**
	 * @deprecated
	 * @param unknown $addon
	 * @return mixed
	 */
	public static function is_active( $addon ) {
		
		$model = self::load();
		$active = false;
		
		if( in_array( $addon, self::get_addon_types() ) ) {
			$active = ! empty( $model->addons[ $addon ] );
		}
		return apply_filters( 'ms_model_addon_is_active', $active, $addon );
	}

	public static function is_enabled( $addon ) {
	
		$model = self::load();
		$enabled = false;
	
		if( in_array( $addon, self::get_addon_types() ) ) {
			$enabled = ! empty( $model->addons[ $addon ] );
		}
		return apply_filters( 'ms_model_addon_is_enabled', $enabled, $addon );
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
	
	public function get_addon_list() {
		return apply_filters( 'ms_model_addon_get_addon_list', array( 
				self::ADDON_MULTI_MEMBERSHIPS => (object) array(
					'id' => self::ADDON_MULTI_MEMBERSHIPS,
					'name' => __( 'Multiple Memberships', MS_TEXT_DOMAIN ), 	
					'description' => __( 'Allow members to join multiple membership levels.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_MULTI_MEMBERSHIPS ), 	
				),
				self::ADDON_POST_BY_POST => (object) array(
					'id' => self::ADDON_POST_BY_POST,
					'name' => __( 'Post by Post', MS_TEXT_DOMAIN ),
					'description' => __( 'Protect content post by post instead of post categories.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_POST_BY_POST ),
				),
				self::ADDON_URL_GROUPS => (object) array(
					'id' => self::ADDON_URL_GROUPS,
					'name' => __( 'Url Groups', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable Url Groups protection.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_URL_GROUPS ),
				),
				self::ADDON_CPT_POST_BY_POST => (object) array(
					'id' => self::ADDON_CPT_POST_BY_POST,
					'name' => __( 'Custom Post Type - Post by post', MS_TEXT_DOMAIN ),
					'description' => __( 'Protect custom post type post by post instead of post type groups.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_CPT_POST_BY_POST ),
				),
				self::ADDON_COUPON => (object) array(
					'id' => self::ADDON_COUPON,
					'name' => __( 'Coupon', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable discount coupons.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_COUPON ),
				),
				self::ADDON_TRIAL => (object) array(
					'id' => self::ADDON_TRIAL,
					'name' => __( 'Trial', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable trial period.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_TRIAL ),
				),
				self::ADDON_MEDIA => (object) array(
					'id' => self::ADDON_MEDIA,
					'name' => __( 'Media', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable media protection.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_MEDIA ),
				),
				self::ADDON_PRIVATE_MEMBERSHIPS => (object) array(
					'id' => self::ADDON_URL_GROUPS,
					'name' => __( 'Private Memberships', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable private memberships.', MS_TEXT_DOMAIN ),
					'active' => $this->is_active( self::ADDON_PRIVATE_MEMBERSHIPS ),
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
				case 'addons':
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}