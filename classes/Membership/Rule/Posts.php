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
 * Rule class responsible for posts protection.
 *
 * @category Membership
 * @package Rule
 */
class Membership_Rule_Posts extends Membership_Rule {

	var $name = 'posts';
	var $label = 'Posts';
	var $description = 'Allows specific posts to be protected.';

	var $rulearea = 'public';

	function admin_main($data) {
		global $M_options;

		if ( !$data ) {
			$data = array();
		}

		$posts_to_show = !empty( $M_options['membership_post_count'] ) ? $M_options['membership_post_count'] : MEMBERSHIP_POST_COUNT;
		$posts = get_posts( array(
			'numberposts' => $posts_to_show,
			'offset'      => 0,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'post',
			'post_status' => 'publish'
		) );

		?><div class='level-operation' id='main-posts'>
			<h2 class='sidebar-name'><?php _e('Posts', 'membership');?><span><a href='#remove' id='remove-posts' class='removelink' title='<?php _e("Remove Posts from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the posts to be covered by this rule by checking the box next to the relevant posts title.','membership'); ?></p>
				<?php if ( $posts ) : ?>
					<table cellspacing="0" class="widefat fixed">
						<thead>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Post title', 'membership'); ?></th>
							<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Post date', 'membership'); ?></th>
						</tr>
						</thead>

						<tfoot>
						<tr>
							<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
							<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Post title', 'membership'); ?></th>
							<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Post date', 'membership'); ?></th>
						</tr>
						</tfoot>

						<tbody>
						<?php foreach( $posts as $post ) : ?>
							<tr valign="middle" class="alternate" id="post-<?php echo $post->ID; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $post->ID; ?>" name="posts[]"<?php checked( in_array( $post->ID, $data ) ) ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($post->post_title); ?></strong>
								</td>
								<td class="column-date">
									<?php echo date( 'd M y', strtotime( $post->post_date ) ); ?>
								</td>
						    </tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<p class='description'><?php printf( __( "Only the most recent %d posts are shown above, if you have more than that then you should consider using categories instead.", 'membership' ), $posts_to_show ) ?></p>
			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
		add_action( 'pre_get_posts', array( $this, 'add_viewable_posts' ), 99 );
	}

	function on_negative( $data ) {
		$this->data = array_filter( array_map( 'intval', (array)$data ) );
		add_action( 'pre_get_posts', array( $this, 'add_unviewable_posts' ), 99 );
	}

	function add_viewable_posts( $wp_query ) {
		if ( !$wp_query->is_singular && empty( $wp_query->query_vars['pagename'] ) && ( !isset( $wp_query->query_vars['post_type'] ) || in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) )) ) {

			// We are in a list rather than on a single post
			foreach ( (array) $this->data as $key => $value ) {
				$wp_query->query_vars['post__in'][] = $value;
			}

			$wp_query->query_vars['post__in'] = array_unique( $wp_query->query_vars['post__in'] );
		}
	}

	function add_unviewable_posts( $wp_query ) {
		if ( !$wp_query->is_singular && empty( $wp_query->query_vars['pagename'] ) && ( !isset( $wp_query->query_vars['post_type'] ) || in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) ) ) ) {

			// We are on a list rather than on a single post
			foreach ( (array) $this->data as $key => $value ) {
				$wp_query->query_vars['post__not_in'][] = $value;
			}

			$wp_query->query_vars['post__not_in'] = array_unique( $wp_query->query_vars['post__not_in'] );
		}
	}

	function validate_negative() {
		if ( !is_single() || is_attachment() ) {
			return parent::validate_negative();
		}

		return !in_array( get_the_ID(), $this->data );
	}

	function validate_positive() {
		if ( !is_single() || is_attachment() ) {
			return parent::validate_positive();
		}

		return in_array( get_the_ID(), $this->data );
	}

}