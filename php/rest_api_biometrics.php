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
            'methods'               => 'POST,GET',
            'callback'              => function(){
                $creationCeremony    = new CreationCeremony();
                return $creationCeremony->createOptions();
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
                    return new WP_Error('Logged in error', "No credential id given");
                }

                $creationCeremony    = new CreationCeremony();
                return $creationCeremony->verifyResponse($credential, sanitize_text_field($_POST['identifier']));
            },
            'permission_callback'   => '__return_true',
            'args'					=> array(
				'publicKeyCredential'		=> array(
					'required'	=> true
				),
                'identifier'		=> array(
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
            'callback' => __NAMESPACE__.'\startAuthentication',
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
            'callback' => __NAMESPACE__.'\finishAuthentication',
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
			'callback' 				=> function(){  
                $webAuthCeremony = new webAuthCeremony();
                $webAuthCeremony->removeCredential(sanitize_text_field($_POST['key']));
            },
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

// Auth challenge
function startAuthentication(){
    try{
        if(empty($_POST['username'])){

            $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
                random_bytes(32),
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
            );

            storeInTransient("pkcco_auth", $publicKeyCredentialRequestOptions);
            deleteFromTransient("user_name_auth");
            deleteFromTransient("user_auth");
            deleteFromTransient("user");

            return $publicKeyCredentialRequestOptions;
        }

        if(is_numeric($_POST['username'])){
            $user           = get_user_by('id', $_POST['username']);
        }else{
            $user           = get_user_by('login', $_POST['username']);
        }

        if(!$user){
            return new WP_Error('User error', "No user with user name {$_POST['username']} found.");
        }

        $webauthnKey    = get_user_meta($user->ID, '2fa_webauthn_key', true);

        //User has no webauthn yet
        if(!$webauthnKey){
            //indicate a failed webauth for content filtering
            storeInTransient('webauthn', 'failed');
            return;
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            getProfilePicture($user->ID)
        );

        $credentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $server = new Server(
            getRpEntity(),
            $credentialSourceRepository,
            null
        );

        // Get the list of authenticators associated to the user
        $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);

        // If the user haven't bind a authenticator yet, exit
        if(count($credentialSources) === 0){
            SIM\printArray("ajax_auth: (ERROR)No authenticator found");
            SIM\printArray($userEntity);
            return new WP_Error('authenticator error', "No authenticator available");
        }

        // Convert the Credential Sources into Public Key Credential Descriptors for excluding
        $allowedCredentials = array_map(function(PublicKeyCredentialSource $credential){
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);

        // Set user verification
        $userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
        //$userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
        //$userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;

        // Create a auth challenge
        $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
            $userVerification,
            $allowedCredentials
        );

        // Save for future use
        storeInTransient("pkcco_auth", $publicKeyCredentialRequestOptions);
        storeInTransient("user_name_auth", $user->user_login);
        storeInTransient("user_auth", $userEntity);
        storeInTransient("user", $user);

        return $publicKeyCredentialRequestOptions;
    }catch(\Exception $exception){
        SIM\printArray("ajax_auth: (ERROR)".$exception->getMessage());
        SIM\printArray(generateCallTrace($exception));
        return new WP_Error('webauthn error',"Something went wrong 5.");
    }catch(\Error $error){
        SIM\printArray("ajax_auth: (ERROR)".$error->getMessage());
        SIM\printArray(generateCallTrace($error));
        return new WP_Error('webauthn error',"Something went wrong 6.");
    }
}

// Verify webauthn
function finishAuthentication(){
    try{
        $publicKeyCredential                = sanitize_text_field(stripslashes($_POST['publicKeyCredential']));
        $publicKeyCredentialRequestOptions  = getFromTransient("pkcco_auth");
        $userNameAuth                       = getFromTransient("user_name_auth");
        $userEntity                         = getFromTransient("user_auth");
        $user                               = getFromTransient("user");

        // May not get the challenge yet
        if(empty($publicKeyCredentialRequestOptions)){
            SIM\printArray("ajax_auth_response: (ERROR)Challenge not found in transient, exit");
            return new WP_Error('webauthn',"Bad request.");
        }

        // check if doing passkey login
        if(empty($user) || empty($userNameAuth) || empty($userEntity)){
            $usedIds    = get_option('sim-webauth-ids');
            if(!$usedIds){
                $usedIds    = [];
            }

            $userId = $usedIds[json_decode(stripslashes($_POST['publicKeyCredential']))->rawId];
            if(empty($userId)){
                storeInTransient('webauthn', 'failed');
                return new WP_Error('webauthn',"Authenticator id not found");
            }

            $user           = get_userdata($userId);

            if(empty($userId)){
                storeInTransient('webauthn', 'failed');
                return new WP_Error('webauthn',"User not found");
            }

            $userNameAuth   = $user->user_login;

            storeInTransient("username", $userNameAuth);
            storeInTransient("allow_passwordless_login", true);
        }

        $psr17Factory   = new Psr17Factory();
        $creator        = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $serverRequest  = $creator->fromGlobals();
        $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        // If user entity is not saved, read from WordPress
        $webauthnKey   = get_user_meta($user->ID, '2fa_webauthn_key', true);
        if(!$webauthnKey){
            storeInTransient('webauthn', 'failed');
            return new WP_Error('webauthn', "User not inited.");
        }

        if(empty($userEntity)){
            $userEntity = new PublicKeyCredentialUserEntity(
                $user->user_login,
                $webauthnKey,
                $user->display_name,
                getProfilePicture($user->ID)
            );
        }

        $server = new Server(
            getRpEntity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Allow to bypass scheme verification when under localhost
        $currentDomain = $_SERVER['HTTP_HOST'];
        if($currentDomain === "localhost" || $currentDomain === "127.0.0.1" || str_contains($_SERVER['HTTP_HOST'], '.local')){
            $server->setSecuredRelyingPartyId([$currentDomain]);
        }

        // Verify
        try {
            $server->loadAndCheckAssertionResponse(
                $publicKeyCredential,
                $publicKeyCredentialRequestOptions,
                $userEntity,
                $serverRequest
            );

            // Store last used
            $publicKeyCredentialSourceRepository->updateCredentialLastUsed($publicKeyCredential);

            storeInTransient('webauthn', 'success');

            return true;
        }catch(\Throwable $exception){
            // Failed to verify
            SIM\printArray("ajax_auth_response: (ERROR)".$exception->getMessage());
            SIM\printArray(generateCallTrace($exception));
            return new WP_Error('webauthn', $exception->getMessage());
        }
    }catch(\Exception $exception){
        SIM\printArray("ajax_auth_response: (ERROR)".$exception->getMessage());
        SIM\printArray(generateCallTrace($exception));
        return new WP_Error('webauthn', $exception->getMessage());
    }catch(\Error $error){
        SIM\printArray("ajax_auth_response: (ERROR)".$error->getMessage());
        SIM\printArray(generateCallTrace($error));
        return new WP_Error('webauthn', $error->getMessage());
    }
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