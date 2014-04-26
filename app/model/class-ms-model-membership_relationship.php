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

class MS_Model_Membership_Relationship extends MS_Model {
	
	const MEMBERSHIP_STATUS_ACTIVE = 'active';
	
	const MEMBERSHIP_STATUS_TRIAL = 'trial';

	const MEMBERSHIP_STATUS_EXPIRED = 'expired';
	
	const MEMBERSHIP_STATUS_DEACTIVATED = 'deactivated';

	protected $membership_id;
	
	protected $start_date;
	
	protected $expire_date;
	
	protected $update_date;
	
	protected $trial_expire_date;
	
	protected $gateway;
	
	protected $status;
	
	public function __construct( $membership_id, $gateway ) {
		
		$membership = MS_Model_Membership::load( $membership_id );
		
		$this->membership_id = $membership_id;
		$this->start_date = MS_Helper_Period::current_date();
		$this->update_date = MS_Helper_Period::current_date();
		$this->trial_expire_date = $membership->get_trial_expire_date();
		$this->expire_date = $membership->get_expire_date();
		$this->gateway = $gateway;
		$this->status = ( $membership->trial_period_enabled )
			? MS_Model_Membership_Relationship::MEMBERSHIP_STATUS_TRIAL
			: MS_Model_Membership_Relationship::MEMBERSHIP_STATUS_ACTIVE;
		
	}
	
	public function get_membership() {
		return MS_Model_Membership::load( $this->membership_id );
	}
}