<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
function loadAssets()
{
    if (!is_user_logged_in()) {
        //login form
        wp_register_style('tsjippy_login_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/login.min.css'), array(), PLUGINVERSION);
        wp_enqueue_style('tsjippy_login_style');

        wp_enqueue_script_module('@tsjippy/login_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/login' . TSJIPPY\JSEXTENSION), array('@tsjippy/main', '@tsjippy/formsubmit_script'), PLUGINVERSION);

        add_filter( 'script_module_data_@tsjippy/login_script', function($data){
            $data['restNonce'] = wp_create_nonce('wp_rest');
            $data['userId']    = get_current_user_id();

            return $data; 
        } );
    } else {
        wp_enqueue_script_module('@tsjippy/logout_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/logout' . TSJIPPY\JSEXTENSION), array('@tsjippy/main', '@tsjippy/formsubmit_script'), PLUGINVERSION);
    }

    wp_register_style('tsjippy_pw_reset_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/pw_reset.min.css'), array(), PLUGINVERSION);

    wp_register_script_module('@tsjippy/password_strength_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/password_strength' . TSJIPPY\JSEXTENSION), array('@tsjippy/form_submit_functions'), PLUGINVERSION);

    wp_register_script_module('@tsjippy/2fa_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/2fa' . TSJIPPY\JSEXTENSION), array('@tsjippy/table_script'), PLUGINVERSION);

    if (is_numeric(get_the_ID())) {
        $passwordResetPage  = SETTINGS['password-reset-page'] ?? createDefaultPages('password-reset-page');
        $registerPage       = SETTINGS['register-page'] ?? createDefaultPages('register-page');
        if (get_the_ID() == $passwordResetPage || get_the_ID() == $registerPage) {
            wp_enqueue_style('tsjippy_pw_reset_style');

            wp_enqueue_script_module('@tsjippy/password_strength_script');
        }

        if (get_the_ID() == (SETTINGS['2fa-page'] ?? createDefaultPages('2fa-page'))) {
            wp_enqueue_script_module('@tsjippy/2fa_script');
        }
    }
}
