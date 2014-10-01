<?php
/**
 * Plugin Name: BuddyPress Private Message Rate Limiter
 * Plugin URI: http://buddydev.com/plugins/bp-private-message-rate-limiter/
 * Author: Brajesh Singh
 * Author URI: http://buddydev.com/members/sbrajesh/
 * Version: 1.0.1
 * Description: Limit the no. of private messages users can send 
 */
class BP_Rate_Limit_Private_Message_Helper{
    
    
    private static $instance;
    
    private function __construct(){
       
        
         add_filter( 'bp_get_send_message_button', array( $this, 'hide_send_message_button' ) );
        //remove ajx based activity post/comment
        add_filter( 'init', array( $this, 'remove_ajax_filter' ) );
  
        //send message reply hook
        add_action('wp_ajax_messages_send_reply', array($this, 'ajax_messages_send_reply'));
        
        //admin settings
         add_action( 'bp_admin_init', array( $this, 'register_settings' ), 20 );
        
         //load text domain
        add_action ( 'bp_loaded', array($this,'load_textdomain'), 2 );
        
        add_action('bp_messages_setup_nav', array( $this, 'change_compose_callback' ));
        
    }
    
    /**
     * Singleton instance
     * @return BP_Rate_Limit_Private_Message_Helper
     */
    public static function get_instance(){
        if( !isset( self::$instance ) )
                self::$instance = new self();
        return self::$instance;
    }
        /**
     * Load plugin textdomain for translation
     */
    function load_textdomain(){
         $locale = apply_filters( 'bp-pm-rate-limit_get_locale', get_locale() );
        
      
	// if load .mo file
	if ( !empty( $locale ) ) {
		$mofile_default = sprintf( '%slanguages/%s.mo', plugin_dir_path( __FILE__ ), $locale );
              
		$mofile = apply_filters( 'bp-pm-rate-limit_load_textdomain_mofile', $mofile_default );
		
                if ( is_readable( $mofile ) ) {
                    // make sure file exists, and load it
                    load_textdomain( 'bp-pm-rate-limit', $mofile );
		}
	}
       
    }
    
    function remove_ajax_filter(){
        if( has_action( 'messages_send_reply', 'bp_dtheme_ajax_messages_send_reply' ) )
            remove_action( 'messages_send_reply', 'bp_dtheme_ajax_messages_send_reply' );
        if(has_action( 'messages_send_reply', 'bp_legacy_theme_ajax_messages_send_reply' ) )
            remove_action( 'messages_send_reply', 'bp_legacy_theme_ajax_messages_send_reply' );
        
    }
    
    
    /**
     * Changes the callback used for compose screen
     * @global type $bp
     */
    function change_compose_callback(){
        
        global $bp;
        //compose screen callback name
        $callback = $bp->bp_options_nav['messages']['compose']['screen_function'];
       
        if( has_action( 'bp_screens', $callback ) ){
            
           remove_action( 'bp_screens', $callback, 3 );
           //add our own callback to show the compose screen          
           add_action( 'bp_screens', array( $this, 'screen_compose' ), 3 );
            
            //let usstore our callback method in options nav in case someone needs it in future
            
            $bp->bp_options_nav['messages']['compose']['screen_function'] = array($this,'screen_compose');
        }
        
    }
   /**
    * How many messages a user can send currently
    * @return int no. of remaining messages
    */
   function get_remaining_count( $user_id = false ){

       $sent_messages = self::get_sent_messages_count( $user_id );
       $allowed = self::get_limit( $user_id );

       return intval( $allowed - $sent_messages );

   }
 
    function  get_sent_messages_count($user_id =  false){
        global $bp;
        if( !$user_id )
            $user_id = get_current_user_id ();

        $type = self::get_message_type();

        //thread or all
        //in case of thread, count unique thread only


        global $wpdb;

        //find all the unique threads in last n minutes
        //find all threads before last na minute
        //find all threads in n minute which were not before n minute

        $duration = self::get_duration();

        $query_new_ids = $wpdb->prepare("SELECT DISTINCT thread_id FROM {$bp->messages->table_name_messages} where sender_id = %d AND DATE_ADD(date_sent, INTERVAL %d MINUTE ) >= UTC_TIMESTAMP()",$user_id,$duration);
        $query_old_ids = $wpdb->prepare("SELECT DISTINCT thread_id FROM {$bp->messages->table_name_messages} where sender_id = %d AND DATE_ADD(date_sent, INTERVAL %d MINUTE ) < UTC_TIMESTAMP()",$user_id,$duration);

        if( $type == 'thread' ){
        //$query_new_ids =$wpdb->prepare("SELECT DISTINCT thread_id FROM {$bp->messages->table_name_messages} where sender_id = %d AND DATE_ADD(date_sent, INTERVAL %d MINUTE ) >= UTC_TIMESTAMP()",$user_id,$duration);
        //$query_old_ids = $wpdb->prepare("SELECT DISTINCT thread_id FROM {$bp->messages->table_name_messages} where sender_id = %d AND DATE_ADD(date_sent, INTERVAL %d MINUTE ) < UTC_TIMESTAMP()",$user_id,$duration);


        $query_thread_ids = $query_new_ids .' AND thread_id NOT IN  ('.$query_old_ids.')'; 

        //find all new conversation threads between users
        $query = "SELECT COUNT(DISTINCT mr.user_id) FROM {$bp->messages->table_name_recipients} mr WHERE thread_id IN ($query_thread_ids)";

        $count= $wpdb->get_var($query);

        }else{
        //just find all messages sent during that time

        //1. for old and existing thread, find      

        $query = $wpdb->prepare("SELECT COUNT(m.id) FROM {$bp->messages->table_name_messages} m WHERE sender_id = %d AND DATE_ADD(date_sent, INTERVAL %d MINUTE ) >= UTC_TIMESTAMP()",$user_id,$duration);
       //problem, it ignores the thread count

       //$query = "SELECT COUNT(DISTINCT mr.id) FROM {$bp->messages->table_name_recipients} mr WHERE thread_id in ( SELECT DISTINCT thread_id FROM {$bp->messages->table_name_messages} where sender_id = %d AND DATE_ADD(date_sent, INTERVAL %d MINUTE ) >= UTC_TIMESTAMP())";
       //echo $wpdb->prepare($query, $user_id,self::get_duration());
        $count= $wpdb->get_var($query);
        }


        return (int) $count; 
    }
 

    
    
    
    //can the logged in user send a new message(Used for new message threads)
    public static function can_send( $user_id = false ){
       
        if( is_super_admin() )
            return true;//do not stop super admin
      
        if( !$user_id )
            $user_id = get_current_user_id();
        
         //do not limit if not specified
        if( self::get_limit( $user_id ) == 0 )
            return true;
         
        if( self::get_remaining_count() > 0 )
          return true;
        
        //do not allow if the limit has been reached
        return false;
    }
    
 
   function can_send_reply( $user_id = false ){
       
       if( self::get_message_type( $user_id ) == 'thread' )
           return true;
       
        if( self::get_limit( $user_id ) == 0 )
            return true;
       
       $type = self::get_message_type( $user_id );
       //if the restriction is only for new threads, let us not worry here
       if( $type == 'thread' )
           return true;
       
       //othewise, we need to check for total no. of messages sent
       
      if( self::get_remaining_count() > 0 )
          return true;
      
      return false;
       
       
   }

    /**
     * Compose Screen
     * @global type $bp
     * @return type
     */
    function screen_compose() {
        
        global $bp;

        if ( bp_action_variables() ) {
            bp_do_404();
            return;
        }

        // Remove any saved message data from a previous session.
        messages_remove_callback_values();

        // Check if the message form has been submitted
        if ( isset( $_POST['send'] ) ) {

            // Check the nonce
            check_admin_referer( 'messages_send_message' );

            // Check we have what we need
            if ( empty( $_POST['subject'] ) || empty( $_POST['content'] ) ) {
                bp_core_add_message( __( 'There was an error sending that message, please try again', 'bp-pm-rate-limit' ), 'error' );
            } else {
                // If this is a notice, send it
                if ( isset( $_POST['send-notice'] ) ) {
                    if ( messages_send_notice( $_POST['subject'], $_POST['content'] ) ) {
                        bp_core_add_message( __( 'Notice sent successfully!', 'bp-pm-rate-limit' ) );
                        bp_core_redirect( bp_loggedin_user_domain() . $bp->messages->slug . '/inbox/' );
                    } else {
                        bp_core_add_message( __( 'There was an error sending that notice, please try again', 'bp-pm-rate-limit' ), 'error' );
                    }
                } else {
                    // Filter recipients into the format we need - array( 'username/userid', 'username/userid' )
                    $autocomplete_recipients = explode( ',', $_POST['send-to-input'] );
                    $typed_recipients        = explode( ' ', $_POST['send_to_usernames'] );
                    $recipients              = array_merge( (array) $autocomplete_recipients, (array) $typed_recipients );
                    $recipients              = apply_filters( 'bp_messages_recipients', $recipients );
                    
                    //check if the user is allowed to send a message or not
                    //strategy
                    //1. find total number of messages   sent
                    //total number of remaining messages
                    //is message remaining
                    //if yes, we will need to check if this is a thread and the user is sending to multipl erecipients at once
                    //if yes, check if the count is less than the no. of receipients
                    //if no, show error?
                    $message= self::get_message();
                    $user_id = get_current_user_id();
                    
                    if( ! self::can_send($user_id) ){
                        //ok, limit is over, relax
                        bp_core_add_message( $message, 'error' );
                         self::load_template();
                         exit(0);
                        bp_core_redirect( bp_loggedin_user_domain() . $bp->messages->slug . '/compose/');
                        
                    }
                    
                    //if we are here, seems like the limit is not crossed
                    $remaining_count = self::get_remaining_count($user_id);
                    $recipients = array_filter($recipients);
                 
                  
                    if( $remaining_count < count( $recipients ) ){
                        
                        bp_core_add_message( $message, 'error' );
                        self::load_template();
                         exit(0);
                        bp_core_redirect( bp_loggedin_user_domain() . $bp->messages->slug . '/compose/');
                        
                    }
                    
                    //otherwise all is good, let him/her proceed
                        
                    
                    // Send the message
                    if ( $thread_id = messages_new_message( array( 'recipients' => $recipients, 'subject' => $_POST['subject'], 'content' => $_POST['content'] ) ) ) {
                        bp_core_add_message( __( 'Message sent successfully!', 'bp-pm-rate-limit' ) );
                        bp_core_redirect( bp_loggedin_user_domain() . $bp->messages->slug . '/view/' . $thread_id . '/' );
                    } else {
                        bp_core_add_message( __( 'There was an error sending that message, please try again', 'bp-pm-rate-limit' ), 'error' );
                    }
                }
            }
        }

       self::load_template();
    }
    
    //ajax handler
    
    function ajax_messages_send_reply() {
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	check_ajax_referer( 'messages_send_message' );
    $message =self::get_message();
    //are we limiting replies too?
    if( !self::can_send_reply(get_current_user_id())){
        
        echo "-1<div id='message' class='error'><p>{$message}</p></div>";
        exit(0);
        
    }
    
    
	$result = messages_new_message( array( 'thread_id' => (int) $_REQUEST['thread_id'], 'content' => $_REQUEST['content'] ) );

	if ( $result ) { ?>
		<div class="message-box new-message">
			<div class="message-metadata">
				<?php do_action( 'bp_before_message_meta' ); ?>
				<?php echo bp_loggedin_user_avatar( 'type=thumb&width=30&height=30' ); ?>

				<strong><a href="<?php echo bp_loggedin_user_domain(); ?>"><?php bp_loggedin_user_fullname(); ?></a> <span class="activity"><?php printf( __( 'Sent %s', 'bp-pm-rate-limit' ), bp_core_time_since( bp_core_current_time() ) ); ?></span></strong>

				<?php do_action( 'bp_after_message_meta' ); ?>
			</div>

			<?php do_action( 'bp_before_message_content' ); ?>

			<div class="message-content">
				<?php echo stripslashes( apply_filters( 'bp_get_the_thread_message_content', $_REQUEST['content'] ) ); ?>
			</div>

			<?php do_action( 'bp_after_message_content' ); ?>

			<div class="clear"></div>
		</div>
	<?php
	} else {
		echo "-1<div id='message' class='error'><p>" . __( 'There was a problem sending that reply. Please try again.', 'bp-pm-rate-limit' ) . '</p></div>';
	}

	exit;
}
  


 function load_template(){
         do_action( 'messages_screen_compose' );

        bp_core_load_template( apply_filters( 'messages_template_compose', 'members/single/home' ) );
    }
   
  /**
   * hide button to send private message if the user is not allowed to send more message
   */
    
 function hide_send_message_button($btn){
     
    if(!self::should_hide_button())
        return $btn;
     
     if(is_super_admin())
         return $btn;
     
     if(self::can_send(get_current_user_id()))
           return $btn;
     
     //otherwise do not show the button
    return '';//nothing oops
     
 }

 
 
  

    /*helper*/
    
     //Settings fetch
 
    public static function get_limit( $user_id = false ){
        $allowed_count = absint( get_option( 'bp_rate_limit_pm_count', 5 ) );//20 default request count   
        return apply_filters( 'bp_rate_limit_pm_count', $allowed_count, $user_id );
    }
    public static function get_duration( $user_id = false ){
        $allowed_time = absint( bp_get_option( 'bp_rate_limit_pm_throttle_duration', 5 ) );//5 mintutes    
        return apply_filters( 'bp_rate_limit_pm_throttle_duration', $allowed_time, $user_id );
    }
    
    public static function get_message_type( $user_id=false ){
        $type = bp_get_option( 'bp_rate_limit_pm_type', 'thread' );//Limit all activity
        return apply_filters( 'bp_rate_limit_pm_type', $type, $user_id );
    }
    
    public static function get_message(){
        return bp_get_option( 'bp_rate_limit_pm_message', __( 'You are going too quick. Please be tender!', 'bp-activity-rate-limit' ) );
    }
   
    public static function should_hide_button(){
        return bp_get_option( 'bp_rate_limit_pm_hide_button',1 );
    }
   
    /** register settings for admin*/
    function register_settings(){
            // Add the ajax Registration settings section
            add_settings_section( 'bp_limit_private_message_rate', __( 'BP Rate Limit Private Message', 'bp-pm-rate-limit' ), array( $this, 'reg_section' ),'buddypress' );
            // Allow loading form via jax or nt?
            add_settings_field( 'bp_rate_limit_pm_count', __( 'How many Messages a User can send?', 'bp-pm-rate-limit' ), array( $this, 'settings_field_count' ), 'buddypress', 'bp_limit_private_message_rate' );
            add_settings_field( 'bp_rate_limit_pm_throttle_duration', __( 'During the time?', 'bp-pm-rate-limit' ), array( $this, 'settings_field_throttle_time' ), 'buddypress', 'bp_limit_private_message_rate' );
            add_settings_field( 'bp_rate_limit_pm_type', __( 'Criteria?',   'bp-pm-rate-limit' ),  array( $this, 'settings_field_message_type'),   'buddypress', 'bp_limit_private_message_rate' );
            add_settings_field( 'bp_rate_limit_pm_hide_button', __( 'HIde Message Button?',   'bp-pm-rate-limit' ),  array( $this, 'settings_field_hide_button'),   'buddypress', 'bp_limit_private_message_rate' );
            
            add_settings_field( 'bp_rate_limit_pm_message', __( 'What Message you want to display if the user reaches the limit?', 'bp-pm-rate-limit' ), array( $this, 'settings_field_message' ),   'buddypress', 'bp_limit_private_message_rate' );
            register_setting  ( 'buddypress', 'bp_rate_limit_pm_count','intval' );
            register_setting  ( 'buddypress', 'bp_rate_limit_pm_throttle_duration', 'intval');
            register_setting  ( 'buddypress', 'bp_rate_limit_pm_type');
            register_setting  ( 'buddypress', 'bp_rate_limit_pm_hide_button');
            register_setting  ( 'buddypress', 'bp_rate_limit_pm_message');
    }  
    
    function reg_section(){
        
    }
    
    function settings_field_count(){
            $val=self::get_limit();?>
            <input id="bp_rate_limit_pm_count" name="bp_rate_limit_pm_count" type="text" value="<?php echo $val;?>"  />
    <?php
    }
   
   function settings_field_throttle_time(){
        $val=self::get_duration(); ?>

         <label><input id='bp_rate_limit_pm_throttle_duration' name='bp_rate_limit_pm_throttle_duration' type='text' value='<?php echo $val;?>'  /> <?php _e('Minutes','bp-pm-rate-limit');?></label>     
   <?php    
       
   }
   function settings_field_message_type(){
        $val=self::get_message_type(); ?>
         <label><input  name='bp_rate_limit_pm_type' type='radio' value='thread' <?php echo checked('thread',$val);?> /> <?php _e('Only New Messages','bp-pm-rate-limit');?></label>     
        
         <label><input  name='bp_rate_limit_pm_type' type='radio' value='all' <?php echo checked('all',$val);?> /> <?php _e('New message( including replies )','bp-pm-rate-limit');?></label>     
           <?php    
       
   }
   
   function settings_field_hide_button(){
       
       $val =  self::should_hide_button();
       ?>
        <label><input  name='bp_rate_limit_pm_hide_button' type='checkbox' value='1' <?php echo checked(1,$val);?> /> <?php _e('Yes, Hide Private Message Button when limit is reached.','bp-pm-rate-limit');?></label>     
        
       <?php 
   }
   function settings_field_message(){
        $val=self::get_message() ?>

         <label>
             <textarea id='bp_rate_limit_pm_message' name='bp_rate_limit_pm_message' rows="5" cols="80" ><?php echo esc_textarea( $val);?></textarea></label>     
   <?php    
       
   }
   
}

BP_Rate_Limit_Private_Message_Helper::get_instance();
