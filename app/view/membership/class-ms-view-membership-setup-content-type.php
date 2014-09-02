<?php

class MS_View_Membership_Setup_Content_Type extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		$membership = $this->data['membership'];
		
		ob_start();
		?>
						
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => __( 'Set Up Content Types', MS_TEXT_DOMAIN ),
					'desc' => array( 
							sprintf( __( 'Here you can set-up different types of content to be available to different types of %s members.', MS_TEXT_DOMAIN ), $membership->name ),
							__( '(eg. Cooking recipes for Cooking Members, PHP tutorials for Programming Members) ', MS_TEXT_DOMAIN ), 
					),
				) );
			?>
			<div class="ms-content-type-wrapper">
				<form action="" method="post">
					<?php MS_Helper_Html::html_input( $this->fields['action'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['step'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['_wpnonce'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['content_type_name'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['submit_content_type'] ); ?>
				</form>
				<?php 
					MS_Helper_Html::settings_footer( 
							array( 'fields' => array( $this->fields['step'] ) ),
							true,
							$this->data['initial_setup']
					); 
				?>
			</div>	
			<div class="clear"></div>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	private function prepare_fields() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );
		
		$this->fields = array(
				'content_type_name' => array(
						'id' => 'content_type_name',
						'title' => __( 'Name Your Content Type:', MS_TEXT_DOMAIN ),
						'value' => '',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'class' => 'ms-text-medium',
				),
				'submit_content_type' => array(
						'id' => 'submit_content_type',
						'value' => __( 'Add Content Type', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'class' => '',
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $action,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['step'],
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $nonce,
				),
	
		);
	}
}