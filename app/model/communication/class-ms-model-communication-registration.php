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
 * Communication model - registration.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Communication_Registration extends MS_Model_Communication {
	
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
	 * Add action to credit card expire event.
	 *
	 * *Hooks Actions: *
	 * * ms_model_event_paid
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_REGISTRATION;
		
	/**
	 * Add action to signup event.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	public function after_load() {
	
		parent::after_load();
		
		if( $this->enabled ) {
			$this->add_action( 'ms_model_event_'. MS_Model_Event::TYPE_MS_SIGNED_UP, 'process_communication_registration', 10, 2 );
		}
	}
	
	/**
	 * Get communication description.
	 *
	 * @since 1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __( 'Sent when a member completes the signup for a membership.', MS_TEXT_DOMAIN );
	}

	/**
	 * Communication default communication.
	 *
	 * @since 1.0.0
	 */
	public function reset_to_default() {
	
		parent::reset_to_default();
		
		$this->subject = sprintf( __( 'Confirmation of your membership at %s', MS_TEXT_DOMAIN ), self::COMM_VAR_BLOG_NAME );
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
			<h2> Welcome, <?php echo self::COMM_VAR_USERNAME; ?>,</h2>
			<br /><br />
			You have successfully subscribed to our <?php echo self::COMM_VAR_MS_NAME; ?> membership level at <?php echo self::COMM_VAR_BLOG_NAME; ?>
			<br /><br />
			You can review and edit your membership details here: <?php echo self::COMM_VAR_MS_ACCOUNT_PAGE_URL; ?>
			<br /> <br />
			Here are your latest payment details: 
			<br /><br />
			<?php echo self::COMM_VAR_MS_INVOICE; ?>
		<?php 
		$html = ob_get_clean();
		
		return apply_filters( 'ms_model_communication_registration_get_default_message', $html );
	}
	
	/**
	 * Process communication registration.
	 *
	 * *Hooks Actions: *
	 * * ms_model_event_signed_up
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	public function process_communication_registration( $event, $ms_relationship ) {
		
		do_action( 'ms_model_communication_registration_process_before', $ms_relationship, $event, $this );
		
		$this->send_message( $ms_relationship );
		
		do_action( 'ms_model_communication_registration_process_after', $ms_relationship, $event, $this );
	}
}