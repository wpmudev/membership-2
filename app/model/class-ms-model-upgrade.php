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
		
	public static function upgrade() {
		$settings = MS_Plugin::instance()->settings;
		/** Compare current src version to DB version */
		if ( version_compare( MS_Plugin::instance()->version, $settings->version, '>' ) ) {
			switch( $settings->version ) {
				case '4.0.0.0.2':
					break;
				case '4.0.0.0.0':
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
		$transactions = MS_Model_Transaction::get_transactions( array( 'posts_per_page' => -1 ) );
		foreach( $transactions as $transaction ) {
			$transaction->delete();
		}
		$gateways = MS_Model_Gateway::get_gateways();
		foreach( $gateways as $gateway ) {
			$gateway->delete();
		}
		$coupons = MS_Model_Coupon::get_coupons( array( 'posts_per_page' => -1 ) );
		foreach( $coupons as $coupon ) {
			$coupon->delete();
		}
		$news = MS_Model_News::get_news( array( 'posts_per_page' => -1 ) );
		foreach( $news as $new ) {
			$new->delete();
		}
		$settings = MS_Plugin::instance()->settings;
		$settings->tax = array( 'tax_name' => false, 'tax_rate' => false );
		$settings->save();
		
		$simulate = MS_Model_Simulate::load();
		$simulate->reset_simulation();
	}
}