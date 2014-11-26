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
 * Plugin Page model.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Page extends MS_Model_Custom_Post_Type {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * @var string $POST_TYPE
	 */
	public static $POST_TYPE = 'ms_page';
	public $post_type = 'ms_page';

	/**
	 * MS Page Type.
	 *
	 * @see MS_Model_Pages constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * MS Page slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * The full URL to this MS Page
	 *
	 * @since 1.0.4.4
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Create WP page.
	 *
	 * @since 1.0.0
	 */
	public function create_wp_page() {
		$this->name = $this->title;
		$this->description = $this->get_ms_page_default_content();
		$this->save();

		$this->set_page_status( 'publish' );

		do_action( 'ms_model_page_create_wp_page', $this );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0 register_post_type
	 */
	public static function get_register_post_type_args() {
		return apply_filters(
			'ms_model_page_register_post_type_args',
			array(
				'public' => false,
				'publicly_queriable' => true,
				'show_ui' => false,
				'show_in_menu' => false,
				'exclude_from_search' => true,
				'show_in_nav_menus' => true, // Add pages in Front-end menus
				'rewrite' => array( 'slug' => 'member', 'with_front' => false ),
				'has_archive' => false,
				'supports' => array( 'title', 'editor', 'revisions' ),
			)
		);
	}

	/**
	 * Get default content for plugin pages.
	 *
	 * @since 1.0.0
	 *
	 * @return string The default content.
	 */
	public function get_ms_page_default_content() {
		$content = null;

		switch ( $this->type ) {
			case MS_Model_Pages::MS_PAGE_MEMBERSHIPS:
				$content = sprintf(
					'[ms-green-note] %1$s [/ms-green-note]',
					__( 'We have the following subscriptions available for our site. You can renew, cancel or upgrade your subscriptions by using the forms below.', MS_TEXT_DOMAIN )
				);
				$content .= '['. MS_Helper_Shortcode::SCODE_SIGNUP .']';
				break;

			case MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT:
				//The text in Settings > "Protection Messages" is added in front end controller
				break;

			case MS_Model_Pages::MS_PAGE_ACCOUNT:
				$content = '['. MS_Helper_Shortcode::SCODE_MS_ACCOUNT .']<hr />';
				$content .= '['. MS_Helper_Shortcode::SCODE_LOGOUT .']';
				break;

			case MS_Model_Pages::MS_PAGE_REGISTER:
				$content = sprintf(
					'[ms-green-note] %1$s [/ms-green-note]',
					__( 'We have the following subscriptions available for our site. To join, simply click on the Sign Up button and then complete the registration details.', MS_TEXT_DOMAIN )
				);
				$content .= '['. MS_Helper_Shortcode::SCODE_SIGNUP .']';
				break;

			case MS_Model_Pages::MS_PAGE_REG_COMPLETE:
				$content .= sprintf(
					'[ms-green-note] %1$s <br/> %2$s [/ms-green-note]',
					__( 'Your request to join the membership was successfully received!', MS_TEXT_DOMAIN ),
					__( 'The Payment Gateway could take a couple of minutes to process and return the payment status.', MS_TEXT_DOMAIN )
				);
				$content .= sprintf(
					'<a href="%s">%s</a>',
					MS_Factory::load( 'MS_Model_Pages' )->get_ms_page_url( MS_Model_Pages::MS_PAGE_ACCOUNT, false, true ),
					__( 'Visit your account page for more information.', MS_TEXT_DOMAIN )
				);
				break;
		}

		return apply_filters(
			'ms_model_page_get_ms_page_default_content',
			$content,
			$this
		);
	}

	/**
	 * Set WP page status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status The WP status to set.
	 */
	public function set_page_status( $status ) {
		if ( ! empty( $this->id ) ) {
			$page = array();
			$page['ID'] = $this->id;
			$page['post_status'] = $status;
			wp_update_post( $page );

			$this->status = $status;
		}

		do_action( 'ms_model_page_set_page_status', $status, $this );
	}

	/**
	 * Set specific property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'id':
					$this->id = $this->validate_min( $value, 0 );
					break;

				case 'slug':
					$value = untrailingslashit( $value );
					$this->slug = sanitize_title( $value );

					$this->url = home_url( $this->slug . '/' );
					break;

				default:
					$this->$property = $value;
					break;
			}
		}

		do_action( 'ms_model_page__set_after', $property, $value, $this );
	}

	/**
	 * Get specific properties.
	 *
	 * @since 1.0.4.4
	 *
	 * @param string $property The name of a property to associate.
	 * @return mixed $value The value of a property.
	 */
	public function __get( $property ) {
		$value = null;

		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'url':
					if ( empty( $this->url ) ) {
						$this->url = home_url( $this->slug . '/' );
					}
					$value = $this->url;
					break;

				default:
					$value = $this->$property;
					break;
			}
		}

		return apply_filters( 'ms_model_page__get', $value, $property, $this );
	}
}