<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Rule class responsible for comments protection.
 *
 * @category Membership
 * @package Rule
 */
class Membership_Rule_Comments extends Membership_Rule {

	var $name = 'comments';
	var $label = 'Comments';
	var $description = 'Allows the display of, or ability to comment on posts to be protected.';

	var $rulearea = 'public';

	function admin_main( $data ) {
		?><div id="main-comments" class="level-operation">
			<h2 class="sidebar-name">
				<?php _e( 'Comments', 'membership' ) ?>
				<span>
					<a id="remove-comments" class="removelink" href="#remove" title=""<?php _e( "Remove Comments from this rules area.", 'membership' ) ?>"><?php
						_e( 'Remove', 'membership' )
					?></a>
				</span>
			</h2>
			<div class="inner-operation">
				<p><strong><?php _e( 'Positive : ', 'membership' ) ?></strong><?php _e( 'User gets read and make comments of posts.', 'membership' ) ?></p>
				<p><strong><?php _e( 'Negative : ', 'membership' ) ?></strong><?php _e( 'User can not read or comment on posts.', 'membership' ) ?></p>
				<input type='hidden' name='comments[]' value='yes' />
			</div>
		</div><?php
	}

	function on_positive( $data ) {
		$this->data = $data;
		add_filter( 'comments_open', array( $this, 'open_comments' ), 99 );
	}

	function on_negative( $data ) {
		$this->data = $data;

		add_filter( 'comments_open', '__return_false', 99 );
		if ( !defined( 'MEMBERSHIP_VIEW_COMMENTS' ) || !filter_var( MEMBERSHIP_VIEW_COMMENTS, FILTER_VALIDATE_BOOLEAN )  ) {
			add_filter( 'comments_array', '__return_empty_array', 99 );
		}
	}

	function open_comments( $open ) {
		return $open;
	}

}