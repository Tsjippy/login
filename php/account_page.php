<?php 
namespace SIM\LOGIN;
use SIM;

add_filter('sim_user_info_page', __NAMESPACE__.'\userInfoPage', 10, 3);
function userInfoPage($html, $showCurrentUserData, $user){
    /*
        Two FA Info

    */
    if($showCurrentUserData){
        //Add tab button
        $html['tabs']['Two factor']	= '<li class="tablink" id="show-2fa_info" data-target="twofa-info">Two factor</li>';
        
        //Content
        $twofaHtml = '<div id="twofa-info" class="tabcontent hidden">';
            $twofaHtml .= twoFaSettingsForm($user->ID);
        $twofaHtml .= '</div>';

        $html['html']	.= $twofaHtml;	
    }

    return $html;
}