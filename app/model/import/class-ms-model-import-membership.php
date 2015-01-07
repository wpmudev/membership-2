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
 * Imports data from WPMU DEV Membership plugin.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Import_Membership extends MS_Model_Import {

	/**
	 * Stores the result of present() call
	 *
	 * @since  1.1.0
	 *
	 * @var bool
	 */
	static protected $is_present = null;

	/**
	 * This function parses the Import source (i.e. an file-upload) and returns
	 * true in case the source data is valid. When returning true then the
	 * $source property of the model is set to the sanitized import source data.
	 *
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public function prepare() {
	}

	/**
	 * Returns true if the specific import-source is present and can be used
	 * for import.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function present() {
		if ( null === self::$is_present ) {
			global $wpdb;
			$rule_table = $wpdb->prefix . 'm_membership_rules';

			$sql = 'SHOW TABLES LIKE %s;';
			$sql = $wpdb->prepare( $sql, $rule_table );
			self::$is_present = $wpdb->get_var( $sql ) == $rule_table;
		}

		return self::$is_present;
	}

}
