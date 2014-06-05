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
	
	protected $id =  'addon_options';
	
	protected $name = 'Add-on Options';
	
	protected $multiple_membership = false;
	
	protected $post_by_post = false;
	
	protected $url_groups = false;
	
	protected $cpt_post_by_post = false;
	
	public function get_addon_list() {
		return apply_filters( 'ms_model_addon_get_addon_list', array( 
				'multiple_membership' => (object) array(
					'id' => 'multiple_membership',
					'name' => __( 'Multiple Memberships', MS_TEXT_DOMAIN ), 	
					'description' => __( 'Allow members to join multiple memberships.', MS_TEXT_DOMAIN ),
					'active' => $this->multiple_membership, 	
				),
				'post_by_post' => (object) array(
					'id' => 'post_by_post',
					'name' => __( 'Post by Post', MS_TEXT_DOMAIN ),
					'description' => __( 'Protect content post by post instead of post categories.', MS_TEXT_DOMAIN ),
					'active' => $this->post_by_post,
				),
				'url_groups' => (object) array(
					'id' => 'url_groups',
					'name' => __( 'Url Groups', MS_TEXT_DOMAIN ),
					'description' => __( 'Enable Url Groups protection.', MS_TEXT_DOMAIN ),
					'active' => $this->url_groups,
				),
				'cpt_post_by_post' => (object) array(
					'id' => 'cpt_post_by_post',
					'name' => __( 'Custom Post Type - Post by post', MS_TEXT_DOMAIN ),
					'description' => __( 'Protect custom post type post by post instead of post type groups.', MS_TEXT_DOMAIN ),
					'active' => $this->cpt_post_by_post,
				),
			)
		);
	}
}