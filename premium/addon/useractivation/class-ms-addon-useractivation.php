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
                'user_row_actions',
                'user_row_actions'
            );
            
            $this->add_action(
                'ms_user_row_actions',
                'user_row_actions'
            );
            
            $this->action(
                'wp_authenticate_user',
                'wp_authenticate_user'
            );
            
            $this->action(
                'user_register',
                'user_register'
            );
            
            $this->action(
                'shake_error_codes',
                'shake_error_codes'
            );
            
            $this->action(
                'admin_menu',
                'admin_menu'
            );
            
            $this->action(
                'admin_print_scripts-users.php',
                'admin_print_scripts_users_php'
            );
            
            $this->action(
                'admin_print_scripts-site-users.php',
                'admin_print_scripts_users_php'
            );
        }
    }
    
}