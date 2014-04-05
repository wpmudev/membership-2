<?php
class MS_Model_Membership extends MS_Model_Custom_Post_Type {

	protected $custom_post_type = 'ms_membership';

	public function __construct() {
	}

	public function register_custom_post_type() {
		// register the membership post type
		register_post_type( $this->custom_post_type, 
				apply_filters( 'ms_register_post_type_ms_membership', 
						array(
								'labels' => array(
										'name' => __( 'Membership', MS_TEXT_DOMAIN ),
										'singular_name' => __( 'Membership', MS_TEXT_DOMAIN ),
										'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
										'view_item' => __( 'View Membership', MS_TEXT_DOMAIN ),
										'search_items' => __( 'Search Membership', MS_TEXT_DOMAIN ),
										'not_found' => __( 'No Membership Found', MS_TEXT_DOMAIN ) 
								),
								'description' => __( 'Membership available.', MS_TEXT_DOMAIN ),
								'public' => false,
								'show_ui' => false,
								// 'capability_type' => apply_filters('ms_membership_capability',
								// 'page'),
								'hierarchical' => false,
								'rewrite' => false,
								'query_var' => false,
								'supports' => array() 
						) ) );
	}

}