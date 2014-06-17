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
 * Membership List Table 
 *
 *
 * @since 4.0.0
 *
 */
class MS_Helper_List_Table_Member extends MS_Helper_List_Table {

	public function __construct(){
		parent::__construct( array(
				'singular'  => 'member',
				'plural'    => 'members',
				'ajax'      => false
		) );
	}
	
	public function get_columns() {
		$columns = array(
			'cb'		 => '<input type="checkbox" />',
			'username'	 => __('Username', MS_TEXT_DOMAIN ),
			'email'		 => __('E-mail', MS_TEXT_DOMAIN ),
			'active' 	 => __('Active', MS_TEXT_DOMAIN ),				
			'membership' => __('Membership', MS_TEXT_DOMAIN ),
			'start' => __('Membership Start', MS_TEXT_DOMAIN ),
			'trial' => __('Trial Date', MS_TEXT_DOMAIN ),
			'expire' => __('Membership Expire', MS_TEXT_DOMAIN ),
			'gateway' => __('Gateway', MS_TEXT_DOMAIN ),
		);
		return $columns;
	}
	
	public function get_hidden_columns() {
		return array();
	}
	
	public function get_sortable_columns() {
		return array(
			'username' => array( 'login', false ),
			'email' => array( 'email', false ),
			'active' => array( 'active', false ),			
// 			'membership' => array( 'membership_ids', false ),
// 			'start' => array( 'start', false ),
// 			'trial' => array( 'trial', false ),
// 			'expire' => array( 'expire', false ),
// 			'gateway' => array( 'gateway', false ),
		);
	}
	
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );

		$per_page = $this->get_items_per_page( 'members_per_page', 10 );
		$current_page = $this->get_pagenum();
		
		$args = array(
				'number' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
		);
		
		if( ! empty( $_REQUEST['orderby'] ) && !empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}
		/**
		 * Prepare order by statement.
		 */
		if( ! empty( $args['orderby'] ) ) {
			if( ! in_array( $args['orderby'], array( 'login', 'email' ) ) && property_exists( 'MS_Model_Member', $args['orderby'] ) ) {
				
				switch( $args['orderby'] ) {
					case '':
						$args['orderby'] = 'meta_value_num';
					default:
						$args['meta_key'] = 'ms_'. $args['orderby'];
						$args['orderby'] = 'meta_value';
						break;
				}
			}
		}
		/**
		 * Search string.
		 */
		if( ! empty( $_REQUEST['search_options'] ) ) {
			$search_options = $_REQUEST['search_options'];
			$search_value = $_REQUEST['s'];
			$membership = $_REQUEST['membership_filter'];
			switch( $search_options ) {
				case 'email':
				case 'username':
					$args['search'] =  '*' . $search_value . '*';
					break;
				case 'membership':
					$members = array();
					$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships( array( 'membership_id' => $membership ) );
					foreach( $ms_relationships as $ms_relationship ) {
						$members[ $ms_relationship->user_id ] = $ms_relationship->user_id;
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
		/**
		 * Views filters.
		 */
		if( ! empty( $_REQUEST['status'] ) ) {
			switch( $_REQUEST['status'] ) {
				case 'active':
					$args['meta_query']['ms_active'] = array( 
							'key' => 'ms_active',
							'value' => true,
					);
					break;
				case 'members':
					$members = array();
					$ms_relationships = MS_Model_Membership_Relationship::get_membership_relationships();
					foreach( $ms_relationships as $ms_relationship ) {
						$members[ $ms_relationship->user_id ] = $ms_relationship->user_id;
					}
					if( ! empty( $args['include'] ) ) {
						$args['include'] = array_intersect( $members, $args['include'] );
					}
					else {
						$args['include'] = $members;
					}
					break;
			}
			
		}
		$total_items =  MS_Model_Member::get_members_count( $args );
		
		$this->items = MS_Model_Member::get_members( $args );
				
		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
		
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'email':
				$html = $item->$column_name;
				break;
			case 'active':
				$html = ( 1 == $item->active) ? __('Active', MS_TEXT_DOMAIN ) : __('Inactive', MS_TEXT_DOMAIN );
			default:
				print_r( $item, true );
		}
		echo $html;
	}
	
	function column_username( $item ) {
		$actions = array(
// 				'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
				'edit' => sprintf( '<a href="user-edit.php?user_id=%s">%s</a>', $item->id,  __('Edit', MS_TEXT_DOMAIN ) ),
			);
		
		echo sprintf( '%1$s %2$s', $item->username, $this->row_actions( $actions ) );
	}
	
	function column_active( $item ) {
		ob_start();
		/* Render toggles */
		$nonce_url = wp_nonce_url(
				sprintf( '%s?page=%s&member_id=%s&action=%s',
						admin_url('admin.php'),
						$_REQUEST['page'],
						$item->id,
						'toggle_activation'
				) );
		?>
			<div class="ms-radio-slider <?php echo 1 == $item->active ? 'on' : ''; ?>">
			<div class="toggle"><a href="<?php echo $nonce_url; ?>"></a></div>
			</div>
		<?php
		$html = ob_get_clean();
		
		echo $html;
	}
	/**
	 * Create membership column.
	 * 
	 * @param MS_Model_Member $item The member object.
	 */
	function column_membership( $item ) {
		
		if( MS_Model_Member::is_admin_user( $item->id ) ) {
			return __( 'Admin User', MS_TEXT_DOMAIN );
		}
		$html = array();
		foreach( $item->membership_relationships as $id => $membership_relationship ) {
			$membership = $membership_relationship->get_membership(); 
			$html[] = "{$membership->name} ({$membership_relationship->get_status()})";
		}
		$html = join('<br /> ', $html);
		
		$actions = array(
				'add' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'add', $item->id, __('Add', MS_TEXT_DOMAIN ) ),
				'move' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'move', $item->id, __('Move', MS_TEXT_DOMAIN ) ),
				'drop' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'drop', $item->id, __('Drop', MS_TEXT_DOMAIN ) ),
		);
		
		$multiple_membership = apply_filters( 'membership_addon_multiple_membership', MS_Plugin::instance()->addon->multiple_membership );

		if( count( $item->membership_relationships ) > 0 ) {
			if( ! $multiple_membership ) {
				unset( $actions['add'] );
			}
		}
		else {
			unset( $actions['move'] );
			unset( $actions['drop'] );
		}
		echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
	}
	
	function column_start( $item ) {
		if( count( $item->membership_relationships ) > 0 ) {
			$html = array();
			foreach( $item->membership_relationships as $membership_relationship ) {
				$period = $membership_relationship->get_current_period()->format( "%a days");
				$html[] = "$membership_relationship->start_date ($period)";
			}
			$html = join('<br /> ', $html);
			
			$actions = array(
					'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit_date', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
				);
			
			echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
		}		
	}

	function column_trial( $item ) {
		$html = array();
		foreach( $item->membership_relationships as $membership_relationship ) {
			if( $membership_relationship->trial_expire_date )  {
				$period = $membership_relationship->get_remaining_trial_period()->format( "%r%a days");
				$html[] = "$membership_relationship->trial_expire_date ($period)";
			}
			else {
				$html[] = __('No trial', MS_TEXT_DOMAIN );
			}
		}
		$html = join('<br /> ', $html);
		
		$actions = array(
// 				'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit_date', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
		);
		
		echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
	}
	
	function column_expire( $item ) {
		if( count( $item->membership_relationships ) > 0 ) {
			$html = array();
			foreach( $item->membership_relationships as $membership_relationship ) {
				if( $membership_relationship->expire_date )  {
					$period = $membership_relationship->get_remaining_period()->format( "%r%a days");
					$html[] = "$membership_relationship->expire_date ($period)";
				}
				else {
					$html[] = __('Permanent', MS_TEXT_DOMAIN );
				}
			}
			$html = join('<br /> ', $html);
			
			$actions = array(
					'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit_date', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
			);
			
			echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
		}
	}

	function column_gateway( $item ) {
		if( count( $item->membership_relationships ) > 0 ) {
			$html = array();
			foreach( $item->membership_relationships as $membership_relationship ) {
				$html[] = $membership_relationship->gateway_id;
			}
			$html = join('<br /> ', $html);
			$actions = array(
					'move' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'move_gateway', $item->id, __('Move', MS_TEXT_DOMAIN ) ),
				);
			
			echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
		}
	}
	
	public function get_bulk_actions() {
	  $actions = array(
	    	'toggle_activation' => __('Toggle Activation', MS_TEXT_DOMAIN ),
	  		'Memberships' => array(
  				'add' => __('Add membership', MS_TEXT_DOMAIN ),
	  			'move' => __('Move membership', MS_TEXT_DOMAIN ),
  				'drop' => __('Drop membership', MS_TEXT_DOMAIN ),
	  		),
	  	);
	  return $actions;
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function bulk_actions() {
		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			/**
			 * Filter the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, $this->screen->id, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			*/
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
		} else {
			$two = '2';
		}
	
		if ( empty( $this->_actions ) )
			return;
	
		echo "<select name='action$two'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";
	
		foreach ( $this->_actions as $name => $title ) {
			if( is_array( $title ) ) {
				echo "<optgroup label='$name'>";
				foreach( $title as $value => $label ){
					echo "<option value='$value'>$label</option>";
				}				
				echo "</optgroup>";
			}
			else {
				$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';
				
				echo "\t<option value='$name'$class>$title</option>\n";
			}
		}
	
		echo "</select>\n";
	
		submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => "doaction$two" ) );
			echo "\n";
	}
	
	function column_cb($item) {
        echo sprintf(
            '<input type="checkbox" name="member_id[]" value="%s" />', $item->id
        );    
    }
    

	function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;
		
		$search_options = array(
				'id' => 'search_options',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => ! empty( $_REQUEST['search_options'] ) ? $_REQUEST['search_options'] : 0,
				'field_options' => array(
						'username' => __( 'Username', MS_TEXT_DOMAIN ),
						'email' => __( 'Email', MS_TEXT_DOMAIN ),
						'nickname' => __( 'Nickname', MS_TEXT_DOMAIN ),
						'first_name' => __( 'First Name', MS_TEXT_DOMAIN ),
						'last_name' => __( 'Last Name', MS_TEXT_DOMAIN ),
						'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
					),
		);
		$membership_names = array(
			'id' => 'membership_filter',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => ! empty( $_REQUEST['membership_filter'] ) ? $_REQUEST['membership_filter'] : 0,
			'field_options' => MS_Model_Membership::get_membership_names(),
		);
		
		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		if ( ! empty( $_REQUEST['post_mime_type'] ) )
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		if ( ! empty( $_REQUEST['detached'] ) )
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		?>
		<div id="member-search-box" class="search-box">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<div class="input-container">
				<?php MS_Helper_Html::html_input( $search_options ); ?>
				<?php MS_Helper_Html::html_input( $membership_names ); ?>
				<input type="search" id="member-search" name="s" value="<?php _admin_search_query(); ?>" />
				<?php submit_button( $text , 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
			</div>
		</div>
		<?php
	}

	public function get_views(){
		return apply_filters( "ms_helper_list_table_member_views", array(
				'all' => sprintf( '<a href="%s">%s</a>', remove_query_arg( array ( 'status' ) ), __( 'All', MS_TEXT_DOMAIN ) ),
				'active' => sprintf( '<a href="%s">%s</a>', add_query_arg( array ( 'status' => 'active' ) ), __( 'Active', MS_TEXT_DOMAIN ) ),
				'members' => sprintf( '<a href="%s">%s</a>', add_query_arg( array ( 'status' => 'members' ) ), __( 'Members', MS_TEXT_DOMAIN ) ),
		) );
	}
}
