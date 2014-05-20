<?php

class MS_View_Registration_Payment extends MS_View {
	
	protected $data;
	
	public function to_html() {
		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<legend><?php _e( 'Join Membership', MS_TEXT_DOMAIN ) ?></legend>
			<p class="ms-alert-box">
				<?php _e( 'Please check the details of your subscription below and click on the relevant button to complete the subscription.', MS_TEXT_DOMAIN ); ?>
			</p>
	
			<form class="ms-membership-form" method="post">
			</form>
		</div>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		return $html;
	}
}