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
 * View that displays the payment gateways and offers configuration options.
 *
 * This view is used in "Settings > Payments" and also in "Membership > Payment"
 * when the user creates the first membership (i.e. the global payment options)
 *
 * @uses MS_Helper_Html Helper used to create various form elements.
 *
 * @since   1.0
 *
 * @return  object
 */
class MS_View_Settings_Payment extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * HTML contains the list of available payment gateways.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$gateway_list = MS_Factory::create( 'MS_Helper_List_Table_Gateway' );
		$gateway_list->prepare_items();
		$fields = $this->get_global_payment_fields();

		$gateways = MS_Model_Gateway::get_gateways();

		ob_start();
		?>
		<div class="ms-global-payment-wrapper">
			<div class="ms-list-table-wrapper">
				<?php
				MS_Helper_Html::settings_tab_header(
					array(
						'title' => __( 'Global Payment Settings', MS_TEXT_DOMAIN ),
						'desc' => __( 'These are shared across all memberships.', MS_TEXT_DOMAIN ),
					)
				);
				?>
				<div class="ms-half space">
					<?php MS_Helper_Html::html_element( $fields['currency'] ); ?>
				</div>
				<div class="ms-half">
					<?php MS_Helper_Html::html_element( $fields['invoice_sender_name'] ); ?>
				</div>

				<div class="ms-group-head">
					<div class="ms-bold"><?php _e( 'Payment Gateways:', MS_TEXT_DOMAIN ); ?></div>
					<div class="ms-description"><?php _e( 'You need to set-up at least one Payment Gateway to be able to process payments.', MS_TEXT_DOMAIN ); ?></div>
				</div>

				<?php $gateway_list->display(); ?>
			</div>

			<?php MS_Helper_Html::settings_footer( null, false ); ?>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Prepares a list with field definitions that are required to render the
	 * payment list/global options (i.e. currency and sender name)
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	private function get_global_payment_fields() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'currency' => array(
				'id' => 'currency',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Select payment currency', MS_TEXT_DOMAIN ),
				'value' => $settings->currency,
				'field_options' => $settings->get_currencies(),
				'class' => '',
				'class' => 'chosen-select',
				'data_ms' => array(
					'field' => 'currency',
				),
			),

			'invoice_sender_name' => array(
				'id' => 'invoice_sender_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Invoice sender name', MS_TEXT_DOMAIN ),
				'value' => $settings->invoice_sender_name,
				'data_ms' => array(
					'field' => 'invoice_sender_name',
				),
			),
		);

		foreach ( $fields as $key => $field ) {
			if ( is_array( $field['data_ms'] ) ) {
				$fields[ $key ]['data_ms']['_wpnonce'] = $nonce;
				$fields[ $key ]['data_ms']['action'] = $action;
			}
		}

		return apply_filters( 'ms_gateway_view_get_global_payment_fields', $fields );
	}

}