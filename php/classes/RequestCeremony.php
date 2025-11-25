<?php

namespace SIM\LOGIN;

use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorAssertionResponse;

        

/**
* Register a webauthn method
*/
class RequestCeremony extends WebAuthCeremony{
    public $ceremonyRequestManager;
    
    public function __construct(){
        parent::__construct();
        
        $this->ceremonyRequestManager = $this->factory->requestCeremony();
    }
    
    public function createOptions($identifier){
        // Store identifier in session for next step
        $_SESSION['identifier'] = $identifier;

        // List of registered PublicKeyCredentialDescriptor classes associated to the user
        $registeredAuthenticators = $this->getOSCredentials();
        
        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $registeredAuthenticators
        ); // should be null for login without username

        // Public Key Credential Request Options
        return $_SESSION['publicKeyCredentialRequestOptions'] =
            PublicKeyCredentialRequestOptions::create(
                random_bytes(32), // Challenge
                allowCredentials: $allowedCredentials,
                userVerification: $this->verificationType
            )
        ;
    }
    
    public function verifyResponse($response){
        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyRequestManager
        );
        
        $this->loadPublicKey($response );
        
        if (!$this->publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            //e.g. process here with a redirection to the public key login/MFA page. 
            return;
        }
        
        $publicKeyCredentialSource = $this->getCredential(
            $this->publicKeyCredential->rawId
        );
        
        if ($publicKeyCredentialSource === null) {
           // Throw an exception if the credential is not found.
           // It can also be rejected depending on your security policy (e.g. disabled by the user because of loss)
           return;
        }
        
        $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
            $publicKeyCredentialSource,
            $response,
            $_SESSION['publicKeyCredentialRequestOptions'],
            $this->domain,
            $this->getUserIdentity()?->id // Should be `null` if the user entity is not known before this step
        );
        
        // Optional, but highly recommended, you can save the credential source as it may be modified
        // during the verification process (counter may be higher).
        //$this->saveCredential($publicKeyCredentialSource);
        
    }
}