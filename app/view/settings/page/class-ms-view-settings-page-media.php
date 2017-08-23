<?php

/**
 * Advanced Media Settings
 *
 * @since 1.0.4
 */
class MS_View_Settings_Page_Media extends MS_View_Settings_Edit {

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
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		$direct_access = array( 'jpg', 'jpeg', 'png', 'gif', 'mp3', 'ogg' );
		if ( isset( $settings->downloads['direct_access'] ) ) {
			$direct_access = $settings->downloads['direct_access'];
		}

		$fields = array(
			'direct_access' => array(
				'id' 	=> 'direct_access',
				'type' 	=> MS_Helper_Html::INPUT_TYPE_TEXT,
				'desc' 	=> __( 'Only allow direct access to the following file extensions.', 'membership2' ),
				'value' => implode( ",", $direct_access ),
				'class' => 'ms-text-large',
				'data_ms' => array(
					'field' 	=> 'direct_access',
					'action' 	=> MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
					'_wpnonce' 	=> true, // Nonce will be generated from 'action'
				),
			),

			'regenerate_htaccess' => array(
				'id' 	=> 'regenerate_htaccess',
				'type' 	=> MS_Helper_Html::INPUT_TYPE_BUTTON,
				'title' => __( 'Regenerate htaccess file', 'membership2' ),
				'desc' 	=> __( 'This will update the Membership rules in the htaccess file in the uploads directory.', 'membership2' ),
				'value' => __( 'Update htaccess', 'membership2' ),
				'data_ms' => array(
					'field' 	=> 'regenerate_htaccess',
					'action' 	=> MS_Controller_Settings::AJAX_ACTION_TOGGLE_PROTECTION_FILE,
					'_wpnonce' 	=> true, // Nonce will be generated from 'action'
				),
			),
		);
		

		ob_start();
		?>
		<div class="cf">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Protect uploaded files', 'membership2' ),
					'desc' => __( 'Prevent direct access to your uploaded media files', 'membership2' ),
				)
			);
			?>
			<?php MS_Helper_Html::html_element( $fields['direct_access'] ); ?>
			<?php MS_Helper_Html::html_element( $fields['regenerate_htaccess'] ); ?>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	

}