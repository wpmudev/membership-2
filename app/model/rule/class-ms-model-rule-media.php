<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/


class MS_Model_Rule_Media extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_MEDIA;
	
	const PROTECTION_TYPE_DISABLED = 'protection_type_disabled';
	
	const PROTECTION_TYPE_BASIC = 'protection_type_basic';
	
	const PROTECTION_TYPE_COMPLETE = 'protection_type_complete';
	
	const PROTECTION_TYPE_HYBRID = 'protection_type_hybrid';
	
	const FILE_PROTECTION_PREFIX = 'ms_';
	
	const FILE_PROTECTION_INCREMENT = 2771;
	
	protected $ms_relationship;
	
	/**
	 * Get the protection type available.
	 * 
	 * @since 4.0.0
	 *
	 * @access public static
	 * @return array The protection type => Description.
	 */
	public static function get_protection_types() {
		return apply_filters( 'ms_model_rule_media_get_protection_types', array(
				self::PROTECTION_TYPE_DISABLED => __( 'Disable protection', MS_TEXT_DOMAIN ),
				self::PROTECTION_TYPE_BASIC => __( 'Basic protection', MS_TEXT_DOMAIN ),
				self::PROTECTION_TYPE_COMPLETE => __( 'Complete protection', MS_TEXT_DOMAIN ),
				self::PROTECTION_TYPE_HYBRID => __( 'Hybrid protection', MS_TEXT_DOMAIN ),
		) );
	}
		
	/**
	 * Setup filter hook to protect media file.
	 *  
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->ms_relationship = $membership_relationship;
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			$this->add_filter( 'the_content', 'protect_download_content' );
			$this->add_action( 'pre_get_posts', 'handle_download_protection', 3 );
		}
	}
	
	/**
	 * Protect media file.
	 * 
	 * Search content and mask media filename and path.
	 * 
	 * @since 4.0.0
	 *
	 * @filter the_content  
	 * @access public
	 * @return string The content with masked media url.
	 */
	public function protect_download_content( $the_content ) {
		$download_settings = MS_Plugin::instance()->settings->downloads;
		
		if( self::PROTECTION_TYPE_DISABLED == $download_settings['protection_type'] ) {
			return $the_content;
		}
		
		$upload_dir = wp_upload_dir();
		$original_url = trailingslashit( $upload_dir['baseurl'] );
		$new_path = trailingslashit( trailingslashit( get_option( 'home' ) ) . $download_settings['masked_url'] );
		
		/**
		 * Find all the urls in the post and then we'll check if they are protected
		 * Regular expression from http://blog.mattheworiordan.com/post/13174566389/url-regular-expression-for-links-with-or-without-the 
		 */
		$url_exp = '/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)/';
		
		$matches = array();
		if( preg_match_all( $url_exp, $the_content, $matches ) ) {
			$home = get_option( 'home' );
			if( ! empty( $matches ) && ! empty( $matches[2] ) ) {
				foreach( (array) $matches[0] as $key => $domain ) {
					if( strpos( $domain, untrailingslashit( $home ) ) === 0 ) {
						$found_local = $key;
						$file = basename( $matches[4][ $found_local ] );
		
						extract( $this->extract_file_info( $file ) );

						$post_id = $this->get_attachment_id( $filename );

						if( ! empty( $post_id ) ) {
							/**
							 * We have a protected file - so we'll mask it
							 */
							switch( $download_settings['protection_type'] ) {
								case self::PROTECTION_TYPE_COMPLETE:	
									$protected_filename = self::FILE_PROTECTION_PREFIX . ( $post_id + (int) self::FILE_PROTECTION_INCREMENT) . $size_extension;
									$protected_filename .= "." . pathinfo( $filename, PATHINFO_EXTENSION );
		
									$the_content = str_replace( $matches[0][$found_local], $new_path . $protected_filename, $the_content );
									break;
								case self::PROTECTION_TYPE_HYBRID:
									$protected_filename = self::FILE_PROTECTION_PREFIX . ($post_id + (int) self::FILE_PROTECTION_INCREMENT) . $size_extension;
									$protected_filename .= "." . pathinfo($filename, PATHINFO_EXTENSION);
		
									$the_content = str_replace( $matches[0][ $found_local ], $new_path . "?ms_file=" . $protected_filename, $the_content );
									break;
								case self::PROTECTION_TYPE_BASIC:
								default:			
									$the_content = str_replace( $matches[0][ $found_local ], str_replace( $original_url, $new_path, $matches[0][ $found_local ] ), $the_content );
								break;
							}
							
						}
					}
				}
			}
		}
		
		return $the_content;
	}
	
	/**
	 * Extract filename and size extension info.
	 * 
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $file The filename to extract info from.
	 */
	public function extract_file_info( $file ) {
		// See if the filename has a size extension and if so, strip it out
		$filename_exp = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
		$filematch = array();
		if( preg_match( $filename_exp, $file, $filematch ) ) {
			// We have an image with an image size attached
			$filename = $filematch[1] . "." . $filematch[3];
			$size_extension = '-' . $filematch[2];
		} 
		else {
			$filename = $file;
			$size_extension = '';
		}
		return array( 'filename' => $filename, 'size_extension' => $size_extension );
	}
	
	/**
	 * Get attachment post_id using the filename.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param string $filename The filename to obtain the post_id.
	 */
	public function get_attachment_id( $filename ) {
		$args = array(
			'posts_per_page' => 1,
			'post_type'   => 'attachment',
			'post_status' => 'any',
				'meta_query' => array(
						'relation' => 'OR',
						array(
								'key' => '_wp_attached_file',
								'value' => $filename,
								'compare' => 'LIKE'
						),
						array(
								'key' => '_wp_attachment_metadata',
								'value' => $filename,
								'compare' => 'LIKE'
						)
				)
		);
		$query = new WP_Query( $args );
		$post = $query->get_posts();

		if( ! empty( $post[0] ) ) {
			return $post[0]->ID;
		}
		return null;		
	}
	
	/**
	 * Handle protected media access.
	 * 
	 * Search for masked file and show the proper content, or no access image if don't have access.
	 * 
	 * @since 4.0.0
	 *
	 * @action pre_get_posts  
	 * @access public
	 * @param WP_Query $wp_query
	 */
	public function handle_download_protection( $wp_query ) {
		$download_settings = MS_Plugin::instance()->settings->downloads;

		if( self::PROTECTION_TYPE_DISABLED == $download_settings['protection_type'] ) {
			return;
		}
		
		if ( ! empty( $wp_query->query_vars['protectedfile'] ) ) {
			$protected = explode( "/", $wp_query->query_vars['protectedfile'] );
			$protected = array_pop( $protected );
		}
		elseif ( ! empty( $_GET['ms_file'] ) && self::PROTECTION_TYPE_HYBRID == $download_settings['protection_type'] ) {
			$protected = $_GET['ms_file'];
		}
		
		if ( ! empty( $protected ) ) {
			extract( $this->extract_file_info( $protected ) );
			
			switch( $download_settings['protection_type'] ) {
				case self::PROTECTION_TYPE_COMPLETE:
				case self::PROTECTION_TYPE_HYBRID:
					/** Work out the post_id again */
					$attachment_id = preg_replace( '/^' . self::FILE_PROTECTION_PREFIX . '/', '', $filename );
					$attachment_id -= (int) self::FILE_PROTECTION_INCREMENT;
					
					$image = $this->restore_filename( $attachment_id, $size_extension );
					break;
				default:
				case self::PROTECTION_TYPE_BASIC:
					$attachment_id = $this->get_attachment_id( $filename );
					$image = $this->restore_filename( $attachment_id, $size_extension );
					break;
			}
			if ( ! empty( $image ) && ! empty( $attachment_id ) && is_numeric( $attachment_id ) ) {
				
				$post_id = get_post_field( 'post_parent', $attachment_id );
				
				/** check for access configuration */
				$membership = $this->ms_relationship->get_membership();
				if( $membership->has_access_to_current_page( $this->ms_relationship, $post_id ) ) {
					$upload_dir = wp_upload_dir();
					$file = trailingslashit( $upload_dir['basedir'] ) . $image;
					$this->output_file( $file );
				}
				else {
					$this->show_no_access_image();
				}
			}
			
				
		}
	}
	/**
	 * Restore filename from post_id.
	 * 
	 * @since 4.0
	 *
	 * @todo refactory hack to get extension.
	 * @access public
	 * @param int $post_id The attachment post_id.
	 * @param string $size_extension The image size extension.
	 * @return string The attachment filename.
	 */
	public function restore_filename( $post_id, $size_extension ) {
		$image = null;
		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$image = get_post_meta( $post_id, '_wp_attached_file', true );
			if ( ! empty( $size_extension ) ) {
				/** Add back in a size extension if we need to */
				$image = str_replace( '.' . pathinfo( $image, PATHINFO_EXTENSION ), $size_extension . '.' . pathinfo( $image, PATHINFO_EXTENSION ), $image );
				/** hack to remove any double extensions :/ need to change when work out a neater way */
				$image = str_replace( $size_extension . $size_extension, $size_extension, $image );
			}
		}
		return $image;
	}
	
	/**
	 * Output file to the browser.
	 * 
	 * @since 4.0.0
	 *
	 * @access private
	 * @param string $file The complete path to the file.
	 */
	private function output_file( $file ) {
	
		if ( ! is_file( $file ) ) {
			status_header( 404 );
			die( '404 &#8212; File not found.' );
		}
		
		$mime = wp_check_filetype( $file );
		if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) ) {
			$mime[ 'type' ] = mime_content_type( $file );
		}
		
		if( $mime[ 'type' ] ) {
			$mimetype = $mime[ 'type' ];
		}
		else {
			$mimetype = 'image/' . substr( $trueurl, strrpos( $file, '.' ) + 1 );
		}
	
		header( 'Content-type: ' . $mimetype );
		if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ) {
			header( 'Content-Length: ' . filesize( $file ) );
		}
		$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
		$etag = '"' . md5( $last_modified ) . '"';
		header( "Last-Modified: $last_modified GMT" );
		header( 'ETag: ' . $etag );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );
	
		/** Support for Conditional GET */
		$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;
	
		if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
		}
		
		$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		/** If string is empty, return 0. If not, attempt to parse into a timestamp */
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;
	
		/** Make a timestamp for our most recent modification... */
		$modified_timestamp = strtotime($last_modified);
	
		if ( ( $client_last_modified && $client_etag )
			? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
			: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
				) {
			status_header( 304 );
			exit;
		}
	
		/** If we made it this far, just serve the file */
		readfile( $file );
		die();
	}
	
	/**
	 * Show no access image.
	 * 
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function show_no_access_image() {
		$no_access_file = apply_filters( 'ms_model_rule_media_show_no_access_image_path', MS_Plugin::instance()->dir . 'app/assets/images/no-access.png' );
		$this->output_file( $no_access_file );	
	}
	
	/**
	 * Get content eligible for protection.
	 * 
	 * @since 4.0.0
	 *
	 * @access public
	 * @return object[] The content array.
	 */
	public function get_contents( $args = null ) {
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => 'attachment',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$contents = get_posts( $args );
						
		foreach( $contents as $content ) {
			$content->id = $content->ID;
			if( in_array( $content->id, $this->rule_value ) ) {
				$content->access = true;
			}
			else {
				$content->access = false;
			}
		}
		
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		
		return $contents;
	}
}