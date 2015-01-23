<?php

class MS_View_Membership_List extends MS_View {

	public function to_html() {
		$membership = $this->data['membership'];
		$admin_message = MS_Helper_Membership::get_admin_message(
			array( $membership->name ),
			$membership
		);
		$title = MS_Helper_Membership::get_admin_title();

		$membership_list = MS_Factory::create( 'MS_Helper_ListTable_Membership' );
		$membership_list->prepare_items();

		$create_new_button = array(
			'id' => 'create_new_ms_button',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'url' => $this->data['create_new_url'],
			'value' => __( 'Create New Membership', MS_TEXT_DOMAIN ),
			'class' => 'button',
		);

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
				'title' => $title,
				'desc' => array(
						__( 'Here you can view and edit all the Memberships you have created.', MS_TEXT_DOMAIN ),
						$admin_message,
					)
				)
			);
			?>
			<form action="" method="post">
				<div class="ms-list-table-wrapper ms-membership-list">
					<?php
					MS_Helper_Html::html_element( $create_new_button );
					$membership_list->display();
					MS_Helper_Html::html_element( $create_new_button );
					?>
				</div>
			</form>
		</div>

		<?php
		$html = ob_get_clean();
		echo $html;
	}

}