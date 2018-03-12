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
	const COMBINED				= 'sequence_type_combined'; //combined sequence of progressive and custom

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
			$this->add_filter(
				'ms_model_invoice_the_number',
				'invoice_number',
				10, 2
			);
        }
	}

	/**
	 * Invoice Se
	 */
	public static function sequence_types() {
		return apply_filters( 'ms_addon_invoice_sequence_types', array(
			self::DEFAULT_SEQUENCE 		=> __( 'Basic invoice ID generation (default)', 'membership2' ),
			self::PROGRESSIVE_SEQUENCE 	=> __( 'Progressive invoice ID generation', 'membership2' ),
			self::CUSTOM_SEQUENCE 		=> __( 'Custom invoice ID generation for all gateways', 'membership2' ),
			self::COMBINED				=> __( 'Combined generation that combines custom prefix and progressive invoice ID settings', 'membership2' ),
		) );
	}

	/**
	 * Set the invoice number depending on the addon settings
	 * 
	 * @param string $default_number - default invoice number
	 * @param MS_Model_Invoice $invoice - the current invoice
	 * 
	 * @return string
	 */
	public function invoice_number( $default_number, $invoice ) {
		switch ( $this->plugin_settings->invoice['sequence_type'] ) {
			case self::PROGRESSIVE_SEQUENCE :
				if ( $invoice->custom_invoice_id > 0 ) {
					$default_number = $invoice->custom_invoice_id;
				}
			break;

			case self::CUSTOM_SEQUENCE :
				$prefix 		= $this->plugin_settings->invoice['invoice_prefix'];
				$default_number = substr( $default_number, 1 );
				$default_number = $prefix . '' . $default_number;
			break;

			case self::COMBINED :
				$prefix 		= $this->plugin_settings->invoice['invoice_prefix'];
				if ( $invoice->custom_invoice_id > 0 ) {
					$default_number = $invoice->custom_invoice_id;
				} else {
					$default_number = substr( $default_number, 1 );
				}
				$default_number = $prefix . '' . $default_number;
				
			break;
		}
		return apply_filters( 'ms_addon_invoice_invoice_number', $default_number, $invoice );
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.4
	 * 
	 * @param  array $list The Add-Ons list.
	 * 
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$settings 		= MS_Factory::load( 'MS_Model_Settings' );
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Additional Invoice Settings', 'membership2' ),
			'description' 	=> __( 'More control for your invoices', 'membership2' ),
			'icon' 			=> 'wpmui-fa wpmui-fa-credit-card',
			'footer' 		=> sprintf( '<i class="dashicons dashicons dashicons-admin-settings"></i> %s', __( 'Options available', 'membership2' ) ),
			'class' 		=> 'ms-options',
			'details' 		=> array(
				array(
					'id' 			=> 'invoice_sequence_type',
					'type' 			=> MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' 		=> __( 'Select invoice number sequence', 'membership2' ),
					'value' 		=> $settings->invoice['sequence_type'],
					'field_options' => MS_Addon_Invoice::sequence_types(),
					'class' 		=> 'ms-select',
					'ajax_data' 	=> array(
						'field' 	=> 'sequence_type',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					)
				),
				array(
					'type' 	=> MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => $this->render_settings_html( $settings )
				)
			)
		);
		return $list;
	}

	protected function render_settings_html( $settings ) {
		$total_invoices = MS_Model_Invoice::get_invoice_count();
		ob_start();
		?>
		<div id="ms-invoice-settings-wrapper">
			<div class="ms-list-table-wrapper">
				<?php
				$sequence_types = self::sequence_types();
				foreach ( $sequence_types as $key => $value ) {
					$callback_name 		= 'render_' . $key;
					$render_callback 	= apply_filters(
						'ms_addon_invoice_render_settings_html_callback',
						array( $this, $callback_name ),
						$key,
						$value,
						$this->data
					);
					$html 				= call_user_func( $render_callback, $settings, $total_invoices );
					$display 			= 'none;';
					if ( $settings->invoice['sequence_type'] === $key  ) {
						$display = 'block;';
					}
					?>
					<div class="space invoice-types" style="display:<?php echo $display;?>" id="<?php echo $key; ?>">
						<?php echo $html; ?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Render default sequence type
	 * 
	 * @param MS_Model_Settings $settings
	 * 
	 * @since 1.1.2
	 * 
	 * @return string
	 */
	public function render_sequence_type_default( $settings, $total_invoices ) {
		return __( "Invoice ID's will be generated in the default order", "membership2" );
	}

	/**
	 * Render progressive sequence type
	 * 
	 * @param MS_Model_Settings $settings
	 * 
	 * @since 1.1.2
	 * 
	 * @return string
	 */
	public function render_sequence_type_progressive( $settings, $total_invoices ) {
		$invoices_set = (bool) MS_Factory::get_option( 'ms_addon_invoice_set_invoice_numeric_id' );
		if ( $total_invoices > 0 && !$invoices_set ) {
			ob_start();
			?>
			<div class="ms-common-prefix">
				<?php MS_Helper_Html::html_element( array(
					'id' 		=> 'invoice_id_update',
					'type' 		=> MS_Helper_Html::INPUT_TYPE_BUTTON,
					'desc' 		=> sprintf( __( "Update ID's of current (%d) invoices.", 'membership2' ), $total_invoices ),
					'value' 	=> __( 'Update', 'membership2' ),
					'class' 	=> 'button-primary',
					'data_ms' 	=> array(
						'field' 	=> 'invoice_id_update',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_GENERATE_INVOICE_ID,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					)
				) );
				MS_Helper_Html::html_element( array(
					'type' 	=> MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => __( "Invoice ID's will be generated in a progressive order. This will depend on the total number of invoices you have in your installation", "membership2" )
				) ); ?>

			</div>
			<?php
			$html = ob_get_clean();
			return $html;
		} else {
			return __( "Invoice ID's will be generated in a progressive order. This will depend on the total number of invoices you have in your installation", "membership2" );
		}
	}

	/**
	 * Render custom sequence type
	 * 
	 * @param MS_Model_Settings $settings
	 * 
	 * @since 1.1.2
	 * 
	 * @return string
	 */
	public function render_sequence_type_custom( $settings, $total_invoices ) {
		ob_start();
		?>
		<div class="ms-common-prefix">
			<?php MS_Helper_Html::html_element( array(
				'id' 	=> 'invoice_prefix',
				'type' 	=> MS_Helper_Html::INPUT_TYPE_TEXT,
				'desc' 	=> __( 'Invoice prefix to apply to all invoice', 'membership2' ),
				'value' => $settings->invoice['invoice_prefix'],
				'class' => 'ms-text-large',
				'data_ms' => array(
					'field' 	=> 'invoice_prefix',
					'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
					'_wpnonce' 	=> true, // Nonce will be generated from 'action'
				),
			) ); ?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}


	/**
	 * Render combined sequence type
	 * 
	 * @param MS_Model_Settings $settings
	 * 
	 * @since 1.1.2
	 * 
	 * @return string
	 */
	public function render_sequence_type_combined( $settings, $total_invoices ) {
		ob_start();
		?>
		<div class="ms-common-prefix">
			<?php MS_Helper_Html::html_element( array(
				'id' 	=> 'invoice_prefix',
				'type' 	=> MS_Helper_Html::INPUT_TYPE_TEXT,
				'desc' 	=> __( 'Invoice prefix to apply to all invoice. This will be combined with the progressive sequence', 'membership2' ),
				'value' => $settings->invoice['invoice_prefix'],
				'class' => 'ms-text-large',
				'data_ms' => array(
					'field' 	=> 'invoice_prefix',
					'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
					'_wpnonce' 	=> true, // Nonce will be generated from 'action'
				),
			) ); ?>
		</div>
		<?php
		$invoices_set = (bool) MS_Factory::get_option( 'ms_addon_invoice_set_invoice_numeric_id' );
		if ( $total_invoices > 0 && !$invoices_set ) {
			?>
			<div class="ms-common-prefix">
				<?php MS_Helper_Html::html_element( array(
					'id' 		=> 'invoice_id_update',
					'type' 		=> MS_Helper_Html::INPUT_TYPE_BUTTON,
					'desc' 		=> sprintf( __( "Update ID's of current (%d) invoices.", 'membership2' ), $total_invoices ),
					'value' 	=> __( 'Update', 'membership2' ),
					'class' 	=> 'button-primary',
					'data_ms' 	=> array(
						'field' 	=> 'invoice_id_update',
						'action' 	=> MS_Controller_Settings::AJAX_ACTION_GENERATE_INVOICE_ID,
						'_wpnonce' 	=> true, // Nonce will be generated from 'action'
					)
				) );
				MS_Helper_Html::html_element( array(
					'type' 	=> MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => __( "Invoice ID's will be generated in a progressive order. This will depend on the total number of invoices you have in your installation", "membership2" )
				) ); ?>

			</div>
			<?php
		}
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Set numeric ids of invoices
	 */
	public static function set_invoice_numeric_id() {
		$invoices_set = (bool) MS_Factory::get_option( 'ms_addon_invoice_set_invoice_numeric_id' );
		if ( !$invoices_set ) {
			$args = array(
				'posts_per_page' 	=> -1,
				'post_status' 		=> 'any',
				'fields' 			=> 'ids',
				'order' 			=> 'DESC',
				'orderby' 			=> 'ID',
			);
			$invoices = MS_Model_Invoice::get_invoices( $args, false );
			$count = 1;
			foreach ( $invoices as $invoice ) {
				if ( $invoice->custom_invoice_id <= 0 ) {
					$invoice->custom_invoice_id = $count;
					$invoice->save();
					$count++;
				}
			}
			MS_Factory::update_option( 'ms_addon_invoice_set_invoice_numeric_id', true );
		}
	}
}

?>