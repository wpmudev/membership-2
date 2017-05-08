<?php
/**
 * Renders Membership Plugin Settings.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since  1.0.0
 */
class MS_View_Settings_Edit extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$this->check_simulation();

		// Setup navigation tabs.
		$tabs 		= $this->data['tabs'];
		$settings 	= $this->data['settings'];
		$desc 		= array();

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			$desc = $this->advanced_forms( $desc );

			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Membership 2 Settings', 'membership2' ),
					'title_icon_class' => 'wpmui-fa wpmui-fa-cog',
					'desc' => $desc,
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$tab_name = str_replace( '-', '_', $active_tab );
			$callback_name = 'render_tab_' . $tab_name;
			$render_callback = apply_filters(
				'ms_view_settings_edit_render_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data
			);
			?>
			<div class="ms-settings ms-settings-<?php echo esc_attr( $tab_name ); ?>">
				<?php
				$html = call_user_func( $render_callback );
				$html = apply_filters( 'ms_view_settings_' . $callback_name, $html );
				echo $html;
				?>
			</div>
		</div>
		<?php
		$this->render_settings_footer( $tab_name , $settings->enable_cron_use );

		$html = ob_get_clean();

		return $html;
	}

	/* ====================================================================== *
	 *                               ADVANCED-FORMS
	 * ====================================================================== */

	/**
	 * Display advanced setting forms that can be triggered via an URL param.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $desc Array of items to display in the settings header.
	 * @return array New Array of items to display. Might include a HTML form.
	 */
	protected function advanced_forms( $desc ) {
		// A "Reset" button that can be added via URL param
		// Intentionally not translated (purpose is dev/testing)
		if ( ! empty( $_GET['reset'] ) ) {
			$reset_url = MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'reset' => 1 )
			);
			$reset_url = esc_url_raw(
				add_query_arg(
					MS_Model_Upgrade::get_token( 'reset' ),
					$reset_url
				)
			);
			$cancel_url = esc_url_raw( remove_query_arg( 'reset' ) );

			$desc[] = sprintf(
				'<div class="error" style="width:600px;margin:20px auto;text-align:center"><p><b>%1$s</b></p><hr />%2$s</div>',
				'Careful: This will completely erase all your Membership2 settings and details!',
				sprintf(
					'<form method="POST" action="%s" style="padding:20px 0">' .
					'<label style="line-height:28px">' .
					'<input type="checkbox" name="confirm" value="yes" /> Yes, reset everything!' .
					'</label><p>' .
					'<button class="button-primary">Do it!</button> ' .
					'<a href="%s" class="button">Cancel</a>' .
					'</p></form>',
					$reset_url,
					$cancel_url
				)
			);
		}

		// A "Resore" button that can be added via URL param
		// Intentionally not translated (purpose is dev/testing)
		if ( ! empty( $_GET['restore'] ) ) {
			$restore_url = MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'restore' => 1 )
			);
			$restore_url = esc_url_raw(
				add_query_arg(
					MS_Model_Upgrade::get_token( 'restore' ),
					$restore_url
				)
			);
			$cancel_url = esc_url_raw( remove_query_arg( 'restore' ) );
			$options = array();
			$files = lib3()->updates->plugin( 'membership2' );
			$files = lib3()->updates->list_files( 'json' );
			foreach ( $files as $file ) {
				$parts = explode( '-', $file );
				if ( 3 == count( $parts ) ) {
					$version = str_replace( 'upgrade_', '', $parts[0] );
					$version = str_replace( '_', '.', $version );
					$date = substr( $parts[1], 0, 4 ) . '-' . substr( $parts[1], 4, 2 ) . '-' . substr( $parts[1], 6, 2 );
					$time = substr( $parts[2], 0, 2 ) . ':' . substr( $parts[2], 2, 2 ) . ':' . substr( $parts[2], 4, 2 );
					$label = sprintf(
						'%2$s (%3$s) - Upgrade to %1$s',
						$version,
						$date,
						$time
					);
				} else {
					$label = $file;
				}
				$options[$label] = sprintf(
					'<option value="%1$s">%2$s</option>',
					$file,
					$label
				);
			}
			krsort( $options );

			$desc[] = sprintf(
				'<div class="error" style="width:600px;margin:20px auto;text-align:center"><p><b>%1$s</b></p><hr />%2$s</div>',
				'Careful: This will overwrite and replace existing data with old data from the Snapshot!',
				sprintf(
					'<form method="POST" action="%s" style="padding:20px 0">' .
					'<label style="line-height:28px">Snapshot:</label><p>' .
					'<select name="restore_snapshot">' . implode( '', $options ) . '</select>' .
					'</p><label style="line-height:28px">' .
					'<input type="checkbox" name="confirm" value="yes" /> Yes, overwrite data!' .
					'</label><p>' .
					'<button class="button-primary">Do it!</button> ' .
					'<a href="%s" class="button">Cancel</a>' .
					'</p></form>',
					$restore_url,
					$cancel_url
				)
			);
		}

        // A "Fix subscriptions" button that can be added via URL param
        // Intentionally not translated (purpose is dev/testing)
        if ( ! empty( $_GET['fixsub'] ) ) {
            $fix_url = MS_Controller_Plugin::get_admin_url(
                'settings',
                array( 'fixsub' => 1 )
            );
            $fix_url = esc_url_raw(
                add_query_arg(
                    MS_Model_Upgrade::get_token( 'fixsub' ),
                    $fix_url
                )
            );
            $cancel_url = esc_url_raw( remove_query_arg( 'fixsub' ) );

            $desc[] = sprintf(
                '<div class="error" style="width:600px;margin:20px auto;text-align:center"><p><b>%1$s</b></p><hr />%2$s</div>',
                'Careful: This might change the subscription status of some members!',
                sprintf(
                    '<form method="POST" action="%s" style="padding:20px 0">' .
                    '<label style="line-height:28px">' .
                    '<input type="checkbox" name="confirm" value="yes" /> Yes, fix subscriptions!' .
                    '</label><p>' .
                    '<button class="button-primary">Do it!</button> ' .
                    '<a href="%s" class="button">Cancel</a>' .
                    '</p></form>',
                    $fix_url,
                    $cancel_url
                )
            );
        }

		return $desc;
	}


	/* ====================================================================== *
	 *                               SETTINGS-FOOTER
	 * ====================================================================== */

	/**
	 * Display a footer below the Settings box.
	 * The footer will show information on the next scheduled cron jobs and also
	 * allow the user to run these jobs instantly.
	 *
	 * @since  1.0.0
	 * @param  string $tab_name Name of the currently open settings-tab.
	 */
	protected function render_settings_footer( $tab_name , $show = true ) {
		if ( 'general' != $tab_name ) { return; }

		$status_stamp = wp_next_scheduled( 'ms_cron_check_membership_status' ) - time();

		if ( $status_stamp > 0 ) {
			$status_delay = sprintf(
				__( 'in %s hrs %s min', 'membership2' ),
				floor( ($status_stamp - 1) / 3600 ),
				date( 'i', $status_stamp )
			);
		} else {
			$status_delay = __( '(now...)', 'membership2' );
		}

		$status_url = esc_url_raw(
			add_query_arg( array( 'run_cron' => 'ms_cron_check_membership_status' ) )
		);
		
		$lbl_run = __( 'Run now!', 'membership2' );


		echo '<div class="cf ms-settings-footer"><div class="ms-tab-container">&nbsp;</div>';
		echo '<div>';

		if ( MS_Plugin::get_modifier( 'MS_LOCK_SUBSCRIPTIONS' ) ) {
			_e( 'Membership Status Checks are disabled.', 'membership2' );
			echo ' ';
		} else {
			printf(
				__( 'Check Membership Status changes %s.' ) . ' ',
				'<a href="' . $status_url . '" title="' . $lbl_run . '">' . $status_delay . '</a>'
			);
		}

		if ( MS_Plugin::get_modifier( 'MS_STOP_EMAILS' ) ) {
			_e( 'Sending Email Responses is disabled.', 'membership2' );
		} else {
			
			$email_stamp = wp_next_scheduled( 'ms_cron_process_communications' ) - time();
			
			if ( $email_stamp > 0 ) {
				$email_delay = sprintf(
					__( 'in %s hrs %s min', 'membership2' ),
					floor( ($email_stamp - 1) / 3600 ),
					date( 'i', $email_stamp )
				);
			} else {
				$email_delay = __( '(now...)', 'membership2' );
			}
			
			$email_url = esc_url_raw(
				add_query_arg( array( 'run_cron' => 'ms_cron_process_communications' ) )
			);
			
			if ( !$show ) {
				$count = MS_Model_Communication::get_queue_count();
				if ( ! $count ) {
					$msg = __( 'No pending Email Responses found', 'membership2' );
				} elseif ( 1 == $count ) {
					$msg = __( 'Send 1 pending Email Response %1$s', 'membership2' );
				} else {
					$msg = __( 'Send %2$s pending Email Responses %1$s', 'membership2' );
				}
				echo '<span class="ms-settings-email-cron">';
				printf(
					$msg,
					'<a href="' . $email_url . '"title="' . $lbl_run . '">' . $email_delay . '</a>',
					$count
				);
				echo '</span>';
			}
		}

		echo '</div></div>';
	}

	/* ====================================================================== *
	 *                               GENERAL
	 * ====================================================================== */

	public function render_tab_general() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_General' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PAYMENT
	 * ====================================================================== */

	public function render_tab_payment() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Payment' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               PROTECTION MESSAGE
	 * ====================================================================== */

	public function render_tab_messages() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Messages' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               AUTOMATED MESSAGES
	 * ====================================================================== */

	public function render_tab_emails() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Communications' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

	/* ====================================================================== *
	 *                               IMPORT
	 * ====================================================================== */

	public function render_tab_import() {
		$tab = MS_Factory::create( 'MS_View_Settings_Page_Import' );
		$tab->data = $this->data;

		return $tab->to_html();
	}

}