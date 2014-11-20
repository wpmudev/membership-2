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
 * Class that handles Export functions.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Import_Export extends MS_Model {

	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @since  1.1.0
	 */
	public function process() {
		$data = (object) array();
		$data->source = 'Protected Content';
		$data->plugin_version = MS_PLUGIN_VERSION;

		// Export the base membership (i.e. the Protected Content settings)
		$membership = MS_Model_Membership::get_protected_content();
		$data->protected_content = $this->export_membership( $membership->id, false );

		// Export all memberships.
		$memberships = MS_Model_Membership::get_memberships( array( 'post_parent' => 0 ) );
		$data->memberships = array();
		foreach ( $memberships as $membership ) {
			$data->memberships[] = $this->export_membership( $membership->id );
		}

		// Export the members.
		$members = MS_Model_Member::get_members();
		$data->members = array();
		foreach ( $members as $member ) {
			if ( ! $member->is_member ) { continue; }
			$data->members[] = $this->export_member( $member->id );
		}

		// Export plugin settings.
		$obj = array();
		$data->settings = $this->export_settings();

		// Export Coupons.
		$coupons = MS_Model_Coupon::get_coupons( array( 'nopaging' => true ) );
		$data->coupons = array();
		foreach ( $coupons as $coupon ) {
			$data->coupons[] = $this->export_coupon( $coupon->id );
		}

		WDev()->file_download( json_encode( $data ), 'protected-content-export.json' );
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.0
	 * @param  int $membership_id
	 * @return object Export data
	 */
	protected function export_membership( $membership_id, $has_children = true ) {
		$src = MS_Factory::load( 'MS_Model_Membership', $membership_id );

		$obj = (object) array();
		$obj->id = $this->exp_id( 'membership', $src->id );
		$obj->name = $src->name;
		$obj->description = $src->description;
		$obj->type = $src->type;
		$obj->active = (bool) $src->active;
		$obj->private = (bool) $src->private;
		$obj->free = (bool) $src->is_free;
		$obj->dripped = $src->dripped_type;

		if ( ! $obj->free ) {
			$obj->pay_type = $src->payment_type;
			$obj->price = $src->price;
			$obj->trial = (bool) $src->trial_period_enabled;

			switch ( $obj->pay_type ) {
				case MS_Model_Membership::PAYMENT_TYPE_FINITE:
					$obj->period = $src->period;
					break;

				case MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE:
					$obj->period_start = $src->period_date_start;
					$obj->period_end = $src->period_date_end;
					break;

				case MS_Model_Membership::PAYMENT_TYPE_RECURRING:
					$obj->period_cycle = $src->pay_cycle_period;
					break;
			}

			if ( $obj->trial ) {
				$obj->trial_price = $src->trial_price;
				$obj->trial_period = $src->trial_period;
			}
		}

		if ( $has_children ) {
			$children = MS_Model_Membership::get_memberships( array( 'post_parent' => $membership_id ) );
			if ( count( $children ) ) {
				$obj->children = array();
				foreach ( $children as $child ) {
					$obj->children[] = $this->export_membership( $child->id, false );
				}
			} else {
				$obj->children = false;
			}
		}

		return $obj;
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.0
	 * @param  int $member_id
	 * @return object Export data
	 */
	protected function export_member( $member_id ) {
		$src = MS_Factory::load( 'MS_Model_Member', $member_id );

		$obj = (object) array();
		$obj->id = $this->exp_id( 'user', $src->username );
		$obj->email = $src->email;
		$obj->username = $src->username;

		$gw_stripe = MS_Model_Gateway::GATEWAY_STRIPE;
		$gw_auth = MS_Model_Gateway::GATEWAY_AUTHORIZE;
		$obj->payment = array(
			// Stripe.
			'stripe_card_exp' => $src->get_gateway_profile( $gw_stripe, 'card_exp' ),
			'stripe_card_num' => $src->get_gateway_profile( $gw_stripe, 'card_num' ),
			'stripe_customer' => $src->get_gateway_profile( $gw_stripe, 'customer_id' ),

			// Authorize.
			'authorize_card_exp' => $src->get_gateway_profile( $gw_auth, 'card_exp' ),
			'authorize_card_num' => $src->get_gateway_profile( $gw_auth, 'card_num' ),
			'authorize_cim_profile' => $src->get_gateway_profile( $gw_auth, 'cim_profile_id' ),
			'authorize_cim_payment_profile' => $src->get_gateway_profile( $gw_auth, 'cim_payment_profile_id' ),
		);

		$obj->registrations = array();
		foreach ( $src->ms_relationships as $membership_id => $registration ) {
			$obj->registrations[] = $this->export_relationship( $registration );
		}

		return $obj;
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.0
	 * @param  MS_Model_Membership_Relationship $src
	 * @return object Export data
	 */
	protected function export_relationship( $src ) {
		$obj = (object) array();
		$obj->id = $this->exp_id( 'relationship', $src->id );
		$obj->membership = $this->exp_id( 'membership', $src->membership_id );
		$obj->status = $src->status;
		$obj->gateway = $src->gateway_id;
		$obj->start = $src->start_date;
		$obj->expire = $src->expire_date;

		$obj->trial_finished = $src->trial_period_completed;
		if ( ! $obj->trial_finished ) {
			$obj->trial_end = $src->trial_expire_date;
		}

		$obj->invoices = array();
		$invoices = $src->get_invoices();
		foreach ( $invoices as $invoice ) {
			$obj->invoices[] = $this->export_invoice( $invoice );
		}

		return $obj;
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.0
	 * @param  MS_Model_Invoice $src
	 * @return object Export data
	 */
	protected function export_invoice( $src ) {
		$obj = (object) array();
		$obj->id = $this->exp_id( 'invoice', $src->id );
		$obj->invoice_number = $src->invoice_number;
		$obj->external_id = $src->external_id;
		$obj->gateway = $src->gateway_id;
		$obj->status = $src->status;

		$obj->coupon = $this->exp_id( 'coupon', $src->coupon_id );
		$obj->currency = $src->currency;
		$obj->amount = $src->amount;
		$obj->discount = $src->discount;
		$obj->pro_rate = $src->pro_rate;
		$obj->total = $src->total;

		$obj->for_trial = (bool) $src->trial_period;
		$obj->due = $src->due_date;
		$obj->notes = $src->notes;

		$obj->taxable = (bool) $src->taxable;
		if ( $obj->taxable ) {
			$obj->tax_rate = $src->tax_rate;
			$obj->tax_name = $src->tax_name;
		}

		return $obj;
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.0
	 * @param  int $coupon_id
	 * @return object Export data
	 */
	protected function export_coupon( $coupon_id ) {
		return (object) array( 'coupon' . $coupon_id );
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.0
	 * @return object Export data
	 */
	protected function export_settings() {
		return (object) array( 'settings' );
	}

	/**
	 * Returns a static export-ID for the given type.
	 *
	 * The export-ID will be same during this request but may change in the next
	 * export. This ID ensures that all links inside the export file are valid
	 * but that we do not use actual WordPress IDs.
	 *
	 * @since  1.1.0
	 * @param  string $type Type
	 * @param  int $internal_id WordPress ID
	 * @return int Export-ID
	 */
	protected function exp_id( $type, $internal_id ) {
		static $Ids = array();

		$Ids[$type] = WDev()->get_array( $Ids[$type] );
		if ( ! isset( $Ids[$type][$internal_id] ) ) {
			$Ids[$type][$internal_id] = count( $Ids[$type] );
		}

		return $Ids[$type][$internal_id];
	}

}
