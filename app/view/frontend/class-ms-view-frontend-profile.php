<?php

class MS_View_Frontend_Profile extends MS_View {

	protected $data;

	protected $fields;

	public function to_html() {
		$this->prepare_fields();
		$cancel = array(
				'id' => 'cancel',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'title' => __('Cancel', MS_TEXT_DOMAIN ),
				'value' => __('Cancel', MS_TEXT_DOMAIN ),
				'url' => remove_query_arg( array( 'action' ) ),
				'class' => 'wpmui-field-button button',
		);
		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors() ?>
			<form id="ms-view-frontend-profile-form" class="form-membership" action="" method="post">
				<legend><?php _e( 'Edit profile', MS_TEXT_DOMAIN ); ?></legend>
				<?php foreach( $this->fields as $field ): ?>
					<div class="ms-form-element">
						<?php MS_Helper_Html::html_element( $field );?>
					</div>
				<?php endforeach;?>
				<?php do_action( 'ms_view_frontend_profile_after_fields' ); ?>
				<?php do_action( 'ms_view_frontend_profile_extra_fields', $this->error ); ?>
			</form>
			<div class="ms-form-element">
			<?php  MS_Helper_Html::html_link( $cancel ); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	public function prepare_fields() {
		$member = $this->data['member'];

		$this->fields = array(
				'first_name' => array(
						'id' => 'first_name',
						'title' => __( 'First Name', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $member->first_name,
				),
				'last_name' => array(
						'id' => 'last_name',
						'title' => __( 'Last Name', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $member->last_name,
				),
				'email' => array(
						'id' => 'email',
						'title' => __( 'Email Address', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $member->email,
				),
				'password' => array(
						'id' => 'password',
						'title' => __( 'Password', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
// 						'desc' => __( 'Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', MS_TEXT_DOMAIN ),
						'value' => '',
				),
				'password2' => array(
						'id' => 'password2',
						'title' => __( 'Confirm Password', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
						'value' => '',
				),
				'submit' => array(
						'id' => 'submit',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
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
		);
	}
	/**
	 * Renders error messages.
	 *
	 * @access private
	 */
	private function render_errors() {
		if( ! empty( $this->data['errors'] ) ) {
		?>
			<div class="ms-alert-box ms-alert-error">
				<?php echo $this->data['errors']; ?>
			</div>
		<?php
		}
	}

}