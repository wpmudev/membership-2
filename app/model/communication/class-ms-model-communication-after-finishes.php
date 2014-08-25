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
class MS_Model_Communication_After_Finishes extends MS_Model_Communication {
	
	public static $POST_TYPE = 'ms_communication';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $type = self::COMM_TYPE_AFTER_FINISHES;
	
	public function get_description() {
		return __( 'Sent a predefined numer of days after the membership finishes. You must decide how many days after a message is to be sent.', MS_TEXT_DOMAIN );
	}
	
	public static function create_default_communication() {
		$model = new self();
	
		$model->subject = sprintf( __( 'Remainder: your %s membership has ended', MS_TEXT_DOMAIN ), self::COMM_VAR_MS_NAME );
		$model->message = self::get_default_message();
		$model->enabled = false;
		$model->period_enabled = true;
		$model->save();

		return $model;
	}
	
	public static function get_default_message() {
		ob_start();
		?>
			<h2> Hi, <?php echo self::COMM_VAR_USERNAME; ?>,</h2>
			<br /><br />
			This is a remainder that your <?php echo self::COMM_VAR_MS_NAME; ?> membership at <?php echo self::COMM_VAR_BLOG_NAME; ?> has ended on <?php echo self::COMM_VAR_MS_EXPIRY_DATE; ?>.
			<br /><br />
			You can renew your membership here: <?php echo self::COMM_VAR_MS_ACCOUNT_PAGE_URL; ?>
		<?php 
		$html = ob_get_clean();
		return apply_filters( 'ms_model_communication_after_finished_get_default_message', $html );
	}
}