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


class MS_Model_Option extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
		
	public function save() {
		
		$this->before_save();
		
		$settings = array();
		
		$fields = get_object_vars( $this );
		foreach ( $fields as $field => $val) {
			if ( in_array( $field, self::$ignore_fields ) ) {
				continue;
			}
			$settings[ $field ] = $this->$field;
		}
// 			$method = ( is_multisite() ) ? 'update_site_option' : 'update_option';
				
		update_option( static::$CLASS_NAME, $settings );
		
		$this->after_save();
	}
	
	public static function load( $model_id = false ) {
// 		$method = ( is_multisite() ) ? 'get_site_option' : 'get_option';
		$settings = get_option( static::$CLASS_NAME );
		
		$model = new static::$CLASS_NAME();
		
		$model->before_load();
		
		$fields = get_object_vars( $model );
		foreach ( $fields as $field => $val) {
			if ( in_array( $field, self::$ignore_fields ) ) {
				continue;
			}
			if( isset( $settings[ $field ] ) ) {
				$model->$field = $settings[ $field ];
			}
		}
		
		$model->after_load();
		
		return $model;	
	}
	
	public function delete() {
		delete_option( static::$CLASS_NAME );
	}
}