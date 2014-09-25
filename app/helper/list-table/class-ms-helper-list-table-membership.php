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
class MS_Helper_List_Table_Membership extends MS_Helper_List_Table {
		
	protected $id = 'membership';
	
	public function __construct(){
		parent::__construct( array(
				'singular'  => 'membership',
				'plural'    => 'memberships',
				'ajax'      => false
		) );
	}
		
	public function get_columns() {
		$columns = array(
				'name' => __( 'Membership Name', MS_TEXT_DOMAIN ),
				'type_description' => __( 'Type of Membership', MS_TEXT_DOMAIN ),
				'active' => __( 'Active', MS_TEXT_DOMAIN ),
				'members' => __( 'Members', MS_TEXT_DOMAIN ),
				'price' => __( 'Cost', MS_TEXT_DOMAIN ),
				'payment_structure' => __( 'Payment Structure', MS_TEXT_DOMAIN ),
				'shortcode' => __( 'Membership Shortcode', MS_TEXT_DOMAIN ),
		);
		
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_PRIVATE_MEMBERSHIPS ) ) {
			unset( $columns['public'] );
		} 

		return apply_filters( 'membership_helper_list_table_membership_columns', $columns );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_membership_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_membership_sortable_columns', array(
				'name' => array( 'name', true ),
				'type' => array( 'type', true ),
				'active' => array( 'active', true ),
				'public' => array( 'public', true ),
		) );
	}
	
	function column_active( $item ) {
		
		$toggle = array(
				'id' => 'ms-toggle-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $item->active,
				'class' => '',
				'data_ms' => array(
						'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
						'field' => 'active',
						'membership_id' => $item->id,
				),
		);
		$html = MS_Helper_Html::html_element( $toggle, true );
		
		return $html;
	}
	
	function column_public( $item ) {
		
		$toggle = array(
				'id' => 'ms-toggle-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $item->private,
				'class' => '',
				'data_ms' => array(
						'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
						'field' => 'public',
						'membership_id' => $item->id,
				),
		);
		$html = MS_Helper_Html::html_element( $toggle, true );
		
		return $html;
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$args = array();
		
		if( ! empty( $_REQUEST['orderby'] ) && !empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}
		/**
		 * Prepare order by statement.
		 */
		if( ! empty( $args['orderby'] ) ) {
			if( property_exists( 'MS_Model_Membership', $args['orderby'] ) ) {
				$args['meta_key'] = $args['orderby'];
				$args['orderby'] = 'meta_value';
			}
		}

		$this->items = apply_filters( 'membership_helper_list_table_membership_items', MS_Model_Membership::get_grouped_memberships( $args ) );
		
	}

	public function column_name( $item ) {
		$actions = array(
			 	'edit' => sprintf( '<a href="?page=%s&step=%s&membership_id=%s">%s</a>',
						$_REQUEST['page'],
						MS_Controller_Membership::STEP_OVERVIEW,
						$item->id,
						__( 'Edit', MS_TEXT_DOMAIN )
				),
				'delete' => sprintf( '<span class="delete"><a href="%s">%s</a></span>',
					wp_nonce_url( 
						sprintf( '?page=%s&membership_id=%s&action=%s',
							$_REQUEST['page'],
							$item->id,
							'delete'
							),
						'delete'
						),
					__( 'Delete', MS_TEXT_DOMAIN )
				),
		);
		if( $item->has_parent() ) {
			$actions['edit'] = sprintf( '<a href="?page=%s&step=%s&membership_id=%s&tab=%s">%s</a>',
					$_REQUEST['page'],
					MS_Controller_Membership::STEP_OVERVIEW,
					$item->parent_id,
					$item->id,
					__( 'Edit', MS_TEXT_DOMAIN )
			);
		}
		
		$actions = apply_filters( "ms_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
		return sprintf( '%1$s %2$s', $item->name, $this->row_actions( $actions ) );
		
	}
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'members':
				$html = $item->get_members_count();
				break;
			case 'type_description':
				$html = sprintf( '<div class="ms-type-desc ms-%s"><span>%s<span></div>', $item->type, $item->type_description );
				break;
			case 'payment_structure':
				$html = $item->get_payment_type_desc();
				break;
			case 'price':
				if( $item->can_have_children() ) {
					$html = __( 'Varied', MS_TEXT_DOMAIN );	
				}
				elseif( $item->price > 0 ) {
					$html = $item->price;
				}
				else {
					$html = __( 'Free', MS_TEXT_DOMAIN );
				}
				break;
			case 'shortcode':
				$html = '['. MS_Model_Rule_Shortcode::PROTECT_CONTENT_SHORTCODE ." id='$item->id']";
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
	
	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.0
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		static $row_class = '';
	
		$row_class = ( $row_class == '' ? 'alternate' : '' );
		$class = ( $item->parent_id > 0 ) ? 'ms-child-row' : '';
		$class = "class='$row_class $class'";
		
		echo "<tr $class >";
		$this->single_row_columns( $item );
		echo '</tr>';
	}
}
