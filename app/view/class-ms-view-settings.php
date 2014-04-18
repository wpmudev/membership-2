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
 * Renders Membership Plugin Settings.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_View_Settings extends MS_View {
		
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
		ob_start();

		/** Setup navigation tabs. */
		$tabs = array(
			'general' => array(
					'title' =>	__( 'General', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=general',
			),
			'pages' => array(
					'title' =>	__( 'Pages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=pages',
			),
			'payment' => array(
					'title' =>	__( 'Payment', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=payment',
			),
			'messages-protection' => array(
					'title' =>	__( 'Protection Messages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=messages-protection',
			),
			'messages-automated' => array(
					'title' =>	__( 'Automated Messages', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=messages-automated',
			),			
			'downloads' => array(
					'title' =>	__( 'Media / Downloads', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=downloads',
			),
			'repair' => array(
					'title' =>	__( 'Verify and Repair', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-settings&tab=repair',
			),												
		);
		
		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
		<h2 class='ms-settings-title'><?php  _e( 'Membership Settings', MS_TEXT_DOMAIN ) ; ?></h2>		

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

	public function render_general() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'General Settings', MS_TEXT_DOMAIN ) ; ?>	
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_pages() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Page Settings', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_payment() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Payment Settings', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_messages_protection() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Protection Messages', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}

	public function render_messages_automated() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Automated Messages', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}

	public function render_downloads() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Media / Download Settings', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}
	
	public function render_repair() {
		?>
	   <div class='ms-settings'>
		   <?php  _e( 'Verify and Repair', MS_TEXT_DOMAIN ) ; ?>
	       <form id="setting_form" method="post">
	
		   </form>
	   </div>
		<?php
	}	
	
		
}