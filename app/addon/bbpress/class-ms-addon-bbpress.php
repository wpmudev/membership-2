<?php
class MS_Addon_Bbpress extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'bbpress';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		if ( ! self::bbpress_active()
			&& MS_Model_Addon::is_enabled( self::ID )
		) {
			$model = MS_Factory::load( 'MS_Model_Addon' );
			$model->disable( self::ID );
		}

		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	public function init() {

		if ( self::is_active() ) {
			// Always remove bbpress from MS_Rule_CptGroup_Model.
			$this->add_filter(
				'ms_rule_cptgroup_model_get_excluded_content',
				'exclude_bbpress_cpts'
			);

			$this->add_filter(
				'ms_controller_protection_tabs',
				'rule_tabs'
			);

			MS_Factory::load( 'MS_Addon_Bbpress_Rule' );
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'bbPress Integration', 'membership2' ),
			'description' 	=> __( 'Enable bbPress rules integration.', 'membership2' ),
			'icon' 			=> 'dashicons dashicons-format-chat',
		);

		if ( ! self::bbpress_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate bbPress to use this Add-on', 'membership2' )
			);
			$list[ self::ID ]->action = '-';
		}

		return $list;
	}

	/**
	 * Returns true, when the BuddyPress plugin is activated.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function bbpress_active() {
		return class_exists( 'bbPress' );
	}

	/**
	 * Add bbpress rule tabs in membership level edit.
	 *
	 * @since  1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function rule_tabs( $tabs ) {
		$rule 			= MS_Addon_Bbpress_Rule::RULE_ID;
		$tabs[ $rule  ] = true;

		return $tabs;
	}

	/**
	 * Exclude BBPress custom post type from MS_Rule_CptGroup_Model.
	 *
	 * @since  1.0.0
	 *
	 * @filter ms_rule_cptgroup_model_get_excluded_content
	 *
	 * @param array $excluded The current excluded ctps.
	 * @return array The filtered excluded ctps.
	 */
	public function exclude_bbpress_cpts( $excluded ) {
		$excluded = array_merge(
			$excluded,
			MS_Addon_Bbpress_Rule_Model::get_bb_cpt()
		);

		return $excluded;
	}
}
