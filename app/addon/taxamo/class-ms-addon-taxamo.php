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
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		if ( self::is_active() ) {
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

			// Confirm payments with Taxamo
			$this->add_action(
				'ms_gateway_paypalsingle_payment_processed_' . MS_Model_Invoice::STATUS_PAID,
				'confirm_payment'
			);
			$this->add_action(
				'ms_gateway_paypalstandard_payment_processed_' . MS_Model_Invoice::STATUS_PAID,
				'confirm_payment'
			);

			// Add taxes to the price, based on users country.
			$this->add_filter(
				'ms_apply_taxes',
				'apply_taxes'
			);

			// Set tax-details on a new invoice.
			$this->add_filter(
				'ms_invoice_tax_rate',
				'invoice_tax_rate'
			);

			$this->add_filter(
				'ms_invoice_tax_name',
				'invoice_tax_name'
			);

			// Track the transaction in taxamo
			$this->add_filter(
				'ms_invoice_paid',
				'invoice_paid'
			);
		}
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
	 * Returns the Taxamo-Settings model.
	 *
	 * @since  1.0.0
	 * @return MS_Addon_Taxamo_Model
	 */
	static public function model() {
		static $Model = null;

		if ( null === $Model ) {
			$Model = MS_Factory::load( 'MS_Addon_Taxamo_Model' );
		}

		return $Model;
	}

	/**
	 * Returns the Taxamo REST API object.
	 *
	 * @since  1.1.0
	 * @return Taxamo
	 */
	static public function api() {
		static $Api = null;

		if ( null === $Api ) {
			$Api = MS_Factory::load( 'MS_Addon_Taxamo_Api' );
		}

		return $Api;
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
		$tabs[ self::ID ] = array(
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
			$view = MS_Factory::load( 'MS_Addon_Taxamo_View' );
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
			$model = self::model();
			$model->set( $_POST['field'], $_POST['value'] );
			$model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg );
	}

	/**
	 * Adds taxes to the net-amount.
	 *
	 * @since  1.1.0
	 * @param  numeric $net_value Net value
	 * @return numeric Gross value
	 */
	public function apply_taxes( $net_value ) {
		$gross_value = 0;

		if ( is_numeric( $net_value ) ) {
			$api = self::api();
			$tax = $api::tax_info( $net_value );
			$gross_value = $net_value + $tax->amount;
		}

		return $gross_value;
	}

	/**
	 * Return the tax-rate for the users country.
	 *
	 * @since  1.1.0
	 * @param  numeric $rate Default rate (0)
	 * @return numeric Tax rate to apply (e.g. 20 for 20%)
	 */
	public function invoice_tax_rate( $rate ) {
		$api = self::api();
		$tax = $api::tax_info();

		return $tax->rate;
	}

	/**
	 * Return the tax-name for the users country.
	 *
	 * @since  1.1.0
	 * @param  string $name Default name (empty string)
	 * @return string Tax display-name (e.g. 'EU Standard Tax (20 %)')
	 */
	public function invoice_tax_name( $rate ) {
		$api = self::api();
		$tax = $api::tax_info();

		return $tax->rate . '% ' . $tax->name;
	}

	/**
	 * When an invoice was paid we need to notify taxamo of the transaction.
	 *
	 * @since  1.1.0
	 * @param  MS_Model_Invoice $invoice The processed invoice.
	 */
	public function invoice_paid( $invoice ) {
		if ( $invoice->status != MS_Model_Invoice::STATUS_PAID ) { return; }
		if ( $invoice->total == 0 ) { return; }

		$membership = $invoice->get_membership();
		$member = $invoice->get_member();

		$api = self::api();
		$api::register_payment(
			$invoice->total,   // Transaction amount
			$membership->name, // Transaction title
			$invoice->tax_rate, // Tax-rate
			$invoice->invoice_number, // Internal Transaction ID = Invoice Number
			$member->full_name,  // Buyer name
			$member->email,  // Buyer email
			$invoice->gateway_id // Payment gateway
		);
	}

}