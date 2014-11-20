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

	protected $fields;

	protected $data;

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

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Protect Content Settings', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'ms-fa ms-fa-cog',
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_settings_edit_render_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data
			);

			$html = call_user_func( $render_callback );
			$html = apply_filters( 'ms_view_settings_' . $callback_name, $html );
			echo '' . $html;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               GENERAL
	 * ====================================================================== */

	public function render_tab_general() {
		$settings = $this->data['settings'];
		$menu_protection_options = array(
			'item' => __( 'Protect single Menu Items', MS_TEXT_DOMAIN ),
			'menu' => __( 'Replace individual Menus', MS_TEXT_DOMAIN ),
			'location' => __( 'Overwrite contents of Menu Locations', MS_TEXT_DOMAIN ),
		);

		$fields = array(
			'plugin_enabled' => array(
				'id' => 'plugin_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'This setting toggles the content protection on this site.', MS_TEXT_DOMAIN ),
				'value' => MS_Plugin::is_enabled(),
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'plugin_enabled',
				),
			),

			'hide_admin_bar' => array(
				'id' => 'hide_admin_bar',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Hide the admin toolbar for non administrator users.', MS_TEXT_DOMAIN ),
				'value' => $settings->hide_admin_bar,
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'hide_admin_bar',
				),
			),

			'initial_setup' => array(
				'id' => 'initial_setup',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'title' => __( 'Use the wizard to setup a new membership.', MS_TEXT_DOMAIN ),
				'value' => __( 'Activate the Wizard', MS_TEXT_DOMAIN ),
				'button_value' => 1,
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
					'field' => 'initial_setup',
				),
			),

			'menu_protection' => array(
				'id' => 'menu_protection',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Choose how you want to protect your WordPress menus.', MS_TEXT_DOMAIN ),
				'value' => $settings->menu_protection,
				'field_options' => $menu_protection_options,
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
					'field' => 'menu_protection',
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_general_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'General Settings', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<form action="" method="post">
				<?php
				MS_Helper_Html::settings_box(
					array( $fields['plugin_enabled'] ),
					__( 'Content Protection', MS_TEXT_DOMAIN )
				);

				MS_Helper_Html::settings_box(
					array( $fields['hide_admin_bar'] ),
					__( 'Hide admin toolbar', MS_TEXT_DOMAIN )
				);

				MS_Helper_Html::settings_box(
					array( $fields['initial_setup'] ),
					__( 'Setup Wizard', MS_TEXT_DOMAIN )
				);

				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_ADV_MENUS ) ) {
					MS_Helper_Html::settings_box(
						array( $fields['menu_protection'] ),
						__( 'Advanced menu protection', MS_TEXT_DOMAIN )
					);
				}
				?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAGES
	 * ====================================================================== */

	public function render_tab_pages() {

		$action = MS_Controller_Page::AJAX_ACTION_UPDATE_PAGE;
		$nonce = wp_create_nonce( $action );

		$ms_pages = $this->data['ms_pages'];

		$fields = array();
		foreach ( $ms_pages as $ms_page ) {
			$fields['pages'][ $ms_page->type ] = array(
				'id' => $ms_page->type,
				'page_id' => $ms_page->id,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'read_only' => true,
				'title' => sprintf( __( 'Select %s page', MS_TEXT_DOMAIN ), $ms_page->title ),
				'value' => sprintf( '/%1$s/', $ms_page->slug ),
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'page_type' => $ms_page->type,
					'field' => 'slug',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			);
			$fields['edit'][ $ms_page->type ] = array(
				'id' => 'edit_slug_' . $ms_page->type,
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Edit URL', MS_TEXT_DOMAIN ),
				'class' => 'ms-edit-url',
			);
		}

		$fields = apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Page Settings', MS_TEXT_DOMAIN ),
					'desc' => __( 'Set Up plugin pages that will be displayed on your website. Membership Page, Registration Page etc.', MS_TEXT_DOMAIN ),
				)
			);
			?>
			<div class="ms-separator"></div>

			<form action="" method="post">

				<?php foreach ( $fields['pages'] as $page_type => $field ) :
					MS_Helper_Html::html_element( $field );
					MS_Helper_Html::html_element( $fields['edit'][ $page_type ] );
					?>
					<div id="ms-settings-page-links-wrapper">
						<?php
						MS_Helper_Html::html_link(
							array(
								'id' => 'url_page_' . $field['page_id'],
								'url' => get_permalink( $field['page_id'] ),
								'value' => __( 'View Page', MS_TEXT_DOMAIN ),
							)
						);
						?>
						<span> | </span>
						<?php
						MS_Helper_Html::html_link(
							array(
								'id' => 'edit_url_page_' . $field['page_id'],
								'url' => get_edit_post_link( $field['page_id'] ),
								'value' => __( 'Edit Page', MS_TEXT_DOMAIN ),
							)
						);
						?>
					</div>
				<?php endforeach; ?>
			</form>
		</div>
		<?php MS_Helper_Html::save_text();
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAYMENT
	 * ====================================================================== */

	public function render_tab_payment() {
		$view = MS_Factory::create( 'MS_View_Settings_Payment' );

		ob_start();
		?>
		<div class="ms-settings">
			<div id="ms-payment-settings-wrapper">
				<?php $view->render(); ?>
			</div>
		</div>
		<?php
		echo '' . ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PROTECTION MESSAGE
	 * ====================================================================== */

	public function render_tab_messages_protection() {
		$settings = $this->data['settings'];
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_PROTECTION_MSG;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'content' => array(
				'editor' => array(
					'id' => 'content',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_CONTENT ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary ms-ajax-update',
					'data_ms' => array(
						'type' => 'content',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'shortcode' => array(
				'editor' => array(
					'id' => 'shortcode',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected shortcode content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary ms-ajax-update',
					'data_ms' => array(
						'type' => 'shortcode',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'more_tag' => array(
				'editor' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content under more tag.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_MORE_TAG ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary ms-ajax-update',
					'data_ms' => array(
						'type' => 'more_tag',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );

		$membership = $this->data['membership'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$has_more = $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<form class="ms-form" action="" method="post">
				<?php
				MS_Helper_Html::settings_box(
					$fields['content'],
					__( 'Content protection message', MS_TEXT_DOMAIN ),
					'',
					'open'
				);

				MS_Helper_Html::settings_box(
					$fields['shortcode'],
					__( 'Shortcode protection message', MS_TEXT_DOMAIN ),
					'',
					'open'
				);

				if ( $has_more ) {
					MS_Helper_Html::settings_box(
						$fields['more_tag'],
						__( 'More tag protection message', MS_TEXT_DOMAIN ),
						'',
						'open'
					);
				}
				?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               AUTOMATED MESSAGES
	 * ====================================================================== */

	public function render_tab_messages_automated() {
		$comm = $this->data['comm'];

		$action = MS_Controller_Communication::AJAX_ACTION_UPDATE_COMM;
		$nonce = wp_create_nonce( $action );
		$comm_titles = MS_Model_Communication::get_communication_type_titles();

		$fields = array(
			'comm_type' => array(
				'id' => 'comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => @$comm->type,
				'field_options' => $comm_titles,
			),

			'switch_comm_type' => array(
				'id' => 'switch_comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Load Email', MS_TEXT_DOMAIN ),
			),

			'type' => array(
				'id' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => @$comm->type,
			),

			'enabled' => array(
				'id' => 'enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => @$comm->enabled,
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => @$comm->type,
					'field' => 'enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_unit' => array(
				'id' => 'period_unit',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Period after/before', MS_TEXT_DOMAIN ),
				'value' => @$comm->period['period_unit'],
			),

			'period_type' => array(
				'id' => 'period_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => @$comm->period['period_type'],
				'field_options' => MS_Helper_Period::get_periods(),
			),

			'subject' => array(
				'id' => 'subject',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Message Subject', MS_TEXT_DOMAIN ),
				'value' => @$comm->subject,
				'class' => 'ms-comm-subject widefat',
			),

			'message' => array(
				'id' => 'message',
				'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'value' => @$comm->description,
				'field_options' => array( 'media_buttons' => false, 'editor_class' => 'ms-ajax-update' ),
			),

			'cc_enabled' => array(
				'id' => 'cc_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Send copy to Administrator', MS_TEXT_DOMAIN ),
				'value' => @$comm->cc_enabled,
			),

			'cc_email' => array(
				'id' => 'cc_email',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => @$comm->cc_email,
				'field_options' => MS_Model_Member::get_admin_user_emails(),
			),

			'save_email' => array(
				'id' => 'save_email',
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			),

			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'save_comm',
			),

			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'save_comm' ),
			),

			'load_action' => array(
				'id' => 'load_action',
				'name' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'load_action',
			),

			'load_nonce' => array(
				'id' => '_wpnonce1',
				'name' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'load_action' ),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_messages_automated_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Automated Messages', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<form id="ms-comm-type-form" action="" method="post">
				<?php MS_Helper_Html::html_element( $fields['load_action'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['load_nonce'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['comm_type'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['switch_comm_type'] ); ?>
			</form>

			<div class="ms-separator"></div>

			<form action="" method="post" class="ms-editor-form">
				<?php
				MS_Helper_Html::html_element( $fields['action'] );
				MS_Helper_Html::html_element( $fields['nonce'] );
				MS_Helper_Html::html_element( $fields['type'] );

				if ( is_a( $comm, 'MS_Model_Communication' ) ) {
					printf(
						'<h3>%1$s %2$s: %3$s</h3><div class="ms-description" style="margin-bottom:20px;">%4$s</div>',
						esc_html( $comm_titles[ $comm->type ] ),
						__( 'Message', MS_TEXT_DOMAIN ),
						MS_Helper_Html::html_element( $fields['enabled'], true ),
						$comm->get_description()
					);

					if ( $comm->period_enabled ) {
						echo '<div class="ms-period-wrapper">';
						MS_Helper_Html::html_element( $fields['period_unit'] );
						MS_Helper_Html::html_element( $fields['period_type'] );
						echo '</div>';
					}
				}

				MS_Helper_Html::html_element( $fields['subject'] );
				MS_Helper_Html::html_element( $fields['message'] );

				MS_Helper_Html::html_element( $fields['cc_enabled'] );
				echo ' &nbsp; ';
				MS_Helper_Html::html_element( $fields['cc_email'] );
				MS_Helper_Html::html_separator();
				MS_Helper_Html::html_element( $fields['save_email'] );
				?>
			</form>
		</div>
		<?php
		/**
		 * Print JS details for the custom TinyMCE "Insert Variable" button
		 *
		 * @see class-ms-controller-settings.php (function add_mce_buttons)
		 * @see ms-view-settings-automated-msg.js
		 */
		$var_button = array(
			'title' => __( 'Insert Membership Variables', MS_TEXT_DOMAIN ),
			'items' => @$comm->comm_vars,
		);
		printf(
			'<script>window.ms_data.var_button = %1$s;window.ms_data.lang_confirm = %2$s</script>',
			json_encode( $var_button ),
			json_encode(
				__( 'You have made changes that are not saved yet. Do you want to discard those changes?', MS_TEXT_DOMAIN )
			)
		);

		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               MEDIA / DOWNLOADS
	 * ====================================================================== */

	public function render_tab_downloads() {
		$settings = $this->data['settings'];

		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'protection_enabled' => array(
				'id' => 'protection_enabled',
				'title' => __( 'Media / Downloads protection', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $settings->downloads['protection_enabled'],
				'class' => '',
				'data_ms' => array(
					'field' => 'protection_enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),
			'protection_type' => array(
				'id' => 'protection_type',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'Protection method', MS_TEXT_DOMAIN ),
				'value' => $settings->downloads['protection_type'],
				'field_options' => MS_Model_Rule_Media::get_protection_types(),
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'field' => 'protection_type',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),
			'masked_url' => array(
				'id' => 'masked_url',
				'desc' => esc_html( trailingslashit( get_option( 'home' ) ) ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Masked download URL:', MS_TEXT_DOMAIN ),
				'value' => $settings->downloads['masked_url'],
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'field' => 'masked_url',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),
		);

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA_PLUS ) ) {
			unset( $fields['protection_type'] );
		}

		$fields = apply_filters( 'ms_view_settings_prepare_downloads_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Media / Download Settings', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<div>
				<form action="" method="post">
					<?php MS_Helper_Html::settings_box( $fields ); ?>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               IMPORT
	 * ====================================================================== */

	public function render_tab_import() {
		$export_action = MS_Controller_Dialog::AJAX_EXPORT;

		$export_fields = array(
			'export' => array(
				'id' => 'btn_export',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => 'Export',
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $export_action,
			),
			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $export_action ),
			),
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Import Tool', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<div>
				<form action="" method="post">
					<?php MS_Helper_Html::settings_box( $export_fields ); ?>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}