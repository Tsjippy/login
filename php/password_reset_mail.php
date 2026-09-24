<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * sends the password reset e-mail
 *
 * @param    object    $user    WP_User
 *
 * @param    true|WP_Error    true on success, error on failure
 */
function sendPasswordResetMessage($user)
{
    $key         = get_password_reset_key($user);
    if (is_wp_error($key)) {
        return $key;
    }

    $pageurl     = get_permalink(SETTINGS['password-reset-page'] ?? createDefaultPages('password-reset-page'));
    $url         = "$pageurl?key=$key&login=$user->user_login";

    //Send e-mail
    $mail    = new PasswordResetMail($user, $url);
    $mail->filterMail();

    $result                = wp_mail($user->user_email, $mail->subject, $mail->message);

    if (!$result) {
        return new \WP_Error('sending email failed', 'Sending e-mail failed');
    }
}

add_filter('retrieve_password_message', __NAMESPACE__ . '\passwordMessage', 10, 4);
/**
 * Filters the message body of the password reset mail.
 *
 * If the filtered message is empty, the password reset email will not be sent.
 *
 * @since 2.8.0
 * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
 *
 * @param string  $message    Email message.
 * @param string  $key        The activation key.
 * @param string  $userLogin  The username for the user.
 * @param WP_User $user       WP_User object.
 */
function passwordMessage($message, $key, $userLogin, $user)
{
    $pageurl     = get_permalink(SETTINGS['password-reset-page'] ?? createDefaultPages('password-reset-page'));

    if (!$pageurl) {
        return $message;
    }

    $url         = "$pageurl?key=$key&login=$userLogin";
    $mail        = new PasswordResetMail($user, $url);
    $mail->filterMail();

    return $mail->message;
}
