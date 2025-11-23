<?php

namespace SIM\LOGIN;

use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponse;


/**
* Register a webauthn method
*/
class CreationCeremony extends WebAuthCeremony{
    public $verificationType;
    public $ceremonyRequestManager;
    
    public function __construct(){
        $this->ceremonyRequestManager = $this->factory->creationCeremony();

        $this->verificationType = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
    }
    
    public function createOptions(){
        $excludedPublicKeyDescriptors = [
            PublicKeyCredentialDescriptor::create('public-key', 'CREDENTIAL ID1'),
            PublicKeyCredentialDescriptor::create('public-key', 'CREDENTIAL ID2'),
            ...
        ];
        
        $publicKeyCredentialCreationOptions =
            PublicKeyCredentialCreationOptions::create(
                $this->rpEntity,
                $this->userEntity,
                $this->getChalenge(),
                excludeCredentials: $excludedPublicKeyDescriptors,
            )
        ;
        
        $jsonObject = $this->serializer->serialize(
            $publicKeyCredentialCreationOptions,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
            ]
        );
        
        // store in session
        $_SESSION['publicKeyCredentialCreationOptions'] = $publicKeyCredentialCreationOptions;
        
        return $jsonObject;
    }
    
    public function verifyResponse(){
        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyRequestManager
        );
        
        if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            //e.g. process here with a redirection to the public key creation page. 
        }
        
        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            $authenticatorAttestationResponse,
            $_SESSION['publicKeyCredentialCreationOptions'],
            'my-application.com'
        );
        
        // store in db
        
    }
}