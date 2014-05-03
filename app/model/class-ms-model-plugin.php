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


class MS_Model_Plugin extends MS_Model {
	
	private $member;
	
	public function __construct() {
		
		$this->init_member();
		
		$this->add_action( 'plugins_loaded', 'check_membership_status' );
		$this->add_action( 'template_redirect', 'protect_current_page', 1 );
		$this->add_action( 'parse_request', 'setup_protection', 2 );
	}
	
	public function init_member() {
		$this->member = MS_Model_Member::get_current_member();
		
		/** Visitor: assign a Visitor Membership */
		if( ! $this->member->is_logged_user() ){
			$this->member->add_membership( MS_Model_Membership::get_visitor_membership()->id );
		}
		
		/** Logged user with no memberships: assign default Membership */
		elseif( $this->member->is_logged_user() && ! $this->member->is_member() ) {
			//TODO
		}
	}
	
	/**
	 * Checks whether curren member is active or not. 
	 * 
	 * If member is deactivated, then he has to be logged out immediately.
	 *
	 * @since 4.0.0
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function check_membership_status() {
		
	}

	/**
	 * Checks member permissions and protects current page.
	 *
	 * @since 4.0.0
	 * @action template_redirect 1
	 *
	 * @access public
	 */
	public function protect_current_page() {

		/** Admin user has access to everything */
		if( $this->member->is_admin_user() ) {
			return true;
		}
		
		$can_view = false;
		foreach( $this->member->membership_relationships as $membership_relationship ) {
			$membership = $membership_relationship->get_membership();
			foreach( $membership->rules as $rule ) {
				$can_view = ( $can_view || $rule->can_view_current_page() );
			}
		}
		
		if( ! $can_view ) {
			//TODO redirect to protected page
		}

	}
	/**
	 * Setup initial protection.
	 *
	 * Hide menu and pages in the front end.
	 * Protect feeds.
	 * 
	 * @since 4.0.0
	 * @action parse_request
	 *
	 * @access public
	 * @param WP $wp Instance of WP class.
	 */
	public function setup_protection( WP $wp ){
		
	}
}