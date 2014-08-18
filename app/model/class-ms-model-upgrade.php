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


class MS_Model_Upgrade extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id =  'ms_model_update';
	
	protected $name = 'Update DB';

	protected $allowed_actions = array( 'cleanup_db', 'cleanup_settings' );
	
	public function __construct() {
		$this->add_action( 'template_redirect', 'process_actions', 1 );	
	}
	
	public static function init() {
		self::upgrade();
		new self();
	}
	
	
	/**
	 * Handle URI actions for registration.
	 *
	 * Matches returned 'action' to method to execute.
	 *
	 * **Hooks Actions: **
	 *
	 * * template_redirect
	 *
	 * @todo Sanitize and protect from possible random function calls.
	 *
	 * @since 4.0.0
	 */
	public function process_actions() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		/**
		 * If $action is set, then call relevant method.
		 */
		if( ! empty( $action ) && method_exists( $this, $action ) && in_array( $action, $this->allowed_actions ) ) {
			$this->$action();
		}
	}
	
	public static function upgrade() {
		$settings = MS_Plugin::instance()->settings;
		/** Compare current src version to DB version */
		if ( version_compare( MS_Plugin::instance()->version, $settings->version, '>' ) ) {
			switch( $settings->version ) {
				default:
					self::cleanup_db();
					flush_rewrite_rules();
					break;
			}
			$settings->version = MS_Plugin::instance()->version;
			$settings->save();
		}
	}
	
	private static function cleanup_db() {
		$users = MS_Model_Member::get_members( );
		foreach( $users as $user ) {
			$user->delete_all_membership_usermeta();
			$user->save();
		}
		$memberships = MS_Model_Membership::get_memberships( array( 'posts_per_page' => -1 ) );
		foreach( $memberships as $membership ) {
			$membership->delete( true );
		}
		$comms = MS_Model_Communication::load_communications();
		foreach( $comms as $comm ) {
			$comm->delete();
		}
		$membership_relationships = MS_Model_Membership_Relationship::get_membership_relationships( array( 'status' => 'all' ) );
		foreach( $membership_relationships as $membership_relationship ) {
			$membership_relationship->delete();
		}
		$invoices = MS_Model_Invoice::get_invoices( array( 'posts_per_page' => -1 ) );
		foreach( $invoices as $invoice ) {
			$invoice->delete();
		}
		$coupons = MS_Model_Coupon::get_coupons( array( 'posts_per_page' => -1 ) );
		foreach( $coupons as $coupon ) {
			$coupon->delete();
		}
		$events = MS_Model_Event::get_events( array( 'posts_per_page' => -1 ) );
		foreach( $events as $event ) {
			$event->delete();
		}
		self::cleanup_settings();
		
	}
	
	private static function cleanup_settings() {
		$gateways = MS_Model_Gateway::get_gateways();
		foreach( $gateways as $gateway ) {
			$gateway->delete();
		}
		$settings = MS_Factory::get_factory()->load_settings();
		$settings->tax = array( 'tax_name' => false, 'tax_rate' => false );
		$settings->save();
		
		$simulate = MS_Factory::get_factory()->load_simulate();
		$simulate->reset_simulation();
		
	}
}