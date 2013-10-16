<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * The module responsible for system tasks.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_System extends Membership_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin class.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );
		$this->_add_action( 'plugins_loaded', 'upgrade', 999 );
	}

	/**
	 * Executes an array of sql queries.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @param array $queries The arrayof queries to execute.
	 */
	private function _exec_queries( array $queries ) {
		foreach ( $queries as $query ) {
			$this->_wpdb->query( $query );
		}
	}

	/**
	 * Generates CREATE TABLE sql script for provided table name and columns list.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @param string $name The name of a table.
	 * @param array $columns The array  of columns, indexes, constraints.
	 * @return string The sql script for table creation.
	 */
	private function _create_table( $name, array $columns ) {
		$charset = '';
		if ( !empty( $this->_wpdb->charset ) ) {
			$charset = " DEFAULT CHARACTER SET " . $this->_wpdb->charset;
		}

		$collate = '';
		if ( !empty( $this->_wpdb->collate ) ) {
			$collate .= " COLLATE " . $this->_wpdb->collate;
		}

		return sprintf( 'CREATE TABLE IF NOT EXISTS `%s` (%s)%s%s', $name, implode( ', ', $columns ), $charset, $collate );
	}

	/**
	 * Performs upgrade plugin evnironment to up to date version.
	 *
	 * @since 3.5
	 * @action init 999
	 *
	 * @access private
	 */
	public function upgrade() {
		$filter = 'membership_upgrade';
		$option = 'membership_version';

		// fetch current database version
		$db_version = get_site_option( $option );
		if ( $db_version === false ) {
			$db_version = '0.0.0';
			update_option( $option, $db_version );
		}

		// check if current version is equal to database version, then there is nothing to upgrade
		if ( version_compare( $db_version, Membership_Plugin::VERSION, '=' ) ) {
			return;
		}

		// upgrade database version to current plugin version
		update_site_option( $option, apply_filters( $filter, Membership_Plugin::VERSION ) );

		// flush rewrite rules
		add_action( 'init', 'flush_rewrite_rules' );
	}

}