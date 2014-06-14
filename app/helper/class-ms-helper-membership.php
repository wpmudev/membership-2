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

class MS_Helper_Membership extends MS_Helper {
	
	const MEMBERSHIP_MSG_ADDED = 1;
	const MEMBERSHIP_MSG_DELETED = 2;
	const MEMBERSHIP_MSG_UPDATED = 3;
	const MEMBERSHIP_MSG_ACTIVATION_TOGGLED = 4;
	const MEMBERSHIP_MSG_STATUS_TOGGLED = 5;
	const MEMBERSHIP_MSG_BULK_UPDATED = 6;
	const MEMBERSHIP_MSG_DRIPPED_COPIED = 7;
	const MEMBERSHIP_MSG_NOT_ADDED = -1;
	const MEMBERSHIP_MSG_NOT_DELETED = -2;
	const MEMBERSHIP_MSG_NOT_UPDATED = -3;
	const MEMBERSHIP_MSG_ACTIVATION_NOT_TOGGLED = -4;
	const MEMBERSHIP_MSG_STATUS_NOT_TOGGLED = -5;
	const MEMBERSHIP_MSG_BULK_NOT_UPDATED = -6;
	const MEMBERSHIP_MSG_DRIPPED_NOT_COPIED = -7;
	const MEMBERSHIP_MSG_PARTIALLY_UPDATED = -8;
	
	public static function get_admin_message( $msg = 0 ) {
	
		$messages = apply_filters( 'ms_helper_membership_get_admin_messages', array(
				self::MEMBERSHIP_MSG_ADDED => __( 'Membership added.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_DELETED => __( 'Membership deleted.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_UPDATED => __( 'Membership updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_ACTIVATION_TOGGLED => __( 'Membership activation toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_STATUS_TOGGLED => __( 'Membership status toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_BULK_UPDATED => __( 'Memberships bulk updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_DRIPPED_COPIED => __( 'Memberships dripped schedule copied.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_NOT_ADDED => __( 'Membership not added.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_NOT_DELETED => __( 'Membership not deleted.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_NOT_UPDATED => __( 'Membership not updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_ACTIVATION_NOT_TOGGLED => __( 'Membership activation not toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_STATUS_NOT_TOGGLED => __( 'Membership status not toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_BULK_NOT_UPDATED => __( 'Memberships bulk not updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_DRIPPED_NOT_COPIED => __( 'Memberships dripped schedule not copied.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_PARTIALLY_UPDATED => __( 'Memberships partially updated. Some fields could not be changed after members have signed up.', MS_TEXT_DOMAIN ),
			)
		);
	
		if ( array_key_exists( $msg, $messages ) ) {
			return $messages[ $msg ];
		}
		return null;
	}
	
	public static function print_admin_message() {
		$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;

		$class = ( $msg > 0 ) ? 'updated' : 'error';
		
		if ( $msg = MS_Helper_Membership::get_admin_message( $msg ) ) {
			echo "<div id='message' class='$class'><p>$msg</p></div>";
		}
		
	}
	
}