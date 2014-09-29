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
	
	public $post_type = 'ms_communication';
	
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
	
	const COMM_TYPE_AFTER_PAYMENT_DUE = 'type_after_payment_due';
	
	const COMM_VAR_MS_NAME = '%ms-name%';
	const COMM_VAR_MS_INVOICE = '%ms-invoice%';
	const COMM_VAR_MS_ACCOUNT_PAGE_URL = '%ms-account-page-url%';
	const COMM_VAR_MS_REMAINING_DAYS = '%ms-remaining-days%';
	const COMM_VAR_MS_REMAINING_TRIAL_DAYS = '%ms-remaining-trial-days%';
	const COMM_VAR_MS_EXPIRY_DATE = '%ms-expiry-date%';
	const COMM_VAR_USER_DISPLAY_NAME = '%user-display-name%';
	const COMM_VAR_USER_FIRST_NAME = '%user-first-name%';
	const COMM_VAR_USER_LAST_NAME = '%user-last-name%';
	const COMM_VAR_USERNAME = '%username%';
	const COMM_VAR_BLOG_NAME = '%blog-name%';
	const COMM_VAR_BLOG_URL = '%blog-url%';
	const COMM_VAR_NET_NAME = '%network-name%';
	const COMM_VAR_NET_URL = '%network-url%';
	
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
	
	protected $content_type = 'text/html';
	
	public $ignore_fields = array( 'message', 'description', 'name', 'actions', 'filters', 'ignore_fields', 'post_type', 'comm_vars' );
		
	public function __construct() {
		
		$this->comm_vars = array(
				 self::COMM_VAR_MS_NAME => __( 'Membership name', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_MS_INVOICE => __( 'Invoice details', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_MS_ACCOUNT_PAGE_URL => __( 'Account page url', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_MS_REMAINING_DAYS => __( 'Membership remaining days', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_MS_REMAINING_TRIAL_DAYS => __( 'Membership remaining trial days', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_MS_EXPIRY_DATE => __( 'Membership expiry date', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_USER_DISPLAY_NAME => __( 'User display name', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_USER_FIRST_NAME => __( 'User first name', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_USER_LAST_NAME => __( 'User last name', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_USERNAME => __( 'Username', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_BLOG_NAME => __( 'Blog/site name', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_BLOG_URL => __( 'Blog/site url', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_NET_NAME => __( 'Network name', MS_TEXT_DOMAIN ),
				 self::COMM_VAR_NET_URL => __( 'Network url', MS_TEXT_DOMAIN ),
		);
	}
	
	public function before_save() {
		$this->description = $this->message;
	}
	
	public function after_load() {
		$this->message = $this->description;
		if( $this->enabled ) {
			$this->add_action( 'ms_model_plugin_process_communications', 'process_communication' );
		}
	}
	
	
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
				self::COMM_TYPE_AFTER_PAYMENT_DUE,
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
				self::COMM_TYPE_AFTER_PAYMENT_DUE => 'MS_Model_Communication_After_Payment_Due',
			)
		);
	}
	public static function get_communication_type_titles() {
		
		return apply_filters( 'ms_model_communication_get_communication_type_titles', array(
				self::COMM_TYPE_REGISTRATION => __( 'Signup completed', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_INVOICE => __( 'Invoice/Receipt', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_BEFORE_FINISHES => __( 'Before Membership finishes', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_FINISHED => __( 'Membership finished', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_AFTER_FINISHES => __( 'After Membership finishes', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_CANCELLED => __( 'Membership cancelled', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_BEFORE_TRIAL_FINISHES => __( 'Before Trial finishes', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_INFO_UPDATE => __( 'Billing details updated', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_CREDIT_CARD_EXPIRE => __( 'Credit card is about to expire', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_FAILED_PAYMENT => __( 'Failed payment', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_BEFORE_PAYMENT_DUE => __( 'Before payment due', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_AFTER_PAYMENT_DUE => __( 'After payment due', MS_TEXT_DOMAIN ),
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
			$model = MS_Factory::load( $comm_classes[ $type ], $item[0]->ID );
		}
		else {
			$model = self::communication_factory( $type, $comm_classes[ $type ] );
		}		
		return apply_filters( 'ms_model_communication_' . $type, $model );
	}
	
	public static function communication_factory( $type, $class ) {
		$model = new $class();
		$model->create_default_communication();
		return apply_filters( 'ms_model_communication_communication_factory', $model, $type, $class );
	}
	
	public static function create_default_communication() {
		
	}
	
	public static function load_communications() {
		if( empty( self::$communications ) ) {
			$comm_types = self::get_communication_types();
			
			foreach( $comm_types as $type ) {
				self::$communications[ $type ] = self::get_communication( $type );
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
		if( ! $this->check_object_lock() ) {
			$this->set_object_lock();
			
			/** max emails that could be sent in each call. */
			$max_emails_qty = apply_filters( 'ms_model_communication_process_communication_max_email_qty', 50 );
			$count = 0;
				
			/** max seconds for processing */
			$time_limit = apply_filters( 'ms_model_communication_process_communication_time_limit', 10 ); 
			$start_time = time();

			foreach( $this->queue as $ms_relationship_id => $date ) {
				if( ( time() > $start_time + $time_limit ) || ( ++$count > $max_emails_qty ) ) {
					break;
				}
				$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $ms_relationship_id );
				if( $this->send_message( $ms_relationship ) ) {
					$this->remove_from_queue( $ms_relationship_id ); 
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
			$this->delete_object_lock();
		}
	}
	
	public function enqueue_messages( $event, $ms_relationship ) {
		if( ! $this->enabled ) {
			return;
		}
		$this->add_to_queue( $ms_relationship->id );
		$this->save();
	}
	
	public function add_to_queue( $ms_relationship_id ) {
		if( ! $this->enabled ) {
			return;
		}
// 		if( ! array_key_exists( $ms_relationship_id, $this->queue ) && ! array_key_exists( $ms_relationship_id, $this->sent_queue ) ) {
		if( ! array_key_exists( $ms_relationship_id, $this->queue ) ) {	
			$this->queue[ $ms_relationship_id ] = MS_Helper_Period::current_date( MS_Helper_Period::DATE_FORMAT_SHORT );
		}	
	}
	
	public function remove_from_queue( $ms_relationship_id ) {
		$this->sent_queue[ $ms_relationship_id ] = MS_Helper_Period::current_date( MS_Helper_Period::DATE_FORMAT_SHORT );
		unset( $this->queue[ $ms_relationship_id ] );
		
		$max_history = apply_filters( 'ms_model_communication_sent_queue_max_history', 300 );
		/** Delete history */
		if( count( $this->sent_queue ) > $max_history ) {
			$this->sent_queue = array_slice( $this->sent_queue, -100, $max_history, true );
		}
	}
	
	public function send_message( $ms_relationship ) {
		if( ! $this->enabled ) {
			return;
		}
		
		$wp_user = new WP_User( $ms_relationship->user_id );
		if( ! is_email( $wp_user->user_email ) ) {
			MS_Helper_Debug::log( "Invalid user email. User_id: $ms_relationship->user_id, email: $wp_user->user_email" );
			return;
		}
		
		$sent = false;
		$comm_vars = $this->get_comm_vars( $ms_relationship, $wp_user );
		
		/** Replace the variables. */
		$message = str_replace( array_keys( $comm_vars ), array_values( $comm_vars ), stripslashes( $this->message ) );
		$subject = str_replace( array_keys( $comm_vars ), array_values( $comm_vars ), stripslashes( $this->subject ) );
		
		$html_message = wpautop( $message );
		$text_message = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $message ) );
		$subject = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $subject ) );
		
		$message = $text_message;
		if( 'text/html' == $this->get_mail_content_type() ) {
			$this->add_filter( 'wp_mail_content_type', 'get_mail_content_type' );
			$message = $html_message;
		}
		
		$recipients = array( $wp_user->user_email );
		if( $this->cc_enabled ) {
			$recipients[] = $this->cc_email;
		}
		
		$admin_emails = MS_Model_Member::get_admin_user_emails();
		$headers = '';
		if( ! empty( $admin_emails[0] ) ) {
			$headers = array(
					sprintf( 'From: %s <%s> ', get_option( 'blogname' ), $admin_emails[0] )
			);
		}
		
		$html_message = apply_filters( 'ms_model_communication_send_message_html_message', $html_message, $this, $ms_relationship );
		$text_message = apply_filters( 'ms_model_communication_send_message_text_message', $text_message, $this, $ms_relationship );
		$subject = apply_filters( 'ms_model_communication_send_message_subject', $subject, $this, $ms_relationship );
		$headers = apply_filters( 'ms_model_communication_send_message_headers', $headers );
		 
		$sent = @wp_mail( $recipients, $subject, $message, $headers );
		
		if( 'text/html' == $this->get_mail_content_type() ) {
			$this->remove_filter( 'wp_mail_content_type', 'get_mail_content_type' );
		}

		return $sent;
	}
	
	/**
	 * Replace comm_vars with corresponding values.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_comm_vars( $ms_relationship, $wp_user ) {
		
		$currency = MS_Plugin::instance()->settings->currency . ' ';
		$membership = $ms_relationship->get_membership();
		
		$class = get_class( $this );
		$previous_invoice = array(
				'MS_Model_Communication_Registration',
				'MS_Model_Communication_Invoice',
				'MS_Model_Communication_After_Payment_Made',
		);
		
		$invoice = null;
		if( in_array( $class, $previous_invoice ) ) {
			if( ! $invoice = MS_Model_Invoice::get_previous_invoice( $ms_relationship ) ) {
				$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
			}
		}
		else {
			$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		}
		
		$comm_vars = apply_filters( 'ms_model_communication_comm_vars', $this->comm_vars );
		
		foreach ( $comm_vars as $key => $description ) {
			switch ( $key ) {
				case self::COMM_VAR_BLOG_NAME:
					$comm_vars[ $key ] = get_option( 'blogname' );
					break;
				case self::COMM_VAR_BLOG_URL:
					$comm_vars[ $key ] = get_option( 'home' );
					break;
				case self::COMM_VAR_USERNAME:
					$comm_vars[ $key ] = $wp_user->user_login;
					break;
				case self::COMM_VAR_USER_DISPLAY_NAME:
					$comm_vars[ $key ] = $wp_user->display_name;
					break;
				case self::COMM_VAR_USER_FIRST_NAME:
					$comm_vars[ $key ] = $wp_user->user_firstname;
					break;
				case self::COMM_VAR_USER_LAST_NAME:
					$comm_vars[ $key ] = $wp_user->user_lastname;
					break;
				case self::COMM_VAR_NET_NAME:
					$comm_vars[ $key ] = get_site_option( 'site_name' );
					break;
				case self::COMM_VAR_NET_URL:
					$comm_vars[ $key ] = get_site_option( 'siteurl' );
					break;
				case self::COMM_VAR_MS_NAME:
					if( $membership->name ) {
						$comm_vars[ $key ] = $membership->name;
					}
					else {
						$comm_vars[ $key ] = '';
					}
					break;
				case self::COMM_VAR_MS_INVOICE:
					if( isset( $invoice ) && $invoice->total > 0 ) {
						$comm_vars[ $key ] = do_shortcode( sprintf( '[%s post_id="%s" pay_button="0"]',
								MS_Helper_Shortcode::SCODE_MS_INVOICE,
								$invoice->id
						) );
					}
					else {
						$comm_vars[ $key ] = '';
					}
					break;
				case self::COMM_VAR_MS_ACCOUNT_PAGE_URL:
					$comm_vars[ $key ] = sprintf( '<a href="%s">%s</a>', 
						MS_Plugin::instance()->settings->get_special_page_url( MS_Model_Settings::SPECIAL_PAGE_ACCOUNT ), 
						__( 'account page', MS_TEXT_DOMAIN )
					);
					break;
				case self::COMM_VAR_MS_REMAINING_DAYS:
					$days = $ms_relationship->get_remaining_period();
					$comm_vars[ $key ] = sprintf( __( '%s day%s', MS_TEXT_DOMAIN ), $days,  abs( $days ) > 1 ? 's': '' );
					break;
				case self::COMM_VAR_MS_REMAINING_TRIAL_DAYS:
					$days = $ms_relationship->get_remaining_trial_period();
					$comm_vars[ $key ] = sprintf( __( '%s day%s', MS_TEXT_DOMAIN ), $days,  abs( $days ) > 1 ? 's': '' );
					break;
				case self::COMM_VAR_MS_EXPIRY_DATE:
					$comm_vars[ $key ] = $ms_relationship->expire_date;
					break;
			}
			$comm_vars[ $key ] = apply_filters( "ms_model_communication_send_message_comm_var_$key", $comm_vars[ $key ], $ms_relationship );
		}
		return apply_filters( 'ms_model_communication_get_comm_vars', $comm_vars );	
	}
	
	/**
	 * Set wp_mail_content_type to text/html.
	 * 
	 * @since 4.0.0
	 * 
	 * @return string
	 */
	public function get_mail_content_type() {
		return apply_filters( 'ms_model_communication_set_html_content_type', $this->content_type );
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
		else {
			switch( $property ) {
				case 'period_unit':
					$this->period['period_unit'] = $this->validate_period_unit( $value );
					break;
				case 'period_type':
					$this->period['period_type'] = $this->validate_period_type( $value );
					break;
			}
		}
	}
	
}