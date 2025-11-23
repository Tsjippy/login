<?php

namespace LOGIN;

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
    }
    
    public function verifyResponse(){
        
    }
}