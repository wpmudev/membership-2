<?php

class MS_View_Frontend_Welcome extends MS_View {
	
	protected $data;
	
	public function to_html() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-welcome-wrapper">
			<div class="ms-welcome-msg">
				<?php 
					foreach( $fields as $field ) {
						MS_Helper_Html::html_element( $field );
					}
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	protected function prepare_fields() {
		$ms_relationship = $this->data['ms_relationship'];
		$membership = $ms_relationship->get_membership();
		
		$fields = array(
			'welcome_title' => array(
					'id' => 'welcome_title',
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => sprintf( __( 'Request to join %s Membership received!', MS_TEXT_DOMAIN ), $membership->name ),
			),
			'welcome_msg' => array(
					'id' => 'welcome_msg',
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => sprintf( '%s<br/><a href="%s">%s</a>',
							__( 'The Payment Gateway could take a couple of minutes to process and return the payment status.', MS_TEXT_DOMAIN ),
							MS_Factory::load( 'MS_Model_Pages' )->get_ms_page_url( MS_Model_Pages::MS_PAGE_ACCOUNT, false, true ),
							__( 'Visit your account page for more information.', MS_TEXT_DOMAIN )
					)
			),
		);
		
		return apply_filters( 'ms_view_frontend_welcome', $fields );
	}
}