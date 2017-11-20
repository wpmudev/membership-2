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

		$server = MS_Helper_Media::get_server();
		if ( isset( $settings->downloads['application_server'] ) && !empty( $settings->downloads['application_server'] ) ) {
			$server = $settings->downloads['application_server'];
		}
		$upload_dir     = wp_upload_dir();
        $uploads_dir    = $upload_dir['basedir'];
		//Wp-content dir used for nginx
		if ( DIRECTORY_SEPARATOR == '\\' ) {
			//Windows
			$wp_content     = str_replace( ABSPATH, '', $uploads_dir );
		} else {
			$wp_content     = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $uploads_dir );
		}
		

		$fields = array(
			'wp_content_dir' => array(
				'id' 	=> 'wp_content_dir',
				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $wp_content,
			),

			'select_server' => array(
				'id' 			=> 'application_server',
				'type' 			=> MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' 		=> __( 'Application Server', 'membership2' ),
				'value' 		=> $server,
				'field_options' => MS_Helper_Media::server_types(),
				'class' 		=> 'ms-select',
				'data_ms' 		=> array(
					'field' 	=> 'application_server',
					'action' 	=> MS_Controller_Settings::AJAX_ACTION_TOGGLE_PROTECTION_FILE,
					'_wpnonce' 	=> true, // Nonce will be generated from 'action'
				),
			),

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
			)
		);

		$apache_settings = array(
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
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			<div style="display:<?php echo ( $server != 'apache' ) ? 'none' : 'block'; ?>"  class="application-servers application-server-apache">
				<?php MS_Helper_Html::html_element( $apache_settings['regenerate_htaccess'] ); ?>
			</div>
			<div style="display:<?php echo ( $server != 'litespeed' ) ? 'none' : 'block'; ?>" class="application-servers application-server-litespeed">
				<?php MS_Helper_Html::html_element( $apache_settings['regenerate_htaccess'] ); ?>
			</div>
			<div style="display:<?php echo ( $server != 'nginx' ) ? 'none' : 'block'; ?>" class="application-servers application-server-nginx">
				<?php
				$rules = "
				# Deny direct access to media files in the /wp-content/uploads/ directory (including sub-folders)
				location ^~ ^/$wp_content {
				  deny all;
				}
				";
				?>
				<pre style="white-space: pre-line;">
				## Membership 2 - Media Protection ##
				<?php echo esc_html( $rules ); ?>
				<span class="application-servers-nginx-extra-instructions">
				<?php
				$extensions = implode( "|", $direct_access );
				$rules = "location ~* ^$wp_content/.*\.($extensions)$ {
					allow all;
				}";
				echo esc_html( $rules );
				?>
				</span>
				## Membership 2 - End ##
				</pre>
			</div>
			<div stle="display:<?php echo ( $server != 'iis' ) ? 'none' : 'block'; ?>" class="application-servers application-server-iis">

			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	

}