<?php
/**
 * Payment Setup page (only used when creating a new membership)
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Membership_PaymentSetup extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->get_fields();
		$wrapper_class = $this->data['is_global_payments_set'] ? '' : 'wide';

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Payment', 'membership2' ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-money',
					'desc' => __( 'Set up your payment gateways and Membership Price' ),
				)
			);
			?>
			<div class="ms-settings ms-wrapper-center ms-membership-payment cf <?php echo esc_attr( $wrapper_class ); ?>">
				<?php
				$this->global_payment_settings();
				$this->specific_payment_settings();

				MS_Helper_Html::settings_footer(
					$this->fields['control_fields'],
					$this->data['show_next_button']
				);
				?>
			</div>
		</div>

		<?php
		$html = ob_get_clean();

		echo $html;
	}

	private function get_fields() {
		$membership = $this->data['membership'];

		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'control_fields' => array(
				'membership_id' => array(
					'id' => 'membership_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $membership->id,
				),
				'step' => array(
					'id' => 'step',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['step'],
				),
				'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
				),
				'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce( $this->data['action'] ),
				),
			),
		);

		return apply_filters(
			'ms_view_membership_payment_get_fields',
			$fields
		);
	}

	/**
	 * Render the Payment settings the first time the user creates a membership.
	 * After the user set up a payment gateway these options are not displayed
	 * anymore
	 *
	 * @since  1.0.0
	 */
	public function global_payment_settings() {
		if ( $this->data['is_global_payments_set'] ) {
			return;
		}

		$view = MS_Factory::create( 'MS_View_Settings_Page_Payment' );

		echo '<div class="ms-half space">';
		$view->render();
		MS_Helper_Html::html_separator( 'vertical' );
		echo '</div>';
	}

	/**
	 * Render the payment box for a single Membership subscription.
	 *
	 * @since  1.0.0
	 */
	public function specific_payment_settings() {
		$membership = $this->data['membership'];

		$title = sprintf(
			__( 'Payment settings for %s', 'membership2' ),
			$membership->get_name_tag()
		);

		$type_class = $this->data['is_global_payments_set'] ? '' : 'ms-half right';
		?>
		<div class="ms-specific-payment-wrapper <?php echo esc_attr( $type_class ); ?>">
			<div class="ms-header">
				<div class="ms-settings-tab-title">
					<h3><?php echo $title; ?></h3>
				</div>
				<?php MS_Helper_Html::html_separator(); ?>
			</div>

			<div class="inside">
				<?php
				$view = MS_Factory::create( 'MS_View_Membership_Tab_Payment' );
				$view->data = $this->data;
				echo $view->to_html();
				?>
			</div>
			<?php MS_Helper_Html::save_text(); ?>
		</div>
		<?php
	}

}