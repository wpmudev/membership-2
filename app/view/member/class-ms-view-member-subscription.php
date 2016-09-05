<?php

/**
 * Dialog: Member Subscription Infos
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Member_Subscription extends MS_Dialog {

	/**
	 * Generate/Prepare the dialog attributes.
	 *
	 * @since  1.0.0
	 */
	public function prepare() {
		$subscription_id = $_POST['subscription_id'];
		$subscription = MS_Factory::load( 'MS_Model_Relationship', $subscription_id );

		if ( ! empty( $_REQUEST['statuscheck'] ) ) {
			$subscription->check_membership_status();
		}

		$data = array(
			'model' => $subscription,
		);

		$data = apply_filters( 'ms_view_member_subscription_data', $data );

		// Dialog Title
		$this->title = sprintf(
			__( 'Subscription Details: %1$s', 'membership2' ),
			esc_html( $subscription->get_membership()->name )
		);

		// Dialog Size
		$this->width = 940;
		$this->height = 800;

		// Contents
		$this->content = $this->get_contents( $data );

		// Make the dialog modal
		$this->modal = true;
	}

	/**
	 * Save the dialog details.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function submit() {
		// Does nothing...
	}

	/**
	 * Returns the contens of the dialog
	 *
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public function get_contents( $data ) {
		$subscription = $data['model'];
		$gateways = MS_Model_Gateway::get_gateway_names( false, true );

		if ( isset( $gateways[ $subscription->gateway_id ] ) ) {
			$gateway = $gateways[ $subscription->gateway_id ];
		} elseif ( empty( $subscription->gateway_id ) ) {
			$gateway = __( '- No Gateway -', 'membership2' );
		} else {
			$gateway = '(' . $subscription->gateway_id . ')';
		}

		$events = MS_Model_Event::get_events(
			array(
				'topic' => 'membership',
				'nopaging' => true,
				'relationship_id' => $subscription->id,
			)
		);

		$sub_details = array(
			'title' => __( 'Subscription Details', 'membership2' ),
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'value' => array(
				array( 'Subscription ID', $subscription->id ),
				array( 'Membership', $subscription->get_membership()->name ),
				array( 'Payment Gateway', $gateway ),
				array( 'Payment Type', $subscription->get_payment_description( null, true ) ),
				array( 'Subscription Start', $subscription->start_date ),
				array( 'Subscription End', $subscription->expire_date ),
				array( 'Status', $subscription->status ),
			),
			'field_options' => array(
				'head_col' => true,
			),
		);

		$evt_details = array();

		foreach ( $events as $event ) {
			$evt_details[] = array(
				'title' => __( 'Event Details', 'membership2' ),
				'type' => MS_Helper_Html::TYPE_HTML_TABLE,
				'value' => array(
					array( 'Event ID', $event->id ),
					array( 'Date', $event->date ),
					array( 'Description', $event->description ),
				),
				'field_options' => array(
					'head_col' => true,
				),
			);
		}

		ob_start();
		?>
		<div>
			<?php
			MS_Helper_Html::html_element( $sub_details );

			MS_Helper_Html::html_separator();

			foreach ( $evt_details as $detail ) {
				MS_Helper_Html::html_element( $detail );
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		return apply_filters( 'ms_view_member_subscription_to_html', $html );
	}

};