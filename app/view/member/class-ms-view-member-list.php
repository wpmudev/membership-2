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


class MS_View_Member_List extends MS_View {
	
	protected $data;

	public function to_html() {
		$member_list = new MS_Helper_List_Table_Member();
		$member_list->prepare_items();

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Members', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'fa fa-users',
					'desc' => __( 'Here you can manage your Members and Add New Members from your Users list.', MS_TEXT_DOMAIN ),
				)
			);
			?>

			<div class="ms-separator"></div>
			<div>
				<?php $this->render_add_member_form(); ?>
			</div>
			<div class="clear"></div>

			<?php $member_list->views(); ?>
			<form method="post">
				<?php $member_list->search_box( 'Search', 'search' ); ?>
				<?php $member_list->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();
		echo $html;
	}

	/**
	 * Echo the form to add new members from the WordPress User-list.
	 *
	 * @since  1.0.0
	 */
	protected function render_add_member_form() {

		$action = $this->data['action'];

		$fields = array(
// 			'list' => array(
// 				'id' => 'new_member',
// 				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 				'title' => __( 'Add from your Users list:', MS_TEXT_DOMAIN ),
// 				'class' => 'manual-init ms-text-medium',
// 			),
			'list' => array(
					'id' => 'user_id',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Add from your Users list:', MS_TEXT_DOMAIN ),
					'field_options' => $this->data['usernames'],
					'class' => 'manual-init ms-text-medium',
			),
			'add' => array(
				'id' => 'add_member',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Add User to Members', MS_TEXT_DOMAIN ),
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action,
			),
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action ),
			),
		);

		echo '<form action="" method="post" id="form_add_member">';
		foreach ( $fields as $field ) {
			MS_Helper_Html::html_element( $field );
		}
		echo '</form>';
	}

}