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
 * Rule class responsible for categories protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_Categories extends Membership_Model_Rule {

	/**
	 * Handles rule's stuff initialization.
	 *
	 * @access public
	 */
	public function on_creation() {
		parent::on_creation();

		$this->name = 'categories';
		$this->label = __( 'Categories', 'membership' );
		$this->description = __( 'Allows posts to be protected based on their assigned categories.', 'membership' );
		$this->rulearea = 'public';
	}

	/**
	 * Renders rule settings at access level edit form.
	 *
	 * @access public
	 * @param mixed $data The data associated with this rule.
	 */
	public function admin_main( $data ) {
		$categories = get_categories( 'get=all' );
		if ( !$data ) {
			$data = array();
		}

		?><div class="level-operation" id="main-categories">
			<h2 class="sidebar-name"><?php _e( 'Categories', 'membership' ) ?>
				<span>
					<a href='#remove' class="removelink" id="remove-categories" title="<?php _e( "Remove Categories from this rules area.", 'membership' ) ?>">
						<?php _e( 'Remove', 'membership' ) ?>
					</a>
				</span>
			</h2>

			<div class="inner-operation">
				<p><?php _e( 'Select the Categories to be covered by this rule by checking the box next to the relevant categories name.', 'membership' ) ?></p>

				<?php if ( $categories ) : ?>
				<table cellspacing="0" class="widefat fixed">
					<thead>
						<tr>
							<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Category name', 'membership' ) ?></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th class="manage-column column-name" id="name" scope="col"><?php _e( 'Category name', 'membership' ) ?></th>
						</tr>
					</tfoot>

					<tbody>
						<?php foreach ( $categories as $category ) : ?>
						<tr valign="middle" class="alternate" id="post-<?php echo $category->term_id ?>">
							<th class="check-column" scope="row">
								<input type="checkbox" value="<?php echo $category->term_id ?>" name="categories[]"<?php checked( in_array( $category->term_id, $data ) ) ?>>
							</th>
							<td class="column-name">
								<strong><?php echo esc_html( $category->name ) ?></strong>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div><?php
	}

	/**
	 * Associates positive data with this rule.
	 *
	 * @access public
	 * @param mixed $data The positive data to associate with the rule.
	 */
	public function on_positive( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
		
		add_action( 'pre_get_posts', array( $this, 'filter_viewable_posts' ), 1 );
		add_filter( 'get_terms', array( $this, 'filter_viewable_categories' ), 1 );
	}

	/**
	 * Associates negative data with this rule.
	 *
	 * @access public
	 * @param mixed $data The negative data to associate with the rule.
	 */
	public function on_negative( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );

		add_action( 'pre_get_posts', array( $this, 'filter_unviewable_posts' ), 1 );
		add_filter( 'get_terms', array( $this, 'filter_unviewable_categories' ), 1 );
	}

	/**
	 * Adds category__in filter for posts query to remove all posts which not
	 * belong to allowed categories.
	 *
	 * @sicne 3.5
	 * @action pre_get_posts 1
	 *
	 * @access public
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function filter_viewable_posts( WP_Query $query ) {
		if ( isset($query->post_type) && $query->post_type == 'post' )	//don't apply these rules to custom post types!
			$query->query_vars['category__in'] = array_unique( array_merge( $query->query_vars['category__in'], $this->data ) );
	}

	/**
	 * Adds category__not_in filter for posts query to remove all posts which
	 * belong to prohibited categories.
	 *
	 * @sicne 3.5
	 * @action pre_get_posts 1
	 *
	 * @access public
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function filter_unviewable_posts( WP_Query $wp_query ) {
		if ( isset($wp_query->post_type) && $wp_query->post_type == 'post' )	//don't apply these rules to custom post types!
			$wp_query->query_vars['category__not_in'] = array_unique( array_merge( $wp_query->query_vars['category__not_in'], $this->data ) );
	}

	/**
	 * Filters categories and removes all not accessible categories.
	 *
	 * @sicne 3.5
	 *
	 * @access public
	 * @param array $terms The terms array.
	 * @return array Fitlered terms array.
	 */
	public function filter_viewable_categories( $terms ) {
		$new_terms = array();
		foreach ( (array)$terms as $key => $term ) {
			if ( $term->taxonomy == 'category' ) {
				if ( in_array( $term->term_id, $this->data ) ) {
					$new_terms[$key] = $term;
				}
			}
		}

		return $new_terms;
	}

	/**
	 * Filters categories and removes all not accessible categories.
	 *
	 * @sicne 3.5
	 *
	 * @access public
	 * @param array $terms The terms array.
	 * @return array Fitlered terms array.
	 */
	public function filter_unviewable_categories( $terms ) {
		$new_terms = array();
		foreach ( (array)$terms as $key => $term ) {
			if ( $term->taxonomy == 'category' ) {
				if ( !in_array( $term->term_id, $this->data ) ) {
					$new_terms[$key] = $term;
				}
			}
		}

		return $new_terms;
	}

	/**
	 * Validates the rule on negative assertion.
	 *
	 * @access public
	 * @return boolean TRUE if assertion is successfull, otherwise FALSE.
	 */
	public function validate_negative() {
		if ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( get_the_ID() );
			$intersect = array_intersect( $categories, $this->data );
			return empty( $intersect );
		}

		if ( is_category() ) {
			return !in_array( get_queried_object_id(), $this->data );
		}

		return parent::validate_negative();
	}

	/**
	 * Validates the rule on positive assertion.
	 *
	 * @access public
	 * @return boolean TRUE if assertion is successfull, otherwise FALSE.
	 */
	public function validate_positive() {
		if ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( get_the_ID() );
			$intersect = array_intersect( $categories, $this->data );
			return !empty( $intersect );
		}

		if ( is_category() ) {
			return in_array( get_queried_object_id(), $this->data );
		}
		
		return parent::validate_positive();
	}
}