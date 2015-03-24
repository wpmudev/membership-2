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

/**
 * Membership Media Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_Media_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Media::RULE_ID;

	/**
	 * Media protection type constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string $protection_type
	 */
	const PROTECTION_TYPE_BASIC = 'protection_type_basic';
	const PROTECTION_TYPE_COMPLETE = 'protection_type_complete';
	const PROTECTION_TYPE_HYBRID = 'protection_type_hybrid';

	/**
	 * Media protection file change prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE_PROTECTION_PREFIX = 'ms_';

	/**
	 * Media protection file seed/token.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE_PROTECTION_INCREMENT = 2771;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA );
	}


	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because the media-item is
	 * protected via the download URL and not by protecting the current page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id ) {
		if ( MS_Model_Addon::is_enabled( MS_Addon_Mediafiles::ID )
			&& 'attachment' == get_post_type( $id )
		) {
			return parent::has_access( $id );
		} else {
			return null;
		}
	}

	/**
	 * Get the protection type available.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     The protection type => Description.
	 *     @type string $protection_type The media protection type.
	 *     @type string $description The media protection description.
	 * }
	 */
	public static function get_protection_types() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$mask = $settings->downloads['masked_url'];
		$example1 = home_url( $mask . date( '/Y/m/' ) . 'my-image.jpg' );
		$example2 = home_url( $mask . '/ms_12345.jpg' );
		$example3 = home_url( $mask . '/?ms_file=ms_12345.jpg' );
		$example1 = '<br /><small>' . __( 'Example:', MS_TEXT_DOMAIN ) . ' ' . $example1 . '</small>';
		$example2 = '<br /><small>' . __( 'Example:', MS_TEXT_DOMAIN ) . ' ' . $example2 . '</small>';
		$example3 = '<br /><small>' . __( 'Example:', MS_TEXT_DOMAIN ) . ' ' . $example3 . '</small>';

		$protection_types = array(
			self::PROTECTION_TYPE_BASIC => __( 'Basic protection (default)', MS_TEXT_DOMAIN ) . $example1,
			self::PROTECTION_TYPE_COMPLETE => __( 'Complete protection', MS_TEXT_DOMAIN ) . $example2,
			self::PROTECTION_TYPE_HYBRID => __( 'Hybrid protection', MS_TEXT_DOMAIN ) . $example3,
		);

		return apply_filters(
			'ms_rule_media_model_get_protection_types',
			$protection_types
		);
	}

	/**
	 * Validate protection type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The protection type to validate.
	 * @return boolean True if is valid.
	 */
	public static function is_valid_protection_type( $type ) {
		$valid = false;
		$types = self::get_protection_types();

		if ( array_key_exists( $type, $types ) ) {
			$valid = true;
		}

		return apply_filters(
			'ms_rule_media_model_is_valid_protection_type',
			$valid
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		parent::protect_content();

// Add Filter: get_attachment_link

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			$this->add_filter( 'the_content', 'protect_download_content' );
			$this->add_action( 'parse_request', 'handle_download_protection', 3 );
		}
	}

	/**
	 * Protect media file.
	 *
	 * Search content and mask media filename and path.
	 *
	 * Related Action Hooks:
	 * - the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $the_content The content before filter.
	 * @return string The content with masked media url.
	 */
	public function protect_download_content( $the_content ) {
		do_action(
			'ms_rule_media_model_protect_download_content_before',
			$the_content,
			$this
		);

		$download_settings = MS_Plugin::instance()->settings->downloads;

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			return $the_content;
		}

		$upload_dir = wp_upload_dir();
		$original_url = trailingslashit( $upload_dir['baseurl'] );
		$new_path = trailingslashit(
			trailingslashit( get_option( 'home' ) ) .
			$download_settings['masked_url']
		);

		/*
		 * Find all the urls in the post and then we'll check if they are protected
		 * Regex from http://blog.mattheworiordan.com/post/13174566389/url-regular-expression-for-links-with-or-without-the
		 */
		$url_exp = '/((([A-Za-z]{3,9}:(?:\/\/)?)' .
					'(?:[-;:&=\+\$,\w]+@)?'.
					'[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?' .
					'\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)/';

		$matches = array();
		if ( preg_match_all( $url_exp, $the_content, $matches ) ) {
			$home = get_option( 'home' );

			if ( ! empty( $matches ) && ! empty( $matches[2] ) ) {
				foreach ( (array) $matches[0] as $key => $domain ) {
					if ( 0 === strpos( $domain, untrailingslashit( $home ) ) ) {
						$found_local = $key;
						$file = basename( $matches[4][ $found_local ] );

						extract( $this->extract_file_info( $file ) );

						$post_id = $this->get_attachment_id( $filename );

						if ( ! empty( $post_id ) ) {

							// We have a protected file - so we'll mask it
							switch ( $download_settings['protection_type'] ) {
								case self::PROTECTION_TYPE_COMPLETE:
									$protected_filename = self::FILE_PROTECTION_PREFIX .
										( $post_id + (int) self::FILE_PROTECTION_INCREMENT ) .
										$size_extension .
										'.' . pathinfo( $filename, PATHINFO_EXTENSION );

									$the_content = str_replace(
										$matches[0][$found_local],
										$new_path . $protected_filename,
										$the_content
									);
									break;

								case self::PROTECTION_TYPE_HYBRID:
									$protected_filename = self::FILE_PROTECTION_PREFIX .
										($post_id + (int) self::FILE_PROTECTION_INCREMENT ) .
										$size_extension .
										'.' . pathinfo( $filename, PATHINFO_EXTENSION );

									$the_content = str_replace(
										$matches[0][ $found_local ],
										$new_path . '?ms_file=' . $protected_filename,
										$the_content
									);
									break;

								case self::PROTECTION_TYPE_BASIC:
								default:
									$the_content = str_replace(
										$matches[0][ $found_local ],
										str_replace(
											$original_url,
											$new_path,
											$matches[0][$found_local]
										),
										$the_content
									);
								break;
							}
						}
					}
				}
			}
		}

		return apply_filters(
			'ms_rule_media_model_protect_download_content',
			$the_content,
			$this
		);
	}

	/**
	 * Extract filename and size extension info.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file The filename to extract info from.
	 * @return array {
	 *     @type string $filename The filename.
	 *     @type string $size_extension The wordpress thumbnail size extension. Default empty.
	 * }
	 */
	public function extract_file_info( $file ) {
		// See if the filename has a size extension and if so, strip it out
		$filename_exp = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
		$filematch = array();

		if ( preg_match( $filename_exp, $file, $filematch ) ) {
			// Image with an image size attached
			$filename = $filematch[1] . '.' . $filematch[3];
			$size_extension = '-' . $filematch[2];
		} else {
			$filename = $file;
			$size_extension = '';
		}

		$info = array(
			'filename' => $filename,
			'size_extension' => $size_extension,
		);

		return apply_filters(
			'ms_rule_media_model_extract_file_info',
			$info,
			$file,
			$this
		);
	}

	/**
	 * Get attachment post_id using the filename.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename The filename to obtain the post_id.
	 * @return int The post ID or 0 if not found.
	 */
	public function get_attachment_id( $filename ) {
		$post_id = 0;

		$args = array(
			'posts_per_page' => 1,
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_wp_attached_file',
					'value' => $filename,
					'compare' => 'LIKE',
				),
				array(
					'key' => '_wp_attachment_metadata',
					'value' => $filename,
					'compare' => 'LIKE',
				)
			)
		);

		$args = apply_filters(
			'ms_rule_media_model_get_attachment_id_args',
			$args
		);
		$query = new WP_Query( $args );
		$post = $query->get_posts();

		if ( ! empty( $post[0] ) ) {
			$post_id = $post[0];
		}

		return apply_filters(
			'ms_rule_get_attachment_id',
			$post_id,
			$filename,
			$this
		);
	}

	/**
	 * Handle protected media access.
	 *
	 * Search for masked file and show the proper content, or no access image if don't have access.
	 *
	 * Realted Action Hooks:
	 * - pre_get_posts
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function handle_download_protection( $query ) {
		do_action(
			'ms_rule_media_model_handle_download_protection_before',
			$query,
			$this
		);

		$the_file = false;
		$requested_item = false;
		$download_settings = MS_Plugin::instance()->settings->downloads;
		$protection_type = $download_settings['protection_type'];

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			return;
		}

		if ( ! empty( $query->query_vars['protectedfile'] ) ) {
			$requested_item = explode( '/', $query->query_vars['protectedfile'] );
			$requested_item = array_pop( $requested_item );
		} elseif ( ! empty( $_GET['ms_file'] )
			&& self::PROTECTION_TYPE_HYBRID == $protection_type
		) {
			$requested_item = $_GET['ms_file'];
		}

		if ( ! empty( $requested_item ) ) {
			// At this point we know that the requested post is an attachment.

			extract( $this->extract_file_info( $requested_item ) );

			switch ( $protection_type ) {
				case self::PROTECTION_TYPE_COMPLETE:
				case self::PROTECTION_TYPE_HYBRID:
					// Work out the post_id again
					$attachment_id = preg_replace(
						'/^' . self::FILE_PROTECTION_PREFIX . '/',
						'',
						$filename
					);
					$attachment_id -= (int) self::FILE_PROTECTION_INCREMENT;

					$the_file = $this->restore_filename( $attachment_id, $size_extension );
					break;

				default:
				case self::PROTECTION_TYPE_BASIC:
					$attachment_id = $this->get_attachment_id( $filename );
					$the_file = $this->restore_filename( $attachment_id, $size_extension );
					break;
			}

			if ( ! empty( $the_file )
				&& ! empty( $attachment_id )
				&& is_numeric( $attachment_id )
			) {
				if ( $this->can_access_file( $attachment_id ) ) {
					$upload_dir = wp_upload_dir();
					$file = trailingslashit( $upload_dir['basedir'] ) . $the_file;
					$this->output_file( $file );
				} else {
					$this->show_no_access_image();
				}
			}
		}

		do_action(
			'ms_rule_media_model_handle_download_protection_after',
			$query,
			$this
		);
	}

	/**
	 * Checks if the current user can access the specified attachment.
	 *
	 * @since  1.1.1.2
	 * @param  int $attachment_id
	 * @return bool
	 */
	public function can_access_file( $attachment_id ) {
		$access = false;

		if ( MS_Model_Member::is_normal_admin() ) {
			return true;
		}

		if ( ! MS_Model_Addon::is_enabled( MS_Addon_Mediafiles::ID ) ) {
			/*
			 * Default protection mode:
			 * Protect Attachments based on the parent post.
			 */
			$parent_id = get_post_field( 'post_parent', $attachment_id );

			if ( ! $parent_id ) {
				$access = true;
			} else {
				$member = MS_Model_Member::get_current_member();
				foreach ( $member->subscriptions as $subscription ) {
					$membership = $subscription->get_membership();
					$access = $membership->has_access_to_post( $parent_id );
					if ( $access ) { break; }
				}
			}
		} else {
			/*
			 * Advanced protection mode (via Add-on):
			 * Each Attachment can be protected individually.
			 */
			$member = MS_Model_Member::get_current_member();
			foreach ( $member->subscriptions as $subscription ) {
				$rule = $subscription->get_membership()->get_rule( MS_Rule_Media::RULE_ID );
				$access = $rule->has_access( $attachment_id );
				if ( $access ) { break; }
			}
		}

		return apply_filters(
			'ms_rule_media_can_access_file',
			$access,
			$attachment_id
		);
	}

	/**
	 * Restore filename from post_id.
	 *
	 * @since 1.0.0
	 *
	 * @todo refactory hack to get extension.
	 *
	 * @param int $post_id The attachment post_id.
	 * @param string $size_extension The image size extension.
	 * @return string The attachment filename.
	 */
	public function restore_filename( $post_id, $size_extension ) {
		$img_filename = null;

		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$img_filename = get_post_meta( $post_id, '_wp_attached_file', true );
			if ( ! empty( $size_extension ) ) {
				// Add back in a size extension if we need to
				$img_filename = str_replace(
					'.' . pathinfo( $img_filename, PATHINFO_EXTENSION ),
					$size_extension . '.' . pathinfo( $img_filename, PATHINFO_EXTENSION ),
					$img_filename
				);

				// hack to remove any double extensions :/ need to change when work out a neater way
				$img_filename = str_replace(
					$size_extension . $size_extension,
					$size_extension,
					$img_filename
				);
			}
		}

		return apply_filters(
			'ms_rule_restore_filename',
			$img_filename,
			$post_id,
			$this
		);
	}

	/**
	 * Output file to the browser.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file The complete path to the file.
	 */
	private function output_file( $file ) {
		do_action( 'ms_rule_media_model_output_file_before', $file, $this );

		if ( ! is_file( $file ) ) {
			status_header( 404 );
			die( '404 &#8212; File not found.' );
		}

		$mime = wp_check_filetype( $file );
		if ( empty( $mime['type'] ) && function_exists( 'mime_content_type' ) ) {
			$mime['type'] = mime_content_type( $file );
		}

		if ( $mime['type'] ) {
			$mimetype = $mime['type'];
		} else {
			$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
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

		// Support for Conditional GET
		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) {
			$client_etag = stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] );
		} else {
			$client_etag = false;
		}

		if ( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
		}

		$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		// If string is empty, return 0. If not, attempt to parse into a timestamp
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

		// Make a timestamp for our most recent modification...
		$modified_timestamp = strtotime( $last_modified );

		if ( $client_last_modified && $client_etag ) {
			$valid_etag = ( $client_modified_timestamp >= $modified_timestamp )
				&& ( $client_etag === $etag );
		} else {
			$valid_etag = ( $client_modified_timestamp >= $modified_timestamp )
				|| ( $client_etag === $etag );
		}

		if ( $valid_etag ) {
			status_header( 304 );
			exit;
		}

		// If we made it this far, just serve the file
		readfile( $file );

		do_action( 'ms_rule_media_model_output_file_after', $file, $this );

		die();
	}

	/**
	 * Show no access image.
	 *
	 * @since 1.0.0
	 */
	private function show_no_access_image() {
		$no_access_file = apply_filters(
			'ms_rule_media_model_show_no_access_image_path',
			MS_Plugin::instance()->dir . 'app/assets/images/no-access.png'
		);

		$this->output_file( $no_access_file );

		do_action( 'ms_rule_show_no_access_image_after', $this );
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$args = self::get_query_args( $args );
		$posts = get_posts( $args );

		$contents = array();
		foreach ( $posts as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Rule_Media::RULE_ID;
			$content->name = $content->post_name;
			$content->access = $this->can_access_file( $content->id );

			$contents[ $content->id ] = $content;
		}

		return apply_filters(
			'ms_rule_media_model_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get the default query args.
	 *
	 * @since 1.0.0
	 *
	 * @param string $args The query post args.
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The parsed args.
	 */
	public function get_query_args( $args = null ) {
		$defaults = array(
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_type' => 'attachment',
			'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );

		return parent::prepare_query_args( $args, 'get_posts' );
	}
}
