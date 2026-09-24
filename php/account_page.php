<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-user-management-user-info-page', __NAMESPACE__ . '\userInfoPage', 10, 3);
/**
 * Filters the tab content of the profile page
 * @param   array       $html                   contains index 'tabs' for the tab buttons html and 'html' for the content of each tab
 * @param   bool        $showCurrentUserData    Current or another user
 * @param   \WP_User    $user                   The user data
 */
function userInfoPage($html, $showCurrentUserData, $user)
{
    /*
        Two FA Info
    */
    if ($showCurrentUserData) {
        //Add tab button
        $html['tabs']['Two factor']    = '<li class="tablink" id="show-2fa_info" data-target="twofa-info">Two factor</li>';

        //Content
        $twofaHtml = '<div id="twofa-info" class="tabcontent hidden">';
        $twofaHtml .= twoFaSettingsForm($user->ID);
        $twofaHtml .= '</div>';

        $html['html']    .= $twofaHtml;
    }

    return $html;
}
