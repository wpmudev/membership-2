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

class MS_Helper_Plugin extends MS_Helper {
	
	public function __construct() {

	}
	
	public static function create_admin_pages( $view ) {
		$pages = array();

		// $pages[] = add_menu_page( __( 'Membership', MS_TEXT_DOMAIN ), __( 'Membership', MS_TEXT_DOMAIN ), 'membershipadmindashboard', 'membership', array( $this, 'handle_membership_panel' ), membership_url( 'membershipincludes/images/members.png' ) );
		$pages[] = add_menu_page( __( 'Membership', MS_TEXT_DOMAIN ), __( 'Membership', MS_TEXT_DOMAIN ), 'manage_options', 'membership', array( $view, 'render' ) );
		
		$pages[] = add_submenu_page( 'membership', __( 'Members', MS_TEXT_DOMAIN ), __( 'Membership Settings', MS_TEXT_DOMAIN ), 'manage_options', 'membership-settings', array( $view, 'render' ) );
		
		$pages[] = add_submenu_page( 'membership', __( 'Memberships', MS_TEXT_DOMAIN ), __( 'Memberships', MS_TEXT_DOMAIN ), 'manage_options', 'edit.php?post_type=ms_membership' );
	}
	
	
}