<?php
namespace SIM\LOGIN;
use SIM;

// only direct loading allowed
if ( defined( 'ABSPATH' ) ) {
    return;
}

require_once(__DIR__ . '/wordpress_loader.php');
loadWordpress('Qr Code Login');

if(!is_user_logged_in() && !auth_redirect()){
    die('<div style="text-align: center;"><p>You do not have permission to view this file!</p></div>');
}

    ?>
    <style>
        .hidden{
            display: none;
        }

        body{
            text-align: center;
        }

        main, .loader-wrapper{
            margin-top: 20px;
        }
    </style>
    <body>
        <div class='loader-wrapper'>
			<div class="loader-image-trigger"></div>
			
			<span id='message' class='message' style='display: block;'>Waiting for biometric</span>
		</div>
		<main>
			<p>
				Please authenticate to approve the qr code login request
			</p>
		</main>
        <script src="<?php echo SIM\pathToUrl(MODULE_PATH.'js/qr_code_login.min.js');?>"></script>
    </body>
</html>
<?php
exit;