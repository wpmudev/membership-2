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
class MS_Helper_List_Table_Rule_Media extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_media';

	public function __construct( $model, $membership = null ) {
		parent::__construct( $model, $membership );
		$this->name['singular'] = __( 'Media Item', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Media Items', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		return apply_filters(
			"membership_helper_list_table_{$this->id}_columns",
			array(
				'cb' => true,
				'icon' => '',
				'file' => __( 'File', MS_TEXT_DOMAIN ),
				'access' => true,
				'uploaded' => __( 'Uploaded to', MS_TEXT_DOMAIN ),
				'date' => __( 'Date', MS_TEXT_DOMAIN ),
			)
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			"membership_helper_list_table_{$this->id}_sortable_columns",
			array(
				'filename' => 'filename',
				'access' => 'access',
				'uploaded' => 'uploaded',
				'date' => 'date',
			)
		);
	}

	public function column_file( $item, $column_name ) {
		return $item->post_title;
	}

	public function column_date( $item, $column_name ) {
		return $item->post_date;
	}

	public function column_icon( $item, $column_name ) {
		$html = '';
		$thumb = wp_get_attachment_image( $item->ID, array( 80, 60 ), true );

		if ( $thumb ) {
			$html = $thumb;
		}

		return $html;
	}

	public function column_uploaded( $item, $column_name ) {
		$html = '';

		if ( $item->post_parent > 0 ) {
			$parent = get_post( $item->post_parent );
		} else {
			$parent = false;
		}

		if ( $parent ) {
			$title = _draft_or_post_title( $item->post_parent );
			$parent_type = get_post_type_object( $parent->post_type );
			$url = get_post_permalink( $parent->ID );

			$html = sprintf(
				'<a href="%1$s">$2%s</a>',
				esc_attr( $url ),
				esc_attr( $title )
			);
		} else {
			$html = __( 'Unattached', MS_TEXT_DOMAIN );
		}

		return $html;
	}

}
