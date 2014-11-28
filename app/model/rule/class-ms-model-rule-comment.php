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
	 * This rule will return NULL (not relevant), because the comments are
	 * protected via WordPress hooks instead of protecting the whole page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id = null ) {
		return apply_filters(
			'ms_model_rule_comment_has_access',
			null,
			$id,
			$this
		);
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
		$value = 0;
		if ( isset( $this->rule_value[ $id ] ) ) {
			$value = $this->rule_value[ $id ];
		}

		return apply_filters(
			'ms_model_rule_comment_get_rule_value',
			$value,
			$id,
			$this
		);
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

		/*
		 * This is a static variable so it can collect the most generous
		 * permission of any rule that is applied for the current user.
		 */
		if ( self::$comment_access > $rule_value ) {
			self::$comment_access = $rule_value;
		}

		$this->add_action(
			'ms_model_plugin_setup_protection_after',
			'protect_comments'
		);
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
				// Don't change the inherent comment status.
				break;

			case self::RULE_VALUE_READ:
				$this->add_filter( 'comment_form_before', 'hide_form_start', 1 );
				$this->add_filter( 'comment_form_after', 'hide_form_end', 99 );
				add_filter( 'comment_reply_link', '__return_null', 99 );
				break;

			case self::RULE_VALUE_NO_ACCESS:
				add_filter( 'comments_open', '__return_false', 99 );
				add_filter( 'get_comments_number', '__return_zero', 99 );
				break;
		}
	}

	/**
	 * Before the comment form is output we start buffering.
	 *
	 * @since  1.0.4.4
	 */
	public function hide_form_start() {
		ob_start();
	}

	/**
	 * At the end of the comment form we clear the buffer: The form is gone!
	 *
	 * @since  1.0.4.4
	 */
	public function hide_form_end() {
		ob_end_clean();
	}

	/**
	 * Close comments for membership special pages.
	 *
	 * Related Action Hooks:
	 * - the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The content to filter.
	 */
	public function check_special_page( $content ) {
		if ( MS_Factory::load( 'MS_Model_Pages' )->is_ms_page() ) {
			add_filter( 'comments_open', '__return_false', 100 );
		}

		return apply_filters(
			'ms_model_rule_comment_check_special_page',
			$content,
			$this
		);
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

		return apply_filters(
			'ms_model_rule_comment_count_rules',
			$count,
			$this
		);
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

		return apply_filters(
			'ms_model_rule_comment_get_content',
			$contents,
			$args,
			$this
		);
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

		return apply_filters(
			'ms_model_rule_comment_get_content_array',
			$contents,
			$this
		);
	}

}