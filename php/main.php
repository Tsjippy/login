<?php
namespace SIM\LOGIN;
use SIM;

//disable wp-login.php except for logout
add_action('init', __NAMESPACE__.'\redirectToLogin');
function redirectToLogin(){
    // do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }

    global $pagenow;
	if( 
        $pagenow == 'wp-login.php' &&                       // we want the default login page      
        (
            !isset($_GET['action'])     ||                  // There is not action param set
            $_GET['action'] != "postpass"                   // or the post password action is not set
        )   &&
        get_option("wpstg_is_staging_site") != "true" &&    // we are not on a staging site
        !is_user_logged_in()                                // we are not logged in  
    ){                    
        //redirect to login screen
        wp_redirect(SITEURL."/?showlogin");
        exit;
	}
}

//make sure wp_login_url returns correct url
add_filter( 'login_url', __NAMESPACE__.'\loginUrl', 10, 2);
function loginUrl($loginUrl, $redirect ){
    return add_query_arg(['showlogin' => '', 'redirect' => $redirect], home_url());
}

// Tweak the message people see when not logged in and making an ajax request
add_filter('sim-content-filter-rest-not-logged-in-message', __NAMESPACE__.'\notLoggedInMsg');
function notLoggedInMsg($message){
    $message    = "<div id='iframe-loader'>";
        $message    .= "<h4>You are not logged in, loading login form...</h4>";
        $message    .= '<div class="loader-image-trigger"></div>';
    $message    .= "</div>";
    $message    .= "<iframe src='".SITEURL."/wp-content/sim-modules/login/php/login_modal.php?iframe=true' style='position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;'></iframe>";
    
    return $message;
}

// Tweak the message people see when not logged in and making an ajax request
add_filter('sim-content-filter-rest-not-logged-in-data', __NAMESPACE__.'\notLoggedInData');
function notLoggedInData($data){
    if(!empty($data['status'])){
        unset($data['status']);
    }

    return $data;
}

/**
 * Creates a login form modal
 */
function loginModal($message='', $required=false, $username=''){
    // Login modal already added
    if(isset($GLOBALS['loginadded'])){
        return;
    }
    $GLOBALS['loginadded']  = true;

    require(__DIR__ . '/login_modal.php');
}

//add hidden login modal to page if not logged in
add_action( 'loop_end', __NAMESPACE__.'\loopEnd', 99999);
function loopEnd() {
    if(!is_main_query()){
        return;
    }
    
	if (!is_user_logged_in()){
        if(isset($_GET['showlogin'])){
            loginModal('', true, $_GET['showlogin']);
        }else{
            loginModal();
        }
    }
}