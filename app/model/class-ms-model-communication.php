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

/**
 * Communicataion model class.
 * 
 */
class MS_Model_Communication extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_communication';
	
	const COMM_TYPE_REGISTRATION = 'type_registration';
	
	const COMM_TYPE_INVOICE = 'type_invoice';
	
	const COMM_TYPE_FINISH_SOON = 'type_finish_soon';
	
	const COMM_TYPE_FINISHED = 'type_finished';
	
	const COMM_TYPE_CANCELLED = 'type_cancelled';
	
	const COMM_TYPE_TRIAL_FINISH_SOON = 'type_trial_finish_soon';
	
	const COMM_TYPE_BILL_INFO_UPDATE = 'type_bill_info_update';
	
	const COMM_TYPE_CREDIT_CARD_EXPIRE = 'type_credit_card_expire';
	
	const COMM_TYPE_FAILED_PAYMENT = 'type_failed_payment';
	
	const COMM_TYPE_BEFORE_PAYMENT_DUE = 'type_before_payment_due';
	
	const COMM_TYPE_AFTER_PAYMENT_MADE = 'type_after_payment_made';
	
	protected $type;
	
	protected $subject;
	
	protected $message;
	
	protected $period;
	
	protected $for_membership_ids;
	
	protected static $ignore_fields = array( 'subject', 'message', 'actions', 'filters' );
	
	/**
	 * Communication types.
	 *
	 */
	public static function get_communication_types() {
		return apply_filters( 'ms_model_communication_get_communication_types', array(
				self::COMM_TYPE_REGISTRATION,
				self::COMM_TYPE_INVOICE,
				self::COMM_TYPE_FINISH_SOON,
				self::COMM_TYPE_FINISHED,
				self::COMM_TYPE_CANCELLED,
				self::COMM_TYPE_TRIAL_FINISH_SOON,
				self::COMM_TYPE_BILL_INFO_UPDATE,
				self::COMM_TYPE_CREDIT_CARD_EXPIRE,
				self::COMM_TYPE_FAILED_PAYMENT,
				self::COMM_TYPE_BEFORE_PAYMENT_DUE,
				self::COMM_TYPE_AFTER_PAYMENT_MADE,
			)
		);
	}
	
	public static function is_valid_communication_type( $type ) {
		return apply_filters( 'ms_model_communication_is_valid_communication_type', in_array( $type, self::get_communication_types() ) );
	}
	
	public static function get_communication( $type ) {
		
		if( ! self::is_valid_communication_type( $type ) ) {
			return null;
		}
		
		$args = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
				'meta_query' => array(
						array(
								'key' => 'type',
								'value' => $type,
								'compare' => '='
						)
				)
		);
		$query = new WP_Query($args);
		$item = $query->get_posts();
	
		$comm = null;
		if( ! empty( $item[0] ) ) {
			$comm = self::load( $item[0]->ID );
		}
		else {
			$comm = self::create_default_communication( $type );
		}
		return $comm;
	}
	
	public static function create_default_communication( $type ) {
		
	}
	
	public function save() {
		$this->name = $this->subject;
		$this->description = $this->message;
		parent::save();
	}
	
	public static function load( $model_id ) {
		$model = parent::load( $model_id );
		$model->subject = $model->name;
		$model->message = $model->description;
		return $model;
	}
}