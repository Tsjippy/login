<?php 
namespace TSJIPPY\LOGIN;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// When we die, make sure we output the login modal
add_filter( 'wp_die_handler', function($handler){

    if(!is_user_logged_in()){
        return "TSJIPPY\LOGIN\loginModal";
    }else{
        return $handler;
    }
});

