<?php
/**
 * Class that handles Members Export functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Export_Members extends MS_Model_Export_Base {

	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @param String $format - export format
	 *
	 * @since  1.1.3
	 */
	public function process( $format ) {
		$data 				= $this->export_base( 'members' ); 
		$data['members'] 	= array();
		$members 			= MS_Model_Member::get_members();
		foreach ( $members as $member ) {
			if ( ! $member->is_member ) { continue; }
			$data['members'][] = $this->export_member( $member );
		}
		$milliseconds 	= round( microtime( true ) * 1000 );
		$file_name 		= $milliseconds . '_membership2-members';
		switch ( $format ) {
			case MS_Model_Export::JSON_EXPORT :
				mslib3()->net->file_download( json_encode( $data ), $file_name . '.json' );
			break;

			case MS_Model_Export::XML_EXPORT :
				$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><membership2></membership2>");
				foreach ( $data as $key => $members ) {
					if ( is_array( $members ) ) {
						$node = $xml->addChild( $key );
						foreach ( $members as $member ) {
							if ( is_array( $member ) ) {
								$subnode = $node->addChild( substr( $key, 0, -1 ) );
								MS_Helper_Media::generate_xml( $subnode, $member );
							} else {
								$node->addChild( substr( $key, 0, -1 ), $member );
							}
						}
					} else {
						$xml->addChild( $key, $members );
					}
				}
				mslib3()->net->file_download( $xml->asXML(), $file_name . '.xml' );
			break;
		}
	}

	/**
	 * Export member data to array
	 * 
	 * @since 1.1.5
	 * 
	 * @param int $user_id - the user id
	 * 
	 * @return array
	 */
	public function member_data( $user_id ) {
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		$member_data = $this->export_member( $member );
		$data 	= array();
		$payment_data = array();
		if ( !empty( $member_data['payment']['stripe_card_num'] ) ) {
			$payment_data[] = array(
				'name' 	=> __( 'Stripe Card Expire', 'membership2' ),
				'value' 	=> $member_data['payment']['stripe_card_exp']
			);
			$payment_data[] = array(
				'name' 	=> __( 'Stripe Card Number', 'membership2' ),
				'value' 	=> $member_data['payment']['stripe_card_num']
			);
			$payment_data[] = array(
				'name' 	=> __( 'Stripe Card Customer', 'membership2' ),
				'value' => $member_data['payment']['stripe_customer']
			);
		}

		if ( !empty( $member_data['payment']['authorize_card_exp'] ) ) {
			$payment_data[] = array(
				'name' 	=> __( 'Auth.net Card Expire', 'membership2' ),
				'value' 	=> $member_data['payment']['authorize_card_exp']
			);
			$payment_data[] = array(
				'name' 	=> __( 'Auth.net Card Number', 'membership2' ),
				'value' 	=> $member_data['payment']['authorize_card_num']
			);
			$payment_data[] = array(
				'name' 	=> __( 'Auth.net CIM Profile', 'membership2' ),
				'value' => $member_data['payment']['authorize_cim_profile']
			);
			$payment_data[] = array(
				'name' 	=> __( 'Auth.net CIM Payment Profile', 'membership2' ),
				'value' => $member_data['payment']['authorize_cim_payment_profile']
			);
		}
		if ( !empty( $payment_data ) ) {
			$data[] = array(
				'group_id' 		=> 'member_payment_detail',
				'group_label' 	=> __( 'Member Payment Details', 'membership2' ),
				'item_id' 		=> "payment->{$user_id}",
				'data' 			=> $payment_data
			);
		}

		$member_invoices 	= array();
		$sub_data 			= array();
		if ( !empty( $member_data['subscriptions'] ) ) {
			foreach ( $member_data['subscriptions'] as $subscription ) {
				$sub_data[] = array(
					'name' 	=> __( 'Membership Name', 'membership2' ),
					'value' => $subscription['membership_name']
				);
				$sub_data[] = array(
					'name' 	=> __( 'Membership Status', 'membership2' ),
					'value' => $subscription['status']
				);
				$sub_data[] = array(
					'name' 	=> __( 'Membership Gateway', 'membership2' ),
					'value' => $subscription['gateway']
				);
				$sub_data[] = array(
					'name' 	=> __( 'Membership Start', 'membership2' ),
					'value' => $subscription['start']
				);

				$sub_data[] = array(
					'name' 	=> __( 'Membership End', 'membership2' ),
					'value' => $subscription['end']
				);

				$sub_data[] = array(
					'name' 	=> __( 'Total Invoices', 'membership2' ),
					'value' => count( $subscription['invoices'] )
				);
				if ( count( $subscription['invoices'] ) > 0 ) {
					array_push( $member_invoices, $subscription['invoices'] );
				}
			}
		}
		if ( !empty( $sub_data ) ) {
			$data[] = array(
				'group_id' 		=> 'member_subscription_detail',
				'group_label' 	=> __( 'Member Subscription Details', 'membership2' ),
				'item_id' 		=> "subscription->{$user_id}",
				'data' 			=> $sub_data
			);
		}

		$invoice_data = array();
		if ( !empty( $member_invoices ) ) {
			foreach ( $member_invoices as $invoices ) {
				foreach ( $invoices as $invoice ) {
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Number', 'membership2' ),
						'value' => $invoice['invoice_number']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Gateway', 'membership2' ),
						'value' => $invoice['gateway']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Status', 'membership2' ),
						'value' => $invoice['status']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Currency', 'membership2' ),
						'value' => $invoice['currency']
					);

					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Amount', 'membership2' ),
						'value' => $invoice['amount']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Discount', 'membership2' ),
						'value' => $invoice['discount']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Total', 'membership2' ),
						'value' => $invoice['total']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Due Date', 'membership2' ),
						'value' => $invoice['due']
					);
					$invoice_data[] = array(
						'name' 	=> __( 'Invoice Notes', 'membership2' ),
						'value' => implode( " ", $invoice['notes'] )
					);
				}
			}
		}

		if ( !empty( $invoice_data ) ) {
			$data[] = array(
				'group_id' 		=> 'member_invoice_detail',
				'group_label' 	=> __( 'Member Invoices', 'membership2' ),
				'item_id' 		=> "invoice->{$user_id}",
				'data' 			=> $invoice_data
			);
		}

		return $data;
	}
}
?>