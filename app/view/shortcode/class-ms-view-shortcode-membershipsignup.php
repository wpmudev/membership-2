<?php
class MS_View_Shortcode_MembershipSignup extends MS_View {

	/**
	 * Return the HTML code.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$member = $this->data['member'];
		$subscriptions = $this->data['subscriptions'];
		$memberships = $this->data['memberships'];

		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<?php
			if ( count( $subscriptions ) > 0 ) {
				foreach ( $subscriptions as $subscription ) {
					$msg = $subscription->get_status_description();

					$membership = MS_Factory::load(
						'MS_Model_Membership',
						$subscription->membership_id
					);
                                        
                                        $membership->_move_from = $member->cancel_ids_on_subscription(
                                                $membership->id
                                        );
                                        
					switch ( $subscription->status ) {
						case MS_Model_Relationship::STATUS_CANCELED:
							$this->membership_box_html(
								$membership,
								MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW,
								$msg,
								$subscription
							);
							break;

						case MS_Model_Relationship::STATUS_EXPIRED:
							$this->membership_box_html(
								$membership,
								MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW,
								$msg,
								$subscription
							);
							break;

						case MS_Model_Relationship::STATUS_TRIAL:
						case MS_Model_Relationship::STATUS_ACTIVE:
						case MS_Model_Relationship::STATUS_WAITING:
							$this->membership_box_html(
								$membership,
								MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL,
								$msg,
								$subscription
							);
							break;

						case MS_Model_Relationship::STATUS_PENDING:
							if ( $membership->is_free() ) {
								$memberships[] = $membership;
							} else {
                                                            
                                                                if ( ! empty( $membership->_move_from ) ) {
                                                                        $m_action = MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE;
                                                                } else {
                                                                        $m_action = MS_Helper_Membership::MEMBERSHIP_ACTION_PAY;
                                                                }
                                                            
								$this->membership_box_html(
									$membership,
									$m_action,
									$msg,
									$subscription
								);
							}
							break;

						default:
							$this->membership_box_html(
								$membership,
								MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL,
								$msg,
								$subscription
							);
							break;
					}
				}
			}

			if ( $member->has_membership() && ! empty( $memberships ) ) {
				?>
				<legend class="ms-move-from">
					<?php _e( 'Available Memberships', 'membership2' ); ?>
				</legend>
				<?php
			}
			?>
			<div class="ms-form-price-boxes">
				<?php
				do_action(
					'ms_view_shortcode_membershipsignup_form_before_memberships',
					$this->data
				);

				foreach ( $memberships as $membership ) {
					if ( ! empty( $membership->_move_from ) ) {
						$action = MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE;
					} else {
						$action = MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP;
					}

					$this->membership_box_html(
						$membership,
						$action,
						null,
						null
					);
				}

				do_action(
					'ms_view_shortcode_membershipsignup_form_after_memberships',
					$this->data
				);

				do_action( 'ms_show_prices' );
				?>
			</div>
		</div>

		<div style="clear:both;"></div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return apply_filters(
			'ms_shortcode_signup',
			$html,
			$this->data
		);
	}

	/**
	 * Generate a standalone "Sign up for Membership" button.
	 *
	 * @since  1.0.0
	 *
	 * @param  MS_Model_Membership $membership The membership to sign up for.
	 * @param  string $label The button label.
	 * @return string
	 */
	public function signup_form( $membership, $label ) {
		$html = '';

		$url = $this->get_action_url(
			$membership,
			$this->data['action'],
			$this->data['step']
		);

		$link = array(
			'url' => $url,
			'class' => 'ms-pay-button button',
			'value' => $label,
		);
		$html = MS_Helper_Html::html_link( $link, true );

		return $html;
	}

	/**
	 * Returns a URL to trigger the specified membership action.
	 *
	 * The URL can be used in a link or a form with only a submit button.
	 *
	 * @since  1.0.0
	 * @param  string $action
	 * @return string The URL.
	 */
	protected function get_action_url( $membership, $action, $step ) {
		if ( empty( $this->data['member'] ) ) {
			$member = MS_Model_Member::get_current_member();
		} else {
			$member = $this->data['member'];
		}

		if ( is_numeric( $membership ) ) {
			$membership = MS_Factory::load(
				'MS_Model_Membership',
				$membership
			);
		}

		$membership->_move_from = $member->cancel_ids_on_subscription(
			$membership->id
		);

		$fields = $this->prepare_fields(
			$membership->id,
			$action,
			$step,
			$membership->_move_from
		);

		if ( is_user_logged_in() ) {
			$current = MS_Model_Pages::MS_PAGE_MEMBERSHIPS;
		} else {
			$current = MS_Model_Pages::MS_PAGE_REGISTER;
		}

		$url = MS_Model_Pages::get_page_url( $current );

		if ( $action == MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP ) {
			// Only add the membership_id to the URL.
			$url = esc_url_raw(
				add_query_arg(
					'membership_id',
					$membership->id,
					$url
				)
			);
		} else {
			$url = esc_url_raw(
				add_query_arg(
					'_wpnonce',
					wp_create_nonce( $action ),
					$url
				)
			);

			foreach ( $fields as $field ) {
				$url = esc_url_raw(
					add_query_arg(
						$field['id'],
						$field['value'],
						$url
					)
				);
			}
		}

		return apply_filters(
			'ms_view_shortcode_membershipsignup_action_url',
			$url,
			$action,
			$membership,
			$this
		);
	}

	/**
	 * Output the HTML content of a single membership box.
	 * This includes the membership name, description, price and the action
	 * button (Sign-up, Cancel, etc.)
	 *
	 * @since  1.0.0
	 * @param  MS_Model_Membership $membership
	 * @param  string $action
	 * @param  string $msg
	 * @param  MS_Model_Relationship $subscription
	 */
	private function membership_box_html( $membership, $action, $msg = null, $subscription = null ) {
		$fields = $this->prepare_fields(
			$membership->id,
			$action,
			$this->data['step'],
			$membership->_move_from
		);
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		if ( 0 == $membership->price ) {
			$price = __( 'Free', 'membership2' );
		} else {
			$price = sprintf(
				'%s %s',
				$settings->currency,
				MS_Helper_Billing::format_price( $membership->total_price ) // Includes Tax
			);
		}
		$price = apply_filters( 'ms_membership_price', $price, $membership );

		if ( is_user_logged_in() ) {
			$current = MS_Model_Pages::MS_PAGE_MEMBERSHIPS;
		} else {
			$current = MS_Model_Pages::MS_PAGE_REGISTER;
		}

		$url = MS_Model_Pages::get_page_url( $current );

		$classes = array(
			'ms-membership-details-wrapper',
			'ms-signup',
			'ms-membership-' . $membership->id,
			'ms-type-' . $membership->type,
			'ms-payment-' . $membership->payment_type,
			$membership->trial_period_enabled ? 'ms-with-trial' : 'ms-no-trial',
			'ms-status-' . ( $subscription ? $subscription->status : 'none' ),
			'ms-subscription-' . ($subscription ? $subscription->id : 'none' ),
		);
                
                $action_url = esc_url( $url );
                $membership_id = esc_attr( $membership->id );
                $membership_wrapper_classes = esc_attr( implode( ' ', $classes ) );
                $membership_name = esc_html( $membership->name );
                $membership_description = $membership->get_description();
                $membership_price = esc_html( $price );
                
                $class = apply_filters(
                        'ms_view_shortcode_membershipsignup_form_button_class',
                        'ms-signup-button ' . esc_attr( $action )
                );

                $button = array(
                        'id' => 'submit',
                        'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
                        'value' => esc_html( $this->data[ "{$action}_text" ] ),
                        'class' => $class,
                );

                /**
                 * Allow customizing the Signup button.
                 *
                 * Either adjust the array properties or return a valid HTML
                 * string that will be directly output.
                 *
                 * @since  1.0.1.2
                 * @param  array|string $button
                 * @param  MS_Model_Membership $membership
                 * @param  MS_Model_Subscription $subscription
                 */
                $button = apply_filters(
                        'ms_view_shortcode_membershipsignup_button',
                        $button,
                        $membership,
                        $subscription
                );
                
                if ( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL === $action ) {
                        /**
                         * PayPal Standard Gateway uses a special Cancel button.
                         *
                         * @see MS_Controller_Gateway
                         */
                        $button = apply_filters(
                                'ms_view_shortcode_membershipsignup_cancel_button',
                                $button,
                                $subscription,
                                $this
                        );
                } elseif ( MS_Helper_Membership::MEMBERSHIP_ACTION_PAY === $action ) {
                        // Paid membership: Display a Cancel button

                        $cancel_action = MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL;
                        $url = $this->get_action_url(
                                $membership->id,
                                $cancel_action,
                                '' // step is not required for cancel
                        );

                        $link = array(
                                'url' => $url,
                                'class' => 'ms-cancel-button button',
                                'value' => esc_html( $this->data[ "{$cancel_action}_text" ] ),
                        );
                        
                }
                
                $template_data = array(
                                    'membership_id' => $membership_id,
                                    'membership_wrapper_classes' => $membership_wrapper_classes,
                                    'membership_name' => $membership_name,
                                    'membership_description' => $membership_description,
                                    'membership_price' => $membership_price,
                                    'msg' => $msg,
                                    'action' => $action,
                                    'link' => $link,
                                    'fields' => $fields,
                                    'button' => $button
                                );
                $m2_obj = $this;
                ms_single_box_prepare( $template_data );
                ?>
                <form action="<?php echo $action_url; ?>" class="ms-membership-form" method="post">
                    <?php
                        wp_nonce_field( $fields['action']['value'] );
                        
                        if( $path = MS_Helper_Template::template_exists( 'membership_box_html.php' ) ) {
                            require $path;
                        }
                    ?>
                </form>
		<?php
		do_action( 'ms_show_prices' );
	}

	/**
	 * Return an array with input field definitions used on the
	 * membership-registration page.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $membership_id
	 * @param  string $action
	 * @param  string $step
	 * @param  string $move_from_id
	 * @return array Field definitions
	 */
	protected function prepare_fields( $membership_id, $action, $step, $move_from_id = null ) {
		$fields = array(
			'membership_id' => array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership_id,
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action,
			),
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $step,
			),
		);

		if ( $move_from_id ) {
			if ( is_array( $move_from_id ) ) {
				$move_from_id = implode( ',', $move_from_id );
			}

			$fields['move_from_id'] = array(
				'id' => 'move_from_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $move_from_id,
			);
		}

		if ( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL == $action ) {
			unset( $fields['step'] );
		}

		return $fields;
	}
}