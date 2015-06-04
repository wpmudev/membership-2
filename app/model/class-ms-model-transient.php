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
 * Abstract Option model.
 *
 * @uses WP Transient API to persist data.
 *
 * @since 1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Transient extends MS_Model {

	/**
	 * Save content in wp_option table.
	 *
	 * Update WP cache and instance singleton.
	 *
	 * @since 1.0.0
	 */
	public function save() {
		$this->before_save();

		$option_key = $this->option_key();
		$settings = array();

		$fields = get_object_vars( $this );
		foreach ( $fields as $field => $val ) {
			$settings[ $field ] = $this->$field;
		}

		MS_Factory::set_transient( $option_key, $settings );

		$this->after_save();

		wp_cache_set( $option_key, $this, 'MS_Model_Transient' );
	}

	/**
	 * Delete transient.
	 *
	 * @since 1.0.0
	 */
	public function delete() {
		do_action( 'ms_model_transient_delete_before', $this );

		$option_key = $this->option_key();
		MS_Factory::delete_transient( $option_key );
		wp_cache_delete( $option_key, 'MS_Model_Transient' );

		do_action( 'ms_model_transient_delete_after', $this );
	}

	/**
	 * Returns the option name of the current object.
	 *
	 * @since  2.0.0
	 * @return string The option key.
	 */
	protected function option_key() {
		// Option key should be all lowercase.
		$key = strtolower( get_class( $this ) );

		// Network-wide mode uses different options then single-site mode.
		if ( MS_Plugin::is_network_wide() ) {
			$key .= '-network';
		}

		return substr( $key, 0, 45 );
	}

}