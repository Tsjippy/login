<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_shortcode('tsjippy_twofa_setup', __NAMESPACE__ . '\twoFaSettingsForm');
function twoFaSettingsForm($userId = '')
{
    ob_start();
    //we need to approve a qr code login
    // phpcs:ignore
    if (!empty($_GET['token']) && !empty($_GET['key'])) {
        ?>
        <div class='loader-wrapper'>
            <div class="loader-image-trigger"></div>

            <span id='message' class='message'>Waiting for biometric</span>
        </div>
        <div>
            <p>
                Please authenticate to approve the qr code login request
            </p>
        </div>
        <?php
        wp_enqueue_script('tsjippy_qr_code_login', TSJIPPY\pathToUrl(PLUGINPATH . 'js/qr_code_login.min.js'), [], PLUGINVERSION, true);
        return ob_get_clean();
    }

    //Load js
    wp_enqueue_script('tsjippy_2fa_script');

    if (!is_numeric($userId)) {
        $userId = get_current_user_id();
    }

    if(empty($userId)){
        return "<div class='error'>You are not logged in</div>";
    }

    $twoFaMethods    = array_flip(get_user_meta($userId, 'tsjippy_2fa_methods'));

    if (!empty($_GET['redirected'])) {
    ?>
        <div class='error'>
            <p style='border-left: 4px solid #bd2919;padding: 5px;'>
                You have been redirected to this page because you need to setup a second login factor before you can visit other pages.
            </p>
        </div>
    <?php
    }
    ?>
    <form id="2fa-setup-wrapper">
        <div id='2fa-options-wrapper' style='margin-bottom:20px;'>
            <h4>Second login factor</h4>
            <?php
            if (empty($twoFaMethods) || isset($twoFaMethods['webauthn']) && count($twoFaMethods) == 1) {
            ?>
                <p>
                    Please setup an second login factor to keep this website safe.<br>
                    Choose one of the options below.
                </p>
            <?php
            } else {
            ?>
                <p>
                    Your active second login factor is:
                </p>
            <?php
            }
            ?>
            <label>
                <input 
                    type="radio" 
                    class="twofa-option-checkbox" 
                    name="2fa-methods[]" 
                    value="authenticator" 
                    <?php if (isset($twoFaMethods['authenticator'])) echo "checked";  ?>>
                <span class="option-label">Authenticator app</span>
            </label>
            <br>
            <label>
                <input 
                    type="radio" 
                    class="twofa-option-checkbox" 
                    name="2fa-methods[]" 
                    value="email" 
                    <?php if (isset($twoFaMethods['email'])) echo "checked";  ?>>
                <span class="option-label">E-mail</span>
            </label>
            <br>
        </div>

        <?php
        // authenticator app not yet setup
        if (empty($twoFaMethods) || !isset($twoFaMethods['authenticator'])) {
            $secondFactor    = setupTimeCode();
        ?>
            <input type='hidden' class='no-reset' name='secretkey' value='<?php echo esc_attr($secondFactor->secretKey); ?>'>
            <div id='setup-authenticator' class='twofa-option hidden'>
                <p>
                    You need an authenticator app as a second login factor.<br>
                    Both "Google Authenticator" and "Microsoft Authenticator" are good options.<br>
                    For iOS you can use the built-in password manager.
                    Make sure you have one of them available on your phone. <br>
                </p>
                <div id="authenticatorlinks" class='hidden mobile'>
                    <p>
                        You can use one of the links below to download an app<br>
                        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Download for Android</a><br>
                        <a href="https://apps.apple.com/us/app/google-authenticator/id388497605">Download for iPhone</a><br>
                        <br>
                        Click the button below when you have an app installed.<br>
                        This will open the app and create a code.<br>
                        You can also manually add an entry using this code: <code><?php echo esc_attr($secondFactor->secretKey); ?></code>.
                        Copy the code created by the authenticator app in the field below.<br>
                        <?php echo esc_attr($secondFactor->appLink); ?><br>
                    </p>
                </div>
                <div class='hidden desktop'>
                    <p>
                        Scan the qr code displayed below to open up your authenticator app.<br>
                        You can also manually add an entry using this code: <code><?php echo esc_attr($secondFactor->secretKey); ?></code>
                        Copy the code created by the authenticator app in the field below.<br>
                        <?php echo $secondFactor->imageHtml; ?>
                    </p>
                </div>
                <label>
                    Insert the created code here.<br>
                    <input type='text' name='auth-secret' required>
                </label>
                <p>Not sure what to do? Check the <a href="<?php echo TSJIPPY\SITEURL; ?>'/manuals/">manuals!</a></p>
            </div>
        <?php
        }

        // E-mail not yet setup
        if (empty($twoFaMethods) || !isset($twoFaMethods['email'])) {
        ?>
            <div id='setup-email' class='twofa-option hidden'>
                <input type='hidden' class='no-reset' id='username' value='<?php echo esc_attr($userId); ?>'>
                <p>
                    Click the button below to enable e-mail verification <br>
                    You will receive an e-mail on <code><?php echo get_userdata($userId)->user_email; ?></code>.<br>
                    Copy the code from that e-mail.<br>
                    <button type='button' class='button small' id='email-code-button'>E-mail the code</button>
                <div id='email-message'></div>
                </p>
                <label id='email-code-validation' class='hidden'>
                    Insert the code e-mailed to you here.<br>
                    <input type='text' name='email-code' required>
                </label>
            </div>
        <?php
        }

        TSJIPPY\addSaveButton('save2fa', "Save 2fa settings", 'hidden');
        ?>
    </form>

    <div id='webauthn-wrapper' class='hidden'>
        <?php
        $webAuthCeremony    = new WebAuthCeremony();
        $webAuthCeremony->authTable($userId);
        ?>
    </div>
<?php
    return ob_get_clean();
}
