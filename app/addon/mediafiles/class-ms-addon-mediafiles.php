<?php
class MS_Addon_Mediafiles extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'addon_mediafiles';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID ) &&
			MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA );
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
		// This Add-on has no real logic.
		// It is only a switch that is used in the MS_Rule_Category files...
		MS_Model_Addon::toggle_media_htaccess();
		$this->add_filter(
			'ms_model_addon_is_enabled_' . self::ID,
			'is_enabled'
		);
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		// This Add-on is controlled inside the Media Protection Add-on.
		return $list;
	}

	/**
	 * Add a dependency check to this add-on: It can only be enabled when the
	 * parent Add-on "Media" is also enabled.
	 *
	 * Filter: 'ms_model_addon_is_enabled_addon_mediafiles'
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  bool $enabled State of this add-on
	 *         (without considering the parent add-on)
	 * @return bool The actual state of this add-on.
	 */
	public function is_enabled( $enabled ) {
		if ( $enabled ) {
			$enabled = MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA );
		}

		return $enabled;
	}
}
