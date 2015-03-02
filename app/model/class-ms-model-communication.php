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
 * Communication model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Communication extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * @var string $POST_TYPE
	 */
	public static $POST_TYPE = 'ms_communication';
	public $post_type = 'ms_communication';

	/**
	 * Communication types, static reference to loaded child objects.
	 *
	 * @since 1.0.0
	 * @var string $communications
	 */
	protected static $communications;

	/**
	 * Communication type constants.
	 *
	 * @since 1.0.0
	 * @see $type
	 * @var string The communication type
	 */
	const COMM_TYPE_REGISTRATION = 'type_registration';
	const COMM_TYPE_REGISTRATION_FREE = 'type_registration_free';
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

	/**
	 * Communication variable constants.
	 *
	 * These variables are used inside emails and are replaced by variable value.
	 *
	 * @since 1.0.0
	 * @see comm_vars
	 * @var string The communication variable name.
	 */
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

	/**
	 * Communication type.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	protected $type;

	/**
	 * Email subject.
	 *
	 * @since 1.0.0
	 * @var string The email subject.
	 */
	protected $subject;

	/**
	 * Email body message.
	 *
	 * @since 1.0.0
	 * @var string The email body message.
	 */
	protected $message;

	/**
	 * Communication period enabled.
	 *
	 * When the communication has a period to consider.
	 *
	 * @since 1.0.0
	 * @var bool The period enabled status.
	 */
	protected $period_enabled = false;

	/**
	 * The communication period settings.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $period = array(
		'period_unit' => 1,
		'period_type' => MS_Helper_Period::PERIOD_TYPE_DAYS,
	);

	/**
	 * Communication enabled status.
	 *
	 * @since 1.0.0
	 * @var string The communication enabled status.
	 */
	protected $enabled;

	/**
	 * Communication carbon copy enabled.
	 *
	 * @since 1.0.0
	 * @var string The communication carbon copy enabled status.
	 */
	protected $cc_enabled;

	/**
	 * Communication copied recipient email.
	 *
	 * @since 1.0.0
	 * @var string The copied recipient email.
	 */
	protected $cc_email;

	/**
	 * Communication variables.
	 *
	 * @since 1.0.0
	 * @var string The communication vars.
	 */
	protected $comm_vars;

	/**
	 * Communication queue of emails to send.
	 *
	 * @since 1.0.0
	 * @var string The communication queue.
	 */
	protected $queue = array();

	/**
	 * Communication sent emails queue.
	 *
	 * Keep for a limited history of sent emails.
	 *
	 * @since 1.0.0
	 * @var string The communication sent queue.
	 */
	protected $sent_queue = array();

	/**
	 * Communication default content type.
	 *
	 * @since 1.0.0
	 * @var string The communication default content type.
	 */
	protected $content_type = 'text/html';

	/**
	 * Don't persist this fields.
	 *
	 * @since 1.0.0
	 * @var string[] The fields to ignore when persisting.
	 */
	static public $ignore_fields = array(
		'message',
		'description',
		'name',
		'post_type',
		'comm_vars',
	);

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Protected Content Email Templates', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::$POST_TYPE
		);
	}

	/**
	 * Communication constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->comm_vars = array(
			self::COMM_VAR_MS_NAME => __( 'Membership name', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_INVOICE => __( 'Invoice details', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_ACCOUNT_PAGE_URL => __( 'Account page url', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_REMAINING_DAYS => __( 'Membership remaining days', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_REMAINING_TRIAL_DAYS => __( 'Membership remaining trial days', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_EXPIRY_DATE => __( 'Membership expiration date', MS_TEXT_DOMAIN ),
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

	/**
	 * Set description field to persist in WP column.
	 *
	 * @since 1.0.0
	 */
	public function before_save() {
		parent::before_save();
		$this->description = $this->message;
	}

	/**
	 * Hook process communication actions.
	 *
	 * Used by child classes to process communication queue.
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->message = $this->description;
		if ( $this->enabled ) {
			$this->add_action(
				'ms_cron_process_communications',
				'process_communication'
			);
		}
	}

	/**
	 * Get communication types.
	 *
	 * @since 1.0.0
	 *
	 * @return array The communication types.
	 */
	public static function get_communication_types() {
		static $types;

		if ( empty( $types ) ) {
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_AUTO_MSGS_PLUS ) ) {
				$types = array(
					self::COMM_TYPE_REGISTRATION,
					self::COMM_TYPE_REGISTRATION_FREE,
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
				);
			} else {
				$types = array(
					self::COMM_TYPE_REGISTRATION,
					self::COMM_TYPE_INVOICE,
					self::COMM_TYPE_FINISHED,
					self::COMM_TYPE_CANCELLED,
					self::COMM_TYPE_INFO_UPDATE,
					self::COMM_TYPE_CREDIT_CARD_EXPIRE,
					self::COMM_TYPE_FAILED_PAYMENT,
				);
			}
		}

		return apply_filters(
			'ms_model_communication_get_communication_types',
			$types
		);
	}

	/**
	 * Get Communication types and respective classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Return array of $type => $class_name.
	 *
	 *     @type string $type The communication type.
	 *     @type string $class_name The class name of the communication type.
	 * }
	 */
	public static function get_communication_type_classes() {
		static $type_classes;

		if ( empty( $type_classes ) ) {
			$type_classes = array(
				self::COMM_TYPE_REGISTRATION => 'MS_Model_Communication_Registration',
				self::COMM_TYPE_REGISTRATION_FREE => 'MS_Model_Communication_Registration_Free',
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
			);
		}

		return apply_filters(
			'ms_model_communication_get_communication_type_classes',
			$type_classes
		);
	}

	/**
	 * Get Communication types and respective titles.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Return array of $type => $title.
	 *
	 *     @type string $type The communication type.
	 *     @type string $title The title of the communication type.
	 * }
	 */
	public static function get_communication_type_titles() {
		static $type_titles;

		if ( empty( $type_titles ) ) {
			$type_titles = array(
				self::COMM_TYPE_REGISTRATION => __( 'Signup completed', MS_TEXT_DOMAIN ),
				self::COMM_TYPE_REGISTRATION_FREE => __( 'Signup completed for a free membership', MS_TEXT_DOMAIN ),
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
			);

			foreach ( $type_titles as $type => $title ) {
				if ( ! self::is_valid_communication_type( $type ) ) {
					unset( $type_titles[ $type ] );
				}
			}
		}

		return apply_filters(
			'ms_model_communication_get_communication_type_titles',
			$type_titles
		);
	}

	/**
	 * Validate communication type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The type to validate.
	 * @return bool True if is valid.
	 */
	public static function is_valid_communication_type( $type ) {
		$valid = ! empty( $type )
			&& in_array( $type, self::get_communication_types() );

		return apply_filters(
			'ms_model_communication_is_valid_communication_type',
			$valid,
			$type
		);
	}

	/**
	 * Get communication type object.
	 *
	 * Load from DB if exists, create a new one if not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The type of the communication.
	 * @param boolean $create_if_not_exists Optional. Flag to create a comm type if not exists.
	 * @return MS_Model_Communication The communication object.
	 */
	public static function get_communication( $type, $create_if_not_exists = false ) {
		$model = null;

		if ( self::is_valid_communication_type( $type ) ) {
			if ( ! empty( self::$communications[ $type ] ) ) {
				$model = self::$communications[ $type ];
			} else {
				$args = array(
					'post_type' => self::$POST_TYPE,
					'post_status' => 'any',
					'fields' => 'ids',
					'order' => 'DESC',
					'orderby' => 'ID',
					'meta_query' => array(
						array(
							'key' => 'type',
							'value' => $type,
							'compare' => '=',
						),
					)
				);

				$args = apply_filters(
					'ms_model_communication_get_communication_args',
					$args
				);

				$query = new WP_Query( $args );
				$item = $query->get_posts();

				$comm_classes = self::get_communication_type_classes();

				if ( ! empty( $item[0] ) ) {
					$model = MS_Factory::load(
						$comm_classes[$type],
						$item[0]
					);
				} elseif ( $create_if_not_exists ) {
					$model = self::communication_factory(
						$type,
						$comm_classes[$type]
					);
				}
			}
		}

		return apply_filters(
			'ms_model_communication_get_communication_' . $type,
			$model
		);
	}

	/**
	 * Communication factory.
	 *
	 * Create a new communication object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The type of the communication.
	 * @param string $class The communication type class.
	 * @return MS_Model_Communication The communication object.
	 */
	public static function communication_factory( $type, $class ) {
		$model = MS_Factory::create( $class );
		$model->reset_to_default();

		return apply_filters(
			'ms_model_communication_communication_factory',
			$model,
			$type,
			$class
		);
	}

	/**
	 * Communication default communication.
	 *
	 * To be overridden by children classes creating a new object with the default subject, message, enabled, etc.
	 *
	 * @since 1.0.0
	 */
	public function reset_to_default() {
		do_action(
			'ms_model_communication_reset_to_default',
			$this->type,
			$this
		);
	}

	/**
	 * Retrieve and return all communication types objects.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $create_if_not_exists Optional. Flag to create a comm type if not exists.
	 * @return MS_Model_Communication[] The communication objects array.
	 */
	public static function load_communications( $create_if_not_exists = false ) {
		if ( empty( self::$communications ) ) {
			$comm_types = self::get_communication_types();

			foreach ( $comm_types as $type ) {
				$comm = self::get_communication( $type, $create_if_not_exists );

				if ( ! empty( $comm ) ) {
					self::$communications[ $type ] = $comm;
				}
			}
		}

		return apply_filters(
			'ms_model_communication_communication_set_factory',
			self::$communications
		);
	}

	/**
	 * Get communication description.
	 *
	 * Override it in children classes.
	 *
	 * @since 1.0.0
	 *
	 * @return string The description.
	 */
	public function get_description() {
		$description = __( 'Override this description in child class', MS_TEXT_DOMAIN );

		return apply_filters(
			'ms_model_communication_get_description',
			$description
		);
	}

	/**
	 * Process communication.
	 *
	 * Send email and manage queue.
	 *
	 * @since 1.0.0
	 */
	public function process_communication() {
		do_action(
			'ms_model_communication_process_communication_before',
			$this
		);

		/**
		 * Use `define( 'MS_STOP_EMAILS', true );` in wp-config.php to prevent
		 * Protected Content from sending *any* emails to users.
		 * Also any currently enqueued message is removed from the queue
		 *
		 * @since 1.1.0.5
		 */
		if ( MS_Plugin::get_modifier( 'MS_STOP_EMAILS' ) ) {
			$this->queue = array();
		}

		if ( $this->enabled && ! $this->check_object_lock() ) {
			$this->set_object_lock();

			// Max emails that are sent in one process call.
			$max_emails_qty = apply_filters(
				'ms_model_communication_process_communication_max_email_qty',
				50
			);
			$count = 0;

			// Email-processing timeout, in seconds.
			$time_limit = apply_filters(
				'ms_model_communication_process_communication_time_limit',
				10
			);
			$start_time = time();

			foreach ( $this->queue as $subscription_id => $date ) {
				if ( time() > $start_time + $time_limit
					|| ++$count > $max_emails_qty
				) {
					break;
				}

				$subscription = MS_Factory::load( 'MS_Model_Relationship', $subscription_id );
				if ( $this->send_message( $subscription ) ) {
					$this->remove_from_queue( $subscription_id );
				} else {
					do_action(
						'lib2_debug_log',
						sprintf(
							__( '[error: Communication email failed] comm_type=%s, subscription_id=%s, user_id=%s', MS_TEXT_DOMAIN ),
							$this->type,
							$subscription->id,
							$subscription->user_id
						)
					);
				}
			}

			$this->save();
			$this->delete_object_lock();
		}

		do_action( 'ms_model_communication_process_communication_after', $this );
	}

	/**
	 * Enqueue a message in the "send queue".
	 *
	 * Action handler hooked up in child classes.
	 *
	 * Related Action Hooks:
	 * - ms_model_event_{$comm_type}
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Event $event The event object.
	 * @param MS_Model_Relationship $ms_relationship The membership relationship to send message to.
	 */
	public function enqueue_messages( $event, $ms_relationship ) {
		do_action( 'ms_model_communication_enqueue_messages_before', $this );

		if (  $this->enabled ) {
			$this->add_to_queue( $ms_relationship->id );
			$this->save();
		}

		do_action( 'ms_model_communication_enqueue_messages_after', $this );
	}

	/**
	 * Add a message in the "send queue".
	 *
	 * @since 1.0.0
	 *
	 * @param int $subscription_id The membership relationship ID to add to queue.
	 */
	public function add_to_queue( $subscription_id ) {
		do_action( 'ms_model_communication_add_to_queue_before', $this );

		/**
		 * Documented in process_communication()
		 *
		 * @since 1.1.0.5
		 */
		if ( MS_Plugin::get_modifier( 'MS_STOP_EMAILS' ) ) {
			$subscription = MS_Factory::load( 'MS_Model_Relationship', $subscription_id );
			do_action(
				'lib2_debug_log',
				sprintf(
					'Following Email was not sent: "%s" to user "%s".',
					$this->type,
					$subscription->user_id
				)
			);

			return false;
		}

		if ( $this->enabled
			&& ! array_key_exists( $subscription_id, $this->queue )
		) {
			$can_add = true;

			/*
			 * Verify if it was recently sent.
			 * Uncomment this if needing less emails.
			 */
			/*
			if ( array_key_exists( $subscription_id, $this->sent_queue ) ) {
				$min_days_before_resend = apply_filters( 'ms_model_communication_add_to_queue_min_days_before_resend', 1 );
				$sent_date = $this->sent_queue[ $subscription_id ];
				$now = MS_Helper_Period::current_date( MS_Helper_Period::DATE_FORMAT_SHORT );

				if ( MS_Helper_Period::subtract_dates( $now, $sent_date ) < $min_days_before_resend ) {
					$can_add = false;
				}
			}
			*/

			if ( $can_add ) {
				$this->queue[ $subscription_id ] = MS_Helper_Period::current_date(
					MS_Helper_Period::DATE_FORMAT_SHORT
				);
			}
		}

		do_action( 'ms_model_communication_add_to_queue_after', $this );
	}

	/**
	 * Remove from queue.
	 *
	 * Delete history of sent messages after max is reached.
	 *
	 * @since 1.0.0
	 *
	 * @param int $ms_relationship_id The membership relationship ID to remove from queue.
	 */
	public function remove_from_queue( $ms_relationship_id ) {
		do_action( 'ms_model_communication_remove_from_queue_before', $this );

		$this->sent_queue[ $ms_relationship_id ] = MS_Helper_Period::current_date(
			MS_Helper_Period::DATE_FORMAT_SHORT
		);
		unset( $this->queue[ $ms_relationship_id ] );

		$max_history = apply_filters(
			'ms_model_communication_sent_queue_max_history',
			300
		);

		// Delete history
		if ( count( $this->sent_queue ) > $max_history ) {
			$this->sent_queue = array_slice(
				$this->sent_queue,
				-100,
				$max_history,
				true
			);
		}

		do_action( 'ms_model_communication_remove_from_queue_after', $this );
	}

	/**
	 * Send email message.
	 *
	 * Delete history of sent messages after max is reached.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The membership relationship to send message to.
	 * @return bool True if successfully sent email.
	 */
	public function send_message( $subscription ) {
		do_action(
			'ms_model_communication_send_message_before',
			$subscription,
			$this
		);

		$sent = false;

		if ( $this->enabled ) {
			$wp_user = new WP_User( $subscription->user_id );

			if ( ! is_email( $wp_user->user_email ) ) {
				do_action(
					'lib2_debug_log',
					sprintf(
						'Invalid user email. User_id: %1$s, email: %2$s',
						$subscription->user_id,
						$wp_user->user_email
					)
				);
				return false;
			}

			$comm_vars = $this->get_comm_vars( $subscription, $wp_user );

			// Replace the email variables.
			$message = str_replace(
				array_keys( $comm_vars ),
				array_values( $comm_vars ),
				stripslashes( $this->message )
			);

			$subject = str_replace(
				array_keys( $comm_vars ),
				array_values( $comm_vars ),
				stripslashes( $this->subject )
			);

			$html_message = wpautop( $message );
			$text_message = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $message ) );
			$subject = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $subject ) );

			$message = $text_message;

			if ( 'text/html' == $this->get_mail_content_type() ) {
				$this->add_filter( 'wp_mail_content_type', 'get_mail_content_type' );
				$message = $html_message;
			}

			$recipients = array( $wp_user->user_email );
			if ( $this->cc_enabled ) {
				$recipients[] = $this->cc_email;
			}

			$admin_emails = MS_Model_Member::get_admin_user_emails();
			$headers = '';

			if ( ! empty( $admin_emails[0] ) ) {
				$headers = array(
					sprintf( 'From: %s <%s> ', get_option( 'blogname' ), $admin_emails[0] )
				);
			}

			$recipients = apply_filters(
				'ms_model_communication_send_message_html_message',
				$recipients,
				$this,
				$subscription
			);
			$html_message = apply_filters(
				'ms_model_communication_send_message_html_message',
				$html_message,
				$this,
				$subscription
			);
			$text_message = apply_filters(
				'ms_model_communication_send_message_text_message',
				$text_message,
				$this,
				$subscription
			);
			$subject = apply_filters(
				'ms_model_communication_send_message_subject',
				$subject,
				$this,
				$subscription
			);
			$headers = apply_filters(
				'ms_model_communication_send_message_headers',
				$headers,
				$this,
				$subscription
			);

			/*
			 * Send the mail.
			 * wp_mail will not throw an error, so no error-suppression/handling
			 * is required here. On error the function response is FALSE.
			 */
			$sent = wp_mail( $recipients, $subject, $message, $headers );

			// Log the outgoing email.
			do_action(
				'lib2_debug_log',
				sprintf(
					'Sent email [%s] to <%s>: %s',
					$this->type,
					implode( '>, <', $recipients ),
					$sent ? 'OK' : 'ERR'
				)
			);

			/*
			// -- Debugging code --
			MS_Helper_Debug::log(
				sprintf(
					"Variables:\n%s",
					print_r( $comm_vars, true )
				)
			);
			//*/

			if ( 'text/html' == $this->get_mail_content_type() ) {
				$this->remove_filter(
					'wp_mail_content_type',
					'get_mail_content_type'
				);
			}
		}

		do_action(
			'ms_model_communication_send_message_before',
			$subscription,
			$this
		);

		return $sent;
	}

	/**
	 * Replace comm_vars with corresponding values.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship The membership relationship to send message to.
	 * @param WP_User $wp_user The wordpress user object to get info from.
	 * @return array {
	 *     Returns array of ( $var_name => $var_replace ).
	 *
	 *     @type string $var_name The variable name to replace.
	 *     @type string $var_replace The variable corresponding replace string.
	 * }
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

		if ( in_array( $class, $previous_invoice ) ) {
			if ( ! $invoice = MS_Model_Invoice::get_previous_invoice( $ms_relationship ) ) {
				$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
			}
		} else {
			$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		}

		$comm_vars = apply_filters(
			'ms_model_communication_comm_vars',
			$this->comm_vars
		);

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
					if ( $membership->name ) {
						$comm_vars[ $key ] = $membership->name;
					} else {
						$comm_vars[ $key ] = '';
					}
					break;

				case self::COMM_VAR_MS_INVOICE:
					if ( isset( $invoice )
						&& ( $invoice->total > 0 || $invoice->trial_period )
					) {
						$attr = array( 'post_id' => $invoice->id, 'pay_button' => 0 );
						$scode = MS_Plugin::instance()->controller->controllers['membership_shortcode'];
						$comm_vars[ $key ] = $scode->membership_invoice( $attr );
					} else {
						$comm_vars[ $key ] = '';
					}
					break;

				case self::COMM_VAR_MS_ACCOUNT_PAGE_URL:
					$comm_vars[ $key ] = sprintf(
						'<a href="%s">%s</a>',
						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_ACCOUNT ),
						__( 'account page', MS_TEXT_DOMAIN )
					);
					break;

				case self::COMM_VAR_MS_REMAINING_DAYS:
					$days = $ms_relationship->get_remaining_period();
					$comm_vars[ $key ] = sprintf(
						__( '%s day%s', MS_TEXT_DOMAIN ),
						$days,
						abs( $days ) > 1 ? 's': ''
					);
					break;

				case self::COMM_VAR_MS_REMAINING_TRIAL_DAYS:
					$days = $ms_relationship->get_remaining_trial_period();
					$comm_vars[ $key ] = sprintf(
						__( '%s day%s', MS_TEXT_DOMAIN ),
						$days,
						abs( $days ) > 1 ? 's': ''
					);
					break;

				case self::COMM_VAR_MS_EXPIRY_DATE:
					$comm_vars[ $key ] = $ms_relationship->expire_date;
					break;
			}

			$comm_vars[ $key ] = apply_filters(
				'ms_model_communication_send_message_comm_var_' . $key,
				$comm_vars[ $key ],
				$ms_relationship
			);
		}

		return apply_filters(
			'ms_model_communication_get_comm_vars',
			$comm_vars
		);
	}

	/**
	 * Get Email content type.
	 *
	 * Eg. text/html, text.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_mail_content_type() {
		$this->content_type = apply_filters(
			'ms_model_communication_set_html_content_type',
			'text/html'
		);

		return $this->content_type;
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'type':
					if ( $this->is_valid_communication_type( $value ) ) {
						$this->$property = $value;
					}
					break;

				case 'subject':
					$this->$property = sanitize_text_field( $value );
					break;

				case 'cc_email':
					if ( is_email( $value ) ) {
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
			switch ( $property ) {
				case 'period_unit':
					$this->period['period_unit'] = $this->validate_period_unit( $value );
					break;

				case 'period_type':
					$this->period['period_type'] = $this->validate_period_type( $value );
					break;
			}
		}

		do_action(
			'ms_model_communication__set_after',
			$property,
			$value,
			$this
		);
	}

};