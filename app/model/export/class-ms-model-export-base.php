<?php
/**
 * Class that handles Base Export functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Export_Base extends MS_Model {

	/**
	 * Identifier for this Import source
	 *
	 * @since  1.1.3
	 */
	const KEY = 'membership2';

	/**
	 * Set Export base messages
	 *
	 * @param String $type - the export type
	 *
	 * @return Array
	 */
	protected function export_base( $type = 'full' ) {
		$data 					= array();
		$data['source_key'] 	= self::KEY;
		$data['source'] 		= 'Membership2';
		$data['type'] 			= $type;
		$data['plugin_version'] = MS_PLUGIN_VERSION;
		$data['export_time']  	= date( 'Y-m-d H:i' );
		if ( $type === 'full' ) {
			$data['notes']  	= array(
				'title' => __( 'Exported data:', 'membership2' ),
				__( '- Memberships (without protection rules)', 'membership2' ),
				__( '- Members (including Stripe/Authorize payment settings)', 'membership2' ),
				__( '- Subscriptions (link between Members and Memberships)', 'membership2' ),
				__( '- Invoices', 'membership2' )
			);
		} else if ( $type === 'members' ) {
			$data['notes']  	= array(
				__( 'Exported data:', 'membership2' ),
				__( '- Members (including Stripe/Authorize payment settings)', 'membership2' ),
				__( '- Subscriptions (link between Members and Memberships)', 'membership2' ),
				__( '- Invoices', 'membership2' )
			);
		} else if ( $type === 'memberships' ) {
			$data['notes']  	= array(
				__( 'Exported data:', 'membership2' ),
				__( '- Memberships (without protection rules)', 'membership2' )
			);
		}
		
		return $data;
	}

	/**
	 * Export Membership data.
	 *
	 * @since  1.1.3
	 * @param  MS_Model_Membership $src
	 * @return object Export data
	 */
	protected function export_membership( $src ) {
		$obj 				= array();
		$obj['id'] 			= $this->exp_id( 'membership', $src->id );
		$obj['name']		= $src->name;
		$obj['description']	= $src->description;
		$obj['type']		= $src->type;
		$obj['active'] 		= (bool) $src->active;
		$obj['private']		= (bool) $src->private;
		$obj['free']		= (bool) $src->is_free;

		if ( ! $obj['free'] ) {
			$obj['price'] = $src->price;
			$obj['trial'] = (bool) $src->trial_period_enabled;

			switch ( $src->payment_type ) {
				case MS_Model_Membership::PAYMENT_TYPE_FINITE:
					$obj['payment_type'] 	= 'finite';
					$obj['period_unit'] 	= $src->period['period_unit'];
					$obj['period_type'] 	= $src->period['period_type'];
					break;

				case MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE:
					$obj['payment_type'] 	= 'date';
					$obj['period_start'] 	= $src->period_date_start;
					$obj['period_end'] 		= $src->period_date_end;
					break;

				case MS_Model_Membership::PAYMENT_TYPE_RECURRING:
					$obj['payment_type'] 		= 'recurring';
					$obj['period_unit'] 		= $src->pay_cycle_period['period_unit'];
					$obj['period_type'] 		= $src->pay_cycle_period['period_type'];
					$obj['period_repetition'] 	= $src->pay_cycle_repetition;
					break;

				default:
					$obj['payment_type'] 		= 'permanent';
					break;
			}

			if ( $obj['trial'] ) {
				$obj['trial_price'] 			= $src->trial_price;
				$obj['trial_period_unit'] 		= $src->trial_period['period_unit'];
				$obj['trial_period_type'] 		= $src->trial_period['period_type'];
			}
		}

		return apply_filters( 'ms_export/export_membership', $obj, $src, $this );
	}

	/**
	 * Export member specific data.
	 *
	 * @since  1.1.3
	 * @param  MS_Model_Member $member
	 * @return object Export data
	 */
	protected function export_member( $member ) {
		$output 				= array();
		$output['id'] 			= $this->exp_id( 'user', $member->id );
		$output['email'] 		= $member->email;
		$output['username'] 	= $member->username;
		$output['first_name'] 	= $member->first_name;
		$output['last_name'] 	= $member->last_name;

		// Get WP user data also.
		$user = get_userdata( $member->id );
		// Store them to data array.
		$output['wp_user'] 	= array(
			'nickname'      => $user->nickname,
			'description'   => $user->description,
			'user_url'      => $user->user_url,
			'display_name'  => $user->display_name,
			'user_nicename' => $user->user_nicename,
		);

		$gw_stripe 				= MS_Gateway_Stripe::ID;
		$gw_auth 				= MS_Gateway_Authorize::ID;

		$output['payment'] 		= array(
			// Stripe.
			'stripe_card_exp' 				=> $member->get_gateway_profile( $gw_stripe, 'card_exp' ),
			'stripe_card_num' 				=> $member->get_gateway_profile( $gw_stripe, 'card_num' ),
			'stripe_customer' 				=> $member->get_gateway_profile( $gw_stripe, 'customer_id' ),

			// Authorize.
			'authorize_card_exp' 			=> $member->get_gateway_profile( $gw_auth, 'card_exp' ),
			'authorize_card_num' 			=> $member->get_gateway_profile( $gw_auth, 'card_num' ),
			'authorize_cim_profile' 		=> $member->get_gateway_profile( $gw_auth, 'cim_profile_id' ),
			'authorize_cim_payment_profile' => $member->get_gateway_profile( $gw_auth, 'cim_payment_profile_id' ),
		);

		$output['subscriptions'] = array();
		foreach ( $member->subscriptions as $registration ) {
			$output['subscriptions'][] = $this->export_relationship( $registration );
		}

		return apply_filters( 'ms_export/export_member', $output, $member, $this );
	}


	/**
	 * Export specific data.
	 *
	 * @since  1.1.3
	 * @param  MS_Model_Relationship $src
	 * @return object Export data
	 */
	protected function export_relationship( $src ) {
		$membership				= MS_Factory::load( 'MS_Model_Membership', $src->membership_id );
		$obj 					= array();
		$obj['id'] 				= $this->exp_id( 'relationship', $src->id );
		$obj['membership'] 		= $this->exp_id( 'membership', $src->membership_id );
		$obj['membership_name'] = $membership->name;
		$obj['status'] 			= $src->status;
		$obj['gateway'] 		= $src->gateway_id;
		$obj['start'] 			= $src->start_date;
		$obj['end'] 			= $src->expire_date;

		$obj['trial_finished'] = $src->trial_period_completed;
		if ( ! $obj['trial_finished'] ) {
			$obj['trial_end'] = $src->trial_expire_date;
		}

		$obj['invoices'] 	= array();
		$invoices 			= $src->get_invoices();
		foreach ( $invoices as $invoice ) {
			$obj['invoices'][] = $this->export_invoice( $invoice );
		}

		return apply_filters( 'ms_export/export_relationship', $obj, $src, $this );
	}

	/**
	 * Export specific data.
	 *
	 * @since  1.1.3
	 * @param  MS_Model_Invoice $src
	 * @return object Export data
	 */
	protected function export_invoice( $src ) {
		$obj['id'] 				= $this->exp_id( 'invoice', $src->id );
		$obj['invoice_number'] 	= $src->invoice_number;
		$obj['external_id'] 	= $src->external_id;
		$obj['gateway']			= $src->gateway_id;
		$obj['status'] 			= $src->status;

		$obj['coupon'] 			= $this->exp_id( 'coupon', $src->coupon_id );
		$obj['currency']		= $src->currency;
		$obj['amount'] 			= $src->amount;
		$obj['discount']		= $src->discount;
		$obj['discount2'] 		= $src->pro_rate;
		$obj['total'] 			= $src->total;

		$obj['for_trial'] 		= (bool) $src->trial_period;
		$obj['due']				= $src->due_date;
		$obj['notes'] 			= $src->notes;

		return apply_filters( 'ms_export/export_invoice', $obj, $src, $this );
	}

	/**
	 * Returns a static export-ID for the given type.
	 *
	 * The export-ID will be same during this request but may change in the next
	 * export. This ID ensures that all links inside the export file are valid
	 * but that we do not use actual WordPress IDs.
	 *
	 * @since  1.1.3
	 * @param  string $type Type
	 * @param  int $internal_id WordPress ID
	 * @return int Export-ID
	 */
	protected function exp_id( $type, $internal_id ) {
		static $Counter = 10000;
		static $Ids 	= array();

		$Ids[$type] 	= mslib3()->array->get( $Ids[$type] );
		if ( ! isset( $Ids[$type][$internal_id] ) ) {
			$Ids[$type][$internal_id] = $Counter;
			$Counter += 1;
		}

		return $Ids[$type][$internal_id];
	}
}
?>