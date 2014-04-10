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

class MS_View_Membership extends MS_View {
	
	public function __construct() {
		
	}
	public static function membership_definition_metabox( $post ) {
		$membership = MS_Model_Membership::load( $post->ID );
		ob_start();
		?>
			<div id="post-body-content">
				<div id="titlediv">
					<div id="titlewrap">
						<input id="title" type="text" autocomplete="off" value="<?php echo $membership->name; ?>" size="30" name="post_title">
					</div>
				</div>
			</div>	
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}