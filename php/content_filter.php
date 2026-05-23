<?php 
namespace TSJIPPY\LOGIN;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// When we die, make sure we output the login modal
add_filter( 'wp_die_handler', function($handler){

    if(!is_user_logged_in()){
        return __NAMESPACE__.'\wpDieCallLoginModal';
    }else{
        return $handler;
    }
});

function wpDieCallLoginModal($message, $title, $args ){
    TSJIPPY\printArray($_SERVER);
    loginModal($message, true);
}
