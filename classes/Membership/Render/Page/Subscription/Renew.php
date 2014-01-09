<?php
// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+


// FIXME: this template class contains a lot of functionality which should not
// be here. Unfortunately I have to leave it here for now.

/**
 * Renders renew subscription page template.
 *
 * @category Membership
 * @package Render
 * @subpackage Page
 *
 * @since 3.5
 */
class Membership_Render_Page_Subscription_Renew extends Membership_Render {

	/**
	 * Renders message for non logged in users.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function _render_login_message() {
		?><div id="membership-wrapper">
			<form class="form-membership">
				<fieldset>
					<legend><?php echo __( 'Your Subscriptions', 'membership' ) ?></legend>
					<div class="alert alert-error">
						<?php echo __( 'You are not currently logged in. Please login to view your subscriptions.', 'membership' ) ?>
					</div>
				</fieldset>
			</form>
		</div><?php
	}

	/**
	 * Renders subscription plans for user without subscription.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @global membershippublic $membershippublic The global membershippublic object.
	 */
	private function _render_select_subscriptions() {
		global $membershippublic;

		$factory = Membership_Plugin::factory();
		$subs = array_filter( (array) apply_filters( 'membership_override_subscriptions', $membershippublic->get_subscriptions() ) );

		?><div id="membership-wrapper">
			<form class="form-membership" method="post">
				<fieldset>
					<legend><?php echo __( 'Your Subscriptions', 'membership' ); ?></legend>

					<div class="alert alert-error"><?php
						esc_html_e( 'You do not currently have any subscriptions in place. You can sign up for a new subscription by selecting one below', 'membership' )
					?></div>

					<div class="priceboxes"><?php
						do_action( 'membership_subscription_form_before_subscriptions' );

						foreach ( $subs as $sub ) :
							$this->_render_buy_subscription( $factory->get_subscription( $sub->id ) );
						endforeach;

						do_action( 'membership_subscription_form_after_subscriptions' );
					?></div><!-- price boxes -->
				</fieldset>
			</form>
		</div><?php
	}

	/**
	 * Render buy subscription plan box.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @global array $M_options The settings array.
	 * @param Membership_Model_Subscription $subscription
	 */
	private function _render_buy_subscription( Membership_Model_Subscription $subscription ) {
		global $M_options;

		$pricing = $subscription->get_pricingarray();

		?><div class="pricebox subscriptionbox" id="subscriptionbox-<?php echo $subscription->id ?>">
			<div class="topbar">
				<span class="title"><?php echo $subscription->sub_name() ?></span>
			</div>

			<div class="pricedetails">
				<?php echo $subscription->sub_description() ?>
			</div>

			<div class="bottombar">
				<?php if ( !empty( $pricing ) ) : ?>
					<div class="link" style="float:right;margin-right:10px">
						<?php
						$class = '';
						if ( $M_options['formtype'] == 'new' ) {
							// pop up form
							$link = add_query_arg( array( 'action' => 'buynow', 'subscription' => $subscription->id ), admin_url( 'admin-ajax.php' ) );
							$class = 'popover';
						} else {
							// original form
							$link = add_query_arg( array( 'action' => 'registeruser', 'subscription' => $subscription->id ), get_permalink( $M_options['registration_page'] ) );
						}
						?><a href="<?php echo esc_url( $link ) ?>" class="button <?php echo $class ?> <?php echo esc_attr( apply_filters( 'membership_subscription_button_color', 'blue' ) ) ?>">
						<?php echo esc_html( apply_filters( 'membership_subscription_signup_text', __( 'Sign Up', 'membership' ) ) ) ?>
						</a>
					</div>
				<?php endif; ?>
				<span class="price"><?php echo $subscription->sub_pricetext() ?></span>
			</div>
		</div><?php
	}

	/**
	 * Process actions.
	 *
	 * @since 3.5
	 *
	 * @access private
	 */
	private function _process_action() {
		if ( !isset( $_POST['action'] ) || !isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$member = current_member();
		$user = filter_input( INPUT_POST, 'user', FILTER_VALIDATE_INT );
		if ( $user != $member->ID ) {
			return;
		}

		$nonce = $_REQUEST['_wpnonce'];
		$sub_id = filter_input( INPUT_POST, 'subscription', FILTER_VALIDATE_INT );

		//Handle the processing if needed
		switch ( $_POST['action'] ) {
			case 'unsubscribe':
				// Unsubscribe button has been clicked for solo gateways
				if ( apply_filters( 'membership_unsubscribe_subscription', true, $sub_id, $user ) && wp_verify_nonce( $nonce, 'cancel-sub_' . $sub_id ) ) {
					if ( filter_input( INPUT_POST, 'gateway' ) == 'admin' ) {
						$member->expire_subscription( $sub_id );
					} else {
						$member->mark_for_expire( $sub_id );
					}
				}
				break;

			case 'renewfree':
				// Renew a free level on this subscription
				$level = (int) $_POST['level'];
				if ( apply_filters( 'membership_renewfree_subscription', true, $sub_id, $user ) && wp_verify_nonce( $nonce, 'renew-sub_' . $sub_id ) ) {
					$member->record_active_payment( $sub_id, $level, time() );
				}
				//update_user_meta( $member->ID, '_membership_last_upgraded', time());
				break;

			case 'upgradesolo': // Upgrade a solo subscription
				$fromsub_id = (int) $_POST['fromsub_id'];
				$gateway = $_POST['gateway'];
				if ( apply_filters( 'membership_upgradesolo_subscription', true, $sub_id, $user ) && wp_verify_nonce( $nonce, 'upgrade-sub_' . $sub_id ) ) {
					// Join the new subscription
					$member->create_subscription( $sub_id, $gateway );
					// Remove the old subscription
					$member->drop_subscription( $fromsub_id );
					// Timestamp the update
					update_user_meta( $user, '_membership_last_upgraded', time() );
				}
				break;

			case 'upgradefromfree':
				$fromsub_id = (int) $_POST['fromsub_id'];
				$gateway = $_POST['gateway'];
				if ( apply_filters( 'membership_upgradefromfree_subscription', true, $sub_id, $user ) && wp_verify_nonce( $nonce, 'upgrade-sub_' . $sub_id ) ) {
					// Join the new subscription
					$member->create_subscription( $sub_id, $gateway );
					// Remove the old subscription
					$member->drop_subscription( $fromsub_id );
					// Timestamp the update
					update_user_meta( $user, '_membership_last_upgraded', time() );
				}
				break;
		}
	}

	/**
	 * Returns transformed period unit into date period.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @param string $unit The period unit to transoform.
	 * @param int $period The period amount.
	 * @return string Transformed date period.
	 */
	private function _get_period( $unit, $period ) {
		switch ( $unit ) {
			case 'w':
				return _n( 'week', 'weeks', $period, 'membership' );
			case 'm':
				return _n( 'month', 'months', $period, 'membership' );
			case 'y':
				return _n( 'year', 'years', $period, 'membership' );
		}

		return _n( 'day', 'days', $period, 'membership' );
	}

	/**
	 * Renders renew subscriptions.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @global membershippublic $membershippublic The global membershippublic object.
	 * @global array $M_options The settings array.
	 */
	private function _render_renew_subscription() {
		global $membershippublic, $M_options;

		$factory = Membership_Plugin::factory();

		// The user has a subscription so we can display it with the information
		$member = current_member();
		$rels = array_filter( (array)$member->get_relationships() );

		if ( empty( $M_options['renewalperiod'] ) ) {
			$M_options['renewalperiod'] = 7;
		}

		?><div id="membership-wrapper">
			<legend><?php echo __( 'Your Subscriptions', 'membership' ) ?></legend>

			<div class="alert alert-success">
				<?php echo __( 'Your current subscriptions are listed here. You can renew, cancel or upgrade your subscriptions by using the forms below.', 'membership' ); ?>
			</div>

			<div class="priceboxes"><?php
				foreach ( $rels as $rel ) {
					$sub = $factory->get_subscription( $rel->sub_id );

					$nextlevel = $sub->get_next_level( $rel->level_id, $rel->order_instance );
					$currentlevel = $sub->get_level_at( $rel->level_id, $rel->order_instance );

					if ( !empty( $rel->usinggateway ) && $rel->usinggateway != 'admin' ) {
						$gateway = Membership_Gateway::get_gateway( $rel->usinggateway );

						if ( !empty( $gateway ) && $gateway->issingle ) {
							$gatewayissingle = 'yes';
						} else {
							$gatewayissingle = 'no';
						}
					} else {
						$gatewayissingle = 'admin';
					}

					?><div class="pricebox subscribedbox" id="subscribedbox-<?php echo $sub->id ?>">
						<div class="topbar">
							<span class="title"><?php echo $sub->sub_name(); ?></span>
						</div>

						<div class="pricedetails"><?php
							if ( $member->is_marked_for_expire( $rel->sub_id ) ) {
								echo __( 'Your membership has been cancelled and will expire on : ', 'membership' );
								echo date( "jS F Y", mysql2date( "U", $rel->expirydate ) );
							} else {
								if ( $currentlevel->sub_type == 'indefinite' ) {
									echo __( 'You are on an <strong>indefinite</strong> membership.', 'membership' );
								} elseif ( $gatewayissingle == 'yes' ) {
									echo __( 'Your membership is due to expire on : ', 'membership' );
									echo "<strong>" . date( "jS F Y", mysql2date( "U", $rel->expirydate ) ) . "</strong>";
								} else {
									// Serial gateway
									switch ( $currentlevel->sub_type ) {
										case 'serial':
											echo __( 'Your membership is set to <strong>automatically renew</strong>', 'membership' );
											break;

										case 'finite':
											if ( !empty( $nextlevel ) ) {
												// We have a level we can move to next
												echo __( 'Your membership is set to <strong>automatically renew</strong>', 'membership' );
											} else {
												echo __( 'Your membership is due to expire on: ', 'membership' );
												echo "<strong>" . date( "jS F Y", mysql2date( "U", $rel->expirydate ) ) . "</strong>";
											}
											break;
									}
								}
							}

							// Get the last upgrade time
							$upgradedat = get_user_meta( $member->ID, '_membership_last_upgraded', true );
							if ( empty( $upgradedat ) ) {
								$upgradedat = strtotime( '-1 year' );
							}

							$period = isset( $M_options['upgradeperiod'] ) ? $M_options['upgradeperiod'] : 1;
							if ( empty( $period ) && $period != 0 ) {
								$period = 1;
							}

							if ( !$member->is_marked_for_expire( $rel->sub_id ) && $gatewayissingle == 'yes' ) {
								$renewalperiod = strtotime( '-' . $M_options['renewalperiod'] . ' days', mysql2date( "U", $rel->expirydate ) );

								if ( $nextlevel && time() >= $renewalperiod ) {
									// we have a next level so we can display the details and form for it
									if ( $member->has_active_payment( $rel->sub_id, $nextlevel->level_id, $nextlevel->level_order ) ) {
										?><legend><?php echo __( 'Renewal your subscription', 'membership' ) ?></legend>
										<div class="renew-form">
											<div class="formleft">
												<p><?php
													printf(
														__( 'Renewal for the %s following %s has been completed.', 'membership' ),
														sprintf( '<strong>%s %s</strong>', $nextlevel->level_period, $this->_get_period( $nextlevel->level_period_unit, $nextlevel->level_period ) ),
														date( "jS F Y", mysql2date( "U", $rel->expirydate ) )
													)
												?></p>
											</div>
										</div> <!-- renew-form --><?php
									} else {
										?><div class="renew-form">
											<div class="formleft">
												<p><?php
													printf(
														$nextlevel->level_price > 0
															? __( 'To renew your subscription for another %s following %s you will need to pay %s.', 'membership' )
															: __( 'To renew your subscription for another %s following %s click on the button to the right.', 'membership' ),
														sprintf( '<strong>%s %s</strong>', $nextlevel->level_period, $this->_get_period( $nextlevel->level_period_unit, $nextlevel->level_period ) ),
														date( "jS F Y", mysql2date( "U", $rel->expirydate ) ),
														sprintf( '<strong>%s %s</strong>', $nextlevel->level_price, apply_filters( 'membership_real_currency_display', $M_options['paymentcurrency'] ) )
													);

													// Need to put in coupon code bit here in case they have signed up with one
													$gateway->display_subscribe_button( $sub, $sub->get_pricingarray(), $member->ID, $nextlevel->level_order );
												?></p>
											</div>
										</div> <!-- renew-form -->
										<?php
									}
								}
							}
						?></div>

						<div class="bottombar">
							<div style="float:right;margin-right:10px"><?php
								if ( !$member->is_marked_for_expire( $rel->sub_id ) ) {
									if ( $gatewayissingle != 'admin' && method_exists( $gateway, 'display_cancel_button' ) ) {
										$gateway->display_cancel_button( $sub, $sub->get_pricingarray(), $member->ID );
									} else {
										?><form class="unsubbutton" method="post">
											<input type="hidden" name="action" value="unsubscribe">
											<input type="hidden" name="gateway" value="admin">
											<input type="hidden" name="subscription" value="<?php echo esc_attr( $rel->sub_id ) ?>">
											<input type="hidden" name="user" value="<?php echo esc_attr( $member->ID ) ?>">
											<?php wp_nonce_field( 'cancel-sub_' . $rel->sub_id ) ?>
											<input type="submit" value="<?php esc_attr_e( 'Unsubscribe', 'membership' ) ?>" class="button <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>">
										</form><?php
									}
								}
							?></div>
						</div>
					</div> <!-- price box --><?php

					if ( $upgradedat <= strtotime( '-' . $period . ' days' ) ) {

						$upgradesubs = array();
						foreach ( array_filter( (array)apply_filters( 'membership_override_upgrade_subscriptions', $membershippublic->get_subscriptions() ) ) as $upgradesub ) {
							if ( $upgradesub->id == $rel->sub_id || $member->on_sub( $upgradesub->id ) ) {
								// Don't want to show our current subscription as we will display this above.
								continue;
							}

							$upgradesubs[] = $upgradesub;
						}

						// Show upgrades
						if ( !empty( $upgradesubs ) ) :
						?><legend class="upgradefrom-<?php echo $sub->id ?>">
							<?php printf( _x( 'Upgrade from %s', 'Upgrade from subscription plan', 'membership' ), $sub->sub_name() ) ?>
						</legend><?php
						endif;

						foreach ( $upgradesubs as $upgradesub ) {
							$subscription = $factory->get_subscription( $upgradesub->id );

							?><div class="pricebox upgradebox upgradefrom-<?php echo $sub->id; ?>" id="upgradebox-<?php echo $subscription->id ?>">
								<div class="topbar">
									<span class="title">
										<strong><?php _ex( 'Move to:', 'Move to another subscription', 'membership' ) ?></strong>
										<?php echo $subscription->sub_name() ?>
									</span>
								</div>

								<div class="pricedetails">
									<?php echo $subscription->sub_description() ?>
								</div>

								<div class="bottombar">
									<div style="float:right;margin-right:10px"><?php
										// do an upgrade button
										$pricing = $subscription->get_pricingarray();
										if ( !empty( $pricing ) ) {
											if ( $gatewayissingle != 'admin' ) {
												if ( $currentlevel->level_price < 1 ) {
													// We are on a free level, so need to do an upgrade from free
													if ( method_exists( $gateway, 'display_upgrade_from_free_button' ) ) {
														$gateway->display_upgrade_from_free_button( $subscription, $pricing, $member->ID, $rel->sub_id, $sub->id );
													}
												} else {
													// We want a normal upgrade button
													if ( method_exists( $gateway, 'display_upgrade_button' ) ) {
														$gateway->display_upgrade_button( $subscription, $pricing, $member->ID, $rel->sub_id );
													}
												}
											} else {
												$class = '';
												if ( $M_options['formtype'] == 'new' ) {
													// pop up form
													$link = add_query_arg( array( 'action' => 'buynow', 'subscription' => $subscription->id ), admin_url( 'admin-ajax.php' ) );
													$class = 'popover';
												} else {
													// original form
													$link = add_query_arg( array( 'action' => 'registeruser', 'subscription' => $subscription->id ), get_permalink( $M_options['registration_page'] ) );
												}
												?><a href="<?php echo esc_url( $link ) ?>" class="button <?php echo $class ?> <?php echo esc_attr( apply_filters( 'membership_subscription_button_color', 'blue' ) ) ?>">
												<?php echo esc_html( apply_filters( 'membership_subscription_signup_text', __( 'Sign Up', 'membership' ) ) ) ?>
												</a><?php
											}
										}
									?></div>

									<span class="price"><?php echo $subscription->sub_pricetext() ?></span>
								</div>
							</div> <!-- pricebox --><?php
						}
					}
				}
			?></div> <!-- price boxes -->
		</div><!-- membership wrapper --><?php
	}

	/**
	 * Renders button template.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function _to_html() {
		if ( defined( 'MEMBERSHIP_RENEW_FORM' ) && file_exists( MEMBERSHIP_RENEW_FORM ) ) {
			include MEMBERSHIP_RENEW_FORM;
			return;
		}

		$renew_form = apply_filters( 'membership_override_renew_form', false );
		if ( $renew_form && is_readable( $renew_form ) ) {
			include $renew_form;
			return;
		}

		if ( !is_user_logged_in() ) {
			$this->_render_login_message();
		} else {
			$this->_process_action();
			if ( !current_user_has_subscription() ) {
				$this->_render_select_subscriptions();
			} else {
				$this->_render_renew_subscription();
			}
		}
	}

}