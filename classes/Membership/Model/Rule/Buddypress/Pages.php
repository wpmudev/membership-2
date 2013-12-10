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
 * Rule class responsible for BuddyPress pages protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Buddypress
 */
class Membership_Model_Rule_Buddypress_Pages extends Membership_Model_Rule {

	var $name = 'bppages';
	var $label = 'BuddyPress Pages';
	var $description = 'Allows specific BuddyPress pages to be protected.';

	var $rulearea = 'public';

	function on_bp_page() {

	}

	function get_pages() {
		global $bp;

		$directory_pages = array();
		foreach ( $bp->loaded_components as $component_id ) {
			// Only components that need directories should be listed here
			if ( isset( $bp->{$component_id} ) && !empty( $bp->{$component_id}->has_directory ) ) {
				// component->name was introduced in BP 1.5, so we must provide a fallback
				$component_name = !empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );
				$directory_pages[$component_id] = $component_name;
			}
		}

		return apply_filters( 'bp_directory_pages', $directory_pages );
	}

	function admin_main($data) {

		global $bp;

		if(!$data) $data = array();

		$existing_pages = bp_core_get_directory_page_ids();

		$directory_pages = $this->get_pages();

		?>
		<div class='level-operation' id='main-bppages'>
			<h2 class='sidebar-name'><?php _e('BuddyPress Pages', 'membership');?><span><a href='#remove' id='remove-bppages' class='removelink' title='<?php _e("Remove BuddyPress Pages from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the BuddyPress Pages to be covered by this rule by checking the box next to the relevant pages title.','membership'); ?></p>
				<?php

					//print_r($existing_pages);

					//print_r($directory_pages);

					if($directory_pages) {
						?>
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
						<?php


						foreach($directory_pages as $key => $page) {

							if ( !empty( $existing_pages[$key] ) ) { ?>

							<tr valign="middle" class="alternate" id="post-<?php echo $post->ID; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $existing_pages[$key]; ?>" name="bppages[]" <?php if(in_array($existing_pages[$key], $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($page); ?></strong>
								</td>
						    </tr>
							<?php
							}
						}
						?>
							</tbody>
						</table>
						<?php
					}

				?>
			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );

		add_action( 'pre_get_posts', array( $this, 'add_viewable_pages' ), 3 );
		add_filter( 'get_pages', array( $this, 'add_viewable_pages_menu' ), 2 );
	}

	function on_negative( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );

		add_action( 'pre_get_posts', array( $this, 'add_unviewable_pages' ), 3 );
		add_filter( 'get_pages', array( $this, 'add_unviewable_pages_menu' ), 2 );
	}

	function add_viewable_pages( $wp_query ) {
		if ( !$wp_query->is_single && !empty( $wp_query->query_vars['post__in'] ) ) {
			// We are not on a single page - so just limit the viewing
			foreach ( (array)$this->data as $value ) {
				$wp_query->query_vars['post__in'][] = $value;
			}

			$wp_query->query_vars['post__in'] = array_unique( $wp_query->query_vars['post__in'] );
		}
	}

	function add_viewable_pages_menu( $pages ) {
		$existing_pages = bp_core_get_directory_page_ids();
		foreach ( (array) $pages as $key => $page ) {
			if ( !in_array( $page->ID, (array)$this->data ) && in_array( $page->ID, (array)$existing_pages ) ) {
				unset( $pages[$key] );
			}
		}

		return $pages;
	}

	function add_unviewable_pages( $wp_query ) {
		if ( !$wp_query->is_single ) {
			// We are not on a single page - so just limit the viewing
			foreach ( (array) $this->data as $key => $value ) {
				$wp_query->query_vars['post__not_in'][] = $value;
			}

			$wp_query->query_vars['post__not_in'] = array_unique( $wp_query->query_vars['post__not_in'] );
		}
	}

	function add_unviewable_pages_menu( $pages ) {
		foreach ( (array) $pages as $key => $page ) {
			if ( in_array( $page->ID, (array) $this->data ) ) {
				unset( $pages[$key] );
			}
		}

		return $pages;
	}

	function validate_negative() {
		$component = bp_current_component();
		if ( $component ) {
			$pages = bp_core_get_directory_page_ids();
			if ( !empty( $pages[$component] ) ) {
				return !in_array( $pages[$component], $this->data );
			}
		}
		return parent::validate_negative();
	}

	function validate_positive() {
		$component = bp_current_component();
		if ( $component ) {
			$pages = bp_core_get_directory_page_ids();
			if ( !empty( $pages[$component] ) ) {
				return in_array( $pages[$component], $this->data );
			}
		}
		return parent::validate_positive();
	}

}