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
 * Renders Plugin options.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_View_Plugin extends MS_View {
		
	/**
	 * Overrides parent's _to_html() method.
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