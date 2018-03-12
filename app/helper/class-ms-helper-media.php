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
                "Deny from all" .PHP_EOL,
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
                $ht_config 			= file( $ht_file );
                $contains_search 	= array_diff( $rules, $ht_config );
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
	
	/**
	 * Get the active server
	 *
	 * @since 1.0.4
	 *
	 * @return String
	 */
	public static function get_server() {
		global $is_nginx, $is_IIS, $is_iis7;
		$active_server = 'apache';
		if ( $is_nginx ) {
			$active_server = 'nginx';
		} else if ( $is_IIS ) {
			$active_server = 'iis';
		} else if ( $is_iis7 ) {
			$active_server = 'iis-7';
		}
		return $active_server;
	}

	/**
	 * List server types
	 *
	 * @return array
	 */
	public static function server_types() {
		return apply_filters( 'ms_helper_media_server_types', array(
			'apache'    => 'Apache',
			'litespeed' => 'LiteSpeed',
			'nginx'     => 'NGINX',
			'iis'       => 'IIS',
			'iis-7'     => 'IIS 7'
		) );
	}

	/**
	 * Get the membership directory in the uploads directory
	 *
	 * @since 1.1.3
	 *
	 * @return String
	 */
	public static function get_membership_dir() {
		$upload_dir     = wp_upload_dir();
		$uploads_dir    = $upload_dir['basedir'];
		$ms_dir    		= $uploads_dir . DIRECTORY_SEPARATOR . 'membership2';
		if ( ! is_dir( $ms_dir ) ) {
			wp_mkdir_p( $ms_dir );
		}
		if ( ! is_file( $ms_dir . DIRECTORY_SEPARATOR . 'index.php' ) ) {
			//create a blank index file
			file_put_contents( $ms_dir . DIRECTORY_SEPARATOR . 'index.php', '' );
		}
		return $ms_dir;
	}

	/**
	 * Create CSV file
	 *
	 * @param String $filepath - the file path
	 * @param Array $data - the data
	 * @param Array $header - header data
	 *
	 * @return bool - success true
	 */
	public static function create_csv( $filepath, $data, $header = array() ) {
		$handle 	= fopen( $filepath, 'w' );
		$has_header = true; 
		if ( $handle ) {
			if ( empty( $header ) ) { 
				$has_header = false; 
				reset( $data ); 
				$line = current( $data ); 
				if ( !empty( $line ) ) { 
					reset( $line ); 
					$first = current( $line ); 
					if ( substr( $first, 0, 2 ) == 'ID' && !preg_match( '/["\\s,]/', $first ) ) {
						array_shift( $data ); 
						array_shift( $line ); 
						if ( empty( $line ) ) { 
							fwrite( $handle, "\"{$first}\"\r\n" ); 
						} else { 
							fwrite( $handle, "\"{$first}\"," ); 
							fputcsv( $handle, $line ); 
							fseek( $handle, -1, SEEK_CUR ); 
							fwrite( $handle, "\r\n" ); 
						} 
					} 
				} 
			} else {
				reset( $header ); 
				$first = current( $header ); 
				if ( substr( $first, 0, 2 ) == 'ID' && !preg_match( '/["\\s,]/', $first ) ) {
					array_shift( $header ); 
					if ( empty( $header ) ) { 
						$show_header = false; 
						fwrite( $handle, "\"{$first}\"\r\n" ); 
					} else { 
						fwrite( $handle, "\"{$first}\"," ); 
					} 
				}
			}
			if ( $has_header ) { 
				fputcsv( $handle, $header ); 
				fseek( $handle, -1, SEEK_CUR ); 
				fwrite( $handle, "\r\n" ); 
			} 
			foreach ( $data as $line ) { 
				fputcsv( $handle, $line ); 
				fseek( $handle, -1, SEEK_CUR ); 
				fwrite( $handle, "\r\n" ); 
			} 
			fclose($handle);
			return true;
		}
		return false;
	}

	/**
	 * Generate XML
	 *
	 * @param SimpleXMLElement $xml - the child node
	 * @param Array $data - the data
	 */
	public static function generate_xml( &$xml, $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( !is_numeric( $key ) ) {
						$subnode = $xml->addChild( "$key" );
						self::generate_xml( $subnode, $value );
					} else {
						self::generate_xml( $xml, $value );
					}
				} else {
					$xml->addChild( $key, $value );
				}
			}
		} else {
			$xml->addChild( "$data" );
		}
	}

}
?>