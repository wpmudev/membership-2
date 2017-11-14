<?php
/**
 * Invoice Settings Page
 *
 * @since 1.1.3
 */
class MS_View_Settings_Page_Invoice extends MS_View_Settings_Edit{

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * HTML contains the list of advanced media settings
	 *
	 * @since  1.0.4
	 *
	 * @return string
	 */
	public function to_html() {
		$settings 	= MS_Factory::load( 'MS_Model_Settings' );
		$fields 	= $this->prepare_fields();
		ob_start();
		?>
		<div id="ms-invoice-settings-wrapper">
			<div class="ms-list-table-wrapper">
				<?php
				MS_Helper_Html::settings_tab_header(
					array(
						'title' => __( 'Advanced Invoice Settings', 'membership2' )
					)
				);
				?>
				<div class="space">
					<?php MS_Helper_Html::html_element( $fields['sequence_type'] ); ?>
				</div>
				<?php
				$sequence_types = MS_Addon_Invoice::sequence_types();
				foreach ( $sequence_types as $key => $value ) {
					$display = 'none;';
					if ( $settings->invoice['sequence_type'] === $key  ) {
						$display = 'block;';
					}
					?>
					<div class="space invoice-types" style="display:<?php echo $display;?>">
						<?php echo $value; ?>
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

	protected function prepare_fields() {
		$settings 	= MS_Factory::load( 'MS_Model_Settings' );
		$fields 	= array(
			'sequence_type' => array(
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
		);

		return $fields;
	}
}
?>