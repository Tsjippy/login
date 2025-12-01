<?php
namespace SIM\LOGIN;
use SIM;
use WP_Error;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', __NAMESPACE__.'\addLoginUrls');
function addLoginUrls($urls){
    $urls[] = RESTAPIPREFIX.'/login/check-cred';
    $urls[] = RESTAPIPREFIX.'/login/request_login';
    $urls[] = RESTAPIPREFIX.'/login/request_pwd_reset';
    $urls[] = RESTAPIPREFIX.'/login/update_password';
    $urls[] = RESTAPIPREFIX.'/login/request_user_account';

    return $urls;
}

add_action( 'rest_api_init', __NAMESPACE__.'\loginRestApi');
function loginRestApi() {
    // check credentials
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/check-cred',
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> __NAMESPACE__.'\checkCredentials',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'username'		=> array(
					'required'	=> true
				),
                'password'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // request_login
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/request_login',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\userLogin',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'username'		=> array(
					'required'	=> true
				),
                'password'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // logout
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/logout',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
                wp_logout();
                return 'Log out success';
            },
			'permission_callback' 	=> '__return_true',
		)
	);

    // request_pwd_reset
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/request_pwd_reset',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\requestPasswordReset',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'username'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // update password
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/update_password',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\processPasswordUpdate',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'user-id'		=> array(
					'required'	=> true,
                    'validate_callback' => function($userId){
						return is_numeric($userId);
					}
				),
                'pass1'		=> array(
					'required'	=> true
				),
                'pass2'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // request_user_account
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/request_user_account',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\requestUserAccount',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'first-name'		=> array(
					'required'	=> true
				),
                'last-name'		=> array(
					'required'	=> true
				),
                'email'		=> array(
					'required'	=> true
				)
			)
		)
	);
}

add_filter( 'check_password', __NAMESPACE__.'\checkPassword', 10, 4);
function checkPassword($check, $password, $storedHash, $userId ){
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

        $check  = $hash === $storedHash;
        if($check){
            wp_set_password( $password, $userId );
        }
    }

    return $check;
}

// Verify username and password
function checkCredentials(){
    $username   = sanitize_text_field($_POST['username']);
    $password   = sanitize_text_field($_POST['password']);

    $user       = get_user_by('login', $username);

    $user       = apply_filters( 'sim-after-user-check', $user);

    if(is_wp_error($user)){
        return $user;
    }

    //validate credentials
    if($user && wp_check_password($password, $user->data->user_pass, $user->ID)){
        //get 2fa methods for this user
        $methods  = get_user_meta($user->ID, '2fa_methods');

        if(!in_array('webauthn', $methods)){
            SIM\storeInTransient('webauthn', 'failed');
        }

        //return the methods
        if(!empty($methods)){
            return array_values($methods);
        //no 2fa setup yet, login straight away
        }else{
            return userLogin();
        }
    }

    return new WP_Error('login', 'Invalid credentials!');
}

// function to update the $_COOKIE variable without refreshing the page
// Needed to create a nonce after ajax login
function storeInCookieVar($loggedInCookie, $expire, $expiration, $userId, $type, $token){
    // make sure we only write the right cookie
    //if(get_current_user_id() == $userId){
        $_COOKIE[ LOGGED_IN_COOKIE ] = $loggedInCookie;
    //}
}

/**
 * Perform the login
 * 
 * @return array        Array of 'redirect'  => $redirect,  'message'   => $message, 'nonce'     => wp_create_nonce('wp_rest'),  'id'        => $user->ID
 **/
function userLogin(){
    $username       = !empty($_REQUEST['username'])   ? sanitize_text_field($_REQUEST['username'])   : '';
    $password       = !empty($_REQUEST['password'])   ? sanitize_text_field($_REQUEST['password'])   : '';
    $remember       = !empty($_REQUEST['rememberme']) ? sanitize_text_field($_REQUEST['rememberme']) : true;

    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    );

    // add a filter to allow passwordless sign in
    add_filter( 'authenticate', __NAMESPACE__.'\allowPasswordlessLogin', 999, 3 );

    // Add action to store the login cookie in $_COOKIE
    add_action( 'set_logged_in_cookie', __NAMESPACE__.'\storeInCookieVar', 10, 6 );

    // perform the login
    $user = wp_signon( $creds);

    // Remove action to store the login cookie in $_COOKIE
    remove_action( 'set_logged_in_cookie', __NAMESPACE__.'\storeInCookieVar' );

    // remove the filter to allow passwordless sign in
    remove_filter( 'authenticate', __NAMESPACE__.'\allowPasswordlessLogin', 999, 3 );

    if ( is_wp_error( $user ) ) {
        return new WP_Error('Login error', $user->get_error_message());
    }

    // make sure we set the current user to the just logged in user
    wp_set_current_user($user->ID);    

    //Update the current logon count
    $currentLoginCount = get_user_meta( $user->ID, 'login_count', true );
    if(is_numeric($currentLoginCount)){
        $loginCount = intval( $currentLoginCount ) + 1;
    }else{
        //it is the first time a user logs in
        $loginCount = 1;

        //Save the first login data
        update_user_meta( $user->ID, 'first_login', time() );

        //Get the account validity
        $validity = get_user_meta( $user->ID, 'account_validity', true);

        //If the validity is set in months
        if(is_numeric($validity)){
            //Get the timestamp of today plus X months
            $expiryTime = strtotime("+$validity month", time());

            //Convert to date
            $expiryDate = date('Y-m-d', $expiryTime);

            //Save the date
            update_user_meta( $user->ID, 'account_validity', $expiryDate);
        }
    }
    update_user_meta( $user->ID, 'login_count', $loginCount );

    //store login date
    update_user_meta( $user->ID, 'last_login_date', date('Y-m-d'));

    /* check if we should redirect */
    $redirect   = '';

    // check url query arguments
    $urlComp    = parse_url($_SERVER['HTTP_REFERER']);

    // redirect to the current page if not the home page
    if(SIM\getCurrentUrl() != get_home_url()){
        $redirect   = SIM\getCurrentUrl();
    }
    // Redirect from rest api
    elseif(isset($urlComp['query'])){
        parse_str($urlComp['query'], $urlParam);

        if(isset($urlParam['redirect'])){
            $redirect   = $urlParam['redirect'];
        }
    }
    // Redirect from url
    elseif(!empty($_GET['redirect'])){
        $redirect   = $_GET['redirect'];
    }

    $redirect   = apply_filters('login_redirect', $redirect, $redirect, $user);

    // check if we are an admin that needs to confirm its details
    $result     = checkAdminDetails($user);

    if($result){
        $redirect   = $result;
    }

    $message    = 'Login successful';

    return [
        'redirect'  => $redirect,
        'message'   => $message,
        'nonce'     => wp_create_nonce('wp_rest'),
        'id'        => $user->ID
    ];
}

// Send password reset e-mail
function requestPasswordReset(){
    $username   = sanitize_text_field($_POST['username']);

	$user	= get_user_by('login', $username);
    if(!$user){
        return new WP_Error('Username error', 'Invalid username');
    }

	$email  = $user->user_email;
    if(!$email || str_contains('.empty', $email)){
        return new WP_Error('email error', "No valid e-mail found for user $username");
    }

    $errors = new \WP_Error();
    $errors = apply_filters( 'lostpassword_errors', $errors, $user );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	$result = sendPasswordResetMessage($user);

    if(is_wp_error($result)){
        return new WP_Error('pw reset error', $result->get_error_message());
    }

	return "Password reset link send to $email";
}

//Save a new password
function processPasswordUpdate(){
	$userId	= $_POST['user-id'];

	$user   = get_userdata($userId);
	if(!$user){
        return new WP_Error('user-id error','Invalid user id given');
    }

	if($_POST['pass1'] != $_POST['pass2']){
        return new WP_Error('Password error', "Passwords do not match, try again.");
    }

    add_filter('application_password_is_api_request', '__return_false');
	
	wp_set_password( $_POST['pass1'], $userId );

    $message    = 'Changed password succesfully';
    if(is_user_logged_in()){
        if(get_current_user_id() == $userId){
            $message .= ', please login again';
        }else{
            $message .= " for $user->display_name";
        }
    }
	return [
        'message'	=> $message,
        'redirect'	=> SITEURL."/?showlogin=$user->user_login"
    ];
}

// Request user account.
function requestUserAccount(){
	$firstName	= $_POST['first-name'];
	$lastName	= $_POST['last-name'];
	$email	    = $_POST['email'];
	$pass1	    = $_POST['pass1'];
	$pass2	    = $_POST['pass2'];

	if($pass1 != $pass2){
        return new WP_Error('Password error', "Passwords do not match, try again.");
    }

	$username	= SIM\getAvailableUsername($firstName, $lastName);

	// Creating account
	//Build the user
	$userdata = array(
		'user_login'    => $username,
		'last_name'     => $lastName,
		'first_name'    => $firstName,
		'user_email'    => $email,
		'display_name'  => "$firstName $lastName",
	);

    if(!empty($pass1)){
        $userdata['user_pass']     = $pass1;
    }

    $errors = new \WP_Error();
    $errors = apply_filters( 'registration_errors', $errors, $userdata );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	//Insert the user
	$userId = wp_insert_user( $userdata ) ;
	
	if(is_wp_error($userId)){
		SIM\printArray($userId->get_error_message());
		return new WP_Error('User insert error', $userId->get_error_message());
	}

	// Disable the useraccount until approved by admin
	update_user_meta( $userId, 'disabled', 'pending' );

	return 'Useraccount successfully created, you will receive an e-mail as soon as it gets approved.';
}

/**
  * An 'authenticate' filter callback that authenticates the user using only the username.
  *
  * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
  * and unhooked immediately after it fires.
  * 
  * @param WP_User $user
  * @param string $username
  * @param string $password
  * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
*/
function allowPasswordlessLogin( $user, $username, $password ) {
    
    $ignoreCodes    = [
        "invalid_username",
        "incorrect_password",
        "empty_username",
        'empty_password'
    ];

    // If the user is an error object with an arror code not in the ignore list, return it
    if(is_wp_error($user) && array_diff($user->get_error_codes(), $ignoreCodes)){
        return $user;
    }

    session_start();

    if(SIM\getFromTransient('allow_passwordless_login')){
        SIM\deleteFromTransient('allow_passwordless_login');
        SIM\deleteFromTransient('webauthn');
        SIM\deleteFromTransient('user');

        session_write_close();

        $user   =  get_user_by( 'login', SIM\getFromTransient('username') );

        return $user;
    }

    return $user;
}

/**
 * Function to redirect an admin to a page to confirm his e-mail address
 * Taken from wp-login.php
 *
 * @param   object  $user       WP User object
 *
 * @return  string|bool         Returns a url to redirect to or false if check is not needed
 */
function checkAdminDetails($user){
    // Check if it is time to add a redirect to the admin email confirmation screen.
    if ( $user instanceof \WP_User && $user->exists() && $user->has_cap( 'manage_options' ) ) {
        $adminEmailLifespan = (int) get_option( 'admin_email_lifespan' );

        /*
        * If `0` (or anything "falsey" as it is cast to int) is returned, the user will not be redirected
        * to the admin email confirmation screen.
        */
        /** This filter is documented in wp-login.php */
        $adminEmailCheckInterval = (int) apply_filters( 'admin_email_check_interval', 6 * MONTH_IN_SECONDS );

        if ( $adminEmailCheckInterval > 0 && time() > $adminEmailLifespan ) {
            if ( isset( $_REQUEST['redirect_to'] ) && is_string( $_REQUEST['redirect_to'] ) ) {
                $redirectTo = $_REQUEST['redirect_to'];
            } else {
                $redirectTo = admin_url();
            }

            $requestedRedirectTo = isset( $_REQUEST['redirect_to'] ) && is_string( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';

            /**
             * Filters the login redirect URL.
             *
             * @since 3.0.0
             *
             * @param string           $redirectTo              The redirect destination URL.
             * @param string           $requestedRedirectTo     The requested redirect destination URL passed as a parameter.
             * @param WP_User|WP_Error $user                    WP_User object if login was successful, WP_Error object otherwise.
             */
            $redirectTo = apply_filters( 'login_redirect', $redirectTo, $requestedRedirectTo, $user );

            $redirectTo = add_query_arg(
                array(
                    'action'  => 'confirm_admin_email',
                    'wp_lang' => get_user_locale( $user ),
                ),
                site_url( 'wp-login.php', 'login' )
            );

            return $redirectTo;
        }
    }

    return false;
}