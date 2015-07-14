<?php
/**
 * Renders Coupon.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage View
 */
class MS_Addon_Coupon_View_List extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$coupon_list = MS_Factory::create( 'MS_Addon_Coupon_Helper_Listtable' );
		$coupon_list->prepare_items();

		$title = __( 'Coupons', MS_TEXT_DOMAIN );
		$add_new_button = array(
			'id' => 'add_new',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'url' => MS_Controller_Plugin::get_admin_url(
				'coupons', array( 'action' => 'edit', 'coupon_id' => 0 )
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