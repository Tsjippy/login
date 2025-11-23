<?php

namespace LOGIN;

/**
* Register a webauthn method
*/
class CreationCeremony{
    public $verificationType;
    
    public function __construct(){
        $this->verificationType = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
    }
    
    public function createOptions(){
    }
    
    public function verifyResponse(){
        
    }
}