<?php
/* ------------------------------------------------------------------------- *
 *  Custom functions
/* ------------------------------------------------------------------------- */
	
	// Add your custom functions here, or overwrite existing ones. Read more how to use:
	// http://codex.wordpress.org/Child_Themes
	
	// Code for assigning different role based on role drop-down selection	
function custom_bp_core_signup_user($user_id) {
    $user_role = strtolower(xprofile_get_field_data('User Role', $user_id));
    switch($user_role) {
        case "Professional":
            $new_role = 'Professional';
            break;
        case "High School":
            $new_role = 'Highschool';
            break;
		case "College":
            $new_role = 'College';
            break;
		case "Amateur":
            $new_role = 'Amateur';
            break;			
        case "Others":
            $new_role = 'Others';
            break;
    }
    wp_update_user(array(
        'ID' => $user_id,
        'role' => $new_role
    ));
}
add_action( 'bp_core_signup_user', 'custom_bp_core_signup_user', 10, 1);


//hide the user role select field in edit-screen to prevent changes after registration
add_filter("xprofile_group_fields","bpdev_filter_profile_fields_by_usertype",10,2);
function bpdev_filter_profile_fields_by_usertype($fields,$group_id){
//only disable these fields on edit page
if(!bp_is_profile_edit())
return $fields;
//please change it with the name of fields you don't want to allow editing
$field_to_remove=array("User Role");
$count=count($fields);
$flds=array();
for($i=0;$i<$count;$i++){
if(in_array($fields[$i]->name,$field_to_remove))
unset($fields[$i]);
else
$flds[]=$fields[$i];//doh, I did not remember a way to reset the index, so creating a new array
}
return $flds;
}