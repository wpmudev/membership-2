<?php
/*
Addon Name: Membership Login Widget
Description: Membership widgets
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class membershiploginwidget extends WP_Widget {

	function membershiploginwidget() {

		$locale = apply_filters( 'membership_locale', get_locale() );
		$mofile = membership_dir( "membershipincludes/languages/membership-$locale.mo" );

		if ( file_exists( $mofile ) )
			load_textdomain( 'membership', $mofile );

		$widget_ops = array( 'classname' => 'membershiploginwidget', 'description' => __('Membership Login Widget', 'membership') );
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'membershiploginwidget');
		$this->WP_Widget( 'membershiploginwidget', __('Membership Login Widget', 'membership'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args );

		// build the check array
		$defaults = array(
			'redirect' 		=> ''
		);

		foreach($defaults as $key => $value) {
			if(isset($instance[$key])) {
				$defaults[$key] = $instance[$key];
			}
		}

		extract($defaults);

		if( empty($redirect) ) {
			do_shortcde("[membershiplogin]");
		} else {
			do_shortcde("[membershiplogin redirect='" . $redirect . "']");
		}

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$defaults = array(
			'redirect' 		=> ''
		);

		foreach ( $defaults as $key => $val ) {
			$instance[$key] = $new_instance[$key];
		}


		return $instance;
	}


	function form( $instance ) {

		$defaults = array(
			'redirect' 		=> ''
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		extract($instance);

		?>
			<p>
				<?php _e('Set a URL to redirect to (leave blank to use current page):','membership'); ?>
			</p>
			<p>
				<?php _e('Redirect','membership'); ?><br/>
				<input type='text' class='widefat' name='<?php echo $this->get_field_name( 'redirect' ); ?>' id='<?php echo $this->get_field_id( 'redirect' ); ?>' value='<?php echo esc_attr($instance['redirect']); ?>' />
			</p>
	<?php
	}
}

function membershiploginwidget_register() {
	register_widget( 'membershiploginwidget' );
}

add_action( 'widgets_init', 'membershiploginwidget_register' );


?>