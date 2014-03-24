<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * This module allows for customizing navigation menus.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_Menu extends Membership_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin class.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_add_filter( 'wp_nav_menu_args', 'remove_register_menu' );
	}

	/**
	 * Removes registration menu item from Members with subscriptions.
	 *
	 * @since 3.5
	 * @action widgets_init
	 *
	 * @access public
	 */
	function remove_register_menu( $original, $args ) {
	     $original['walker'] = new Membership_Menu_Walker();
	     return $original;
	}

}



/**
 * The class responsible for the menu walker.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Menu_Walker extends Walker_Nav_Menu {
	/**
	* Start the element output.
	*
	* @see Walker::start_el()
	*
	* @since 3.0.0
	*
	* @param string $output Passed by reference. Used to append additional content.
	* @param object $item   Menu item data object.
	* @param int    $depth  Depth of menu item. Used for padding.
	* @param array  $args   An array of arguments. @see wp_nav_menu()
	* @param int    $id     Current item ID.
	*/
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $M_options;
		$menu_item_output = '';

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		/**
		* Filter the CSS class(es) applied to a menu item's <li>.
		*
		* @since 3.0.0
		*
		* @param array  $classes The CSS classes that are applied to the menu item's <li>.
		* @param object $item    The current menu item.
		* @param array  $args    An array of arguments. @see wp_nav_menu()
		*/
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		/**
		* Filter the ID applied to a menu item's <li>.
		*
		* @since 3.0.1
		*
		* @param string The ID that is applied to the menu item's <li>.
		* @param object $item The current menu item.
		* @param array $args An array of arguments. @see wp_nav_menu()
		*/
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$menu_item_output .= $indent . '<li' . $id . $value . $class_names .'>';

		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';

		/**
		* Filter the HTML attributes applied to a menu item's <a>.
		*
		* @since 3.6.0
		*
		* @param array $atts {
		*     The HTML attributes applied to the menu item's <a>, empty strings are ignored.
		*
		*     @type string $title  The title attribute.
		*     @type string $target The target attribute.
		*     @type string $rel    The rel attribute.
		*     @type string $href   The href attribute.
		* }
		* @param object $item The current menu item.
		* @param array  $args An array of arguments. @see wp_nav_menu()
		*/
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		/** This filter is documented in wp-includes/post-template.php */
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		/**
		* Filter a menu item's starting output.
		*
		* The menu item's starting output only includes $args->before, the opening <a>,
		* the menu item's title, the closing </a>, and $args->after. Currently, there is
		* no filter for modifying the opening and closing <li> for a menu item.
		*
		* @since 3.0.0
		*
		* @param string $item_output The menu item's starting HTML output.
		* @param object $item        Menu item data object.
		* @param int    $depth       Depth of menu item. Used for padding.
		* @param array  $args        An array of arguments. @see wp_nav_menu()
		*/
		$menu_item_output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$member = Membership_Plugin::factory()->get_member( $current_user->ID );
			if ( $member->has_subscription() )
			{
				if ( $item->object_id != $M_options['registration_page'] ) {
					$output .= $menu_item_output;
				}
			} else {
				$output .= $menu_item_output;
			}
		}
	}

	/**
	* Ends the element output, if needed.
	*
	* @see Walker::end_el()
	*
	* @since 3.0.0
	*
	* @param string $output Passed by reference. Used to append additional content.
	* @param object $item   Page data object. Not used.
	* @param int    $depth  Depth of page. Not Used.
	* @param array  $args   An array of arguments. @see wp_nav_menu()
	*/
	function end_el( &$output, $item, $depth = 0, $args = array() ) {
		if ( $item->object_id != $M_options['registration_page'] ) {	
			$output .= "</li>\n";
		}
	}

}