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
class MS_Model_Communication_Before_Finishes extends MS_Model_Communication {
	
	public static $POST_TYPE = 'ms_communication';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $type = self::COMM_TYPE_BEFORE_FINISHES;
	
	public function after_load() {
	
		parent::after_load();
	
// 		if( $this->enabled ) {
// 			$this->add_action( 'ms_model_plugin_check_membership_status_'. MS_Model_Membership_Relationship::STATUS_ACTIVE, 'check_enqueue_messages', 10, 3 );
// 		}
	}
	
	public function check_enqueue_messages( $ms_relationship, $remaining_days, $remaining_trial_days ) {
		if( $this->enabled && MS_Model_Membership_Relationship::STATUS_ACTIVE == $ms_relationship->status ) {
				
			$days = MS_Helper_Period::get_period_in_days( $this->period );
			if( $days == $remaining_days ) {
				$this->add_to_queue( $ms_relationship->id );
				$this->save();
	
				MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_BEFORE_FINISHES, $ms_relationship );
			}
		}
	}
	
	public function get_description() {
		return __( 'Sent a predefined numer of days before the membership finishes. You must decide how many days beforehand a message is to be sent.', MS_TEXT_DOMAIN );
	}
	
	public static function create_default_communication() {
		$model = new self();
	
		$model->subject = __( 'Membership will finish soon', MS_TEXT_DOMAIN );
		$model->message = self::get_default_message();
		$model->enabled = false;
		$model->period_enabled = true;
		$model->save();
	
		return $model;
	}
	
	public static function get_default_message() {
		ob_start();
		?>
			<h2>Hi, <?php echo self::COMM_VAR_USERNAME; ?>,</h2>
			<br /><br />
			your membership will finish in <?php echo self::COMM_VAR_MS_REMAINING_DAYS; ?>.
			<br /><br />
			<?php echo self::COMM_VAR_MS_INVOICE; ?>
		<?php 
		$html = ob_get_clean();
		return apply_filters( 'ms_model_communication_before_finishes_get_default_message', $html );
	}
}