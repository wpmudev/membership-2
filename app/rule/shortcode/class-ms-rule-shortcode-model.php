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
 * Membership Shortcode Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_Shortcode_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Shortcode::RULE_ID;

	/**
	 * Holds the membership-IDs of all memberships of the user.
	 *
	 * @since  1.1.1.2
	 *
	 * @var array
	 */
	static protected $membership_ids = array();

	/**
	 * Protect content shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PROTECT_CONTENT_SHORTCODE = 'ms-protect-content';

	/**
	 * Returns the active flag for a specific rule.
	 * State depends on Add-on
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE );
	}

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because shortcodes are
	 * replaced inside the page content instead of protecting the whole page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id, $admin_has_access = true ) {
		return null;
	}

	/**
	 * Set initial protection.
	 *
	 * Add [ms-protect-content] shortcode to protect membership content inside post.
	 *
	 * @since 1.0.0
	 */
	public function protect_content() {
		parent::protect_content();

		self::$membership_ids[] = $this->membership_id;

		add_shortcode(
			self::PROTECT_CONTENT_SHORTCODE,
			array( __CLASS__, 'protect_content_shortcode' )
		);

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
			global $shortcode_tags;
			$exclude = MS_Helper_Shortcode::get_membership_shortcodes();

			foreach ( $shortcode_tags as $shortcode => $callback_funciton ) {
				if ( in_array( $shortcode, $exclude ) ) {
					continue;
				}
				if ( ! parent::has_access( $shortcode ) ) {
					$shortcode_tags[ $shortcode ] = array(
						&$this,
						'do_protected_shortcode',
					);
				}
			}
		}
	}

	/**
	 * Do protected shortcode [do_protected_shortcode].
	 *
	 * This shortcode is executed to replace a protected shortcode.
	 *
	 * @since 1.0.0
	 */
	public function do_protected_shortcode() {
		$content = null;
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$msg = $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE );

		if ( $msg ) {
			$content = $msg;
		} else {
			$content = __( 'Shortcode content protected.', MS_TEXT_DOMAIN );
		}

		return apply_filters(
			'ms_model_shortcode_do_protected_shortcode_content',
			$content,
			$this
		);
	}

	/**
	 * Do membership content protection shortcode.
	 *
	 * self::PROTECT_CONTENT_SHORTCODE
	 *
	 * Verify if content is protected comparing to self::$membership_ids.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The content inside the shortcode.
	 * @param string $code The shortcode code.
	 * @return string The shortcode output
	 */
	static public function protect_content_shortcode( $atts, $content = null, $code = '' ) {
		$atts = apply_filters(
			'ms_model_shortcode_protect_content_shortcode_atts',
			shortcode_atts(
				array(
					'id' => '',
					'access' => true,
					'silent' => false,
					'msg' => false,
				),
				$atts
			)
		);
		extract( $atts );

		$membership_ids = explode( ',', $id );

		if ( $silent ) {
			$msg = '';
		} else {
			if ( ! is_string( $msg ) || ! strlen( $msg ) ) {
				$settings = MS_Factory::load( 'MS_Model_Settings' );
				$msg = $settings->get_protection_message(
					MS_Model_Settings::PROTECTION_MSG_SHORTCODE
				);
			}
		}

		$access = lib2()->is_true( $access );

		if ( ! $access ) {
			// No access to member of membership_ids

			if ( self::is_member_of( $membership_ids ) ) {
				// User belongs to these memberships and therefore cannot see
				// this content...

				if ( $silent ) {
					// Silent protection: Do not show a message, simply hide it
					$content = '';
				} else {
					$content = '<div class="ms-protection-msg">';
					if ( ! empty( $msg ) ) {
						$content .= $msg;
					} else {
						$membership_names = MS_Model_Membership::get_membership_names(
							array( 'post__in' => $membership_ids )
						);
						$content .= __( 'No access to members of: ', MS_TEXT_DOMAIN );
						$content .= implode( ', ', $membership_names );
					}
					$content .= '</div>';
				}
			}
		} else {
			// Give access to member of membership_ids

			if ( ! self::is_member_of( $membership_ids ) ) {
				// User does not belong to these memberships and therefore
				// cannot see this content...

				if ( $silent ) {
					// Silent protection: Do not show a message, simply hide it
					$content = '';
				} else {
					$content = '<div class="ms-protection-msg">';
					if ( ! empty( $msg ) ) {
						$content .= $msg;
					} else {
						$membership_names = MS_Model_Membership::get_membership_names(
							array( 'post__in' => $membership_ids )
						);
						$content .= __( 'Content protected to members of: ', MS_TEXT_DOMAIN );
						$content .= implode( ', ', $membership_names );
					}
					$content .= '</div>';
				}
			}
		}

		return apply_filters(
			'ms_rule_shortcode_model_protect_content_shortcode_content',
			do_shortcode( $content ),
			$atts,
			$content,
			$code
		);
	}

	/**
	 * For Admin-Users only:
	 * The [ms-protected-content] shortcode is replaced with some debug values
	 * for better understanding of the page structure.
	 *
	 * @since  1.1.1.5
	 * @param array $atts The shortcode attributes.
	 * @param string $content The content inside the shortcode.
	 * @return string The shortcode output
	 */
	public function debug_protect_content_shortcode( $atts, $content = '' ) {
		$do_debug = true;

		/**
		 * This wp-config setting defines the default state of the shortcode
		 * debugging flag.
		 */
		if ( defined( 'MS_NO_SHORTCODE_PREVIEW' ) && MS_NO_SHORTCODE_PREVIEW ) {
			$do_debug = false;
		}

		/**
		 * Use this filter to disable the protected-content debugging
		 * information for admin users.
		 * The content will always be displayed for admin users.
		 */
		$do_debug = apply_filters(
			'ms_model_shortcode_debug_protected_content',
			$do_debug,
			$atts,
			$content
		);

		if ( ! $do_debug ) {
			return $content;
		}

		$atts = apply_filters(
			'ms_model_shortcode_protect_content_shortcode_atts',
			shortcode_atts(
				array(
					'id' => '',
					'access' => true,
					'silent' => false,
					'msg' => false,
				),
				$atts
			)
		);
		extract( $atts );

		if ( lib2()->is_true( $access ) ) {
			$msg_access = __( 'Visible for members of', MS_TEXT_DOMAIN );
			$alt_msg1 = __( 'Other users will see', MS_TEXT_DOMAIN );
			$alt_msg2 = __( 'Other uses will see nothing', MS_TEXT_DOMAIN );
		} else {
			$msg_access = __( 'Hidden from members of', MS_TEXT_DOMAIN );
			$alt_msg1 = __( 'Those users will see', MS_TEXT_DOMAIN );
			$alt_msg2 = __( 'Those uses will see nothing', MS_TEXT_DOMAIN );
		}

		if ( $msg ) {
			$msg_alt = sprintf( '%s: %s', $alt_msg1, $msg );
		} else {
			$msg_alt = $alt_msg2;
		}

		$membership_ids = explode( ',', $id );
		$memberships = array();
		foreach ( $membership_ids as $membership_id ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			$memberships[] = $membership->get_name_tag();
		}

		$css = '<style>
		.ms-membership {
			display: inline-block;
			border-radius: 3px;
			color: #FFF;
			background: #888888;
			padding: 1px 5px;
			font-size: 12px;
			height: 20px;
			line-height: 20px;
		}
		.ms-protected-info {
			border: 1px solid rgba(0,0,0,0.07);
		}
		.ms-protected-info:hover {
			border: 1px solid rgba(0,0,0,0.3);
		}
		.ms-protected-info .ms-details,
		.ms-protected-info .ms-alternate-msg {
			background: #EEE;
			padding: 4px;
			font-size: 12px;
			color: #666;
			opacity: 0.25;
		}
		.ms-protected-info:hover .ms-details,
		.ms-protected-info:hover .ms-alternate-msg {
			opacity: 1;
		}
		.ms-protected-info .ms-contents {
			padding: 4px 8px;
		}
		</style>';

		$code = sprintf(
			'<div class="ms-protected-info" title="%5$s"><div class="ms-details">%3$s: %4$s</div><div class="ms-contents">%1$s</div><div class="ms-alternate-msg">%2$s</div></div>',
			$content,
			$msg_alt,
			$msg_access,
			implode( ' ', $memberships ),
			__( 'This information is only displayed for admin users', MS_TEXT_DOMAIN )
		);

		return $css . $code;
	}

	/**
	 * Returns true when the current user is a member of one of the specified
	 * memberships.
	 *
	 * @since  1.0.4.2
	 *
	 * @return bool
	 */
	static protected function is_member_of( $ids ) {
		$result = false;

		if ( empty( $ids ) ) {
			$result = true;
		} else {
			if ( ! is_array( $ids ) ) {
				$ids = array( $ids );
			}

			foreach ( self::$membership_ids as $the_id ) {
				if ( in_array( $the_id, $ids ) ) {
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Get the total content count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args. Not used.
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$items = $this->get_contents( $args );
		$count = count( $items );

		return apply_filters(
			'ms_rule_shortcode_model_get_content_count',
			$count,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The filter args
	 *
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		global $shortcode_tags;

		$exclude = MS_Helper_Shortcode::get_membership_shortcodes();
		$contents = array();

		foreach ( $shortcode_tags as $key => $function ) {
			if ( in_array( $key, $exclude ) ) {
				continue;
			}

			// Search the shortcode-tag...
			if ( ! empty( $args['s'] ) ) {
				if ( false === stripos( $key, $args['s'] ) ) {
					continue;
				}
			}

			$contents[ $key ] = new StdClass();
			$contents[ $key ]->id = $key;
			$contents[ $key ]->name = "[$key]";
			$contents[ $key ]->type = MS_Rule_Shortcode::RULE_ID;
			$contents[ $key ]->access = $this->get_rule_value( $key );
		}

		$filter = $this->get_exclude_include( $args );
		if ( is_array( $filter->include ) ) {
			$contents = array_intersect_key( $contents, array_flip( $filter->include ) );
		} elseif ( is_array( $filter->exclude ) ) {
			$contents = array_diff_key( $contents, array_flip( $filter->exclude ) );
		}

		if ( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return apply_filters(
			'ms_rule_shortcode_model_get_contents',
			$contents
		);
	}

}