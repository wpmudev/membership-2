<?php

/**
 * The Settings-Form
 */
class MS_Addon_Redirect_View extends MS_View {

	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Redirect Settings', 'membership2' ),
					'desc' => array(
						__( 'Specify your custom URLs here. You can use either an absolute URL (starting with "http://") or an site-relative path (like "/some-page/")', 'membership2' ),
						sprintf(
							__( 'The URLs you specify here can always be overwritten in the %slogin shortcode%s using the redirect-attributes. Example: <code>[%s redirect_login="/welcome/" redirect_logout="/good-bye/"]</code>.', 'membership2' ),
							sprintf(
								'<a href="%s#ms-membership-login" target="_blank">',
								MS_Controller_Plugin::get_admin_url(
									'help',
									array( 'tab' => 'shortcodes' )
								)
							),
							'</a>',
							MS_Helper_Shortcode::SCODE_LOGIN
						),
					),
				)
			);

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function prepare_fields() {
		$model = MS_Addon_Redirect::model();

		$action = MS_Addon_Redirect::AJAX_SAVE_SETTING;

		$fields = array(
			'redirect_login' => array(
				'id' => 'redirect_login',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'After Login', 'membership2' ),
				'desc' => __( 'This page is displayed to users right after login.', 'membership2' ),
				'placeholder' => MS_Model_Pages::get_url_after_login( false ),
				'value' => $model->get( 'redirect_login' ),
				'class' => 'ms-text-large',
				'ajax_data' => array(
					'field' => 'redirect_login',
					'action' => $action,
				),
			),

			'redirect_logout' => array(
				'id' => 'redirect_logout',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'After Logout', 'membership2' ),
				'desc' => __( 'This page is displayed to users right after they did log out.', 'membership2' ),
				'placeholder' => MS_Model_Pages::get_url_after_logout( false ),
				'value' => $model->get( 'redirect_logout' ),
				'class' => 'ms-text-large',
				'ajax_data' => array(
					'field' => 'redirect_logout',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}