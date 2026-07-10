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
            'icon'  => 'id'
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
            'icon'  => 'forms'
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
            'icon'  => 'id'
        )
    );

	register_block_type(
        'tsjippy-login/login-count',
        array(
            'title'           => __( 'User Login Count', 'tsjippy' ),
            'render_callback' => function(){
                return "<span>".loginCount()."</span>";
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
            "icon"  => "plus"
        )
    );
}

//Shortcode to return the amount of loggins in words
add_shortcode("tsjippy-login-count", __NAMESPACE__ . '\loginCount');
function loginCount()
{
	$userId				= get_current_user_id();
	$currentLogginCount = get_user_meta($userId, 'tsjippy_login_count', true);
	//Get the word from the array
	if (is_numeric($currentLogginCount)) {
		return TSJIPPY\numberToWords($currentLogginCount);
		//key not set, assume its the first time
	} else {
		return "your first";
	}
}
