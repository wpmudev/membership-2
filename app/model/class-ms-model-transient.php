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
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Transient extends MS_Model {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar MS_Model_Option
	 */
	public static $instance;

	/**
	 * Save content in wp_option table.
	 *
	 * Update WP cache and instance singleton.
	 *
	 * @since 1.0.0
	 */
	public function save() {
		$this->before_save();

		$class = get_class( $this );
		$settings = array();

		$fields = get_object_vars( $this );
		foreach ( $fields as $field => $val ) {
			$settings[ $field ] = $this->$field;
		}

		set_transient( $class, $settings );

		$this->instance = $this;
		$this->after_save();

		wp_cache_set( $class, $this, 'MS_Model_Transient' );
	}

	/**
	 * Delete transient.
	 *
	 * @since 1.0.0
	 */
	public function delete() {
		do_action( 'ms_model_transient_delete_before', $this );

		$class = get_class( $this );
		delete_transient( $class );

		do_action( 'ms_model_transient_delete_after', $this );
	}

}