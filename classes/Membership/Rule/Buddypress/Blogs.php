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
 * Rule class responsible for BuddyPress blogs protection.
 *
 * @category Membership
 * @package Rule
 * @subpackage Buddypress
 */
class Membership_Rule_Buddypress_Blogs extends Membership_Rule {

	var $name = 'bpblogs';
	var $label = 'Blogs';
	var $description = 'Allows the protection of specific blogs.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpblogs'>
			<h2 class='sidebar-name'><?php _e('Blogs', 'membership');?><span><a href='#remove' id='remove-bpblogs' class='removelink' title='<?php _e("Remove Blogs from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><?php _e('Select the blogs to be covered by this rule by checking the box next to the relevant blogs name.','membership'); ?></p>
				<?php

					if(function_exists('bp_blogs_get_blogs')) {
						$blogs = bp_blogs_get_blogs(array('per_page' => 50));
					}

					if($blogs) {
						?>
						<table cellspacing="0" class="widefat fixed">
							<thead>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Blog title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Last activity', 'membership'); ?></th>
							</tr>
							</thead>

							<tfoot>
							<tr>
								<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
								<th style="" class="manage-column column-name" id="name" scope="col"><?php _e('Blog title', 'membership'); ?></th>
								<th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Last activity', 'membership'); ?></th>
							</tr>
							</tfoot>

							<tbody>
						<?php
						foreach($blogs['blogs'] as $key => $blog) {
							?>
							<tr valign="middle" class="alternate" id="bpblog-<?php echo $blog->blog_id; ?>">
								<th class="check-column" scope="row">
									<input type="checkbox" value="<?php echo $blog->blog_id; ?>" name="bpblogs[]" <?php if(in_array($blog->blog_id, $data)) echo 'checked="checked"'; ?>>
								</th>
								<td class="column-name">
									<strong><?php echo esc_html($blog->name); ?></strong>
								</td>
								<td class="column-date">
									<?php
										echo date("Y/m/d", strtotime($blog->last_activity));
									?>
								</td>
						    </tr>
							<?php
						}
						?>
							</tbody>
						</table>
						<?php
					}

					if($blogs['total'] > 50) {
						?>
						<p class='description'><?php _e("Only the most recently updated 50 blogs are shown above.",'membership'); ?></p>
						<?php
					}

				?>

			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		$this->data = $data;

		add_filter( 'bp_blogs_get_blogs', array( $this, 'add_viewable_blogs' ), 10, 2 );
		add_filter( 'bp_has_blogs', array( $this, 'add_has_blogs' ), 10, 2 );
		add_filter( 'bp_activity_get', array( $this, 'add_has_activity' ), 10, 2 );
		add_filter( 'bp_get_total_blog_count', array( $this, 'fix_blog_count' ) );
	}

	function on_negative( $data ) {
		$this->data = $data;

		add_filter( 'bp_blogs_get_blogs', array( $this, 'add_unviewable_blogs' ), 10, 2 );
		add_filter( 'bp_has_blogs', array( $this, 'add_unhas_blogs' ), 10, 2 );
		add_filter( 'bp_activity_get', array( $this, 'add_unhas_activity' ), 10, 2 );
		add_filter( 'bp_get_total_blog_count', array( $this, 'fix_unblog_count' ) );
	}

	function fix_blog_count( $count ) {
		$count = count( $this->data );
		return $count;
	}

	function fix_unblog_count( $count ) {
		$count -= count( $this->data );
		return $count;
	}

	function add_has_activity( $activities, $two ) {
		$inneracts = $activities['activities'];
		foreach ( (array)$inneracts as $key => $act ) {
			if ( $act->component == 'blogs' ) {
				if ( !in_array( $act->item_id, $this->data ) ) {
					unset( $inneracts[$key] );
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach ( (array)$inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_unhas_activity( $activities, $two ) {
		$inneracts = $activities['activities'];
		foreach ( (array)$inneracts as $key => $act ) {
			if ( $act->component == 'blogs' ) {
				if ( in_array( $act->item_id, $this->data ) ) {
					unset( $inneracts[$key] );
					$activities['total']--;
				}
			}
		}

		$activities['activities'] = array();
		foreach ( (array)$inneracts as $key => $act ) {
			$activities['activities'][] = $act;
		}

		return $activities;
	}

	function add_unhas_blogs( $one, $blogs ) {
		$innerblogs = $blogs->blogs;
		foreach ( (array)$innerblogs as $key => $blog ) {
			if ( in_array( $blog->blog_id, $this->data ) ) {
				unset( $innerblogs[$key] );
				$blogs->total_blog_count--;
			}
		}

		$blogs->blogs = array();
		foreach ( (array) $innerblogs as $key => $blog ) {
			$blogs->blogs[] = $blog;
		}

		return !empty( $blogs->blogs );
	}

	function add_has_blogs( $one, $blogs ) {
		$innerblogs = $blogs->blogs;
		foreach ( (array)$innerblogs as $key => $blog ) {
			if ( !in_array( $blog->blog_id, $this->data ) ) {
				unset( $innerblogs[$key] );
				$blogs->total_blog_count--;
			}
		}

		$blogs->blogs = array();
		foreach ( (array)$innerblogs as $key => $blog ) {
			$blogs->blogs[] = $blog;
		}

		return !empty( $blogs->blogs );
	}

	function add_viewable_blogs( $blogs, $params ) {
		$innerblogs = $blogs['blogs'];
		foreach ( (array)$innerblogs as $key => $blog ) {
			if ( !in_array( $blog->blog_id, $this->data ) ) {
				unset( $innerblogs[$key] );
				$blogs['total']--;
			}
		}

		$blogs['blogs'] = array();
		foreach ( (array)$innerblogs as $key => $blog ) {
			$blogs['blogs'][] = $blog;
		}

		return $blogs;
	}

	function add_unviewable_blogs( $blogs, $params ) {
		$innerblogs = $blogs['groups'];
		foreach ( (array)$innerblogs as $key => $blog ) {
			if ( in_array( $blog->blog_id, $this->data ) ) {
				unset( $innerblogs[$key] );
				$blogs['total']--;
			}
		}

		$blogs['blogs'] = array();
		foreach ( (array)$innerblogs as $key => $blog ) {
			$blogs['blogs'][] = $blog;
		}

		return $blogs;
	}

}