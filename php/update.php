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
}