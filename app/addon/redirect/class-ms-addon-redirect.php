<?php
/**
 * Add-On controller for: Redirect control
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Redirect extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'addon_redirect';

	// Ajax Actions
	const AJAX_SAVE_SETTING = 'addon_redirect_save';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	public function init() {
		if ( self::is_active() ) {
			// Add new settings tab
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10, 2
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10, 3
			);

			// Save settings via ajax
			$this->add_ajax_action(
				self::AJAX_SAVE_SETTING,
				'ajax_save_setting'
			);

			// Add filter to replace the default plugin URLs with custom URLs
			$this->add_action(
				'ms_url_after_login',
				'filter_url_after_login',
				10, 2
			);

			$this->add_action(
				'ms_url_after_logout',
				'filter_url_after_logout',
				10, 2
			);
                        
                        $this->add_filter(
                            'login_redirect',
                            'm2_login_redirect',
                            999, 3
                        );
                        
                        $this->add_action(
                            'wp_logout',
                            'm2_logout_redirect',
                            999
                        );
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Redirect Control', 'membership2' ),
			'description' => __( 'Define your individual URL to display after a user is logged-in or logged-out.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-share',
			'details' => array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => __( 'Settings', 'membership2' ),
					'desc' => __( 'When this Add-on is enabled you will see a new section in the "Settings" page with additional options.', 'membership2' ),
				),
			),
		);

		return $list;
	}

	/**
	 * Returns the Redirect-Settings model.
	 *
	 * @since  1.0.0
	 * @return MS_Addon_Redirect_Model
	 */
	static public function model() {
		static $Model = null;

		if ( null === $Model ) {
			$Model = MS_Factory::load( 'MS_Addon_Redirect_Model' );
		}

		return $Model;
	}

	/**
	 * Add redirect settings tab in settings page.
	 *
	 * @since  1.0.0
	 *
	 * @param array $tabs The current tabs.
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'Redirect', 'membership2' ),
			'url' => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}

	/**
	 * Add redirect settings-view callback.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $callback The current function callback.
	 * @param  string $tab The current membership rule tab.
	 * @param  array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = MS_Factory::load( 'MS_Addon_Redirect_View' );
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * @since  1.0.0
	 */
	public function ajax_save_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$model = self::model();

			$model->set( $_POST['field'], $_POST['value'] );
			$model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg );
	}

	/**
	 * Replaces the default "After Login" URL
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url
	 * @return string
	 */
	public function filter_url_after_login( $url, $enforce ) {
		if ( ! $enforce ) {
			$model = self::model();
			$new_url = $model->get( 'redirect_login' );

			if ( ! empty( $new_url ) ) {
				$url = lib3()->net->expand_url( $new_url );
			}
		}

		return $url;
	}
        
        /**
         * Login redirect
         *
         * @since 1.0.2.8
         */
        public function m2_login_redirect( $redirect_to, $request, $user ) {
            if( isset( $user->ID ) && ! MS_Model_Member::is_admin_user( $user->ID ) ) {
                $model = self::model();
                $new_url = $model->get( 'redirect_login' );

                if ( ! empty( $new_url ) ) {
                        $redirect_to = lib3()->net->expand_url( $new_url );
                }
            }
            
            return $redirect_to;
        }

	/**
	 * Replaces the default "After Logout" URL
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url
	 * @return string
	 */
	public function filter_url_after_logout( $url, $enforce ) {
		if ( ! $enforce ) {
			$model = self::model();
			$new_url = $model->get( 'redirect_logout' );

			if ( ! empty( $new_url ) ) {
				$url = lib3()->net->expand_url( $new_url );
			}
		}

		return $url;
	}
        
        /**
         * Logout URL
         *
         * @since 1.0.2.8
         */
        public function m2_logout_redirect() {
            $model = self::model();
            $new_url = $model->get( 'redirect_logout' );

            if ( ! empty( $new_url ) ) {
                $logout_url = lib3()->net->expand_url( $new_url );
            }else{
                $logout_url = site_url();
            }
            
            wp_redirect( $logout_url );
            exit;
        }

}