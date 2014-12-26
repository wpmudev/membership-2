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

	public function get_columns() {
		return apply_filters(
			"membership_helper_list_table_{$this->id}_columns",
			array(
				'cb'     => '<input type="checkbox" />',
				'icon' => '',
				'file' => __( 'File', MS_TEXT_DOMAIN ),
				'access' => __( 'Access', MS_TEXT_DOMAIN ),
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

	public function column_default( $item, $column_name ) {
		$html = '';

		switch ( $column_name ) {
			case 'file':
				$html = $item->post_title;
				break;

			case 'date':
				$html = $item->post_date;
				break;

			case 'icon':
				if ( $thumb = wp_get_attachment_image( $item->ID, array( 80, 60 ), true ) ) {
					$html = $thumb;
				}
				break;

			case 'uploaded':
				if ( $item->post_parent > 0 ) {
					$parent = get_post( $item->post_parent );
				} else {
					$parent = false;
				}

				if ( $parent ) {
					$title = _draft_or_post_title( $item->post_parent );
					$parent_type = get_post_type_object( $parent->post_type );
					$url = get_post_permalink( $parent->ID );
					$html = "<a href='$url'>$title</a>";
				} else {
					$html = __( 'Unattached', MS_TEXT_DOMAIN );
				}
				break;

			default:
				$html = print_r( $item, true );
				break;
		}

		return $html;
	}

	public function get_views(){
		$views = parent::get_views();
		unset( $views['dripped'] );
		return $views;
	}

}
