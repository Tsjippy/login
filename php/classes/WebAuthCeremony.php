<?php

namespace SIM\LOGIN;

use Webauthn\PublicKeyCredentialRpEntity;

/**
* Register a webauthn method
*/
class WebAuthCeremony{
    public $verificationType;
    public $rpEntity;
    
    public function __construct(){
        $this->verificationType = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
    }
    
    /**
     * Creates a new rp entity
     *
     * @return  object the rprntity
     */
    public function getRpEntity(){
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
            $_SERVER['SERVER_NAME'],       // The application ID = the domain
            //$logo
            //picture from example on https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-party , does not work
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC'
        );
    }
}