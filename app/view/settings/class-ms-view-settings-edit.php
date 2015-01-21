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
 * Renders Membership Plugin Settings.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since 1.0
 *
 * @return object
 */
class MS_View_Settings_Edit extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		// Setup navigation tabs.
		$tabs = $this->data['tabs'];
		$desc = array();

		// A "Reset" button that can be added via URL param
		// Intentionally not translated (purpose is dev/testing)
		if ( isset( $_GET['reset'] ) ) {
			$reset_url = admin_url( 'admin.php?page=protected-content-settings&reset=1' );
			$reset_url = add_query_arg(
				MS_Model_Upgrade::get_reset_token(),
				$reset_url
			);
			$cancel_url = remove_query_arg( 'reset' );
			$desc[] = sprintf(
				'<div class="error" style="width:600px;margin:20px auto;text-align:center"><p><b>%1$s</b></p><hr />%2$s</div>',
				'Careful: This will completely erase all your Protected Content settings and details!',
				sprintf(
					'<form method="POST" action="%s" style="padding:20px 0">' .
					'<label style="line-height:28px">' .
					'<input type="checkbox" name="confirm" value="reset" /> Yes, reset everything!' .
					'</label><p>' .
					'<button class="button-primary">Do it!</button> ' .
					'<a href="%s" class="button">Cancel</a>' .
					'</p></form>',
					$reset_url,
					$cancel_url
				)
			);
		}

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Protect Content Settings', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-cog',
					'desc' => $desc,
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$tab_name = str_replace( '-', '_', $active_tab );
			$callback_name = 'render_tab_' . $tab_name;
			$render_callback = apply_filters(
				'ms_view_settings_edit_render_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data
			);
			?>
			<div class="ms-settings ms-settings-<?php echo esc_attr( $tab_name ); ?>">
				<?php
				$html = call_user_func( $render_callback );
				$html = apply_filters( 'ms_view_settings_' . $callback_name, $html );
				echo '' . $html;
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               GENERAL
	 * ====================================================================== */

	public function render_tab_general() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_General' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PAYMENT
	 * ====================================================================== */

	public function render_tab_payment() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Payment' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PROTECTION MESSAGE
	 * ====================================================================== */

	public function render_tab_messages_protection() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Messages' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               AUTOMATED MESSAGES
	 * ====================================================================== */

	public function render_tab_messages_automated() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Communications' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               IMPORT
	 * ====================================================================== */

	public function render_tab_import() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Import' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

}