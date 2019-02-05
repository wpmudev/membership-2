<?php

/**
 * Controller for compatibility with other plugins/themes.
 *
 * All compatibility related functions are handled here.
 *
 * @since      1.1.6
 *
 * @package    Membership2
 * @subpackage Controller
 *
 * @author     Joel James
 */
class MS_Controller_Compatibility extends MS_Controller {

	/**
	 * Register hook for the compatibility.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		// Initialize parent.
		parent::__construct();

		// Flush cache when a membership is assigned to a member.
		$this->add_filter( 'ms_model_member_add_membership', 'refresh_membership_cache' );
		// Flush cache when a membership is dropped from a member.
		$this->add_filter( 'ms_model_membership_drop_membership', 'refresh_membership_cache' );
		// Flush cache when a membership is cancelled from a member.
		$this->add_filter( 'ms_model_membership_cancel_membership', 'refresh_membership_cache' );
		// Flush cache when a membership is moved.
		$this->add_filter( 'ms_model_membership_move_membership', 'refresh_membership_cache' );
	}

	/**
	 * Refresh cache on membership create/cancel/drop/move.
	 *
	 * In few hosting environments where Object cache is persistent
	 * we need to make sure updates are being reflected in real time.
	 * Otherwise new members may not be able to access member's only
	 * pages and features.
	 *
	 * @since 1.1.6
	 *
	 * @return mixed $data
	 */
	public function refresh_membership_cache( $data ) {
		// Flush caches.
		MS_Helper_Cache::refresh_cache();

		return $data;
	}

}