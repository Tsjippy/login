<?php

namespace TSJIPPY\LOGIN;

/**
 * Plugin Name:          Tsjippy Login
 * Description:          This plugin turns on AJAX login and adds two factor login, webauthentication(fingerprint, facial, pincode) and qr code login. The normal login (wp-login.php) is no longer available once this plugin is activated. A 'login' or 'logout' menu button will be added instead. These buttons are used for AJAX based logins and logouts, saving loading time. Three second factor options are available: - Authenticator code - E-mail - Biometrics As biometrics is device specific, it is advisable to also add e-mail or authenticator login. The login process will be this:- Click login - If webauthentication is available you will be logged in straigt away if not: - Fill in username and password  - Click verify credentials - If a biometric login is enabled for the current device that will be used - If no biometric login is enabled for the current device a authenticator or e-mail code will be requested - If no second factor is setup yet the user will be redirected to the page to setup 2fa and cannot access the website. on Devices without webauthenticators people can login by scanning a qr code using a device with authenticator enabled 2fa setup is done on the page containing the twofa_setup shortcode. Use like this: <code>[twofa_setup]</code>
 * Version:              10.4.9
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         6.9
 * Plugin URI:            https://github.com/Tsjippy/login
 * Tested:                6.9
 * TextDomain:            tsjippy
 * Requires Plugins:    
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_login_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {
    // Load shared code
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    $publicCat    = get_cat_ID('Public');

    $settings    = SETTINGS;

    // Create password reset page
    $settings['password-reset-page'] = \TSJIPPY\ADMIN\createDefaultPage('Change password', '[tsjippy_change_password]', ['post_category' => [$publicCat]]);

    // Registration page
    $settings['register-page']       = \TSJIPPY\ADMIN\createDefaultPage('Request user account', '[tsjippy_request_account]', ['post_category' => [$publicCat]]);
    // Add 2fa page
    $settings['2fa-page']            = \TSJIPPY\ADMIN\createDefaultPage('Two Factor Authentication', '[tsjippy_twofa_setup]');

    update_option('tsjippy_login_settings', $settings);
});

// run on deactivation
register_deactivation_hook(__FILE__, function () {
    $removePages    = [];

    if (isset(SETTINGS['password-reset-page'])) {
        $removePages[]    = SETTINGS['password-reset-page'];
    }

    if (isset(SETTINGS['register-page'])) {
        $removePages[]    = SETTINGS['register-page'];
    }

    if (isset(SETTINGS['2fa-page'])) {
        $removePages[]    = SETTINGS['2fa-page'];
    }

    // Remove the auto created pages
    foreach ($removePages as $page) {
        // Remove the auto created page
        wp_delete_post($page, true);
    }
});