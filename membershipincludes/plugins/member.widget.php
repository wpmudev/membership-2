<?php
/*
Plugin Name: Membership widget
Plugin URI: http://incsub.com
Description: This plugin adds a simple membership message widget.
Author: Barry
Version: 1.0
Author URI: http://caffeinatedb.com
*/

/*
Copyright 2007-2010 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class membershipleveltext extends WP_Widget {

	function membershipleveltext() {

		$locale = apply_filters( 'membership_locale', get_locale() );
		$mofile = membership_dir( "membershipincludes/languages/membership-$locale.mo" );

		if ( file_exists( $mofile ) )
			load_textdomain( 'membership', $mofile );

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

			echo apply_filters('the_content', $content);

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

class membershipsubtext extends WP_Widget {

	function membershipsubtext() {

		$locale = apply_filters( 'membership_locale', get_locale() );
		$mofile = membership_dir( "membershipincludes/languages/membership-$locale.mo" );

		if ( file_exists( $mofile ) )
			load_textdomain( 'membership', $mofile );

		$widget_ops = array( 'classname' => 'membershipsubtext', 'description' => __('Membership Subscription Text', 'membership') );
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'membershipsubtext');
		$this->WP_Widget( 'membershipsubtext', __('Membership Subscription Text', 'membership'), $widget_ops, $control_ops );
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

			echo apply_filters('the_content', $content);

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

		$sql = $wpdb->prepare( "SELECT * FROM " . membership_db_prefix($wpdb, 'subscriptions') . " WHERE sub_active = 1");

		return $wpdb->get_results($sql);

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

function membershipwidget_register() {
	register_widget( 'membershipleveltext' );
	register_widget( 'membershipsubtext' );
}

add_action( 'widgets_init', 'membershipwidget_register' );


?>