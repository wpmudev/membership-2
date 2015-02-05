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
 * Renders Billing/Transaction History.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage View
 */
class MS_View_Billing_List extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$billing_list = MS_Factory::create( 'MS_Helper_ListTable_Billing' );
		$billing_list->prepare_items();

		$title = __( 'Billing', MS_TEXT_DOMAIN );

		if ( ! empty( $_GET['gateway_id'] ) ) {
			$gateway = MS_Model_Gateway::factory( $_GET['gateway_id'] );
			if ( $gateway->name ) {
				$title .= ' - '. $gateway->name;
			}
		}

		$add_new_button = array(
			'id' => 'add_new',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'url' => sprintf( 'admin.php?page=%s&action=edit&invoice_id=0', MS_Controller_Plugin::MENU_SLUG . '-billing' ),
			'value' => __( 'Add New', MS_TEXT_DOMAIN ),
			'class' => 'button',
		);

		ob_start();
		?>

		<div class="wrap ms-wrap ms-billing">
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
			<?php $billing_list->views(); ?>
			<form action="" method="post">
				<?php $billing_list->search_box( __( 'Search user', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $billing_list->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_view_billing_list',
			$html,
			$this
		);
	}
}