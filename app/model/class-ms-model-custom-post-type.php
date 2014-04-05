<?php

class MS_Model_Custom_Post_Type extends MS_Hooker {
	
	protected $custom_post_type;
	
	protected $id;
	
	protected $name;
	
	protected $description;
	
	protected $user_id;
	
	protected $modified;
	
	protected static $ignore_fields;
	
	public function __construct() {
		$this->_add_action( 'init', 'register_custom_post_type', 0 );
	}
	
	public function register_custom_post_type() {
		
	}
	public function save()
	{
// 		switch_to_blog(1);
	
		if(empty($this->id))
		{
			$post = array(
					'comment_status' =>  'closed',
					'ping_status' => 'closed',
					'post_author' => $this->user_id,
					'post_content' => $this->description,
					'post_excerpt' => $this->description,
					'post_name' => $this->name,
					'post_status' => 'private',
					'post_title' => $this->name,
					'post_type' => $custom_post_type
			);
			$this->id = wp_insert_post($post);
		}
		//update details in postmeta table
		$this->modified = date('Y-m-d H:i:s');
		$post_meta = get_post_meta($this->id, '',false);
		$fields = get_object_vars($this);
		foreach($fields as $field => $val)
		{
			if(in_array($field, self::$ignore_fields))
			{
				continue;
			}
			if(isset($this->$field) && (!isset($post_meta[$field][0]) || $post_meta[$field][0] != $this->$field))
			{
				update_post_meta( $this->id, $field, $this->$field);
			}
		}
// 		restore_current_blog();
	}
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
	}
	
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			$this->$property = $value;
		}
	
		return $this;
	}
	
}