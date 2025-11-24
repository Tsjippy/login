<?php

namespace SIM\LOGIN;

use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
        
        

/**
* Register a webauthn method
*/
class RequestCeremony extends WebAuthCeremony{
    public $ceremonyRequestManager;
    
    public function __construct(){
        $this->ceremonyRequestManager = $thos->factory->requestCeremony();
    }
    
    public function createOptions(){
        // List of registered PublicKeyCredentialDescriptor classes associated to the user
        $registeredAuthenticators = $this->getOSCredentials();
        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $registeredAuthenticators
        );

        // Public Key Credential Request Options
        $publicKeyCredentialRequestOptions =
            PublicKeyCredentialRequestOptions::create(
                random_bytes(32), // Challenge
                allowCredentials: $allowedCredentials,
                userVerification: $this->requestOption
            )
        ;
    }
    
    public function verifyResponse(){
        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyRequestManager
        );
    }
}