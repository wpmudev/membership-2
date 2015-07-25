<?php
/**
 * Transaction Log Model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.1.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Transactionlog extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since  1.0.1.0
	 *
	 * @var string
	 */
	protected static $POST_TYPE = 'ms_transaction_log';

	/**
	 * Timestamp of the transaction.
	 *
	 * @since 1.0.1.
	 * @var   string
	 */
	protected $date = '';

	/**
	 * The gateway that made the call.
	 *
	 * @since 1.0.1.
	 * @var   string
	 */
	protected $gateway_id = '';

	/**
	 * The transaction method.
	 *
	 * Possible methods are:
	 *        "handle": IPN response
	 *        "process": Process order (i.e. user comes from Payment screen)
	 *        "request": Automatically request recurring payment
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	protected $method = '';

	/**
	 * Indicator if the transaction was successfully processed by M2
	 *
	 * True means that an invoice was marked paid.
	 * False indicates an error or unknown input.
	 * NULL indicates a message that was processed but irrelevant.
	 *
	 * @since 1.0.1.0
	 * @var   bool|null
	 */
	protected $success = null;

	/**
	 * Transaction state, similar to $success but as string.
	 *
	 * 'ok' means that an invoice was marked paid.
	 * 'err' indicates an error or unknown input.
	 * 'ignored' indicates a message that was processed but irrelevant.
	 *
	 * @since 1.0.1.0
	 * @var   string $state Access via $item->state (not $item->_state!)
	 */
	protected $_state = null;

	/**
	 * The subscription linked with the transaction.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $subscription_id = 0;

	/**
	 * The invoice linked with the transaction.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $invoice_id = 0;

	/**
	 * The transaction amount reported by the gateway.
	 *
	 * @since 1.0.1.0
	 * @var   int
	 */
	protected $amount = 0;

	/**
	 * The URL used to report the transaction.
	 *
	 * This is especially relevant for IPN messages (method "handle")
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	protected $url = '';

	/**
	 * A collection of all POST parameters passed to the $url.
	 *
	 * @since 1.0.1.0
	 * @var   array
	 */
	protected $post = null;


	//
	//
	//
	// -------------------------------------------------------------- COLLECTION


	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  1.0.1.0
	 * @return string The post-type name.
	 */
	public static function get_post_type() {
		return parent::_post_type( self::$POST_TYPE );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since  1.0.1.0
	 * @return array Post Type details.
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Membership2 Transaction Logs', MS_TEXT_DOMAIN ),
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
	 * @since  1.0.1.0
	 *
	 * @param  array $args The default query args.
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total count.
	 */
	public static function get_item_count( $args = null ) {
		MS_Factory::select_blog();
		$args = self::get_query_args( $args );
		unset( $args['posts_per_page'] );
		$query = new WP_Query( $args );
		MS_Factory::revert_blog();

		return apply_filters(
			'ms_model_transactionlog_get_item_count',
			$query->found_posts,
			$args
		);
	}

	/**
	 * Get transaction log items.
	 *
	 * @since  1.0.1.0
	 *
	 * @param  $args The query post args.
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
			$items[] = MS_Factory::load( 'MS_Model_Transactionlog', $post_id );
		}

		return apply_filters(
			'ms_model_transactionlog_get_items',
			$items,
			$args
		);
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since  1.0.1.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args ) {
		$defaults = array(
			'post_type' => self::get_post_type(),
			'post_status' => 'any',
			'fields' => 'ids',
			'order' => 'DESC',
			'orderby' => 'ID',
		);

		if ( ! empty( $args['meta_query'] ) ) {
			if ( is_array( $args['meta_query']['gateway_id'] ) ) {
				$args['meta_query']['gateway_id']['key'] = '_gateway_id';
			}
		}

		$args = wp_parse_args( $args, $defaults );

		return apply_filters(
			'ms_model_transactionlog_get_item_args',
			$args
		);
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Constructor, initialize a new item.
	 *
	 * @since 1.0.1.0
	 */
	public function __construct() {
		$this->url = lib2()->net->current_url();
		$this->post = $_POST;
		$this->user_id = get_current_user_id();
		$this->title = 'Transaction Log';
	}

	/**
	 * Prepares an object after it was loaded from database.
	 *
	 * @since  1.0.1.0
	 */
	public function prepare_obj() {
		$this->set_state( $this->success );
	}

	/**
	 * Populate custom fields from the wp_posts table.
	 *
	 * @since  1.0.1.0
	 * @param  WP_Post $post The post object.
	 */
	public function load_post_data( $post ) {
		$this->date = $post->post_date;
	}

	/**
	 * Updates the state and success properties of the object.
	 *
	 * @since 1.0.1.0
	 * @param mixed $value The new state value
	 */
	protected function set_state( $value ) {
		switch ( $value ) {
			case 'ok':
				$this->_state = $value;
				$this->success = true;
				break;

			case 'ignored':
				$this->_state = $value;
				$this->success = null;
				break;

			case 'err':
				$this->_state = $value;
				$this->success = false;
				break;

			case true:
				$this->_state = 'ok';
				$this->success = $value;
				break;

			case false:
				$this->_state = 'err';
				$this->success = $value;
				break;

			case null:
				$this->_state = 'ignored';
				$this->success = $value;
				break;

			default:
				// Unrecognized values are not saved.
		}
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {
			case 'state':
				$value = $this->_state;
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_model_transactionlog__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Set specific property.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		switch ( $property ) {
			case 'state':
			case 'success':
				$this->set_state( $value );
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$this->$property = $value;
				}
				break;
		}

		do_action(
			'ms_model_transactionlog__set_after',
			$property,
			$value,
			$this
		);
	}
}