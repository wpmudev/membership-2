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

	private $simulate_membership_id;
	
	public function __construct() {
		
		$this->init_member();
		
		$this->add_action( 'plugins_loaded', 'check_membership_status' );
		$this->add_action( 'template_redirect', 'protect_current_page', 1 );
		$this->add_action( 'parse_request', 'setup_protection', 2 );
	}
	
	public function init_member() {
		$this->member = MS_Model_Member::get_current_member();
		
		/** Admin user simulating membership */
		if( $this->member->is_admin_user() && ! empty( $_COOKIE[ MS_Controller_Admin_Bar::MS_SIMULATE_COOKIE ] ) && 
			( $membership_id = absint( $_COOKIE[ MS_Controller_Admin_Bar::MS_SIMULATE_COOKIE ] ) ) ) {
			$this->member->add_membership( $membership_id );
			$this->simulate_membership_id = $membership_id;
			if( ! empty( $_COOKIE[ MS_Controller_Admin_Bar::MS_PERIOD_COOKIE ] ) ) {
				$period = explode( ';', $_COOKIE[ MS_Controller_Admin_Bar::MS_PERIOD_COOKIE ] );
				if( count( $period ) == 2 ) {
					$membership_relationships = $this->member->membership_relationships;
					$membership_relationships[ $membership_id ]->set_elapsed_period( $period[0], $period[1] );
					$this->member->membership_relationships = $membership_relationships;
				}
			}
			elseif( ! empty( $_COOKIE[ MS_Controller_Admin_Bar::MS_DATE_COOKIE ] ) ) {
				$membership_relationships = $this->member->membership_relationships;
				$membership_relationships[ $membership_id ]->set_elapsed_date( $_COOKIE[ MS_Controller_Admin_Bar::MS_DATE_COOKIE ] );
				$this->member->membership_relationships = $membership_relationships;
			}
			
		}
		/** Visitor: assign a Visitor Membership */
		elseif( ! $this->member->is_logged_user() ){
			$this->member->add_membership( MS_Model_Membership::get_visitor_membership()->id );
		}
		/** Logged user with no memberships: assign default Membership */
		elseif( $this->member->is_logged_user() && ! $this->member->is_member() ) {
			$this->member->add_membership( MS_Model_Membership::get_default_membership()->id );
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
		if( $this->member->is_admin_user() && ! $this->simulate_membership_id ) {
			return true;
		}
		$settings = MS_Plugin::instance()->settings;
		$addon = MS_Plugin::instance()->addon;
		$has_access = false;
		
		/**
		 * Search permissions through all memberships joined.
		 * Rules are sorted in a hierarchy way. First one has priority.
		 * If 'has access' is found, it does have access
		 */
		foreach( $this->member->membership_relationships as $membership_relationship ) {
			/**
			 * Verify status of the membership.
			 * Only active or trial memberships.
			 */
			if( ! $this->member->is_member( $membership_relationship->membership_id ) ) {
				continue;
			}
			/** Verify membership rules hierachyly 
			 * @todo implement better hierachy verifying which content type it is.
			 */
			$membership = $membership_relationship->get_membership();
			foreach( $membership->rules as $rule ) {
				$has_access = ( $has_access || $rule->has_access( $membership_relationship ) );
				
				if( $has_access ) {
					break 2;
				}
			}
		}
		
		if( ! $has_access ) {
			$url = get_permalink( $settings->pages['no_access'] );
			wp_safe_redirect( $url );
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