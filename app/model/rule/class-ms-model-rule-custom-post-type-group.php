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


class MS_Model_Rule_Custom_Post_Type_Group extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content() {
		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
	}
	
	/**
	 * Adds filter for posts query to remove all protected custom post types.
	 *
	 * @since 4.0
	 * @action pre_get_posts 
	 *
	 * @access public
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		$post_type = $wp_query->get( 'post_type' );
		
		/**
		 * Only protect if cpt group.
		 * Protect in list rather than on a single post.
		 * Workaroudn to invalidate the query. 
		 */
		if( ! MS_Plugin::instance()->addon->cpt_post_by_post ) {
			if ( ! $wp_query->is_singular && empty( $wp_query->query_vars['pagename'] ) && ! empty( $post_type ) &&
			  		in_array( $post_type, self::get_custom_post_types() ) && ! in_array( $post_type, $this->rule_value ) )  {
				$wp_query->query_vars['post__in'] = array( 0 => 0 );
			}
		}
	}
	
	/**
	 * Verify access to the current post.
	 * @return boolean
	 */
	public function has_access( $post_id = null ) {
		
		$has_access = false;
		/**
		 * Only verify permission if NOT ruled by cpt post by post.
		 */
		if( ! MS_Plugin::instance()->addon->cpt_post_by_post ) {
			$has_access = false;
			if( ! empty( $post_id ) ) {
				$post = get_post( $post_id );
			}
			else {	
				$post = get_queried_object();
			}
			$post_type = ! empty( $post->post_type ) ? $post->post_type : '';
			if( is_a( $post, 'WP_Post' ) && in_array( $post_type, self::get_custom_post_types() ) && in_array( $post_type, $this->rule_value ) )  {
				$has_access = true;
			}
		}

		return $has_access;
	}
	
	/**
	 * Prepare content to be shown in list table.
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
	public function get_content( $args = null ) {
		$cpts = self::get_custom_post_types();
		
		$contents = array();
		foreach( $cpts as $key => $content ) {
			$contents[ $key ] = new StdClass();
			$contents[ $key ]->id = $key;
			$contents[ $key ]->type = $this->rule_type;
			if( in_array( $key, $this->rule_value ) ) {
				$contents[ $key ]->access = true;
			}
			else {
				$contents[ $key ]->access = false;
			}
		}
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		return $contents;
	}
	/**
	 * Get post types that should not be protected.
	 * Default WP post types, membership post types
	 * @return array The excluded post types.
	 */
	public static function get_excluded_content() {
		return apply_filters( 'ms_model_rule_custom_post_type_group_get_excluded_content', array(
				'post',
				'page',
				'attachment',
				'revision',
				'nav_menu_item',
				'ms_membership',
				'ms_transaction',
				'ms_communication',
				'ms_coupon',
		) );
	}
	/**
	 * Get custom post types.
	 * 
	 * Excludes membership plugin and default wp post types. 
	 * @return array
	 */
	public static function get_custom_post_types() {
		$cpts = get_post_types();
		$excluded = self::get_excluded_content();
		return apply_filters( 'ms_model_rule_custom_post_type_group_get_custom_post_types', array_diff( $cpts, $excluded ) );
		
	}
}