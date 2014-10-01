<?php


add_action( 'wp_print_scripts', 'bp_gallery_load_scripts' );
add_action( 'wp_print_styles', 'bp_gallery_load_style' );
add_action( 'wp_footer', 'gallery_print_settings_js' );

/*load any required stylesheet files*/
function bp_gallery_load_style(){
         do_action( 'bp_gallery_css_loaded' );
     
}
/*load any Js required*/
function bp_gallery_load_scripts(){
    if( is_admin() )
        return;
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'flash-detect', BP_GALLERY_PLUGIN_URL."inc/js/flash-detect.js");//for detecting flash
    if( is_user_logged_in() ){
         wp_enqueue_script( 'json2' );
         if( bp_is_current_component('gallery') || bp_is_current_action( 'gallery' ) ) {
            wp_enqueue_script( 'jquery-ui-core',array( 'jquery' ) );
            wp_enqueue_script( 'jquery-ui-draggable',array( 'jquery-ui-core' ) );
            wp_enqueue_script( 'jquery-ui-droppable', array( 'jquery-ui-draggable' ) );
            wp_enqueue_script( 'jquery-ui-selectable',  array( 'jquery-ui-core' ) );
            wp_enqueue_script( 'jquery-ui-sortable',  array( 'jquery-ui-core' ) );
         }
       //  wp_enqueue_script("jqueryui",BP_GALLERY_PLUGIN_URL."inc/js/jquery-ui-1.7.2.custom.min.js");//for sorting dragging/dropping
        
    }
    wp_enqueue_script( 'bp-gallery-general', BP_GALLERY_PLUGIN_URL . 'inc/js/general.js' );
    wp_localize_script( 'bp-gallery-general', 'bp_gallery_js_terms', bp_gallery_get_localizable_js_vars() );
   
        
    do_action( 'bp_gallery_js_loaded' );//allow template to hook other js here
}

/* for loading some scripts when we are at gallery component*/

function gallery_print_settings_js(){
 ?>   <script type='text/javascript'>
     cur_component='<?php echo gallery_get_current_object_type(); ?>';
     cur_component_id='<?php echo gallery_get_current_object_id(); ?>';
//alert(cur_component_id);
    gallery_home_url="<?php echo bp_get_gallery_home_url();?>";
 
    gallery_plugin_url="<?php echo BP_GALLERY_PLUGIN_URL;?>";
    gallery_debaug_mode="<?php echo bp_gallery_is_debug_mode();?>";

</script>
<?php
do_action( "gallery_settings_js_loaded" );//load to override the above js values
}
function bp_gallery_is_debug_mode(){

    return bool_from_yn(get_site_option( "gallery_debug_mode", "n" ) );//by default no
}
function bp_gallery_is_flash_uploader_enabled(){

    return bool_from_yn(get_site_option( "gallery_enable_flash_uploader","y"));//by default yes
}


function bp_gallery_get_localizable_js_vars(){
    return array("delete_media_confirm"=>__("Are You sure? You will lose all the comments and the media permanently!","bp-gallery"),
                 "delete_gallery_confirm_message"=>__("Are You sure? You will lose all the media/comments!","bp-gallery")

    );
}


//find the url of the gallery template

function bp_gallery_get_template_url(){
    $theme_dir= get_template_directory();
    $stylesheet_dir= get_stylesheet_directory();
    $location ='';
    if(file_exists($stylesheet_dir .'/gallery/'))
            $location = get_stylesheet_directory_uri ().'/gallery';
    elseif(file_exists($stylesheet_dir.'/gallery-theme-compat/gallery/'))
            $location = get_stylesheet_directory_uri () .'/gallery-theme-compat/gallery';
    elseif(file_exists($theme_dir .'/gallery/'))
           $location = get_template_directory_uri ().'/gallery';
    elseif(file_exists($stylesheet_dir.'gallery-theme-compat/gallery/'))
            $location = get_stylesheet_directory_uri () .'gallery-theme-compat/gallery';
    
    //if we don't have a location yet, load it from bp-gallery plugin dir
    if( !$location )
        $location = BP_GALLERY_PLUGIN_URL.'gallery-theme-compat/gallery';
   
   return $location;
    
}

function bp_gallery_get_template_dir(){
   
    $located_template = locate_template( array('gallery'), false);
    $located_compat = locate_template( array('gallery-theme-compat/gallery'), false);
    
//the gallery is present in the theme
    if( $located_template )
        return $located_template;
    //if we are here, the compat is present

    if( !$located_compat )
        $located_compat = BP_GALLERY_PLUGIN_DIR . 'gallery-theme-compat/gallery';

    return $located_compat;

    
}


//in multisite, the functions.php should be loaded from the root blogs theme directory


