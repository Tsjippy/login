<?php
namespace SIM\LOGIN;
use SIM;
use RobThree\Auth\TwoFactorAuth;
use BaconQrCode\Renderer\ImageRenderer;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use stdClass;
use WP_Error;

require( plugin_dir_path(__DIR__)  . 'lib/vendor/autoload.php');

if(!class_exists('BaconQrCode\Renderer\ImageRenderer')){
    return new WP_Error('2fa', "bacon-qr-code interface does not exist. Please run 'composer require bacon/bacon-qr-code'");
}
if(!class_exists('RobThree\Auth\TwoFactorAuth')){
    return new WP_Error('2fa', "twofactorauth interface does not exist. Please run 'composer require robthree/twofactorauth'");
}

//https://robthree.github.io/TwoFactorAuth/getting-started.html
/**
 * Setup the one time key for authenticator
 *
 * @return  object       An object with the secret key and qr code
 */
function setupTimeCode(){
    $user                           = wp_get_current_user();
    $userId                         = $user->ID;
    $twofa                          = new TwoFactorAuth(new BaconQrCodeProvider());
    $setupDetails                   = new stdClass();
    $setupDetails->secretKey        = $twofa->createSecret();

    update_user_meta($userId, '2fa_hash', password_hash($setupDetails->secretKey, PASSWORD_DEFAULT));

    if (!extension_loaded('imagick')){
        $setupDetails->imageHtml    = "<img src=".$twofa->getQRCodeImageAsDataUri(SITENAME." (".get_userdata($userId)->user_login.")", $setupDetails->secretKey)." loading='lazy'>";
    }else{
        $qrCodeUrl                  = $twofa->getQRText(SITENAME." (".get_userdata($userId)->user_login.")",$setupDetails->secretKey);

        $renderer                   = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage   = base64_encode($writer->writeString($qrCodeUrl));

        $setupDetails->imageHtml     = "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/>";
    }
    otpauth://totp/Example:alice@google.com?secret=JBSWY3DPEHPK3PXP&issuer=Example

    $websiteName                   = rawurlencode(get_bloginfo('name'));
    $userName                      = rawurlencode($user->display_name);
    $totpUrl                       = "otpauth://totp/$websiteName:$userName?secret={$setupDetails->secretKey}&issuer=$websiteName";
    $setupDetails->appLink        = "<a href='$totpUrl' class='button' id='2fa-authenticator-link'>Go to authenticator app</a>";

    return $setupDetails;
}

/** 
 * Create a randow code and send it via e-mail to an user
 * 
 * @param   object  WP_User
*/
function sendEmailCode($user){
    $emailCode  = mt_rand(1000000000,9999999999);

    SIM\storeInTransient('2fa_email_key', $emailCode);

    $twoFaEmail = new TwoFaEmail($user, $emailCode);
	$twoFaEmail->filterMail();
						
	return wp_mail( $user->user_email, $twoFaEmail->subject, $twoFaEmail->message);
}

/**
 * Verify the submitted e-mail code
 * 
 * @return  bool    true if valid code false otherwise
 */
function verifyEmailCode(){
    if(SIM\getFromTransient('2fa_email_key') == $_POST['email-code']){
        SIM\deleteFromTransient('2fa_email_key');

        return true;
    }
    
    return false;
}

/**
 * Send an e-mail if two factor is not enabled and someone logs in
 * 
 * @param   object  $user       WP_User
 */
function send2faWarningEmail($user){
    //if this is the first time ever login we do not have to send a warning
    if(!get_user_meta($user->id, 'login_count', true)){
        return;
    }

    //Send e-mail
    $unsafeLogin    = new UnsafeLogin($user);
	$unsafeLogin->filterMail();
						
	wp_mail( $user->user_email, $unsafeLogin->subject, $unsafeLogin->message);
}

/**
 * Reset 2fa and send a message about it
 *
 * @param int   $userID
 */
function reset2fa($userId){
	global $wpdb;

	//Remove all 2fa keys
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '2fa%' AND user_id=$userId" );
	
	$userdata = get_userdata($userId);
    
	//Send e-mail
    $twoFaReset    = new TwoFaReset($userdata);
	$twoFaReset->filterMail();
						
	wp_mail( $userdata->user_email, $twoFaReset->subject, $twoFaReset->message);
}

// Check 2fa after user credentials are checked
add_filter( 'authenticate', __NAMESPACE__.'\authenticate', 40);
function authenticate( $user) {
    if(is_wp_error($user)){
        return $user;
    }
    
    $methods    = get_user_meta($user->ID, '2fa_methods');
    if(!empty($methods)){        
        //we did a succesfull webauthn or are on localhost
        if(
            $_SERVER['HTTP_HOST'] == 'localhost'  || 
            str_contains($_SERVER['HTTP_HOST'], '.local') || 
            SIM\getFromTransient('last-used-cred-id')
        ){
            //succesfull webauthentication done before
            return $user;
        }
        
        // We have an authenticator app set up and did not supply an e-mail code
        elseif(in_array('authenticator', $methods) && empty($_POST['email-code'])){
            $twofa      = new TwoFactorAuth(new BaconQrCodeProvider());
            $secretKey  = get_user_meta($user->ID, '2fa_key', true);        
            $authcode   = $_POST['authcode'];
            $last2fa    = get_user_meta($user->ID, '2fa_last', true);
            $timeslice  = 0; // will be filled by reference

            if(!is_numeric($authcode)){
                $user = new \WP_Error(
                    '2fa error',
                    'No 2FA code given'
                );
            }elseif($twofa->verifyCode($secretKey, $authcode, 1, null, $timeslice)){
                //timeslice should be larger then last2fa
                if($timeslice <= $last2fa){
                    $user = new \WP_Error(
                        '2fa error',
                        'Invalid 2FA code given'
                    );
                }else{
                    //store last time
                    update_user_meta($user->ID, '2fa_last', $last2fa);
                }
            }else{
                $user = new \WP_Error(
                    '2fa error',
                    'Invalid 2FA code given'
                );
            }
        }elseif(in_array('email', $methods)){
            if(!verifyEmailCode()){
                $user = new \WP_Error(
                    '2fa error',
                    'Invalid e-mail code given'
                );
            }
        }else{
            //we have setup an authenticator method but did not use it
            send2faWarningEmail($user);
        }
    }else{
        //no 2fa configured yet
        send2faWarningEmail($user);
    }

    return $user;
}

//Redirect to 2fa page if not setup
add_action('init', __NAMESPACE__.'\redirectTo2fa');
function redirectTo2fa(){
    // do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }

    $user		= wp_get_current_user();

    //If 2fa not enabled and we are not on the account page
    $methods	= get_user_meta($user->ID, '2fa_methods');

    if (
        is_user_logged_in()                             &&	// we are logged in
        !str_contains($user->user_email,'.empty')       && 	// we have a valid email
        !is_admin()                                     &&  // we are not on an admin page
        (
            !$methods                                   ||	// and we have no 2fa enabled or
            (
                !SIM\getFromTransient('last-used-cred-id')       &&  // the current login is not with webauth
                count($methods) == 1                    &&	// and we only have one 2fa method
                in_array('webauthn', $methods)				// and that method is webauthn
            )
        )
    ){
        $url		= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, '2fa-page');
        
        if(!$url){
            return;
        }

        $fromUrl    = SIM\currentUrl();

        if(str_replace(['http://', 'https://'], '', $fromUrl) != str_replace(['http://', 'https://'], '', $url)){            
            SIM\printArray("Redirecting from ".SIM\currentUrl()." to $url");
            wp_redirect($url);
            exit();
        }
    }
}