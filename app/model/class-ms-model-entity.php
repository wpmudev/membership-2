<?php

/**
 * Abstract Custom Database Type model.
 *
 * Persists data into the custom database table of the object
 *
 * @since  1.2
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Entity extends MS_Model {

	/**
	 * ID of the model object.
	 *
	 * @since  1.2
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Model name (this is the model name)
	 *
	 * @since  1.2
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Model description.
	 *
	 * Saved in description if it exists
	 *
	 * @since  1.2
	 *
	 * @var string
	 */
	 protected $description = '';
	 
	/**
	 * The user ID of the owner.
	 *
	 * Saved in user_id
	 *
	 * @since  1.2
	 *
	 * @var int
	 */
	protected $user_id = 0;


	/**
	 * Custom data can be used by other plugins via the set_custom_data() and
	 * get_custom_data() functions.
	 *
	 * This can be used to store data that other plugins use to store object
	 * related information, like affiliate options for a membership, etc.
	 *
	 * @since  1.2
	 *
	 * @var array
	 */
	 protected $custom_data = array();
	 
	/**
	 * Not persisted fields.
	 *
	 * @since  1.2
	 *
	 * @var array
	 */
	static public $ignore_fields = array();

	/**
	 *
	 * If table has meta
	 * Set to true to save in the meta table
	 *
	 * @since 1.2
	 * @var Boolean
	 */
	protected $has_meta = false;

	/**
	 *
	 * Name of the meta object type
	 *
	 * @since 1.2
	 * @var String
	 */
	protected $meta_name;


	/**
	 *
	 * Name of the database table
	 *
	 * @since 1.2
	 * @var String
	 */
	protected $table_name;


	/**
	 * Hold the filds not to save in the meta table
	 *
	 * @since 1.2
	 * @internal
	 * @var Array
	 */
	protected $ignore_meta = array();


	/**
	 * MS_Model_Entity Constructor
	 *
	 * @since  1.2
	 */
	public function __construct() {
		$this->prepare_obj();

		/**
		 * Actions to execute when constructing the parent Model.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Model object.
		 */
		do_action( 'ms_model_construct', $this );
	}

	/**
	 * Validates the object right after it was loaded/initialized.
	 * We ensure that the custom_data field is an array and the table name is set
	 *
	 * @since  1.2
	 */
	public function prepare_obj() {
		parent::prepare_obj();
		$this->_before_prepare_obj();

		$this->before_load();

		if ( ! is_array( $this->custom_data ) ) {
			$this->custom_data = array();
		}

		if ( $this->has_meta ){
			if ( empty( $this->meta_name ) ) {
				throw new Exception( 'Class ' . get_class( $this ) . ' has no meta name defined' );
			}
		}

		if ( $this->table_name == false || empty( $this->table_name ) ) {
			throw new Exception( 'Class ' . get_class( $this ) . ' has no table name defined' );
		}
	}

	/**
	 * Prepare object
	 * Called in the child class mainly to define the variables needed
	 *
	 * @since 1.2
	 */
	protected function _before_prepare_obj() {
		throw new Exception( 'Class ' . get_class( $this ) . ' has not been initiated correctly' );
	}

	/**
	 * Set up values before load via the Factory Loader
	 *
	 * @since 1.2
	 */
	function before_load() {
		parent::before_load();
		$this->_before_prepare_obj();
	}


	/**
	 * Save content in the tables.
	 *
	 * Update WP cache.
	 *
	 * @since  1.0.3.7
	 */
	 public function save() {
		MS_Factory::select_blog();
		$this->before_load();
		$this->before_save();

		$class = get_class( $this );

		/*
		 * Serialize data that is later saved to the postmeta table.
		 *
		 * While data is serialized it can also modify the model data before
		 * writing it to the posts table.
		 */
		$data = MS_Factory::serialize_model( $this );
		$this->_save();

		if ( is_numeric( $this->id ) && absint( $this->id ) ) {
			
			if ( $this->has_meta ) {

				// We first remove any metadata of our custom post type that is not
				// contained in the serialized data collection.
				$this->clean_metadata( array_keys( $data ) );

				//Save the meta
				// Then we update all meta fields that are inside the collection
				foreach ( $data as $field => $val ) {
					if ( is_array( $this->ignore_meta ) && ! in_array( $field, $this->ignore_meta ) ){
						$this->save_meta( $this->id, $field, $val );
					}
				}
			}

			wp_cache_set( $this->id, $this, $class );
			$this->after_save();
		} else {
			$this->log( 'Error saving Data to ' . $this->table_name );
		}

		MS_Factory::revert_blog();

		global $wp_current_filter;
		if ( ! in_array( 'ms_saved_' . $class, $wp_current_filter ) ) {
			/**
			 * Action triggered after a custom post type model was saved to
			 * database.
			 *
			 * @since  1.0.0
			 */
			do_action( 'ms_saved_' . $class, $this );
		}
	}

	/**
	 * Database get
	 *
	 * @since 1.2
	 *
	 * @return Array
	 */
	public function get( $id ) {
		global $wpdb;
		$this->before_load();
		if ( !is_numeric( $id ) ) {
			return;
		}
		$values = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE ID = %d", $id ) );

		return $values;
	}

	/**
	 * Model save function
	 * Set up the data to be saved or updated
	 *
	 * @since 1.2
	 */
	protected function _save() {
		
	}


	/**
	 * Perform insert
	 *
	 * @param Array $data - the insert data
	 *
	 * @since  1.2
	 *
	 * @return bool
	 */
	private function _maybe_insert( $data ) {
		global $wpdb;
		if ( empty( $data ) ) {
			return false;
		}
		$result = $wpdb->insert( $this->table_name, $data );

		if ( false === $result )
            return false;

        $object_id 	= (int) $wpdb->insert_id;
		$this->id 	= $object_id;

        return true;
	}

	/**
	 * Persist
	 *
	 * @param Array $data - the data to be saved or updated
	 *
	 * @since  1.2
	 *
	 * @return bool|int
	 */
	protected function _maybe_persist( $data ) {
		global $wpdb;

		if ( empty( $data ) ){
			return false;
		}

		if ( is_numeric( $this->id ) && absint( $this->id ) ) {
			if ( isset( $data['id'] ) ) {
				unset( $data['id'] );
			}
			if ( isset( $data['ID'] ) ) {
				unset( $data['ID'] );
			}
			$result = $wpdb->update( $this->table_name, $data, array(
				'ID' => $this->id
			) );
			if ( false !== $result )
				return true;
		} else {
			return $this->_maybe_insert( $data );
		}
		return false;
	}



	/**
	 * Save Meta
	 *
	 * @since  1.0.3.7
	 *
	 * @return int|bool
	 */
	protected function save_meta( $id, $key, $value ) {
		if ( $this->has_meta ) {
			return MS_Helper_Database_TableMeta::update( $this->meta_name, $id, $key, $value );
		}
		return false;
	}

	/**
	 * Delete Meta
	 *
	 * @since  1.0.3.7
	 */
	protected function delete_meta( $id, $key, $all = false ) {
		if ( $this->has_meta ) {
			if ( $all ) {
				MS_Helper_Database_TableMeta::delete_all( $this->meta_name, $id );
			} else {
				MS_Helper_Database_TableMeta::delete( $this->meta_name, $id, $key );
			}
			
		}
	}

	/**
	 * Get Meta
	 *
	 * @since  1.0.3.7
	 *
	 * @return bool|string|array
	 */
	protected function get_meta( $id, $key, $single ) {
		if ( $this->has_meta ) {
			return MS_Helper_Database_TableMeta::get( $this->meta_name, $id, $key, $single );
		}
		return false;
	}

	/**
	 * Delete post from wp table
	 *
	 * @since  1.2
	 *
	 * @return bool
	 */
	public function delete() {
		MS_Factory::select_blog();
		$this->before_load();
		global $wpdb;
		do_action( 'MS_Model_Entity_delete_before', $this );
		$res = false;

		if ( is_numeric( $this->id ) && absint( $this->id ) ) {
			//Delete all meta first
			$this->delete_meta( $this->id, '', true );

			$query 	= "DELETE FROM {$this->table_name} WHERE `ID` = %d";
       	 	$res 	= $wpdb->query( $wpdb->prepare( $query, $this->id ) );
		}

		do_action( 'MS_Model_Entity_delete_after', $this, $res );
		MS_Factory::revert_blog();
		return $res;
	}

	/**
	 * Removes all meta fields, except the ones that are specified in the
	 * second parameter.
	 *
	 * @since  1.2
	 * @param  array $data_to_keep List of meta-fields to keep (field-names)
	 */
	private function clean_metadata( $data_to_keep ) {

		$all_fields = MS_Helper_Database_TableMeta::keys( $this->meta_name, $this->id );

		if ( is_array( $all_fields ) ) {
			$remove = array_diff( $all_fields, $data_to_keep );
		} else {
			$remove = array();
		}

		$remove = apply_filters(
			'ms_model_clean_metadata',
			$remove,
			$all_fields,
			$this->id,
			$data_to_keep
		);

		foreach ( $remove as $key ) {
			$this->delete_meta( $this->id, $key );
		}
	}

	/**
	 * Check to see if the post is currently being edited.
	 *
	 * @see wp_check_post_lock.
	 *
	 * @since  1.2
	 *
	 * @return boolean True if locked.
	 */
	public function check_object_lock() {
		MS_Factory::select_blog();
		$locked = false;

		if ( $this->is_valid()
			&& $lock = $this->get_meta( $this->id, '_ms_edit_lock', true )
		) {
			$time 			= $lock;
			$time_window 	= apply_filters(
				'MS_Model_Entity_check_object_lock_window',
				150
			);
			if ( $time && $time > time() - $time_window ) {
				$locked = true;
			}
		}

		MS_Factory::revert_blog();
		return apply_filters(
			'MS_Model_Entity_check_object_lock',
			$locked,
			$this
		);
	}

	/**
	 * Mark the object as currently being edited.
	 *
	 * Based in the wp_set_post_lock
	 *
	 * @since  1.2
	 *
	 * @return bool|int
	 */
	public function set_object_lock() {
		MS_Factory::select_blog();
		$lock = false;

		if ( $this->is_valid() ) {
			$lock = apply_filters(
				'MS_Model_Entity_set_object_lock',
				time()
			);
			$this->save_meta( $this->id, '_ms_edit_lock', $lock );
		}

		MS_Factory::revert_blog();
		return apply_filters(
			'MS_Model_Entity_set_object_lock',
			$lock,
			$this
		);
	}

	/**
	 * Delete object lock.
	 *
	 * @since  1.2
	 */
	public function delete_object_lock() {
		MS_Factory::select_blog();
		if ( $this->is_valid() ) {
			$this->save_meta( $this->id, '_ms_edit_lock', '' );
		}

		do_action( 'MS_Model_Entity_delete_object_lock', $this );
		MS_Factory::revert_blog();
	}

	/**
	 * Check if the current post type exists.
	 *
	 * @since  1.2
	 *
	 * @return boolean True if valid.
	 */
	public function is_valid() {
		$valid = false;

		if ( $this->id > 0 ) {
			$valid = true;
		}

		return apply_filters(
			'MS_Model_Entity_is_valid',
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
	 * @since  1.2
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
	 * @since  1.2
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
	 * @since  1.2
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
	 * Populate the model with custom data from the wp_posts table.
	 *
	 * @see    MS_Factory::load_from_custom_table()
	 * @since  1.2
	 * @param  array $post Data collection passed to wp_update_post().
	 */
	public function load_table_data( $post ) {
	}

	/**
	 * Populate the model with custom data from the wp_postmeta table.
	 *
	 * @see    MS_Factory::load_from_custom_table()
	 * @since  1.2
	 * @param  array $data Key-Value pairs that represent metadata.
	 */
	public function load_meta_data( $data ) {
	}
}
?>