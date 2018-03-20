<?php

/**
 * Class that handles Report functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Model
 */
 class MS_Model_Report_Members extends MS_Model {


	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @since  1.1.3
	 */
	public function process() {
		$contents 		= __( 'No Data', 'membership2' );
		$status 		= $_REQUEST['status'];
		$dir 			= MS_Helper_Media::get_membership_dir();
		$milliseconds 	= round( microtime( true ) * 1000 );
		if ( empty( $status ) ) { 
			$status = MS_Model_Relationship::STATUS_ACTIVE; 
		}
		$filename 		= $milliseconds . '_' . $status.'-memberships.csv';
		$header = apply_filters( 'ms_model_report_members_csv_header', array( 
			__( 'User ID', 'membership2' ),
			__( 'Email', 'membership2' ),
			__( 'Username', 'membership2' ),
			__( 'First Name', 'membership2' ),
			__( 'Last Name', 'membership2' ),
			__( 'Membership Name', 'membership2' ),
			__( 'Subscription Status', 'membership2' ),
			__( 'Payment Gateway', 'membership2' ),
			__( 'Payment Description', 'membership2' ),
			__( 'Start Date', 'membership2' ),
			__( 'End Date', 'membership2' )
		) );

		$data = array();
		$args = array();

		$args['posts_per_page'] 		= -1;
		$args['number'] 				= false;
		$args['offset'] 				= 0;
		$args['subscription_status'] 	= $status;
		if ( isset( $_REQUEST['membership_id'] ) ) {
			$args['membership_id'] 	= $_REQUEST['membership_id'];
		}
		$count = 0;
		$members = MS_Model_Member::get_members( $args );
		if ( is_array( $members ) && !empty( $members ) ) {
			$gateways = MS_Model_Gateway::get_gateway_names( false, true );
			foreach ( $members as $member ) {
				if ( $member->subscriptions ) {
					$sub = false;
					foreach ( $member->subscriptions as $subscription ) {
						$the_membership 			= $subscription->get_membership();
						$data[$count]['id'] 		= $member->id;
						$data[$count]['email'] 		= $member->email;
						$data[$count]['username'] 	= $member->username;
						$data[$count]['fname'] 		= $member->first_name;
						$data[$count]['lname'] 		= $member->last_name;
						$data[$count]['membership'] = $the_membership->name;
						$data[$count]['status'] 	= $subscription->status;
	
						if ( isset( $gateways[ $subscription->gateway_id ] ) ) {
							$gateway_name = $gateways[ $subscription->gateway_id ];
						} elseif ( empty( $subscription->gateway_id ) ) {
							$gateway_name = __( '- No Gateway -', 'membership2' );
						} else {
							$gateway_name = '(' . $subscription->gateway_id . ')';
						}
	
						$data[$count]['gateway'] 	= $gateway_name;
						$data[$count]['type'] 		= $subscription->get_payment_description( null, true );
						$data[$count]['start'] 		= $subscription->start_date;
						$data[$count]['end'] 		= $subscription->expire_date;
						$count++;
					}
					
				}
				
			}

			$filepath 		= $dir . DIRECTORY_SEPARATOR . $filename;
			$status 		= MS_Helper_Media::create_csv( $filepath, $data, $header );
			if ( $status && file_exists( $filepath ) ) {
				$handle 	= fopen( $filepath, "rb" );
				if ( $handle ) {
					$contents 	= fread( $handle, filesize( $filepath ) );
					fclose( $handle );
				}
				unlink( $filepath );
			}
		}

		mslib3()->net->file_download( $contents, $filename );
	}
 }
?>