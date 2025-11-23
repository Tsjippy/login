<?php

namespace SIM\LOGIN;

use Webauthn\AuthenticatorAssertionResponseValidator;


/**
* Register a webauthn method
*/
class RequestCeremony{
    public $ceremonyRequestManager;
    
    public function __construct(){
        $this->ceremonyRequestManager = $thos->factory->requestCeremony();
    }
    
    public function createOptions(){
    }
    
    public function verifyResponse(){
        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyRequestManager
        );
    }
}