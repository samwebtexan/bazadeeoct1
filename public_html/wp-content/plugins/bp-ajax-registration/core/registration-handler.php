<?php

class BP_Ajax_Registration_Handler {
    
    private static $instance;      
    
    private function __construct() {

        add_action( 'wp_ajax_nopriv_bpajax_register', array( $this, 'register' ) );
        // add_action( 'wp_ajax_bpajax_register', array( $this, 'register' ) );//backward compatbility for v 1.5.x
        add_action( 'wp_ajax_nopriv_bpajax_get_register_form', array( $this, 'show_form' ) );
        // add_action( 'wp_ajax_bpajax_get_register_form', array( $this, 'show_form' ) );//backward compatibility for v 1.5.x
        
        
        //remove buddypress notifications
        add_action( 'bp_loaded', array( $this, 'remove_bp_filters' ), 100 );

        
       

        //activate the account instantly
        add_action( 'bp_core_signup_user', array( $this, 'activate' ), 10, 5 );
        
        add_filter( 'wpmu_signup_user_notification', array( $this, 'activate_user_for_wpms' ), 10, 4 );
        
        add_filter( 'wpmu_signup_blog_notification', array( $this, 'activate_on_blog_signup' ), 100, 7 );//array($this, 'activate_on_blog_signup'), 10, 7);

        
        //disable the hooks that normaly controls various emails
        
        $this->disable_hooks();



    }
    
    public static function get_instance() {

        if( ! isset ( self::$instance ) ) {
         
            self::$instance = new self();
        }    
    
        return self::$instance;
    }
 
    public function disable_hooks() {
        
        //stop notifications
        add_filter( 'bp_core_signup_send_activation_key', '__return_false', 110 ); //5 args,no need to send the clear text password when blog is activated

        //Prevent the notification On new Account registration
        // Stop User signup Mu notification
        add_filter( 'wpmu_signup_user_notification', '__return_false', 110 );//array($this, 'activate_user_for_wpms'), 10, 4);
        //stop activation email for account with blog
        add_filter( 'wpmu_signup_blog_notification', '__return_false', 100 );//array($this, 'activate_on_blog_signup'), 10, 7);
        
        //on account activation
        add_filter( 'wpmu_welcome_notification', '__return_false', 110 ); //5 args,no need to send the clear text password when blog is activated
        add_filter( 'wpmu_welcome_user_notification', '__return_false', 110 ); //5 args,no need to send the clear text password when blog is activateds
        
    }


    public function  register() {
        global $bp;

        if( !defined( 'DOING_AJAX' ) )
            define( 'DOING_AJAX', true );

           if ( !isset( $bp->signup ) ) {
                $bp->signup = new stdClass;
        }

        $bp->signup->step = 'request-details';
        
        if ( !bp_get_signup_allowed() ) {
        
           $bp->signup->step = 'registration-disabled';
       
           
        }elseif ( isset( $_POST['signup_submit'] ) ) {
         // If the signup page is submitted, validate and save
         // Check the nonce
            check_admin_referer( 'bp_new_signup' );
            
                   // Check the base account details for problems
            $account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );
           
           // If there are errors with account details, set them for display
            if ( !empty( $account_details['errors']->errors['user_name'] ) )
               $bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];

            if ( !empty( $account_details['errors']->errors['user_email'] ) )
               $bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];

            // Check that both password fields are filled in
            if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) )
               $bp->signup->errors['signup_password'] = __( 'Please make sure you enter your password twice', 'bpajaxr' );

            // Check that the passwords match
            if ( ( !empty( $_POST['signup_password'] ) && !empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] )
               $bp->signup->errors['signup_password'] = __( 'The passwords you entered do not match.', 'bpajaxr' );

            $bp->signup->username = $_POST['signup_username'];
            $bp->signup->email = $_POST['signup_email'];

            // Now we've checked account details, we can check profile information
            if ( bp_is_active( 'xprofile' ) ) {

               // Make sure hidden field is passed and populated
               if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {

                   // Let's compact any profile field info into an array
                   $profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

                   // Loop through the posted fields formatting any datebox values then validate the field
                   foreach ( (array) $profile_field_ids as $field_id ) {
                       if ( !isset( $_POST['field_' . $field_id] ) ) {
                           if ( isset( $_POST['field_' . $field_id . '_day'] ) )
                               $_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] ) );
                       }

                       // Create errors for required fields without values
                       if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) )
                           $bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'bpajaxr' );
                   }
                  
               // This situation doesn't naturally occur so bounce to website root
               } else {

               }
           }

           // Finally, let's check the blog details, if the user wants a blog and blog creation is enabled
           if ( isset( $_POST['signup_with_blog'] ) &&!empty( $_POST['signup_with_blog'] ) ) {
               $active_signup = $bp->site_options['registration'];

               if ( 'blog' == $active_signup || 'all' == $active_signup ) {
                   $blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

                   // If there are errors with blog details, set them for display
                   if ( !empty( $blog_details['errors']->errors['blogname'] ) )
                       $bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];

                   if ( !empty( $blog_details['errors']->errors['blog_title'] ) )
                       $bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
               }
           }

           do_action( 'bp_signup_validate' );

           // Add any errors to the action for the field in the template for display.
           if ( !empty( $bp->signup->errors ) ) {
             
                if(!empty($bp->singup->errors['profile_issue']))
                    bp_core_add_message ($bp->singup->errors['profile_issue'],'error');

                foreach ( (array)$bp->signup->errors as $fieldname => $error_message )
                    add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo apply_filters(\'bp_members_signup_error_message\', "<div class=\"error\">' . $error_message . '</div>" );' ) );
           
                
           } else {
             
                $bp->signup->step = 'save-details';

                // No errors! Let's register those deets.
                $active_signup = !empty( $bp->site_options['registration'] ) ? $bp->site_options['registration'] : '';

                if ( 'none' != $active_signup ) {

                   // Let's compact any profile field info into usermeta
                   $profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

                   // Loop through the posted fields formatting any datebox values then add to usermeta
                    foreach ( (array) $profile_field_ids as $field_id ) {
                        if ( !isset( $_POST['field_' . $field_id] ) ) {
                            
                            if ( isset( $_POST['field_' . $field_id . '_day'] ) )
                                $_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] ) );
                        }

                        if ( !empty( $_POST['field_' . $field_id] ) )
                            $usermeta['field_' . $field_id] = $_POST['field_' . $field_id];
                   
                        
                    }

                    // Store the profile field ID's in usermeta
                    $usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];

                    // Hash and store the password
                    $usermeta['password'] = wp_hash_password( $_POST['signup_password'] );
                  
                    // If the user decided to create a blog, save those details to usermeta
                    if ( 'blog' == $active_signup || 'all' == $active_signup )
                        $usermeta['public'] = ( isset( $_POST['signup_blog_privacy'] ) && 'public' == $_POST['signup_blog_privacy'] ) ? true : false;

                    $usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );
                                   
                    $bp->signup->step = 'completed-confirmation';//move up
                   
                    // Finally, sign up the user and/or blog
                    if ( isset( $_POST['signup_with_blog'] ) && is_multisite() )
                        bp_core_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
                    else
                       bp_core_signup_user( $_POST['signup_username'], $_POST['signup_password'], $_POST['signup_email'], $usermeta );

               }

               do_action( 'bp_complete_signup' );
           }
           
       } 



        $data['data'] = self::get_form();   
          // self::get_form();

        $data['redirect'] = 0;

        if( bp_account_was_activated() )
           $data['redirect'] = 1;

        echo json_encode( $data );
        exit( 0 );
   }
    
   /**
     * Update xprofile fields from the signup meta data
     * 
     * @param type $user_id
     * @param type $signup
     */
    public static function update_profile_fields( $user_id, $signup ){
        
         /* Set any profile data */
        if (function_exists('xprofile_set_field_data')) {
            if (!empty($signup['meta']['profile_field_ids'])) {
                $profile_field_ids = explode(',', $signup['meta']['profile_field_ids']);

                foreach ($profile_field_ids as $field_id) {
                    $current_field = $signup['meta']["field_{$field_id}"];

                    if (!empty($current_field))
                        xprofile_set_field_data($field_id, $user_id, $current_field);
                }
            }
        }

    }
    /**
     * make the User logged in
     * @global type $bp
     * @param type $user_id
     * @param type $user_password
     * @return type
     */
    public function login( $user_id, $user_password ) {
        global $bp;
       
        bp_core_add_message( __( 'Your account is now active!', 'bpajaxr' ) );
        
        $bp->activation_complete = true;

        if( is_multisite() ){
            //in case of multisite the auto login only works if the user is member of current blog 
            add_user_to_blog( get_current_blog_id(), $user_id, get_option( 'bp_ajaxr_user_role', 'subscriber' ) );
        
        }
        
        $ud = get_userdata( $user_id );
      //if the user is not valid
        if( !$ud ) {
            bp_core_add_message( __('There was a problem logging you in. Please login with your password!', 'bpajaxr' ) );
            return ;
            
        }    
        
        $creds = array( 'user_login' => $ud->user_login, 'user_password' => $user_password );
   
    
        $user = wp_signon( $creds );
    
        if ( ! is_wp_error( $user ) )//if the signup was success full.redirect to the membership page
               return $user->ID;

        
        
    }   
    /**
     * Activate a User account For Standard WordPress( Not Multisite )
     * It is used when a user does not signup for a blog(in case of multisite) and all cases in normal WordPress
     * used
     * @param type $user_id
     * @param type $user_login
     * @param type $user_password
     * @param type $user_email
     * @param type $usermeta
     * @return type
     */
    public function activate( $user_id, $user_login, $user_password, $user_email, $usermeta ) {
    
        global $bp, $wpdb;
        //if not fdoing via ajax, let us not handle the registration or activation
        if( ! defined( 'DOING_AJAX' ) )
            return $user_id;
        
        if( is_multisite() )
            return $user_id;
        
        
        $signups = BP_Signup::get( array('user_login'=> $user_login ) );

      
        $signups = $signups['signups'];
       
        
        if( !$signups ) {
         
            bp_core_signup_send_validation_email( $user_id, $user_email, bp_get_user_meta( $user_id, 'activation_key', true  ) );
            return false;
            
        }
        
        //if we are here, just popout the array
        $signup = array_pop( $signups );
         
       		// password is hashed again in wp_insert_user
		$password = wp_generate_password( 12, false );

		$user_id = username_exists( $signup->user_login );

        $key = $signup->activation_key;
        
        if( !$key )
            $key = bp_get_user_meta( $user_id, 'activation_key', true  );
        
		// Create the user
		if ( ! $user_id ) {//this should almost never happen
			$user_id = wp_create_user( $signup->user_login, $password, $signup->user_email );

		// If a user ID is found, this may be a legacy signup, or one
		// created locally for backward compatibility. Process it.
		} elseif ( $key == wp_hash( $user_id ) ) {
			// Change the user's status so they become active
			if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 0 WHERE ID = %d", $user_id ) ) ) {
				bp_core_add_message(  __( 'Could not create your account. Please try again later!', 'bpajaxr' ), 'error' );
                return;
			}

			bp_delete_user_meta( $user_id, 'activation_key' );

			$member = get_userdata( $user_id );
			$member->set_role( get_option( 'bp_ajaxr_user_role', 'subscriber' ) );

			$user_already_created = true;

		} else {
			$user_already_exists = true;
		}

		if ( ! $user_id ) {
			bp_core_add_message(  __( 'Could not create your account. Please try again later!', 'bpajaxr' ), 'error' );
		}

		// Fetch the signup so we have the data later on
		$signups = BP_Signup::get( array(
			'activation_key' => $key,
		) );

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Activate the signup
		BP_Signup::validate( $key );

		if ( isset( $user_already_exists ) ) {
			bp_core_add_message(  __( 'Account already activated!', 'bpajaxr' ), 'error' );
		}

		// Set up data to pass to the legacy filter
		$user = array(
			'user_id'  => $user_id,
			'password' => $signup->meta['password'],
			'meta'     => $signup->meta,
		);

		// Notify the site admin of a new user registration
		wp_new_user_notification( $user_id );

		
        wp_cache_delete( 'bp_total_member_count', 'bp' );

       /* Add a last active entry */
       bp_update_user_last_activity( $user_id );

       do_action( 'bp_core_activated_user', $user_id, $key, $user );
		
          


        bp_core_add_message(__('Your account is now active!'));

        $bp->activation_complete = true;
        xprofile_sync_wp_profile();
        //$ud = get_userdata($signup['user_id']);

       return self::login( $user_id, $user_password );

       
    } 
        /**
     * Activates User account on Multisite based on the given key
     * 
     * 
     */
    public static function ms_activate_account( $key ){
        
        //if doing ajax, return
        if( !defined( 'DOING_AJAX' ) )
            return false;
        
        //mimic bp activation
        $bp = buddypress();
        
        $signup = apply_filters( 'bp_core_activate_account', wpmu_activate_signup( $key ) );

        /* If there was errors, add a message and redirect */
        if ( isset( $signup->errors ) && $signup->errors ) {
            
            bp_core_add_message( __( 'There was an error activating your account, please try again.', 'buddypress' ), 'error' );
            
            //show error, exit
            return;
            //send the activation mail in this case
        }

        $user_id = $signup['user_id'];
        //should we pass password as a param instead of the dependency here?
        
        $pass = $_POST['signup_password'];

        $ud = get_userdata( $user_id );

        $data = array( 'user_login' => $ud->user_login, 'user_email' => $ud->user_email, 'user_pass' => $pass, 'ID' => $user_id, 'display_name' => bp_core_get_user_displayname( $user_id ) );
        //update password
        if ( is_multisite() )
            wp_update_user( $data );

       self::update_profile_fields( $user_id, $signup );

        do_action( 'bp_core_activated_user', $user_id, $key, $signup );     //let bp handle the new user registerd activity
        //do_action( 'bp_core_account_activated', &$signup, $_GET['key'] );
        
        bp_core_add_message( __( 'Your account is now active!' ) );

        $bp->activation_complete = true;

        self::login( $ud->ID, $pass );
        
    }
    //this is just a copy of bp_core_active_user, I wanted some control over it, so kept it as a member function

        
    public function activate_user_for_wpms( $user, $user_email, $key, $meta ) {
    
        return self::ms_activate_account( $key );
   
    }      
 

    public function activate_on_blog_signup( $domain, $path, $title, $user, $user_email, $key, $meta ) {

        if( ! defined( 'DOING_AJAX' ) )
            return $domain;

       return self::ms_activate_account( $key );
    }





    public static function get_form() {
        global $bp;
        if ( !isset( $bp->signup ) ) {
            $bp->signup = new stdClass;
        }

        if( empty( $bp->signup->step ) )
           $bp->signup->step = 'request-details';


        //get the form in buffer
        ob_start();

        $located = locate_template( array( 'ajax-register-form.php' ), false );

        if( $located )
            locate_template( array( 'ajax-register-form.php' ), true );
        else
            include_once( BP_AJAX_REGISTER_PATH . 'ajax-register-form.php' );

        $page_data = ob_get_clean();

       return  $page_data ;//append extra div with id
    }

    public static function show_form() {
        
        echo '<div id ="bpajax-register-form-container">';
        echo self::get_form();
        echo '</div>';
        
        exit( 0 );
    }

    /**
     * Remove the BuddyPress attached filters for various notifications
     */
    public function remove_bp_filters() {
        
        if(  has_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification' ) ) {
            remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 ); //remove bp user notification for activating account
        }
        
        if( has_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification' ) ) { //remove bp blog notification
            remove_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 ); //remove bp blog notification
        }
        
        if( has_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification' ) ) {
            remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );
        }    
    }
   


}

//initialize
BP_Ajax_Registration_Handler::get_instance();

