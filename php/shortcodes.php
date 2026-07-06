<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Displays the password reset form for an user
 *
 * @param    object    $user    WP_User
 *
 * @return    string            The html
 */
function passwordResetForm($user)
{
    // Load style
    wp_enqueue_style('tsjippy_pw_reset_style');

    //Load js
    wp_enqueue_script('tsjippy_password_strength_script');

    if (get_current_user_id() == $user->ID || !is_user_logged_in()) {
        $message         = "Change your password using the fields below.<br>";
        $message        .= "<br>Your username is $user->user_login. ";
    } else {
        $message         = "Change the password for $user->display_name using the fields below.<br>";
        $message        .= "<br>Username is $user->user_login. ";
    }

    ob_start();
?>

    <form class="pwd-reset">
        <div class="login-info">
            <input type="hidden" class="no-reset" name="user-id" value="<?php echo esc_attr($user->ID); ?>">

            <p style="margin-top:30px;">
                <?php echo $message; ?>
            </p>
            <div class='password'>
                <label>
                    New Password<br>
                    <input type="password" class='changepass wide' name="pass1" size="16" autocomplete="off" required />
                </label>
                <button type="button" class='toggle-pwd-view' data-toggle="0" title="Show password">
                    <img src="<?php echo TSJIPPY\PICTURESURL . '/invisible.png'; ?>" loading='lazy' alt='togglepasword'>
                </button>
                <br>
                <span class="pass-strength-result hidden" id="pass-strength-result1">Strength indicator</span>
                <br>
            </div>
            <div class='password'>
                <label>
                    Confirm New Password<br>
                    <input type="password" class='changepass wide' name="pass2" size="16" autocomplete="off" required />
                </label>
                <button type="button" class='toggle-pwd-view' data-toggle="0" title="Show password">
                    <img src="<?php echo TSJIPPY\PICTURESURL . '/invisible.png'; ?>" loading='lazy' alt='togglepasword'>
                </button>
                <br>
                <span class="pass-strength-result hidden" id="pass-strength-result2">Strength indicator</span>
            </div>
            <?php do_action('resetpass_form'); ?>
        </div>
        <?php TSJIPPY\addSaveButton('update-password', 'Change password'); ?>
    </form>

<?php
    return ob_get_clean();
}

// Make password reset links valid for 7 days
add_filter('password_reset_expiration', function () {
    return DAY_IN_SECONDS * 7;
});

// Display password reset
add_shortcode("tsjippy_change_password", __NAMESPACE__ . '\changePassword');
function changePassword()
{
    $user    = '';

    if (!empty($_GET['key']) && !empty($_GET['login'])) {
        $user    = check_password_reset_key(TSJIPPY\sanitize($_GET['key']), esc_html(TSJIPPY\sanitize($_GET['login'])));
    }

    if (is_wp_error($user) || empty($user)) {
        if (!is_user_logged_in() && !empty($user)) {
            if ($user->get_error_message() == "Invalid key.") {
                return "<div class='error'>This link has expired, please request a new password using the login menu.</div>";
            }

            return "<div class='error'>".$user->get_error_message() . "<br>Please try again.</div>";
        }


        $user    = wp_get_current_user();
    }

    return passwordResetForm($user);
}

/**
 * ACCOUNT REQUEST
 */
//Shortcode for people to register themselves
add_shortcode('tsjippy_request_account', __NAMESPACE__ . '\requestAccount');
function requestAccount()
{
    ob_start();
?>
    <form class='request-account'>
        <p>Please fill in the form to create an user account</p>

        <input type="hidden" class="no-reset" name="action" value="requestuseraccount">
        <input type="hidden" class="no-reset" name="nonce" value="<?php echo wp_create_nonce('account-creation'); ?>">

        <label>
            <h4>
                First name<span class="required">*</span>
            </h4>
            <input type="text" class='wide' name="first-name" value="" required>
        </label>

        <label>
            <h4>
                Last name<span class="required">*</span>
            </h4>
            <input type="text" class='wide' name="last-name" required>
        </label>

        <label>
            <h4>
                Desired Password
            </h4>
            <input type="password" class='changepass wide' name="pass1" size="16" autocomplete="off" />
        </label>
        <br>
        <span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result1">Strength indicator</span>
        <br>
        <label>
            <h4>
                Confirm Password
            </h4>
            <input type="password" class='changepass wide' name="pass2" size="16" autocomplete="off" />
        </label>
        <br>
        <span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result2">Strength indicator</span>

        <label>
            <h4>
                E-mail<span class="required">*</span>
            </h4>
            <input class="wide" type="email" name="email" required>
        </label>
        <?php
        do_action('register_form');
        TSJIPPY\addSaveButton('request_account', 'Request an account');
        ?>
    </form>
<?php

    return ob_get_clean();
}
