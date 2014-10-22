<?php

/**
 * Render Coupon add/edit view.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage View
 */
class MS_View_Coupon_Edit extends MS_View {

	/**
	 * Data set by controller.
	 *
	 * @since 1.0.0
	 *
	 * @var mixed $data
	 */
	protected $data;

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<?php
					$text = $this->data['coupon']->is_valid() ? __( 'Add', MS_TEXT_DOMAIN ) : __( 'Edit', MS_TEXT_DOMAIN );
					MS_Helper_Html::settings_header( array(
						'title' => sprintf( __( ' %s Coupon', MS_TEXT_DOMAIN ), $text ),
						'title_icon_class' => 'ms-fa ms-fa-pencil-square',
					) );
				?>
				<form action="<?php echo remove_query_arg( array( 'action', 'coupon_id' ) ); ?>" method="post" class="ms-form">
					<?php MS_Helper_Html::settings_box( $fields ); ?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_coupon_edit_to_html', $html, $this );
	}

	/**
	 * Prepare html fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function prepare_fields() {
		$coupon = $this->data['coupon'];
		$fields = array(
			'code' => array(
					'id' => 'code',
					'title' => __( 'Coupon code', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->code,
			),
			'discount' => array(
					'id' => 'discount',
					'title' => __( 'Discount', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->discount,
			),
			'discount_type' => array(
					'id' => 'discount_type',
					'title' => __( 'Discount Type', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $coupon->get_discount_types(),
					'value' => $coupon->discount,
			),
			'start_date' => array(
					'id' => 'start_date',
					'title' => __( 'Start date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => ( $coupon->start_date ) ? $coupon->start_date : MS_Helper_Period::current_date(),
					'class' => 'ms-date',
			),
			'expire_date' => array(
					'id' => 'expire_date',
					'title' => __( 'Expire date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->expire_date,
					'class' => 'ms-date',
			),
			'membership_id' => array(
					'id' => 'membership_id',
					'title' => __( 'Memberships', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $this->data['memberships'],
					'value' => $coupon->membership_id,
			),
			'max_uses' => array(
					'id' => 'max_uses',
					'title' => __( 'Max uses', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->max_uses,
			),
			'coupon_id' => array(
					'id' => 'coupon_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $coupon->id,
			),
			'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce( $this->data['action'] ),
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
			'separator' => array(
					'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'cancel' => array(
					'id' => 'cancel',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'title' => __( 'Cancel', MS_TEXT_DOMAIN ),
					'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
					'url' => remove_query_arg( array( 'action', 'coupon_id' ) ),
					'class' => 'ms-link-button button',
			),
			'submit' => array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);

		return apply_filters( 'ms_view_coupon_edit_prepare_fields', $fields, $this );
	}
}