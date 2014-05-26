<?php
/**
 * This file defines the MS_Controller_Coupon class.
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
 * Controller to manage Membership coupons.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Coupon extends MS_Controller {

	/**
	 * The custom post type used with Coupons.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $post_type
	 */
	private $post_type;

	/**
	 * Capability required to manage Coupons.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */	
	private $capability = 'manage_options';

	/**
	 * The model to use for loading/saving coupon data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * View to use for rendering coupon settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;

	/**
	 * Prepare the Coupon manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		/** Menu: Coupons */
		$this->views['coupon'] = apply_filters( 'membership_coupon_view', new MS_View_Coupon() );			
	}
	
	/**
	 * Render the Coupon admin manager.
	 *
	 * @since 4.0.0
	 */	
	public function admin_coupon() {
		$this->views['coupon']->render();
	}

}