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

class MS_Model_Transaction extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_transaction';
	
	protected static $CLASS_NAME = __CLASS__;
		
	protected $amount;
	
	protected $currency;
	
	protected $status;
	
	protected $due_date;
	
	protected $notes;
	
	protected $expire_date;
	
	protected $invoice;
	
	protected $taxable;
	
	protected $tax_rate;
	
	protected $tax_description;

	public function __construct() {
	}
	
}