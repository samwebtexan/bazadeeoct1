<?php
/*
Plugin Name: Branded Login for Buddypress
Plugin URI: http://buddydev.com/plugins/bp-branded-login/
Author: Brajesh Singh
Author URI: http://buddydev.com/members/sbrajesh
Description: Handle Login, forgot password/reset password all actions from front end
Version: 1.2.1
Date Updated: April 18, 2014
*/
/***
 * A special note for BP 1.5+, In BuddyPress 1.5 and above, we will need a page for each of the top level component page. So, we will need to use 3 pages here 
 */
//change slugs if you want
define('BL_PLUGIN_NAME','bl');//for localized text domain

if(!defined('BP_LOGIN_SLUG'))
    define('BP_LOGIN_SLUG','login');

if(!defined('BP_PASSWORD_RESET_SLUG'))
    define('BP_PASSWORD_RESET_SLUG','resetpass');

if(!defined('BP_LOGOUT_SLUG'))
    define('BP_LOGOUT_SLUG','logout');

$bp_bl_dir = str_replace( basename( __FILE__), '',plugin_basename(__FILE__));
define( 'BL_DIR_NAME',$bp_bl_dir);//the directory name of bp-randed login
define( 'BL_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define( 'BL_PLUGIN_URL',  plugin_dir_url(__FILE__));



class BPBrandedLogin{
    private static $instance;
    private function __construct(){
        add_action( 'bp_init', array( $this, 'load_textdomain' ), 2 );
        add_action( 'bp_setup_globals', array( $this,'setup_globals' ) );
        add_action( 'bp_setup_root_components', array( $this, 'setup_component' ), 2 );
        add_action( 'bp_include', array( $this, 'load_files' ) );

    }
    
    public function get_instance(){
        if( !isset ( self::$instance ) )
                self::$instance = new self();
        
        return self::$instance;
    }
   
/*
 * Localization support
 * Put your files into
 * bp-branded-login/languages/your_local.mo(e.g en_US.mo)
 */
   /*load text domain*/
    function load_textdomain() {
        $locale = apply_filters( 'bl_load_textdomain_get_locale', get_locale() );
	// if load .mo file
	if ( !empty( $locale ) ) {
		$mofile_default = sprintf( '%slanguages/%s.mo', BL_PLUGIN_DIR, $locale );
		$mofile = apply_filters( 'bl_load_textdomain_mofile', $mofile_default );
		// make sure file exists, and load it
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'bl', $mofile );
		}
	}
    }
    function load_files(){
        include_once BL_PLUGIN_DIR.'theme-compat.php';
    }
    /*setup global variables, we are claiming 3 pages here, not a very good idea but there is no solutiion in bp 1.5*/
    function setup_globals(){
        global $bp;
        $bp->login=new stdClass();
        $bp->resetpass=new stdClass();
        $bp->logout=new stdClass();
        $bp->login->slug='login';
        $bp->login->id='login';
        $bp->login->name=__('Login Page','bl');
        $bp->login->root_slug=isset($bp->pages->login->slug)?$bp->pages->login->slug:$bp->login->slug;
      
        //reset pass
        $bp->resetpass->slug='resetpass';
        $bp->resetpass->id='resetpass';
        $bp->resetpass->name=__('Password Reset Page','bl');
        $bp->resetpass->root_slug=isset($bp->pages->resetpass->slug)?$bp->pages->resetpass->slug:'resetpass';
         
        $bp->logout->slug='logout';
        $bp->logout->id='logout';
        $bp->logout->name=__('Logout Page','bl');
        $bp->logout->root_slug=isset($bp->pages->logout->slug)?$bp->pages->logout->slug:'logout';
        
    }
    
    ///regiaster login and reset as root component
    function setup_component(){
                
               // $bp->login->slug=$bp->pages->logout->slug?$bp->pages->logout->slug:BP_LOGOUT_SLUG;
        bp_core_add_root_component( 'login' );
		bp_core_add_root_component( 'resetpass' );
		bp_core_add_root_component( 'logout' );
        
        remove_filter( 'login_redirect', 'bp_core_login_redirect' );
              
	}

}

 BPBrandedLogin::get_instance();

function bp_remove_adminbar_login_signup(){
      remove_action( 'bp_adminbar_menus', 'bp_adminbar_login_menu', 2 );
}
add_action('bp_init','bp_remove_adminbar_login_signup');

//catch login/resetpass/lostpass screen
function bl_screen_handler(){
    global $bp, $current_blog;
 
//check if this is login/resetpass/logout screen or not
if ( !(bp_is_current_component($bp->login->slug)||bp_is_current_component( $bp->resetpass->slug)||bp_is_current_component( $bp->logout->slug)))
        return;//return the control back
//check if the user is logged in and current action is not logout, then redirect back
if((!bp_is_current_component($bp->logout->slug))&&is_user_logged_in())
    bp_core_redirect(bp_get_root_domain());		//redirect to home page , they should never access it man

if(bp_is_current_component( $bp->login->slug))
       bl_handle_login();//handle login
else if(bp_is_current_component ( $bp->resetpass->slug)){
    if($bp->current_action=='validate') //if validation, retrieve password
      bl_handle_reset_password();
          
    else
        bl_handle_retrieve_password();
    }
 else if(bp_is_current_component($bp->logout->slug))
          bl_logout_handle();
} 

add_action('bp_actions','bl_screen_handler',8);


//handle login
function bl_handle_login(){
    global $bp;
//if the form was submitted
    if(!empty($_POST)){
    
        $errors = new WP_Error();
        //Set a cookie now to see if they are supported by the browser.
        setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
        if ( SITECOOKIEPATH != COOKIEPATH )
            setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);

        $http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
        $secure_cookie = '';
        $interim_login = isset($_REQUEST['interim-login']);

    // If the user wants ssl but the session is not ssl, force a secure cookie.
        if ( !empty($_POST['log']) && !force_ssl_admin() ) {
                $user_name = sanitize_user($_POST['log']);
                if ( $user = get_userdatabylogin($user_name) ) {
                        if ( get_user_option('use_ssl', $user->ID) ) {
                                $secure_cookie = true;
                                force_ssl_admin(true);
                                }
                        }
                }

       if ( !empty( $_REQUEST['redirect_to'] ) ) {
            $redirect_to = $_REQUEST['redirect_to'];
                 // Redirect to https if user wants ssl
             if ( $secure_cookie && false !== strpos($redirect_to, 'wp-admin') )
                     $redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);


           } else {
                $redirect_to = bp_get_root_domain();
        }


        if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($redirect_to, 'https') ) && ( 0 === strpos($redirect_to, 'http') ) )
            $secure_cookie = false;

       $user = wp_signon('', $secure_cookie);
        $redirect_to = apply_filters('login_redirect', $redirect_to, (!empty( $_REQUEST['redirect_to'] )) ? $_REQUEST['redirect_to'] : '', $user);

        if ( !is_wp_error($user) ) {
            if ( $interim_login ) {
                    $message =  __('You have logged in successfully.','bl') ;
                    bp_core_add_message($message);
                }
        $message =  __('You have logged in successfully.','bl') ;
        wp_safe_redirect($redirect_to);
            exit();
        }
        else{
            $error=bl_handle_error($user);
            $message=bl_process_errors($error);
            bp_core_add_message($message, 'error');
        }
        }

bp_core_load_template(apply_filters('bl_login_template','/blogin/login'),true);
}

/* Just for a clean error message*/
function bl_handle_error($errors){

    $error_code=$errors->get_error_code();
  
    unset($errors->errors[$error_code]);//remove the ugly message prodecued by wp
    $message=array('incorrect_password'=>__('Your Password is incorrect. Please try again.','bl'),
                   'empty_password'=>__('Your Password is empty. Please enter correct password.','bl'),
                   'empty_username'=>__('Your Username is empty. Please enter your Username.','bl'),
                   'invalid_username'=>__('The username is Invalid!','bl')
          );
    $errors->add($error_code,$message[$error_code], 'message');

 if ( isset($_POST['testcookie']) && empty($_COOKIE[TEST_COOKIE]) )
	$errors->add('test_cookie', __('Cookies are blocked or not supported by your browser. You must <a href=\'http://www.google.com/cookies.html\'>enable cookies</a> to use this site.','bl'));
        return $errors;
}

//hnadle password retriving
function  bl_handle_retrieve_password(){
    $errors="";
    $http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
    
    if ( $http_post ) {
       
           $errors = bl_retrieve_password();
          
            if ( !is_wp_error($errors) ) 
                    bp_core_add_message($errors);
	 
        }
   
    if(is_wp_error($errors)){
            $message=bl_process_errors($errors);
            
            bp_core_add_message($message, 'error');
        }

bp_core_load_template(apply_filters('bl_resetepass_template','/blogin/resetpass'),true);
}


//reset password, generating new pass
function bl_handle_reset_password(){
//we are here only iof this is validation state
   $errors = bl_reset_password($_GET['key'], $_GET['login']);

   if ( ! is_wp_error($errors) )
		bp_core_add_message($errors);
    if(is_wp_error($errors)){
      
            $message=bl_process_errors($errors);
            bp_core_add_message($message, 'error');
            bp_core_redirect(bl_get_reset_pass_link());
     }
     else
          bp_core_redirect(bl_get_login_link());

}

//process error to generate message string, or better provide a nice message
function bl_process_errors($wp_error = '') {

if(empty($wp_error))
    return;//there is no error
		
    if ( $wp_error->get_error_code() ) {
	$errors = '';
	$messages = '';
	foreach ( $wp_error->get_error_codes() as $code ) {
		$severity = $wp_error->get_error_data($code);
		foreach ( $wp_error->get_error_messages($code) as $error ) {
			if ( 'message' == $severity )
				$messages .= '	' . $error . "\n";
			else
				$errors .= '	' . $error . "\n";
        		}
            }

           if ( !empty($errors) )
		return $errors;
            if ( !empty($messages) )
		return $messages;
    }

 } // End of process error

// helper functions, taken from wp-login.php and slighlt cleaned up
/**
 * Handles sending password retrieval email to user.
 *
 * @uses $wpdb WordPress Database object
 *
 * @return bool|WP_Error True: when finish. WP_Error on error
 */
function bl_retrieve_password() {
	global $wpdb, $current_site;

	$errors = new WP_Error();

	if ( empty( $_POST['user_login'] ) && empty( $_POST['user_email'] ) )
		$errors->add('empty_username', __('Enter a username or e-mail address.','bl'));

	if ( strpos($_POST['user_login'], '@') ) {
		$user_data = get_user_by_email(trim($_POST['user_login']));
		if ( empty($user_data) )
			$errors->add('invalid_email', __('There is no user registered with that email address.','bl'));
	} else {
		$login = trim($_POST['user_login']);
		$user_data = get_userdatabylogin($login);
	}

	do_action('lostpassword_post');

	if ( $errors->get_error_code() )
		return $errors;

	if ( !$user_data ) {
		$errors->add('invalidcombo', __('Invalid username or e-mail.','bl'));
		return $errors;
	}

	// redefining user_login ensures we return the right case in the email
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;

	do_action('retreive_password', $user_login);  // Misspelled and deprecated
	do_action('retrieve_password', $user_login);

	$allow = apply_filters('allow_password_reset', true, $user_data->ID);

	if ( ! $allow )
		return new WP_Error('no_password_reset', __('Password reset is not allowed for this user','bl'));
	else if ( is_wp_error($allow) )
		return $allow;

	$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM {$wpdb->users} WHERE user_login = %s", $user_login));
	if ( empty($key) ) {
		// Generate something random for a key...
		$key = wp_generate_password(20, false);
		do_action('retrieve_password_key', $user_login, $key);
		// Now insert the new md5 key into the db
		$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
	}
	$message = __('Someone has asked to reset the password for the following site and username.','bl') . "\r\n\r\n";
	$message .= 'http://' . trailingslashit( $current_site->domain . $current_site->path ) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s','bl'), $user_login) . "\r\n\r\n";
	$message .= __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.','bl') . "\r\n\r\n";
	$message .= bl_get_validate_mail_link()."/?key=$key&login=" . rawurlencode($user_login) . "\r\n";

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$title = sprintf(__('[%s] Password Reset'), $blogname);

	$title = apply_filters('retrieve_password_title', $title);
	$message = apply_filters('retrieve_password_message', $message, $key);

	if ( $message && !wp_mail($user_email, $title, $message) )
		return new WP_Error('email_problem', __('The e-mail could not be sent.Possible reason: your host may have disabled the mail() function...','bl'));

         else
             return  __('We have sent you a link to reset password.Please check your mail.','bl');
	
}

//handle logout
function bl_logout_handle(){
   
	check_admin_referer('log-out');
	wp_logout();

	$redirect_to = bp_get_root_domain();
	if ( isset( $_REQUEST['redirect_to'] ) )
		$redirect_to = $_REQUEST['redirect_to'];

        bp_core_add_message(__('You have succesfully logged out!','bl'));
	wp_safe_redirect($redirect_to);
	exit();
}
//filter wordpress actions for reset password and login and send to  login/reset password page

/**
 * Handles resetting the user's password.
 *
 * @uses $wpdb WordPress Database object
 *
 * @param string $key Hash to validate sending user's password
 * @return bool|WP_Error
 */
function bl_reset_password($key, $login) {
	global $wpdb, $current_site;

	$key = preg_replace('/[^a-z0-9]/i', '', $key);

	if ( empty( $key ) || !is_string( $key ) )
		return new WP_Error('invalid_key', __('Invalid key','bl'));

	if ( empty($login) || !is_string($login) )
		return new WP_Error('invalid_key', __('Invalid key','bl'));

	$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));
	if ( empty( $user ) )
		return new WP_Error('invalid_key', __('Invalid key','bl'));

	// Generate something random for a password...
	$new_pass = wp_generate_password();

	do_action('password_reset', $user, $new_pass);

	wp_set_password($new_pass, $user->ID);
	update_usermeta($user->ID, 'default_password_nag', true); //Set up the Password change nag.
	$message  = sprintf(__('Username: %s','bl'), $user->user_login) . "\r\n";
	$message .= sprintf(__('Password: %s','bl'), $new_pass) . "\r\n";
	$message .= bl_get_login_link() . "\r\n";

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$title = sprintf(__('[%s] Your new password','bl'), $blogname);

	$title = apply_filters('password_reset_title', $title);
	$message = apply_filters('password_reset_message', $message, $new_pass);

	if ( $message && !wp_mail($user->user_email, $title, $message) )
  		return new WP_Error('email_problem', __('The e-mail could not be sent.Possible reason: your host may have disabled the mail() function...','bl'));

	wp_password_change_notification($user);

	return __('We have generated a password for you. Please check your mail for the new password.','bl');
}

//expoded api for theme developers

function bl_is_login_page(){
    global $bp;
    return bp_is_current_component( $bp->login->slug );
}

function bl_is_password_reset(){
    global $bp;

    if( bp_is_current_component( $bp->resetpass->slug ) && empty( $bp->current_action ) )
            return true;
    
    return false;

}

function bl_is_mail_validation(){
    global $bp;
    
    if( bp_is_current_component( $bp->resetpass->slug ) && bp_is_current_action( 'validate' ) )
            return true;
    
    return false;
}


function bl_get_login_link(){
    global $bp;
    
    return bp_get_root_domain().'/'.$bp->pages->login->slug;
}
//link to rest password
function bl_get_reset_pass_link(){
    global $bp;
    return bp_get_root_domain().'/'.$bp->pages->resetpass->slug;
}

function bl_get_validate_mail_link(){
    global $bp;
    return bp_get_root_domain().'/'.$bp->pages->resetpass->slug."/validate";
}

function bl_get_logout_link(){
    global  $bp;
     
    return bp_get_root_domain().'/'.$bp->pages->logout->slug;
}

/***
 * helpers for tweaking the experience a bit
 */

//filter login url from wordpress

/*** Filter The Topbar menu*/

// **** "Log In" and "Sign Up" links (Visible when not logged in) ********
function bl_adminbar_login_menu() {
	global $bp;

	if ( is_user_logged_in() )
		return false;

	echo '<li class="bp-login no-arrow"><a href="' . bl_get_login_link(). '?redirect_to=' . urlencode( $bp->root_domain ) . '">' . __( 'Log In', 'bl' ) . '</a></li>';

	// Show "Sign Up" link if user registrations are allowed
	if ( bp_get_signup_allowed() ) {
		echo '<li class="bp-signup no-arrow"><a href="' . bp_get_signup_page(false) . '">' . __( 'Sign Up', 'bl' ) . '</a></li>';
	}
}

//remove buddypress admin barl login

add_action( 'bp_adminbar_menus', 'bl_adminbar_login_menu', 2 );


//sniff logout url sir
add_filter( 'logout_url', 'bl_filter_logout_url', 10, 2 ) ;

function bl_filter_logout_url( $logout_url, $redirect ) {
	
    if( empty( $redirect ) )
        $redirect = get_home_url ();

        $redirect = apply_filters('bl_logout_redirect', $redirect);//allow other to modify
	
        $args = array( 'action' => 'logout' );
	
        if ( !empty( $redirect ) ) {
            $args['redirect_to'] = urlencode( $redirect );
        }

	$logout_url = add_query_arg( $args, bl_get_logout_link() );
	$logout_url = wp_nonce_url( $logout_url, 'log-out' );

	return $logout_url;//, $redirect);
}

/* catch wp-login.php and various actions, so user will never see it :) */

add_action( 'plugins_loaded', 'bl_wp_url_catcher' );

function bl_wp_url_catcher(){
    //catch all urls which have wp-login.php
    global $bp;
 
    if(!strripos( $_SERVER['REQUEST_URI'], 'wp-login.php') )
         return;
 
    $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';

    $to_where="";
 
    switch ($action) {
        case 'lostpassword' :
        case 'retrievepassword' :
            $to_where = $bp->pages->restepass->slug;
            break;
    
        case 'resetpass' :
        case 'rp' :
            $to_where = $bp->pages->restepass->slug."/?key=".$_GET['key']."&login=".$_GET['login'];
            break;
        
        case 'login' :
        default:
            $to_where = $bp->pages->login->slug;
    }
 
    wp_safe_redirect(bp_get_root_domain()."/".$to_where);
 
    exit();
}

add_filter( 'login_redirect', 'bl_login_redirect' );
function bl_login_redirect( $redirect_to ) {//compliment buddypress redirect to function
	global $bp, $current_blog;
        
    $where_redirect="";//find where to redirect
	
    if ( is_multisite() && $current_blog->blog_id != BP_ROOT_BLOG )
		$where_redirect = $redirect_to;

	else if ( !empty( $_REQUEST['redirect_to'] ) || strpos( $_REQUEST['redirect_to'], 'wp-admin' ) )
		$where_redirect = $redirect_to;

	else if ( false === strpos( wp_get_referer(), $bp->pages->login->slug ) && false === strpos( wp_get_referer(), BP_ACTIVATION_SLUG ) && empty( $_REQUEST['nr'] ) )
		$where_redirect = wp_get_referer();
    else
         $where_redirect=$bp->root_domain;
            
	return apply_filters('bp_bl_login_redirect_url',$where_redirect);//allow to change it and make other plugins to redirct to profile etc
}

//replace the form urls with the new url if site_url("wp-login.php" is used) in forms, otherwise we may not change dynamically,so you will have to use bl_get_login_link() manually.

add_filter( 'site_url', 'bl_change_form_url', 10, 3 );

function bl_change_form_url( $url, $path, $orig_schema ){

    if( $path != 'wp-login.php' )
        return $url;

    return bl_get_login_link();/*modified URl sir */

}

//update page title
add_filter('bp_modify_page_title','bl_get_title',5,4);

function bl_get_title( $title, $cur_title, $sep, $seplocation ){
   global $bp;
   
    if( bp_is_current_component( $bp->login->slug ) )
        $title = get_the_title( $bp->pages->login->id ). " $sep ";
    else if(bp_is_current_component( $bp->resetpass->slug ) )
        $title = get_the_title( $bp->pages->resetpass->id ). " $sep ";
        
    else if ( bp_is_current_component( $bp->logout->slug ) )
            $title = get_the_title( $bp->pages->logout->id ). " $sep ";
    
     
 return $title;


}
/**
 * Exclude pages from nav menu
 */
add_filter('bp_core_exclude_pages','bl_exclude_from_nav');
function bl_exclude_from_nav($pages){
    global $bp;
    $pages[] = $bp->pages->logout->id;
    
    if( is_user_logged_in() ){
     $pages[] = $bp->pages->resetpass->id;
     $pages[] = $bp->pages->login->id;
    }
    
    return $pages;
}

//filter the logout page url

add_filter( 'page_link', 'bl_filter_logout_page_url', 20, 3 );

function bl_filter_logout_page_url ( $link, $page_id, $sample ){
     global $bp;
  
    if( $page_id != $bp->pages->logout->id )
        return $link;
    
    //if we are here, it is the logout page
    //assuming that bp needs permalink
    return bl_filter_logout_url('', false);
}