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

class MS_Controller_Addon extends MS_Controller {

	private $post_type;
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $views;
		
	public function __construct() {
		$this->add_action( 'load-membership_page_membership-addons', 'membership_addon_manager' );
		$this->model = apply_filters( 'membership_addon_model', MS_Model_Addon::load() );
	}
	
	public function membership_addon_manager() {
		$msg = 0;
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['addon'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$msg = $this->save_addon( $_GET['action'], array( $_GET['addon'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'addon', 'action', '_wpnonce' ) ) ) ) ;
		}
		elseif( ! empty( $_POST['addon'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-addons' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->save_addon( $action, $_POST['addon'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
		}
	}
	/**
	 * Menu add-ons.
	 * 
	 */
	public function admin_addon() {
		$this->views['addon'] = apply_filters( 'membership_addon_view', new MS_View_Addon() );
		$this->views['addon']->model = $this->model;
		$this->views['addon']->render();
	}
	
	public function save_addon( $action, $addons ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		foreach( $addons as $addon ) {
			if( 'enable' == $action ) {
				$this->model->$addon = true;
			}
			elseif ( 'disable' == $action ) {
				$this->model->$addon = false;
			}
		}
		$this->model->save();
	}	
	
	
}