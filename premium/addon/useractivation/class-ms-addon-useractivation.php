<?php
/**
 * Add-on: Allow Search-Engines to index protected content.
 *
 * @since  1.0.3
 */
class MS_Addon_Useractivation extends MS_Addon {
    
    /**
     * The Add-on ID
     *
     * @since 1.0.3
     */
    const ID = 'addon_useractivation';
    
    /**
     * The Add-on ID
     *
     * @since 1.0.3
     */
    const SLUG = 'm2_unapproved_members';
    
    /**
     * The meta slug
     *
     * @since 1.0.3
     */
    const META_SLUG = 'm2-approved-user';
    
    /**
     * Checks if the current Add-on is enabled.
     *
     * @since 1.0.3
     * @return bool
     */
    static public function is_active() {
        return MS_Model_Addon::is_enabled( self::ID );
    }
    
    /**
     * Returns the Add-on ID (self::ID).
     *
     * @since 1.0.3
     * 
     */
    public function get_id() {
	return self::ID;
    }
    
    public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'User Activation', 'membership2' ),
			'description' => __( 'Allow to enable user activation for free membership.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-search',
		);

		return $list;
	}
    
    public function init() {
        if ( self::is_active() ) {
            
            $this->add_action(
                'ms_model_event_save_event',
                'ms_model_event_save_event',
                20, 3
            );
            
            /*$this->add_action(
                'wp_authenticate_user',
                'wp_authenticate_user'
            );*/
            
            $this->add_action(
                'the_content',
                'user_approval_message'
            );
            
            $this->add_action(
                'the_title',
                'user_approval_title'
            );
            
            $this->add_filter(
                'ms_plugin_menu_pages',
                'menu_item',
                10, 3
            );
            
            $this->add_filter(
                'ms_route_submenu_request',
                'route_submenu_request'
            );
            
            $this->add_filter(
                'ms_rule_has_access',
                'ms_rule_has_access_cb',
                20, 4
            );
            
            $this->add_action(
                'template_redirect',
                'template_redirect_cb'
            );
            
            /*$this->add_action(
                'user_row_actions',
                'user_row_actions'
            );
            
            $this->add_action(
                'ms_user_row_actions',
                'user_row_actions'
            );
            
            
            
            
            
            $this->add_action(
                'shake_error_codes',
                'shake_error_codes'
            );
            
            $this->add_action(
                'admin_menu',
                'admin_menu'
            );
            
            $this->add_action(
                'admin_print_scripts-users.php',
                'admin_print_scripts_users_php'
            );
            
            $this->add_action(
                'admin_print_scripts-site-users.php',
                'admin_print_scripts_users_php'
            );
            
            $this->add_action(
                'admin_print_styles-settings_page_wp-approve-user',
                'admin_print_styles_settings_page_wp_approve-user'
            );
            
            $this->add_action(
                'admin_action_wpau_approve',
                'admin_action_wpau_approve'
            );
            
            $this->add_action(
                'admin_action_wpau_bulk_approve',
                'admin_action_wpau_bulk_approve'
            );
            
            $this->add_action(
                'admin_action_wpau_unapprove',
                'admin_action_wpau_unapprove'
            );
            
            $this->add_action(
                'admin_action_wpau_bulk_unapprove',
                'admin_action_wpau_bulk_unapprove'
            );
            
            $this->add_action(
                'admin_action_wpau_update',
                'admin_action_wpau_update'
            );
            
            $this->add_action(
                'wpau_approve',
                'wpau_approve'
            );
            
            $this->add_action(
                'delete_user',
                'delete_user'
            );
            
            $this->add_action(
                'admin_init',
                'admin_init'
            );
            
            if ( is_admin() ) {
                $this->add_action(
                    'views_users',
                    'views_users'
                );
                
                $this->add_action(
                    'pre_user_query',
                    'pre_user_query'
                );
            }*/
        }
    }
    
    /**
     * Redirect free members to a different page
     * so that admin can approve those users. Paid members will be
     * bypassed and the route is same as before/usual.
     *
     * @since 1.0.3
     */
    public function ms_model_event_save_event( $event, $type, $data ) {
        
        if( $type == MS_Model_Event::TYPE_MS_SIGNED_UP )
        {
            $membership_id = $event->membership_id;
            $membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
            
            if( $membership->is_free )
            {
                update_user_meta( $event->user_id, self::META_SLUG, current_user_can( 'create_users' ) );
            }
            else
            {
                update_user_meta( $event->user_id, self::META_SLUG, true );
            }
            
        }
        
    }
    
    /**
     * Show the message to the users of
     * free membership just after registration. We are
     * redirecting them to a different page instead of
     * immediatte login.
     *
     * @since 1.0.3
     */
    public function user_approval_message( $content )
    {
        if( isset( $_REQUEST['current_step'] ) && $_REQUEST['current_step'] == 'm2-user-approval' )
        {
            return __( 'Your registration is under approval. An admin will check and aprrove your account. You will be informed via email.', 'membership2' );
        }
        
        return $content;
    }
    
    /**
     *
     */
    public function user_approval_title( $title )
    {
        if( isset( $_REQUEST['current_step'] ) && $_REQUEST['current_step'] == 'm2-user-approval' )
        {
            return __( 'Pending membership', 'membership2' );
        }
        
        return $title;
    }
    
    /**
     * Check if the user is already active when he
     * tries to login, set an error based on the saved meta value.
     *
     * Super Admin user will be skipped.
     *
     * @since 1.0.3
     */
    public function wp_authenticate_user( $userdata ) {
        
        $meta_slug = get_user_meta( $userdata->ID, self::META_SLUG, true );
        
        if( is_wp_error( $userdata ) )
        {
            return $userdata;
        }
        
        if( $userdata->user_email == get_bloginfo( 'admin_email' ) )
        {
            return $userdata;
        }
        
        if( isset( $meta_slug ) && $meta_slug )
        {
            return $userdata;
        }
        
        $userdata = new WP_Error(
                        'm2_confirmation_error',
                        __( '<strong>ERROR:</strong> Your account has to be confirmed by an administrator before you can login.', 'membership2' )
                    );

        return $userdata;
    }
    
    /**
     * Register the submenu for unapproved members
     *
     * @since 1.0.3
     */
    public function menu_item( $items, $limited_mode, $controller ) {
        if ( ! $limited_mode ) {
            $menu_item = array(
                self::ID => array(
                    'title' => __( 'Unapproved Members', 'membership2' ),
                    'slug' => self::SLUG,
                )
            );
            lib3()->array->insert( $items, 'before', 'addon', $menu_item );
        }

        return $items;
    }
    
    /**
    * Handles all sub-menu clicks. We check if the menu item of our add-on was
    * clicked and if it was we display the correct page.
    *
    * The $handler value is ONLY changed when the current menu is displayed.
    * If another menu item was clicked then don't do anythign here!
    *
    * @since  1.0.0
    * @param  array $handler {
    *         Menu-item handling information.
    *
    *         0 .. any|network|site  The admin-area that can handle our menu item.
    *         1 .. callable          A callback to handle the menu item.
    * @return array Menu-item handling information.
    */
    public function route_submenu_request( $handler ) {
        if ( MS_Controller_Plugin::is_page( self::SLUG ) ) {
            $handler = array(
                'network',
                array( $this, 'unapproved_members' ),
            );
        }

        return $handler;
   }
   
   /**
    * Render the admin page for unapproved members
    *
    * @since 1.0.3
    */
    public function unapproved_members()
    {
        $view = MS_Factory::create( 'MS_Addon_Useractivation_View_List' );
	$view->render();
    }
    
    /**
     * Check if user is active
     *
     * @since 1.0.3
     */
    public function is_current_user_active()
    {
        if( is_super_admin() ) return true;
        
        $user_id = get_current_user_id();
        $h = get_user_meta( $user_id, self::META_SLUG, true );
        
        return get_user_meta( $user_id, self::META_SLUG, true );
    }
    
    /**
     * Check and change access based on permission
     *
     * @since 1.0.3
     */
    public function ms_rule_has_access_cb( $access, $id, $rule_type, $obj )
    {
        if( $this->is_current_user_active() ) return $access;
        
        return ! $access;
    }
    
    public function template_redirect_cb()
    {
        if( ! $this->is_current_user_active() && ! isset( $_REQUEST['current_step'] ) )
        {
            wp_safe_redirect(
                add_query_arg(
                    array(
                        'current_step' => 'm2-user-approval'
                    ),
                    MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_MEMBERSHIPS )
                )
            );
        }
    }
}