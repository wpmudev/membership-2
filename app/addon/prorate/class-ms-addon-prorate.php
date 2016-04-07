<?php
/**
 * Add-on: Enable the Pro-Rating function.
 *
 * @since  1.0.1.0
 */
class MS_Addon_Prorate extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'addon_prorate';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	static public function is_active() {
		return false;
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
	 * @since  1.0.1.0
	 */
	public function init() {
		MS_Model_Addon::disable( self::ID );
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.0.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Pro-Rating', 'membership2' ),
			'description' => __( 'Pro-Rate previous payments when switching memberships.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-money',
			'details' => array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => __( 'Pro-Rating is applied when a user upgrades/downgrades a membership. Not when he cancels and subscribes in two steps.<br><br>Reason:<br>When a user cancels a membership he keeps access to the membership until the current period expires (exception: permanent access expires instantly)', 'membership2' ),
				),
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => '<b>' . __( 'When Multiple Memberships Add-on is disabled', 'membership2' ) . '</b>',
					'value' => __( 'Changing a membership always expires the old memberships and adds a subscription for the the new membership <em>in one step</em>. Pro Rating is always applied here.', 'membership2' ),
				),
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => '<b>' . __( 'When Multiple Memberships Add-on is enabled', 'membership2' ) . '</b>',
					'value' => __( 'Only when you manually set the "Cancel and Pro-Rate" setting in the Upgrade Paths settings of the membership then the change is recognized as upgrade/downgrade. In this case the old membership is deactivated when the new subscription is created.<br>If you do not set this option the default logic applies: The user can access the old membership for the duration he paid, even when he cancels earlier. So no Pro-Rating then.', 'membership2' ),
				),
			),
			'action' => array( __( 'Pro Version', 'membership2' ) ),
		);
		return $list;
	}

}
