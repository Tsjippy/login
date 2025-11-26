<?php
namespace SIM\LOGIN;
use SIM;

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

    if($oldVersion < '9.0.0'){
        $users  = get_users([
            'meta_key'      => '2fa_webautn_cred',
            'meta_compare'  => 'EXISTS'
        ]);

        foreach($users as $user){
            /**
             * Load old cred data
             */
            $userCred  = get_user_meta($user->ID, "2fa_webautn_cred", true);
            delete_user_meta($user->ID, "2fa_webautn_cred");

            /**
             * Add as seperate entries
             */
            foreach(unserialize(base64_decode($userCred)) as $cred){
                $cred->removeAaguid();
                add_user_meta($user->ID, "2fa_webautn_cred", base64_encode(serialize($cred)));
            }

            /**
             * Load old cred meta data
             */
            $metas  = maybe_unserialize(get_user_meta($user->ID, "2fa_webautn_cred_meta", true));
            delete_user_meta($user->ID, "2fa_webautn_cred_meta");

            /**
             * Add as seperate entries, rename user to userHandle
             */
            foreach($metas as $credId => $meta){
                $meta['userHandle'] = $meta['user'];
                unset($meta['user']);

                $meta['cred_id']    = $credId;

                add_user_meta($user->ID, "2fa_webautn_cred_meta", base64_encode(serialize($meta)));
            }
        }
    }
}

add_action('init', function(){
    //pluginUpdate('8.9.0');
});