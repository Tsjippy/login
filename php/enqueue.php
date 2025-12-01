<?php
namespace SIM\LOGIN;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets(){
    if(!is_user_logged_in()){
	    //login form
	    wp_register_style( 'sim_login_style', SIM\pathToUrl(MODULE_PATH.'css/login.min.css'), array(), MODULE_VERSION);
        wp_enqueue_style( 'sim_login_style');

        wp_enqueue_script('sim_login_script', SIM\pathToUrl(MODULE_PATH.'js/login.min.js'), array('sim_script', 'sim_purify', 'sim_formsubmit_script'), MODULE_VERSION, true);
    }else{
        if(!SIM\getFromTransient('last-used-cred-id')){
            wp_enqueue_script('sim_register_webauthn_script', SIM\pathToUrl(MODULE_PATH.'js/register_webauth.min.js'), array('sim_script', 'sim_formsubmit_script'), MODULE_VERSION, true);
            
            // Make sure we do this only once per login
            SIM\storeInTransient('last-used-cred-id', 1);
        }
        
        wp_enqueue_script('sim_logout_script', SIM\pathToUrl(MODULE_PATH.'js/logout.min.js'), array('sim_script', 'sim_formsubmit_script'), MODULE_VERSION, true);
    }

    wp_register_style( 'sim_pw_reset_style', SIM\pathToUrl(MODULE_PATH.'css/pw_reset.min.css'), array(), MODULE_VERSION);

    wp_register_script('sim_password_strength_script', SIM\pathToUrl(MODULE_PATH.'js/password_strength.min.js'), array('password-strength-meter'), MODULE_VERSION,true);

	wp_register_script('sim_2fa_script', SIM\pathToUrl(MODULE_PATH.'js/2fa.min.js'), array('sim_table_script'), MODULE_VERSION, true);

    if(is_numeric(get_the_ID())){
        $passwordResetPage  = SIM\getModuleOption(MODULE_SLUG, 'password-reset-page');
        $registerPage       = SIM\getModuleOption(MODULE_SLUG, 'register-page');
        if(get_the_ID() == $passwordResetPage || get_the_ID() == $registerPage){
            wp_enqueue_style('sim_pw_reset_style');

            wp_enqueue_script('sim_password_strength_script');
        }

        if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG, '2fa-page', false))){
            wp_enqueue_script('sim_2fa_script');
        }
    }
}