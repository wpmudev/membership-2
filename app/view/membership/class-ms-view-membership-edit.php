<?php
/**
 * Displays the membership edit page.
 *
 * @since 1.0.1.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Membership_Edit extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function to_html() {
		$this->check_simulation();

		// Setup navigation tabs.
		$tabs = $this->data['tabs'];
		$membership = $this->data['membership'];
		$desc = array();

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => $membership->get_name_tag(),
					'title_icon_class' => '',
					'desc' => __( 'Edit Memberhip details, payment details, etc.', MS_TEXT_DOMAIN ),
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
				echo $html;
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/* ====================================================================== *
	 *                               DETAILS
	 * ====================================================================== */

	public function render_tab_details() {
		$tab = MS_Factory::create( 'MS_View_Membership_Tab_Details' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PAYMENT
	 * ====================================================================== */

	public function render_tab_payment() {
		$tab = MS_Factory::create( 'MS_View_Membership_Tab_Payment' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               ACCESS (alias for 'payment')
	 * ====================================================================== */

	public function render_tab_access() {
		$tab = MS_Factory::create( 'MS_View_Membership_Tab_Payment' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

}
