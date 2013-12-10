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
 * The rule class responsible for access to general pages.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_Pages extends Membership_Model_Rule {

	/**
	 * Renders rule settings at access level edit form.
	 *
	 * @access public
	 * @param mixed $data The data associated with this rule.
	 */
	public function admin_main( $data ) {
		global $M_options;

		if ( !$data ) {
			$data = array();
		}

		$exclude = array();
		foreach ( array( 'registration_page', 'account_page', 'subscriptions_page', 'nocontent_page', 'registrationcompleted_page' ) as $page ) {
			if ( isset( $M_options[$page] ) && is_numeric( $M_options[$page] ) ) {
				$exclude[] = $M_options[$page];
			}
		}

		$posts_to_show = !empty( $M_options['membership_page_count'] ) ? $M_options['membership_page_count'] : MEMBERSHIP_PAGE_COUNT;
		$posts = apply_filters( 'staypress_hide_protectable_pages', get_posts( array(
			'numberposts' => $posts_to_show,
			'offset'      => 0,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'page',
			'post_status' => 'publish',
			'exclude'     => $exclude,
		) ) );

		?>
		<div id="main-pages" class="level-operation">
			<h2 class="sidebar-name">
				<?php _e( 'Pages', 'membership' ) ?>
				<span>
					<a id="remove-pages" href="#remove" class="removelink" title="<?php _e( "Remove Pages from this rules area.", 'membership' ) ?>"><?php
						_e( 'Remove', 'membership' )
					?></a>
				</span>
			</h2>

			<div class="inner-operation">
				<p><?php _e( 'Select the Pages to be covered by this rule by checking the box next to the relevant pages title. Pay attention that pages selected as Membership page (in the options) are not listed below.', 'membership' ) ?></p>

				<?php if ( $posts ) : ?>
				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
						<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Page title', 'membership'); ?></th>
						</tr>
					</thead>

					<tfoot>
					<tr>
						<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Page title', 'membership'); ?></th>
						</tr>
					</tfoot>

					<tbody>
						<?php foreach ( $posts as $post ) : ?>
							<?php if ( membership_is_special_page( $post->ID, false ) ) continue; ?>
							<tr valign="middle" class="alternate" id="post-<?php echo $post->ID ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $post->ID ?>" name="pages[]"<?php checked( in_array( $post->ID, $data ) ) ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html( $post->post_title ) ?></strong>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>

				<p class="description"><?php printf( __( "Only the most recent %d pages are shown above.", 'membership' ), $posts_to_show ) ?></p>
			</div>
		</div><?php
	}

	/**
	 * Handles rule's stuff initialization.
	 *
	 * @access public
	 */
	public function on_creation() {
		$this->name = 'pages';
		$this->rulearea = 'public';

		$this->label = esc_html__( 'Pages', 'membership' );
		$this->description = esc_html__( 'Allows specific pages to be protected.', 'membership' );
	}

	/**
	 * Associates positive data with this rule.
	 *
	 * @access public
	 * @param mixed $data The positive data to associate with the rule.
	 */
	public function on_positive( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
		add_filter( 'get_pages', array( $this, 'add_viewable_pages_menu' ), 1 );
	}

	/**
	 * Associates negative data with this rule.
	 *
	 * @access public
	 * @param mixed $data The negative data to associate with the rule.
	 */
	public function on_negative( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
		add_filter( 'get_pages', array( $this, 'add_unviewable_pages_menu' ), 1 );
	}

	/**
	 * Filets protected pages from array.
	 *
	 * @action get_pages 1
	 *
	 * @param array $pages The array of pages.
	 * @return array Filtered array which doesn't include prohibited pages.
	 */
	public function add_viewable_pages_menu( $pages ) {
		$override_pages = apply_filters( 'membership_override_viewable_pages_menu', array() );

		foreach ( (array)$pages as $key => $page ) {
			if ( !in_array( $page->ID, (array) $this->data ) && !in_array( $page->ID, (array) $override_pages ) ) {
				unset( $pages[$key] );
			}
		}

		return $pages;
	}

	/**
	 * Filets protected pages from array.
	 *
	 * @action get_pages 1
	 *
	 * @param array $pages The array of pages.
	 * @return array Filtered array which doesn't include prohibited pages.
	 */
	public function add_unviewable_pages_menu( $pages ) {
		foreach ( (array) $pages as $key => $page ) {
			if ( in_array( $page->ID, (array) $this->data ) ) {
				unset( $pages[$key] );
			}
		}

		return $pages;
	}

	/**
	 * Validates the rule on negative assertion.
	 *
	 * @access public
	 * @return boolean TRUE if assertion is successfull, otherwise FALSE.
	 */
	public function validate_negative() {
		$page = get_queried_object();
		return is_a( $page, 'WP_Post' ) && $page->post_type == 'page'
			? !in_array( $page->ID, $this->data )
			: parent::validate_negative();
	}

	/**
	 * Validates the rule on positive assertion.
	 *
	 * @access public
	 * @return boolean TRUE if assertion is successfull, otherwise FALSE.
	 */
	public function validate_positive() {
		$page = get_queried_object();
		return is_a( $page, 'WP_Post' ) && $page->post_type == 'page'
			? in_array( $page->ID, $this->data )
			: parent::validate_positive();
	}

}