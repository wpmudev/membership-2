<?php
/**
 * An Addon controller.
 *
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
 * Add-On controller for: Taxamo
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Addon_Taxamo extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'addon_taxamo';

	// Ajax Actions
	const AJAX_SAVE_SETTING = 'taxamo_save';

	/**
	 * Status of the Taxamo.js integration
	 *
	 * @since 1.1.0
	 *
	 * @var bool
	 */
	static protected $has_js = false;

	/**
	 * Holds a reference to the Taxamo settings-model
	 *
	 * @since 1.1.0
	 *
	 * @var MS_Addon_Taxamo_Model
	 */
	static protected $model = null;

	/**
	 * The Taxamo REST API object
	 *
	 * @since 1.1.0
	 *
	 * @var Taxamo
	 */
	static protected $api = null;

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {

	}

	/**
	 * Activates the Add-on logic, only executed when add-on is active.
	 *
	 * @since  1.1.0
	 */
	public function activate() {
		// Add new settings tab
		$this->add_filter(
			'ms_controller_settings_get_tabs',
			'settings_tabs',
			10, 2
		);

		$this->add_filter(
			'ms_view_settings_edit_render_callback',
			'manage_render_callback',
			10, 3
		);

		// Save settings via ajax
		$this->add_action(
			'wp_ajax_' . self::AJAX_SAVE_SETTING,
			'ajax_save_setting'
		);

		// Add the taxamo.js integration on the payment pages
		$this->add_action(
			'ms_show_prices',
			'add_taxamo_js'
		);

		// Replace default payment buttons with Taxamo compatible buttons
		$this->add_filter(
			'ms_gateway_form',
			'payment_form',
			10, 4
		);

		// Confirm payments with Taxamo
		$this->add_action(
			'ms_gateway_paypalsingle_payment_processed_' . MS_Model_Invoice::STATUS_PAID,
			'confirm_payment'
		);
		$this->add_action(
			'ms_gateway_paypalstandard_payment_processed_' . MS_Model_Invoice::STATUS_PAID,
			'confirm_payment'
		);
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Taxamo', MS_TEXT_DOMAIN ),
			'description' => __( 'Addresses EU VAT regulations.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-euro',
		);

		return $list;
	}

	/**
	 * Returns the Taxamo-Settings model
	 *
	 * @since  1.0.0
	 * @return MS_Addon_Taxamo_Model
	 */
	static public function get_model() {
		if ( null === self::$model ) {
			self::$model = MS_Factory::load( 'MS_Addon_Taxamo_Model' );
		}

		return self::$model;
	}

	/**
	 * Returns the Taxamo REST API object.
	 *
	 * @since  1.1.0
	 * @return Taxamo
	 */
	static public function get_api() {
		if ( null === self::$api ) {
			if ( ! class_exists( 'Taxamo' ) ) {
				require_once MS_Plugin::instance()->dir . '/lib/taxamo/Taxamo.php';
			}
			$model = self::get_model();

			// Initialize the Taxamo API connection
			$connection = new APIClient(
				$model->private_key,
				'https://api.taxamo.com'
			);

			// Initialize the Taxamo REST API wrapper.
			self::$api = new Taxamo( $connection );
		}

		return self::$api;
	}

	/**
	 * Add taxamo settings tab in settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'Taxamo', MS_TEXT_DOMAIN ),
			'url' => 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-settings&tab=' . self::ID,
		);

		return $tabs;
	}

	/**
	 * Add taxamo settings-view callback.
	 *
	 * @since 1.0.0
	 *
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Taxamo_View_Settings' );
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * @since 1.1.0
	 */
	public function ajax_save_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$model = self::get_model();
			$model->set( $_POST['field'], $_POST['value'] );
			$model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg );
	}

	/**
	 * Adds the taxamo.js integration to the current page.
	 *
	 * We let taxamo do the heavy lifting:
	 *  -The javascript searches the page for all elements that match '.price'
	 *   (e.g. <span class="price">12.00 USD</span>) and update the value with
	 *   the users country-price, including local VAT.
	 *   Taxamo assumes that the original price does NOT include VAT yet.
	 *  -Also it will detect the users country and allow the user to change it.
	 *  -The Javascript also updates payment buttons to PayPal and Stripe.
	 *
	 * Call this function on any page that contains prices or a payment button.
	 *
	 * @since 1.1.0
	 */
	public function add_taxamo_js() {
		// Only add the script once.
		if ( self::$has_js ) { return; }

		$model = self::get_model();
		self::$has_js = true;

		?>
		<script type="text/javascript" src="https://api.taxamo.com/js/v1/taxamo.all.js"></script>
		<script type="text/javascript">
		Taxamo.initialize(<?php echo json_encode( $model->get( 'public_key' ) ); ?>);
		Taxamo.setCurrencyCode(<?php echo json_encode( $model->currency ); ?>);
		Taxamo.scanPrices(
			'.price',
			{
				"priceTemplate": '${totalAmount} <small class="ms-vat-info"><?php _e( 'VAT', MS_TEXT_DOMAIN ); ?>: ${taxAmount} (${taxRate}%)</small>',
				"noTaxTitle": '<small class="ms-vat-info">(<?php _e( 'No tax', MS_TEXT_DOMAIN ); ?>)</small>',
				"taxTitle": false
			}
		);
		Taxamo.detectButtons();
		Taxamo.enhancePayPalForms();
		Taxamo.detectCountry();
		</script>
		<?php
	}

	/**
	 * Returns HTML code for a Payment button that is compatible with Taxamo.
	 *
	 * Currently Taxamo offers a special payment integration for:
	 * - Stripe
	 * - Braintree
	 *
	 * @since  1.1.0
	 * @param  string $html HTML code for the payment button.
	 * @param  MS_Model_Gateway $gateway Payment gateway.
	 * @param  MS_Model_Invoice $invoice The invoice which will be paid.
	 * @param  MS_Model_Membership $membership The membership refered to on the invoice.
	 * @return string New HTML code for the payment button.
	 */
	public function payment_form( $html, $gateway, $invoice, $membership ) {
		// custom pay button label defined in gateway settings
		$button_label = $gateway->pay_button_url;

		if ( strpos( $button_label, '://' ) !== false ) {
			$button_label = sprintf(
				'<img src="%1$s" />',
				$button_label
			);
		}

		switch ( $gateway->id ) {
			case MS_Gateway_Stripe::ID:
				$html = sprintf(
					'<button taxamo-button taxamo-provider="stripe" taxamo-price="%1$s" taxamo-item-description="%2$s" taxamo-product-type="%3$s" taxamo-currency="%4$s">%5$s</button>',
					esc_attr( $invoice->total ),
					esc_attr( $membership->name ),
					'default',
					esc_attr( $invoice->currency ),
					$button_label
				);
				break;

			// Braintree Button could be added here, when we add the gateway...
		}

		return $html;
	}

	/**
	 * A payment is confirmed by PayPal: Notify Taxamo that we're good!
	 *
	 * @since  1.1.0
	 * @param  MS_Model_Invoice $invoice
	 */
	public function confirm_payment_paypal( $invoice ) {
		$api = self::get_api();

		// Taxamo sets the "custom" PayPal field to the transaction-key
		if ( isset( $_POST['custom'] ) ) {
			$transaction_key = $_POST['custom'];
			$api->confirmTransaction( $transaction_key, null );
		}
	}

}