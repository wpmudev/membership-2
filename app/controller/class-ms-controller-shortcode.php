<?php
/**
 * This file defines the MS_Controller_Shortcode class.
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
 * Controller for managing Plugin Shortcodes.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Shortcode extends MS_Controller {

	/**
	 * Prepare the shortcode hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		add_shortcode(
			MS_Helper_Shortcode::SCODE_REGISTER_USER,
			array( $this, 'membership_register_user' )
		);
		add_shortcode(
			MS_Helper_Shortcode::SCODE_SIGNUP,
			array( $this, 'membership_signup' )
		);
		add_shortcode(
			MS_Helper_Shortcode::SCODE_UPGRADE,
			array( $this, 'membership_upgrade' )
		);
		add_shortcode(
			MS_Helper_Shortcode::SCODE_RENEW,
			array( $this, 'membership_renew' )
		);

		add_shortcode(
			MS_Helper_Shortcode::SCODE_MS_TITLE,
			array( $this, 'membership_title' )
		);
		add_shortcode(
			MS_Helper_Shortcode::SCODE_MS_PRICE,
			array( $this, 'membership_price' )
		);

		add_shortcode(
			MS_Helper_Shortcode::SCODE_LOGIN,
			array( $this, 'membership_login' )
		);
		add_shortcode(
			MS_Helper_Shortcode::SCODE_MS_ACCOUNT,
			array( $this, 'membership_account' )
		);

		add_shortcode(
			MS_Helper_Shortcode::SCODE_MS_INVOICE,
			array( $this, 'membership_invoice' )
		);

		add_shortcode(
			MS_Helper_Shortcode::SCODE_GREEN_NOTE,
			array( $this, 'ms_green_note' )
		);
		add_shortcode(
			MS_Helper_Shortcode::SCODE_RED_NOTE,
			array( $this, 'ms_red_note' )
		);
	}

	/**
	 * Membership register callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_register_user( $atts ) {
		$data = apply_filters(
			'ms_controller_shortcode_membership_register_user_atts',
			shortcode_atts(
				array(
					'first_name' => substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 ),
					'last_name' => substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 ),
					'username' => substr( trim( filter_input( INPUT_POST, 'username' ) ), 0, 50 ),
					'email' => substr( trim( filter_input( INPUT_POST, 'email' ) ), 0, 50 ),
					'membership_id' => filter_input( INPUT_POST, 'membership_id' ),
					'errors' => '',
				),
				$atts
			)
		);
		$data['action'] = 'register_user';
		$data['step'] = MS_Controller_Frontend::STEP_REGISTER_SUBMIT;

		$view = MS_Factory::create( 'MS_View_Shortcode_Membership_Register_User' );
		$view->data = apply_filters( 'ms_view_shortcode_membership_register_user_data', $data, $this );

		return $view->to_html();
	}

	/**
	 * Membership signup callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_signup( $atts ) {
		$data = apply_filters(
			'ms_controller_shortcode_membership_signup_atts',
			shortcode_atts(
				array(
					MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP . '_text' => __( 'Signup', MS_TEXT_DOMAIN ),
					MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE . '_text' => __( 'Change', MS_TEXT_DOMAIN ),
					MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL . '_text' => __( 'Cancel', MS_TEXT_DOMAIN ),
					MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW . '_text' => __( 'Renew', MS_TEXT_DOMAIN ),
				),
				$atts
			)
		);

		$member = MS_Model_Member::get_current_member();
		$data['member'] = $member;
		$data['ms_relationships'] = array();

		if ( $member->is_valid() ) {
			// Get member's memberships, including pending relationships.
			$data['ms_relationships'] = MS_Model_Membership_Relationship::get_membership_relationships(
				array(
					'user_id' => $data['member']->id,
					'status' => 'valid',
				)
			);
		}

		$memberships = MS_Model_Membership::get_signup_membership_list( null, array_keys( $data['ms_relationships'] ) );

		$data['memberships'] = $memberships;

		// When Multiple memberships is not enabled, a member should move to another membership.
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
			// Membership Relationship status which can move to another one
			$valid_status = array(
				MS_Model_Membership_Relationship::STATUS_TRIAL,
				MS_Model_Membership_Relationship::STATUS_ACTIVE,
				MS_Model_Membership_Relationship::STATUS_EXPIRED,
			);

			foreach ( $data['member']->ms_relationships as $ms_relationship ) {
				if ( in_array( $ms_relationship->status, $valid_status ) ) {
					$data['move_from_id'] = $ms_relationship->membership_id;
					break;
				}
			}
		}

		$data['action'] = 'membership_signup';
		$data['step'] = MS_Controller_Frontend::STEP_PAYMENT_TABLE;

		$view = MS_Factory::create( 'MS_View_Shortcode_Membership_Signup' );
		$view->data = apply_filters( 'ms_view_shortcode_membership_signup_data', $data, $this );

		return $view->to_html();
	}

	/**
	 * Membership title shortcode callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_title( $atts ) {
		$title = null;

		$data = apply_filters(
			'ms_controller_shortcode_membership_title_atts',
			shortcode_atts(
				array(
					'id' => 0,
					'title' => '',
				),
				$atts
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $data['id'] );
			$title = sprintf(
				__( 'Membership title: %1$s', MS_TEXT_DOMAIN ),
				$membership->name
			);
		}
		else {
			$title = $data['title'];
		}

		return apply_filters( 'ms_controller_shortcode_membership_title', $title, $atts, $this );
	}

	/**
	 * Membership price shortcode callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_price( $atts ) {
		$price = 0;

		$data = apply_filters(
			'ms_controller_shortcode_membership_price_atts',
			shortcode_atts(
				array(
					'id' => 0,
				),
				$atts
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $data['id'] );
			$price = sprintf(
				__( 'Membership price: %1$s %2$s', MS_TEXT_DOMAIN ),
				MS_Factory::load( 'MS_Model_Settings' )->currency,
				$membership->price
			);
		}

		return apply_filters( 'ms_controller_shortcode_membership_price', $price, $atts, $this );
	}

	/**
	 * Membership login shortcode callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_login( $atts ) {
		$data = apply_filters(
			'ms_controller_shortcode_membership_login_atts',
			shortcode_atts(
				array(
					'holder'        => 'div',
					'holderclass'   => 'ms-login-form',
					'item'          => '',
					'itemclass'     => '',
					'postfix'       => '',
					'prefix'        => '',
					'wrapwith'      => '',
					'wrapwithclass' => '',
					'redirect'      => filter_input( INPUT_GET, 'redirect_to', FILTER_VALIDATE_URL ),
					'lostpass'      => '',
					'header'		=> true,
					'register'		=> true,
					'title'			=> '',
					'show_note'		=> true,   // Show the "you are not logged in" note?
					'lost_pass'		=> false,  // Show the lost-password form by default?
				),
				$atts
			)
		);

		$data['header'] = self::is_true( $data['header'] );
		$data['register'] = self::is_true( $data['register'] );
		$data['show_note'] = self::is_true( $data['show_note'] );

		$view = MS_Factory::create( 'MS_View_Shortcode_Membership_Login' );
		$view->data = apply_filters( 'ms_view_shortcode_membership_login_data', $data, $this );

		return $view->to_html();
	}

	/**
	 * Membership account page shortcode callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_account( $atts ) {
		$data = apply_filters(
			'ms_controller_shortcode_membership_account_atts',
			shortcode_atts(
				array(
					'user_id' => 0,
				),
				$atts
			)
		);

		$data['member'] = MS_Model_Member::get_current_member();
		if ( is_array( $data['member']->ms_relationships ) ) {
			foreach ( $data['member']->ms_relationships as $ms_relationship ) {
				$data['membership'][] = $ms_relationship->get_membership();
				$gateway = $ms_relationship->get_gateway();
				$data['gateway'][ $ms_relationship->id ] = $gateway;
			}
		}
		$data['invoices'] = MS_Model_Invoice::get_invoices(
			array(
				'author' => $data['member']->id,
				'posts_per_page' => 12,
				'meta_query' => array(
					array(
						'key' => 'amount',
						'value' => '0',
						'compare' => '!=',
					),
				)
			)
		);

		$data['events'] = MS_Model_Event::get_events(
			array(
				'author' => $data['member']->id,
				'posts_per_page' => 10,
			)
		);

		$view = MS_Factory::create( 'MS_View_Shortcode_Account' );
		$view->data = apply_filters( 'ms_view_shortcode_account_data', $data, $this );

		return $view->to_html();
	}

	/**
	 * Membership invoice shortcode callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_invoice( $atts ) {
		$data = apply_filters(
			'ms_controller_shortcode_invoice_atts',
			shortcode_atts(
				array(
					'post_id' => 0,
					'pay_button' => 1,
				),
				$atts,
				MS_Helper_Shortcode::SCODE_MS_INVOICE
			)
		);

		if ( ! empty( $data['post_id'] ) ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $data['post_id'] );
			$trial_invoice = null;

			if ( $invoice->trial_period ) {
				// This is the trial-period invoice. Use thethe first real invoice instead.
				$paid_args = array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => 'ms_relationship_id',
							'value' => $invoice->ms_relationship_id,
							'compare' => '=',
						),
						array(
							'key' => 'trial_period',
							'value' => '',
							'compare' => '=',
						),
						array(
							'key' => 'invoice_number',
							'value' => $invoice->invoice_number + 1,
							'compare' => '=',
						)
					)
				);
				$paid_invoice = MS_Model_Invoice::get_invoices( $paid_args );

				if ( ! empty( $paid_invoice ) ) {
					$trial_invoice = $invoice;
					$invoice = reset( $paid_invoice );
				}
			}

			$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $invoice->ms_relationship_id );

			$data['invoice'] = $invoice;
			$data['member'] = MS_Factory::load( 'MS_Model_Member', $invoice->user_id );
			$data['ms_relationship'] = $ms_relationship;
			$data['membership'] = $ms_relationship->get_membership();
			$data['gateway'] = MS_Model_Gateway::factory( $invoice->gateway_id );

			// Try to find a related trial-period invoice.
			if ( null === $trial_invoice ) {
				$trial_args = array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => 'ms_relationship_id',
							'value' => $invoice->ms_relationship_id,
							'compare' => '=',
						),
						array(
							'key' => 'trial_period',
							'value' => '',
							'compare' => '!=',
						),
						array(
							'key' => 'invoice_number',
							'value' => $invoice->invoice_number,
							'compare' => '<',
							'type' => 'NUMERIC',
						)
					)
				);
				$trial_invoice = MS_Model_Invoice::get_invoices( $trial_args );

				if ( ! empty( $trial_invoice ) ) {
					$trial_invoice = reset( $trial_invoice );
				}
			}

			$data['trial_invoice'] = $trial_invoice;

			$view = MS_Factory::create( 'MS_View_Shortcode_Invoice' );
			$view->data = apply_filters( 'ms_view_shortcode_invoice_data', $data, $this );

			return $view->to_html();
		}
	}

	/**
	 * Green text note shortcode callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function ms_green_note( $atts, $content = '' ) {

		$data = apply_filters(
			'ms_controller_ms_green_note_atts',
			shortcode_atts(
				array(
					'class' => 'ms-alert-box ms-alert-success',
				),
				$atts
			)
		);

		$content = sprintf( '<p class="%1$s"> %2$s </p> ', $data['class'], $content );

		return apply_filters( 'ms_controller_shortcode_ms_gren_note', $content, $this );
	}

	/**
	 * Evaluates if the specified variable is a boolean TRUE value
	 *
	 * @since  1.0.0
	 *
	 * @param  mixed $value The variable to evaluate.
	 * @return bool
	 */
	public static function is_true( $value ) {
		if ( true === $value || false === $value ) {
			return $value;
		} else if ( is_string( $value ) ) {
			return in_array( $value, array( '1', 'on', 'yes', 'true' ) );
		} else if ( is_scalar( $value ) ) {
			return ! ! $value;
		} else {
			return ! empty( $value );
		}
	}
}