<?php
namespace SIM\LOGIN;
use SIM;

$standAlone = false;

if ( !defined( 'ABSPATH' ) ) {
    require_once(__DIR__ . '/wordpress_loader.php');
    loadWordpress('Login');
    
    $required   = true;
    $standAlone = true;
}elseif($required === null){
    return;
}

if(!is_string($username)){
    $username   = '';
}

if($standAlone && is_user_logged_in()){
    ?>
    <body>
        <div style='text-align:center;margin-top:20px;'>
            You are already logged in.<br><br>
            <a href='<?php echo SITEURL;?>'>Go Home</a></body>
        </div>
    </body>
    <?php
    exit();
}

loadAssets();

?>
<div id="login-modal" class="modal <?php if(!$required){echo 'hidden';}?>" style="display:unset;">
    <div class="modal-content">
        <?php
        if(!$required){
            echo '<span class="close">×</span>';
        }
        ?>
        <div id='login-wrapper'>
            <h3>
                Login form
            </h3>
            <p id="message"><?php
                if(!empty($message)){
                    echo $message;
                }
            ?></p>

            <div id='qrcode-wrapper' style='margin-top: -30px;min-height: 30px;'><span></span></div>

            <form id="loginform" action="login" method="post">
                <input type='hidden' class='no-reset' name='action' value='request_login'>
                
                <div id='message-wrapper' class='hidden'>
                    <h4 class='status-message'></h4>
                </div>

                <div id='credentials-wrapper'>
                    <label style="width: 100%;">
                        Username<br>
                        <input id="username" type="text" class='wide' name="username" value="<?php echo $username;?>" autofocus autocomplete="username webauthn">
                    </label>

                    <div class="password">
                        <label style='width:100%'>
                            Password
                            <input id="password" type="password" class='wide' name="password" autocomplete="password webauthn">
                        </label>
                        <button type="button" class='toggle-pwd-view' data-toggle="0" title="Show password">
                            <img src="<?php echo SIM\PICTURESURL.'/invisible.png';?>" loading='lazy' alt='togglepasword'>
                        </button>
                    </div>
                    <div id='check-cred-wrapper'>
                        <label id='remember-me-label'>
                            <input name="rememberme" type="checkbox" id="rememberme" value="forever" checked>
                            Remember Me
                        </label>
                    </div>
                    <?php do_action( 'login_form' );?>
                    
                    <button class='sim small button show-login-qr' type='button'>Login using QR code</button>
                    <button type='button' id='check-cred' class='button'>Login</button>
                </div>

                <div id='authenticator-wrapper' class='authenticator-wrapper hidden'>
                    <label>
                        Please enter the two-factor authentication (2FA) verification code below to login.
                        <input type="tel" name="authcode"  class='wide' size="20" pattern="[0-9]*" required>
                    </label>
                </div>

                <div id='email-wrapper' class='authenticator-wrapper hidden'>
                    <label>
                        Please enter the code sent to your e-mail below to login.
                        <input type="tel" name="email-code"  class='wide' size="20" pattern="[0-9]*" required>
                    </label>
                </div>

                <div id='login-button-wrapper' class='hidden'>
                    <div class='submit-wrapper'>
                        <button type='button' class='button' id='login-button' disabled>Login</button>
                    </div>
                </div>
            </form>

            <form id="password-reset-form">
                <div class='form-elements hidden' style='margin-bottom: 10px;'>
                    <?php echo do_action('resetpass_form');?>
                </div>
                <a href='#pwd_reset' id='lost-pwd-link'>Request password reset</a>
            </form>
        </div>
    </div>
</div>
<?php

if($standAlone){
    print_footer_scripts();

    exit;
}