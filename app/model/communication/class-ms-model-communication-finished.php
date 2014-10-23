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
 * Communication model -  membership finished.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Communication_Finished extends MS_Model_Communication {
	
	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * @var string $POST_TYPE
	 * @var string $post_type is inherited.
	 */
	public static $POST_TYPE = 'ms_communication';
	
	/**
	 * Communication type.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_FINISHED;
	
	/**
	 * Add action to deactivated event.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	public function after_load() {
	
		parent::after_load();
	
		if( $this->enabled ) {
			$this->add_action( 'ms_model_event_' . MS_Model_Event::TYPE_MS_DEACTIVATED, 'enqueue_messages', 10, 2 );
		}
	}
	
	/**
	 * Get communication description.
	 *
	 * @since 1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __( 'Sent as soon as the membership is deactivated.', MS_TEXT_DOMAIN );
	}
	
	/**
	 * Communication default communication.
	 *
	 * @since 1.0.0
	 */
	public function reset_to_default() {
	
		parent::reset_to_default();
		
		$this->subject = sprintf( __( 'Your %s membership has ended', MS_TEXT_DOMAIN ), self::COMM_VAR_MS_NAME );
		$this->message = self::get_default_message();
		$this->enabled = false;
		$this->save();

		do_action( 'ms_model_communication_reset_to_default_after', $this->type, $this );
	}
	
	/**
	 * Get default email message.
	 *
	 * @since 1.0.0
	 * @return string The email message.
	 */
	public static function get_default_message() {//i18n please
		
		ob_start();
		?>
			<h2> Hi, <?php echo self::COMM_VAR_USERNAME; ?>,</h2>
			<br /><br />
			Your <?php echo self::COMM_VAR_MS_NAME; ?> membership at <?php echo self::COMM_VAR_BLOG_NAME; ?> has ended on <?php echo self::COMM_VAR_MS_EXPIRY_DATE; ?>.
			<br /><br />
			You can renew your membership here: <?php echo self::COMM_VAR_MS_ACCOUNT_PAGE_URL; ?>
		<?php  
		$html = ob_get_clean();
		
		return apply_filters( 'ms_model_communication_finished_get_default_message', $html );
	}
}