<?php
/**
 * Plugin Name: BP Ajax registration
 * Version: 1.1.2
 * Plugin URI: http://buddydev.com/plugins/bp-ajax-registration/
 * Author: Brajesh Singh
 * Author URI: http://buddydev.com/members/sbrajesh
 * License: GPL
 * Last Updated: August 19, 2014
 * Compatible with BP 2.0+
 */

define( 'BP_AJAX_REGISTER_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Helper class
 * loads files and assets
 * 
 */
class BP_Ajax_Register_Helper {
    
    private static $instance;
    
    private function __construct() {
        
                    //load text domain
        add_action ( 'bp_loaded', array( $this, 'load_textdomain' ), 2 );
        //load files
        add_action( 'bp_loaded', array( $this, 'load' ) );
        
        
        //js/css loader
        add_action( 'wp_print_scripts', array( $this, 'load_js' ) );
        add_action( 'wp_print_styles', array( $this, 'load_css' ) );
        
    }
    
    /**
     * 
     * @return BP_Ajax_Register_Helper
     */
    public static function get_instance() {
        
        if( !isset( self::$instance ) ) {
            
            self::$instance = new self();
        }    
        
        return self::$instance;
        
    }
    //localization
    public function load_textdomain() {
           
        $locale = apply_filters( 'bpajaxr_load_textdomain_get_locale', get_locale() );

        // if load .mo file
        if ( !empty( $locale ) ) {
            $mofile_default = sprintf( '%slanguages/%s.mo', plugin_dir_path( __FILE__ ), $locale );

            $mofile = apply_filters( 'bpajaxr_load_textdomain_mofile', $mofile_default );

            if ( file_exists( $mofile ) ) {
                        // make sure file exists, and load it
                load_textdomain( 'bpajaxr', $mofile );
            }
        }
    }
    
    /**
     * Load required files
     * 
     */
    public function load() {
        
        $path = plugin_dir_path( __FILE__ );
        
        require_once $path . 'core/registration-handler.php';
        
        if( is_admin() )
            require_once $path . 'admin.php';
        
    }
    
    
    public function load_js() {
        
        if( is_user_logged_in() || ! apply_filters( 'bp_ajaxr_load_js', true ) )
            return;
        
        $url = plugin_dir_url( __FILE__ );
        
        wp_register_script( 'magnific-popup', $url . '_inc/jquery.magnific-popup.min.js', array( 'jquery' ) );
        wp_register_script( 'bp-ajax-register-js', $url . '_inc/bp-ajax-register.js', array( 'jquery', 'magnific-popup' ) );
       
        wp_enqueue_script( 'magnific-popup' );
        wp_enqueue_script( 'bp-ajax-register-js' );


    }

    public function load_css() {
        
        if( is_user_logged_in() || ! apply_filters( 'bp_ajaxr_load_css', true ) )
            return;
        
        $url = plugin_dir_url( __FILE__ );
        
        wp_register_style( 'bp-ajaxr-register-css', $url . '_inc/bp-ajax-register.css' );
        wp_register_style( 'bp-ajaxr-magnific-css', $url . '_inc/magnific-popup.css' );
        
        wp_enqueue_style( 'bp-ajaxr-magnific-css' );
        wp_enqueue_style( 'bp-ajaxr-register-css' );
    }
    
}

BP_Ajax_Register_Helper::get_instance();

add_action( 'wp_footer', 'bpajaxr_include_form' );

/**
 * If the ajax loading of form is disabled, we keep the form as hidden in the footer
 * @return type
 */
function bpajaxr_include_form() {
    
    if( is_user_logged_in() || ! bp_get_signup_allowed() )
        return;
    
    $is_enabled = get_option( 'bp_ajax_use_registration_form', 1 );
    
    if( $is_enabled )
        return;
    
    $helper = BP_Ajax_Registration_Handler::get_instance();
    
    BP_Ajax_Registration_Handler::show_form();
}


