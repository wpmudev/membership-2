<?php

class MS_View_Gateway_Authorize extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		// let 3rd party themes/plugins use their own form
		if ( ! apply_filters( 'ms_view_gateway_authorize_form_to_html', true, $this ) ) {
			return;
		}
		
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2><?php echo __( 'Signup ', MS_TEXT_DOMAIN ); ?> </h2>
				<form action="" method="post" class="ms-form">
					<table class="form-table">
						<tbody>
							<?php foreach( $this->fields as $field ): ?>
								<tr>
									<td>
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
		$currency = MS_Plugin::instance()->settings->currency;
		$this->fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['gateway'],
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['membership_id'],
				),
				'move_from_id' => array(
						'id' => 'move_from_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['move_from_id'],
				),
				'coupon_id' => array(
						'id' => 'coupon_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['coupon_id'],
				),
		);
	}
}