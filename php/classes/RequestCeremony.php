<?php

namespace SIM\LOGIN;
use SIM;

use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorAssertionResponse;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use WP_Error;

/**
* Register a webauthn method
*/
class RequestCeremony extends WebAuthCeremony{
    public $ceremonyRequestManager;
    
    public function __construct(){
        parent::__construct();
        
        $this->ceremonyRequestManager = $this->factory->requestCeremony();
    }
    
    /**
     * Creates and stores 
     */
    public function createOptions(){
        
        $allowedCredentials = [];

        if(!empty($_POST['username'])){
            $this->user = get_user_by('login', sanitize_text_field($_POST['username']));
 
            // List of registered PublicKeyCredentialDescriptor classes associated to the user
            $registeredAuthenticators = $this->getOSCredentials();
            
            $allowedCredentials = array_map(
                static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                    return $credential->getPublicKeyCredentialDescriptor();
                },
                $registeredAuthenticators
            ); // should be null for login without username
        }

        // Public Key Credential Request Options
        $publicKeyCredentialRequestOptions =
            PublicKeyCredentialRequestOptions::create(
                $this->getChallenge(),
                $this->domain,
                allowCredentials: $allowedCredentials,
                userVerification: $this->verificationType
            )
        ;

        SIM\storeInTransient('publicKeyCredentialRequestOptions', $publicKeyCredentialRequestOptions);

        $jsonObject = $this->serializer->serialize(
            $publicKeyCredentialRequestOptions,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
            ]
        );

        return json_decode($jsonObject);
    }

    public function passkeyLogin(){
        // get all passkey login users
        $usedIds    = get_option('sim-webauth-user-handles', []);

        // Find the user id by credential userhandle
        $userId         = $usedIds[$this->publicKeyCredential->response->userHandle];

        if(empty($userId)){
            SIM\storeInTransient('webauthn', 'failed');
            return new \WP_Error('webauthn',"Authenticator id not found");
        }

        $this->user           = get_userdata($userId);

        if(empty($this->user)){
            SIM\storeInTransient('webauthn', 'failed');

            return new \WP_Error('webauthn',"User not found");
        }

        $userNameAuth   = $this->user->user_login;

        SIM\storeInTransient("username", $userNameAuth);
        SIM\storeInTransient("allow_passwordless_login", true);
    }
    
    public function verifyResponse($response, $isPassKeyLogin){
        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyRequestManager
        );
        
        $this->loadPublicKey($response );
        
        if (!$this->publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            //e.g. process here with a redirection to the public key login/MFA page. 
            return;
        }

        if($isPassKeyLogin){
            $this->passkeyLogin();
        }else{
            $this->user = get_user_by('login', sanitize_text_field($_POST['username']));
        }
        
        $prevCredential = $this->getCredential( $this->publicKeyCredential->rawId );
        
        if (empty($prevCredential)) {
           // Throw an exception if the credential is not found.
           // It can also be rejected depending on your security policy (e.g. disabled by the user because of loss)
           return new WP_Error('sim-login', 'Credential not found!');
        }
        
        $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
            clone $prevCredential,
            $this->publicKeyCredential->response,
            SIM\getFromTransient('publicKeyCredentialRequestOptions'),
            $this->domain,
            $this->getUserIdentity()?->id // Should be `null` if the user entity is not known before this step
        );

        /** @disregard P1080 */
        if($publicKeyCredentialSource->counter <= $prevCredential->counter){
            return new WP_Error('sim-login', 'You cannot use this again, please refresh the page');
        }
        
        // Update the credential to keep track of the count
        $this->updateUserMeta("2fa_webautn_cred", $publicKeyCredentialSource, $prevCredential);

        /** @disregard P1080 */
        SIM\storeInTransient('last-used-cred-id', $publicKeyCredentialSource->publicKeyCredentialId);

        // Update the last used
        foreach($this->getCredentialMetas() as $meta){
            /** @disregard P1080 */
            if($meta['cred_id'] == $publicKeyCredentialSource->publicKeyCredentialId){
                $newMeta    = $meta;

                $newMeta['last_used']   = date('Y-m-d H:i:s', current_time('timestamp'));

                // Update the credential to keep track of the count
                $this->updateUserMeta("2fa_webautn_cred_meta", $newMeta, $meta);
            }
        }

        return "Verified";
    }
}