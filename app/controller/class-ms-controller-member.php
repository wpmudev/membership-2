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

class MS_Controller_Member extends MS_Controller {

	/**
	 * Instance of MS_Model_Member.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _model
	 */
	private $_model;
	
	/**
	 * Instance of MS_View_Member.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _view
	 */
	private $_view;	
	
	public function __construct() {
		$this->_model = new MS_Model_Member();
		$this->_view = new MS_View_Member();
	}
	
	
}