<?php

class MS_View_Coupon_Edit extends MS_View {

	const COUPON_SECTION = 'coupon_section';
	const COUPON_NONCE = 'coupon_nonce';
	
	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2>Coupon edit</h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'coupon_id' ) ); ?>" method="post" class="ms-form">
					<?php wp_nonce_field( self::COUPON_NONCE, self::COUPON_NONCE ); ?>
					<table class="form-table">
						<tbody>
							<?php foreach( $this->fields as $field ): ?>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $field ); ?>
									</td>
								</tr>
								<?php endforeach; ?>
						</tbody>
					</table>
					<?php MS_Helper_Html::html_submit(); ?>
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
			'start_date' => array(
					'id' => 'amount',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Start date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->start_date,
			),
			'expire_date' => array(
					'id' => 'tax_name',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Expire date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->expire_date,
			),
			'membership_id' => array(
					'id' => 'membership_id',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Memberships', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->membership_id,
			),
			'max_uses' => array(
					'id' => 'max_uses',
					'section' => self::COUPON_SECTION,
					'title' => __( 'Max uses', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $coupon->max_uses,
					'class' => 'ms-date',
			),
			'coupon_id' => array(
					'id' => 'coupon_id',
					'section' => self::COUPON_SECTION,
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $coupon->id,
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
		);
	}
}