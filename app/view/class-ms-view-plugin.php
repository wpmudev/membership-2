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

class MS_View_Plugin extends MS_View {
	
	
	public function _to_html() {
		echo $this->test; ?>
		<h1>TO HTML</h1>
		<?php
	}
	
	public function render_me() {		
		ob_start();
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'>Title</h2>		
			<div class='ms-tab-container'>
		       <ul id="sortable-units" class="ms-tabs" style="">
		               <li class="ms-tab">
		                   <a class="ms-tab-link" href="#">Link Title</a>                                                         
		               </li>

		               <li class="ms-tab active">
		                   <a class="ms-tab-link" href="#">Active Link Title</a>                                                         
		               </li>

		               <li class="ms-tab">
		                   <a class="ms-tab-link" href="#">Link Title</a>                                                         
		               </li>
		       </ul>
		   </div>		
		<?php
		call_user_func( array( $this, 'render_' . str_replace('-', '_', $_GET['page'] ) ) );
		?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	// Render Membership Page
	public function render_membership() {
		?>
	   <div class='ms-settings'>
		   Membership
	       <form>....</form>
	   </div>
		<?php
	}
	
	public function render_membership_settings() {
		?>
	   <div class='ms-settings'>
		   Membership Settings
	       <form>....</form>
	   </div>
		<?php
	}
		
}