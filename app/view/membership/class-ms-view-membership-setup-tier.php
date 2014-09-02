<?php

class MS_View_Membership_Setup_Tier extends MS_View {

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
							sprintf( __( 'Set up different Tier Levels for %s.', MS_TEXT_DOMAIN ), $membership->name ),
							__( 'You can have as many Tier Levels as you want, though remember, less is more.', MS_TEXT_DOMAIN ),
							__( 'Begin with your lowest Level and move up.', MS_TEXT_DOMAIN ),
					),
				) );
			?>
			<div class="ms-tier-wrapper">
				<form action="" method="post">
					<?php MS_Helper_Html::html_input( $this->fields['action'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['step'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['_wpnonce'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['tier_name'] ); ?>
					<?php MS_Helper_Html::html_input( $this->fields['submit_tier'] ); ?>
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
				'tier_name' => array(
						'id' => 'tier_name',
						'title' => __( 'Name Your Content Type:', MS_TEXT_DOMAIN ),
						'value' => '',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'class' => 'ms-text-medium',
				),
				'submit_tier' => array(
						'id' => 'submit_tier',
						'value' => __( 'Create New Tier Level', MS_TEXT_DOMAIN ),
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