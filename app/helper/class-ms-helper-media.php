<?php
/**
 * Helper class for media functions
 *
 * @since  1.0.4
 * @package Membership2
 * @subpackage Helper
 */
class MS_Helper_Media extends MS_Helper {

    /**
	 * Write htacess rule
	 *
	 * @param Array $files - the current file extensions array
	 *
	 * @since 1.0.4
	 */
	public static function write_htaccess_rule( $files = array() ) {
		if ( !empty( $files ) && is_array( $files ) ) {
			$new_rule = array(
				PHP_EOL. "## Membership 2 - Media Protection ##" . PHP_EOL,
				"Options -Indexes" .PHP_EOL,
				"<FilesMatch '\.(" . implode( '|', $files ) . ")$'>" .PHP_EOL .
				"Order Allow,Deny" .PHP_EOL . 
				"Allow from all" .PHP_EOL .
				"</FilesMatch>" .PHP_EOL,
				"## Membership 2 - End ##" . PHP_EOL
			);
			self::write_to_uploads_htaccess( $new_rule );
		}
	}

    /**
     * Clear htaccess
     *
     * @since 1.0.4
     */
    public static function clear_htaccess() {
        $upload_dir     = wp_upload_dir();
        $uploads_dir    = $upload_dir['basedir'];
        $ht_file        = $uploads_dir . DIRECTORY_SEPARATOR . '.htaccess';
        if ( file_exists( $ht_file ) ) {
            if ( ! is_writeable( $ht_file ) ) {
                return new WP_Error( 'not_writeable', __( 'The .htaccess file is not writeable', 'membership2' ) );
            } else {
                $ht_content = file_get_contents( $ht_file );
                if ( !empty( $ht_content) ) {
                    preg_match_all('/## Membership 2(.*?)## Membership 2 - End ##/s', $ht_content, $matches);
                    if ( is_array( $matches ) && count( $matches ) > 0 ) {
                        $ht_content = str_replace( implode( '', $matches[0] ), '', $ht_content );
                        $ht_content = trim( $ht_content );
                        @file_put_contents( $ht_file, $ht_content );
                    }
                }
            }
        }
        return true;
    }

    /**
     * Write to .htaccess file in the uploads directory
     *
     * @param Array $rules - htaccess rules
     *
     * @since 1.0.4
     */
    public static function write_to_uploads_htaccess( $rules = array() ) {
        $upload_dir     = wp_upload_dir();
        $uploads_dir    = $upload_dir['basedir'];
        $ht_file        = $uploads_dir . DIRECTORY_SEPARATOR . '.htaccess';
        if ( !empty( $rules ) ) {
            if ( file_exists( $ht_file ) ) {
                $ht_config = file( $ht_file );
                $contains_search = array_diff( $rules, $ht_config );
                if ( count( $contains_search ) == 0 || ( count( $contains_search ) == count( $rules ) ) ) {
                    $ht_config = array_merge( $ht_config, array( implode( '', $rules ) ) );
                    @file_put_contents( $ht_file, implode( '', $ht_config ) );
                }
            } else if ( wp_is_writable( $uploads_dir ) ) {
                 @file_put_contents( $ht_file, implode( '', $rules ) );
            }
        }
        return true;
    }
}
?>