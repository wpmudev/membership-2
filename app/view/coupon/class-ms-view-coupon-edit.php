<?php

class MS_View_Coupon_Edit extends MS_View {

	const COUPON_SECTION = 'coupon_section';
	
	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2 class="ms-settings-title">
					<i class="fa fa-pencil-square"></i>
					<?php 
						echo empty( $this->data['coupon']->id ) 
							? __( 'Add', MS_TEXT_DOMAIN ) 
							: __( 'Edit', MS_TEXT_DOMAIN ); 
						_e( ' Coupon', MS_TEXT_DOMAIN ); 
					?>
				</h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'coupon_id' ) ); ?>" method="post" class="ms-form">
					<?php MS_Helper_Html::settings_box( $this->fields ); ?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	function prepare_fields() {
		$coupon = $this->data['coupon'];
		$this->fields = array(
			'code' => array(
					'id' => 'code',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Coupon code', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->code,
			),
			'discount' => array(
					'id' => 'discount',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Discount', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->discount,
			),
			'discount_type' => array(
					'id' => 'discount_type',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Discount Type', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $coupon->get_discount_types(),
					'value' => $coupon->discount,
			),
			'start_date' => array(
					'id' => 'start_date',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Start date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => ( $coupon->start_date ) ? $coupon->start_date : MS_Helper_Period::current_date(),
					'class' => 'ms-date',
			),
			'expire_date' => array(
					'id' => 'expire_date',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Expire date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->expire_date,
					'class' => 'ms-date',
			),
			'membership_id' => array(
					'id' => 'membership_id',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Memberships', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $this->data['memberships'],
					'value' => $coupon->membership_id,
			),
			'max_uses' => array(
					'id' => 'max_uses',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Max uses', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->max_uses,
			),
			'coupon_id' => array(
					'id' => 'coupon_id',
					'section' => self::COUPON_SECTION,
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
					'title' => __('Cancel', MS_TEXT_DOMAIN ),
					'value' => __('Cancel', MS_TEXT_DOMAIN ),
					'url' => remove_query_arg( array( 'action', 'coupon_id' ) ),
					'class' => 'ms-link-button button',
			),
			'submit' => array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
	}
}