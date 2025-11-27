<?php
namespace SIM\LOGIN;
use SIM;

add_action("sim-github-before-updating-module-login", __NAMESPACE__.'\preUpdate', 10, 2);
function preUpdate($oldVersion, $newVersion){
    SIM\printArray($oldVersion);
    if($oldVersion < '9.0.0' && $newVersion >= '9.0.0'){

        $github         = new SIM\GITHUB\Github();

        // Load update version of class definition
        $fileContent    = $github->contents->download('tsjippy', 'login', "preupdate/PublicKeyCredentialSource.php");

        wp_delete_file(SIM\MODULESPATH."login/lib/vendor/web-auth/webauthn-lib/src/PublicKeyCredentialSource.php");
        file_put_contents(SIM\MODULESPATH."login/lib/vendor/web-auth/webauthn-lib/src/PublicKeyCredentialSource.php", $fileContent);
        require_once(SIM\MODULESPATH."login/lib/vendor/web-auth/webauthn-lib/src/PublicKeyCredentialSource.php");

        $users  = get_users([
            'meta_key'      => '2fa_webautn_cred',
            'meta_compare'  => 'EXISTS'
        ]);

        foreach($users as $user){
            /**
             * Load old cred data
             */
            $userCreds  = get_user_meta($user->ID, "2fa_webautn_cred", true);
            //delete_user_meta($user->ID, "2fa_webautn_cred");

            $userCreds  = unserialize(base64_decode($userCreds));

            if(!is_array($userCreds)){
                $userCreds  = [$userCreds];
            }

            /**
             * Add as seperate entries
             */
            foreach($userCreds as $cred){
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

                $userHandles[$meta['userHandle']]  = $user->ID;
                
                unset($meta['user']);

                $meta['cred_id']    = $credId;

                add_user_meta($user->ID, "2fa_webautn_cred_meta", base64_encode(serialize($meta)));
            }
        }

        update_option('sim-webauth-user-handles', $userHandles);
    }
}