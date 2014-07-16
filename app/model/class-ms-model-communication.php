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
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected static $communications;
	
	const COMM_TYPE_REGISTRATION = 'type_registration';
	
	const COMM_TYPE_INVOICE = 'type_invoice';
	
	const COMM_TYPE_BEFORE_FINISHES = 'type_before_finishes';
	
	const COMM_TYPE_FINISHED = 'type_finished';
	
	const COMM_TYPE_AFTER_FINISHES = 'type_after_finishes';
	
	const COMM_TYPE_CANCELLED = 'type_cancelled';
	
	const COMM_TYPE_BEFORE_TRIAL_FINISHES = 'type_before_trial_finishes';
	
	const COMM_TYPE_INFO_UPDATE = 'type_info_update';
	
	const COMM_TYPE_CREDIT_CARD_EXPIRE = 'type_credit_card_expire';
	
	const COMM_TYPE_FAILED_PAYMENT = 'type_failed_payment';
	
	const COMM_TYPE_BEFORE_PAYMENT_DUE = 'type_before_payment_due';
	
	const COMM_TYPE_AFTER_PAYMENT_MADE = 'type_after_payment_made';
	
	protected $type;
	
	protected $subject;
	
	protected $message;
	
	protected $period_enabled = false;
	
	protected $period = array( 'period_unit' => 1, 'period_type' => MS_Helper_Period::PERIOD_TYPE_DAYS );
	
	protected $enabled;
	
	protected $cc_enabled;
	
	protected $cc_email;
	
	protected $comm_vars;
	
	protected $queue = array();
	
	protected $sent_queue = array();
	
	public function __construct() {
		
		$this->comm_vars = array(
				'TODO' => 'config '. $this->type,
				'%blogname%' => 'Blog/site name',
				'%blogurl%' => 'Blog/site url',
				'%username%' => 'Username',
				'%usernicename%' => 'User nice name',
				'%userdisplayname%' => 'User display name',
				'%userfirstname%' => 'User first name',
				'%userlastname%' => 'User last name',
				'%networkname%' => 'Network name',
				'%networkurl%' => 'Network url',
				'%membershipname%' => 'Membership name',
				'%total%' => 'Invoice Total',
				'%taxname%' => 'Tax name',
				'%taxamount%' => 'Tax amount',
		);
	}
	
	public function before_save() {
		$this->title = $this->subject;
		$this->description = $this->message;
	}
	
	public function after_load() {
		$this->subject = $this->title;
		$this->message = $this->description;
		if( $this->enabled ) {
			$this->add_action( 'ms_model_plugin_process_communications', 'process_communication' );
		}
	}
	
	protected static $ignore_fields = array( 'subject', 'message', 'description', 'name', 'title', 'actions', 'filters' );
	
	/**
	 * Communication types.
	 *
	 */
	public static function get_communication_types() {
		return apply_filters( 'ms_model_communication_get_communication_types', array(
				self::COMM_TYPE_REGISTRATION,
				self::COMM_TYPE_INVOICE,
				self::COMM_TYPE_BEFORE_FINISHES,
				self::COMM_TYPE_FINISHED,
				self::COMM_TYPE_AFTER_FINISHES,
				self::COMM_TYPE_CANCELLED,
				self::COMM_TYPE_BEFORE_TRIAL_FINISHES,
				self::COMM_TYPE_INFO_UPDATE,
				self::COMM_TYPE_CREDIT_CARD_EXPIRE,
				self::COMM_TYPE_FAILED_PAYMENT,
				self::COMM_TYPE_BEFORE_PAYMENT_DUE,
				self::COMM_TYPE_AFTER_PAYMENT_MADE,
			)
		);
	}
	
	/**
	 * Communication types and respective classes.
	 *
	 */
	public static function get_communication_type_classes() {
		return apply_filters( 'ms_model_comunication_get_communication_type_classes', array(
				self::COMM_TYPE_REGISTRATION => 'MS_Model_Communication_Registration',
				self::COMM_TYPE_INVOICE => 'MS_Model_Communication_Invoice',
				self::COMM_TYPE_BEFORE_FINISHES => 'MS_Model_Communication_Before_Finishes',
				self::COMM_TYPE_FINISHED => 'MS_Model_Communication_Finished',
				self::COMM_TYPE_AFTER_FINISHES => 'MS_Model_Communication_After_Finishes',
				self::COMM_TYPE_CANCELLED => 'MS_Model_Communication_Cancelled',
				self::COMM_TYPE_BEFORE_TRIAL_FINISHES => 'MS_Model_Communication_Before_Trial_Finishes',
				self::COMM_TYPE_INFO_UPDATE => 'MS_Model_Communication_Info_Update',
				self::COMM_TYPE_CREDIT_CARD_EXPIRE => 'MS_Model_Communication_Credit_Card_Expire',
				self::COMM_TYPE_FAILED_PAYMENT => 'MS_Model_Communication_Failed_Payment',
				self::COMM_TYPE_BEFORE_PAYMENT_DUE => 'MS_Model_Communication_Before_Payment_Due',
				self::COMM_TYPE_AFTER_PAYMENT_MADE => 'MS_Model_Communication_After_Payment_Made',
			)
		);
	}
	public static function get_communication_type_titles() {
		
		return apply_filters( 'ms_model_communication_get_communication_type_titles', array(
				self::COMM_TYPE_REGISTRATION => __( 'Registration complete', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_INVOICE => __( 'Invoice', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_BEFORE_FINISHES => __( 'Before Membership finishes', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_FINISHED => __( 'Membership finished', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_AFTER_FINISHES => __( 'After Membership finishes', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_CANCELLED => __( 'Membership cancelled', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_BEFORE_TRIAL_FINISHES => __( 'Before Trial finishes', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_INFO_UPDATE => __( 'Updating personal info/Billing details', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_CREDIT_CARD_EXPIRE => __( 'Credit card is about to expire', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_FAILED_PAYMENT => __( 'Failed payment', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_BEFORE_PAYMENT_DUE => __( 'Before payment due', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_AFTER_PAYMENT_MADE => __( 'After payment made', MS_TEXT_DOMAIN ),
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
		
		if( ! empty( self::$communications[ $type ] ) ) {
			return self::$communications[ $type ];
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
	
		$comm_classes = self::get_communication_type_classes();
		$model = null;
		if( ! empty( $item[0] ) ) {
			$model = $comm_classes[ $type ]::load( $item[0]->ID );
		}
		else {
			$model = $comm_classes[ $type ]::create_default_communication();
		}		
		return $model;
	}
	
	public static function load_communications() {
		if( empty( self::$communications ) ) {
			$comm_types = self::get_communication_types();
			
			foreach( $comm_types as $type ) {
				$comms[ $type ] = self::get_communication( $type );
			}
		}
	
		return apply_filters( 'ms_model_communication_communication_set_factory', self::$communications );
	}
	
	public function get_description() {
			
	}
	
	public function process_communication() {
		if( ! $this->enabled ) {
			return;
		}
		$count = 0;
		$max_emails_qty = apply_filters( 'ms_model_communication_process', 50 );
		foreach( $this->queue as $index => $ms_relationship_id ) {
			if( ++$count > $max_emails_qty ) {
				break;
			}
			$ms_relationship = MS_Model_Membership_Relationship::load( $ms_relationship_id );
			if( $this->send_message( $ms_relationship ) ) {
				unset( $this->queue[ $index ] );
				$this->sent_queue[] = $ms_relationship->id; 
			}
			else {
				MS_Helper_Debug::log( sprintf( __( '[error: Communication email failed] comm_type=%s, ms_relationship_id=%s, user_id=%s', MS_TEXT_DOMAIN ),
						$this->type,
						$ms_relationship->id,
						$ms_relationship->user_id
				) );
			}
		}
		$this->save();
	}
	
	public function enqueue_messages( $event, $ms_relationship ) {
		$this->add_to_queue( $ms_relationship->id );
		$this->save();
	}
	
	public function add_to_queue( $ms_relationship_id ) {
		if( ! in_array( $ms_relationship_id, $this->queue ) ) {
			$this->queue[] = $ms_relationship_id;
		}	
	}
	
	public function send_message( $ms_relationship ) {
		
		$wp_user = new WP_User( $ms_relationship->user_id );
		if ( ! is_email( $wp_user->user_email ) || ! $this->enabled ) {
			return;
		}

		$currency = MS_Plugin::instance()->settings->currency . ' ';
		$membership = $ms_relationship->get_membership();
		
		if( ! $invoice = $ms_relationship->get_previous_invoice() ) {
			$invoice = $ms_relationship->get_current_invoice();
		}

		$comm_vars = apply_filters( 'membership_comm_vars_list', $this->comm_vars );
		foreach ( $comm_vars as $key => $description ) {
			switch ( $key ) {
				case '%blogname%':
					$comm_vars[ $key ] = get_option( 'blogname' );
					break;
				case '%blogurl%':
					$comm_vars[ $key ] = get_option( 'home' );
					break;
				case '%username%':
					$comm_vars[ $key ] = $wp_user->user_login;
					break;
				case '%usernicename%':
					$comm_vars[ $key ] = $wp_user->user_nicename;
					break;
				case '%userdisplayname%':
					$comm_vars[ $key ] = $wp_user->display_name;
					break;
				case '%userfirstname%':
					$comm_vars[ $key ] = $wp_user->user_firstname;
					break;
				case '%userlastname%':
					$comm_vars[ $key ] = $wp_user->user_lastname;
					break;
				case '%networkname%':
					$comm_vars[ $key ] = get_site_option( 'site_name' );
					break;
				case '%networkurl%':
					$comm_vars[ $key ] = get_site_option( 'siteurl' );
					break;
				case '%membershipname%':
					if( ! empty( $membership->name ) ) {
						$comm_vars[ $key ] = $membership->name;
					}
					else {
						$comm_vars[ $key ] = '';
					}
					break;
				case '%taxname%':
					if( isset( $invoice ) ) {
						$comm_vars[ $key ] = $currency . $invoice->tax_name;
					}
					else {
						$comm_vars[ $key ] = 0;
					}
					break;
				case '%taxamount%':
					if( isset( $invoice ) ) {
						$comm_vars[ $key ] = $currency . $invoice->tax_rate * $invoice->amount;
					}
					else {
						$comm_vars[ $key ] = 0;
					}
					break;
				case '%total%':
					if( isset( $invoice ) ) {
						$comm_vars[ $key ] = $currency . $invoice->total;
					}
					else {
						$comm_vars[ $key ] = 0;
					}
					break;
						
				default:
					$comm_vars[ $key ] = apply_filters( "ms_model_communication_send_message_comm_var_$key", '', $ms_relationship->user_id );
					break;
			}
		}

		// Globally replace the values in the ping and then make it into an array to send
		$message = str_replace( array_keys( $comm_vars ), array_values( $comm_vars ), stripslashes( $this->message ) );
	
		$html_message = wpautop( $message );
		$text_message = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $message ) );
	
		$this->add_filter( 'wp_mail_content_type', 'set_mail_content_type' );
		
		global $wp_better_emails;
		$lambda_function = false;
		if ( $wp_better_emails ) {
			$html_message = apply_filters( 'wpbe_html_body', $wp_better_emails->template_vars_replacement( $wp_better_emails->set_email_template( $html_message, 'template' ) ) );
			$text_message = apply_filters( 'wpbe_plaintext_body', $wp_better_emails->template_vars_replacement( $wp_better_emails->set_email_template( $text_message, 'plaintext_template' ) ) );
	
			// lets use WP Better Email to wrap communication content if the plugin is used
			$lambda_function = create_function( '', sprintf( 'return "%s";', addslashes( $text_message ) ) );
			add_filter( 'wpbe_plaintext_body', $lambda_function );
			add_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
		} 
		elseif ( apply_filters( 'ms_model_communication_wrap_communication', true ) ) {
			$html_message = "<html><head></head><body>{$html_message}</body></html>";
		}
		
		$recipients = array( $wp_user->user_email );
		if( $this->cc_enabled ) {
			$recipients[] = $this->cc_email;
		}

		$sent = @wp_mail( $recipients, stripslashes( $this->subject ), $html_message );
		
		$this->remove_filter( 'wp_mail_content_type', 'set_mail_content_type' );
		if ( $lambda_function ) {
			remove_filter( 'wpbe_plaintext_body', $lambda_function );
			remove_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
		}
		
		return $sent;
	}
	
	/**
	 * Set wp_mail_content_type to text/html.
	 * 
	 * @since 4.0.0
	 * 
	 * @return string
	 */
	public function set_mail_content_type() {
		return apply_filters( 'ms_model_communication_set_html_content_type', 'text/html' );
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'type':
					if( $this->is_valid_communication_type( $value ) ) {
						$this->$property = $value;
					}
					break;
				case 'subject':
					$this->$property = sanitize_text_field( $value );
					break;
				case 'cc_email':
					if( is_email( $value ) ) {
						$this->$property = $value; 
					}
					break;
				case 'enabled':
				case 'cc_enabled':
					$this->$property = $this->validate_bool( $value );
					break;
				case 'period':
					$this->$property = $this->validate_period( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
	
}