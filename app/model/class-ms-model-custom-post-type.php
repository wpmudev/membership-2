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

class MS_Model_Custom_Post_Type extends MS_Model {

	public static $POST_TYPE;
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $title;
	
	protected $description;
	
	protected $user_id;
	
	protected $post_modified;
	
	public function __construct() {
	}

	public function save() {
		
		$this->before_save();
				
		$this->post_modified = date( 'Y-m-d H:i:s' );
		
		$class = get_class( $this );
		$post = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_author' => $this->user_id,
				'post_content' => $this->description,
				'post_excerpt' => $this->description,
				'post_name' => sanitize_title( $this->name ),
				'post_status' => 'private',
				'post_title' => sanitize_text_field( ! empty( $this->title ) ? $this->title : $this->name ),
				'post_type' => $class::$POST_TYPE,
				'post_modified' => $this->post_modified, 
		);
		if ( empty( $this->id ) ) {
			$this->id = wp_insert_post( $post );
		} else {
			$post[ 'ID' ] = $this->id;
			wp_update_post( $post );
		}
		
		// save attributes in postmeta table
		$post_meta = get_post_meta( $this->id );
		
		$fields = get_object_vars( $this );
		foreach ( $fields as $field => $val) {
			if ( in_array( $field, $class::$ignore_fields ) ) {
				continue;
			}
			if ( isset( $this->$field ) && ( !isset( $post_meta[ $field ][ 0 ] ) || $post_meta[ $field ][ 0 ] != $this->$field ) ) {
				update_post_meta( $this->id, $field, $this->$field );
			}
		}
		$this->after_save();
	}

	public function delete() {
		if( ! empty( $this->id ) ) {
			wp_delete_post( $this->id );
		}
	}
	
	/**
	 * Register and Filter the custom post type.
	 *
	 * @since 4.0.0
	 * @param object $this The MS_Plugin object.
	 */
	public static function register_post_type() {
		register_post_type( static::$POST_TYPE, apply_filters( 'ms_register_post_type_' . static::$POST_TYPE, array(
				'public' => false,
				'has_archive' => false,
				'publicly_queryable' => false,
				'supports' => false,
				'capability_type' => apply_filters( static::$POST_TYPE, '_capability', 'page' ),
				'hierarchical' => false
		) ) );
	}
	
	/**
	 * Check to see if the post is currently being edited.
	 *
	 * Based in the wp_check_post_lock.
	 * 
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function check_object_lock() {
		
		$locked = false;
		
		if( $this->is_valid() && $lock = get_post_meta( $this->id, '_ms_edit_lock', true ) ) {
			
			$time = $lock;
			$time_window = apply_filters( 'ms_model_custom_post_type_check_object_lock_window', 150 );
			if ( $time && $time > time() - $time_window ) {			
				$locked = true;
			}
		}

		return $locked;
	}
	
	/**
	 * Mark the object as currently being edited.
	 *
	 * Based in the wp_set_post_lock
	 * 
	 * @since 4.0.0
	 *
	 * @return bool|int
	 */
	public function set_object_lock() {
		
		$lock = false;
		
		if( $this->is_valid() ) {
			$lock = apply_filters( 'ms_model_custom_post_type_set_object_lock', time() );
			update_post_meta( $this->id, '_ms_edit_lock', $lock );
		}

		return $lock;
	}
	
	/**
	 * Delete object lock.
	 *
	 * @since 4.0.0
	 *
	 */
	public function delete_object_lock() {
		if( $this->is_valid() ) {
			update_post_meta( $this->id, '_ms_edit_lock', '' );
		}
	}
	
	/**
	 * Check to see if the current post type exists.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_valid() {
		if ( $this->id > 0 ) {
			return true;
		}
	}
}