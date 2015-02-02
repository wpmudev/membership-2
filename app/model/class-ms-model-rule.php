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
 * Membership Rule Management class.
 *
 * @since 1.1.0
 */
class MS_Model_Rule extends MS_Model {

	/**
	 * Access status constants.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	const RULE_VALUE_NO_ACCESS = FALSE;
	const RULE_VALUE_HAS_ACCESS = TRUE;
	const RULE_VALUE_UNDEFINED = NULL;

	/**
	 * Filter type constants.
	 *
	 * @since 1.0.0
	 */
	const FILTER_PROTECTED = 'protected';
	const FILTER_NOT_PROTECTED = 'not_protected';
	const FILTER_DRIPPED = 'dripped';

	/**
	 * Dripped type constants.
	 *
	 * @since 1.0.0
	 * @var string Available Drip-Types.
	 */
	const DRIPPED_TYPE_INSTANTLY = 'instantly';
	const DRIPPED_TYPE_SPEC_DATE = 'specific_date';
	const DRIPPED_TYPE_FROM_REGISTRATION = 'from_registration';

	/**
	 * Collection of meta-information on a rule.
	 * @see register_rule()
	 *
	 * @since 1.1.0
	 * @var array
	 */
	static protected $rule_meta = null;

	/**
	 * Remember if we already called 'prepare_class'
	 *
	 * @since 1.1.0
	 * @var bool
	 */
	static protected $prepared = false;

	/**
	 * Initializes the rules.
	 * By creating the rule-object here we make sure that the rule is
	 * initialized correctly.
	 *
	 * To add a new rule only 2 steps are required:
	 * 1. Create a new file-structure in the app/rule/ directory
	 * 2. Load the rule in this function
	 *
	 * @since  1.1.0
	 */
	static public function prepare_class() {
		MS_Factory::load( 'MS_Rule_Adminside' );
		MS_Factory::load( 'MS_Rule_Category' );
		MS_Factory::load( 'MS_Rule_Content' );
		MS_Factory::load( 'MS_Rule_CptItem' );
		MS_Factory::load( 'MS_Rule_CptGroup' );
		MS_Factory::load( 'MS_Rule_Media' );
		MS_Factory::load( 'MS_Rule_MemberCaps' );
		MS_Factory::load( 'MS_Rule_MemberRoles' );
		MS_Factory::load( 'MS_Rule_MenuItem' );
		MS_Factory::load( 'MS_Rule_Page' );
		MS_Factory::load( 'MS_Rule_Post' );
		MS_Factory::load( 'MS_Rule_ReplaceLocation' );
		MS_Factory::load( 'MS_Rule_ReplaceMenu' );
		MS_Factory::load( 'MS_Rule_Shortcode' );
		MS_Factory::load( 'MS_Rule_Special' );
		MS_Factory::load( 'MS_Rule_Url' );

		self::$prepared = true;
	}

	/**
	 * Makes sure all rules are registered.
	 *
	 * @since  1.1.0
	 */
	static private function prepare() {
		if ( ! self::$prepared ) {
			// This will call prepare_class() above.
			MS_Factory::load( 'MS_Model_Rule' );
		}
	}

	/**
	 * Register meta-information on a rule
	 *
	 * @since  1.1.0
	 * @param  string $id ID of the rule.
	 * @param  string $class Name of the rule class, used to find Model-class.
	 * @param  string $title Rule Title for display.
	 * @param  int $priority Loading-priority (0 - 999), lower is earlier.
	 */
	static public function register_rule( $id, $class, $title, $priority = 0, $dripped = false ) {
		if ( ! is_array( self::$rule_meta ) ) {
			self::$rule_meta = array(
				'title' => array(),
				'class' => array(),
				'model_class' => array(),
				'order' => array(),
				'dripped' => array(),
			);
		}

		self::$rule_meta['title'][ $id ] = $title;
		self::$rule_meta['class'][ $id ] = $class;
		self::$rule_meta['model_class'][ $id ] = $class . '_Model';

		if ( $dripped ) {
			self::$rule_meta['dripped'][] = $id;
		}

		$priority = min( $priority, 999 );
		$priority = max( $priority, 0 );

		$real_priority = $priority * 20;
		while ( isset( self::$rule_meta['order'][ $real_priority ] ) ) {
			$real_priority += 1;
		}
		self::$rule_meta['order'][ $real_priority ] = $id;
	}

	/**
	 * Rule types.
	 *
	 * This array is ordered in the hierarchy way.
	 * First one has more priority than the last one.
	 * This hierarchy is used to determine access to protected content.
	 *
	 * @since 1.0.0
	 * @return array $rule_types {
	 *     @type in $priority The rule type priority in the execution sequence.
	 *     @type string $rule_type The rule type.
	 * }
	 */
	public static function get_rule_types() {
		static $Types = null;

		if ( null === $Types ) {
			self::prepare();

			$settings = MS_Factory::load( 'MS_Model_Settings' );
			$rule_types = self::$rule_meta['order'];

			$rule_types = apply_filters( 'ms_rule_get_rule_types', $rule_types );
			$rule_type = ksort( $rule_types );

			$Types = $rule_types;
		}

		return $Types;
	}

	/**
	 * Rule types and respective classes.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type string $rule_type The rule type constant.
	 *     @type string $class_name The rule type class.
	 * }
	 */
	public static function get_rule_type_classes() {
		static $Rule_Classes = null;

		if ( null === $Rule_Classes ) {
			self::prepare();

			$Rule_Classes = self::$rule_meta['model_class'];

			$Rule_Classes = apply_filters(
				'ms_rule_get_rule_type_classes',
				$Rule_Classes
			);
		}

		return $Rule_Classes;
	}

	/**
	 * Rule types and respective titles.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type string $rule_type The rule type constant.
	 *     @type string $rule_title The rule title.
	 * }
	 */
	public static function get_rule_type_titles() {
		static $Rule_Titles = null;

		if ( null === $Rule_Titles ) {
			self::prepare();

			$Rule_Titles = self::$rule_meta['title'];

			$Rule_Titles = apply_filters(
				'ms_rule_get_rule_type_titles',
				$Rule_Titles
			);
		}

		return $Rule_Titles;
	}

	/**
	 * Dripped Rule types.
	 *
	 * Return only rule types with dripped rules.
	 *
	 * @since 1.0.0
	 * @return string[] $rule_type The rule type constant.
	 */
	public static function get_dripped_rule_types() {
		static $Dripped_Rules = null;

		if ( null === $Dripped_Rules ) {
			self::prepare();

			$Dripped_Rules = self::$rule_meta['dripped'];

			$Dripped_Rules = apply_filters(
				'ms_rule_get_dripped_rule_types',
				$Dripped_Rules
			);
		}

		return $Dripped_Rules;
	}

	/**
	 * Get dripped types.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type string $dripped_type The dripped type constant.
	 *     @type string $dripped_type_desc The dripped type description.
	 * }
	 */
	public static function get_dripped_types() {
		$dripped_types = array(
			self::DRIPPED_TYPE_INSTANTLY => __( 'Instantly', MS_TEXT_DOMAIN ),
			self::DRIPPED_TYPE_SPEC_DATE => __( 'On specific Date', MS_TEXT_DOMAIN ),
			self::DRIPPED_TYPE_FROM_REGISTRATION => __( 'Relative to Subscription', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_rule_get_dripped_types',
			$dripped_types
		);
	}

}