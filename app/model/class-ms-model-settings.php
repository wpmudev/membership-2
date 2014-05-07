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


class MS_Model_Settings extends MS_Model_Option {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id =  'ms_plugin_settings';
	
	protected $name = 'Plugin settings';
	
	protected $plugin_enabled = false;
	
	protected $initial_setup;
	
	protected $pages;
	
	protected $show_default_membership;

	public function __construct() {
		$this->add_action( 'wp_loaded', 'create_initial_pages' );	
	}
		
	public function create_initial_pages() {
		if( ! $this->initial_setup ) {
			if( empty( $this->pages['no_access'] ) ) {
				$this->create_no_access_page();
			}
			$this->initial_setup = true;
			$this->save();
		}
	}
	
	public function create_no_access_page() {
		$content = '<p>' . __('The content you are trying to access is only available to members. Sorry.', MS_TEXT_DOMAIN ) . '</p>';
		$pagedetails = array('post_title' => __('Protected Content', MS_TEXT_DOMAIN ), 'post_name' => 'protected', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $content);
		$id = wp_insert_post( $pagedetails );
		$this->pages['no_access'] = $id;
	}

}