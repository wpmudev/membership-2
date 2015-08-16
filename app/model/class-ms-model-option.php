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
	 * Settings data for extensions/integrations.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $custom = array();

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

	/**
	 * Set custom setting.
	 *
	 * @since  1.0.0
	 *
	 * @param string $group_name The custom setting group.
	 * @param string $field_name The custom setting field.
	 * @param mixed $value The custom setting value.
	 */
	public function set_custom_setting( $group_name, $field_name, $value ) {
		if ( isset( $this->custom[ $group_name ] ) ) {
			$group = $this->custom[ $group_name ];
		} else {
			$group = array();
		}

		$field_value = apply_filters(
			'ms_model_settings_set_custom_setting',
			$value,
			$group_name,
			$field_name,
			$this
		);

		$key = false;

		// Very basic support for array updates.
		// We only support updating 1-dimensional arrays with a
		// specified key value.
		if ( strpos( $field_name, '[' ) ) {
			$field_name = str_replace( ']', '', $field_name );
			list( $field_name, $key ) = explode( '[', $field_name, 2 );
		}

		if ( $key ) {
			if ( empty( $group[ $field_name ] ) ) {
				$group[ $field_name ] = array();
			}
			if ( is_array( $group[ $field_name ] ) ) {
				$group[ $field_name ][ $key ] = $field_value;
			}
		} else {
			$group[ $field_name ] = $field_value;
		}

		$this->custom[ $group_name ] = $group;
	}

	/**
	 * Get custom setting.
	 *
	 * @since  1.0.0
	 *
	 * @param string $group_name The custom setting group.
	 * @param string $field_name The custom setting field.
	 * @return mixed $value The custom setting value.
	 */
	public function get_custom_setting( $group_name, $field_name ) {
		$value = '';

		if ( isset( $this->custom[ $group_name ] ) ) {
			$group = $this->custom[ $group_name ];
		} else {
			$group = array();
		}

		$key = false;

		// Very basic support for array updates.
		// We only support updating 1-dimensional arrays with a
		// specified key value.
		if ( strpos( $field_name, '[' ) ) {
			$field_name = str_replace( ']', '', $field_name );
			list( $key, $field_name ) = explode( '[', $field_name, 2 );
		}

		if ( $key ) {
			if ( isset( $group[ $key ] ) ) {
				$group = $group[ $key ];
			} else {
				$group = array();
			}
		}

		if ( isset( $group[ $field_name ] ) ) {
			$value = $group[ $field_name ];
		}

		return apply_filters(
			'ms_model_settings_get_custom_setting',
			$value,
			$group_name,
			$field_name,
			$this
		);
	}
}