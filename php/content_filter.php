<?php 
namespace SIM\LOGIN;
use SIM;

// When we die, make sure we output the login modal
add_filter( 'wp_die_handler', function($handler){

    if(!is_user_logged_in()){
        return "SIM\LOGIN\loginModal";
    }else{
        return $handler;
    }
});

