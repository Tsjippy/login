<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

//disable wp-login.php except for logout
add_action('init', __NAMESPACE__ . '\redirectToLogin');
function redirectToLogin()
{
    // do not run during rest request
    if (TSJIPPY\isRestApiRequest()) {
        return;
    }

    global $pagenow;
    if (
        $pagenow == 'wp-login.php' &&                       // we want the default login page
        (
            !isset($_GET['action'])     ||                  // There is not action param set
            $_GET['action'] != "postpass"                   // or the post password action is not set
        )   &&
        get_option("wpstg_is_staging_site") != "true" &&    // we are not on a staging site
        !is_user_logged_in()                                // we are not logged in
    ) {
        //redirect to login screen
        wp_redirect(TSJIPPY\SITEURL . "/?showlogin");
        exit;
    }
}

//make sure wp_login_url returns correct url
add_filter('login_url', __NAMESPACE__ . '\loginUrl', 10, 2);
/**
 * Tweaks the login url
 *
 * @param   string  $loginUrl    The default login url
 * @param   string  $redirect    The redirect url
 *
 * @return  string  The login url to show
 */
function loginUrl($loginUrl, $redirect)
{
    return add_query_arg(['showlogin' => '', 'redirect' => $redirect], home_url());
}

// Tweak the message people see when not logged in and making an ajax request
add_filter('tsjippy-content-filter-rest-not-logged-in-message', __NAMESPACE__ . '\notLoggedInMsg');
/**
 * Tweaks the message people see when not logged in and making an ajax request
 *
 * @param   string  $message    The default message
 *
 * @return  string  The message to show when not logged in and making an ajax request
 */
function notLoggedInMsg($message)
{
    $message    = "<div id='iframe-loader'>";
    $message    .= "<h4>You are not logged in, loading login form...</h4>";
    $message    .= '<div class="loader-image-trigger"></div>';
    $message    .= "</div>";
    $message    .= "<iframe src='" . WP_PLUGIN_URL . "/tsjippy-login/php/on_request/login_modal.php?iframe=true' style='position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;'></iframe>";

    return $message;
}

// Tweak the message people see when not logged in and making an ajax request
add_filter('tsjippy-content-filter-rest-not-logged-in-data', __NAMESPACE__ . '\notLoggedInData');
/**
 * Tweaks the data returned when not logged in and making an ajax request
 *
 * @param   array  $data    The default data
 *
 * @return  array  The data to show when not logged in and making an ajax request
 */
function notLoggedInData($data)
{
    if (!empty($data['status'])) {
        unset($data['status']);
    }

    return $data;
}

/**
 * Creates a login form modal
 *
 * @param   string  $message    An optional message to show in the modal
 * @param   bool    $required   Whether the login is required or just optional (default false)
 * @param   string  $username   An optional username to prefill the form with
 *
 */
function loginModal($message = '', $required = false, $username = '')
{
    // Login modal already added
    if (isset($GLOBALS['loginadded'])) {
        return;
    }
    $GLOBALS['loginadded']  = true;

    require(__DIR__ . '/on_request/login_modal.php');
}

//add hidden login modal to page if not logged in
add_action('loop_end', __NAMESPACE__ . '\loopEnd', 99999);
function loopEnd()
{
    if (!is_main_query()) {
        return;
    }

    if (!is_user_logged_in()) {
        if (isset($_GET['showlogin'])) {
            loginModal('', true, TSJIPPY\sanitize($_GET['showlogin']));
        } else {
            loginModal();
        }
    }
}

/**
 * Adds a 2fa method if it does not exist already
 *
 * @param   string  $method     one of email, authenticator or webauthn
 * @param   int     $userId     The userId
 */
function addMethod($method, $userId)
{
    $methods    = get_user_meta($userId, "tsjippy_2fa_methods");
    if (!in_array($method, $methods)) {
        add_user_meta($userId, "tsjippy_2fa_methods", $method);
    }
}

add_filter('display_post_states', __NAMESPACE__ . '\postStates', 10, 2);
/**
 * Tweaks the post states displayed in the admin area
 *
 * @param   array  $states    The current post states
 * @param   object  $post     The post object
 *
 * @return  array  The modified post states
 */
function postStates($states, $post)
{

    if ($post->ID == (SETTINGS['password-reset-page'] ?? false)) {
        $states[] = __('Password reset page', '%TEXTDOMAIN%');
    } elseif ($post->ID == (SETTINGS['register-page'] ?? false)) {
        $states[] = __('User register page', '%TEXTDOMAIN%');
    } elseif ($post->ID == (SETTINGS['2fa-page'] ?? false)) {
        $states[] = __('Two Factor Setup page', '%TEXTDOMAIN%');
    }

    return $states;
}

/**
 * Tweaks the lost password url to point to our custom password reset page if set
 */
add_filter('lostpassword_url', function ($lostpasswordUrl) {
    $pageUrl    = get_permalink(SETTINGS['password-reset-page'] ?? '');
    if ($pageUrl) {
        return $pageUrl;
    }
    return $lostpasswordUrl;
});
