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
	
	public static function factory() {
		$comm_types = self::get_communication_types();
	
		foreach( $comm_types as $type ) {
			$comms[ $type ] = self::get_communication( $type );
		}
	
		return apply_filters( 'ms_model_communication_communication_set_factory', $comms );
	}
	
	public function before_save() {
		$this->title = $this->subject;
		$this->description = $this->message;
	}
	
	public function after_load() {
		$this->subject = $this->title;
		$this->message = $this->description;
	}
	
	public function get_description() {
		return 'tst';	
	}
	
	function send_message( $user_id, $membership_id = null ) {
	
		$member = MS_Model_Member::loadd( $user_id );
		if ( !is_email( $member->email ) ) {
			return;
		}
	
		$this->comm = $this->get_communication();
		$commdata = apply_filters( 'membership_comm_constants_list', $this->commconstants );
	
		foreach ( array_keys( $commdata ) as $key ) {
			switch ( $key ) {
				case '%blogname%':
					$commdata[$key] = get_option( 'blogname' );
					break;
	
				case '%blogurl%':
					$commdata[$key] = get_option( 'home' );
					break;
	
				case '%username%':
					$commdata[$key] = $member->user_login;
					break;
	
				case '%usernicename%':
					$commdata[$key] = $member->user_nicename;
					break;
	
				case '%userdisplayname%':
					$commdata[$key] = $member->display_name;
					break;
	
				case '%userfirstname%':
					$commdata[$key] = $member->user_firstname;
					break;
	
				case '%userlastname%':
					$commdata[$key] = $member->user_lastname;
					break;
	
				case '%networkname%':
					$commdata[$key] = get_site_option( 'site_name' );
					break;
	
				case '%networkurl%':
					$commdata[$key] = get_site_option( 'siteurl' );
					break;
	
				case '%subscriptionname%':
					if ( !$sub_id ) {
						$ids = $member->get_subscription_ids();
						if ( !empty( $ids ) ) {
							$sub_id = $ids[0];
						}
					}
	
					if ( !empty( $sub_id ) ) {
						$sub = Membership_Plugin::factory()->get_subscription( $sub_id );
						$commdata[$key] = $sub->sub_name();
					} else {
						$commdata[$key] = '';
					}
	
					break;
	
				case '%levelname%':
					if ( !$level_id ) {
						$ids = $member->get_level_ids();
						if ( !empty( $ids ) ) {
							$level_id = $ids[0]->level_id;
						}
					}
	
					if ( !empty( $level_id ) ) {
						$level = Membership_Plugin::factory()->get_level( $level_id );
						$commdata[$key] = $level->level_title();
					} else {
						$commdata[$key] = '';
					}
					break;
	
				case '%accounturl%':
					$commdata[$key] = M_get_account_permalink();
					break;
	
				default:
					$commdata[$key] = apply_filters( 'membership_commfield_' . $key, '', $user_id );
					break;
			}
		}
	
		// Globally replace the values in the ping and then make it into an array to send
		$original_commmessage = str_replace( array_keys( $commdata ), array_values( $commdata ), stripslashes( $this->comm->message ) );
	
		$html_message = wpautop( $original_commmessage );
		$text_message = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $original_commmessage ) );
	
		add_filter( 'wp_mail_content_type', 'M_Communications_set_html_content_type' );
	
		$lambda_function = false;
		if ( $wp_better_emails ) {
			$html_message = apply_filters( 'wpbe_html_body', $wp_better_emails->template_vars_replacement( $wp_better_emails->set_email_template( $html_message, 'template' ) ) );
			$text_message = apply_filters( 'wpbe_plaintext_body', $wp_better_emails->template_vars_replacement( $wp_better_emails->set_email_template( $text_message, 'plaintext_template' ) ) );
	
			// lets use WP Better Email to wrap communication content if the plugin is used
			$lambda_function = create_function( '', sprintf( 'return "%s";', addslashes( $text_message ) ) );
			add_filter( 'wpbe_plaintext_body', $lambda_function );
			add_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
		} elseif ( !defined( 'MEMBERSHIP_DONT_WRAP_COMMUNICATION' ) ) {
			$html_message = "<html><head></head><body>{$html_message}</body></html>";
		}
	
		@wp_mail( $member->user_email, stripslashes( $this->comm->subject ), $html_message );
	
		remove_filter( 'wp_mail_content_type', 'M_Communications_set_html_content_type' );
		if ( $lambda_function ) {
			remove_filter( 'wpbe_plaintext_body', $lambda_function );
			remove_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
		}
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