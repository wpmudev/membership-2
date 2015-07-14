<?php
/**
 * Abstract Option model.
 *
 * @uses WP Option API to persist data into wp_option table.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Option extends MS_Model {

	/**
	 * Singleton instance.
	 *
	 * @since  1.0.0
	 *
	 * @staticvar MS_Model_Option
	 */
	public static $instance;

	/**
	 * Save content in wp_option table.
	 *
	 * Update WP cache and instance singleton.
	 *
	 * @since  1.0.0
	 */
	public function save() {
		$this->before_save();

		$option_key = $this->option_key();

		$settings = MS_Factory::serialize_model( $this );
		MS_Factory::update_option( $option_key, $settings );

		$this->instance = $this;
		$this->after_save();

		wp_cache_set( $option_key, $this, 'MS_Model_Option' );
	}

	/**
	 * Reads the options from options table
	 *
	 * @since  1.0.0
	 */
	public function refresh() {
		$option_key = $this->option_key();

		$settings = MS_Factory::get_option( $option_key );
		MS_Factory::populate_model( $this, $settings );

		wp_cache_set( $option_key, $this, 'MS_Model_Option' );
	}

	/**
	 * Delete from wp option table
	 *
	 * @since  1.0.0
	 */
	public function delete() {
		do_action( 'ms_model_option_delete_before', $this );

		$option_key = $this->option_key();

		MS_Factory::delete_option( $option_key );
		wp_cache_delete( $option_key, 'MS_Model_Option' );

		do_action( 'ms_model_option_delete_after', $this );
	}

	/**
	 * validates and prepares the option key before it is used to read/write
	 * a value in the database.
	 *
	 * @since  1.0.0
	 * @api Used by MS_Factory
	 *
	 * @return string
	 */
	public function option_key() {
		// Option key should be all lowercase.
		$key = strtolower( get_class( $this ) );

		// Network-wide mode uses different options then single-site mode.
		if ( MS_Plugin::is_network_wide() ) {
			$key .= '-network';
		}

		return substr( $key, 0, 64 );
	}
}