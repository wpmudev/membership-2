<?php
/**
 * Abstract Custom Post Type model.
 *
 * Persists data into wp_post and wp_postmeta
 *
 * @since 1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_CustomPostType extends MS_Model {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 * Override this value in child object.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected static $POST_TYPE;

	/**
	 * ID of the model object.
	 *
	 * Saved as WP post ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Model name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Model title.
	 *
	 * Saved in $post->post_title.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Model description.
	 *
	 * Saved in $post->post_content and $post->excerpt.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * The user ID of the owner.
	 *
	 * Saved in $post->post_author
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * The last modified date.
	 *
	 * Saved in $post->post_modified
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $post_modified;

	/**
	 * Custom data can be used by other plugins via the set_custom_data() and
	 * get_custom_data() functions.
	 *
	 * This can be used to store data that other plugins use to store object
	 * related information, like affiliate options for a membership, etc.
	 *
	 * @since  2.0
	 *
	 * @var array
	 */
	protected $custom_data = array();

	/**
	 * Not persisted fields.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	static public $ignore_fields = array();

	/**
	 * Validates the object right after it was loaded/initialized.
	 *
	 * We ensure that the custom_data field is an array.
	 *
	 * @since  2.0.0
	 */
	public function prepare_obj() {
		parent::prepare_obj();

		if ( ! is_array( $this->custom_data ) ) {
			$this->custom_data = array();
		}
	}

	/**
	 * Save content in wp tables (wp_post and wp_postmeta).
	 *
	 * Update WP cache.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	public function save() {
		MS_Factory::select_blog();
		$this->before_save();

		$this->post_modified = gmdate( 'Y-m-d H:i:s' );
		$class = get_class( $this );

		/*
		 * Serialize data that is later saved to the postmeta table.
		 *
		 * While data is serialized it can also modify the model data before
		 * writing it to the posts table.
		 */
		$data = MS_Factory::serialize_model( $this );

		$post = array(
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_author' => $this->user_id,
			'post_content' => $this->description,
			'post_excerpt' => $this->description,
			'post_name' => sanitize_text_field( $this->name ),
			'post_status' => 'private',
			'post_title' => sanitize_title( ! empty( $this->title ) ? $this->title : $this->name ),
			'post_type' => $this->get_post_type(),
			'post_modified' => $this->post_modified,
		);

		if ( empty( $this->id ) ) {
			$this->id = wp_insert_post( $post );
		} else {
			$post[ 'ID' ] = $this->id;
			wp_update_post( $post );
		}

		// We first remove any metadata of our custom post type that is not
		// contained in the serialized data collection.
		$this->clean_metadata( array_keys( $data ) );

		// Then we update all meta fields that are inside the collection
		foreach ( $data as $field => $val ) {
			update_post_meta( $this->id, $field, $val );
		}

		wp_cache_set( $this->id, $this, $class );
		$this->after_save();
		MS_Factory::revert_blog();

		global $wp_current_filter;
		if ( ! in_array( 'ms_saved_' . $class, $wp_current_filter ) ) {
			/**
			 * Action triggered after a custom post type model was saved to
			 * database.
			 *
			 * @since 2.0.0
			 */
			do_action( 'ms_saved_' . $class, $this );
		}
	}

	/**
	 * Delete post from wp table
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		MS_Factory::select_blog();
		do_action( 'MS_Model_CustomPostType_delete_before', $this );
		$res = false;

		if ( ! empty( $this->id ) ) {
			$res = ( false !== wp_delete_post( $this->id, true ) );
		}

		do_action( 'MS_Model_CustomPostType_delete_after', $this, $res );
		MS_Factory::revert_blog();
		return $res;
	}

	/**
	 * Removes all meta fields, except the ones that are specified in the
	 * second parameter.
	 *
	 * @since  1.1.0
	 * @param  array $data_to_keep List of meta-fields to keep (field-names)
	 */
	private function clean_metadata( $data_to_keep ) {
		global $wpdb;

		$sql = "SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %s;";
		$sql = $wpdb->prepare( $sql, $this->id );
		$all_fields = $wpdb->get_col( $sql );

		$remove = array_diff( $all_fields, $data_to_keep );

		$remove = apply_filters(
			'ms_model_clean_metadata',
			$remove,
			$all_fields,
			$this->id,
			$data_to_keep
		);

		foreach ( $remove as $key ) {
			delete_post_meta( $this->id, $key );
		}
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0
	 */
	public static function get_register_post_type_args() {
		return apply_filters(
			'ms_customposttype_register_args',
			array()
		);
	}

	/**
	 * Check to see if the post is currently being edited.
	 *
	 * @see wp_check_post_lock.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if locked.
	 */
	public function check_object_lock() {
		MS_Factory::select_blog();
		$locked = false;

		if ( $this->is_valid()
			&& $lock = get_post_meta( $this->id, '_ms_edit_lock', true )
		) {
			$time = $lock;
			$time_window = apply_filters(
				'MS_Model_CustomPostType_check_object_lock_window',
				150
			);
			if ( $time && $time > time() - $time_window ) {
				$locked = true;
			}
		}

		MS_Factory::revert_blog();
		return apply_filters(
			'MS_Model_CustomPostType_check_object_lock',
			$locked,
			$this
		);
	}

	/**
	 * Mark the object as currently being edited.
	 *
	 * Based in the wp_set_post_lock
	 *
	 * @since 1.0.0
	 *
	 * @return bool|int
	 */
	public function set_object_lock() {
		MS_Factory::select_blog();
		$lock = false;

		if ( $this->is_valid() ) {
			$lock = apply_filters(
				'MS_Model_CustomPostType_set_object_lock',
				time()
			);
			update_post_meta( $this->id, '_ms_edit_lock', $lock );
		}

		MS_Factory::revert_blog();
		return apply_filters(
			'MS_Model_CustomPostType_set_object_lock',
			$lock,
			$this
		);
	}

	/**
	 * Delete object lock.
	 *
	 * @since 1.0.0
	 */
	public function delete_object_lock() {
		MS_Factory::select_blog();
		if ( $this->is_valid() ) {
			update_post_meta( $this->id, '_ms_edit_lock', '' );
		}

		do_action( 'MS_Model_CustomPostType_delete_object_lock', $this );
		MS_Factory::revert_blog();
	}

	/**
	 * Check if the current post type exists.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if valid.
	 */
	public function is_valid() {
		$valid = false;

		if ( $this->id > 0 ) {
			$valid = true;
		}

		return apply_filters(
			'MS_Model_CustomPostType_is_valid',
			$valid,
			$this
		);
	}

	/**
	 * Either creates or updates the value of a custom data field.
	 *
	 * Note: Remember to prefix the $key with a unique string to prevent
	 * conflicts with other plugins that also use this function.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 * @param  mixed $value The new value to assign to the field.
	 */
	public function set_custom_data( $key, $value ) {
		$this->custom_data[ $key ] = $value;
	}

	/**
	 * Removes a custom data field from this object.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 */
	public function delete_custom_data( $key ) {
		unset( $this->custom_data[ $key ] );
	}

	/**
	 * Returns the value of a custom data field.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 * @return mixed The value that was previously assigned to the custom field
	 *         or false if no value was set for the field.
	 */
	public function get_custom_data( $key ) {
		$res = false;
		if ( isset( $this->custom_data[ $key ] ) ) {
			$res = $this->custom_data[ $key ];
		}
		return $res;
	}

	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  2.0.0
	 * @return string The post-type name.
	 */
	protected static function _post_type( $orig_posttype ) {
		// Post-type is always lower case.
		$posttype = strtolower( substr( $orig_posttype, 0, 20 ) );

		// Network-wide mode uses different post-types then single-site mode.
		if ( MS_Plugin::is_network_wide() ) {
			$posttype = substr( $posttype, 0, 18 );
			$posttype .= '-n';
		}

		return $posttype;
	}
}