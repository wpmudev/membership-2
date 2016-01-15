<?php
/**
 * Model.
 * @package Membership2
 */

/**
 * Email Log Model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.2.7
 */
class MS_Model_Communicationlog extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since  1.0.2.7
	 *
	 * @var string
	 */
	protected static $POST_TYPE = 'ms_communication_log';

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


	/*
	 *
	 *
	 * -------------------------------------------------------------- COLLECTION
	 */


	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  1.0.2.7
	 * @return string The post-type name.
	 */
	public static function get_post_type() {
		return parent::_post_type( self::$POST_TYPE );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since  1.0.2.7
	 * @return array Post Type details.
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Membership2 Communication Logs', 'membership2' ),
			'supports'            => array(),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::get_post_type()
		);
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
		$args = lib3()->array->get( $args );
		$args['posts_per_page'] = -1;
		$items = self::get_items( $args );

		$count = count( $items );

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
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );
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
			'post_type' => self::get_post_type(),
			'post_status' => 'any',
			'fields' => 'ids',
			'order' => 'DESC',
			'orderby' => 'ID',
			'posts_per_page' => 20,
		);

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_communicationlog_get_item_args',
			$args
		);
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
}
