<?php
/**
 * Bp Ajax Registration Admin 
 */

class BPAjaxAdminHelper{
    
    private static $instance;
    
    private function __construct() {
        
        add_action( 'bp_admin_init', array( $this, 'register_settings' ), 20 );
    }
    
    
    public static function get_instance(){
     
        if( !isset ( self::$instance ) )
             self::$instance = new self();
     
        return self::$instance;
    }
    
    public function register_settings(){
        // Add the ajax Registration settings section
            add_settings_section( 'bp_ajaxr', __( 'BP Ajax Registration Settings',  'bpajaxr' ), array( $this, 'reg_section' ), 'buddypress' );
            // Allow loading form via ajax or not?
            add_settings_field( 'bp_ajax_use_registration_form', __( 'Registration Form',   'bpajaxr' ), array( $this, 'settings_field' ), 'buddypress', 'bp_ajaxr' );
            add_settings_field( 'bp_ajaxr_user_role', __( 'Default User Role',   'bpajaxr' ), array( $this, 'settings_field_role' ), 'buddypress', 'bp_ajaxr' );
            register_setting  ( 'buddypress', 'bp_ajax_use_registration_form', 'intval' );
            register_setting  ( 'buddypress', 'bp_ajaxr_user_role' );
    }
    
    public function reg_section(){
        
    }
    
    public function settings_field(){
      
        $val = get_option( 'bp_ajax_use_registration_form', 1 ); ?>

         
        <input id="bp_ajax_use_registration_form" name="bp_ajax_use_registration_form" type="checkbox"value="1" <?php checked( 1, $val ); ?> />
        <label for="bp_ajax_use_registration_form"><?php _e( 'Enable Ajax Loading of Form ', 'bpajaxr' ); ?></label>
        <p class="description"><?php _e( 'When a user clicks registration link, the form is loaded via ajax', 'bpajaxr' ); ?></p>
        <?php 
        
    }
    


    public function settings_field_role(){
        $role = get_option( 'bp_ajaxr_user_role', 'subscriber' );
        ?>
        <select name ="bp_ajaxr_user_role">
            <?php      wp_dropdown_roles( $role );
        ?>
        </select>
        <p><?php _e( 'The role setting only applies on multisite!', 'bpajaxr');?></p>
        <?php 

    }
}
BPAjaxAdminHelper::get_instance();
