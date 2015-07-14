<?php
class MS_Helper_Settings extends MS_Helper {

	// Success response codes
	const SETTINGS_MSG_ADDED = 1;
	const SETTINGS_MSG_DELETED = 2;
	const SETTINGS_MSG_UPDATED = 3;
	const SETTINGS_MSG_ACTIVATION_TOGGLED = 4;
	const SETTINGS_MSG_STATUS_TOGGLED = 5;
	const SETTINGS_MSG_BULK_UPDATED = 6;
	const SETTINGS_MSG_SITE_UPDATED = 7;

	// Error response codes
	const SETTINGS_MSG_NOT_ADDED = -1;
	const SETTINGS_MSG_NOT_DELETED = -2;
	const SETTINGS_MSG_NOT_UPDATED = -3;
	const SETTINGS_MSG_ACTIVATION_NOT_TOGGLED = -4;
	const SETTINGS_MSG_STATUS_NOT_TOGGLED = -5;
	const SETTINGS_MSG_BULK_NOT_UPDATED = -6;
	const SETTINGS_MSG_UNCONFIGURED = -7;

	/**
	 * Returns the status messages for a given status code
	 *
	 * @since  1.0.0
	 * @param  int $msg Status code
	 * @return string Status message
	 */
	public static function get_admin_message( $msg = 0 ) {
		static $Messages = null;

		if ( null === $Messages ) {
			$Messages = apply_filters(
				'ms_helper_membership_get_admin_messages',
				array(
					// Success response codes
					self::SETTINGS_MSG_ADDED => __( 'Setting added.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_DELETED => __( 'Setting deleted.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_UPDATED => __( 'Setting updated.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_ACTIVATION_TOGGLED => __( 'Setting activation toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_STATUS_TOGGLED => __( 'Setting status toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_BULK_UPDATED => __( 'Bulk settings updated.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_SITE_UPDATED => __( 'Changed the network site that hosts Membership pages. Remember to check your Pages and change them if needed!', MS_TEXT_DOMAIN ),

					// Error response messages
					self::SETTINGS_MSG_NOT_ADDED => __( 'Setting not added.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_NOT_DELETED => __( 'Setting not deleted.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_NOT_UPDATED => __( 'Setting not updated.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_ACTIVATION_NOT_TOGGLED => __( 'Setting activation not toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_STATUS_NOT_TOGGLED => __( 'Setting status not toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_BULK_NOT_UPDATED => __( 'Bulk settings not updated.', MS_TEXT_DOMAIN ),
				)
			);
		}

		if ( isset( $Messages[ $msg ] ) ) {
			return $Messages[ $msg ];
		} else {
			return false;
		}
	}

	/**
	 * Displays a status message on the Admin screen.
	 *
	 * The message to display is determined by the URL param 'msg'
	 *
	 * @since  1.0.0
	 */
	public static function print_admin_message() {
		$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;
		$class = ( $msg > 0 ) ? 'updated' : 'error';
		$contents = self::get_admin_message( $msg );

		if ( $contents ) {
			lib2()->ui->admin_message( $contents, $class );
		}
	}

	/**
	 * Returns an array of all sites in the current network.
	 * The array index is the blog-ID and the array value the blog title.
	 *
	 * @since  1.0.0
	 * @param  bool $only_public By default only public sites are returned.
	 * @return array
	 */
	public static function get_blogs( $only_public = true ) {
		static $List = array();
		$key = $only_public ? 'public' : 'all';

		if ( ! isset( $List['_cache'] ) ) {
			$List['_cache'] = array();
		}

		if ( ! isset( $List[$key] ) ) {
			$args = array(
				'limit' => 0,
				'public'     => true,
				'spam'       => false,
				'deleted'    => false,
			);
			if ( $only_public ) {
				$args['archived'] = false;
				$args['mature'] = false;
			}
			$sites = wp_get_sites( $args );
			$List[$key] = array();

			foreach ( $sites as $site_data ) {
				$blog_id = $site_data['blog_id'];

				if ( isset( $List['_cache'][$blog_id] ) ) {
					$title = $List['_cache'][$blog_id];
				} else {
					switch_to_blog( $blog_id );
					$title = get_bloginfo( 'title' );
					$List['_cache'][$blog_id] = $title;
					restore_current_blog();
				}
				$List[$key][$blog_id] = $title;
			}
		}

		return $List[$key];
	}

}