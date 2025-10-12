<?php
namespace SIM\LOGIN;
use SIM;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Imagick;

if(!class_exists('BaconQrCode\Renderer\ImageRenderer')){
    return new \WP_Error('2fa', "bacon-qr-code interface does not exist. Please run 'composer require bacon/bacon-qr-code'");
}

class QrCodeLogin{
    private $token;
    private $key;

    function __construct() {
        $this->token    = '';
        $this->key      = '';
    }

    /**
     * Creates the login link
     *
     * @return  string      The login link
     */
    private function getLoginLink(){
        //$url            = SIM\ADMIN\getDefaultPageLink('login', '2fa-page');
        $url            = SIM\pathToUrl(MODULE_PATH.'php/qr_code_login.php');

        $this->token    = bin2hex(random_bytes(10));
        $this->key      = time();
        set_transient($this->key, $this->token, 60); // one minute

        if(empty($url)){
            $url    = get_home_url().'?message=No%202fa%20Page%20found&type=error';
        }else{
            $url    .= "?key=$this->key&token=$this->token";
        }

        // include the previous key and token
        if(!empty($_POST['token']) && !empty($_POST['key'])){
            $url    .= "&oldtoken={$_POST['token']}&oldkey={$_POST['key']}";
        }
        return $url;
    }

    /**
     * Creates the qr code html image for login purposes
     *
     * @return  string          The html qr code image. Empty string if imagick is not installed
     */
    public function getQrCode(){
        if (! class_exists('Imagick')) {

            SIM\printArray('Imagick is not installed');

            return '';
        }

        $renderer                   = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);

        $url            = $this->getLoginLink();
        $qrcodeImage    = base64_encode($writer->writeString($url));

        return "<span class='close-qr' title='Hide this QR code'>X</span><a href='$url'><img id='login-qr-code' src='data:image/png;base64, $qrcodeImage'/ width=300 height=300 data-token='$this->token' data-key=$this->key></a>";
    }
}