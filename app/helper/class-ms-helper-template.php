<?php
/**
 * Utilities class
 *
 * @since  1.0.3
 */
class MS_Helper_Template extends MS_Helper {
    
    const TARGET_DIRECTORY = 'membership2';
    const TEMPLATE_DIRECTORY = 'app/view/templates/';
    
    static public $ms_single_box;
    static public $ms_registration_form;
    static public $ms_front_payment;
    static public $ms_account;
    
    public function get_template_dir() {
        return MS_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::TEMPLATE_DIRECTORY;
    }
    
    public static function in_child_theme( $file ) {
        $path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . self::TARGET_DIRECTORY . DIRECTORY_SEPARATOR . $file;
        return file_exists( $path ) ? $path : false;
    }
    
    public static function in_parent_theme( $file ) {
        $path = get_template_directory() . DIRECTORY_SEPARATOR . self::TARGET_DIRECTORY . DIRECTORY_SEPARATOR . $file;
        return file_exists( $path ) ? $path : false;
    }
    
    public static function template_exists( $file ) {
        if( $path = self::in_child_theme( $file ) ) {
            return $path;
        }elseif( $path = self::in_parent_theme( $file ) ){
            return $path;
        }else{
            return self::get_template_dir() . $file;
        }
    }
    
}