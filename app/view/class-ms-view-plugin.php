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
	
	
	// public function _to_html() {
	public function _test() {		
		echo $this->test; ?>
		<h1>TO HTML</h1>
		<?php
	}
	
	public function _to_html() {		
		ob_start();

		/** Setup navigation tabs. */
		$tabs = array(
			'settings' => __( 'General Settings', MS_TEXT_DOMAIN ),
			'advanced' => __( 'Advanced Settings', MS_TEXT_DOMAIN ),						
		);
		
		$active_tab = $_GET['tab'] ? $_GET['tab'] : 'settings';

		if ( !array_key_exists( $active_tab, $tabs ) ) { $active_tab = 'settings'; }
		
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'>Membership Settings</h2>		
			<div class='ms-tab-container'>
		       <ul id="sortable-units" class="ms-tabs" style="">
					<?php foreach( $tabs as $tab => $title ) { ?>
		               <li class="ms-tab <?php echo $tab == $active_tab ? 'active' : ''; ?> ">
		                   <a class="ms-tab-link" href="edit.php?post_type=ms_membership&page=membership-settings&tab=<?php echo $tab; ?>"><?php echo $title; ?></a>                                                         
		               </li>
					<?php } ?>
		       </ul>
		   </div>		
		<?php

		/** Call the appropriate form to render. */
		call_user_func( array( $this, 'render_' . str_replace('-', '_', $active_tab ) ) );

		?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function render_settings() {
		?>
	   <div class='ms-settings'>
		   General Settings
	       <form>....</form>
	   </div>
		<?php
	}
	
	public function render_advanced() {
		?>
	   <div class='ms-settings'>
		   Advanced Settings
	       <form>....</form>
	   </div>
		<?php
	}
	
		
}