<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', __NAMESPACE__ . '\initBlocks');
function initBlocks()
{
    register_block_type(
        'tsjippy-login/twofa-setup',
        array(
            'title'           => __( 'Two Factor Setup', 'tsjippy' ),
            'render_callback' => __NAMESPACE__.'\twoFaSettingsForm',
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

    register_block_type(
        'tsjippy-login/change-password',
        array(
            'title'           => __( 'Change Password Form', 'tsjippy' ),
            'render_callback' => __NAMESPACE__.'\changePassword',
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

    register_block_type(
        'tsjippy-login/request-user-account',
        array(
            'title'           => __( 'Request User Account Form', 'tsjippy' ),
            'render_callback' => __NAMESPACE__.'\requestAccount',
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );
}