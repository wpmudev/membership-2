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
	
	
	// Example of how to access passed data
	public function _test() {		
		echo $this->_data['test']; ?>
		<h1>TO HTML</h1>
		<?php
	}
	
	public function _to_html() {		
		ob_start();

		/** Setup navigation tabs. */
		$tabs = array(
			'settings' => array(
					'title' =>	__( 'General Settings', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=settings',
			),
			'pages' => array(
					'title' =>	__( 'Membership Pages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=pages',
			),
			'messages' => array(
					'title' =>	__( 'Protection Messages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=messages',
			),
			'media' => array(
					'title' =>	__( 'Media Protection', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=media',
			),
			'defaults' => array(
					'title' =>	__( 'Default Settings', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=defaults',
			),
			'advanced' => array(
					'title' =>	__( 'Advanced', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=advanced',
			),												
		);
		
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'>Membership Settings</h2>		

		<?php
		$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
		
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
	       <form id="setting_form" method="post">
			<h3>Default Subscription</h3>
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_pages() {
		?>
	   <div class='ms-settings'>
		   Pages Settings
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}

	public function render_messages() {
		?>
	   <div class='ms-settings'>
		   Messages Settings
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_media() {
		?>
	   <div class='ms-settings'>
		   Media Settings
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}

	public function render_defaults() {
		?>
	   <div class='ms-settings'>
		   Default Settings
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_advanced() {
		?>
	   <div class='ms-settings'>
		   Advanced Settings
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
		
}