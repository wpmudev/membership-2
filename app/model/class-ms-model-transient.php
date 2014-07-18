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


class MS_Model_Transient extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
		
	public function save() {
		$settings = array();
		
		$fields = get_object_vars( $this );
		foreach ( $fields as $field => $val) {
			if ( in_array( $field, self::$ignore_fields ) ) {
				continue;
			}
			$settings[ $field ] = $this->$field;
		}
		set_transient( static::$CLASS_NAME, $settings );
	}
	
	public static function load( $model_id = false ) {
		if( static::$instance ) {
			return static::$instance;
		}
		
		$settings = get_transient( static::$CLASS_NAME );
		
		$model = new static::$CLASS_NAME();
		$fields = get_object_vars( $model );
		foreach ( $fields as $field => $val) {
			if ( in_array( $field, self::$ignore_fields ) ) {
				continue;
			}
			if( isset( $settings[ $field ] ) ) {
				$model->$field = $settings[ $field ];
			}
		}
		return $model;	
	}
}