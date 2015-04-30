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
 * Manual Gateway.
 *
 * Process manual payments (Eg. check, bank transfer)
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Gateway_Manual extends MS_Gateway {

	const ID = 'manual';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Gateway ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id = self::ID;

	/**
	 * Gateway name.
	 *
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * Gateway description.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	protected $description = '';

	/**
	 * Gateway active status.
	 *
	 * @since 1.0.0
	 * @var string $active
	 */
	protected $active = false;

	/**
	 * Gateway allow Pro rating.
	 *
	 * @todo To be released in further versions.
	 * @since 1.0.0
	 * @var bool $pro_rate
	 */
	protected $pro_rate = true;

	/**
	 * Manual payment indicator.
	 *
	 * If the gateway does not allow automatic reccuring billing.
	 *
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;

	/**
	 * Payment information for customer.
	 *
	 * The payment procedures like bank account, agency, etc.
	 *
	 * @since 1.0.0
	 * @var string $payment_info
	 */
	protected $payment_info;


	/**
	 * Hook to show payment info.
	 * This is called by the MS_Factory
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->name = __( 'Manual Gateway', MS_TEXT_DOMAIN );
		$this->description = __( '(Bank orders, cash, etc)', MS_TEXT_DOMAIN );

		if ( $this->active ) {
			$this->add_action(
				'ms_controller_gateway_purchase_info_content',
				'purchase_info_content'
			);
		}
	}

	/**
	 * Show manual purchase/payment information.
	 *
	 * Returns a default messsage if gateway is not configured.
	 *
	 * * Hooks Actions: *
	 * * ms_controller_gateway_purchase_info_content
	 *
	 * @since 1.0.0
	 * @return string The payment info.
	 */
	public function purchase_info_content() {
		do_action(
			'ms_gateway_manual_purchase_info_content_before',
			$this
		);

		if ( empty( $this->payment_info ) ) {
			$link = admin_url(
				sprintf(
					'admin.php?page=%s&tab=payment',
					MS_Controller_Plugin::MENU_SLUG . '-settings'
				)
			);
			ob_start();
			?>
				<?php _e( 'This is only an example of manual payment gateway instructions', MS_TEXT_DOMAIN ); ?>
				<br />
				<?php printf(
					'%s <a href="%s">%s</a>',
					__( 'Edit it', MS_TEXT_DOMAIN ),
					$link,
					__( 'here.', MS_TEXT_DOMAIN )
				); ?>
				<br /><br />
				<?php _e( 'Name: Example name.', MS_TEXT_DOMAIN ); ?>
				<br />
				<?php _e( 'Bank: Example bank.', MS_TEXT_DOMAIN ); ?>
				<br />
				<?php _e( 'Bank account: Example bank account 1234.', MS_TEXT_DOMAIN ); ?>
				<br />
			<?php
			$this->payment_info = ob_get_clean();
		}

		if ( ! empty( $_POST['ms_relationship_id'] ) ) {
			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$_POST['ms_relationship_id']
			);
			$invoice = $subscription->get_current_invoice();
			$this->payment_info .= sprintf(
				'<br />%s: %s%s',
				__( 'Total value', MS_TEXT_DOMAIN ),
				$invoice->currency,
				$invoice->total
			);
		}

		return apply_filters(
			'ms_gateway_manual_purchase_info_content',
			wpautop( $this->payment_info )
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since 1.0.0
	 * @return boolean True if configured.
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'payment_info' );

		foreach ( $required as $field ) {
			if ( empty( $this->$field ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_manual_is_configured',
			$is_configured
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'payment_info':
					$this->$property = wp_kses_post( $value );
					break;

				default:
					parent::__set( $property, $value );
					break;
			}
		}

		do_action(
			'ms_gateway_manual__set_after',
			$property,
			$value,
			$this
		);
	}

}