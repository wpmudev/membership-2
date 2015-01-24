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
class MS_View_Membership_ProtectedContent extends MS_View {

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

			$this->membership_filter();

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_membership_protectedcontent_tab_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data,
				$this
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
			'ms_view_membership_protectedcontent_to_html',
			$html,
			$this
		);
	}

	/**
	 * Display a filter to select the current membership
	 *
	 * @since  1.1.0
	 */
	public function membership_filter() {
		$memberships = MS_Model_Membership::get_membership_names();
		$url = remove_query_arg( array( 'membership_id', 'paged' ) );
		$links = array();

		$links['all'] = array(
			'label' => __( 'All', MS_TEXT_DOMAIN ),
			'url' => $url,
		);

		foreach ( $memberships as $id => $name ) {
			if ( empty( $name ) ) {
				$name = __( '(No Name)', MS_TEXT_DOMAIN );
			}

			$links['ms-' . $id] = array(
				'label' => esc_html( $name ),
				'url' => add_query_arg( array( 'membership_id' => $id ), $url ),
			);
		}

		?>
		<div class="wp-filter">
			<ul class="filter-links">
				<?php foreach ( $links as $key => $item ) :
					$is_current = MS_Helper_Utility::is_current_url( $item['url'] );
					$class = ( $is_current ? 'current' : '' );
					?>
					<li>
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( $class ); ?>">
							<?php echo esc_html( $item['label'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
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
		$tab = MS_Factory::create( 'MS_Rule_Category_View' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               CUSTOM POST TYPE
	 * ====================================================================== */

	/**
	 * Render CPT Post by Post tab.
	 *
	 * @since 1.1.0
	 */
	public function render_tab_cpt_item() {
		$tab = MS_Factory::create( 'MS_Rule_CptItem_View' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               CUSTOM POST TYPE
	 * ====================================================================== */

	/**
	 * Render CPT Group tab.
	 *
	 * @since 1.1.0
	 */
	public function render_tab_cpt_group() {
		$tab = MS_Factory::create( 'MS_Rule_CptGroup_View' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PAGE
	 * ====================================================================== */

	public function render_tab_page() {
		$tab = MS_Factory::create( 'MS_Rule_Page_View' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               ADMIN SIDE
	 * ====================================================================== */

	public function render_tab_adminside() {
		$view = MS_Factory::create( 'MS_Rule_Adminside_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               MEMBER CAPS
	 * ====================================================================== */

	public function render_tab_membercaps() {
		$view = MS_Factory::create( 'MS_Rule_MemberCaps_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               MEMBER ROLES
	 * ====================================================================== */

	public function render_tab_memberroles() {
		$view = MS_Factory::create( 'MS_Rule_MemberRoles_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               SPECIAL PAGES
	 * ====================================================================== */

	public function render_tab_special() {
		$view = MS_Factory::create( 'MS_Rule_Special_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$view = MS_Factory::create( 'MS_Rule_Post_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               COMMENT, MORE
	 * ====================================================================== */

	/**
	 * Render tab content for:
	 * Comments, More tag
	 *
	 * @since  1.0.0
	 */
	public function render_tab_content() {
		$view = MS_Factory::create( 'MS_Rule_Content_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               MENU
	 * ====================================================================== */

	/**
	 * Render tab content for:
	 * Menus
	 *
	 * @since  1.1.0
	 */
	public function render_tab_menuitem() {
		$view = MS_Factory::create( 'MS_Rule_Content_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/**
	 * Render tab content for:
	 * Menus
	 *
	 * @since  1.1.0
	 */
	public function render_tab_replacemenu() {
		$view = MS_Factory::create( 'MS_Rule_ReplaceMenu_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/**
	 * Render tab content for:
	 * Menus
	 *
	 * @since  1.1.0
	 */
	public function render_tab_replacelocation() {
		$view = MS_Factory::create( 'MS_Rule_ReplaceLocation_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               SHORTCODE
	 * ====================================================================== */

	public function render_tab_shortcode() {
		$view = MS_Factory::create( 'MS_Rule_Shortcode_View' );
		$view->data = $this->data;

		return $view->to_html();
	}

	/* ====================================================================== *
	 *                               URL GROUP
	 * ====================================================================== */

	public function render_tab_url_group() {
		$view = MS_Factory::create( 'MS_Rule_Url_View' );
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
			'ms_view_membership_protectedcontent_get_control_fields',
			$fields
		);
	}

}