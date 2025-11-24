<?php
namespace SIM\LOGIN;
use SIM;

use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_Error;

//https://webauthn-doc.spomky-labs.com/

/**
 * Format trackback
 */
function generateCallTrace($exception = false){
    $e = $exception;
    if($exception === false){
        $e = new \Exception();
    }
    $trace = explode("\n", $e->getTraceAsString());
    $trace = array_reverse($trace);
    array_shift($trace);
    array_pop($trace);
    $length = count($trace);
    $result = array();

    for($i = 0; $i < $length; $i++){
        $result[] = ($i + 1).')'.substr($trace[$i], str_contains($trace[$i], ' '));
    }

    return "Traceback:\n                              ".implode("\n                              ", $result);
}

/**
 * Get authenticator list
 *
 * @return  object The autenticator object
 */
function authenticatorList(){
    $user = wp_get_current_user();

    if(!current_user_can("read")){
        $name = $user->display_name;
        //SIM\printArray("$name has not enough permissions");
        //return;
    }

    if(isset($_GET["user_id"])){
        $userId = intval(sanitize_text_field($_GET["user_id"]));
        if($userId <= 0){
            SIM\printArray("ajax_ajax_authenticator_list: (ERROR)Wrong parameters, exit");
            return new WP_Error('webauthn', "Bad Request.");
        }

        if($user->ID !== $userId){
            if(!current_user_can("edit_user", $userId)){
                SIM\printArray("ajax_ajax_authenticator_list: (ERROR)No permission, exit");
                return new WP_Error('webauthn', "Bad Request.");
            }
            $user = get_user_by('id', $userId);

            if($user === false){
                SIM\printArray("ajax_ajax_authenticator_list: (ERROR)Wrong user ID, exit");
                return new WP_Error('webauthn', "Bad Request.");
            }
        }
    }

    $userKey   = get_user_meta($user->ID, '2fa_webauthn_key', true);
    if(!$userKey){
        // The user haven't bound any authenticator, return empty list
        if(defined('REST_REQUEST')){
            return "[]";
        }else{
            return array();
        }
    }

    $userEntity = new PublicKeyCredentialUserEntity(
        $user->user_login,
        $userKey,
        $user->display_name
    );

    $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

    return $publicKeyCredentialSourceRepository->getShowList($userEntity);
}

/**
 * Creates a table listing all the webauthn methods of an user
 *
 * @param   int     $authId     The current used webauthn id
 *
 * @return  string              The table html
 */
function authTable($authId=''){
    $webauthnList	= authenticatorList();

    if(empty($webauthnList)){
        return '';
    }

    ob_start();
	
    ?>
    <div id='webautn-devices-wrapper'>
        <h4>Biometric authenticators overview</h4>
        <table class='sim-table'>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>OS</th>
                    <th>Added</th>
                    <th>Last used</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($webauthnList as $key=>$deviceMeta){
                    $identifier		= $deviceMeta['identifier'];
                    $osName		    = $deviceMeta['os_info']['name'];
                    $added			= date('jS M Y', strtotime($deviceMeta['added']));
                    $lastUsed       = $deviceMeta['last_used'];

                    if($lastUsed != '-'){
                        $lastUsed		= date('jS M Y', strtotime($deviceMeta['last_used']));
                    }

                    if($key == $authId){
                        echo "<tr class='current-device'>";
                    }else{
                        echo "<tr>";
                    }
            
                    ?>
                        <td><?php echo $identifier;?></td>
                        <td><?php echo $osName;?></td>
                        <td><?php echo $added;?></td>
                        <td><?php echo $lastUsed;?></td>
                        <td>
                            <button type='button' class='button small remove-webauthn' title='Remove this method' data-key='<?php echo $key;?>'>-</button>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php

    return ob_get_clean();
}