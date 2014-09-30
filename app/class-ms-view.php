<?php
/**
 * This file defines the MS_View object.
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
 * Abstract class for all Views.
 *
 * All views will extend or inherit from the MS_View class.
 * Methods of this class will prepare and output views.
 *
 * @since 4.0.0
 * @package Membership
 */
class MS_View extends MS_Hooker {

	/**
	 * The storage of all data associated with this render.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @var array
	 */
	protected $data;

	/**
	 * Determines whether we need to cache output or not.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $cache = false;

	/**
	 * Time to live for cached HTML.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @var int
	 */
	protected $cache_ttl;

	/**
	 * Determines whether we need to use network wide cache or not.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $use_network_cache = false;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $data The data what has to be associated with this render.
	 */
	public function __construct( $data = array() ) {
		$this->data = $data;
		$this->cache_ttl = 10 * MINUTE_IN_SECONDS;

		/**
		 * Actions to execute when constructing the parent View.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_View object.
		 */
		do_action( 'membership_parent_view_construct', $this );
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $name The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
//	public function __get( $name ) {
//		return array_key_exists( $name, $this->data ) ? $this->data[ $name ] : null;
//	}

//	/**
//	 * Checks whether the render has specific property or not.
//	 *
//	 * @since 3.5
//	 *
//	 * @access public
//	 * @param string $name
//	 * @return boolean TRUE if the property exists, otherwise FALSE.
//	 */
//	public function __isset( $name ) {
//		return array_key_exists( $name, $this->data );
//	}

//	/**
//	 * Associates the render with specific property.
//	 *
//	 * @since 3.5
//	 *
//	 * @access public
//	 * @param string $name The name of a property to associate.
//	 * @param mixed $value The value of a property.
//	 */
//	public function __set( $name, $value ) {
//		$this->data[ $name ] = $value;
//	}

//	/**
//	 * Unassociates specific property from the render.
//	 *
//	 * @since 3.5
//	 *
//	 * @access public
//	 * @param string $name The name of the property to unassociate.
//	 */
//	public function __unset( $name ) {
//		unset( $this->data[ $name ] );
//	}

	/**
	 * Sets flags to cache or not output.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param boolean $cache New cache output directive.
	 * @return boolean Previous value of cache output directive.
	 */
	public function cache_output( $cache = null ) {
		$old = $this->cache;
		if ( func_num_args() > 0 ) {
			$this->cache = (bool)$cache;
		}

		return $old;
	}

	/**
	 * Sets cache time to live value.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param int $new_ttl New time to live value.
	 * @return int Old time to live value.
	 */
	public function cache_ttl( $new_ttl = null ) {
		$old = $this->cache_ttl;
		if ( func_num_args() > 0 ) {
			$this->cache_ttl = absint( $new_ttl );
		}

		return $old;
	}

	/**
	 * Returns cache key.
	 *
	 * @sicne 4.0.0
	 *
	 * @access protected
	 * @return string Cache key.
	 */
	protected function get_cache_key() {
		return __CLASS__;
	}

	/**
	 * Returns HTML from cache.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @return string|boolean HTML on success, otherwise FALSE.
	 */
	public function get_html_from_cache() {
		return $this->use_network_cache
			? get_site_transient( $this->get_cache_key() )
			: get_transient( $this->get_cache_key() );
	}

	/**
	 * Caches generated HTML.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param string $html HTML to cache.
	 */
	protected function cache_html( $html ) {
		if ( $this->use_network_cache ) {
			set_site_transient( $this->get_cache_key(), $html, $this->cache_ttl );
		} else {
			set_transient( $this->get_cache_key(), $html, $this->cache_ttl );
		}
	}

	/**
	 * Builds template and return it as string.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @return string
	 */
	protected function to_html() {
		// This function is implemented different in each child class.
		return '';
	}

	/**
	 * Returns built template as string.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @return string
	 */
	public function toString() {
		return $this->to_html();
	}

	/**
	 * Renders the template.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function render() {
		// render template
		$html = $this->to_html();

		// cache template if need be
		if ( $this->cache ) {
			$this->cache_html( $html );
		}

		echo $html;
	}
}