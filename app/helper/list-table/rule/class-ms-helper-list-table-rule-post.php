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
class MS_Helper_List_Table_Rule_Post extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_post';
			
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'name' => __( 'Post title', MS_TEXT_DOMAIN ),
			'access' => __( 'Access', MS_TEXT_DOMAIN ),
			'dripped' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
			'category' => __( 'Categories', MS_TEXT_DOMAIN ),
			'post_date' => __( 'Post date', MS_TEXT_DOMAIN ),
		);
		
		if( MS_Model_Membership::TYPE_DRIPPED != $this->membership->type ) {
			unset( $columns['dripped'] );
		}
		if( ! empty( $_GET['step'] ) && MS_Controller_Membership::STEP_ACCESSIBLE_CONTENT == $_GET['step'] ) {
			$columns['access'] = __( 'Members Access', MS_TEXT_DOMAIN );
		}
		
		return apply_filters( "ms_helper_list_table_{$this->id}_columns", $columns );
	}
		
	public function get_sortable_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_sortable_columns", array(
				'name' => 'name',
				'access' => 'access',
				'dripped' => 'dripped',
				'slug' => 'slug',
				'posts' => 'posts',
		) );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	
		$total_items =  $this->model->get_content_count();
		$per_page = $this->get_items_per_page( "{$this->id}_per_page", 10 );
		$current_page = $this->get_pagenum();
	
		$args = array(
				'posts_per_page' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
		);
	
		if( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status'];
		}
		
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
		$this->items = apply_filters( "membership_helper_list_table_{$this->id}_items", $this->model->get_content( $args ) );
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page' => $per_page,
		) );
		
		$this->items = apply_filters( "ms_helper_list_table_{$this->id}_items", $this->model->get_content( $args ) );
	
		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}
	
	public function column_name( $item ) {
	
		$actions = array(
				sprintf( '<a href="%s">%s</a>',
						get_edit_post_link( $item->id, true ),
						__('Edit', MS_TEXT_DOMAIN )
				),
				sprintf( '<a href="%s">%s</a>',
						get_permalink( $item->id ),
						__('View', MS_TEXT_DOMAIN )
				),
		);
		$actions = apply_filters( "membership_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
	
		return sprintf( '%1$s %2$s', $item->post_title, $this->row_actions( $actions ) );
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'post_date':
				$html = $item->post_date;
				break;
			case 'category':
				$html = join( ', ', $item->categories );
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}

	public function extra_tablenav( $which ) {
		if( 'top' != $which ) {
			return;
		}
		$filter_button = array(
				'id' => 'filter_button',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Filter', MS_TEXT_DOMAIN ),
				'class' => 'button',
		);
		?>
		<div class="alignleft actions">
		<?php
			$this->months_dropdown( 'page' );
			MS_Helper_Html::html_input( $filter_button );
		?>
		</div>
	<?php
	}
}
