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
 * Displays the Setup form.
 * Used in both the success popup when creating the first membership and in the
 * settings page.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_View_Settings_Setup extends MS_View {

	/**
	 * Displays the settings form.
	 *
	 * @since  1.1.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();

		ob_start();

		?>
		<div class="ms-setup-form">
			<div class="ms-title">
				<i class="ms-icon dashicons dashicons-menu"></i>
				<?php _e( 'Please select pages you want to appear in your Navigation:', MS_TEXT_DOMAIN ); ?>
			</div>
			<div class="ms-description">
				<?php
				printf(
					__( 'You can always change those later by going to %1$s in your admin sidebar.', MS_TEXT_DOMAIN ),
					sprintf(
						'<a href="%1$s" target="_blank">%2$s</a>',
						admin_url( 'nav-menus.php' ),
						__( 'Appearance' ) . ' &raquo; ' . __( 'Menus' )
					)
				);
				?>
			</div>
			<?php

			foreach ( $fields['nav'] as $field ) {
				MS_Helper_Html::html_element( $field );
			}

			?>
			<div class="ms-title">
				<i class="ms-icon dashicons dashicons-admin-page"></i>
				<?php _e( 'Protected Content Site Pages', MS_TEXT_DOMAIN ); ?>
			</div>
			<div class="ms-description">
				<?php _e( 'Set Up Protected Content Pages that will be displayed on your website.', MS_TEXT_DOMAIN ); ?>
			</div>
			<?php

			$page_types = array_keys( $fields['pages'] );
			$page_types_menu = array(
				'memberships',
				'register',
				'account',
			);
			$page_types_rest = array_diff( $page_types, $page_types_menu );
			$groups = array(
				'in-menu' => $page_types_menu,
				'no-menu' => $page_types_rest,
			);

			foreach ( $groups as $group_key => $group_items ) :
				printf( '<div class="ms-pages-group %1$s">', esc_attr( $group_key ) );

				foreach ( $group_items as $key ) :
					$field = $fields['pages'][$key];
					?>
					<div class="ms-settings-page-wrapper">
						<?php MS_Helper_Html::html_element( $field ); ?>
						<div class="ms-action">
							<?php
							MS_Helper_Html::html_link(
								array(
									'id' => 'url_page_' . $field['value'],
									'url' => '',
									'value' => __( 'View Page', MS_TEXT_DOMAIN ),
									'target' => '_blank',
									'data_ms' => array( 'base' => home_url( 'index.php?page_id=' ) ),
								)
							);
							?>
							<span> | </span>
							<?php
							MS_Helper_Html::html_link(
								array(
									'id' => 'edit_url_page_' . $field['value'],
									'url' => '',
									'value' => __( 'Edit Page', MS_TEXT_DOMAIN ),
									'target' => '_blank',
									'data_ms' => array( 'base' => admin_url( 'post.php?action=edit&post=' ) ),
								)
							);
							?>
						</div>
					</div>
					<?php
				endforeach;

				echo '</div>';
			endforeach;
			?>
		</div>
		<?php

		$html = ob_get_clean();

		return apply_filters(
			'ms_view_settings_setup_to_html',
			$html
		);
	}

	/**
	 * Prepare the HTML fields that can be displayed
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		// Prepare the return value.
		$nav = array();
		$pages = array();
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );

		$ms_pages->create_missing_pages();
		$page_types = $ms_pages->get_page_types();
		$page_types_menu = array(
			'memberships',
			'register',
			'account',
		);
		$page_types_rest = array_diff( $page_types, $page_types_menu );

		// Prepare NAV fields.
		$menu_action = MS_Controller_Pages::AJAX_ACTION_TOGGLE_MENU;
		$menu_nonce = wp_create_nonce( $menu_action );
		foreach ( $page_types_menu as $type ) {
			$nav_exists = $ms_pages->has_menu( $type );
			$nav[$type] = array(
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'id' => 'nav_' . $type,
				'value' => $nav_exists,
				'title' => $page_types[$type],
				'ajax_data' => array(
					'action' => $menu_action,
					'item' => $type,
					'_wpnonce' => $menu_nonce,
				),
			);
		}

		$nav['sep'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
		);

		// Prepare PAGES fields.
		$pages_action = MS_Controller_Pages::AJAX_ACTION_UPDATE_PAGES;
		$pages_nonce = wp_create_nonce( $pages_action );

		foreach ( $page_types as $type => $label ) {
			$page_id = $ms_pages->get_setting( $type );
			$title = sprintf(
				'<strong>%1$s</strong><span class="lbl-details">: %2$s</span>',
				$label,
				$ms_pages->get_description( $type )
			);

			$pages[ $type ] = array(
				'id' => $type,
				'type' => MS_Helper_Html::INPUT_TYPE_WP_PAGES,
				'title' => $title,
				'value' => $page_id,
				'field_options' => array(
					'no_item' => __( '- Select a page -', MS_TEXT_DOMAIN ),
				),
				'ajax_data' => array(
					'field' => $type,
					'action' => $pages_action,
					'_wpnonce' => $pages_nonce,
				),
			);
		}

		$fields = array(
			'nav' => $nav,
			'pages' => $pages,
		);

		return $fields;
	}

}
