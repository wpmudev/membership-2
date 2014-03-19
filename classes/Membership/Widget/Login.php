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
 * Login widget.
 *
 * @category Membership
 * @package Widget
 */
class Membership_Widget_Login extends WP_Widget {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct( 'membershiploginwidget', __( 'Membership Login Widget', 'membership' ), array(
			'classname'   => 'membershiploginwidget',
			'description' => __( 'Membership Login Widget', 'membership' )
		), array(
			'id_base' => 'membershiploginwidget'
		) );
	}

	/**
	 * Renders the widget content
	 *
	 * @access public
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	public function widget( $args, $instance ) {
		if ( is_user_logged_in() ) {
			return;
		}

		$defaults = array(
			'redirect' => '',
			'lostpass' => '',
		);

		foreach ( array_keys( $defaults ) as $key ) {
			if ( isset( $instance[$key] ) ) {
				$defaults[$key] = $instance[$key];
			}
		}

		$shortcode = "[membershiplogin";
		foreach ( $defaults as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . $value . '"';
		}
		$shortcode .= ']';

		echo $args['before_widget'];
			$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '' );
			if ( !empty( $title ) ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}

			echo do_shortcode( $shortcode );
		echo $args['after_widget'];
	}

	/**
	 * Renders the settings update form.
	 *
	 * @access public
	 * @param array $instance Current settings array.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array)$instance, array(
			'title'    => '',
			'redirect' => '',
			'lostpass' => '',
		) );

		?><p>
			<label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:', 'membership' ) ?></label>
			<input type="text"  id="<?php echo $this->get_field_id( 'title' ) ?>" class="widefat" name="<?php echo $this->get_field_name( 'title' ) ?>" value="<?php echo esc_attr( $instance['title'] ) ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'redirect' ) ?>"><?php _e( 'Redirect:', 'membership' ) ?></label>
			<input type="text"  id="<?php echo $this->get_field_id( 'redirect' ) ?>" class="widefat" name="<?php echo $this->get_field_name( 'redirect' ) ?>" value="<?php echo esc_attr( $instance['redirect'] ) ?>">
			<span class="description"><?php _e( 'Set a URL to redirect to (leave blank to use current page).', 'membership' ) ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'lostpass' ) ?>"><?php _e( 'Lost Password Link:', 'membership' ) ?></label>
			<input type="text"  id="<?php echo $this->get_field_id( 'lostpass' ) ?>" class="widefat" name="<?php echo $this->get_field_name( 'lostpass' ) ?>" value="<?php echo esc_attr( $instance['lostpass'] ) ?>">
			<span class="description"><?php _e( 'Set a URL to lost password restoring page or leave it blank to skip it.', 'membership' ) ?></span>
		</p><?php
	}

}