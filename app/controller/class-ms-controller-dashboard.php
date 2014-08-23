<?php
/**
 * This file defines the MS_Controller_Dashboard class.
 * 
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
 * Controller for Membership Dashboard.
 *
 * Manages Membership over-view, statistics, reports.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Dashboard extends MS_Controller {

	/**
	 * Views to use for rendering Membership Dashboard.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;

	/**
	 * Prepare Membership Dashboard.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		
	}

	/**
	 * Render Membership Dashboard admin page.
	 * 
	 * Menu Dashboard
	 *
	 * @since 4.0.0
	 */	
	public function admin_dashboard() {
		$data = array();
		$data['events'] = MS_Model_Event::get_events();
		$data['plugin_enabled'] = MS_Plugin::instance()->settings->plugin_enabled;
		$data['members_count'] = MS_Model_Member::get_members_count();
		$memberships = MS_Model_Membership::get_membership_names( null, true );
		foreach( $memberships as $id => $name ) {
			$data['memberships'][ $id ] = array( 
					'name' => $name, 
					'count' => MS_Model_Membership_Relationship::get_membership_relationship_count( array( 'membership_id' => $id ) ) 
			);
		}
		$this->views['dashboard'] = apply_filters( 'ms_view_dashboard', new MS_View_Dashboard() );
		$this->views['dashboard']->data = apply_filters( 'ms_view_dashboard_data', $data ); 
		$this->views['dashboard']->render();
	}
	
}