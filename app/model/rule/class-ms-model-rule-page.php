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


class MS_Model_Rule_Page extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public function on_protection() {
		
	}
	
	public function get_content() {
		$posts_to_show = 10; //TODO
// 		$exclude = array();
// 		foreach ( array( 'registration_page', 'account_page', 'subscriptions_page', 'nocontent_page', 'registrationcompleted_page' ) as $page ) {
// 			if ( isset( $M_options[$page] ) && is_numeric( $M_options[$page] ) ) {
// 				$exclude[] = $M_options[$page];
// 			}
// 		}
		$posts = apply_filters( 'membership_hide_protectable_pages', get_posts( array(
				'numberposts' => $posts_to_show,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => 'page',
				'post_status' => array('publish', 'virtual'), //Classifieds plugin uses a "virtual" status for some of it's pages
// 				'exclude'     => $exclude,
		) ) );
		if( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				$content[ $post->ID ] = esc_html( $post->post_title );
			}
		}
		return $content;
		
	}
	
}