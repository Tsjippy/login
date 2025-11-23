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
        
         // Set authenticator type
        $authenticatorType   = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM;
        //$authenticatorType = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;
        //$authenticatorType = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;

        // Set user verification
        //$userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
        $userVerification   = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        $residentKey        = AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED;

        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria();

        $authenticatorSelectionCriteria->setAuthenticatorAttachment($authenticatorType);
        $authenticatorSelectionCriteria->setRequireResidentKey(true);
        $authenticatorSelectionCriteria->setUserVerification($userVerification);
        $authenticatorSelectionCriteria->setResidentKey($residentKey);
        
        $publicKeyCredentialCreationOptions =
            PublicKeyCredentialCreationOptions::create(
                $this->rpEntity,
                $this->userEntity,
                $this->getChalenge(),
                excludeCredentials: $excludedPublicKeyDescriptors,
                $authenticatorSelectionCriteria
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
    
    public function verifyResponse($response){
        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyRequestManager
        );
        
        $this->loadPublicKey($response);
        
        if (!$this->publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            //e.g. process here with a redirection to the public key creation page. 
            return nee WP_Error('sim-login', 'Invalid response try again');
        }
        
        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            $response,
            $_SESSION['publicKeyCredentialCreationOptions'],
            $_SERVER['SERVER_NAME']
        );
        
        // store in db
        save_user_meta($this->user->ID, 'publicKeyCredentialSource', $publicKeyCredentialSource);
    }
}