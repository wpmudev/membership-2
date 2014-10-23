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
class MS_Model_Page extends MS_Model {

	/**
	 * ID of the model object.
	 *
	 * @since 1.0.0
	 * 
	 * @var int 
	 */
	protected $id;
	
	/**
	 * Title of the model object.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	protected $title;
	
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
	 * Create WP page.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $virtual Optional. Default true. Create with virtual status.
	 */
	public function create_wp_page( $virtual = true ) {
		
		$page_details = apply_filters(
				'ms_model_settings_create_' . $this->type,
				array(
						'post_title' => $this->title,
						'post_name' => $this->title,
						'post_status' => ( $virtual ) ? 'virtual' : 'publish',
						'post_type' => 'page',
						'ping_status' => 'closed',
						'comment_status' => 'closed',
						'post_content' => $this->get_ms_page_default_content(),
				)
		);
		$id = wp_insert_post( $page_details );
		$this->id = $id;
		
		do_action( 'ms_model_page_create_wp_page', $virtual, $this );
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

		switch( $this->type ) {
			case MS_Model_Pages::MS_PAGE_MEMBERSHIPS:
				$content = sprintf( '[ms-green-note] %1$s [/ms-green-note]', 
						__( 'We have the following subscriptions available for our site. You can renew, cancel or upgrade your subscriptions by using the forms below.', MS_TEXT_DOMAIN ) 
				);
				$content .= '['. MS_Helper_Shortcode::SCODE_SIGNUP .']';
				break;
			case MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT:
				//The text in Settings > "Protection Messages" is added in front end controller
				break;
			case MS_Model_Pages::MS_PAGE_ACCOUNT:
				$content = '['. MS_Helper_Shortcode::SCODE_MS_ACCOUNT .']';
				break;
			case MS_Model_Pages::MS_PAGE_REGISTER:
				$content = sprintf( '[ms-green-note] %1$s [/ms-green-note]', 
						__( 'We have the following subscriptions available for our site. To join, simply click on the Sign Up button and then complete the registration details.', MS_TEXT_DOMAIN ) 
				);
				$content .= '['. MS_Helper_Shortcode::SCODE_SIGNUP .']';
				break;
			case MS_Model_Pages::MS_PAGE_REG_COMPLETE:
				$content .= sprintf( '[ms-green-note] %1$s <br/> %2$s [/ms-green-note]',
					__( 'Your request to join the membership was successfully received!', MS_TEXT_DOMAIN ),
					__( 'The Payment Gateway could take a couple of minutes to process and return the payment status.', MS_TEXT_DOMAIN )
				);
				$content .= sprintf( '<a href="%s">%s</a>',
					MS_Factory::load( 'MS_Model_Pages' )->get_ms_page_url( MS_Model_Pages::MS_PAGE_ACCOUNT, false, true ),
					__( 'Visit your account page for more information.', MS_TEXT_DOMAIN )
				);
				break;
		}
		
		return apply_filters( 'ms_model_page_get_ms_page_default_content', $content, $this );
	}
	
	/**
	 * Set WP page status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status The WP status to set.
	 */
	public function set_page_status( $status ) {
		
		if( ! empty( $this->id ) ) {
			$page = array();
			$page['ID'] = $this->id;
			$page['post_status'] = $status;
			wp_update_post( $page );
		}
		
		do_action( 'ms_model_page_set_page_status', $status, $this );
	}
	
	/**
	 * Get WP page object.
	 *
	 * @since 1.0.0
	 *
	 * @return null|WP_Post The WP page.
	 */
	public function get_page() {
		
		$page = null;
		
		if ( ! empty( $this->id ) ) {
			$page = get_post( $this->id );
			if ( empty( $page->ID ) || 'trash' == $page->post_status ) {
				$page = null;
			}
		}
		
		return apply_filters( 'ms_model_page_get_page', $page, $this );
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
					$this->$property = $this->validate_min( $value, 0 );
					break;
				case 'slug':
					$this->$property = sanitize_title( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
		
		do_action( 'ms_model_page__set_after', $property, $value, $this );
	}
}