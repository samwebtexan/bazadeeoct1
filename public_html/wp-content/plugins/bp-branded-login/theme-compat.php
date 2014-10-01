<?php

/**
 * Adding Theme compat
 */
function bl_add_template_stack( $templates ) {
    global $bp;
    // if we're on a page of our plugin and the theme is not BP Default, then we
    // add our path to the template path array
   if (bp_is_current_component($bp->login->slug)||bp_is_current_component( $bp->resetpass->slug)) {
 
      if(is_child_theme())
        $templates[] = STYLESHEETPATH. '/blogin-theme-compat/';//theme compat templates
        $templates[] = TEMPLATEPATH. '/blogin-theme-compat/';//theme compat templates
        $templates[] = BL_PLUGIN_DIR. 'blogin-theme-compat/';//theme compat templates
    }
 
    return $templates;
}
add_filter( 'bp_get_template_stack', 'bl_add_template_stack', 10, 1 );

function bl_using_theme_compat(){
     static $is_theme_compat ;
     
     if( isset( $is_theme_compat ) )
         return $is_theme_compat;
    // if using theme compat files
    //if gallery folder is not present in the theme
    if(  locate_template( array('blogin/login.php') ) )
        $is_theme_compat = false;
    else
        $is_theme_compat = true;
   
    return $is_theme_compat;
}
class Blogin_Theme_Compat {
    /**
     * Setup the bp plugin component theme compatibility
     */
    public function __construct() {
        add_action( 'bp_setup_theme_compat', array( $this, 'setup_theme_compat' ) );
    }
 
    /**
     * Are we looking at something that needs theme compatability?
     */
    public function setup_theme_compat() {
        global $bp;
 //! bp_current_action() && !bp_displayed_user_id() &&
         if (bp_is_current_component($bp->login->slug)||bp_is_current_component( $bp->resetpass->slug)){
            buddypress()->theme_compat->use_with_current_theme = bl_using_theme_compat();
            add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
            add_filter( 'bp_replace_the_content', array( $this, 'blogin_content'    ),20 );
            
            }
       
        
    }
 
    /**
     * Update the global $post with meaningless data
     */
    public function directory_dummy_post() {
        global $post;
        bp_theme_compat_reset_post( array(
            'ID'             => get_the_ID(),
            'post_title'     => get_the_title(),//except me, I am meaning full and give admins power to control title from backend
            'post_author'    => 0,
            'post_date'      => 0,
            'post_content'   => $post->post_content,
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'is_archive'     => true,
            'comment_status' => 'closed'
        ) );
    }
    /**
     * Filter the_content with bp-plugin index template part
     */
    public function blogin_content() {
        global $bp;
         if (bp_is_current_component($bp->login->slug))
                 $template ='blogin/login';
         elseif(bp_is_current_component( $bp->resetpass->slug))
                 $template ='blogin/resetpass';
        
        bp_buffer_template_part($template );
        
    }
 
    
}
 
new Blogin_Theme_Compat ();
