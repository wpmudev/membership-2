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
 * @since 1.0.0
 * 
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Membership_Metabox extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_ACCESS = 'toggle_metabox_access';

	/**
	 * The custom post type used with Memberships and access.
	 *
	 * @since 1.0.0
	 * 
	 * @var array
	 */
	private $post_types;

	/**
	 * The metabox ID.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	private $metabox_id = 'ms-membership-access';

	/**
	 * The metabox title.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	private $metabox_title;

	/**
	 * Context for showing the metabox.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	private $context = 'side';

	/**
	 * Metabox priority.
	 *
	 * Effects position in the metabox hierarchy.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	private $priority = 'high';

	/**
	 * Prepare the metabox.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->metabox_title = __( 'Membership Access', MS_TEXT_DOMAIN );

		$post_types = array_merge(
			array( 'page', 'post', 'attachment' ),
			MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types()
		);
		$this->post_types = apply_filters( 'ms_controller_membership_metabox_add_meta_boxes_post_types', $post_types );

		if( MS_Plugin::instance()->settings->plugin_enabled ) {
			$this->add_action( 'add_meta_boxes', 'add_meta_boxes', 10 );

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
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_metabox_access() {

		$fields = array( 'membership_id', 'rule_type', 'post_id' );
		if( $this->verify_nonce() && $this->validate_required( $fields ) && $this->is_admin_user() ) {
			$this->toggle_membership_access( $_POST['post_id'], $_POST['rule_type'], $_POST['membership_id'] );
			if( $_POST['membership_id'] == MS_Model_Membership::get_protected_content()->id ) {
				$post = get_post( $_POST['post_id'] );
				//membership metabox html returned via ajax response 
				$this->membership_metabox( $post );
			}
			else {
				echo true;
			}
		}
		
		do_action( 'ms_controller_membership_metabox_ajax_action_toggle_metabox_access', $this );
		exit;
	}

	/**
	 * Add the metabox for defined post types.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		
		foreach( $this->post_types as $post_type ) {
			add_meta_box( $this->metabox_id, $this->metabox_title, array( $this, 'membership_metabox' ), $post_type, $this->context, $this->priority );
		}

		do_action( 'ms_controller_membership_metabox_add_meta_boxes', $this );
	}

	/**
	 * Membership metabox callback function for displaying the UI.
	 *
	 * @since 1.0.0
	 * 
	 * @param object $post The current post object.
	 */
	public function membership_metabox( $post ) {
		
		$data = array();

		if( 'page' == $post->post_type && MS_Factory::load( 'MS_Model_Pages')->is_ms_page( $post->ID ) ) {
			$data['special_page'] = true;
		}
		else {
			$memberships = MS_Model_Membership::get_memberships();
			$protected_content = MS_Model_Membership::get_protected_content();
			$data['protected_content'] = $protected_content;
			$data['protected_content_enabled'] = ! $protected_content->has_access_to_post( $post->ID );
			
			$rule = $this->get_rule( $protected_content, $post );
			$data['rule_type'] = $rule->rule_type;
			foreach( $memberships as $membership ) {
				$rule = $this->get_rule( $membership, $post );
				
				$data['access'][ $membership->id ]['has_access'] =  $membership->has_access_to_post( $post->ID );

				$data['access'][ $membership->id ]['name'] = $membership->name;
			}
		}
		$data['post_id'] = $post->ID;
		$data['read_only'] =$this->is_read_only( $post->post_type );
		
		$view = MS_Factory::create( 'MS_View_Membership_Metabox' );
		$view->data = apply_filters( 'ms_view_membership_metabox_data', $data, $this );
		$view->render();
	}

	/**
	 * Get rule accordinly to post type.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership The membership to get rule from.
	 * @param object $post The current post object.
	 * @return MS_Model_Rule The rule model.
	 */
	private function get_rule( $membership, $post ) {
		$rule = null;
		$post_type = null;
		
		if( 'attachment' == $post->post_type ) {
			$parent_id = $post->post_parent;
			$post_type = get_post_type( $parent_id );
				
		}
		else {
			$post_type = $post->post_type;
		}
		
		switch( $post_type ) {
			case 'post':
				$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
				break;
			default:
				if( in_array( $post_type, MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types() ) ) {
					if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST) ) {
						$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
					}
					else {
						$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
					}
				}
				else {
					$rule = $membership->get_rule( $post_type );
				}
				break;
		}
		
		return apply_filters( 'ms_controller_metabox_get_rule', $rule, $membership, $post, $this );
	}

	/**
	 * Toggle membership access.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post id or attachment id to save access to.
	 * @param string $rule_type The membership rule type.
	 * @param array $membership_id The membership id to toggle access
	 */
	public function toggle_membership_access( $post_id, $rule_type, $membership_id ) {

		if( $this->is_admin_user() ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			
			$rule = $membership->get_rule( $rule_type );
			
			if( $rule ) {
				$rule->toggle_access( $post_id );
				$membership->set_rule( $rule_type, $rule );
				$membership->save();
			}
		}
				
		do_action( 'ms_controller_membership_metabox_toggle_membership_access', $post_id, $rule_type, $membership_id, $this );
	}

	/**
	 * Determine whether Membership access can be changed or is read-only.
	 *
	 * @since 1.0.0
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

		return apply_filters( 'ms_controller_membership_metabox_is_read_only', $read_only, $post_type, $this );
	}

	/**
	 * Load Membership Metabox specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts() {
		global $post_type;
		if ( in_array( $post_type, $this->post_types ) && ! $this->is_read_only( $post_type ) ) {
			wp_enqueue_script( 'membership-metabox' );
			wp_enqueue_script( 'ms-admin' );
		}

	}
}

?>