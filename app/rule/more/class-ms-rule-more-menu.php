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
 * Membership More tag Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_More_Model extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_MORE_TAG;

	/**
	 * Comment content ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string $content_id
	 */
	const CONTENT_ID = 'more_tag';

	/**
	 * Protection message to display.
	 *
	 * @since 1.0.0
	 *
	 * @var string $protection_message
	 */
	protected $protection_message;

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because the content is
	 * replaced inside the page content instead of protecting the whole page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id = null ) {
		return apply_filters(
			'ms_rule_more_model_has_access',
			null,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->protection_message = MS_Plugin::instance()->settings->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_MORE_TAG
		);

		if ( ! parent::has_access( self::CONTENT_ID ) ) {
			$this->add_filter( 'the_content_more_link', 'show_moretag_protection', 99, 2 );
			$this->add_filter( 'the_content', 'replace_more_tag_content', 1 );
			$this->add_filter( 'the_content_feed', 'replace_more_tag_content', 1 );
		}
	}

	/**
	 * Show more tag protection message.
	 *
	 * Related Action Hooks:
	 * - the_content_more_link
	 *
	 * @since 1.0.0
	 *
	 * @param string $more_tag_link the more tag link before filter.
	 * @param string $more_tag The more tag content before filter.
	 * @return string The protection message.
	 */
	public function show_moretag_protection( $more_tag_link, $more_tag ) {
		$msg = stripslashes( $this->protection_message );

		return apply_filters(
			'ms_rule_more_model_show_moretag_protection',
			$msg,
			$more_tag_link,
			$more_tag,
			$this
		);
	}

	/**
	 * Replace more tag
	 *
	 * Related Action Hooks:
	 * - the_content
	 * - the_content_feed
	 *
	 * @since 1.0.0
	 *
	 * @param string $the_content The post content before filter.
	 * @return string The content replaced by more tag content.
	 */
	public function replace_more_tag_content( $the_content ) {
		$more_starts_at = strpos( $the_content, '<span id="more-' );

		if ( false !== $more_starts_at ) {
			$the_content = substr( $the_content, 0, $more_starts_at );
			$the_content .= stripslashes( $this->protection_message );
		}

		return apply_filters(
			'ms_rule_more_model_replace_more_tag_content',
			$the_content,
			$this
		);
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
		$contents = array();

		if ( $this->get_rule_value( self::CONTENT_ID ) ) {
			$content = new StdClass();
			$content->id = self::CONTENT_ID;
			$content->name = __( 'User can read full post content beyond the More tag.', MS_TEXT_DOMAIN );
			$content->access = $this->get_rule_value( $content->id );

			$contents[] = $content;
		}

		return apply_filters(
			'ms_rule_more_model_get_content',
			$contents,
			$this
		);
	}

	/**
	 * Get options array.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Optional. Not used.
	 * @return array {
	 *     @type string $rule_value The rule value.
	 *     @type string $description The rule description.
	 * }
	 */
	public function get_options_array( $args = null ) {
		$contents = array(
			true => __( 'Yes', MS_TEXT_DOMAIN ),
			false => __( 'No', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_rule_more_model_get_content_array',
			$contents,
			$this
		);
	}
}