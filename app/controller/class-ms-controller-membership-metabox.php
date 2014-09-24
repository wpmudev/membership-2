<?php
/**
 * This file defines the MS_Controller_Membership_Metabox class.
 *
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
 * Creates the Membership access metabox.
 *
 * Creates simple access control UI for Posts/Page edit pages.
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Membership_Metabox extends MS_Controller {

	const AJAX_ACTION_TOGGLE_ACCESS = 'toggle_metabox_access';
	
	/**
	 * The custom post type used with Memberships and access.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $post_type
	 */	
	private $post_types;
	
	/**
	 * The metabox ID.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $metabox_id
	 */	
	private $metabox_id = 'membership_access';
	
	/**
	 * The metabox title.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $metabox_title
	 */
	private $metabox_title;
	
	/**
	 * Context for showing the metabox.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $context
	 */
	private $context = 'side';

	/**
	 * Metabox priority.
	 *
	 * Effects position in the metabox hierarchy.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $priority
	 */	
	private $priority = 'high';
	
	/**
	 * Prepare the metabox.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {		
		$this->metabox_title = __( 'Membership Access', MS_TEXT_DOMAIN );
		$post_types = array_merge( array( 'page', 'post', 'attachment' ), MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() );
		$this->post_types = apply_filters( 'ms_controller_membership_metabox_add_meta_boxes_post_types', $post_types );
		
		if( MS_Plugin::instance()->settings->plugin_enabled ) {
			$this->add_action( 'add_meta_boxes', 'add_meta_boxes', 10 );
// 			$this->add_action( 'save_post', 'save_metabox_data', 10, 2 );
// 			$this->add_action( 'attachment_fields_to_save', 'save_attachment_data' );
			$this->add_action( 'admin_enqueue_scripts', 'admin_enqueue_scripts' );
			$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_ACCESS, 'ajax_action_toggle_metabox_access' );
		}
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_metabox_access
	 *
	 * @since 4.0.0
	 */
	public function ajax_action_toggle_metabox_access() {

		$fields = array( 'membership_id', 'post_type', 'post_id' );
		if( $this->verify_nonce() && $this->validate_required( $fields ) && $this->is_admin_user() ) {
			$this->toggle_membership_access( $_POST['post_id'], $_POST['post_type'], $_POST['membership_id'] );
			echo true;
		}
		exit;
	}
	
	/**
	 * Add the metabox for this post/page.
	 *
	 * @since 4.0.0
	 */			
	public function add_meta_boxes() {
		foreach( $this->post_types as $post_type ) {
			add_meta_box( $this->metabox_id, $this->metabox_title, array( $this, 'membership_metabox' ), $post_type, $this->context, $this->priority );
		}
	
	}

	/**
	 * Membership metabox callback function for displaying the UI.
	 *
	 * @since 4.0.0
	 * @param object $post The current post object.
	 */			
	public function membership_metabox( $post ) {	
		$view = apply_filters( 'ms_view_membership_metabox', new MS_View_Membership_Metabox() );
		
		$settings = MS_Plugin::instance()->settings;
		$data = array();

		if( 'page' == $post->post_type && MS_Plugin::instance()->settings->is_special_page( $post->ID ) ) {
			$view->special_page = true;
		}
		else {
			$memberships = MS_Model_Membership::get_memberships();
			foreach( $memberships as $membership ) {
				$rule_type = $post->post_type;
				switch( $rule_type ) {
					case 'post':
						$post_rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
						$category_rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
						$data['access'][ $membership->id ]['has_access'] =  $membership->rules['post']->has_access( $post->ID ) || $membership->rules['category']->has_access( $post->ID );
						$data['access'][ $membership->id ]['dripped'] = $membership->rules['post']->has_dripped_rules( $post->ID );
						break;
					case 'attachment':
						$parent_id = $post->post_parent;
						$parent = get_post( $parent_id );
						
						$post_rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
						$category_rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
						$data['access'][ $membership->id ]['has_access'] =  $membership->rules['post']->has_access( $parent_id ) || $membership->rules['category']->has_access( $parent_id );
						$data['access'][ $membership->id ]['dripped'] = $membership->rules['post']->has_dripped_rules( $parent_id );
						break;
					default:
						if( in_array( $rule_type, MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() ) ) {
							$rule_cpt = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
							$rule_cpt_group = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
							$data['access'][ $membership->id ]['has_access'] = $rule_cpt->has_access( $post->ID ) || $rule_cpt_group->has_access( $post->ID );
							$data['access'][ $membership->id ]['dripped'] = false;
						}
						else {
							$rule = $membership->get_rule( $rule_type );
							$data['access'][ $membership->id ]['has_access'] = $rule->has_access( $post->ID );
							$data['access'][ $membership->id ]['dripped'] = $rule->has_dripped_rules( $post->ID );
						}
					break;
				}
				$data['access'][ $membership->id ]['name'] = $membership->name;
			}
		}
		$data['post_id'] = $post->ID;
		$data['post_type'] = $post->post_type;
		$view->data = $data;
		$view->read_only = $this->is_read_only( $post->post_type );
		
		$view->render();
	}
	
	/**
	 * Save the metabox data for given post.
	 *
	 * @deprecated
	 * @todo Consider whether both parameters are needed.
	 *
	 * @since 4.0.0
	 * @param int $post_id The ID this metabox applies to.
	 * @param object $post The post object.
	 */			
	public function save_metabox_data( $post_id, $post ) {
		if( empty( $post_id ) || empty( $post ) ) return;
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if( is_int( wp_is_post_revision( $post ) ) ) return;
		if( is_int( wp_is_post_autosave( $post ) ) ) return;
		if( ! $this->is_admin_user() ) {
			return;
		}

		if( $this->verify_nonce( MS_View_Membership_Metabox::MEMBERSHIP_METABOX_NONCE, 'POST', MS_View_Membership_Metabox::MEMBERSHIP_METABOX_NONCE ) ) {
			$rule_type = $post->post_type;
			if( ! empty( $_POST['ms_access'] ) && in_array( $post->post_type, $this->post_types ) ) {
				$this->save_membership_access( $post_id, $post->post_type, $_POST['ms_access'] );
			}
		}
	}
	
	/**
	 * Save the metabox data for given attachment.
	 *
	 * @deprecated 
	 * Media access is determined by parent post.
	 *   
	 * @since 4.0.0
	 * @filter attachment_fields_to_save
	 * @param array $post_data The $_POST data.
	 * @return array $post_data
	 */
	public function save_attachment_data( $post_data ) {
		if( ! $this->is_admin_user() ) {
			return;
		}
				
		if( $this->verify_nonce( MS_View_Membership_Metabox::MEMBERSHIP_METABOX_NONCE, 'POST', MS_View_Membership_Metabox::MEMBERSHIP_METABOX_NONCE ) ) {
			if( ! empty( $post_data['post_type'] ) && ! empty( $post_data['post_ID'] ) && ! empty( $post_data['ms_access'] ) ) {
				$this->save_membership_access( $post_data['post_ID'], $post_data['post_type'], $post_data['ms_access'] );
			}
		}		
		return $post_data;
	}
	
	/**
	 * Save membership access information.
	 * 
	 * @deprecated
	 * @since 4.0.0
	 * 
	 * @param int $post_id The post id or attachment id to save access to.
	 * @param string $post_type The post type dictates with rule_type is used.
	 * @param array $membership_access The access information to save, membership_id => access. 
	 */
	public function save_membership_access( $post_id, $post_type, $membership_access ) {
		$rule_type = $post_type;
		if( ! empty( $membership_access ) && in_array( $post_type, $this->post_types ) ) {
			foreach( $membership_access as $membership_id => $has_access ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
				if( in_array( $rule_type, MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() ) ) {
					$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
				}
				else {
					$rule = $membership->get_rule( $rule_type );
				}
				if( $rule ) {
					if( $has_access ) {
						$rule->give_access( $post_id );
					}
					else {
						$rule->remove_access( $post_id );
					}
					$membership->set_rule( $rule_type, $rule );
					$membership->save();
				}
			}
		}
	}
	
	/**
	 * Toggle membership access.
	 *
	 * @since 4.0.0
	 *
	 * @param int $post_id The post id or attachment id to save access to.
	 * @param string $post_type The post type dictates with rule_type is used.
	 * @param array $membership_id The membership id to toggle access 
	 */
	public function toggle_membership_access( $post_id, $post_type, $membership_id ) {
		$rule_type = $post_type;
		if( in_array( $post_type, $this->post_types ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			if( in_array( $rule_type, MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() ) ) {
				$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
			}
			else {
				$rule = $membership->get_rule( $rule_type );
			}
			if( $rule ) {
				$rule->toggle_access( $post_id );
				$membership->set_rule( $rule_type, $rule );
				$membership->save();
			}
		}
	}
	
	/**
	 * Determine whether Membership can be changed or is read-only.
	 *
	 * @since 4.0.0
	 * @param string $post_type The post type of the post.
	 * @return bool 
	 */		
	public function is_read_only( $post_type ) {
		if( 'post' == $post_type && ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$read_only = true;
		}
		elseif( 'attachment' == $post_type ) {
			$read_only = true;
		}
		elseif( in_array( $post_type, MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() ) ) {
			if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
				$read_only = false;
			}
			else {
				$read_only = true;
			}
		}
		else {
			$read_only = false;
		}
		return $read_only;
	}
	
	/**
	 * Load Membership Metabox specific scripts.
	 *
	 * @since 4.0.0
	 */	
	public function admin_enqueue_scripts() {
		global $post_type;
		if( in_array( $post_type, $this->post_types ) && ! $this->is_read_only( $post_type ) ) {
			wp_register_script( 'membership-metabox', MS_Plugin::instance()->url. 'app/assets/js/ms-view-membership-metabox.js' );
			wp_enqueue_script( 'membership-metabox' );
			wp_enqueue_script( 'ms-functions' );
		}
		
	}
}

?>