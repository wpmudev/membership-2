<?php

/**
 * Render Accessible Content page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_View_Membership_Protected_Content extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		$tabs = $this->data['tabs'];

		$desc = array(
			__( 'Choose Pages, Categories etc. that you want to make <strong>unavailable</strong> to visitors, and non-members.', MS_TEXT_DOMAIN ),
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Set-up Protected Content', MS_TEXT_DOMAIN ),
					'desc' => $desc,
				)
			);

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_membership_protected_content_render_tab_callback',
				array( $this, $callback_name ),
				$active_tab, $this
			);

			$html = call_user_func( $render_callback );
			$html = apply_filters(
				'ms_view_membership_protected_' . $callback_name,
				$html
			);
			echo '' . $html;
			?>
		</div>
		<?php

		// Only in "Protected Content" - not in "Accessible Content"
		if ( isset( $_REQUEST['from'] ) ) {
			$field = array(
				'id'    => 'go_back',
				'type'  => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( '&laquo; Back', MS_TEXT_DOMAIN ),
				'url'   => base64_decode( $_REQUEST['from'] ),
				'class' => 'button',
			);
			MS_Helper_Html::html_element( $field );
		}

		$html = ob_get_clean();

		return apply_filters(
			'ms_view_membership_protected_content_to_html',
			$html,
			$this
		);
	}




	/* ====================================================================== *
	 *                               CATEGORY
	 * ====================================================================== */

	/**
	 * Render category tab.
	 *
	 * @since 1.0.0
	 */
	public function render_tab_category() {
		$tab = MS_Factory::create( 'MS_View_Membership_Rule_Category' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PAGE
	 * ====================================================================== */

	public function render_tab_page() {
		$tab = MS_Factory::create( 'MS_View_Membership_Rule_Page' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               ADMIN SIDE
	 * ====================================================================== */

	public function render_tab_adminside() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Adminside' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               MEMBER CAPS
	 * ====================================================================== */

	public function render_tab_membercaps() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Membercaps' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               SPECIAL PAGES
	 * ====================================================================== */

	public function render_tab_special() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Special' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Post' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               CUSTOM POST TYPE
	 * ====================================================================== */

	public function render_tab_cpt() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Cpt' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               COMMENT, MORE, MENU
	 * ====================================================================== */

	/**
	 * Render tab content for:
	 * Comments, More tag, Menus
	 *
	 * @since  1.0.0
	 */
	public function render_tab_comment() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Comment' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               SHORTCODE
	 * ====================================================================== */

	public function render_tab_shortcode() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Shortcode' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               URL GROUP
	 * ====================================================================== */

	public function render_tab_url_group() {
		$view = MS_Factory::create( 'MS_View_Membership_Rule_Urlgroup' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               SHARED
	 * ====================================================================== */

	public function get_control_fields() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		return apply_filters(
			'ms_view_membership_protected_content_get_control_fields',
			$fields
		);
	}

}