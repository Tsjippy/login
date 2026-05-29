<?php
namespace TSJIPPY\LOGIN;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

class AfterUpdate extends TSJIPPY\AfterPluginUpdate {

    public function afterPluginUpdate($oldVersion){

        if(version_compare('10.0.1', $oldVersion) === 1 ){
            $settings       = get_option('tsjippy-login-settings');

            $settings['login-menu'] = $settings['loginmenu'] ?? [];
            $settings['logout-menu'] = $settings['logoutmenu'] ?? [];
            $settings['visibilty-login-menu'] = $settings['visibiltyloginmenu'] ?? [];
            $settings['visibilty-logout-menu'] = $settings['visibiltylogoutmenu'] ?? [];

            update_option('tsjippy-login-settings', $settings);
        }
    }
}
