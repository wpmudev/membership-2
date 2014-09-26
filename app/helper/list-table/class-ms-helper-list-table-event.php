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
class MS_Helper_List_Table_Event extends MS_Helper_List_Table {
		
	protected $id = 'event';
	
	public function __construct(){
		parent::__construct( array(
				'singular'  => 'event',
				'plural'    => 'events',
				'ajax'      => false
		) );
	}
		
	public function get_columns() {
		$columns = array(
				'post_modified' => __( 'Date', MS_TEXT_DOMAIN ),
				'user_id' => __( 'User', MS_TEXT_DOMAIN ),
				'membership_id' => __( 'Membership', MS_TEXT_DOMAIN ),
				'description' => __( 'Event', MS_TEXT_DOMAIN ),
		);
		
		return apply_filters( 'membership_helper_list_table_event_columns', $columns );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_event_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_event_sortable_columns', array(
				'post_modified' => array( 'post_modified', false ),
				'user_id' => array( 'user_id', false ),
				'membership_id' => array( 'membership_id', false ),
		) );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$per_page = $this->get_items_per_page( "{$this->id}_per_page", 20 );
		$current_page = $this->get_pagenum();
		
		$args = array(
				'posts_per_page' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
		);

		/**
		 * Search string.
		 */
		if( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}
		
		/**
		 * Month filter.
		 */
		if( ! empty( $_REQUEST['m'] ) && strlen( $_REQUEST['m'] ) == 6 ) {
			$args['year'] = substr( $_REQUEST['m'], 0 , 4 );
			$args['monthnum'] = substr( $_REQUEST['m'], 5 , 2 );
		}
		
		if( ! empty( $_REQUEST['orderby'] ) && !empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}
		/**
		 * Prepare order by statement.
		 */
		if( ! empty( $args['orderby'] ) ) {
			if( property_exists( 'MS_Model_Event', $args['orderby'] ) ) {
				$args['meta_key'] = $args['orderby'];
				$args['orderby'] = 'meta_value';
			}
		}

		$total_items = MS_Model_Event::get_event_count( $args );
		$this->items = apply_filters( "ms_helper_list_table_{$this->id}_items", MS_Model_Event::get_events( $args ) );
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page' => $per_page,
		) );
		
	}

	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'user_id':
				$member = MS_Factory::load( 'MS_Model_Member', $item->user_id );
				$html = $member->username;
				break;
			case 'membership_id':
				$membership = MS_Factory::load( 'MS_Model_Membership', $item->membership_id );
				$html = $membership->name;
				break;
			default:
				$html = $item->$column_name;
				break;
		}
		return $html;
	}
	
	public function get_bulk_actions() {
		return apply_filters( 'ms_helper_list_table_membership_bulk_actions', array() );
	}
	
}
