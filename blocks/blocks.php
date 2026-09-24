<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', __NAMESPACE__ . '\initBlocks');
/**
 * Registers login blocks
 */
function initBlocks()
{
    register_block_type(
        'tsjippy-login/twofa-setup',
        array(
            'title'           => __( 'Two Factor Setup', '%TEXTDOMAIN%' ),
            'render_callback' => __NAMESPACE__.'\twoFaSettingsForm',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'id'
        )
    );

    register_block_type(
        'tsjippy-login/change-password',
        array(
            'title'           => __( 'Change Password Form', '%TEXTDOMAIN%' ),
            'render_callback' => __NAMESPACE__.'\changePassword',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'forms'
        )
    );

    register_block_type(
        'tsjippy-login/request-user-account',
        array(
            'title'           => __( 'Request User Account Form', '%TEXTDOMAIN%' ),
            'render_callback' => __NAMESPACE__.'\requestAccount',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'id'
        )
    );

	register_block_type(
        'tsjippy-login/login-count',
        array(
            'title'           => __( 'User Login Count', '%TEXTDOMAIN%' ),
            'render_callback' => function(){
                return "<span>".loginCount()."</span>";
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
            "icon"  => "plus"
        )
    );
}

//Shortcode to return the amount of loggins in words
add_shortcode("tsjippy-login-count", __NAMESPACE__ . '\loginCount');
/**
 * Returns the login count as string
 * 
 * @return string
 */
function loginCount()
{
	$userId				= get_current_user_id();
	$currentLogginCount = get_user_meta($userId, 'tsjippy_login_count', true);
	//Get the word from the array
	if (is_numeric($currentLogginCount)) {
		return TSJIPPY\numberToWords($currentLogginCount);
		//key not set, assume its the first time
	} else {
		return "your first";
	}
}

// Display password reset
/**
 * Returns the password reset form html
 * 
 * @return string
 */
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
    wp_enqueue_script_module('@tsjippy/password_strength_script');

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

/**
 * ACCOUNT REQUEST
 */
function requestAccount()
{
    wp_enqueue_style('tsjippy_pw_reset_style');

    wp_enqueue_script_module('@tsjippy/password_strength_script');
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