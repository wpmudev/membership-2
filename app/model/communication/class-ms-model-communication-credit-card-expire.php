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
class MS_Model_Communication_Credit_Card_Expire extends MS_Model_Communication {
	
	public static $POST_TYPE = 'ms_communication';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $type = self::COMM_TYPE_CREDIT_CARD_EXPIRE;
	
	public function after_load() {
	
		parent::after_load();
		
		if( $this->enabled ) {
			$this->add_action( 'ms_model_event_' . MS_Model_Event::TYPE_CREDIT_CARD_EXPIRE, 'enqueue_messages', 10, 2 );
		}
	}
	
	public function get_description() {
		return __( "A notice to indicate that the member's credit card is about to expire.", MS_TEXT_DOMAIN );
	}
	
	public static function create_default_communication() {
		$model = new self();
	
		$model->subject = __( 'Your credit card is about to expire', MS_TEXT_DOMAIN );
		$model->message = self::get_default_message();
		$model->enabled = true;
		$model->period_enabled = true;
		$model->save();
	
		return $model;
	}
	
	public static function get_default_message() {
		ob_start();
		?>
			<h2>Hi <?php echo self::COMM_VAR_USERNAME; ?>,</h2>
			<br /><br />
			This is a reminder that your credit card is about to expire.
			<br /><br />
			To continue your <?php echo self::COMM_VAR_MS_NAME; ?> membership at <?php echo self::COMM_VAR_BLOG_NAME; ?>, please update your card details before your next payment is due here: <?php echo self::COMM_VAR_MS_ACCOUNT_PAGE_URL; ?>.
		<?php 
		$html = ob_get_clean();
		return apply_filters( 'ms_model_communication_credit_card_expire_get_default_message', $html );
	}
}