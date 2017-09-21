<?php
/**
 * Class that handles Members Export functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Export_Members extends MS_Model_Export_Base {

	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @param String $format - export format
	 *
	 * @since  1.1.3
	 */
	public function process( $format ) {
		$data 		= $this->export_base( 'members' ); 
		$members 	= MS_Model_Member::get_members();
		foreach ( $members as $member ) {
			if ( ! $member->is_member ) { continue; }
			$data[] = $this->export_member( $member );
		}
		$milliseconds 	= round( microtime( true ) * 1000 );
		$file_name 		= $milliseconds . '_membership2-members';
		switch ( $format ) {
			case MS_Model_Export::JSON_EXPORT :
				lib3()->net->file_download( json_encode( $data ), $file_name . '.json' );
			break;

			case MS_Model_Export::XML_EXPORT :
				$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><members></members>");
				foreach ( $data as $member ) {
					$node = $xml->addChild( 'member' );
					MS_Helper_Media::generate_xml( $node, $member );
				}
				lib3()->net->file_download( $xml->asXML(), $file_name . '.xml' );
			break;
		}
	}
}
?>