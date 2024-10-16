<?php
namespace SIM\LOGIN;
use SIM;

if ( defined( 'ABSPATH' ) ) {
    return;
}

ob_start();
define( 'WP_USE_THEMES', false ); // Do not use the theme files
define( 'COOKIE_DOMAIN', false ); // Do not append verify the domain to the cookie
define( 'DISABLE_WP_CRON', true );

require(__DIR__."/../../../../wp-load.php");
require_once ABSPATH . WPINC . '/functions.php';

$discard = ob_get_clean();

require_once ABSPATH . WPINC . '/template-loader.php';

if(!is_user_logged_in() && !auth_redirect()){
    die('<div style="text-align: center;"><p>You do not have permission to view this file!</p></div>');
}

//get the current users username
$user		= wp_get_current_user();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
        <title>Approve login</title>
        <link rel="icon" type="image/x-icon" href="<?php echo get_site_icon_url();?>">
        <script id="sim_script-js-extra">
            var sim = {
                "ajaxUrl":"<?php echo admin_url( 'admin-ajax.php' );?>",
                "userId":"<?php echo $user->ID;?>",
                "username":"<?php echo $user->user_login;?>",
                "loadingGif":"<?php echo SITEURL."/wp-content/plugins/sim-plugin/includes/pictures/loading.gif";?>",
                "baseUrl":"<?php echo get_home_url();?>",
                "restNonce":"<?php echo wp_create_nonce('wp_rest');?>",
                "restApiPrefix":"<?php echo '/'.RESTAPIPREFIX;?>"
            };
        </script>
        <script src="<?php echo get_home_url();?>/wp-content/plugins/sim-plugin/includes/js/main.min.js" id="sim_script-js"></script>
        <script src="<?php echo get_home_url();?>/wp-content/plugins/sim-plugin/includes/js/formsubmit.min.js" id="sim_formsubmit_script-js"></script>
        <script src="<?php echo SIM\pathToUrl(MODULE_PATH.'js/qr_code_login.min.js');?>"></script>
        <style>
            .hidden{
                display: none;
            }

            body{
                text-align: center;
            }

            .success{
                border: 3px solid yellowgreen;
                text-align: center;
                padding: 5px;
                color: darkgreen;
            }

            .warning{
                background-color: #d8a354;
                color: black;
                padding: 10px;
                margin: 10px 0 20px 0;
                text-align: center;
            }

            .error{
                border: 3px solid #8a1a0e;
                color: #bd2919;
                padding: 10px;
                margin: 10px 0 20px 0;
                text-align: center;
            }

            main, .loadergif_wrapper{
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='loadergif_wrapper'>
			<img class='loadergif' src='<?php echo SIM\LOADERIMAGEURL;?>' loading='lazy' width=50 height=50>
			
			<span id='message' class='uploadmessage'>Waiting for biometric</span>
		</div>
		<main>
			<p>
				Please authenticate to approve the qr code login request
			</p>
		</main>
    </body>
</html>
<?php
exit;