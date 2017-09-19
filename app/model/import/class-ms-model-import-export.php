<?php
/**
 * Class that handles Export functions.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Import_Export extends MS_Model {

	/**
	 * Export Settings
	 */
	const PLUGIN_SETTINGS 		= 'plugin';
	const FULL_MEMBERSHIP_DATE 	= 'full';
	const MEMBERSHIP_ONLY 		= 'membership';
	const MEMBERS_ONLY 			= 'members';

	/**
	 * Export formats
	 */
	const JSON_EXPORT 			= 'json';
	const CSV_EXPORT 			= 'csv';
	const XML_EXPORT 			= 'xml';


	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @since  1.1.3
	 */
	public function process() {
		$type 				= $_POST['type'];
		$format 			= $_POST['format'];
		$supported_types 	= self::export_types();
		$supported_formats 	= self::export_formats();
		$supported_types 	= array_keys( $supported_types );
		$supported_formats 	= array_keys( $supported_formats );
		if ( in_array( $type, $supported_types ) && in_array( $format, $supported_formats ) ) {
			switch ( $type ) {
				case self::PLUGIN_SETTINGS :
					$handler = MS_Factory::create( 'MS_Model_Import_Type_Settings' );
					$handler->process();
					break;
			}
		}
		
	}

	/**
	 * Export types
	 *
	 * @since 1.1.3
	 *
	 * @return Array
	 */
	public static function export_types() {
		return array(
			self::PLUGIN_SETTINGS 		=> __( 'Plugin Settings (Note that this is not a full backup of the plugin settings)', 'membership2' ),
			self::FULL_MEMBERSHIP_DATE 	=> __( 'Full Membership Data (Members and Memberships)', 'membership2' ),
			self::MEMBERSHIP_ONLY 		=> __( 'Memberships Only', 'membership2' ),
			self::MEMBERS_ONLY 			=> __( 'Members Only', 'membership2' )
		);
	}

	/**
	 * Supported Export types
	 *
	 * @since 1.1.3
	 *
	 * @return Array
	 */
	public static function export_formats() {
		return array(
			self::JSON_EXPORT 	=> __( 'JSON', 'membership2' ),
			self::XML_EXPORT 	=> __( 'XML', 'membership2' ),
			self::CSV_EXPORT 	=> __( 'CSV', 'membership2' ),
		);
	}

}
