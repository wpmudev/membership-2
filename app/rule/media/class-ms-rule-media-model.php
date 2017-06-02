<?php
/**
 * Membership Media Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */

/**
 * Rule model.
 */
class MS_Rule_Media_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Media::RULE_ID;

	/**
	 * Media protection type constants.
	 *
	 * @since  1.0.0
	 *
	 * @var string $protection_type
	 */
	const PROTECTION_TYPE_BASIC = 'protection_type_basic';
	const PROTECTION_TYPE_COMPLETE = 'protection_type_complete';
	const PROTECTION_TYPE_HYBRID = 'protection_type_hybrid';

	/**
	 * Media protection file change prefix.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const FILE_PROTECTION_PREFIX = 'ms_';

	/**
	 * Media protection file seed/token.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const FILE_PROTECTION_INCREMENT = 2771;

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @param  string $id The content id to verify access.
	 * @param  bool   $admin_has_access Used for post-meta box to get correct
	 *         post permission: By default admin has access to all posts, if
	 *         this flag is FALSE then the admin-check is skipped.
	 * @return bool|null True if has access, false otherwise.
	 *         Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		if ( MS_Model_Addon::is_enabled( MS_Addon_Mediafiles::ID )
			&& 'attachment' == get_post_type( $id )
		) {
			return parent::has_access( $id, $admin_has_access );
		} else {
			return null;
		}
	}

	/**
	 * Get the protection type available.
	 *
	 * @since  1.0.0
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
		$example1 = MS_Helper_Utility::home_url( $mask . date( '/Y/m/' ) . 'my-image.jpg' );
		$example2 = MS_Helper_Utility::home_url( $mask . '/ms_12345.jpg' );
		$example3 = MS_Helper_Utility::home_url( $mask . '/?ms_file=ms_12345.jpg' );
		$example1 = '<br /><small>' . __( 'Example:', 'membership2' ) . ' ' . $example1 . '</small>';
		$example2 = '<br /><small>' . __( 'Example:', 'membership2' ) . ' ' . $example2 . '</small>';
		$example3 = '<br /><small>' . __( 'Example:', 'membership2' ) . ' ' . $example3 . '</small>';

		$protection_types = array(
			self::PROTECTION_TYPE_BASIC => __( 'Basic protection (default)', 'membership2' ) . $example1,
			self::PROTECTION_TYPE_COMPLETE => __( 'Complete protection', 'membership2' ) . $example2,
			self::PROTECTION_TYPE_HYBRID => __( 'Hybrid protection', 'membership2' ) . $example3,
		);

		return apply_filters(
			'ms_rule_media_model_get_protection_types',
			$protection_types
		);
	}

	/**
	 * Validate protection type.
	 *
	 * @since  1.0.0
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
	 * Starts the output buffering to replace all links in the final HTML
	 * document.
	 *
	 * Related filter:
	 * - init
	 *
	 * @since  1.0.0
	 */
	public function buffer_start() {
		ob_start( array( $this, 'protect_download_content' ) );
	}

	/**
	 * Ends the output buffering and calls the output_callback function.
	 *
	 * Related filter:
	 * - shutdown
	 *
	 * @since  1.0.0
	 */
	public function buffer_end() {
		if ( ob_get_level() ) {
			ob_end_flush();
		}
	}

	/**
	 * Set initial protection.
	 *
	 * @since  1.0.0
	 */
	public function initialize() {
		parent::protect_content();

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) && ( ! is_admin() && ! is_customize_preview() ) ) {
			// Start buffering during init action, though output should only
			// happen a lot later... This way we're safe.
			$this->add_action( 'init', 'buffer_start', 9999 );

			// Process the buffer right in the end.
			$this->add_action( 'shutdown', 'buffer_end' );

			$this->add_action( 'parse_request', 'handle_download_protection', 3 );
		}
	}

	/**
	 * Protect media file.
	 *
	 * Search content and mask media filename and path.
	 *
	 * This function is called as output_callback for ob_start() - as a result
	 * we cannot echo/output anything in this function! The return value of the
	 * function will be displayed to the user.
	 *
	 * @since  1.0.0
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

		$upload_dir 	= wp_upload_dir();
		$original_url 	= trailingslashit( $upload_dir['baseurl'] );
		$new_path 		= trailingslashit(
			trailingslashit( get_option( 'home' ) ) .
			$download_settings['masked_url']
		);

		/*
		 * Find all the urls in the post and then we'll check if they are protected
		 * Regex from http://blog.mattheworiordan.com/post/13174566389/url-regular-expression-for-links-with-or-without-the
		 */
		$url_exp = implode(
			'',
			array(
				'/((([A-Za-z]{3,9}:(?:\/\/)?)',
				'(?:[-;:&=\+\$,\w]+@)?',
				'[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?',
				'\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)/',
			)
		);

		$matches = array();
		if ( preg_match_all( $url_exp, $the_content, $matches ) ) {
			$home = untrailingslashit( get_option( 'home' ) );

			/*
			 * $matches[0] .. Full link    'http://example.com/blog/img.png?ver=1'
			 * $matches[1] .. Full link    'http://example.com/blog/img.png?ver=1'
			 * $matches[2] .. Domain only  'http://example.com'
			 * $matches[3] .. Protocol     'http://'
			 * $matches[4] .. File path    '/blog/img.png?ver=1'
			 */
			if ( ! empty( $matches ) && ! empty( $matches[2] ) ) {
				$links = (array) $matches[0];
				$paths = (array) $matches[4];

				foreach ( $links as $key => $link ) {
					// Ignore all external links.
					if ( 0 !== strpos( $link, $home ) ) { continue; }

					// The file is on local site - is it a valid attachment?
					$file = basename( $paths[ $key ] );
					$post_id = $this->get_attachment_id( $link );

					// Ignore links that have no relevant wp_posts entry.
					if ( empty( $post_id ) ) { continue; }
					$f_info = $this->extract_file_info( $file );

					// We have a protected file - so we'll mask it!
					switch ( $download_settings['protection_type'] ) {
						case self::PROTECTION_TYPE_COMPLETE:
							$protected_filename = self::FILE_PROTECTION_PREFIX .
								( $post_id + (int) self::FILE_PROTECTION_INCREMENT ) .
								$f_info->size_extension .
								'.' . pathinfo( $f_info->filename, PATHINFO_EXTENSION );

							$the_content = str_replace(
								$link,
								$new_path . $protected_filename,
								$the_content
							);
							break;

						case self::PROTECTION_TYPE_HYBRID:
							$protected_filename = self::FILE_PROTECTION_PREFIX .
								($post_id + (int) self::FILE_PROTECTION_INCREMENT ) .
								$f_info->size_extension .
								'.' . pathinfo( $f_info->filename, PATHINFO_EXTENSION );

							$the_content = str_replace(
								$link,
								$new_path . '?ms_file=' . $protected_filename,
								$the_content
							);
							break;

						case self::PROTECTION_TYPE_BASIC:
						default:
							$the_content = str_replace(
								$link,
								str_replace(
									$original_url,
									$new_path,
									$link
								),
								$the_content
							);
						break;
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
	 * @since  1.0.0
	 *
	 * @param string $file The filename to extract info from.
	 * @return array {
	 *     @type string $filename The filename.
	 *     @type string $size_extension The wordpress thumbnail size extension. Default empty.
	 * }
	 */
	public function extract_file_info( $file ) {
		// See if the filename has a size extension and if so, strip it out.
		$filename_exp_full = '/(.+)\-(\d+[x]\d+)\.(.+)$/';
		$filename_exp_min = '/(.+)\.(.+)$/';
		$filematch = array();

		if ( preg_match( $filename_exp_full, $file, $filematch ) ) {
			// Image with an image size attached.
			$type = strtolower( $filematch[3] );
			$filename = $filematch[1] . '.' . $type;
			$size_extension = '-' . $filematch[2];
		} elseif ( preg_match( $filename_exp_min, $file, $filematch ) ) {
			// Image without an image size definition.
			$type = strtolower( $filematch[2] );
			$filename = $filematch[1] . '.' . $type;
			$size_extension = '';
		} else {
			// Image without an extension.
			$type = '';
			$filename = $file;
			$size_extension = '';
		}

		$info = (object) array(
			'filename' => $filename,
			'size_extension' => $size_extension,
			'type' => $type,
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
	 * @since  1.0.0
	 *
	 * @param  string $url The URL to obtain the post_id.
	 * @return int The post ID or 0 if not found.
	 */
	public function get_attachment_id( $url ) {
		static $Uploads_Url = null;
		static $Uploads_Url_Len = 0;
		global $wpdb;

		// First let WordPress try to find the Attachment ID.
		$id = $this->thumbnail_url_to_id( $url );

		if ( $id ) {
			// Make sure the result ID is a valid attachment ID.
			if ( 'attachment' != get_post_type( $id ) ) {
				$id = 0;
			}
		} else {
			// Manual attempt: Get the filename from the URL and use a custom query.
			if ( null === $Uploads_Url ) {
				$uploads = wp_upload_dir();
				$Uploads_Url = trailingslashit( $uploads['baseurl'] );
				$Uploads_Url_Len = strlen( $Uploads_Url );
			}

			if ( false !== strpos( $url, $Uploads_Url ) ) {
				$url = substr( $url, $Uploads_Url_Len );
			}

			// See if we cached that URL already.
			$id = wp_cache_get( $url, 'ms_attachment_id' );

			if ( empty( $id ) ) {
				$sql = "
				SELECT wposts.ID
				FROM $wpdb->posts as wposts
					INNER JOIN $wpdb->postmeta as wpostmeta ON wposts.ID = wpostmeta.post_id
				WHERE
					wposts.post_type = 'attachment'
					AND wpostmeta.meta_key = '_wp_attached_file'
					AND wpostmeta.meta_value = %s
				";
				$sql = $wpdb->prepare( $sql, $url );
				$id = $wpdb->get_var( $sql );

				wp_cache_set( $url, $id, 'ms_attachment_id' );
			}
		}

		return apply_filters(
			'ms_rule_get_attachment_id',
			absint( $id ),
			$url,
			$this
		);
	}

	/**
	 * Fetch ID of an image from the image URL.
	 * The URL can contain the size
	 *
	 * @see http://stackoverflow.com/questions/10990808/wordpress-unique-scenario-get-attachment-id-by-url
	 * @since  1.0.2.6
	 * @param  string $file_url URL of an attachment.
	 * @return int The post-ID or 0 if no post found.
	 */
	public function thumbnail_url_to_id( $file_url ) {
		global $wpdb;
		$filename = basename( $file_url );

		// TODO: This is the same code as used in function `get_attachment_id` above??
		$rows = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT     p.ID, m.meta_value
				FROM       {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
					AND m.meta_key = '_wp_attachment_metadata'
					AND m.meta_value LIKE %s
				",
				'%"' . $filename . '"%'
			)
		);

		foreach ( $rows as $row ) {
			$row->meta_value = maybe_unserialize( $row->meta_value );
			return $row->ID;
		}

		return 0;
	}

	/**
	 * Handle protected media access.
	 *
	 * Search for masked file and show the proper content, or no access image if don't have access.
	 *
	 * Realted Action Hooks:
	 * - parse_request
	 *
	 * @since  1.0.0
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

		if ( ! empty( $query->query_vars['protectedfile'] ) && self::PROTECTION_TYPE_COMPLETE == $protection_type ) {
			$requested_item = explode( '/', $query->query_vars['protectedfile'] );
			$requested_item = array_pop( $requested_item );
		} elseif ( ! empty( $_GET['ms_file'] )
			&& self::PROTECTION_TYPE_HYBRID == $protection_type
		) {
			$requested_item = $_GET['ms_file'];
		} else {
			$requested_item = MS_Helper_Utility::get_current_url();
		}

		if ( ! empty( $requested_item ) ) {
			// At this point we know that the requested post is an attachment.
			$f_info = $this->extract_file_info( $requested_item );

			switch ( $protection_type ) {
				case self::PROTECTION_TYPE_COMPLETE:
				case self::PROTECTION_TYPE_HYBRID:
					// Work out the post_id again.
					$attachment_id = preg_replace(
						'/^' . self::FILE_PROTECTION_PREFIX . '/',
						'',
						$f_info->filename
					);

					if( $protection_type == self::PROTECTION_TYPE_COMPLETE ){
						
						$request_name = basename( $attachment_id ); // Get the name of the requested file

						$request_name = pathinfo( $request_name ); // Get the info the of the requested file
						
						$attachment_id = str_replace( 'ms_','',$request_name['filename'] ); // Remove the prefix since we always have ms_ and get the attachment id.
					}

					$attachment_id = $attachment_id - (int) self::FILE_PROTECTION_INCREMENT;

					$the_file = $this->restore_filename( $attachment_id, $f_info->size_extension );
					break;

				default:
				case self::PROTECTION_TYPE_BASIC:
					$upload_dir = wp_upload_dir();
					$original_url = $upload_dir['baseurl'];
					$home = get_option( 'home' );
					$original_url = explode( $home, $original_url );

					$furl = untrailingslashit(
						str_replace(
							'/' . $download_settings['masked_url'],
							$original_url[1],
							$requested_item
						)
					);

					$home = untrailingslashit( get_option( 'home' ) );
					$attachment_id = $this->get_attachment_id( $furl );
					$the_file = $this->restore_filename( $attachment_id, $f_info->size_extension );
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
	 * @since  1.0.0
	 * @param  int $attachment_id The attachment post-ID.
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
	 * @since  1.0.0
	 *
	 * @todo refactory hack to get extension.
	 *
	 * @param  int    $post_id The attachment post_id.
	 * @param  string $size_extension The image size extension.
	 * @return string The attachment filename.
	 */
	public function restore_filename( $post_id, $size_extension ) {
		$img_filename = null;

		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$img_filename = get_post_meta( $post_id, '_wp_attached_file', true );
			if ( ! empty( $size_extension ) ) {
				// Add back in a size extension if we need to.
				$img_filename = str_replace(
					'.' . pathinfo( $img_filename, PATHINFO_EXTENSION ),
					$size_extension . '.' . pathinfo( $img_filename, PATHINFO_EXTENSION ),
					$img_filename
				);

				// Hack to remove any double extensions :/ need to change when work out a neater way.
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
	 * @since  1.0.0
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
                
                if( ! defined( 'M2_MEDIA_ETAG_DISABLED' ) )
                {
                    if( ! defined( 'M2_MEDIA_ETAG' ) ) define( 'M2_MEDIA_ETAG', 'm2_media_addon_etag' );

                    $last_modified = date_i18n( 'D, d M Y H:i:s', filemtime( $file ) );
                    $etag = '"' . md5( $last_modified ) . '"';
                    header( "Last-Modified: $last_modified GMT" );
                    header( 'ETag: ' . $etag );
                    header( 'Expires: ' . date_i18n( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );
    
                    // Support for Conditional GET.
                    if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) {
                            $client_etag = stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] );
                    } else {
                            $client_etag = false;
                    }
    
                    if ( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
                            $_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
                    }
    
                    $client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
                    // If string is empty, return 0. If not, attempt to parse into a timestamp.
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
    
                    /*if ( $valid_etag ) {
                            status_header( 304 );
                            exit;
                    }*/
                }
                
		// If we made it this far, just serve the file.
		readfile( $file );

		do_action( 'ms_rule_media_model_output_file_after', $file, $this );

		die();
	}

	/**
	 * Show no access image.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @param array $args The query post args.
	 *        @see @link http://codex.wordpress.org/Class_Reference/WP_Query
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
	 * Get the total content count.
	 *
	 * @since  1.0.0
	 *
	 * @param array $args The query post args.
	 *        @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );

		$count = $query->found_posts;

		return apply_filters(
			'ms_rule_media_model_get_content_count',
			$count,
			$args
		);
	}

	/**
	 * Get the default query args.
	 *
	 * @since  1.0.0
	 *
	 * @param string $args The query post args.
	 *        @see @link http://codex.wordpress.org/Class_Reference/WP_Query
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
