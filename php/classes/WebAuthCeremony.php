<?php

namespace SIM\LOGIN;

use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\AuthenticatorSelectionCriteria;
use DeviceDetector\Parser\OperatingSystem as OS_info;

/**
* Register a webauthn method
*/
class WebAuthCeremony{
    public $verificationType;
    public $rpEntity;
    public $manager;
    public $serializer;
    public $publicKeyCredential;
    public $factory;
    public $user;
    public $credentials;
    public $credentialMetas;
    public $domain;
    public $userEntity;

    public function __construct(){
        $this->user = wp_get_current_user();
        
        $this->verificationType = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;

        // Doing a passkey login
        if(isset($_POST['username']) && empty($_POST['username'])){
            $this->verificationType = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
        }
        
        // The manager will receive data to load and select the appropriate 
        $this->manager          = AttestationStatementSupportManager::create();
        $this->manager->add(NoneAttestationStatementSupport::create());
        
        $factory                = new WebauthnSerializerFactory($this->manager);
        $this->serializer       = $factory->create();
        
        $this->factory          = new CeremonyStepManagerFactory();
        
        $this->domain           = $_SERVER['SERVER_NAME'];
    }
    
    /**
     * Creates a new rp entity
     *
     * @return  object the rprntity
     */
    public function getRpEntity(){
        if(!empty($this->rpEntity)){
            return $this->rpEntity;
        }

        $logo       = null;
        $path       = get_attached_file(get_option( 'site_icon' ));
        $type       = pathinfo($path, PATHINFO_EXTENSION);
        if(!empty($path)){
            $data = file_get_contents($path);
            if(!empty($contents)){
                $logo   = "data:image/$type;base64,".base64_encode($data);
            }
        }
    
        return $this->rpEntity = new PublicKeyCredentialRpEntity(
            get_bloginfo('name').' Webauthn Server', // The application name
            $this->domain,       // The application ID = the domain
            //$logo
            //picture from example on https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-party , does not work
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC'
        );
    }
    
    /**
     * Get the profile picture for use in the request
     */
    public function getProfilePicture($userId){
        $attachmentId  = get_user_meta($userId, 'profile_picture', true);
        $image          = null;
    
        if(is_numeric($attachmentId)){
            $path   = get_attached_file($attachmentId);
            if($path){
                $type       = pathinfo($path, PATHINFO_EXTENSION);
                $contents   = file_get_contents(get_attached_file($attachmentId));
                if(!empty($contents)){
                    $image = "data:image/$type;base64,".base64_encode($contents);
                }
            }
        }
    
        // test as it doesnt seem to work
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC';
    
        return $image;
    }
    
    /**
     * Create random strings for user ID
     *
     * @param   int $length
     *
     * @return  string  the string
     */
    public function getChallenge($length = 10){
        // Use cryptographically secure pseudo-random generator in PHP 7+
        if(function_exists('random_bytes')){
            $bytes = random_bytes(round($length/2));
            return bin2hex($bytes);
        }else{
            // Not supported, use normal random generator instead
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
            $randomString = '';
            for($i = 0; $i < $length; $i++){
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }
            return $randomString;
        }
    }
    
    /**
     *  Get user ID or create one
     */ 
    public function getUserIdentity(){
        if(isset($this->userEntity)){
            return $this->userEntity;
        }

        $webauthnKey = get_user_meta($this->user->ID, '2fa_webauthn_key', true);

        if(!$webauthnKey){
            $webauthnKey = hash("sha256", $this->user->user_login."-".$this->user->display_name."-".$this->getChallenge(10));
            update_user_meta($this->user->ID, '2fa_webauthn_key', $webauthnKey);
        }

        return $this->userEntity = new PublicKeyCredentialUserEntity(
            $this->user->user_login,
            $webauthnKey,
            $this->user->display_name,
            $this->getProfilePicture($this->user->ID)
        );
    }
    
    /**
     * Unserializes a public key json and converts it to a PublicKeyCredential instance
     */
    public function loadPublicKey($data){
        // $data corresponds to the JSON object showed above
        $this->publicKeyCredential = $this->serializer->deserialize(
            $data,
            PublicKeyCredential::class,
            'json'
        );
    }
    
    /**
    * Get all credentials
    */
    protected function getCredentials(): array {
        if(isset($this->credentials)){
            return $this->credentials;
        }
        
        $this->credentials = [];
        
        $userCreds  = (array)get_user_meta($this->user->ID, "2fa_webautn_cred");
        foreach($userCreds as $userCred){
            try{
                $this->credentials[] = unserialize(base64_decode($userCred));
            }catch(\Throwable $exception) {
                continue;
            }
        }
        
        return $this->credentials;
    }

    /**
     * Get a specific credential by id
     * 
     * @param   string  $id     The credential id string
     * 
     * @return  object|false    The credential or false if no credential found
     */
    public function getCredential($id){
        foreach($this->getCredentials() as $credential){
            if($credential->publicKeyCredentialId == $id){
                return $credential;
            }
        }

        // No credential found
        return false;
    }

    /**
    * Get all credential meta
    */
    protected function getCredentialMetas(): array {
        if(isset($this->credentialMetas)){
            return $this->credentialMetas;
        }
        
        $this->credentialMetas = [];
        
        $credMetas  = get_user_meta($this->user->ID, "2fa_webautn_cred_meta");
        foreach($credMetas as $credMeta){
            try{
                $this->credentialMetas[] = unserialize(base64_decode($credMeta));
            }catch(\Throwable $exception) {
                continue;
            }
        }
        
        return $this->credentialMetas;
    }

    /**
    * Get credential meta for a specific credential
    * 
    * @param    string  $credId     The id of the credential
    *
    * @return   array|false         The credential meta array
    */
    protected function getCredentialMetaById($credId): array|bool {
        $this->getCredentialMetas();

        foreach($this->credentialMetas as $credMeta){
            if($credMeta['cred_id'] == $credId){
                return $credMeta;
            }
        }
        
        return false;
    }
    
    /**
     * Prses os info from user agent
     */
    protected function getOsInfo(){
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
    
        $info = new OS_info($userAgent);
        return $info->parse();
    }
    
    /**
    * Get all credentials for this OS
    */
    public function getOSCredentials() {
        $credentials = [];

        //check if the platform matches
        $os         = $this->getOsInfo()['name'];

        foreach($this->getCredentials() as $data){
            if( $os == $this->getCredentialMetaById($data->publicKeyCredentialId)['os_info']['name']){
                $credentials[] = $data;
            }
        }

        return $credentials;
    }

    /**
     * Delete a credential
     */
    public function removeCredential($id){
        // remove the credential
        $this->getCredential($id);

        // store id for keypasslogin without username
        $usedIds    = get_option('sim-webauth-user-handles');
        if(!$usedIds){
            $usedIds    = [];
        }
        unset($usedIds[$_POST['key']]);
        update_option('sim-webauth-user-handles', $usedIds);

        return 'Succesfull removed the authenticator';
    }
}