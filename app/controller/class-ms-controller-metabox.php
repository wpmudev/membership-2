<?php
/**
 * Creates the Membership access metabox.
 *
 * Creates simple access control UI for Posts/Page edit pages.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Metabox extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_ACCESS = 'toggle_metabox_access';

	/**
	 * The custom post type used with Memberships and access.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	private $post_types;

	/**
	 * The metabox ID.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	private $metabox_id = 'ms-membership-access';

	/**
	 * The metabox title.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	private $metabox_title;

	/**
	 * Context for showing the metabox.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	private $context = 'side';

	/**
	 * Metabox priority.
	 *
	 * Effects position in the metabox hierarchy.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	private $priority = 'high';

	/**
	 * Prepare the metabox.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->metabox_title = __( 'Membership Access', 'membership2' );

		$extra = array();

		/* start:pro */
		$extra = MS_Rule_CptGroup_Model::get_custom_post_types();
		/* end:pro */

		$post_types = array_merge(
			array( 'page', 'post', 'attachment' ),
			$extra
		);

		$this->post_types = apply_filters(
			'ms_controller_membership_metabox_add_meta_boxes_post_types',
			$post_types
		);

		if ( ! MS_Plugin::is_enabled() ) {
			return $this;
		}

		$this->add_action(
			'add_meta_boxes',
			'add_meta_boxes',
			10
		);

		$this->add_action(
			'admin_enqueue_scripts',
			'admin_enqueue_scripts'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_TOGGLE_ACCESS,
			'ajax_action_toggle_metabox_access'
		);

		// Populates the WP editor with default contents of a page
		$this->add_action(
			'the_editor_content',
			'show_default_content'
		);
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_toggle_metabox_access
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_toggle_metabox_access() {
		$fields = array( 'membership_id', 'rule_type', 'post_id' );

		if (
			$this->verify_nonce()
			&& self::validate_required( $fields )
			&& $this->is_admin_user()
		) {
			$this->toggle_membership_access(
				$_POST['post_id'],
				$_POST['rule_type'],
				$_POST['membership_id']
			);

			$post = get_post( $_POST['post_id'] );

			// Return the updated Membership metabox html via ajax response.
			$this->membership_metabox( $post );

			do_action(
				'ms_controller_membership_metabox_ajax_action_toggle_metabox_access',
				$post,
				$this
			);
		}

		exit;
	}

	/**
	 * Add the metabox for defined post types.
	 *
	 * @since  1.0.0
	 */
	public function add_meta_boxes() {
		if ( defined( 'MS_CPT_ENABLE_ACCESS_BOX' ) && MS_CPT_ENABLE_ACCESS_BOX ) {
			$extra = array();

			/* start:pro */
			$extra = MS_Rule_CptGroup_Model::get_custom_post_types();
			/* end:pro */

			$post_types = array_merge(
				array( 'page', 'post', 'attachment' ),
				$extra
			);

			$this->post_types = apply_filters(
				'ms_controller_membership_metabox_add_meta_boxes_post_types',
				$post_types
			);
		}

		foreach ( $this->post_types as $post_type ) {
			if ( ! $this->is_read_only( $post_type ) ) {
				add_meta_box(
					$this->metabox_id,
					$this->metabox_title,
					array( $this, 'membership_metabox' ),
					$post_type,
					$this->context,
					$this->priority
				);
			}
		}

		do_action(
			'ms_controller_membership_metabox_add_meta_boxes',
			$this
		);
	}

	/**
	 * Membership metabox callback function for displaying the UI.
	 *
	 * @since  1.0.0
	 *
	 * @param object $post The current post object.
	 */
	public function membership_metabox( $post ) {
		$data = array();

		if ( MS_Model_Pages::is_membership_page() ) {
			$data['special_page'] = true;
		} else {
			$all_memberships = MS_Model_Membership::get_memberships();
			$base = MS_Model_Membership::get_base();
			$data['base_id'] = $base->id;

			// Find the post-type of the current post.
			if ( 'attachment' == $post->post_type ) {
				$parent_id = $post->post_parent;
				$post_type = get_post_type( $parent_id );
			} else {
				$post_type = $post->post_type;
			}

			// Get the base protection rule and check if post is protected.
			$rule = $this->get_rule( $base, $post_type );
			$data['is_protected'] = ! $rule->has_access( $post->ID, false );
			$data['rule_type'] = $rule->rule_type;

			// Check each membership to see if the post is protected.
			foreach ( $all_memberships as $membership ) {
				if ( $membership->is_base ) { continue; }

				$rule = $this->get_rule( $membership, $post_type );
				$data['access'][ $membership->id ]['has_access'] = $rule->get_rule_value( $post->ID );
				$data['access'][ $membership->id ]['name'] = $membership->name;
			}
		}
		$data['post_id'] = $post->ID;
		$data['read_only'] = $this->is_read_only( $post->post_type );

		$view = MS_Factory::create( 'MS_View_Metabox' );
		$view->data = apply_filters(
			'ms_view_membership_metabox_data',
			$data,
			$this
		);
		$view->render();
	}

	/**
	 * Get rule accordingly to post type.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Membership The membership to get rule from.
	 * @param string $post_type The post_type name of the queried post object.
	 * @return MS_Rule The rule model.
	 */
	private function get_rule( $membership, $post_type ) {
		$rule = null;

		switch ( $post_type ) {
			case 'post':
				$rule = $membership->get_rule( MS_Rule_Post::RULE_ID );
				break;

			case 'page':
				$rule = $membership->get_rule( MS_Rule_Page::RULE_ID );
				break;

			case 'attachment':
				$rule = $membership->get_rule( MS_Rule_Media::RULE_ID );
				break;

			default:
				$rule = $membership->get_rule( $post_type );

				/* start:pro */
				if ( in_array( $post_type, MS_Rule_CptGroup_Model::get_custom_post_types() ) ) {
					if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
						$rule = $membership->get_rule( MS_Rule_CptItem::RULE_ID );
					} else {
						$rule = $membership->get_rule( MS_Rule_CptGroup::RULE_ID );
					}
				}
				/* end:pro */
				break;
		}

		return apply_filters(
			'ms_controller_metabox_get_rule',
			$rule,
			$membership,
			$post_type,
			$this
		);
	}

	/**
	 * Toggle membership access.
	 *
	 * @since  1.0.0
	 *
	 * @param int $post_id The post id or attachment id to save access to.
	 * @param string $rule_type The membership rule type.
	 * @param array $membership_id The membership id to toggle access
	 */
	public function toggle_membership_access( $post_id, $rule_type, $membership_id ) {
		if ( $this->is_admin_user() ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			$rule = $membership->get_rule( $rule_type );
			$protected = ! $rule->get_rule_value( $post_id );

			if ( $membership->is_base() ) {
				/*
				 * If we just modified the protection for the whole post then we
				 * have to update every single membership with the new rule
				 * value before changing the base rule itself.
				 */
				$all_memberships = MS_Model_Membership::get_memberships();

				foreach ( $all_memberships as $the_membership ) {
					if ( $the_membership->is_base ) { continue; }

					$the_rule = $the_membership->get_rule( $rule_type );
					if ( $protected ) {
						$the_rule->give_access( $post_id );
					} else {
						$the_rule->remove_access( $post_id );
					}

					$the_membership->set_rule( $rule_type, $the_rule );
					$the_membership->save();
				}
			}

			if ( $rule ) {
				if ( $protected ) {
					$rule->give_access( $post_id );
				} else {
					$rule->remove_access( $post_id );
				}

				$membership->set_rule( $rule_type, $rule );
				$membership->save();
			}
		}

		do_action(
			'ms_controller_membership_metabox_toggle_membership_access',
			$post_id,
			$rule_type,
			$membership_id,
			$this
		);
	}

	/**
	 * Determine whether Membership access can be changed or is read-only.
	 *
	 * @since  1.0.0
	 * @param string $post_type The post type of the post.
	 * @return bool
	 */
	public function is_read_only( $post_type ) {
		if ( 'post' == $post_type
			&& ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST )
		) {
			$read_only = true;
		} elseif ( 'attachment' == $post_type ) {
			$read_only = true;
			/* start:pro */
		} elseif ( in_array( $post_type, MS_Rule_CptGroup_Model::get_custom_post_types() ) ) {
			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
				$read_only = false;
			} else {
				$read_only = true;
			}
			/* end:pro */
		} else {
			$read_only = false;
		}

		return apply_filters(
			'ms_controller_membership_metabox_is_read_only',
			$read_only,
			$post_type,
			$this
		);
	}

	/**
	 * Filter returns the default contents of a Membership Page if the URL param
	 * &ms-default=1 is set.
	 *
	 * Effectively this will display the default contents inside the Post-Editor
	 * without changing the page itself. Only after the user saves the content
	 * it will affect the Membership page
	 *
	 * @since  1.0.0
	 * @param  string $content Default page content.
	 * @return string Modified page content.
	 */
	public function show_default_content( $content ) {
		static $Message = false;
		global $post, $post_type;

		if ( ! isset( $_GET['ms-default'] ) ) { return $content; }
		if ( '1' != $_GET['ms-default'] ) { return $content; }
		if ( 'page' != $post_type ) { return $content; }

		$ms_page = MS_Model_Pages::get_page_by( 'id', $post->ID );

		if ( empty( $ms_page ) ) { return $content; }

		$type = MS_Model_Pages::get_page_type( $ms_page );

		if ( ! $Message ) {
			$Message = true;
			lib3()->ui->admin_message(
				__(
					'<strong>Tipp</strong>:<br />' .
					'The page content is reset to the default content but is <em>not saved yet</em>!<br />' .
					'You can simply close this page to keep your current page contents.',
					'membership2'
				)
			);
		}

		return MS_Model_Pages::get_default_content( $type );
	}

	/**
	 * Load Membership Metabox specific scripts.
	 *
	 * @since  1.0.0
	 */
	public function admin_enqueue_scripts() {
		global $post_type;

		if ( in_array( $post_type, $this->post_types )
			&& ! $this->is_read_only( $post_type )
		) {
			lib3()->ui->data( 'ms_data', array( 'ms_init' => array( 'metabox' ) ) );
			wp_enqueue_script( 'ms-admin' );
		}
	}
}
