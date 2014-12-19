<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Renders the Welcome Page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.1.0
 *
 * @return object
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
	 * @since 1.1.0
	 *
	 * @return object
	 */
	public function to_html() {
		$hidden_fields = $this->prepare_fields();

		$button = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Let\'s get started &raquo;', MS_TEXT_DOMAIN ),
			'class' => 'ms-welcome-start',
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<form class="ms-welcome-box" action="" method="POST">
				<?php
				foreach ( $hidden_fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>

				<h2 class="ms-welcome-title">
					<?php _e( 'Welcome!', MS_TEXT_DOMAIN ); ?>
				</h2>

				<div class="ms-welcome-text">
					<?php _e( 'Hello and welcome to <strong>Protected Content</strong> by WPMU DEV. Please follow this simple set-up<br />wizard to help us determine the settings that are most relevant to your needs. Don\'t worry, you<br />can always change these settings in the future.', MS_TEXT_DOMAIN ); ?>
				</div>

				<div class="ms-welcome-image-box">
					<img src="<?php echo esc_attr( MS_Plugin::instance()->url ); ?>app/assets/images/welcome.png" class="ms-welcome-image" />
				</div>

				<?php MS_Helper_Html::html_element( $button ); ?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns an array of form fields
	 *
	 * @since  1.1.0
	 * @return array
	 */
	protected function prepare_fields() {
		$fields = array();

		$fields['step'] = array(
			'id' => 'step',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Membership::STEP_CHOOSE_MS_TYPE,
		);

		return $fields;
	}
}