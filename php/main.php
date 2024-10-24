<?php
namespace SIM\LOGIN;
use SIM;

//disable wp-login.php except for logout
add_action('init',function(){
    // do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }

    global $pagenow;
	if( 
        $pagenow == 'wp-login.php' &&                       // we want the default login page                  
        get_option("wpstg_is_staging_site") != "true" &&    // we are not on a staging site
        !is_user_logged_in()                                // we are not logged in  
    ){                    
        //redirect to login screen
        wp_redirect(SITEURL."/?showlogin");
        exit;
	}
});

//make sure wp_login_url returns correct url
add_filter( 'login_url', function($loginUrl, $redirect ){
    return add_query_arg(['showlogin' => '', 'redirect' => $redirect], home_url());
}, 10, 2);

// Tweak the message people see when not logged in and making an ajax request
add_filter('sim-content-filter-rest-not-logged-in-message', function($message){
    $message    = "<div id='iframe-loader'>";
        $message    .= "<h4>You are not logged in, loading login form...</h4>";
        $message    .= "<img class='loadergif' src='".SIM\LOADERIMAGEURL."'>";
    $message    .= "</div>";
    $message    .= "<iframe src='".SITEURL."/wp-content/sim-modules/login/php/login_modal.php?iframe=true' style='position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;'></iframe>";
    
    return $message;
});

// Tweak the message people see when not logged in and making an ajax request
add_filter('sim-content-filter-rest-not-logged-in-data', function($data){
    if(!empty($data['status'])){
        unset($data['status']);
    }

    return $data;
});

/**
 * Creates a login form modal
 */
function loginModal($message='', $required=false, $username=''){
    // Login modal already added
    if(isset($GLOBALS['loginadded'])){
        return;
    }
    $GLOBALS['loginadded']  = 'true';

    require(__DIR__ . '/login_modal.php');
}

//add hidden login modal to page if not logged in
add_action( 'loop_end', function () {
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
}, 99999);

// Disable administration email verification
add_filter( 'admin_email_check_interval', '__return_false' );