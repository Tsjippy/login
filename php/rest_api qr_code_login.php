<?php
namespace SIM\LOGIN;
use SIM;
use RobThree\Auth\TwoFactorAuth;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use WP_Error;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', __NAMESPACE__.'\addQrLoginUrls');
function addQrLoginUrls($urls){
    $urls[] = RESTAPIPREFIX.'/login/get_login_qr_code';
    $urls[] = RESTAPIPREFIX.'/login/qr_code_scanned';

    return $urls;
}

add_action( 'rest_api_init', __NAMESPACE__.'\qrLoginRestApi');
function qrLoginRestApi() {
    // request qr image for login
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/get_login_qr_code',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getLoginQrCode',
			'permission_callback' 	=> '__return_true'
		)
	);

    // check if qr code has been scanned
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/qr_code_scanned',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\isQrCodeScanned',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'token'		=> array(
					'required'	=> true
				),
                'key'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // Stores the username for login
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/qr_code_username',
		array(
			'methods' 				=> 'POST, GET',
			'callback' 				=> __NAMESPACE__.'\submitUsernameForQrCode',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'token'		=> array(
					'required'	=> true
				),
                'key'		=> array(
					'required'	=> true
				)
			)
		)
	);
}

/**
 * Retrives a login qr code
 */
function getLoginQrCode(){
    // check if previous qr code has been scanned
    if(!empty($_POST['token'])){
        $result = isQrCodeScanned();

        // return the login instead of the a new qr code
        if(!empty($result)){
            return $result;
        }
    }

    $qrCodeLogin    = new QrCodeLogin();

    return $qrCodeLogin->getQrCode();
}

/**
 * Check if qr code has been scanned
 */
function isQrCodeScanned(){
    $oldToken       = $_POST['old-token'];
    $token          = $_POST['token'];
    $key            = $_POST['key'];
    $storedToken    = get_transient($key);

    $username   = get_transient($token);
    
    // check if previous token has been approved
    if(!$username){
        $username   = get_transient($oldToken);
    }

    // token expired or not qr code not yet scanned
    if($storedToken != $token || !$username){
        return '';
    }

    // perform the login
    $user   =  get_user_by( 'login', $username );
    if(!$user){
        return new WP_Error('login', 'Invalid login!');
    }

    storeInTransient("username", $username);
    storeInTransient("allow_passwordless_login", true);

    return userLogin();
}

/**
 * Sends the username to use for a qr code login
 */
function submitUsernameForQrCode(){    
    if(!is_user_logged_in()){
        if (!isset($_COOKIE[LOGGED_IN_COOKIE])) {
            return new WP_Error('no_cookie', 'Authentication cookie is missing.', array('status' => 401));
        }

        $cookie = $_COOKIE[LOGGED_IN_COOKIE];

        $userId = wp_validate_auth_cookie($cookie, 'logged_in');

        if (!$userId) {
            return new WP_Error('Login', 'Authentication cookie is invalid.', array('status' => 403));
        }

        $currentUser = get_user_by('id', $userId);

        if (!$currentUser) {
            return new WP_Error('Login', 'No user found for the given ID.', array('status' => 404));
        }

        wp_set_current_user($userId);
    }
    
    $username   = wp_get_current_user()->user_login;

    if(empty($username)){
        return new WP_Error('Login', 'No user found!');
    }
    
    $token          = $_REQUEST['token'];
    $key            = $_REQUEST['key'];
    $storedToken    = get_transient($key);

    if($storedToken != $token){
        return new WP_Error('login', 'Invalid login!');
    }
    
    // 5 minutes
    if(set_transient($token, $username, 300)){
        wp_redirect(get_home_url().'?message=QR code login succesfull!');
        exit;
    }else{
        return false;
    } 
}