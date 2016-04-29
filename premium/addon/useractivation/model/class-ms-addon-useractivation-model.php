<?php
/**
 * Invitation model.
 *
 * Persisted by parent class MS_Model_Member.
 *
 * @since  1.0.3
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Useractivation_Model extends MS_Model_Member {
    
    public $user;
    
    public function __construct( $id )
    {
        $this->user = MS_Factory::load( 'MS_Model_Member', $id );
    }
    
    public static function get_unapproved_member_count()
    {
        global $wpdb;
        $sql = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='%s' AND meta_value='%s'",
                    MS_Addon_Useractivation::META_SLUG,
                    true
                );
        $query = $wpdb->get_var( $sql );
        
        return $query;
    }
    
    public static function get_unapproved_members( $args )
    {
        $members = array();
        global $wpdb;
        $sql = $wpdb->prepare(
                    "SELECT * FROM $wpdb->usermeta WHERE meta_key='%s' AND meta_value='%s'",
                    MS_Addon_Useractivation::META_SLUG,
                    true
                );
        $users = $wpdb->get_results( $sql, ARRAY_A );
        
        foreach( $users as $user )
        {
            $members[] = MS_Factory::load( 'MS_Model_Member', $user['user_id'] );
        }
        
        return $members;
    }
    
    
}