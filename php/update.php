<?php
namespace SIM\LOGIN;
use SIM;


use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSource;

add_action('sim_login_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    

    SIM\printArray($oldVersion);

    if($oldVersion < '8.3.6'){
        foreach(get_users() as $user){
            $methods  = get_user_meta($user->ID, '2fa_methods', true);

            delete_user_meta($user->ID, '2fa_methods');

            if(is_array($methods)){
                foreach($methods as $method){
                    if(in_array($method, ['webauthn', 'authenticator', 'email'])){
                        add_user_meta($user->ID, '2fa_methods', $method);
                    }
                }
            }
        }
    }

    if($oldVersion < '8.4.9'){
        $users  = get_users([
            'meta_key'      => '2fa_webautn_cred_meta',
            'meta_compare'  => 'EXISTS'
        ]);

        foreach($users as $user){
            $userKey   = get_user_meta($user->ID, '2fa_webauthn_key', true);
            $userEntity = new PublicKeyCredentialUserEntity(
                $user->user_login,
                $userKey,
                $user->display_name
            );

            $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

            $list =  $publicKeyCredentialSourceRepository->getShowList($userEntity);

            $cresd  = $publicKeyCredentialSourceRepository->read();

            $metadata   = maybe_unserialize(get_user_meta($user->ID, "2fa_webautn_cred_meta", true));

            foreach($metadata as $id => $meta){
                $publicKeyCredentialSourceRepository->findOneByCredentialId($id);
            }
        }
    }
}

add_action('init', function(){
    //pluginUpdate('8.4.8');
});