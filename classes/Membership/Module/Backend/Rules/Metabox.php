<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * The module responsible for managing rule settings metaboxes.
 *
 * @category Membership
 * @package Module
 * @subpackage Backend
 *
 * @since 3.5
 */
class Membership_Module_Backend_Rules_Metabox extends Membership_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin class.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );
		$this->_add_action( 'add_meta_boxes' );
	}

	/**
	 * Adds meta box to edit access level for current post or page.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $type The current post type.
	 */
	public function add_meta_boxes( $type ) {
		if ( $type != 'post' && $type != 'page' ) {
			return;
		}

		wp_enqueue_style( 'membership-metaboxes', MEMBERSHIP_ABSURL . 'css/metaboxes.css', array(), Membership_Plugin::VERSION );

		$id = 'membership-access-level';
		$title = esc_html__( 'Membership Access Levels', 'membership' );
		$callback = array( $this, 'render_meta_box' );

		add_meta_box( $id, $title, $callback, $type, 'side', 'high' );
	}

	/**
	 * Renders rules metabox.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( WP_Post $post ) {
		// don't render information if it is special page
		if ( $post->post_type == 'page' && $this->_render_special_page_message( $post->ID ) ) {
			return;
		}

		$levels = $this->_wpdb->get_results( sprintf(
			'SELECT id, level_title FROM %s ORDER BY id',
			MEMBERSHIP_TABLE_LEVELS
		), ARRAY_A );

		foreach ( $levels as $level ) {
			$rules = $this->_wpdb->get_results( sprintf(
				"SELECT * FROM %s WHERE level_id = %d AND rule_area = '%ss'",
				MEMBERSHIP_TABLE_RULES,
				$level['id'],
				$post->post_type
			), ARRAY_A );

			$level['rule_ive'] = '';
			$level['rule_ive_text'] = esc_html__( 'Not set', 'membership' );
			if ( !empty( $rules ) ) {
				foreach ( $rules as $rule ) {
					if ( in_array( $post->ID, (array)@unserialize( $rule['rule_value'] ) ) ) {
						$level['rule_ive'] = $rule['rule_ive'];
					}
				}

				if ( empty( $level['rule_ive'] ) ) {
					$level['rule_ive'] = $rules[0]['rule_ive'] == 'negative'
						? 'positive'
						: 'negative';
				}

				switch ( $level['rule_ive'] ) {
					case 'positive':
						$level['rule_ive_text'] = esc_html__( 'Accessible', 'membership' );
						break;
					case 'negative':
						$level['rule_ive_text'] = esc_html__( 'Inaccessible', 'membership' );
						break;
				}
			}

			echo '<div class="misc-pub-section">';
				echo '<span class="membership-level-title">', esc_html( $level['level_title'] ), ':</span>';
				echo '<code class="membership-level-rule-ive ', esc_attr( $level['rule_ive'] ), '">', esc_html( $level['rule_ive_text'] ), '</code>';
			echo '</div>';
		}
	}

	/**
	 * Renders special page notice messages.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @param int $page_id Current page ID.
	 * @return boolean TRUE if it is special page, otherwise FALSE.
	 */
	private function _render_special_page_message( $page_id ) {
		if ( membership_is_registration_page( $page_id, false ) ) {
			?><div class="membership-access-info">
				<p><?php esc_html_e( 'This is the page a new user will be redirected to when they want to register on your site.', 'membership' ) ?></p>
				<p><?php _e( 'You can include an introduction on the page, for more advanced content around the registration form then you <b>should</b> include the <code>[subscriptionform]</code> shortcode in some location on that page. Alternatively leave the page blank for the standard Membership subscription forms.', 'membership' ) ?></p>
			</div><?php

			return true;
		}

		if ( membership_is_account_page( $page_id, false ) ) {
			?><div class="membership-access-info">
				<p><?php esc_html_e( 'This is the page a user will be redirected to when they want to view their account or make a payment on their account.', 'membership' ) ?></p>
				<p><?php _e( 'It can be left blank to use the standard Membership interface, otherwise it can contain any content you want but <b>should</b> contain the <code>[accountform]</code> shortcode in some location.', 'membership' ) ?></p>
			</div><?php

			return true;
		}

		if ( membership_is_subscription_page( $page_id, false ) ) {
			?><div class="membership-access-info">
				<p><?php esc_html_e( 'This is the page a user will be redirected to when they want to view their subscription details and upgrade / renew them.', 'membership' ) ?></p>
				<p><?php _e( 'It can be left blank to use the standard Membership interface, otherwise it can contain any content you want but <b>should</b> contain the <code>[renewform]</code> shortcode in some location.', 'membership' ) ?></p>
			</div><?php

			return true;
		}

		if ( membership_is_welcome_page( $page_id, false ) ) {
			?><div class="membership-access-info">
				<p><?php esc_html_e( 'When a user has signed up for membership and completed any payments required, they will be redirected to this page.', 'membership' ) ?></p>
				<p><?php _e( 'You should include a welcome message on this page and some details on what to do next.', 'membership' ) ?></p>
			</div><?php

			return true;
		}

		if ( membership_is_protected_page( $page_id, false ) ) {
			?><div class="membership-access-info">
				<p><?php esc_html_e( 'If a post / page / content is not available to a user, this is the page that they user will be directed to.', 'membership' ) ?></p>
				<p><?php _e( 'This page will only be displayed if the user has tried to access the post / page / content directly or via a link.', 'membership' ) ?></p>
			</div><?php

			return true;
		}

		return false;
	}

}