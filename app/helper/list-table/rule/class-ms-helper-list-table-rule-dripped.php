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
class MS_Helper_List_Table_Rule_Dripped extends MS_Helper_List_Table {

	protected $id = 'rule_dripped';

	protected $model;
	
	private static $counter = 0;
	
	public function __construct( $model ) {
		parent::__construct( array(
				'singular'  => "rule_$this->id",
				'plural'    => "rules",
				'ajax'      => false
		) );
	
		$this->model = $model;
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	
// 		$total_items =  $this->model->get_content_count();
// 		$per_page = $this->get_items_per_page( "{$this->id}_per_page", 10 );
// 		$current_page = $this->get_pagenum();
	
// 		$args = array(
// 				'posts_per_page' => $per_page,
// 				'offset' => ( $current_page - 1 ) * $per_page,
				
// 		);
		$args['rule_status'] = MS_Model_Rule::FILTER_DRIPPED;
// 		$args= null;
		
		$posts = apply_filters( "membership_helper_list_table_rule_post_items", $this->model['post']->get_content( $args ) );
		$pages = apply_filters( "membership_helper_list_table_rule_page_items", $this->model['page']->get_content( $args ) );
		
		$this->items = apply_filters( "membership_helper_list_table_{$this->id}_items", array_merge( $posts, $pages ) );
	
// 		$this->set_pagination_args( array(
// 				'total_items' => $total_items,
// 				'per_page' => $per_page,
// 			)
// 		);
	}
	
	public function get_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
// 				'cb'     => '<input type="checkbox" />',
				'title' => __( 'Title', MS_TEXT_DOMAIN ),
				'dripped' => __( 'Available after', MS_TEXT_DOMAIN ),
				'type' => __( 'Type', MS_TEXT_DOMAIN ),
				'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_hidden_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_hidden_columns", array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_sortable_columns", array(
		) );
	}
	
	public function get_bulk_actions() {
		return apply_filters( "membership_helper_list_table_{$this->id}_bulk_actions", array(
		) );
	}
	
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%1$s" />', $item->id );
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'title':
				$html = ! empty( $item->post_title ) ? $item->post_title : $item->name;
				$html .= sprintf( '<input type="hidden" id="%s_%s" name="item[%s][id]" value="%s">', $item->type, $item->id,self::$counter, $item->id );
				break;
			case 'dripped':
				$html = $item->delayed_period;
				if( ! empty( $item->dripped ) ) {
					$html .= sprintf( '<input type="hidden" name="item[%s][period_unit]" value="%s">', self::$counter, $item->dripped['period_unit'] );
					$html .= sprintf( '<input type="hidden" name="item[%s][period_type]" value="%s">', self::$counter, $item->dripped['period_type'] );
				}
				break;
			case 'type':
				$html = $item->type;
				$html .= sprintf( '<input type="hidden" name="item[%s][type]" value="%s">', self::$counter, $item->type );
				break;
			case 'delete':
				$html = '<button type="button" class="ms-delete">Delete</button>';
				self::$counter++;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
	
}