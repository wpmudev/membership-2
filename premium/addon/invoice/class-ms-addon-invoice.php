<?php
/**
 * Add-On controller for: Addon advanced invoice
 * Allow for custom invoice prefixes for different gateways
 *
 * @since 1.1.3
 *
 * @package Membership2
 * @subpackage Addon
 */
class MS_Addon_Invoice extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.4
	 */
	const ID = 'addon_invoice';

	/**
	 * Invoice generation type
	 *
	 * @since  1.1.2
	 *
	 * @var string $sequence_type
	 */
	const DEFAULT_SEQUENCE 		= 'sequence_type_default'; //default
	const PROGRESSIVE_SEQUENCE 	= 'sequence_type_progressive'; //sequence like 1,2,3,4
	const CUSTOM_SEQUENCE 		= 'sequence_type_custom'; //custom allows for prefix with or without sequence

	/**
	 * Plugin Settings
	 *
	 * @since  1.0.4
	 */
	protected $plugin_settings = null;

	 /**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.4
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

    /**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.4
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.4
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->plugin_settings = MS_Factory::load( 'MS_Model_Settings' );
        }
	}

	/**
	 * Invoice Se
	 */
	public static function sequence_types() {
		return apply_filters( 'ms_addon_invoice_sequence_types', array(
			self::DEFAULT_SEQUENCE 		=> __( 'Basic invoice ID generation (default)', 'membership2' ),
			self::PROGRESSIVE_SEQUENCE 	=> __( 'Progressive invoice ID generation ( e.g. 1,2,3,4 )', 'membership2' ),
			self::CUSTOM_SEQUENCE 		=> __( 'Custom invoice ID generation for all or each gateway ( e.g. MINE_1, PP_2, STRIPE_3 )', 'membership2' ),
		) );
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.4
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$settings 		= MS_Factory::load( 'MS_Model_Settings' );

		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Additional Invoice Settings', 'membership2' ),
			'description' 	=> __( 'Take full control of your invoices', 'membership2' ),
			'footer' 		=> sprintf( '<i class="dashicons dashicons dashicons-admin-settings"></i> %s <i class="dashicons dashicons dashicons-info"></i>', __( 'Options available', 'membership2' ) ),
			'icon' 			=> 'wpmui-fa wpmui-fa-credit-card',
			'class' 		=> 'ms-options',
			'details' 		=> array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => __( 'Additional invoice settings for better invoices', 'membership2' ),
				),
				array(
					'id' 			=> 'sequence_type',
					'type' 			=> MS_Helper_Html::INPUT_TYPE_RADIO,
					'title' 		=> __( 'Sequence type', 'membership2' ),
					'desc' 			=> __( 'Manage how your invoice ids are generated', 'membership2' ),
					'value' 		=> $settings->invoice['sequence_type'],
					'field_options' => self::sequence_types(),
					'data_ms' 	=> array(
						'field' 	=> 'sequence_type',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					),
				),
			)
		);
		return $list;
	}
}

?>