<?php
/**
 * Renders the Welcome Page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 */
class MS_View_Welcome extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return string
	 */
	public function to_html() {
		$form_fields = $this->prepare_fields();
		$setup_url = MS_Controller_Plugin::get_admin_url( 'setup' );

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<form class="ms-welcome-box" action="<?php echo esc_url( $setup_url ); ?>" method="POST">
				<h2 class="ms-welcome-title">
					<?php _e( 'Welcome!', 'membership2' ); ?>
				</h2>

				<div class="ms-welcome-text">
					<?php _e( 'Hello and welcome to <strong>Membership2</strong> by WPMU DEV. Please follow this simple set-up<br />wizard to help us determine the settings that are most relevant to your needs. Don\'t worry, you<br />can always change these settings in the future.', 'membership2' ); ?>
				</div>

				<div class="ms-welcome-image-box">
					<img src="<?php echo esc_attr( MS_Plugin::instance()->url ); ?>app/assets/images/welcome.png" class="ms-welcome-image" />
				</div>

				<?php
				foreach ( $form_fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns an array of form fields
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		$fields = array();

		$action = MS_Controller_Membership::STEP_ADD_NEW;
		$nonce = wp_create_nonce( $action );

		$fields['step'] = array(
			'id' => 'step',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Membership::STEP_ADD_NEW,
		);
		$fields['button'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Let\'s get started', 'membership2' ) . ' &raquo;',
			'class' => 'ms-welcome-start',
		);

		return $fields;
	}
}