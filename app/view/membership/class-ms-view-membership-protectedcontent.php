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
		$this->check_simulation();

		$desc = array(
			__( 'Choose Pages, Categories etc. that you want to make <strong>unavailable</strong> to visitors, and non-members.', MS_TEXT_DOMAIN ),
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap ms-wrap-protected-content">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Set-up Protected Content', MS_TEXT_DOMAIN ),
					'desc' => $desc,
				)
			);

			// Display a filter to switch between individual memberships.
			$this->membership_filter();

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $this->data['tabs'], $active_tab );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = array( $this, $callback_name );
			$render_callback = apply_filters(
				'ms_view_protectedcontent_define-' . $active_tab,
				$render_callback,
				$this->data
			);

			if ( is_callable( $render_callback ) ) {
				$html = call_user_func( $render_callback );
			} else {
				// This is to notify devs that a file/hook is missing or wrong.
				$html = '<div class="ms-settings">' .
					'<div class="error below-h2"><p>' .
					'<em>No View defined by hook "ms_view_protectedcontent_define-' . $active_tab . '"</em>' .
					'</p></div>' .
					'</div>';
			}

			$html = apply_filters(
				'ms_view_membership_protected_' . $active_tab,
				$html
			);
			echo '' . $html;
			?>
		</div>
		<?php

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
		$url = esc_url_raw(
			remove_query_arg( array( 'membership_id', 'paged' ) )
		);
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
				'url' => esc_url_raw(
					add_query_arg( array( 'membership_id' => $id ), $url )
				),
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

}