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

		$post = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_author' => $this->user_id,
				'post_content' => $this->description,
				'post_excerpt' => $this->description,
				'post_name' => sanitize_title( $this->name ),
				'post_status' => 'private',
				'post_title' => sanitize_text_field( ! empty( $this->title ) ? $this->title : $this->name ),
				'post_type' => static::$POST_TYPE,
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
			if ( in_array( $field, static::$ignore_fields ) ) {
				continue;
			}
			if ( isset( $this->$field ) && ( !isset( $post_meta[ $field ][ 0 ] ) || $post_meta[ $field ][ 0 ] != $this->$field ) ) {
				update_post_meta( $this->id, $field, $this->$field );
			}
		}
		$this->after_save();
	}

	/**
	 * Loads post and postmeta into a object.
	 * 
	 * @param int $model_id
	 * @return MS_Model_Custom_Post_Type
	 */
	public static function load( $model_id ) {
		$model = new static::$CLASS_NAME();
		
		$model->before_load( $model_id );
		
		if ( !empty( $model_id ) ) {
			
			$post = get_post( $model_id );
			$model->id = $model_id;
			if( ! empty( $post ) ) {
				$model->name = ! empty( $post->post_title ) ? $post->post_title : $post->post_name;
				$model->title = ! empty( $post->post_title ) ? $post->post_title : $post->post_name;
				$model->description = $post->post_content;
				$model_details = get_post_meta( $model_id );
				$fields = get_object_vars( $model );
				foreach ( $fields as $field => $val) {
					if ( in_array( $field, static::$ignore_fields ) ) {
						continue;
					}
					if ( isset( $model_details[ $field ][ 0 ] ) ) {
						$model->$field = maybe_unserialize( $model_details[ $field ][ 0 ] );
					}
				}
			}
		}
		
		$model->after_load();
		return $model;
	}
	
	public function delete() {
		if( ! empty( $this->id ) ) {
			wp_delete_post( $this->id );
		}
	}
}