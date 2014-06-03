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
	
	const PROTECTION_TYPE_BASIC = 'protection_type_basic';
	
	const PROTECTION_TYPE_COMPLETE = 'protection_type_complete';
	
	const PROTECTION_TYPE_HYBRID = 'protection_type_hybrid';
	
	const FILE_PROTECTION_PREFIX = 'ms_';
	
	const FILE_PROTECTION_INCREMENT = 2771;
	
	public static function get_protection_types() {
		return apply_filters( 'ms_model_rule_media_get_protection_types', array(
				self::PROTECTION_TYPE_BASIC => __( 'Basic protection', MS_TEXT_DOMAIN ),
				self::PROTECTION_TYPE_COMPLETE => __( 'Complete protection', MS_TEXT_DOMAIN ),
				self::PROTECTION_TYPE_HYBRID => __( 'Hybrid protection', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function protect_content() {
		$this->add_filter( 'the_content', 'protect_download_content' );
	}
	
	public function protect_download_content( $the_content ) {
		$download_settings = MS_Plugin::instance()->settings->downloads;
		$upload_dir = wp_upload_dir();
		$original_url = $upload_dir['baseurl'];
		$new_path = trailingslashit( trailingslashit( get_option( 'home' ) ) . $download_settings['masked_url'] );
		
		// Find all the urls in the post and then we'll check if they are protected
		/* Regular expression from http://blog.mattheworiordan.com/post/13174566389/url-regular-expression-for-links-with-or-without-the */
		
		$url_exp = '/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)/';
		
		$matches = array();
		if( preg_match_all( $url_exp, $the_content, $matches ) ) {
			$home = get_option( 'home' );
			if( ! empty( $matches ) && ! empty( $matches[2] ) ) {
				foreach( (array) $matches[0] as $key => $domain ) {
					if( strpos( $domain, untrailingslashit( $home ) ) === 0 ) {
						$found_local = $key;
						$file = basename( $matches[4][ $found_local ] );
		
						$filename_exp = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
						$filematch = array();
						if( preg_match( $filename_exp, $file, $filematch ) ) {
							// We have an image with an image size attached
							$new_file = $filematch[1] . "." . $filematch[3];
							$size_extension = '-' . $filematch[2];
						} 
						else {
							$new_file = $file;
							$size_extension = '';
						}
		
						$post_id = $this->get_post_id_by_attachment( $new_file );
		
						if( ! empty( $post_id ) && ! in_array( $this->rule_value ) ) {
							// We have a protected file - so we'll mask it
							switch( $download_settings['protection_type'] ) {
								case self::PROTECTION_TYPE_COMPLETE:	
								$protected_filename = self::FILE_PROTECTION_PREFIX . ( $post_id + (int) self::FILE_PROTECTION_INCREMENT) . $size_extension;
								$protected_filename .= "." . pathinfo( $new_file, PATHINFO_EXTENSION );
	
								$the_content = str_replace( $matches[0][$found_local], $new_path . $protected_filename, $the_content );
								break;
								case self::PROTECTION_TYPE_HYBRID:
								$protected_filename = self::FILE_PROTECTION_PREFIX . ($post_id + (int) self::FILE_PROTECTION_INCREMENT) . $size_extension;
								$protected_filename .= "." . pathinfo($new_file, PATHINFO_EXTENSION);
	
								$the_content = str_replace( $matches[0][$found_local], $new_path . "?ms_file=" . $protected_filename, $the_content );
								break;
	
								case self::PROTECTION_TYPE_BASIC:
								default:			
								$the_content = str_replace( $matches[0][$found_local], str_replace( $original_url, $new_path, $matches[0][$found_local] ), $the_content );
	
								break;
							}
						}
		
					}
				}
			}
		
		}
		
		return $the_content;
		
	}
	
	public function get_post_id_by_attachment( $filename ) {
		$args = array(
			'posts_per_page' => 1,
			'post_type'   => 'attachment',
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
		
		$query = new WP_Query($args);
		$post = $query->get_posts();
		
		if( ! empty( $post[0] ) ) {
			$post[0]->ID;
		}
		return null;		
	}
	
	public function get_content( $args = null ) {
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