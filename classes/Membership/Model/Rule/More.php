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
 * Rule class responsible for protection of "Read more" section.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_More extends Membership_Model_Rule {

	var $name = 'more';
	var $label = 'More tag';
	var $description = 'Allows content placed after the More tag to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-more'>
			<h2 class='sidebar-name'><?php _e('More tag', 'membership');?><span><a href='#remove' class='removelink' id='remove-more' title='<?php _e("Remove More tag from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can read full post content beyond the More tag.','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to read full post content beyond the More tag.','membership'); ?></p>
				<input type='hidden' name='more[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		global $M_options, $membershippublic;

		$this->data = $data;

		if ( isset( $M_options['moretagdefault'] ) && $M_options['moretagdefault'] == 'no' ) {
			remove_filter( 'the_content_more_link', array( $membershippublic, 'show_moretag_protection' ), 99, 2 );
			remove_filter( 'the_content', array( $membershippublic, 'replace_moretag_content' ), 1 );
			remove_filter( 'the_content_feed', array( $membershippublic, 'replace_moretag_content' ), 1 );
		}
	}

	function on_negative( $data ) {
		global $M_options, $membershippublic;

		$this->data = $data;
		if ( isset( $M_options['moretagdefault'] ) && $M_options['moretagdefault'] != 'no' ) {
			// add the filters - otherwise we don't need to do anything
			add_filter( 'the_content_more_link', array( $membershippublic, 'show_moretag_protection' ), 99, 2 );
			add_filter( 'the_content', array( $membershippublic, 'replace_moretag_content' ), 1 );
			add_filter( 'the_content_feed', array( $membershippublic, 'replace_moretag_content' ), 1 );
		}
	}

}