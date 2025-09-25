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

        main, .loader_wrapper{
            margin-top: 20px;
        }
    </style>
    <body>
        <div class='loader_wrapper'>
			<?php echo SIM\LOADERIMAGE;?>
			
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