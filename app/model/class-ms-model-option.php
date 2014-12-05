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
 * @uses WP Option API to persist data into wp_option table.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Option extends MS_Model {

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
		$settings = MS_Factory::serialize_model( $this );

		update_option( $class, $settings );

		$this->instance = $this;
		$this->after_save();

		wp_cache_set( $class, $this, 'MS_Model_Option' );
	}

	/**
	 * Reads the options from options table
	 *
	 * @since 1.0.4.5
	 */
	public function refresh() {
		$class = get_class( $this );

		$settings = get_option( $class );
		MS_Factory::populate_model( $this, $settings );

		wp_cache_set( $class, $this, 'MS_Model_Option' );
	}

	/**
	 * Delete from wp option table
	 *
	 * @since 1.0.0
	 */
	public function delete() {
		do_action( 'ms_model_option_delete_before', $this );

		$class = get_class( $this );
		delete_option( $class );

		do_action( 'ms_model_option_delete_after', $this );
	}
}