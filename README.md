This module turns on AJAX login and adds two factor login, webauthentication(fingerprint, facial, pincode) and qr code login.<br>
The normal login (wp-login.php) is no longer available once this module is activated.<br>
A 'login' or 'logout' menu button will be added instead.<br>
These buttons are used for AJAX based logins and logouts, saving loading time.<br>
<br>
Three second factor options are available:<br>
- Authenticator code
- E-mail
- Biometrics

As biometrics is device specific, it is advisable to also add e-mail or authenticator login.<br>
<br>
The login process will be this:<br>
- Click login
- If webauthentication is available you will be logged in straigt away
if not:
- Fill in username and password
- Click verify credentials
- If a biometric login is enabled for the current device that will be used
- If no biometric login is enabled for the current device a authenticator or e-mail code will be requested
- If no second factor is setup yet the user will be redirected to the page to setup 2fa and cannot access the website.

on Devices without webauthenticators people can login by scanning a qr code using a device with authenticator enabled

<br>
2fa setup is done on the page containing the twofa_setup shortcode.<br>
Use like this: <code>[twofa_setup]</code>
