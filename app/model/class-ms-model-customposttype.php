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
 * Abstract Custom Post Type model.
 *
 * Persists data into wp_post and wp_postmeta
 *
 * @since 1.0.0
 *
 * @package Membership
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
	public static $POST_TYPE;
	public $post_type;

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
	 * Not persisted fields.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	static public $ignore_fields = array( 'post_type' );

	/**
	 * Sub-objects that need to be reset when de-serializing the object
	 *
	 * @since 1.1.0
	 * @var array
	 */
	public $_subobjects = array( 'rules' );

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
		$this->before_save();

		$this->post_modified = gmdate( 'Y-m-d H:i:s' );

		$class = get_class( $this );
		$post = array(
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_author' => $this->user_id,
			'post_content' => $this->description,
			'post_excerpt' => $this->description,
			'post_name' => sanitize_text_field( $this->name ),
			'post_status' => 'private',
			'post_title' => sanitize_title( ! empty( $this->title ) ? $this->title : $this->name ),
			'post_type' => $this->post_type,
			'post_modified' => $this->post_modified,
		);

		if ( empty( $this->id ) ) {
			$this->id = wp_insert_post( $post );
		} else {
			$post[ 'ID' ] = $this->id;
			wp_update_post( $post );
		}

		// save attributes in postmeta table
		$data = MS_Factory::serialize_model( $this );

		// We first remove any metadata of our custom post type that is not
		// contained in the serialized data collection.
		$this->clean_metadata( array_keys( $data ) );

		// Then we update all meta fields that are inside the collection
		foreach ( $data as $field => $val ) {
			update_post_meta( $this->id, $field, $val );
		}

		wp_cache_set( $this->id, $this, $class );
		$this->after_save();
	}

	/**
	 * Delete post from wp table
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		do_action( 'MS_Model_CustomPostType_delete_before', $this );
		$res = false;

		if ( ! empty( $this->id ) ) {
			$res = ( false !== wp_delete_post( $this->id, true ) );
		}

		do_action( 'MS_Model_CustomPostType_delete_after', $this, $res );
		return $res;
	}

	/**
	 * Removes all meta fields, except the ones that are specified in the
	 * second parameter.
	 *
	 * @since  1.1.0
	 * @param  array $data_to_keep List of meta-fields to keep (field-names)
	 */
	public function clean_metadata( $data_to_keep ) {
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
			'MS_Model_CustomPostType_register_post_type_args',
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
		$lock = false;

		if ( $this->is_valid() ) {
			$lock = apply_filters(
				'MS_Model_CustomPostType_set_object_lock',
				time()
			);
			update_post_meta( $this->id, '_ms_edit_lock', $lock );
		}

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
		if ( $this->is_valid() ) {
			update_post_meta( $this->id, '_ms_edit_lock', '' );
		}

		do_action( 'MS_Model_CustomPostType_delete_object_lock', $this );
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
}