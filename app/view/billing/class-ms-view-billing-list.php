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
 * Renders Billing/Transaction History.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_View_Billing_List extends MS_View {
		
	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 */
	public function to_html() {		
		$billing_list = new MS_Helper_List_Table_Billing();
		$billing_list->prepare_items();

		ob_start();
		?>
		
		<div class="wrap ms-wrap">
			<h2 class="ms-settings-title"><i class="fa fa-credit-card"></i> <?php  
					_e( 'Billing', MS_TEXT_DOMAIN ); 
					if( ! empty( $_GET['gateway_id'] ) ) {
						$gateway = MS_Model_Gateway::factory( $_GET['gateway_id'] );
						if( $gateway->name ) {
							echo ' - '. $gateway->name;
						}
					} 
				?>
				<a class="add-new-h2" href="admin.php?page=membership-billing&action=edit&invoice_id=0"><?php _e( 'Add New', MS_TEXT_DOMAIN ); ?></a>
			</h2>
			<?php $billing_list->views(); ?>
			<form action="" method="post">
				<?php $billing_list->search_box( __( 'Search user', MS_TEXT_DOMAIN ), 'search'); ?>
				<?php $billing_list->display(); ?>
			</form>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}		
}