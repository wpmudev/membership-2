<?php

class MS_View_Membership_Setup_Tier extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		$membership = $this->data['membership'];
	
		$list_table = new MS_Helper_List_Table_Membership_Group( $membership );
		$list_table->prepare_items();
	
		ob_start();
		?>
							
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => __( 'Set Up Membership Tiers', MS_TEXT_DOMAIN ),
					'desc' => array( 
							sprintf( __( 'Set up different Tier Levels for %s.', MS_TEXT_DOMAIN ), $membership->name ),
							__( 'You can have as many Tier Levels as you want, though remember, less is more.', MS_TEXT_DOMAIN ),
							__( 'Begin with your lowest Tier Level and move up.', MS_TEXT_DOMAIN ),
					),
					'bread_crumbs' => $this->data['bread_crumbs'],
				) );
			?>
			<div class="ms-content-type-wrapper">
				<form action="" method="post" id="ms-create-child-form" >
					<?php MS_Helper_Html::html_element( $this->fields['action'] ); ?>
					<?php MS_Helper_Html::html_element( $this->fields['step'] ); ?>
					<?php MS_Helper_Html::html_element( $this->fields['_wpnonce'] ); ?>
					<?php MS_Helper_Html::html_element( $this->fields['name'] ); ?>
					<?php MS_Helper_Html::html_element( $this->fields['submit_content_type'] ); ?>
				</form>
				<?php $list_table->display(); ?>
				<?php 
					MS_Helper_Html::settings_footer( array( 'fields' => array( $this->fields['step'] ) ) ); 
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
				'name' => array(
						'id' => 'name',
						'title' => __( 'Tier Level name:', MS_TEXT_DOMAIN ),
						'value' => '',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'class' => 'ms-text-large',
						'placeholder' => __( 'eg. Bronze', MS_TEXT_DOMAIN ),
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
	
