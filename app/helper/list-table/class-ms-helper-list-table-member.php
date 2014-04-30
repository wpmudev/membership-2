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
			'username' => array( 'username', false ),
			'email' => array( 'email', false ),
			'active' => array( 'active', false ),			
			'membership' => array( 'membership', false ),
			'start' => array( 'start', false ),
			'expire' => array( 'expire', false ),
			'gateway' => array( 'gateway', false ),
		);
	}
	
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );

		$total_items =  MS_Model_Member::get_members_count();
		$per_page = $this->get_items_per_page( 'members_per_page', 10 );
		$current_page = $this->get_pagenum();
		
		$args = array(
				'number' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
		);
		
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
		return $html;
	}
	
	function column_username( $item ) {
		$actions = array(
			'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
			'toggle_activation' => sprintf( '<a href="%s">%s</a>',
					wp_nonce_url(
							sprintf( '?page=%s&member_id=%s&action=%s',
							$_REQUEST['page'],
							$item->id,
							'toggle_activation'
						),
						'toggle_activation'
					),
					__('Toggle Activation', MS_TEXT_DOMAIN )
				),
			);
		
		echo sprintf( '%1$s %2$s', $item->username, $this->row_actions( $actions ) );
	}
	
	/**
	 * Create membership column.
	 * 
	 * Only allow single membership.
	 * @todo Allow multiple memberships as addon.
	 * @param MS_Model_Member $item The member object.
	 */
	function column_membership( $item ) {
		$multiple_membership_option = true;
		
		$html = array();
		foreach( $item->membership_relationships as $id => $membership_relationship ) {
			$membership = $membership_relationship->get_membership(); 
			$html[] = "{$membership->name} ({$membership_relationship->status})";
		}
		$html = join(', ', $html);
		
		$actions = array(
				'add' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'add', $item->id, __('Add', MS_TEXT_DOMAIN ) ),
				'move' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'move', $item->id, __('Move', MS_TEXT_DOMAIN ) ),
				'drop' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'drop', $item->id, __('Drop', MS_TEXT_DOMAIN ) ),
		);
		
		if( count( $item->membership_ids ) > 0 ) {
			if( ! $multiple_membership_option ) {
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
		$html = array();
		foreach( $item->membership_relationships as $membership_relationship ) {
			$period = $membership_relationship->get_current_period();
			$html[] = "$membership_relationship->start_date ($period)";
		}
		$html = join(', ', $html);
		
		$actions = array(
				'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit_date', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
			);
		
		echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
		
	}
	
	function column_expire( $item ) {
		$html = array();
		foreach( $item->membership_relationships as $membership_relationship ) {
			if( $membership_relationship->expire_date )  {
				$period = $membership_relationship->get_remaining_period();
				$html[] = "$membership_relationship->expire_date ($period)";
			}
			else {
				$html[] = __('Permanent', MS_TEXT_DOMAIN );
			}
		}
		$html = join(', ', $html);
		
		$actions = array(
				'edit' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'edit_date', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
		);
		
		echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
	}

	function column_gateway( $item ) {
		$html = array();
		foreach( $item->membership_relationships as $membership_relationship ) {
			$html[] = $membership_relationship->gateway;
		}
		$html = join(', ', $html);
		$actions = array(
				'move' => sprintf( '<a href="?page=%s&action=%s&member_id=%s">%s</a>', $_REQUEST['page'], 'move_gateway', $item->id, __('Move', MS_TEXT_DOMAIN ) ),
			);
		
		echo sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
	}
	
	function get_bulk_actions() {
	  $actions = array(
	    'deactivate'    => 'Deactivate Membership'
	  );
	  return $actions;
	}

	function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="member[]" value="%s" />', $item->id
        );    
    }

	function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;

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
		<div id="member-search-box">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<div class="input-container">
		<!--		<select id="member-search-options" name="" style="width: 115px;">
					<option value="Value for Item 1" title="Title for Item 1">Full Name</option>
					<option value="Value for Item 2" title="Title for Item 2">Membership</option>
					<option value="Value for Item 3" title="Title for Item 3">E-mail Address</option>
				</select>  -->
				<input type="search" id="member-search" name="s" value="<?php _admin_search_query(); ?>" />		
				<?php submit_button( $text , 'button', false, false, array('id' => 'search-submit') ); ?>
			</div>
		</div>
		<?php
	}







	
}
