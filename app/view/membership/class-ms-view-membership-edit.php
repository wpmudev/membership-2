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
			$membership_name = sprintf(
				'<a href="?page=%1$s&step=%2$s&tab=%3$s&membership_id=%4$s">%5$s</a>',
				esc_attr( $_REQUEST['page'] ),
				MS_Controller_Membership::STEP_OVERVIEW,
				MS_Controller_Membership::TAB_DETAILS,
				esc_attr( $membership->id ),
				$membership->get_name_tag( true )
			);
			MS_Helper_Html::settings_header(
				array(
					'title' => $membership_name,
					'title_icon_class' => '',
					'desc' => __( 'Edit Membership details and define Membership specific settings.', 'membership2' ),
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$tab_name = str_replace( '-', '_', $active_tab );
			$callback_name = 'render_tab_' . $tab_name;
			$render_callback = apply_filters(
				'ms_view_membership_edit_render_callback',
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
	 *                               UPGRADE PATHS
	 * ====================================================================== */

	public function render_tab_upgrade() {
		$tab = MS_Factory::create( 'MS_View_Membership_Tab_Upgrade' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PAGES
	 * ====================================================================== */

	public function render_tab_pages() {
		$tab = MS_Factory::create( 'MS_View_Membership_Tab_Pages' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PROTECTION MESSAGES
	 * ====================================================================== */

	public function render_tab_messages() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Messages' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               AUTOMATED MESSAGES
	 * ====================================================================== */

	public function render_tab_emails() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Communications' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               MEMBERSHIP TYPE
	 * ====================================================================== */

	public function render_tab_type() {
		$tab = MS_Factory::create( 'MS_View_Membership_Tab_Type' );
		$tab->data = $this->data;

		return $tab->to_html();
	}
}
