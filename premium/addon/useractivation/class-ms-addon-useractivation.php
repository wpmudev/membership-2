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
                'ms_model_event_' . MS_Model_Event::TYPE_MS_SIGNED_UP,
                'ms_model_event_save_event',
                20, 2
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
                'ms_model_membership_has_access_to_post',
                'check_post_access',
                20, 2
            );
            
            $this->add_filter(
                'ms_model_membership_has_access_to_current_page',
                'check_current_page_access',
                20, 3
            );
            
            $this->add_action(
                'wp_footer',
                'show_notes_to_unapproved_members'
            );
            
            $this->add_action(
                'ms_user_approve_new_user',
                'send_notification'
            );
            
        }
    }
    
    /**
     * Redirect free members to a different page
     * so that admin can approve those users. Paid members will be
     * bypassed and the route is same as before/usual.
     *
     * @since 1.0.3
     */
    public function ms_model_event_save_event( $event, $data ) {
        $membership_id = $event->membership_id;
        $membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
        
        if( $membership->is_free )
        {
            update_user_meta( $event->user_id, self::META_SLUG, current_user_can( 'create_users' ) );
            if( ! current_user_can( 'create_users' ) )
            {
                do_action( 'ms_user_approve_new_user', $event->user_id, $membership );
            }
        }
        else
        {
            update_user_meta( $event->user_id, self::META_SLUG, true );
        }
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
        
        if( ! is_user_logged_in() ) return true;
        
        $user_id = get_current_user_id();
        
        return get_user_meta( $user_id, self::META_SLUG, true );
    }
    
    /**
     * Check post access
     *
     * @since 1.0.3
     */
    public function check_post_access( $has_access, $membership )
    {
        if( ! $this->is_current_user_active() )
        {
            return false;
        }
        
        return $has_access;
    }
    
    /**
     * Check current page access
     *
     * @since 1.0.3
     */
    public function check_current_page_access( $has_access, $post_id, $membership )
    {
        if( ! $this->is_current_user_active() )
        {
            return false;
        }
        
        return $has_access;
    }
    
    /**
     * Show unapproved user note
     *
     * @since 1.0.3
     */
    private function _unapproved_user_note()
    {
        return apply_filters(
                    'ms_user_activation_unapproved_user_note',
                    __( 'Your membership request is still under approval. An admin will review and make the decision.', 'membership2' )
                );
    }
    
    /**
     * Show note to unapproved members in footer
     *
     * @since 1.0.3
     */
    public function show_notes_to_unapproved_members()
    {
        if( $this->is_current_user_active() )
        {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery( function( $ ) {
            var html = '<div class="ms_unapproved_members_note">';
                html += '<?php echo $this->_unapproved_user_note() ?>';
            html += '</div>';
            
            $( 'body' ).append( html ).css( {
                paddingBottom: $( '.ms_unapproved_members_note' ).outerHeight()
            } );
        } );
        </script>
        
        <style>
        .ms_unapproved_members_note{
            position: fixed;
            width: 100%;
            padding: 15px 20px;
            text-align: center;
            background: #345678;
            color: #fff;
            font-size: 14px;
            bottom: 0;
            left: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Send notifications
     *
     * @since 1.0.3
     */
    public function send_notification( $user_id, $membership )
    {
        $user = new WP_User( $user_id );
        $to = $user->user_email;
        $subject = get_bloginfo( 'name' ) . __( ' :: Your membership request is under approval.', 'membership2' );
        $body = __( 'Dear ' . $user->display_name . ', your membership request is under approval. An admin will review and make a decision. Thank you.' );
        
        $subject = apply_filters( 'ms_user_activation_user_notification_subject', $subject );
        $body = apply_filters( 'ms_user_activation_user_notification_body', $body );
        
        wp_mail(
            $to,
            $subject,
            $body
        );
        
        $super_admins = get_super_admins();
        $admins = array();
        foreach( $super_admins as $super_admin )
        {
            $admin_user = get_user_by( 'login', $super_admin );
            $admins[] = $admin_user->user_email;
        }
        
        $subject = get_bloginfo( 'name' ) . __( ' :: A New membership request is under approval.', 'membership2' );
        $body = __( 'Dear Admin, A new membership request is under approval. You can check it here: ' . admin_url( 'admin.php?page=membership2-' . self::SLUG ) . '. Thank you.' );
        
        $subject = apply_filters( 'ms_user_activation_admin_notification_subject', $subject );
        $body = apply_filters( 'ms_user_activation_admin_notification_body', $body );
        
        wp_mail(
            $admins,
            $subject,
            $body
        );
        
    }
}