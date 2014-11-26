<?php
/**
 * This file defines the MS_Controller_Billing class.
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
 * Controller to manage Plugin Pages.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Page extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_UPDATE_PAGE = 'update_page';

	/**
	 * Prepare the Page manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->add_action(
			'wp_ajax_' . self::AJAX_ACTION_UPDATE_PAGE,
			'ajax_action_update_page'
		);

		$this->add_action( 'pre_get_posts', 'ms_page_router', 1 );
	}

	/**
	 * Redirect to configured ms_page.
	 *
	 * ** Hooks Actions: **
	 * * pre_get_posts
	 *
	 * @since 1.0.0
	 */
	public function ms_page_router( $wp_query ) {
		if ( ! empty( $wp_query->query_vars['ms_page'] ) ) {
			$slug = $wp_query->query_vars['ms_page'];
			$ms_pages = $this->get_ms_pages_model();
			$ms_page = $ms_pages->get_ms_page_by( 'slug', $slug );

			$wp_query->query_vars['post_type'] = 'page';
			$wp_query->query_vars['page_id'] = $ms_page->id;
		}
	}

	/**
	 * Get ms_pages model.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Pages The ms pages model.
	 */
	public function get_ms_pages_model() {
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );

		return apply_filters(
			'ms_controller_pages_get_ms_pages_model',
			$ms_pages,
			$this
		);
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related Action hooks:
	 * - wp_ajax_update_page
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_page() {
		$msg = 0;
		$this->_resp_reset();

		$required = array( 'page_type', 'field', 'value' );

		if ( $this->_resp_ok() && ! $this->verify_nonce() ) { $this->_resp_err( 'update-page-01' ); }
		if ( $this->_resp_ok() && ! $this->validate_required( $required ) ) { $this->_resp_err( 'update-page-02' ); }
		if ( $this->_resp_ok() && ! $this->is_admin_user() ) { $this->_resp_err( 'update-page-03' ); }

		if ( $this->_resp_ok() ) {

			$page_type = $_POST['page_type'];
			$field = $_POST['field'];
			$value = $_POST['value'];

			$ms_pages = $this->get_ms_pages_model();

			$ms_page = $ms_pages->get_ms_page( $page_type );
			$ms_page->$field = $value;

			$ms_pages->set_ms_page( $page_type, $ms_page );
			$ms_pages->save();

			// If slug is changed, need to flush rewrite rules.
			if ( 'slug' === $field ) {
				flush_rewrite_rules();
			}
		}
		$msg .= $this->_resp_code();

		echo $msg;
		exit;
	}
}