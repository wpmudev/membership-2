<?php
/**
 * Model.
 * @package Membership2
 */

/**
 * Email Log Model.
 *
 * Persisted by parent class MS_Model_Entity.
 *
 * @since  1.1.2
 */
class MS_Model_Communicationlog extends MS_Model_Entity {

	/**
	 * Response of wp_mail, indicates if the email was sent successfully.
	 *
	 * @since 1.0.2.7
	 * @var   bool
	 */
	protected $sent = false;

	/**
	 * The email recipients' address.
	 *
	 * @since 1.0.2.7
	 * @var   string
	 */
	protected $recipient = '';

	/**
	 * The subscription linked with the email.
	 *
	 * @since 1.0.2.7
	 * @var   int
	 */
	protected $subscription_id = 0;

	/**
	 * Simple backtrace to function that sent the email.
	 *
	 * @since 1.0.2.7
	 * @var   string
	 */
	protected $trace = '';


	/**
	 * Set model variables needed
	 *
	 * @since 1.2
	 */
	 function _before_prepare_obj() {
		$this->has_meta  	= false;
		$this->table_name 	= MS_Helper_Database::get_table_name( MS_Helper_Database::COMMUNICATION_LOG );
	}

	/**
	 * Get the total number of log entries.
	 * For list table pagination.
	 *
	 * @since  1.0.2.7
	 *
	 * @param  array $args The default query args.
	 * @return int The total count.
	 */
	public static function get_item_count( $args = null ) {
		$args 				= lib3()->array->get( $args );
		$args['per_page'] 	= -1;
		$items 				= self::get_items( $args );
		$count 				= count( $items );

		return apply_filters(
			'ms_model_communicationlog_get_item_count',
			$count,
			$args
		);
	}

	/**
	 * Get transaction log items.
	 *
	 * @since  1.0.2.7
	 *
	 * @param  array $args The query post args.
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array List of transaction log items.
	 */
	public static function get_items( $args = null ) {
		MS_Factory::select_blog();
		$args 	= self::get_query_args( $args );
		$query 	= new MS_Helper_Database_Query_Communication_Log( $args );
		MS_Factory::revert_blog();

		$items = array();
		foreach ( $query->posts as $post_id ) {
			$items[] = MS_Factory::load( 'MS_Model_Communicationlog', $post_id );
		}

		return apply_filters(
			'ms_model_communicationlog_get_items',
			$items,
			$args
		);
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since  1.0.2.7
	 *
	 * @param array $args The query post args.
	 *        @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args ) {
		$defaults = array(
			'fields' 		=> 'ids',
			'order' 		=> 'DESC',
			'orderby' 		=> 'ID',
			'per_page' 		=> 20,
		);

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_communicationlog_get_item_args',
			$args
		);
	}


	/**
	 * Save the current communication log item.
	 * Called in parent class
	 *
	 * @since  1.2
	 */
	function _save() {

		$this->_maybe_persist( array(
			'sent' 				=> $this->sent,
			'recipient' 		=> $this->recipient,
			'subscription_id' 	=> $this->subscription_id,
			'trace' 			=> $this->trace,
			'title'				=> $this->title,
			'author' 			=> $this->user_id,
			'date_created' 		=> MS_Helper_Period::current_date( 'Y-m-d H:i:s' )
		) );
	}

	/*
	 *
	 *
	 * ------------------------------------------------------------- SINGLE ITEM
	 */


	/**
	 * Returns property associated with the render.
	 *
	 * @since  1.0.2.7
	 * @internal
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_model_communicationlog__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Set specific property.
	 *
	 * @since  1.0.2.7
	 * @internal
	 * @param string $property The name of a property to associate.
	 * @param mixed  $value The value of a property.
	 */
	public function __set( $property, $value ) {
		switch ( $property ) {

			default:
				if ( property_exists( $this, $property ) ) {
					$this->$property = $value;
				}
				break;
		}

		do_action(
			'ms_model_communicationlog__set_after',
			$property,
			$value,
			$this
		);
	}

	/**
	 * Check if property isset.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns true/false.
	 */
	public function __isset( $property ) {
		return isset( $this->$property );
	}		
}
