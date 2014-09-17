<?php
/**
 * This file defines the MS_Factory object.
 *
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
 * Factory class for all Models.
 *
 * @since 4.0.0
 *
 * @package Membership
 */
class MS_Factory {
	
	protected static $instance;
	
	/**
	 * Get factory singleton.
	 * @return MS_Factory
	 */
	public static function get_factory() {
		if( empty( self::$instance )  ){
			self::$instance = new self();
		}
		return apply_filters( 'ms_factory_get_factory', self::$instance );
	}
	
	/**
	 * Create an MS Object.
	 *
	 * @since 1.0
	 *
	 * @param string $class
	 */
	public static function create( $class ) {

		$obj = new $class();
		
		return apply_filters( 'ms_factory_create_'. $class, $obj );
	}
	
	/**
	 * Load an MS Object.
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $class
	 * @param int $model_id
	 */
	public static function load( $class, $model_id = 0 ) {
		$model = null;

		if( class_exists( $class ) && $model = new $class() ) {
			if( $model instanceof MS_Model_Option ) {
				$model = self::load_from_wp_option( $class );
			}
			elseif( $model instanceof MS_Model_Custom_Post_Type ) {
				$model = self::load_from_wp_custom_post_type( $class, $model_id );
			}
			elseif( $model instanceof MS_Model_Member ) {
				$args = func_get_args();

				$name = null;
				if( ! empty( $args[2] ) ) {
					$name = $args[2];
				}
				$model = self::load_from_wp_user( $class, $model_id, $name );
			}
			elseif( $model instanceof MS_Model_Transient ) {
				$model = self::load_from_wp_transient( $class, $model_id );
			}
		}

		return apply_filters( 'ms_factory_load_'. $class, $model, $model_id );
	}
	
	/**
	 * Load an option object.
	 * 
	 * Option objects are singletons.
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $class The class name.
	 * @return MS_Model_Option
	 */
	protected static function load_from_wp_option( $class ) {
	
		$model = new $class();
		
		if( empty( $model->instance ) ) {
			
			$model->before_load();
			
			$cache = wp_cache_get( $class, 'MS_Model_Option' );
			
			if( $cache ) {
				$model = $cache;
			}
			else {
				$settings = get_option( $class );
			
				$fields = $model->get_object_vars();
				foreach ( $fields as $field => $val) {
					if ( in_array( $field, $model->ignore_fields ) ) {
						continue;
					}
					if( isset( $settings[ $field ] ) ) {
						$model->set_field( $field, $settings[ $field ] );
					}
				}
			}
			
			$model->after_load();
		
			$model->instance = $model;
		}
		else {
			$model = $model->instance;
		}
		
		return apply_filters( 'ms_factory_load_from_wp_option', $model, $class );
	}
	
	/**
	 * Load a transient object.
	 *
	 * Transient objects are singletons.
	 *
	 * @since 4.0.0
	 * 
	 * @param string $class The class name.
	 * @return $class Loaded Object
	 */
	public static function load_from_wp_transient( $class ) {
		$model = new $class();
		
		if( empty( $model->instance )  ) {
			
			$model->before_load();
			
			$cache = wp_cache_get( $class, 'MS_Model_Transient' );
			
			if( $cache ) {
				$model = $cache;
			}
			else {
				$settings = get_transient( $class );
				
				$fields = $model->get_object_vars();
				foreach ( $fields as $field => $val) {
					if ( in_array( $field, $model->ignore_fields ) ) {
						continue;
					}
					if( isset( $settings[ $field ] ) ) {
						$model->set_field( $field, $settings[ $field ] );
					}
				}
			
				$model->after_load();
			
				$model->instance = $model;
			}
		}
		else {
			$model = $model->instance;
		}
		
		return apply_filters( 'ms_factory_load_from_wp_transient', $model, $class );
	}
	
	/**
	 * Loads post and postmeta into a object.
	 *
	 * @since 4.0.0
	 *
	 * @param string $class The class name.
	 * @param int $model_id
	 * @return $class Loaded Object
	 */
	protected static function load_from_wp_custom_post_type( $class, $model_id = 0 ) {
		$model = new $class();
	
		$model->before_load();
	
		if ( ! empty( $model_id ) ) {

			$cache = wp_cache_get( $model_id, $class );
				
			if( $cache ) {
				$model = $cache;
			}
			else {
				$post = get_post( $model_id );
				if( ! empty( $post ) && $model->post_type == $post->post_type ) {
					$post_meta = get_post_meta( $model_id );
					
					$fields = $model->get_object_vars();
					foreach ( $fields as $field => $val) {
						if ( in_array( $field, $model->ignore_fields ) ) {
							continue;
						}
						if ( isset( $post_meta[ $field ][ 0 ] ) ) {
							$model->set_field( $field, maybe_unserialize( $post_meta[ $field ][ 0 ] ) );
						}
					}
					
					$model->id = $post->ID;
					$model->description = $post->post_content;
					$model->user_id = $post->post_author;
				}
			}
		}
		
		$model->after_load();

		return apply_filters( 'ms_factory_load_from_custom_post_type', $model, $class, $model_id );
	}
	
	/**
	 * Load user and user meta into a object.
	 *
	 * @since 4.0.0
	 *
	 * @param string $class The class name.
	 * @param int $user_id
	 * @return $class Loaded object
	 */
	protected static function load_from_wp_user( $class, $user_id, $name = null ) {
		$member = new $class();
		
		$cache = wp_cache_get( $user_id, $class );
		
		if( $cache ) {
			$member = $cache;
		}
		else {
			$wp_user = new WP_User( $user_id, $name );
			if( ! empty( $wp_user->ID ) ) {
				$member_details = get_user_meta( $user_id );
				$member->id = $wp_user->ID;
				$member->username = $wp_user->user_login;
				$member->email = $wp_user->user_email;
				$member->name = $wp_user->user_nicename;
				$member->first_name = $wp_user->first_name;
				$member->last_name = $wp_user->last_name;
			
				$member->is_admin = $member->is_admin_user( $user_id );
			
				$fields = $member->get_object_vars();
				foreach( $fields as $field => $val ) {
					if( in_array( $field, $member->ignore_fields ) ) {
						continue;
					}
					if( isset( $member_details[ "ms_$field" ][0] ) ) {
						$member->set_field( $field, maybe_unserialize( $member_details[ "ms_$field" ][0] ) );
					}
				}
				
				/**
				 * Load membership_relationships
				 */
				$member->ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships( array( 'user_id' => $member->id ) );
			}
		}
		
		return apply_filters( 'ms_factory_load_from_wp_user', $member, $class, $user_id );
	}
	
	/**
	 * Magic method
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $method
	 * @param array $args
	 * @return 
	 */
	public function __call( $method, $args ) {
		/** Magic method for all load_x() */
		if( 0 === strpos( $method, 'load_' ) ) {
			$parts = str_replace( 'load_', '', $method );
			$parts = explode( '_', $parts );
			$name = array();
			foreach( $parts as $part ) {
				$name[] = ucwords( $part );
			}
			$name = implode( '_', $name );
			$class ="MS_Model_$name";

			$class = apply_filters( 'ms_factory_load_class', $class );
			$model = self::load( $class, implode( ',', $args ) );
			return apply_filters( "ms_factory_$method" , $model );
		}
	}
	
	/**
	 * Custom load membership.
	 * 
	 * @since 4.0.0
	 * 
	 * @param int $model_id
	 * @return MS_Model_Membership
	 */
	public function load_membership( $model_id = 0 ) {
		$class = apply_filters( 'ms_factory_load_membership_class', 'MS_Model_Membership' );
		$model = self::load( $class, $model_id );
	
		if( empty( $model->rules ) ) {
			$model->rules = MS_Model_Rule::rule_set_factory( $model->rules, $this->id );
		}

		return apply_filters( "ms_factory_load_membership", $model );
	}
}