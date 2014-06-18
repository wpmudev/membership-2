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


class MS_View_Member_List extends MS_View {
	
	public function to_html() {		

		$member_list = new MS_Helper_List_Table_Member();
		$member_list->prepare_items();

		ob_start();
		?>
		
		<div class="wrap ms-wrap">
			<h2 class="ms-settings-title">
				<i class="fa fa-users"></i> 
				<?php _e( 'Members', MS_TEXT_DOMAIN ); ?>
			</h2>
			<?php $member_list->views(); ?>
			<form method="post">
				<?php $member_list->search_box('Search', 'search'); ?>
				<?php $member_list->display(); ?>
			</form>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}