<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
function loadAssets()
{
    /**
     * CSS
     */
    wp_register_style('tsjippy_pw_reset_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/pw_reset.min.css'), array(), PLUGINVERSION);

    /**
     * Libraries
     */
    wp_register_script_module('@simplewebauthn/browser', TSJIPPY\pathToUrl(PLUGINPATH . 'js/node_modules/@simplewebauthn/browser/esm/index.js'), array(), PLUGINVERSION);

    /**
     * Modules
     */
    wp_register_script_module('@tsjippy/qr_login', TSJIPPY\pathToUrl(PLUGINPATH . 'js/modules/qr_login.js'), array("@tsjippy/login-shared", "@tsjippy/form_submit_functions", "@tsjippy/show_loader"), PLUGINVERSION);

    wp_register_script_module('@tsjippy/register_webauth', TSJIPPY\pathToUrl(PLUGINPATH . 'js/modules/register_webauth.js'), array("@tsjippy/webauth", "@simplewebauthn/browser", "@tsjippy/login-shared", "@tsjippy/form_submit_functions"), PLUGINVERSION);

    wp_register_script_module('@tsjippy/login-shared', TSJIPPY\pathToUrl(PLUGINPATH . 'js/modules/shared.js'), array(), PLUGINVERSION);

    wp_register_script_module('@tsjippy/webauth', TSJIPPY\pathToUrl(PLUGINPATH . 'js/modules/webauth.js'), array('@simplewebauthn/browser', "@tsjippy/login-shared", "@tsjippy/form_submit_functions"), PLUGINVERSION);

    /**
     * Scripts
     */
    // 2FA
    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions',  
        "@tsjippy/show_loader", 
        "@tsjippy/display_message", 
        "@tsjippy/mobile"
    ] :
    [];

    $deps[] = '@tsjippy/table_script';

    wp_register_script_module('@tsjippy/2fa_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/2fa' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);

    //login form
    if (!is_user_logged_in()) {
        wp_enqueue_style('tsjippy_login_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/login.min.css'), array(), PLUGINVERSION);

        $deps   = SCRIPT_DEBUG ? [  
            '@tsjippy/form_submit_functions', 
            "@tsjippy/login-shared", 
            "@tsjippy/show_loader", 
            "@tsjippy/display_message", 
            "@tsjippy/qr_login",
            "@tsjippy/webauth",
            "@tsjippy/register_webauth",
            "@tsjippy/internet_connection"
        ] :
        [];

        wp_enqueue_script_module('@tsjippy/login_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/login' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);

        add_filter( 'script_module_data_@tsjippy/login_script', function($data){
            $data['restNonce'] = wp_create_nonce('wp_rest');
            $data['userId']    = get_current_user_id();

            return $data; 
        } );
    } 
    
    // Logout forms
    else {
        $deps   = SCRIPT_DEBUG ? [  
            '@tsjippy/form_submit_functions', 
            "@tsjippy/login-shared",  
            "@tsjippy/display_message", 
            "@tsjippy/alert",
        ] :
        [];
        wp_enqueue_script_module('@tsjippy/logout_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/logout' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);
    }

    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions', 
        "@tsjippy/login-shared", 
        "@tsjippy/show_loader", 
        "@tsjippy/display_message"
    ] :
    [];
    wp_register_script_module('@tsjippy/password_strength_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/password_strength' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);
}
