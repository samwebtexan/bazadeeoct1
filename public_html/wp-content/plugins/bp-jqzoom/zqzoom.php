<?php
/*
  Plugin Name: jqZoom Plugin for Bp Gallery
  Description: Show the sitewide public Photos using JqZoom
  Author:Brajesh Singh
  version:1.0.2
  Other:jQZoom is a free plugin developed by http://andreaslagerkvist.com/, so Thank goes to them. Please visit here for more details http://andreaslagerkvist.com/jquery/image-zoom
 */

define( 'ZQZOOM_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); //WITHOUT ANY TRAILING SLASH..MIND IT

add_action( 'wp_print_scripts', 'jqzoom_load_script' );
add_action( 'wp_print_styles', 'jqzoom_load_styles' );

function jqzoom_load_script() {

    wp_enqueue_script( 'jqzoom', ZQZOOM_PLUGIN_URL . 'js/jquery.imageZoom.js', array( 'jquery' ) );
}

function jqzoom_load_styles() {
    wp_enqueue_style( 'jqzoomcss', ZQZOOM_PLUGIN_URL . 'css/jquery.imageZoom.css' );
}

//sidebar widget

class JqZoom_Widget extends WP_Widget {

    function __construct() {

        parent::__construct(false, $name = __('Sitewide Photo Widget', 'jqzoom'));
    }

    function widget($args, $instance) {
        extract($args);
        if (!class_exists("BP_Gallery_Media"))
            return;
        echo $before_widget;
        echo $before_title
        . $instance['title']
        . $after_title;
        echo "<ul id='sitewide-zoomable-images'>";
        $images = BP_Gallery_Media::get_all(array('public'), "photo", null, null, $instance['count'], 1);
        $img_ids = $images['media'];
        foreach ($img_ids as $img) {
            $media = bp_gallery_get_media($img);
            echo "<a href='" . bp_get_media_full_src($media) . "'><img src='" . bp_get_media_thumb_src($media) . "' title='" . bp_get_media_title($media) . "' /></a>";
        }

        echo "</ul>";

        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['count'] = absint($new_instance['count']); //how many galleries
        return $instance;
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => __('Recent Sitewide Photos', 'jqzoom'), 'count' => 5, 'show_thumb' => true));
        $title = strip_tags($instance['title']);
        $count = absint($instance['count']);
        $show_thumb = $instance['show_thumb'];
        ?>
        <p><label for="bpgallery-widget-sitewide-photos-title"><?php _e('Title:', 'bp-gallery'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
        <p>
            <label for="bpgallery-widget-sitewide-photos-count"><?php _e('How Many', 'bp-gallery'); ?>
                <input type="text" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" class="widefat" value="<?php echo esc_attr($count); ?>" />
            </label>
        </p>

        <?php
    }

}

//registering widget and activating

function jqzoom_register_widgets() {
    add_action('widgets_init', create_function('', 'return register_widget("JqZoom_Widget");'));
    //add_action('widgets_init', create_function('', 'return register_widget("BP_Gallery_Sitewide_Media_Widget");') );
}

add_action('bp_loaded', 'jqzoom_register_widgets');

add_action("wp_footer", "jqzoom_script_trigger", 200);

function jqzoom_script_trigger() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {

            //trigger
            jQuery('ul#sitewide-zoomable-images a').imageZoom({hideClicked:false})

        });
    </script>
<?php }

