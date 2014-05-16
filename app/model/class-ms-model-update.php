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


class MS_Model_Update extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id =  'ms_model_update';
	
	protected $name = 'Update DB';
		
	public static function update() {
		$settings = MS_Plugin::instance()->settings;
		/** Compare current src version to DB version */
		if( MS_Plugin::instance()->version > $settings->version ) {
			switch( $settings->version ) {
				default:
					self::cleanup_db();
					break;
			}
		}
		$settings->version = MS_Plugin::instance()->version;
		$settings->save();
	}
	private static function cleanup_db() {
		$users = MS_Model_Member::get_members( );
		foreach( $users as $user ) {
			$user->delete_all_membership_usermeta();
			$user->save();
		}
		$memberships = MS_Model_Membership::get_memberships( array( 'posts_per_page' => 999 ) );
		foreach( $memberships as $membership ) {
			$membership->delete( true );
		}
		$comms = MS_Model_Communication::load_communications();
		foreach( $comms as $comm ) {
			$comm->delete();
		}
		//TODO delete simulation		
	}
}