<?php

/*
 *Adding Support for BuddyPress 1.7 Theme Compat
 */
function bp_gallery_add_template_stack( $templates ) {
    // if we're on a page of our plugin and the theme is not BP Default, then we
    // add our path to the template path array
    //if ( bp_is_current_component( 'gallery' ) || bp_is_current_action( 'gallery' ) ) {
 
      
        $templates[] = BP_GALLERY_PLUGIN_DIR. 'gallery-theme-compat/';//theme compat templates
   // }
 
    return $templates;
}
 
add_filter( 'bp_get_template_stack', 'bp_gallery_add_template_stack', 10, 1 );

function bp_gallery_locate_template( $templates = false , $load = true ) {
    
    if( empty( $templates ) )
        return false;
 
        foreach ($templates as $template){
             
        if( locate_template( array( 'gallery-theme-compat/'.$template  ), false )){
           locate_template( array(  'gallery-theme-compat/'.$template  ), $load );
           
         return;  
        }
        
        }
            return bp_locate_template( $templates, $load );
    
}

class BP_Gallery_Theme_Compat {
    private $load_plugins = false;
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
 //! bp_current_action() && !bp_displayed_user_id() &&
        if(bp_is_current_component('gallery') && !bp_current_action(0)){
           // buddypress()->theme_compat->use_with_current_theme = bp_gallery_is_theme_compat();
            add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
            add_filter( 'bp_replace_the_content', array( $this, 'gallery_content'    ),20 );
            add_filter('bp_get_template_part', array ($this, 'filter_user_template'));
            return;
        }
        //if we   are here, It is a member page or group page 
        if (   bp_is_current_component( 'gallery' ) || bp_is_current_action( 'gallery' ) ) {
             
            //reset post data
           // add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
            // then filter 'the_content' 
           // add_filter( 'bp_replace_the_content', array( $this, 'gallery_content'    ),20 );
            //add_filter('bp_get_template_part', array ($this, 'filter_user_template'));
           
           $this->load_plugins = true;
       }
        
        
    }
 
    /**
     * Update the global $post with meaningless data
     */
    public function directory_dummy_post() {
 
        bp_theme_compat_reset_post( array(
            'ID'             => 0,
            'post_title'     => get_the_title(),//except me, I am meaning full and give admins power to control title from backend
            'post_author'    => 0,
            'post_date'      => 0,
            'post_content'   => '',
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'is_archive'     => true,
            'comment_status' => 'closed'
        ) );
    }
    /**
     * Filter the_content with bp-plugin index template part
     */
    public function gallery_content() {
        
        bp_buffer_template_part( 'gallery/index' );
        
    }
 
    //just some hack to avoid more work. I am not lazy, my keyboard is.
    // it avoid loading of buddypress members/single/home.php and groups/single/home.php on gallery pages.
    function filter_user_template($templates){
        
        for($i=0; $i<count($templates); $i++){
            
            if($templates[$i] == 'members/single/home.php' || $templates[$i] == 'groups/single/home.php' ){
                unset($templates[$i]);
                
            }
            
        }
        
        return $templates;
    }
}
 
new BP_Gallery_Theme_Compat ();
//directory page

//members gallery
//
//
//groups page

//



function bp_gallery_is_theme_compat(){
     static $is_theme_compat ;
     
     if( isset( $is_theme_compat ) )
         return $is_theme_compat;
    // if using theme compat files
    //if gallery folder is not present in the theme
    if(  locate_template( array('gallery/index.php') ) )
        $is_theme_compat = false;
    else
        $is_theme_compat = true;
   
    return $is_theme_compat;
}

//if it is gallery theme compat, load the plugins.php

add_filter( 'gallery_single_media_template', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_single_gallery_template', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_upload_media', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_my_galleries_', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_my_galleries_photo', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_my_galleries_audio', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_my_galleries_video', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_edit_gallery', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_group_gallery_home', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_group_my_galleries', 'bp_gallery_load_theme_compat_files' );
add_filter( 'gallery_template_create_gallery', 'bp_gallery_load_theme_compat_files' );
function bp_gallery_load_theme_compat_files( $file ){
    if(!bp_gallery_is_theme_compat() )
        return $file;
   
    add_action('bp_template_content',  'bp_gallery_content_plugins'   );
                
   //are we on members page
    if(bp_is_user() && bp_is_current_component('gallery') )
           return 'members/single/plugins';
    elseif(bp_is_group()&& bp_is_current_action('gallery'))
        return 'groups/single/plugins';
    
}

function bp_gallery_content_plugins() {
    
       // ob_start();
        bp_gallery_locate_template(array('gallery/index.php'), true);
       // $content =  ob_get_clean();
        
        //return $content;
    }
    
    
    add_action('bp_member_plugin_options_nav', 'bp_gallery_show_user_admin_tabs_in_compat_mode');
    
    
    function bp_gallery_show_user_admin_tabs_in_compat_mode(){
        if(bp_gallery_is_theme_compat() && (bp_is_current_component('gallery') || bp_is_current_action('gallery')))
                bp_user_gallery_admin_tabs_theme_compat();
        
        
    }