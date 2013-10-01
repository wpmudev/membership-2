<?php
/*
Addon Name: Membership Login Widget
Description: Membership widgets
Author: Incsub
Author URI: http://premium.wpmudev.org
*/

class membershiploginwidget extends WP_Widget {

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

add_action( 'widgets_init', 'membershiploginwidget_register' );
function membershiploginwidget_register() {
	register_widget( membershiploginwidget::NAME );
}