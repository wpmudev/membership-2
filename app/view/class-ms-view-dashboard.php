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
 * Renders Membership Dashboard.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_View_Dashboard extends MS_View {

	protected $data;
	
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
		?>
		<div class='ms-wrap'>
			<div class="icon32" id="icon-index"><br></div>
			<h2 class='ms-settings-title'><i class="fa fa-bar-chart-o"></i> <?php _e( 'Membership Dashboard', MS_TEXT_DOMAIN );?></h2>		
			<div id="dashboard-widgets-wrap">
				<div class="metabox-holder" id="dashboard-widgets">
					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="normal-sortables">
							<?php $this->dashboard_members_html();?>
						</div>
					</div>
					<div style="width: 49%;" class="postbox-container">
						<div class="meta-box-sortables ui-sortable" id="side-sortables">
							<?php $this->dashboard_news_html();?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function dashboard_news_html() {
		?>
			<div class="postbox " id="ms-dashboard-news">
			<h3 class="hndle"><span><?php _e( 'News', MS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php if( ! empty( $this->data['news'] ) ): ?>
						<?php foreach( $this->data['news'] as $key => $news_item):?>
							<p class="ms-news-item"> [ <?php echo date( MS_Helper_Period::DATE_TIME_FORMAT, strtotime( $news_item->post_modified ) ); ?> ]
								<?php  echo $news_item->description; ?>
							</p>
						<?php endforeach;?>
					<?php else: ?>
						<p><?php _e( 'There will be some interesting news here when your site gets going.', MS_TEXT_DOMAIN ); ?>		
					<?php endif;?>
					<br class="clear">
				</div>
			</div>
		<?php 
	}
	
	public function dashboard_members_html() {
		?>
		<div class="postbox " id="ms-dashboard-members">
			<h3 class="hndle"><span><?php _e( 'Members', MS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p>Membership protection is <?php echo $this->data['plugin_enabled'] ? __( 'Enabled', MS_TEXT_DOMAIN ) : __( 'Disabled', MS_TEXT_DOMAIN ); ?></p>
				<h4><?php _e( 'Membership breakdown', MS_TEXT_DOMAIN );?></h4>
				<table class="ms-membership-breakdown-wrapper">
					<tr>
						<th><?php _e( 'Membership', MS_TEXT_DOMAIN); ?></th>
						<th><?php _e( 'Count', MS_TEXT_DOMAIN); ?></th>
					</tr>
					<?php foreach( $this->data['memberships'] as $membership ): ?>
						<tr>
							<td>
								<span><?php echo $membership['name'];?></span>
							</td>
							<td>
								<span><?php echo $membership['count']?></span>
							</td>
						</tr>						
					<?php endforeach;?>
				</table>
			</div>
		</div>
		<?php 		
	}
}