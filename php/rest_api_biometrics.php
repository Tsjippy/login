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
use ptlis\SerializedDataEditor\Editor;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', __NAMESPACE__.'\addBioUrls');
function addBioUrls($urls){
    $urls[]	= RESTAPIPREFIX.'/login/auth_finish';
    $urls[]	= RESTAPIPREFIX.'/login/auth_start';
    $urls[] = RESTAPIPREFIX.'/login/request_email_code';
    $urls[] = RESTAPIPREFIX.'/login/mark_bio_as_failed';

    return $urls;
}

add_action( 'rest_api_init', __NAMESPACE__.'\bioRestApi');
function bioRestApi() {
    // Send authentication request for storing fingerprint
	register_rest_route(
        RESTAPIPREFIX.'/login',
        '/fingerprint_options',
        array(
            'methods'               => 'POST',
            'callback'              => function(){
                $ceremony    = new CreationCeremony();
                return $ceremony->createOptions();
            },
            'permission_callback'   => '__return_true'
		)
	);

    // Verify and store fingerprint
    register_rest_route(
        RESTAPIPREFIX.'/login',
        '/store_fingerprint',
        array(
            'methods'               => 'POST,GET',
            'callback'              => function(){
                $credential  = base64_decode(sanitize_text_field($_POST["publicKeyCredential"]));

                // Check param
                if(empty($credential)){
                    return new WP_Error('Logged in error', "No credential data supplied");
                }

                $ceremony    = new CreationCeremony();
                return $ceremony->verifyResponse($credential, sanitize_text_field($_POST['identifier']));
            },
            'permission_callback'   => '__return_true',
            'args'					=> array(
				'publicKeyCredential'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // Send authentication request for login
    register_rest_route(
        RESTAPIPREFIX.'/login',
        '/auth_start',
        array(
            'methods' => 'POST',
            'callback' => function(){
                $ceremony    = new RequestCeremony();
                return $ceremony->createOptions();
            },
            'permission_callback' => '__return_true',
            'args'					=> array(
				'username'		=> array(
					'required'	=> true
				),
			)
		)
	);

    //verify fingerprint for login
    register_rest_route(
        RESTAPIPREFIX.'/login',
        '/auth_finish',
        array(
            'methods' => 'POST,GET',
            'callback' => function(){
                $credential  = base64_decode(sanitize_text_field($_POST["publicKeyCredential"]));

                // Check param
                if(empty($credential)){
                    return new WP_Error('Logged in error', "No credential data supplied");
                }

                $isPassKeyLogin = false;
                if(empty($_POST['username'])){
                    $isPassKeyLogin = true;
                }

                $ceremony       = new RequestCeremony();
                return $ceremony->verifyResponse($credential, $isPassKeyLogin);
            },
            'permission_callback' => '__return_true',
            'args'					=> array(
				'publicKeyCredential'		=> array(
					'required'	=> true
				),
			)
		)
	);

	// send email code
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/request_email_code',
		array(
			'methods' 				=> 'POST, GET',
			'callback' 				=>  __NAMESPACE__.'\requestEmailCode',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'username'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // save_2fa_settings
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/save_2fa_settings',
		array(
			'methods' 				=> 'GET,POST',
			'callback' 				=> __NAMESPACE__.'\saveTwoFaSettings',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'2fa_methods'		=> array(
					'required'	=> true,
                    'validate_callback' => function($param) {
						return is_array($param);
					}
				)
			)
		)
	);

    // remove_web_authenticator
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/remove_web_authenticator',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\removeWebAuthenticator',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'key'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // Mark Biometrics login as failed
    register_rest_route(
		RESTAPIPREFIX.'/login',
		'/mark_bio_as_failed',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
                storeInTransient('webauthn', 'failed');

                return 'Marked as failed';
            },
			'permission_callback' 	=> '__return_true',
		)
	);
}

function requestEmailCode(){
    $username   = sanitize_text_field($_REQUEST['username']);
    if(is_numeric($username)){
        $user       = get_user_by('id', $username);
    }else{
        $user       = get_user_by('login', $username);
    }

    if($user){
        $result = sendEmailCode($user);

        if($result){
            return "E-mail sent to ".$user->user_email;
        }
        return new WP_Error('login', 'Sending e-mail failed');
    }else{
        return new WP_Error('login', 'Invalid username given');
    }
}

function removeWebAuthenticator(){
    $key        = sanitize_text_field($_POST['key']);
    $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository(wp_get_current_user());
    $publicKeyCredentialSourceRepository->removeCredential($key);

    // store id for keypasslogin without username
    $usedIds    = get_option('sim-webauth-ids');
    if(!$usedIds){
        $usedIds    = [];
    }
    unset($usedIds[$_POST['key']]);
    update_option('sim-webauth-ids', $usedIds);

    return 'Succesfull removed the authenticator';
}

add_filter( 'check_password', __NAMESPACE__.'\checkBioPassword', 10, 4);
function checkBioPassword($check, $password, $storedHash, $userId ){
    if(empty($check) && empty($storedHash)){
        $user           = get_user_by('id', $userId);
        $storedHash    = $user->data->user_pass;

        global $wp_hasher;

        if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			// By default, use the portable hash from phpass.
			$wp_hasher = new \PasswordHash( 8, true );
		}

		if ( strlen( $password ) > 4096 ) {
			return false;
		}

		$hash = $wp_hasher->crypt_private($password, $storedHash);

		if ($hash[0] === '*')
			$hash = crypt($password, $stored_hash);

        //SIM\printArray(wp_hash_password( $password ));
        //SIM\printArray($storedHash);
        //SIM\printArray($hash);

        $check  = $hash === $storedHash;
        if($check){
            wp_set_password( $password, $userId );
            //SIM\printArray($userId);
        }
    }

    return $check;
}

// Save 2fa options
function saveTwoFaSettings(){
    $userId         = get_current_user_id();

    $newMethods     = $_POST['2fa-methods'];

    $oldMethods     = get_user_meta($userId, '2fa_methods');

    $twofa          = new TwoFactorAuth();

    $message        = 'Nothing to update';

    //we just enabled the authenticator
    if(in_array('authenticator', $newMethods) && !in_array('authenticator', $oldMethods)){
        $secret     = $_POST['auth-secret'];
        $secretkey  = $_POST['secretkey'];
        $hash       = get_user_meta($userId,'2fa_hash',true);

        //we should have submitted a secret
        if(empty($secret)){
            return new WP_Error('No code',"You have to submit a code when setting up the authenticator");
        }

        //we should not have changed the secretkey
        if(!password_verify($secretkey,$hash)){
            return new WP_Error('Secretkey error',"Why do you try to hack me?");
        }

        $last2fa        = '';
        if($twofa->verifyCode($secretkey, $secret, 1, null, $last2fa)){
            //store in usermeta
            update_user_meta($userId, '2fa_key', $secretkey);
            update_user_meta($userId, '2fa_last', $last2fa);
        }else{
            return new WP_Error('Invalid 2fa code', "Your code is expired");
        }

        add_user_meta($userId, '2fa_methods', 'authenticator');

        $message    = "Succesfully enabled authenticator as a second factor";
    }

    //we just enabled email verification
    if(in_array('email', $newMethods) && !in_array('email', $oldMethods)){
        // verify the code
        if(verifyEmailCode()){
            $userdata   = get_userdata($userId);

            //Send e-mail
            $emailVerfEnabled    = new EmailVerfEnabled($userdata);
            $emailVerfEnabled->filterMail();

            wp_mail( $userdata->user_email, $emailVerfEnabled->subject, $emailVerfEnabled->message);

            $message    = 'Enabled e-mail verification';
        }else{
            return new WP_Error('login', 'Invalid e-mail code');
        }

        add_user_meta($userId, '2fa_methods', 'email');
    }

    return $message;
}