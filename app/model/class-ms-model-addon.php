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
 * Add-on model.
 *
 * Manage add-ons.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Addon extends MS_Model_Option {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar MS_Model_Settings
	 */
	public static $instance;

	/**
	 * Add-on name constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADDON_MULTI_MEMBERSHIPS = 'multi_memberships';
	const ADDON_POST_BY_POST = 'post_by_post';
	const ADDON_URL_GROUPS = 'url_groups';
	const ADDON_CPT_POST_BY_POST = 'cpt_post_by_post';
	const ADDON_COUPON = 'coupon';
	const ADDON_TRIAL = 'trial';
	const ADDON_MEDIA = 'media';
	const ADDON_PRO_RATE = 'pro_rate';
	const ADDON_SHORTCODE = 'shortcode';
	const ADDON_AUTO_MSGS_PLUS = 'auto_msgs_plus';
	const ADDON_SPECIAL_PAGES = 'special_pages';
	const ADDON_ADV_MENUS = 'adv_menus';
	// New since 1.1
	const ADDON_ADMINSIDE = 'adminside';
	const ADDON_MEMBERCAPS = 'membercaps';
	const ADDON_MEMBERCAPS_ADV = 'membercaps_advanced';
	const ADDON_MEMBERCAPS_ROLES = 'membercaps_roles';

	/**
	 * Add-ons array.
	 *
	 * @since 1.0.0
	 *
	 * @var array {
	 *     @type string $addon_type The add-on type.
	 *     @type boolean $enabled The add-on enbled status.
	 * }
	 */
	protected $addons = array();

	/**
	 * Get addon types.
	 *
	 * @since 1.0.0
	 *
	 * @var string[] The add-on types array.
	 */
	public static function get_addon_types() {
		static $types;

		if ( empty( $types ) ) {
			$types = array(
				self::ADDON_MULTI_MEMBERSHIPS,
				self::ADDON_TRIAL,
				self::ADDON_COUPON,
				self::ADDON_POST_BY_POST,
				self::ADDON_CPT_POST_BY_POST,
				self::ADDON_MEDIA,
				self::ADDON_SHORTCODE,
				self::ADDON_URL_GROUPS,
				self::ADDON_AUTO_MSGS_PLUS,
				self::ADDON_SPECIAL_PAGES,
				self::ADDON_ADV_MENUS,
				self::ADDON_ADMINSIDE,
				self::ADDON_MEMBERCAPS,
				self::ADDON_MEMBERCAPS_ADV,
				self::ADDON_MEMBERCAPS_ROLES,
			);
		}

		return apply_filters( 'ms_model_addon_get_addon_types', $types );
	}

	/**
	 * Get addon parents.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $addon The add-on to check
	 * @return false|string The parent add-on of the specified add-on or false.
	 */
	public static function get_parent( $addon ) {
		static $Parents;
		$res = false;

		if ( empty( $Parents ) ) {
			$Parents = array(
				self::ADDON_MEMBERCAPS_ADV => self::ADDON_MEMBERCAPS,
				self::ADDON_MEMBERCAPS_ROLES => self::ADDON_MEMBERCAPS,
			);

			$Parents = apply_filters(
				'ms_model_addon_get_parent_list',
				$Parents
			);
		}

		if ( isset( $Parents[$addon] ) ) {
			$res = $Parents[$addon];
		}

		return $res;
	}

	/**
	 * Verify if an add-on is enabled
	 *
	 * @since 1.0.0
	 *
	 * @var string $addon The add-on type.
	 * @return boolean True if enabled.
	 */
	public static function is_enabled( $addon ) {
		$model = MS_Factory::load( 'MS_Model_Addon' );
		$enabled = false;

		if ( in_array( $addon, self::get_addon_types() ) ) {
			$enabled = ! empty( $model->addons[ $addon ] );

			$parent = self::get_parent( $addon );
			if ( $enabled && $parent ) {
				$enabled = self::is_enabled( $parent );
			}
		}

		return apply_filters(
			'ms_model_addon_is_enabled_' . $addon,
			$enabled
		);
	}

	/**
	 * Enable an add-on type in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string $addon The add-on type.
	 */
	public function enable( $addon ) {
		if ( in_array( $addon, self::get_addon_types() ) ) {
			$this->addons[ $addon ] = true;
		}

		do_action( 'ms_model_addon_enable', $addon, $this );
	}

	/**
	 * Disable an add-on type in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string $addon The add-on type.
	 */
	public function disable( $addon ) {
		if ( in_array( $addon, self::get_addon_types() ) ) {
			$this->addons[ $addon ] = false;
		}

		do_action( 'ms_model_addon_disable', $addon, $this );
	}

	/**
	 * Toggle add-on type status in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string $addon The add-on type.
	 */
	public function toggle_activation( $addon ) {
		if ( in_array( $addon, self::get_addon_types() ) ) {
			$this->addons[ $addon ] = empty( $this->addons[ $addon ] );
		}

		do_action( 'ms_model_addon_toggle_activation', $addon, $this );
	}

	/**
	 * Enable add-on necessary to membership.
	 *
	 * @since 1.0.0
	 *
	 * @var string $addon The add-on type.
	 */
	public function auto_config( $membership ) {
		if ( MS_Model_Membership::TYPE_CONTENT_TYPE == $membership->type ) {
			$this->enable( self::ADDON_MULTI_MEMBERSHIPS );
		}
		if ( $membership->trial_period_enabled ) {
			$this->enable( self::ADDON_TRIAL );
		}

		do_action( 'ms_model_addon_auto_config', $membership, $this );
	}

	/**
	 * Enable add-on every time a membership setup is completed.
	 *
	 * @since 1.0.0
	 *
	 * @var string $addon The add-on type.
	 */
	public function get_addon_list() {
		$list = array();

		$settings = MS_Factory::load( 'MS_Model_Settings' );

		$options_text = sprintf(
			'<i class="dashicons dashicons dashicons-admin-settings"></i> %s',
			__( 'Options available', MS_TEXT_DOMAIN )
		);

		$list[self::ADDON_MULTI_MEMBERSHIPS] = (object) array(
			'name' => __( 'Multiple Memberships', MS_TEXT_DOMAIN ),
			'description' => __( 'Your members can join more than one membership level at the same time.', MS_TEXT_DOMAIN ),
		);

		$list[self::ADDON_TRIAL] = (object) array(
			'name' => __( 'Trial Period', MS_TEXT_DOMAIN ),
			'description' => __( 'Allow your members to sign up for a free membership trial. Trial details can be configured separately for each membership level.', MS_TEXT_DOMAIN ),
		);

		$list[self::ADDON_COUPON] = (object) array(
			'name' => __( 'Coupon', MS_TEXT_DOMAIN ),
			'description' => __( 'Enable discount coupons.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-ticket',
		);

		$list[self::ADDON_POST_BY_POST] = (object) array(
			'name' => __( 'Post by Post Protection', MS_TEXT_DOMAIN ),
			'description' => __( 'Protect content post by post instead of post categories.', MS_TEXT_DOMAIN ),
		);

		$list[self::ADDON_CPT_POST_BY_POST] = (object) array(
			'name' => __( 'Custom Post Type Protection - Post by Post ', MS_TEXT_DOMAIN ),
			'description' => __( 'Protect custom post type post by post instead of post type groups.', MS_TEXT_DOMAIN ),
		);

		$list[self::ADDON_MEDIA] = (object) array(
			'name' => __( 'Media Protection', MS_TEXT_DOMAIN ),
			'description' => __( 'Protect Images and other Media-Library content.', MS_TEXT_DOMAIN ),
			'footer' => $options_text,
			'icon' => 'dashicons dashicons-admin-media',
			'class' => 'ms-options',
			'details' => array(
				array(
					'id' => 'masked_url',
					'before' => esc_html( trailingslashit( get_option( 'home' ) ) ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Mask download URL:', MS_TEXT_DOMAIN ),
					'value' => $settings->downloads['masked_url'],
					'data_ms' => array(
						'field' => 'masked_url',
						'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' => wp_create_nonce( MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING ),
					),
				),
				array(
					'id' => 'protection_type',
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
					'title' => __( 'Protection method', MS_TEXT_DOMAIN ),
					'value' => $settings->downloads['protection_type'],
					'field_options' => MS_Model_Rule_Media::get_protection_types(),
					'data_ms' => array(
						'field' => 'protection_type',
						'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'_wpnonce' => wp_create_nonce( MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING ),
					),
				),
			),
		);

		$list[self::ADDON_SHORTCODE] = (object) array(
			'name' => __( 'Shortcode Protection', MS_TEXT_DOMAIN ),
			'description' => __( 'Protect shortcodes-output via membership levels.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-editor-code',
		);

		$list[self::ADDON_URL_GROUPS] = (object) array(
			'name' => __( 'URL Protection', MS_TEXT_DOMAIN ),
			'description' => __( 'URL Protection will protect pages by the URL. This rule overrides all other rules, so use it carefully.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-admin-links',
		);

		$list[self::ADDON_AUTO_MSGS_PLUS] = (object) array(
			'name' => __( 'Additional Automated Messages', MS_TEXT_DOMAIN ),
			'description' => __( 'Send your members automated Email responses for various additional events.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-email',
		);

		$list[self::ADDON_SPECIAL_PAGES] = (object) array(
			'name' => __( 'Protect Special Pages', MS_TEXT_DOMAIN ),
			'description' => __( 'Change protection of special pages such as the search results.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-admin-home',
		);

		$list[self::ADDON_ADV_MENUS] = (object) array(
			'name' => __( 'Advanced menu protection', MS_TEXT_DOMAIN ),
			'description' => __( 'Adds a new option to the General Settings that controls how WordPress menus are protected.<br />Protect individual Menu-Items, replace the contents of WordPress Menu-Locations or replace each Menu individually.', MS_TEXT_DOMAIN ),
			'footer' => $options_text,
			'class' => 'ms-options',
			'details' => array(
				array(
					'id' => 'menu_protection',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Choose how you want to protect your WordPress menus.', MS_TEXT_DOMAIN ),
					'value' => $settings->menu_protection,
					'field_options' => array(
						'item' => __( 'Protect single Menu Items', MS_TEXT_DOMAIN ),
						'menu' => __( 'Replace individual Menus', MS_TEXT_DOMAIN ),
						'location' => __( 'Overwrite contents of Menu Locations', MS_TEXT_DOMAIN ),
					),
					'data_ms' => array(
						'action' => MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING,
						'field' => 'menu_protection',
					),
				),
			),
		);

		// New since 1.1
		$list[self::ADDON_ADMINSIDE] = (object) array(
			'name' => __( 'Admin Side Protection', MS_TEXT_DOMAIN ),
			'description' => __( 'Control the pages and even Meta boxes that members can access on the admin side.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-admin-network',
		);

		$list[self::ADDON_MEMBERCAPS] = (object) array(
			'name' => __( 'Member Capabilities', MS_TEXT_DOMAIN ),
			'description' => __( 'Manage user-capabilities on membership level.', MS_TEXT_DOMAIN ),
			'footer' => $options_text,
			'class' => 'ms-options',
			'icon' => 'dashicons dashicons-admin-users',
			'details' => array(
				array(
					'id' => 'ms-toggle-' . self::ADDON_MEMBERCAPS_ADV,
					'title' => __( 'Advanced Capability protection', MS_TEXT_DOMAIN ),
					'desc' => __( 'Allows you to protect individual WordPress Capabilities. When activated then the "User Roles" tab is replaced by a "Member Capabilities" tab where you can protect and assign individual WordPress Capabilities instead of roles.', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'value' => self::is_enabled( self::ADDON_MEMBERCAPS_ADV ),
					'class' => 'toggle-plugin',
					'ajax_data' => array(
						'action' => MS_Controller_Addon::AJAX_ACTION_TOGGLE_ADDON,
						'field' => 'active',
						'addon' => self::ADDON_MEMBERCAPS_ADV,
					),
				),
			),
		);

		$list[self::ADDON_MEMBERCAPS_ROLES] = (object) array(
			'name' => __( 'User-Role Memberships', MS_TEXT_DOMAIN ),
			'description' => __( 'Protect content based on a users role / for guests.', MS_TEXT_DOMAIN ),
			'icon' => 'dashicons dashicons-admin-users',
		);

		$list = apply_filters( 'ms_model_addon_get_addon_list', $list );

		foreach ( $list as $key => $data ) {
			$list[$key]->id = $key;
			$list[$key]->active = self::is_enabled( $key );
			$list[$key]->title = $data->name;

			if ( isset( $list[$key]->icon ) ) {
				$list[$key]->icon = '<i class="' . $list[$key]->icon . '"></i>';
			} else {
				$list[$key]->icon = '<i class="wpmui-fa wpmui-fa-puzzle-piece"></i>';
			}

			$list[$key]->action = array();
			$list[$key]->action[] = array(
				'id' => 'ms-toggle-' . $key,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => self::is_enabled( $key ),
				'class' => 'toggle-plugin',
				'ajax_data' => array(
					'action' => MS_Controller_Addon::AJAX_ACTION_TOGGLE_ADDON,
					'field' => 'active',
					'addon' => $key,
				),
			);
			$list[$key]->action[] = MS_Helper_Html::save_text( null, false, true );
		}

		return $list;
	}

}