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

	protected static $CLASS_NAME = __CLASS__;

	protected $custom_post_type;

	protected $post_modified;
	
	public function __construct() {
	}

	public function save() {
		$this->post_modified = date( 'Y-m-d H:i:s' );
// 		$this->description = $this->custom_post_type;
		$post = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_author' => $this->user_id,
				'post_content' => $this->description,
				'post_excerpt' => $this->description,
				'post_name' => $this->name,
				'post_status' => 'private',
				'post_title' => ! empty( $this->title ) ? $this->name : $this->title,
				'post_type' => $this->custom_post_type,
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
			if ( in_array( $field, self::$ignore_fields ) ) {
				continue;
			}
			if ( isset( $this->$field ) && ( !isset( $post_meta[ $field ][ 0 ] ) || $post_meta[ $field ][ 0 ] != $this->$field ) ) {
				update_post_meta( $this->id, $field, $this->$field );
			}
		}
	}

	/**
	 * Loads post and postmeta into a object.
	 * 
	 * @param int $model_id
	 * @return MS_Model_Custom_Post_Type
	 */
	public static function load( $model_id ) {
		$model = null;
		if ( !empty( $model_id ) ) {
			$model = new static::$CLASS_NAME();
			
			$post = get_post( $model_id );
			$model->id = $model_id;
			$model->name = $post->post_name;
			$mode->description = $post->post_content;
			
			$model_details = get_post_meta( $model_id );
			$fields = get_object_vars( $model );
			foreach ( $fields as $field => $val) {
				if ( in_array( $field, self::$ignore_fields ) ) {
					continue;
				}
				if ( isset( $model_details[ $field ][ 0 ] ) ) {
					$model->$field = maybe_unserialize( $model_details[ $field ][ 0 ] );
				}
			}
		}
		return $model;
	}
}