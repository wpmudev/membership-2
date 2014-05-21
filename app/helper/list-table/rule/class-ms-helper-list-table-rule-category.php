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
class MS_Helper_List_Table_Rule_Category extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_category';
			
	public function get_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
			'cb'     => '<input type="checkbox" />',
			'name' => __( 'Category name', MS_TEXT_DOMAIN ),
			'access' => __( 'Access', MS_TEXT_DOMAIN ),
			'slug' => __( 'Slug', MS_TEXT_DOMAIN ),
			'posts' => __( 'Posts', MS_TEXT_DOMAIN ),
		) );
	}

	public function column_access( $item ) {
		$action = $item->access ? 'no_access' : 'give_access';

		ob_start();
		/* Render toggles */
		$nonce_url = wp_nonce_url(
				sprintf( '%s?page=%s&tab=%s&membership_id=%s&item=%s&action=%s',
						admin_url('admin.php'),
						$_REQUEST['page'],
						$_REQUEST['tab'],
						$_REQUEST['membership_id'],
						$item->id,
						$action  
				), MS_View_Membership_Edit::MEMBERSHIP_SAVE_NONCE );
		?>
			<div class="ms-radio-slider <?php echo 1 == $item->access ? 'on' : ''; ?>">
			<div class="toggle"><a href="<?php echo $nonce_url; ?>"></a></div>
			</div>
		<?php
		$html = ob_get_clean();
		
		echo $html;
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
				
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'name':
				$html = $item->name;
				break;
			case 'slug':
				$html = $item->slug;
				break;
			case 'posts':
				$html = 0;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
}
