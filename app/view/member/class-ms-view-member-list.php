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


class MS_View_Member_List extends MS_View {

	public function to_html() {
		$this->check_simulation();

		// Search for orphaned relationships and delete them.
		MS_Model_Member::clean_db();

		$listview = MS_Factory::create( 'MS_Helper_ListTable_Member' );
		$listview->prepare_items();

		ob_start();
		?>

		<div class="wrap ms-wrap ms-member-list">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Members', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-users',
					'desc' => __( 'Here you can manage the Memberships of existing Users.', MS_TEXT_DOMAIN ),
				)
			);

			// Display a filter to switch between individual memberships.
			$this->membership_filter();

			$listview->views();
			$listview->search_box();
			?>
			<form method="post">
				<?php $listview->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Display a filter to select the current membership
	 *
	 * @since  1.1.0
	 */
	public function membership_filter() {
		$memberships = MS_Model_Membership::get_membership_names();
		$url = remove_query_arg( array( 'membership_id', 'paged' ) );
		$links = array();

		$links['all'] = array(
			'label' => __( 'All', MS_TEXT_DOMAIN ),
			'url' => $url,
		);

		foreach ( $memberships as $id => $name ) {
			if ( empty( $name ) ) {
				$name = __( '(No Name)', MS_TEXT_DOMAIN );
			}

			$links['ms-' . $id] = array(
				'label' => esc_html( $name ),
				'url' => add_query_arg( array( 'membership_id' => $id ), $url ),
			);
		}

		?>
		<div class="wp-filter">
			<ul class="filter-links">
				<?php foreach ( $links as $key => $item ) :
					$is_current = MS_Helper_Utility::is_current_url( $item['url'] );
					$class = ( $is_current ? 'current' : '' );
					?>
					<li>
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( $class ); ?>">
							<?php echo esc_html( $item['label'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

}