<?php
namespace TSJIPPY\LOGIN;
use TSJIPPY;
use TSJIPPY\ADMIN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UnsafeLogin extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('unsafe_login', PLUGINSLUG);

        $this->addUser($user);

        $this->defaultSubject    = "Unsafe login detected on %site_name%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "Someone just logged in onto your account without the use of a second login factor.<br>";
    	$this->defaultMessage   .= "Please let us know immidiately if this was not you.";
    }
}
