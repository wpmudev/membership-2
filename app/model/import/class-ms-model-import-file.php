<?php
/**
 * Base class for all import handlers.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Import_File extends MS_Model_Import {

	/**
	 * This function parses the Import source (i.e. an file-upload) and returns
	 * true in case the source data is valid. When returning true then the
	 * $source property of the model is set to the sanitized import source data.
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	public function prepare() {
		self::_message( 'preview', false );

		if ( empty( $_FILES ) || ! isset( $_FILES['upload'] ) ) {
			self::_message( 'error', __( 'No file was uploaded, please try again.', MS_TEXT_DOMAIN ) );
			return false;
		}

		$file = $_FILES['upload'];
		if ( empty( $file['name'] ) ) {
			self::_message( 'error', __( 'Please upload an export file.', MS_TEXT_DOMAIN ) );
			return false;
		}
		if ( empty( $file['size'] ) ) {
			self::_message( 'error', __( 'The uploaded file is empty, please try again.', MS_TEXT_DOMAIN ) );
			return false;
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::_message( 'error', __( 'Uploaded file not found, please try again.', MS_TEXT_DOMAIN ) );
			return false;
		}

		$content = file_get_contents( $file['tmp_name'] );
		try {
			$data = json_decode( $content );
		} catch( Exception $ex ) {
			$data = (object) array();
		}

		$data = $this->validate_object( $data );

		if ( empty( $data ) ) {
			self::_message( 'error', __( 'No valid export file uploaded, please try again.', MS_TEXT_DOMAIN ) );
			return false;
		}

		$this->source = $data;
		return true;
	}

	/**
	 * Returns true if the specific import-source is present and can be used
	 * for import.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function present() {
		return true;
	}

}
