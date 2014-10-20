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
 * Membership Comment Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Comment extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_COMMENT;

	/**
	 * Comment content ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string $content_id
	 */
	const CONTENT_ID = 'comment';

	/**
	 * Rule value constants.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	const RULE_VALUE_NO_ACCESS = 2;
	const RULE_VALUE_READ = 1;
	const RULE_VALUE_WRITE = 0;

	/**
	 * Flag of the final comment access level.
	 * When an user is member of multiple memberships with different
	 * comment-access-restrictions then the MOST GENEROUS access will be granted.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected static $comment_access = self::RULE_VALUE_NO_ACCESS;


	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_access( $id = null ) {
		return apply_filters( 'ms_model_rule_comment_has_access', false, $id, $this );
	}

	/**
	 * Get rule value for a specific content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to get rule value for.
	 * @return boolean The rule value for the requested content. Default $rule_value_default.
	 */
	public function get_rule_value( $id ) {
		$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : 0;

		return apply_filters( 'ms_model_rule_comment_get_rule_value', $value, $id, $this );
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. Not used.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		// No comments on special pages (signup, account, ...)
		$this->add_filter( 'the_content', 'check_special_page' );

		$rule_value = $this->get_rule_value( self::CONTENT_ID );

		if ( self::$comment_access > $rule_value ) {
			self::$comment_access = $rule_value;
		}

		$this->add_action( 'ms_model_plugin_setup_protection_after', 'protect_comments' );
	}

	/**
	 * Setup the comment permissions after all membership rules were parsed.
	 *
	 * @since  1.0.0
	 */
	public function protect_comments() {
		static $Done = false;

		if ( $Done ) { return; }
		$Done = true;

		switch ( self::$comment_access ) {
			case self::RULE_VALUE_WRITE:
				add_filter( 'comments_open', '__return_true', 99 );
				break;

			case self::RULE_VALUE_READ:
				$this->add_filter( 'comment_reply_link', 'comment_reply_link', 99 );
				$this->add_filter( 'comments_open', 'read_only_comments', 99 );
				break;

			case self::RULE_VALUE_NO_ACCESS:
				add_filter( 'comments_open', '__return_false', 99 );
				$this->add_filter( 'get_comments_number', 'get_comments_number' );
				break;
		}
	}

	/**
	 * Workaround to enable read only comments.
	 *
	 * @todo find a better way to allow read only comments.
	 *
	 * **Hooks Filters: **
	 *
	 * * comments_open
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $open The open status before filter.
	 * @return boolean The open status after filter.
	 */
	public function read_only_comments( $open ) {
		$traces = MS_Helper_Debug::debug_trace( true );

		if ( false !== strpos( $traces, 'function: comment_form' ) ) {
			$open = false;
		}

		return apply_filters( 'ms_model_rule_comment_read_only_comments', $open, $this );
	}

	/**
	 * Workaround to hide reply link when in read only mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link The reply link before filter.
	 * @return string The reply (blank) link after filter.
	 */
	public function comment_reply_link( $link ) {
		return apply_filters( 'ms_model_rule_comment_comment_reply_link', '', $this );
	}

	/**
	 * Workaround to hide existing comments.
	 *
	 * **Hooks Filters: **
	 *
	 * * get_comments_number
	 *
	 * @since 1.0
	 *
	 * @return int The zero count.
	 */
	public function get_comments_number() {
		return apply_filters( 'ms_model_rule_comment_get_comments_number', 0, $this );
	}

	/**
	 * Close comments for membership special pages.
	 *
	 * **Hooks Filters: **
	 *
	 * * the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The content to filter.
	 */
	public function check_special_page( $content ) {
		if ( MS_Factory::load( 'MS_Model_Pages' )->is_ms_page() ) {
			add_filter( 'comments_open', '__return_false', 100 );
		}

		return apply_filters( 'ms_model_rule_comment_check_special_page', $content, $this );
	}

	/**
	 * Count protection rules quantity.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $has_access_only Optional. Count rules for has_access status only.
	 * @return int $count The rule count result.
	 */
	public function count_rules( $has_access_only = true ) {
		$count = 0;
		$count = count( $this->rule_value );

		return apply_filters( 'ms_model_rule_comment_count_rules', $count, $this );
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Optional. Not used.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$contents = array();

		if ( count( $this->rule_value ) > 0 ) {
			$rule_value = $this->get_rule_value( self::CONTENT_ID );
			$content_array = $this->get_content_array();
			$content = new StdClass();
			$content->id = self::CONTENT_ID;
			$content->name = $content_array[ $rule_value ];
			$content->access = true;
			$contents[] = $content;
		}

		return apply_filters( 'ms_model_rule_comment_get_content', $contents, $args, $this );
	}

	/**
	 * Get content array.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Optional. Not used.
	 * @return array {
	 *     @type string $rule_value The rule value.
	 *     @type string $description The rule description.
	 * }
	 */
	public function get_content_array( $args = null ) {
		$contents = array(
			self::RULE_VALUE_WRITE => __( 'Read and Write Access', MS_TEXT_DOMAIN ),
			self::RULE_VALUE_READ => __( 'Read Only Access', MS_TEXT_DOMAIN ),
			self::RULE_VALUE_NO_ACCESS => __( 'No Access to Comments', MS_TEXT_DOMAIN ),
		);

		return apply_filters( 'ms_model_rule_comment_get_content_array', $contents, $this );
	}

}