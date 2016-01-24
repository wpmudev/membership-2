<?php
/**
 * Membership Comment Rule class.
 *
 * Persisted by Membership class.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Rule_Content_Model extends MS_Rule {

	/**
	 * Rule type.
	 *
	 * @since  1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Rule_Content::RULE_ID;

	/**
	 * Available special pages
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	protected $_content = null;

	/**
	 * Comment content ID.
	 *
	 * @since  1.0.0
	 *
	 * @var string $content_id
	 */
	const CONTENT_ID = 'content';

	/**
	 * Rule value constants.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	const COMMENT_NO_ACCESS = 'cmt_none';
	const COMMENT_READ = 'cmt_read';
	const COMMENT_WRITE = 'cmt_full';
	const MORE_LIMIT = 'no_more';

	/**
	 * Flag of the final comment access level.
	 * When an user is member of multiple memberships with different
	 * comment-access-restrictions then the MOST GENEROUS access will be granted.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected static $comment_access = self::COMMENT_NO_ACCESS;

	/**
	 * Set to true, if the user did not specify any comment protection.
	 * This means that the default logic should be used...
	 *
	 * @since  1.0.0
	 *
	 * @var bool
	 */
	protected static $comment_public = null;

	/**
	 * The message displayed below the "read more" mark.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	protected $protection_message = '';

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because the comments are
	 * protected via WordPress hooks instead of protecting the whole page.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public function protect_content() {
		parent::protect_content();

		// ********* COMMENTS **********

		// No comments on special pages (signup, account, ...)
		$this->add_filter( 'the_content', 'check_special_page' );

		/*
		 * We find the public comment access once.
		 * This is the access ganted to guests or memberships that do not define
		 * an explicit comment access rule.
		 */
		if ( null === self::$comment_public ) {
			$base_rule = MS_Model_Membership::get_base()->get_rule( $this->rule_type );
			if ( null === $base_rule->get_rule_value( self::COMMENT_WRITE ) ) {
				self::$comment_public = self::COMMENT_WRITE;
			} elseif ( null === $base_rule->get_rule_value( self::COMMENT_READ ) ) {
				self::$comment_public = self::COMMENT_READ;
			} else {
				self::$comment_public = self::COMMENT_NO_ACCESS;
			}
		}

		// Find the most generous comment access rule.
		$has_full = $this->get_rule_value( self::COMMENT_WRITE );
		$has_read = $this->get_rule_value( self::COMMENT_READ );
		$has_none = $this->get_rule_value( self::COMMENT_NO_ACCESS );

		if ( true === $has_full ) {
			// Membership allows full comment access.
			self::$comment_access = self::COMMENT_WRITE;
		} elseif ( true === $has_read ) {
			// Membership allows read-only access.
			if ( self::$comment_access == self::COMMENT_NO_ACCESS ) {
				self::$comment_access = self::COMMENT_READ;
			}
		} elseif ( true === $has_none ) {
			// Membership does not allow any comment access.
			// (no change, this is the default access level)
		} else {
			// This membership does not define a comment access: Use public access!
			self::$comment_access = self::$comment_public;
		}

		$this->add_action(
			'ms_setup_protection_done',
			'protect_comments'
		);

		// ********** READ MORE **********
                
                if( defined( 'MS_PROTECTED_MESSAGE_REVERSE_RULE' ) && MS_PROTECTED_MESSAGE_REVERSE_RULE ) {
                    
                    $rule = MS_Factory::load( 'MS_Rule_Content_Model' );
                    $allowed_memberships = $rule->get_memberships( self::MORE_LIMIT );
                    if( ! is_array( $allowed_memberships ) ) $allowed_memberships = array();
                    
                    $sorted_membership = array();
                    foreach( $allowed_memberships as $allowed_membership_id => $allowed_membership_name ) {
                        $m_obj = MS_Factory::load( 'MS_Model_Membership', $allowed_membership_id );
                        $key = $m_obj->priority;
                        
                        while( array_key_exists( $key, $sorted_membership ) ) {
                            $key++;
                        }
                        
                        $sorted_membership[$key] = $allowed_membership_id;
                    }
                    $protected_membership_id = reset( $sorted_membership );
                    
                }else{
                    $protected_membership_id = $this->membership_id;
                }
                
		$this->protection_message = MS_Plugin::instance()->settings->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_MORE_TAG,
			//$this->membership_id
                        $protected_membership_id
		);

		if ( ! parent::has_access( self::MORE_LIMIT ) ) {
			$this->add_filter( 'the_content_more_link', 'show_moretag_protection', 99, 2 );
			$this->add_filter( 'the_content', 'replace_more_tag_content', 1 );
			$this->add_filter( 'the_content_feed', 'replace_more_tag_content', 1 );
		}
	}

	// ********* COMMENTS **********

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
			case self::COMMENT_WRITE:
				// Don't change the inherent comment status.
				break;

			case self::COMMENT_READ:
				$this->add_filter( 'comment_form_before', 'hide_form_start', 1 );
				$this->add_filter( 'comment_form_after', 'hide_form_end', 99 );
				add_filter( 'comment_reply_link', '__return_null', 99 );
				break;

			case self::COMMENT_NO_ACCESS:
				add_filter( 'comments_open', '__return_false', 99 );
				add_filter( 'get_comments_number', '__return_zero', 99 );
				break;
		}
	}

	/**
	 * Before the comment form is output we start buffering.
	 *
	 * @since  1.0.0
	 */
	public function hide_form_start() {
		ob_start();
	}

	/**
	 * At the end of the comment form we clear the buffer: The form is gone!
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @param string $content The content to filter.
	 */
	public function check_special_page( $content ) {
		if ( MS_Model_Pages::is_membership_page() ) {
			add_filter( 'comments_open', '__return_false', 100 );
		}

		return apply_filters(
			'ms_rule_content_model_check_special_page',
			$content,
			$this
		);
	}

	// ********** READ MORE **********

	/**
	 * Show more tag protection message.
	 *
	 * Related Action Hooks:
	 * - the_content_more_link
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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

	// ********** ADMIN FUNCTIONS **********

	/**
	 * Returns a list of special pages that can be configured by this rule.
	 *
	 * @since  1.0.0
	 *
	 * @param  bool $flat If set to true then all pages are in the same
	 *      hierarchy (no sub-arrays).
	 * @return array List of special pages.
	 */
	protected function get_rule_items() {
		if ( ! is_array( $this->_content ) ) {
			$this->_content = array();

			$this->_content[self::COMMENT_NO_ACCESS] = (object) array(
				'label' => __( 'Comments: No Access', 'membership2' ),
			);
			$this->_content[self::COMMENT_READ] = (object) array(
				'label' => __( 'Comments: Read Only Access', 'membership2' ),
			);
			$this->_content[self::COMMENT_WRITE] = (object) array(
				'label' => __( 'Comments: Read and Write Access', 'membership2' ),
			);
			$this->_content[self::MORE_LIMIT] = (object) array(
				'label' => __( 'Hide "read more" content', 'membership2' ),
			);
		}

		return $this->_content;
	}

	/**
	 * Count protection rules quantity.
	 *
	 * @since  1.0.0
	 *
	 * @return int $count The rule count result.
	 */
	public function count_rules( $args = null ) {
		$count = count( $this->get_contents( $args ) );

		return apply_filters(
			'ms_rule_content_model_count_rules',
			$count,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since  1.0.0
	 *
	 * @param $args Optional. Not used.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$items = $this->get_rule_items();
		$contents = array();

		foreach ( $items as $key => $data ) {
			$content = (object) array();

			// Search the special page name...
			if ( ! empty( $args['s'] ) ) {
				if ( false === stripos( $data->label, $args['s'] ) ) {
					continue;
				}
			}

			$content->id = $key;
			$content->type = MS_Rule_Content::RULE_ID;
			$content->name = $data->label;
			$content->post_title = $data->label;
			$content->access = $this->get_rule_value( $content->id );

			$contents[ $content->id ] = $content;
		}

		return apply_filters(
			'ms_rule_content_model_get_content',
			$contents,
			$args,
			$this
		);
	}

}