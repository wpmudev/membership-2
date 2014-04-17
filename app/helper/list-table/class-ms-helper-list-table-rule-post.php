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
class MS_Helper_List_Table_Rule_Post extends MS_Helper_List_Table {

	protected $id = 'rule_post';
	
	private $columns;
	
	private $hidden;
	
	private $sortable;
	
	public function get_columns() {
		$this->columns = array(
			'cb'     => '<input type="checkbox" />',
			'post_title_col' => __( 'Post title', MS_TEXT_DOMAIN ),
			'post_date_col' => __( 'Post date', MS_TEXT_DOMAIN ),
		);
		return $this->columns;
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="post_id" value="%1$s" />', $item->ID );
	}
	
	public function get_hidden_columns() {
		$this->hidden = array();
		
		return $this->hidden;
	}
	
	public function get_sortable_columns() {
		$this->sortable = array();
		
		return $this->sortable;
	}
	
	public function prepare_items() {
		
		$this->get_columns();
		$this->get_hidden_columns();
		$this->get_sortable_columns();
		$this->get_items();
		
		$this->_column_headers = array( $this->columns, $this->hidden, $this->sortable );
	}
	
	public function get_items() {
		
		$this->items = MS_Model_Rule_Post::get_content();
		
		return $this->items;
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'post_title_col':
				$html = $item->post_title;
				break;
			case 'post_date_col':
				$html = $item->post_date;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}

}
