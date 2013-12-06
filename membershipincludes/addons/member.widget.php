<?php
/*
Addon Name: Membership Widget
Description: Membership widgets
Author: Incsub
Author URI: http://premium.wpmudev.org
*/

class membershipleveltext extends WP_Widget {

	const NAME = __CLASS__;

	function membershipleveltext() {
		$widget_ops = array( 'classname' => 'membershipleveltext', 'description' => __( 'Membership Level Text', 'membership' ) );
		$control_ops = array( 'id_base' => 'membershipleveltext' );
		$this->WP_Widget( 'membershipleveltext', __( 'Membership Level Text', 'membership' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args );

		// build the check array
		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'level'		 	=> 'none'
		);

		foreach($defaults as $key => $value) {
			if(isset($instance[$key])) {
				$defaults[$key] = $instance[$key];
			}
		}

		extract($defaults);

		$show = false;

		switch($level) {

			case 'none':	if(!is_user_logged_in() || !current_user_is_member()) {
								$show = true;
							}
							break;

			default:		if(current_user_on_level($level)) {
								$show = true;
							}
							break;

		}

		if($show) {
			echo $before_widget;
			$title = apply_filters('widget_title', $title );

			if ( !empty($title) ) {
				echo $before_title . $title . $after_title;
			}

			echo do_shortcode($content);

			echo $after_widget;
		}

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'level'		 	=> 'none'
		);

		foreach ( $defaults as $key => $val ) {
			$instance[$key] = $new_instance[$key];
		}

		if ( !current_user_can('unfiltered_html') ) {
			$instance['content'] = stripslashes( wp_filter_post_kses( addslashes($instance['content']) ) ); // wp_filter_post_kses() expects slashed
		}

		return $instance;
	}

	function get_membership_levels() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM " . membership_db_prefix($wpdb, 'membership_levels') . " WHERE level_active = 1;" );
	}

	function form( $instance ) {

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'level'		 	=> 'none'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		extract($instance);

		?>
			<p>
				<?php _e('Show the content below if the user is on the following level:','membership'); ?>
			</p>
			<p>
				<select name='<?php echo $this->get_field_name( 'level' ); ?>' id='<?php echo $this->get_field_id( 'level' ); ?>'>
					<option value='none' <?php selected($level, 'none'); ?>><?php _e('Non-member or not logged in','membership'); ?></option>
					<?php
					$levels = $this->get_membership_levels();

					foreach($levels as $alevel) {
						?>
						<option value='<?php echo $alevel->id; ?>' <?php selected($level, $alevel->id); ?>><?php echo $alevel->level_title; ?></option>
						<?php
					}
				?>
				</select>
			</p>
			<p>
				<?php _e('Title','membership'); ?><br/>
				<input type='text' class='widefat' name='<?php echo $this->get_field_name( 'title' ); ?>' id='<?php echo $this->get_field_id( 'title' ); ?>' value='<?php echo esc_attr(stripslashes($instance['title'])); ?>' />
			</p>
			<p>
				<?php _e('Content','membership'); ?><br/>
				<textarea class='widefat' name='<?php echo $this->get_field_name( 'content' ); ?>' id='<?php echo $this->get_field_id( 'content' ); ?>' rows='5' cols='40'><?php echo stripslashes($instance['content']); ?></textarea>
			</p>
	<?php
	}
}

class membershipsubtext extends WP_Widget {

	const NAME = __CLASS__;

	function membershipsubtext() {
		$widget_ops = array( 'classname' => 'membershipsubtext', 'description' => __( 'Membership Subscription Text', 'membership' ) );
		$control_ops = array( 'id_base' => 'membershipsubtext' );
		$this->WP_Widget( 'membershipsubtext', __( 'Membership Subscription Text', 'membership' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		extract( $args );

		// build the check array
		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'sub'		 	=> 'none'
		);

		foreach($defaults as $key => $value) {
			if(isset($instance[$key])) {
				$defaults[$key] = $instance[$key];
			}
		}

		extract($defaults);

		$show = false;

		switch($sub) {

			case 'none':	if(!is_user_logged_in() || !current_user_is_member()) {
								$show = true;
							}
							break;

			default:		if(current_user_on_subscription($sub)) {
								$show = true;
							}
							break;

		}

		if($show) {
			echo $before_widget;
			$title = apply_filters('widget_title', $title );

			if ( !empty($title) ) {
				echo $before_title . $title . $after_title;
			}

			echo do_shortcode($content);

			echo $after_widget;
		}

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'sub'		 	=> 'none'
		);

		foreach ( $defaults as $key => $val ) {
			$instance[$key] = $new_instance[$key];
		}

		if ( !current_user_can('unfiltered_html') ) {
			$instance['content'] = stripslashes( wp_filter_post_kses( addslashes($instance['content']) ) ); // wp_filter_post_kses() expects slashed
		}

		return $instance;
	}

	function get_subscriptions() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM " . membership_db_prefix( $wpdb, 'subscriptions' ) . " WHERE sub_active = 1" );
	}

	function form( $instance ) {

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'sub'		 	=> 'none'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		extract($instance);

		?>
			<p>
				<?php _e('Show the content below if the user is on the following subscription:','membership'); ?>
			</p>
			<p>
				<select name='<?php echo $this->get_field_name( 'sub' ); ?>' id='<?php echo $this->get_field_id( 'sub' ); ?>'>
					<option value='none' <?php selected($sub, 'none'); ?>><?php _e('Non-member or not logged in','membership'); ?></option>
					<?php
					$subs = $this->get_subscriptions();

					foreach($subs as $asub) {
						?>
						<option value='<?php echo $asub->id; ?>' <?php selected($sub, $asub->id); ?>><?php echo $asub->sub_name; ?></option>
						<?php
					}
				?>
				</select>
			</p>
			<p>
				<?php _e('Title','membership'); ?><br/>
				<input type='text' class='widefat' name='<?php echo $this->get_field_name( 'title' ); ?>' id='<?php echo $this->get_field_id( 'title' ); ?>' value='<?php echo esc_attr(stripslashes($instance['title'])); ?>' />
			</p>
			<p>
				<?php _e('Content','membership'); ?><br/>
				<textarea class='widefat' name='<?php echo $this->get_field_name( 'content' ); ?>' id='<?php echo $this->get_field_id( 'content' ); ?>' rows='5' cols='40'><?php echo stripslashes($instance['content']); ?></textarea>
			</p>
	<?php
	}
}

add_action( 'widgets_init', 'membershipwidget_register' );
function membershipwidget_register() {
	register_widget( membershipleveltext::NAME );
	register_widget( membershipsubtext::NAME );
}