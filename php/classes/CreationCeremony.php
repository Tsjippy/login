<?php

namespace SIM\LOGIN;
use SIM;

use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Cose\Algorithms;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use DeviceDetector\Parser\OperatingSystem as OS_info;

/**
* Register a webauthn method
*/
class CreationCeremony extends WebAuthCeremony{
    public $verificationType;
    public $ceremonyRequestManager;
    
    public function __construct(){
        parent::__construct();

        $this->ceremonyRequestManager = $this->factory->creationCeremony();
    }
    
    /**
     * Creates the options needed to start creating a webauthn credtial
     */
    public function createOptions(){
        $excludedPublicKeyDescriptors = [];
        
        $existingCredentials = $this->getOSCredentials();
        foreach($existingCredentials as $credential){
            $excludedPublicKeyDescriptors[] = PublicKeyCredentialDescriptor::create('public-key', $credential->publicKeyCredentialId);
        }
        
         // Set authenticator type
        $authenticatorSelectionCriteria = AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey:AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );

        $publicKeyCredentialParametersList = [
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K), // More interesting algorithm
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),  //      ||
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),  //      || 
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256),  //      \/
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256),  // Less interesting algorithm
        ];
        
        $publicKeyCredentialCreationOptions =
            PublicKeyCredentialCreationOptions::create(
                $this->getRpEntity(),
                $this->getUserIdentity(),
                $this->getChallenge(),
                pubKeyCredParams: $publicKeyCredentialParametersList,
                excludeCredentials: $excludedPublicKeyDescriptors,
                authenticatorSelection: $authenticatorSelectionCriteria
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
        storeInTransient('publicKeyCredentialCreationOptions', $publicKeyCredentialCreationOptions);
        
        return json_decode($jsonObject);
    }
    
    /**
     * Verifies a credential creation response
     */
    public function verifyResponse($response, $identifier){
        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyRequestManager
        );

        // Parse the response to PublicKeyCredential Instance
        $this->loadPublicKey($response);
        
        // Check if the right class
        if (!$this->publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            //e.g. process here with a redirection to the public key creation page. 
            return new \WP_Error('sim-login', 'Invalid response try again');
        }

        // validate the response
        try{
            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
                $this->publicKeyCredential->response,
                getFromTransient('publicKeyCredentialCreationOptions'),
                $this->domain
            );
        }catch(\Exception $e){
            SIM\printArray($e->getMessage());

            return new \WP_Error('sim-login', $e->getMessage());
        }
        
        // store in db
        $this->storeCredential( $publicKeyCredentialSource, $identifier);

        return "Succesfully Stored The Credential";
    }
    
    protected function storeCredential( $data, $identifier): void {
        $keyMetas = get_user_meta($this->user->ID, "2fa_webautn_cred_meta");
        $credentials = get_user_meta($this->user->ID, "2fa_webautn_cred");

        /**
         * TODO: check for duplicate before adding
         */
        $meta = array(
            'cred_id'       => $data->publicKeyCredentialId,
            "identifier"    => $identifier,
            "os_info"       => $this->getOsInfo(),
            "added"         => date('Y-m-d H:i:s', current_time('timestamp')),
            "userHandle"    => $data->userHandle,
            "last_used"     => "-"
        );
        
        add_user_meta($this->user->ID, "2fa_webautn_cred_meta", base64_encode(serialize($meta)));
        
        add_user_meta($this->user->ID, "2fa_webautn_cred", base64_encode(serialize($data)));
    }
}