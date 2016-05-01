<?php
/**
 * Unapproced Members List Table
 *
 * @since  1.0.3
 */
class MS_Addon_Useractivation_Helper_Listtable extends MS_Helper_ListTable {
    
    protected $id = 'unapproved_users';
    
    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'member',
                'plural'   => 'members',
                'ajax'     => false,
            )
        );
    }
    
    public function get_columns() {
        return apply_filters(
            'ms_addon_useractivation_helper_listtable_columns',
            array(
                'cb'            => '<input type="checkbox" />',
                'username'      => __( 'Username', 'membership2' ),
                'email'         => __( 'Email', 'membership2' ),
                'membership'    => __( 'Membership', 'membership2' )
            )
        );
    }
    
    public function get_hidden_columns() {
        return apply_filters(
            'ms_addon_invitation_helper_listtable_membership_hidden_columns',
            array()
        );
    }

    public function get_sortable_columns() {
        return apply_filters(
            'ms_addon_invitation_helper_listtable_membership_sortable_columns',
            array()
        );
    }
    
    public function prepare_items() {
        $this->_column_headers = array(
            $this->get_columns(),
            $this->get_hidden_columns(),
	    $this->get_sortable_columns(),
        );

        $total_items = MS_Addon_Useractivation_Model::get_unapproved_member_count();
        $per_page = $this->get_items_per_page( 'invitation_per_page', 10 );
        $current_page = $this->get_pagenum();

        $args = array(
            'posts_per_page' => $per_page,
            'offset' => ( $current_page - 1 ) * $per_page,
        );

        $this->items = apply_filters(
            'ms_addon_invitation_helper_listtable_invitation_items',
            MS_Addon_Useractivation_Model::get_unapproved_members( $args )
        );

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page' => $per_page,
            )
        );
    }
    
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="user_id[]" value="%1$s" />',
            esc_attr( $item->id )
        );
    }
    
    public function column_username( $item )
    {
        $actions['edit'] = sprintf(
            '<a href="?page=%s&action=%s&user_id=%s">%s</a>',
            esc_attr( $_REQUEST['page'] ),
            'm2_approve',
            esc_attr( $item->id ),
            __( 'Approve', 'membership2' )
        );
        
        return sprintf(
                    '%1$s %2$s',
                    $item->username,
                    $this->row_actions( $actions )
		);
    }
    
    public function column_default( $item, $column_name )
    {
        switch( $column_name )
        {   
            case 'email':
                return $item->email;
                break;
            
            case 'membership':
                $subscriptions = $item->get_membership_ids();
                $memberships = array();
                foreach( $subscriptions as $subscription )
                {
                    $membership = MS_Factory::load( 'MS_Model_Membership', $subscription );
                    $memberships[] = $membership->name;
                }
                
                return implode( ', ', $memberships );
                break;
            
            default:
                return 'N/A';
        }
    }
    
    
    public function get_bulk_actions() {
        return apply_filters(
            'ms_addon_useractivation_helper_listtable_bulk_actions',
            array(
                'm2_approve' => __( 'Approve', 'membership2' ),
            )
        );
    }
}