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

        wp_enqueue_script('tsjippy_login_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/login.min.js'), array('tsjippy_script', 'tsjippy_purify', 'tsjippy_formsubmit_script'), PLUGINVERSION, true);
    } else {
        wp_enqueue_script('tsjippy_logout_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/logout.min.js'), array('tsjippy_script', 'tsjippy_formsubmit_script'), PLUGINVERSION, true);
    }

    wp_register_style('tsjippy_pw_reset_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/pw_reset.min.css'), array(), PLUGINVERSION);

    wp_register_script('tsjippy_password_strength_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/password_strength.min.js'), array('password-strength-meter'), PLUGINVERSION, true);

    wp_register_script('tsjippy_2fa_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/2fa.min.js'), array('tsjippy_table_script'), PLUGINVERSION, true);

    if (is_numeric(get_the_ID())) {
        $passwordResetPage  = SETTINGS['password-reset-page'] ?? createDefaultPages('password-reset-page');
        $registerPage       = SETTINGS['register-page'] ?? createDefaultPages('register-page');
        if (get_the_ID() == $passwordResetPage || get_the_ID() == $registerPage) {
            wp_enqueue_style('tsjippy_pw_reset_style');

            wp_enqueue_script('tsjippy_password_strength_script');
        }

        if (get_the_ID() == (SETTINGS['2fa-page'] ?? createDefaultPages('2fa-page'))) {
            wp_enqueue_script('tsjippy_2fa_script');
        }
    }
}
