<?php
/**
 * Protection API hook
 *
 * Manages all Protection API actions
 *
 * @since  1.0.4
 *
 * @package Membership2
 * @subpackage Api
 */
class MS_Api_Post_Protection {

    /**
	 * Singletone instance of the plugin.
	 *
	 * @since  1.0.4
	 *
	 * @var MS_Plugin
	 */
	private static $instance = null;


    /**
	 * Returns singleton instance of the plugin.
	 *
	 * @since  1.0.4
	 *
	 * @static
	 * @access public
	 *
	 * @return MS_Api_Post_Protection
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new MS_Api_Post_Protection();
		}

		return self::$instance;
	}
    
}
?>