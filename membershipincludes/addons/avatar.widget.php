<?php
/*
Addon Name: Membership Avatar Widget
Description: Membership widgets
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
*/

class membershipavatarwidget extends WP_Widget {

	function membershipavatarwidget() {
		$widget_ops = array( 'classname' => 'membershipleveltext', 'description' => __('Membership Level Text', 'membership') );
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'membershipleveltext');
		$this->WP_Widget( 'membershipleveltext', __('Membership Level Text', 'membership'), $widget_ops, $control_ops );
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

		$sql = $wpdb->prepare( "SELECT * FROM " . membership_db_prefix($wpdb, 'membership_levels') . " WHERE level_active = 1;");

		return $wpdb->get_results($sql);

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

function membershipavatarwidget_register() {
	register_widget( 'membershipavatarwidget' );
}

add_action( 'widgets_init', 'membershipavatarwidget_register' );