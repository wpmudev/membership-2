<?php
/**
 * Renders Invitation list.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage View
 */
class MS_Addon_Invitation_View_List extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$code_list = MS_Factory::create( 'MS_Addon_Invitation_Helper_Listtable' );
		$code_list->prepare_items();

		$title = __( 'Invitations', MS_TEXT_DOMAIN );
		$add_new_button = array(
			'id' => 'add_new',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'url' => MS_Controller_Plugin::get_admin_url(
				MS_Addon_Invitation::SLUG,
				array( 'action' => 'edit', 'invitation_id' => 0 )
			),
			'value' => __( 'Add New Code', MS_TEXT_DOMAIN ),
			'class' => 'button',
		);

		ob_start();
		?>
		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => $title,
					'title_icon_class' => 'wpmui-fa wpmui-fa-ticket',
				)
			);
			?>
			<div>
				<?php MS_Helper_Html::html_element( $add_new_button );?>
			</div>

			<form action="" method="post">
				<?php $code_list->display(); ?>
			</form>
			<p><em>
				<?php
				_e( 'By default all Memberships are protected and require an invitation code to register.<br>You can manually change this for individual memberships via a new setting in the "Payment Options" settings of each membership.', MS_TEXT_DOMAIN );
				?>
			</em></p>
		</div>

		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_addon_invitation_view_list_to_html', $html, $this );
	}
}