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
 * Members List Table.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Helper
 */
class MS_Helper_List_Table_Member extends MS_Helper_List_Table {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct(){
		parent::__construct(
			array(
				'singular'  => 'member',
				'plural'    => 'members',
				'ajax'      => false,
			)
		);
	}

	/**
	 * Get list table columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *		Returns array of $id => $title.
	 *
	 *		@type string $id The list table column id.
	 *		@type string $title The list table column title.
	 * }
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username', MS_TEXT_DOMAIN ),
			'email'      => __( 'E-mail', MS_TEXT_DOMAIN ),
			'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
		);

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
			unset( $columns['trial'] );
		}

		return apply_filters( 'ms_helper_list_table_member_get_columns', $columns );
	}

	/**
	 * Get list table hidden columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *		Returns array of $id => $title.
	 *
	 *		@type string $id The list table column id.
	 *		@type string $title The list table column title.
	 * }
	 */
	public function get_hidden_columns() {
		return apply_filters(
			'ms_helper_list_table_member_get_hidden_columns',
			array()
		);
	}

	/**
	 * Get list table sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *		Returns array of $id => $title.
	 *
	 *		@type string $id The list table column id.
	 *		@type array $orderby The field id to use order.
	 * }
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_list_table_member_get_sortable_columns',
			array(
				'username' => 'login',
				'email' => 'email',
			)
		);
	}

	/**
	 * Prepare list items.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$per_page = apply_filters( 'ms_helper_list_table_member_items_per_page', 10 );
		$current_page = $this->get_pagenum();

		$args = array(
			'number' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}

		// Prepare order by statement.
		if ( ! empty( $args['orderby'] )
			&& ! in_array( $args['orderby'], array( 'login', 'email' ) )
			&& property_exists( 'MS_Model_Member', $args['orderby'] )
		) {
			$args['meta_key'] = 'ms_'. $args['orderby'];
			$args['orderby'] = 'meta_value';
		}

		// Search string.
		WDev()->load_request_fields( 'membership', 'status', 's' );
		if ( ! empty( $_REQUEST['search_options'] ) ) {

			$search_options = $_REQUEST['search_options'];
			$search_value = $_REQUEST['s'];

			switch ( $search_options ) {
				case 'email':
				case 'username':
					$args['search'] = sprintf( '*%s*', $search_value );
					break;

				case 'membership':
					$membership = $_REQUEST['membership'];
					$status = $_REQUEST['status'];

					$members = array();
					$filter = array();
					if ( ! empty( $membership ) ) {
						$filter['membership_id'] = $membership;
					}
					if ( ! empty( $status ) ) {
						$filter['status'] = $status;
					}
					$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships(
						$filter
					);

					foreach ( $ms_relationships as $ms_relationship ) {
						$members[ $ms_relationship->user_id ] = $ms_relationship->user_id;
					}

					// Workaround to invalidate query
					if ( empty( $members ) ) {
						$members[0] = 0;
					}

					$args['include'] = $members;
					break;

				default:
					$args['meta_query'][ $search_options ] = array(
						'key' => $search_options,
						'value' => $search_value,
						'compare' => 'LIKE',
					);
					break;
			}
		}

		$total_items = MS_Model_Member::get_members_count( $args );
		$this->items = MS_Model_Member::get_members( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);

		do_action( 'ms_helper_list_table_member_prepare_items', $this );
	}

	/**
	 * Default column handler.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 * @param string $column_name The column to display.
	 */
	public function column_default( $item, $column_name ) {
		$html = $item->$column_name;

		echo apply_filters(
			'ms_helper_list_table_member_column_default',
			$html,
			$item,
			$column_name,
			$this
		);
	}

	/**
	 * Display checkbox column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 */
	public function column_cb( $item ) {
		$html = sprintf(
			'<input type="checkbox" name="member_id[]" value="%s" />',
			esc_attr( $item->id )
		);

		echo apply_filters(
			'ms_helper_list_table_member_column_cb',
			$html,
			$item,
			$this
		);
	}

	/**
	 * Display Username column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 */
	public function column_username( $item ) {
		$actions = array();
		$actions['edit'] = sprintf(
			'<a href="user-edit.php?user_id=%s">%s</a>',
			esc_attr( $item->id ),
			__( 'Edit', MS_TEXT_DOMAIN )
		);

		$html = sprintf( '%1$s %2$s', $item->username, $this->row_actions( $actions ) );

		echo apply_filters(
			'ms_helper_list_table_member_column_username',
			$html,
			$item,
			$this
		);
	}

	/**
	 * Create membership column.
	 *
	 * @since 1.0.0
	 *
	 * @todo implement ajax updating.
	 *
	 * @param MS_Model_Member $member The member object.
	 */
	public function column_membership( $member ) {
		if ( MS_Model_Member::is_admin_user( $member->id ) ) {
			$html = '<b>' . __( 'Admin User', MS_TEXT_DOMAIN ) . '</b>';
		}
		else {
			$html = array();

			if ( ! empty( $_REQUEST['status'] ) ) {
				$memberships = MS_Model_Membership_Relationship::get_membership_relationships(
					array(
						'user_id' => $member->id,
						'status' => $_REQUEST['status'],
					)
				);
			} else {
				$memberships = $member->ms_relationships;
			}

			foreach ( $memberships as $id => $membership_relationship ) {
				$membership = $membership_relationship->get_membership();
				$html[] = sprintf(
					'%s (%s)',
					esc_html( $membership->name ),
					esc_html( $membership_relationship->status )
				);
			}
			$html = join( '<br /> ', $html );

			$actions = array();
			$actions['add'] = sprintf(
				'<a href="?page=%s&action=%s&member_id=%s">%s</a>',
				esc_attr( $_REQUEST['page'] ),
				'add',
				esc_attr( $member->id ),
				__( 'Add', MS_TEXT_DOMAIN )
			);
			$actions['drop'] = sprintf(
				'<a href="?page=%s&action=%s&member_id=%s">%s</a>',
				esc_attr( $_REQUEST['page'] ),
				'drop',
				esc_attr( $member->id ),
				__( 'Drop', MS_TEXT_DOMAIN )
			);

			$multiple_membership = apply_filters(
				'membership_addon_multiple_membership',
				MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS )
			);

			if ( count( $member->ms_relationships ) > 0 ) {
				if ( ! $multiple_membership ) {
					unset( $actions['add'] );
				}
			}
			else {
				unset( $actions['move'] );
				unset( $actions['cancel'] );
				unset( $actions['drop'] );
			}

			$html = sprintf(
				'%1$s %2$s',
				$html,
				$this->row_actions( $actions )
			);
		}

		echo apply_filters(
			'ms_helper_list_table_member_column_membership',
			$html,
			$member,
			$this
		);
	}

	/**
	 * Bulk actions options.
	 *
	 * @since 1.0.0
	 *
	 * @param array {
	 *     @type string $action The action name.
	 *     @type mixed $desciption The action description.
	 * }
	 */
	public function get_bulk_actions() {
		$actions = array(
			'toggle_activation' => __( 'Toggle Activation', MS_TEXT_DOMAIN ),
			'Memberships' => array(
				'add'     => __( 'Add membership', MS_TEXT_DOMAIN ),
				'cancel'  => __( 'Cancel membership', MS_TEXT_DOMAIN ),
				'move'    => __( 'Move membership', MS_TEXT_DOMAIN ),
				'drop'    => __( 'Drop membership', MS_TEXT_DOMAIN ),
			),
		);

		return apply_filters( 'ms_helper_list_table_member_get_bulk_actions', $actions, $this );
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $echo Output or return the HTML code? Default is output.
	 */
	public function bulk_actions( $echo = true ) {
		if ( empty( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();

			$this->_actions = apply_filters( 'bulk_actions-' . $this->screen->id, $this->_actions );
			$this->_actions = MS_Helper_Utility::array_intersect_assoc_deep( $this->_actions, $no_new_actions );

			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		if ( ! $echo ) { ob_start(); }

		printf(
			'<select name="action%s"><option value="-1" selected="selected">%s</option>',
			esc_attr( $two ),
			__( 'Bulk Actions' )
		);

		foreach ( $this->_actions as $name => $title ) {
			if ( is_array( $title ) ) {
				printf( '<optgroup label="%s">', esc_attr( $name ) );

				foreach ( $title as $value => $label ){
					printf(
						'<option value="%s">%s</option>',
						esc_attr( $value ),
						esc_attr( $label )
					);
				}
				echo '</optgroup>';
			}
			else {
				$class = 'edit' == $name ? 'hide-if-no-js' : '';

				printf(
					'<option value="%s" class="%s">%s</option>',
					esc_attr( $name ),
					esc_attr( $class ),
					esc_attr( $title )
				);
			}
		}

		echo '</select>';

		submit_button(
			__( 'Apply' ),
			'action',
			false,
			false,
			array( 'id' => 'doaction' . esc_attr( $two ) )
		);

		if ( ! $echo ) { return ob_get_clean(); }
	}

	/**
	 * Display search box.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text The search button text
	 * @param string $input_id The search input id
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}
		if ( ! $this->need_pagination() ) {
			return;
		}

		$search_options = array(
			'id' => 'search_options',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => @$_REQUEST['search_options'],
			'field_options' => array(
				'username'   => __( 'Username / E-mail', MS_TEXT_DOMAIN ),
				'nickname'   => __( 'Nickname', MS_TEXT_DOMAIN ),
				'first_name' => __( 'First Name', MS_TEXT_DOMAIN ),
				'last_name'  => __( 'Last Name', MS_TEXT_DOMAIN ),
				'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
			),
		);

		$membership_names = array(
			'id'    => 'membership_filter',
			'type'  => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => @$_REQUEST['membership_filter'],
			'field_options' => MS_Model_Membership::get_membership_names(),
		);

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			printf(
				'<input type="hidden" name="orderby" value="%s" />',
				esc_attr( $_REQUEST['orderby'] )
			);
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			printf(
				'<input type="hidden" name="order" value="%s" />',
				esc_attr( $_REQUEST['order'] )
			);
		}

		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			printf(
				'<input type="hidden" name="post_mime_type" value="%s" />',
				esc_attr( $_REQUEST['post_mime_type'] )
			);
		}

		if ( ! empty( $_REQUEST['detached'] ) ) {
			printf(
				'<input type="hidden" name="detached" value="%s" />',
				esc_attr( $_REQUEST['detached'] )
			);
		}

		?>
		<p id="member-search-box" class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ) ?>">
				<?php echo esc_html( $text ); ?>:
			</label>
			<?php MS_Helper_Html::html_element( $search_options ); ?>
			<?php MS_Helper_Html::html_element( $membership_names ); ?>
			<input type="search" id="member-search" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text , 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php

		do_action(
			'ms_helper_list_table_member_search_box',
			$text,
			$input_id,
			$this
		);
	}

	/**
	 * Get list table views.
	 *
	 * @see MS_Helper_List_Table::get_views()
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     @type string $url The view url.
	 *     @type string label The view label.
	 *     @type int count The view count.
	 * }
	 *
	 */
	public function get_views() {
		$list_views = array();

		// Active Memberships.
		$status_url = admin_url(
			'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-members'
		);
		$count = MS_Model_Member::get_members_count();
		$list_views['all'] = array(
			'url' => $status_url,
			'label' => __( 'Active', MS_TEXT_DOMAIN ),
			'count' => $count,
		);

		// Pending Memberships.
		$status_url = admin_url(
			sprintf(
				'admin.php?page=%s&search_options=membership&status=%s&s=%s',
				MS_Controller_Plugin::MENU_SLUG . '-members',
				MS_Model_Membership_Relationship::STATUS_PENDING,
				esc_attr( __( 'Pending', MS_TEXT_DOMAIN ) )
			)
		);

		$count = MS_Model_Membership_Relationship::get_membership_relationship_count(
			array( 'status' => MS_Model_Membership_Relationship::STATUS_PENDING )
		);

		$list_views['pending'] = array(
			'url' => $status_url,
			'label' => __( 'Pending', MS_TEXT_DOMAIN ),
			'count' => $count,
			'separator' => false,
		);

		// List of all Membership Levels.
		$list_views['label'] = array(
			'label' => __( 'Memberships', MS_TEXT_DOMAIN ) . ':',
		);

		// Get all memberships.
		$memberships = MS_Model_Membership::get_memberships();

		// Count memberships that are displayed.
		$count = 0;
		foreach ( $memberships as $id => $membership ) {
			if ( $membership->can_have_children() ) { continue; }
			$count += 1;
		}

		if ( 5 >= $count ) {
			foreach ( $memberships as $id => $membership ) {
				if ( $membership->can_have_children() ) { continue; }
				$status_url = admin_url(
					sprintf(
						'admin.php?page=%s&search_options=membership&membership=%s&s=%s',
						MS_Controller_Plugin::MENU_SLUG . '-members',
						esc_attr( $membership->id ),
						esc_attr( $membership->name )
					)
				);
				$list_views[ $id ] = array(
					'url' => $status_url,
					'label' => $membership->name,
					'count' => $membership->get_members_count(),
				);
			}
		} else {
			$grouped = array(
				0 => __( '(Select a membership)', MS_TEXT_DOMAIN ),
			);
			$grouped += MS_Model_Membership::get_membership_hierarchy();

			foreach ( $grouped as $id => $item ) {
				if ( empty( $id ) ) { continue; }

				if ( is_array( $item ) ) {
					foreach ( $item as $child_id => $child ) {
						$ms = MS_Factory::load( 'MS_Model_Membership', $child_id );
						$count = $ms->get_members_count();
						if ( $count ) {
							$grouped[$id][$child_id] .= '  (' . $count . ')';
						}
					}
				} else {
					$ms = MS_Factory::load( 'MS_Model_Membership', $id );
					$count = $ms->get_members_count();
					if ( $count ) {
						$grouped[$id] .= '  (' . $count . ')';
					}
				}
			}

			$url = admin_url(
				sprintf(
					'admin.php?page=%s&search_options=membership&membership=',
					MS_Controller_Plugin::MENU_SLUG . '-members'
				)
			);
			$value = 0;
			if ( isset( $_GET['membership'] ) ) {
				$value = $_GET['membership'];
			}

			$field = array(
				'id' => 'view_membership',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $grouped,
				'data_ms' => array( 'url' => $url ),
				'value' => $value,
			);
			$code = MS_Helper_Html::html_element( $field, true );

			$list_views[ 'membership' ] = array(
				'url' => false,
				'label' => $code,
				'count' => false,
			);
		}

		return apply_filters( 'ms_helper_list_table_member_views', $list_views );
	}
}
