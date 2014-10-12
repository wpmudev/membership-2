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

/**
 * Plugin Page model.
 *
 * @since 1.0.0
 * 
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Page extends MS_Model {

	protected $id;
	
	protected $title;
	
	protected $type;
	
	protected $slug;
	
	public function create_wp_page( $virtual = true ) {
		
		$page_details = apply_filters(
				'ms_model_settings_create_' . $this->type,
				array(
						'post_title' => $this->title,
						'post_name' => $this->title,
						'post_status' => ( $virtual ) ? 'virtual' : 'publish',
						'post_type' => 'page',
						'ping_status' => 'closed',
						'comment_status' => 'closed',
						'post_content' => '',
				)
		);
		$id = wp_insert_post( $page_details );
		$this->id = $id;
	}
	
	/**
	 * Set specific property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			
			switch ( $property ) {
				case 'id':
					$this->$property = $this->validate_min( $value, 0 );
					break;
				case 'slug':
					$this->$property = sanitize_title( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}