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
 * Membership List Table
 *
 *
 * @since 4.0.0
 *
 */
class MS_Rule_Media_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Media::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Media File', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Media Files', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		$columns = array(
			'cb' => true,
			'name' => __( 'Title', MS_TEXT_DOMAIN ),
			'access' => true,
			'file_type' => __( 'Type', MS_TEXT_DOMAIN ),
			'post_date' => __( 'Added', MS_TEXT_DOMAIN ),
			'dripped' => false,
		);

		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			$columns
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_listtable_' . $this->id . '_sortable_columns',
			array(
				'name' => array( 'name', false ),
				'dripped' => array( 'dripped', false ),
			)
		);
	}

	public function column_name( $item ) {
		$actions = array(
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_edit_post_link( $item->id, true ),
				__( 'Edit', MS_TEXT_DOMAIN )
			),
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_permalink( $item->id ),
				__( 'View', MS_TEXT_DOMAIN )
			),
		);
		$actions = apply_filters(
			'ms_rule_' . $this->id . '_column_actions',
			$actions,
			$item
		);

		return sprintf(
			'%1$s %2$s',
			$item->post_title,
			$this->row_actions( $actions )
		);
	}

	public function column_post_date( $item, $column_name ) {
		return MS_Helper_Period::format_date(
			$item->post_date,
			__( 'Y/m/d', MS_TEXT_DOMAIN )
		);
	}

	public function column_file_type( $item, $column_name ) {
		$meta = wp_get_attachment_metadata( $item->id );
		if ( isset( $meta['file'] ) ) {
			$type = wp_check_filetype( $meta['file'] );
		} else {
			$type = array(
				'ext' => 'jpg',
				'type' => 'image/jpeg',
			);
		}

		return $type['ext'];
	}

}
