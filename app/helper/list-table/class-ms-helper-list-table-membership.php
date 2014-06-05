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
		return apply_filters( 'membership_helper_list_table_membership_columns', array(
			'cb'     => '<input type="checkbox" />',
			'name' => __( 'Membership Name', MS_TEXT_DOMAIN ),
			'active' => __( 'Active', MS_TEXT_DOMAIN ),
			'public' => __( 'Public', MS_TEXT_DOMAIN ),
			'members' => __( 'Members', MS_TEXT_DOMAIN ),
			'shortcode' => __( 'Shortcode', MS_TEXT_DOMAIN ),
		) );
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="membership_id[]" value="%1$s" />', $item->id );
	}
	
	function column_active( $item ) {
		ob_start();
		/* Render toggles */
		$nonce_url = wp_nonce_url(
				sprintf( '%s?page=%s&membership_id=%s&action=%s',
						admin_url('admin.php'),
						$_REQUEST['page'],
						$item->id,
						'toggle_activation'
				),
				'toggle_activation'
			);
		?>
			<div class="ms-radio-slider <?php echo 1 == $item->active ? 'on' : ''; ?>">
			<div class="toggle"><a href="<?php echo $nonce_url; ?>"></a></div>
			</div>
		<?php
		$html = ob_get_clean();
		
		echo $html;		
	}
	
	function column_public( $item ) {
		ob_start();
		/* Render toggles */
		$nonce_url = wp_nonce_url(
				sprintf( '%s?page=%s&membership_id=%s&action=%s',
						admin_url('admin.php'),
						$_REQUEST['page'],
						$item->id,
						'toggle_public'
				),
				'toggle_public'
			);
		?>
			<div class="ms-radio-slider <?php echo 1 == $item->public ? 'on' : ''; ?>">
			<div class="toggle"><a href="<?php echo $nonce_url; ?>"></a></div>
			</div>
		<?php
		$html = ob_get_clean();
		
		echo $html;		
	}
	
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_membership_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_membership_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$total_items =  MS_Model_Membership::get_membership_count();
		$per_page = $this->get_items_per_page( 'membership_per_page', 10 );
		$current_page = $this->get_pagenum();
		
		$args = array(
				'posts_per_page' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
			);
		
		$this->items = apply_filters( 'membership_helper_list_table_membership_items', MS_Model_Membership::get_memberships( $args ) );
		
		$this->set_pagination_args( array(
					'total_items' => $total_items,
					'per_page' => $per_page,
				)
			);
	}

	public function column_name( $item ) {
		$actions = array(
				sprintf( '<a href="?page=membership-edit&membership_id=%s">%s</a>',
						$item->id,
						__('Edit', MS_TEXT_DOMAIN )
				),
				sprintf( '<span class="delete"><a href="%s">%s</a></span>',
					wp_nonce_url( 
						sprintf( '?page=%s&membership_id=%s&action=%s',
							$_REQUEST['page'],
							$item->id,
							'delete'
							),
						'delete'
						),
					__('Delete', MS_TEXT_DOMAIN )
				),
		);
		$actions = apply_filters( "membership_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
		return sprintf( '%1$s %2$s', $item->name, $this->row_actions( $actions ) );
		
	}
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'active':
				$html = ( $item->active ) ? __( 'Active', MS_TEXT_DOMAIN ) : __( 'Deactivated', MS_TEXT_DOMAIN );
				break;
			case 'public':
				$html = ( $item->public ) ? __( 'Public', MS_TEXT_DOMAIN ) : __( 'Private', MS_TEXT_DOMAIN );
				break;
			case 'members':
				$html = $item->get_members_count();
				break;
			case 'shortcode':
				$html = '['. MS_Model_Rule_Shortcode::PROTECT_CONTENT_SHORTCODE ." id='$item->id']";
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
	
	public function get_bulk_actions() {
		return apply_filters( 'membership_helper_list_table_membership_bulk_actions', array(
			'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
			'toggle_activation' => __( 'Toggle Activation', MS_TEXT_DOMAIN ),
			'toggle_public' => __( 'Toggle Public Status', MS_TEXT_DOMAIN ),
		) );
	}
	
}
