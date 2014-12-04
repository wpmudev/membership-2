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

		// By default assume no content for the protected-content code
		add_shortcode(
			MS_Helper_Shortcode::SCODE_PROTECTED,
			array( $this, '__return_null' )
		);

		if ( MS_Plugin::is_enabled() ) {
			add_shortcode(
				MS_Helper_Shortcode::SCODE_REGISTER_USER,
				array( $this, 'membership_register_user' )
			);

			add_shortcode(
				MS_Helper_Shortcode::SCODE_SIGNUP,
				array( $this, 'membership_signup' )
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
				MS_Helper_Shortcode::SCODE_MS_DETAILS,
				array( $this, 'membership_details' )
			);

			add_shortcode(
				MS_Helper_Shortcode::SCODE_LOGIN,
				array( $this, 'membership_login' )
			);

			add_shortcode(
				MS_Helper_Shortcode::SCODE_LOGOUT,
				array( $this, 'membership_logout' )
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
		} else {
			$shortcodes = array(
				MS_Helper_Shortcode::SCODE_REGISTER_USER,
				MS_Helper_Shortcode::SCODE_SIGNUP,
				MS_Helper_Shortcode::SCODE_RENEW,
				MS_Helper_Shortcode::SCODE_MS_TITLE,
				MS_Helper_Shortcode::SCODE_MS_PRICE,
				MS_Helper_Shortcode::SCODE_LOGIN,
				MS_Helper_Shortcode::SCODE_LOGOUT,
				MS_Helper_Shortcode::SCODE_MS_ACCOUNT,
				MS_Helper_Shortcode::SCODE_MS_INVOICE,
				MS_Helper_Shortcode::SCODE_GREEN_NOTE,
				MS_Helper_Shortcode::SCODE_RED_NOTE,
			);

			foreach ( $shortcodes as $shortcode ) {
				add_shortcode( $shortcode, array( $this, 'ms_no_value' ) );
			}
		}
	}

	/**
	 * Set up the protected-content shortcode to display the protection message.
	 *
	 * This function is only called from the Frontend-Controller when the
	 * Membership Page "protected content" is displayed.
	 *
	 * @since  1.1.0
	 */
	public function page_is_protected() {
		remove_shortcode(
			MS_Helper_Shortcode::SCODE_PROTECTED,
			array( $this, '__return_null' )
		);

		add_shortcode(
			MS_Helper_Shortcode::SCODE_PROTECTED,
			array( $this, 'protected_content' )
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
					MS_Helper_Membership::MEMBERSHIP_ACTION_PAY . '_text' => __( 'Complete Payment', MS_TEXT_DOMAIN ),
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
		$view->data = apply_filters(
			'ms_view_shortcode_membership_signup_data',
			$data,
			$this
		);

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
		$code = '';

		$data = apply_filters(
			'ms_controller_shortcode_membership_title_atts',
			shortcode_atts(
				array(
					'id' => 0,
					'label' => __( 'Membership title:', MS_TEXT_DOMAIN ),
					'title' => '', // deprecated @since 1.1.0
				),
				$atts
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $data['id'] );
			$code = sprintf(
				'%1$s %1$s',
				$data['label'],
				$membership->name
			);

			$code = trim( $code );
		} else {
			$code = $data['title'];
		}

		return apply_filters(
			'ms_controller_shortcode_membership_title',
			$code,
			$atts,
			$this
		);
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
					'currency' => true,
					'label' => __( 'Membership price:', MS_TEXT_DOMAIN ),
				),
				$atts
			)
		);

		if ( ! empty( $data['id'] ) ) {
			if ( WDev()->is_true( $data['currency'] ) ) {
				$settings = MS_Factory::load( 'MS_Model_Settings' );
				$currency = $settings->currency;
			} else {
				$currency = '';
			}

			$membership = MS_Factory::load( 'MS_Model_Membership', $data['id'] );
			$price = sprintf(
				'%1$s %2$s %3$s',
				$data['label'],
				$currency,
				$membership->price
			);

			$price = trim( $price );
		}

		return apply_filters(
			'ms_controller_shortcode_membership_price',
			$price,
			$atts,
			$this
		);
	}

	/**
	 * Membership details shortcode callback function.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_details( $atts ) {
		$code = '';

		$data = apply_filters(
			'ms_controller_shortcode_membership_details_atts',
			shortcode_atts(
				array(
					'id' => 0,
					'label' => __( 'Membership details:', MS_TEXT_DOMAIN ),
				),
				$atts
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $data['id'] );
			$code = sprintf(
				'%1$s %1$s',
				$data['label'],
				$membership->description
			);

			$code = trim( $code );
		}

		return apply_filters(
			'ms_controller_shortcode_membership_details',
			$code,
			$atts,
			$this
		);
	}

	/**
	 * Display the "protected content" message.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function protected_content( $atts ) {
		global $post;

		$setting = MS_Plugin::instance()->settings;
		$protection_msg = $setting->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_CONTENT
		);

		$html = '<div class="ms-protected-content">';
		if ( ! empty( $protection_msg ) ) {
			$html .= $protection_msg;
		}

		if ( ! MS_Model_Member::is_logged_user() ) {
			$has_login_form = MS_Helper_Shortcode::has_shortcode(
				MS_Helper_Shortcode::SCODE_LOGIN,
				$post->post_content
			);

			if ( ! $has_login_form ) {
				$scode = '[' . MS_Helper_Shortcode::SCODE_LOGIN . ']';
				$html .= do_shortcode( $scode );
			}
		}
		$html .= '</div>';

		return apply_filters(
			'ms_controller_shortcode_protected_content',
			$html,
			$this
		);
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
					'header'		=> true,
					'register'		=> true,
					'title'			=> '',
					'show_note'		=> true,   // Show the "you are not logged in" note?
					'form'			=> '',  // [login|lost|reset|logout]
					'show_labels'	=> false,
					'nav_pos'		=> 'top', // [top|bottom]
				),
				$atts
			)
		);

		$data['header'] = WDev()->is_true( $data['header'] );
		$data['register'] = WDev()->is_true( $data['register'] );
		$data['show_note'] = WDev()->is_true( $data['show_note'] );
		$data['show_labels'] = WDev()->is_true( $data['show_labels'] );

		$view = MS_Factory::create( 'MS_View_Shortcode_Membership_Login' );
		$view->data = apply_filters( 'ms_view_shortcode_membership_login_data', $data, $this );

		return $view->to_html();
	}

	/**
	 * Membership logout shortcode callback function.
	 *
	 * @since 1.0.1
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function membership_logout( $atts ) {
		$data = apply_filters(
			'ms_controller_shortcode_membership_logout_atts',
			shortcode_atts(
				array(
					'holder'        => 'div',
					'holderclass'   => 'ms-logout-form',
					'redirect'      => filter_input( INPUT_GET, 'redirect_to', FILTER_VALIDATE_URL ),
				),
				$atts
			)
		);

		// The form attribute triggers the logout-link to be displayed.
		$data['form'] = 'logout';

		$view = MS_Factory::create( 'MS_View_Shortcode_Membership_Login' );
		$view->data = apply_filters( 'ms_view_shortcode_membership_logout_data', $data, $this );

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
					'id' => 0,
					'pay_button' => 1,
				),
				$atts,
				MS_Helper_Shortcode::SCODE_MS_INVOICE
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$data['post_id'] = $data['id'];
		}

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
			$view->data = apply_filters(
				'ms_view_shortcode_invoice_data',
				$data,
				$this
			);

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
		$content = sprintf(
			'<p class="%1$s">%2$s</p> ',
			'ms-alert-box ms-alert-success',
			$content
		);

		return apply_filters(
			'ms_controller_shortcode_ms_green_note',
			$content,
			$this
		);
	}

	/**
	 * Green text note shortcode callback function.
	 *
	 * @since 1.0.4.3
	 *
	 * @param mixed[] $atts Shortcode attributes.
	 */
	public function ms_red_note( $atts, $content = '' ) {
		$content = sprintf(
			'<p class="%1$s">%2$s</p> ',
			'ms-alert-box ms-alert-error',
			$content
		);

		return apply_filters(
			'ms_controller_shortcode_ms_red_note',
			$content,
			$this
		);
	}

	/**
	 * Replace shortcodes with empty value.
	 *
	 * All Shortcodes use this callback function when Content Protection is
	 * disabled.
	 *
	 * @since 1.0.4.3
	 */
	public function ms_no_value( $atts, $content = '' ) {
		static $Done = false;

		if ( $Done ) { return ''; }
		$Done = true;

		if ( MS_Model_Member::is_admin_user() ) {
			$content = sprintf(
				'<p class="ms-alert-box ms-alert-error ms-unprotected">%s<br /><br /><em>(%s)</em></p>',
				__(
					'Content Protection is disabled. Please enable the protection to see this shortcode.',
					MS_TEXT_DOMAIN
				),
				__(
					'This message is only displayed to Site Administrators',
					MS_TEXT_DOMAIN
				)
			);
		} else {
			$content = '';
		}

		return apply_filters(
			'ms_controller_shortcode_ms_no_value',
			$content,
			$this
		);
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