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
 * Renders Coupon.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage View
 */
class MS_Addon_Coupon_View_List extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$coupon_list = new MS_Addon_Coupon_Helper_Listtable();
		$coupon_list->prepare_items();

		$title = __( 'Coupons', MS_TEXT_DOMAIN );
		$add_new_button = array(
			'id' => 'add_new',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'url' => sprintf(
				'admin.php?page=%s&action=edit&coupon_id=0',
				MS_Controller_Plugin::MENU_SLUG . '-coupons'
			),
			'value' => __( 'Add New', MS_TEXT_DOMAIN ),
			'class' => 'button',
		);

		ob_start();
		?>
		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => $title,
					'title_icon_class' => 'wpmui-fa wpmui-fa-credit-card',
				)
			);
			?>
			<div>
				<?php MS_Helper_Html::html_element( $add_new_button );?>
			</div>

			<form action="" method="post">
				<?php $coupon_list->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_addon_coupon_view_list_to_html', $html, $this );
	}
}