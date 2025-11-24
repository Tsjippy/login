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
                userVerification: $this->verificationType
            )
        ;
    }
    
    public function verifyResponse(){
        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyRequestManager
        );
        
        $this->loadPublicKey($data );
        
        if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            //e.g. process here with a redirection to the public key login/MFA page. 
        }
        
        $publicKeyCredentialSource = $publicKeyCredentialSourceRepository->findOneByCredentialId(
            $publicKeyCredential->rawId
        );
        
        if ($publicKeyCredentialSource === null) {
           // Throw an exception if the credential is not found.
           // It can also be rejected depending on your security policy (e.g. disabled by the user because of loss)
        }
        
        $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
            $publicKeyCredentialSource,
            $authenticatorAssertionResponse,
            $publicKeyCredentialRequestOptions,
            $this->domain,
            $userEntity?->id // Should be `null` if the user entity is not known before this step
        );
        
        // Optional, but highly recommended, you can save the credential source as it may be modified
        // during the verification process (counter may be higher).
        $publicKeyCredentialSourceRepository->saveCredential($publicKeyCredentialSource);
        
    }
}