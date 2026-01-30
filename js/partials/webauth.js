import { 
	startAuthentication,
	WebAuthnError
} from '@simplewebauthn/browser';

import {
	showMessage,
	showStatusMessage
} from './shared.js';

window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(
	result => {
	  if (!result) {
		console.log("No platform authenticator found. If your OS does not come with one, try using devtools to set one up.");
	  }
	}
);

/**
 * Do a webauthn verification after loggin with username and password
 * 
 * @param {string} username The user name to authenticate
 * @param {bool} autofill Whether to use browser autofill
 */
export async function webAuthVerification(username, autofill = false){
	try {
		// 1. Fetch authentication options from server
		let formData				= new FormData();

		if(!autofill){
			formData.append('username', username);
		}

		const optionsJSON			= await FormSubmit.fetchRestApi('login/auth_start', formData);
		if(!optionsJSON){
			throw new Error('Fetching Server Challenge failed');
		}

		// Update message
		if(sim.login != undefined && !autofill){
			sim.login.loadingScreen('Preparing Passkey Verification...');
		}

		let options					= { optionsJSON: optionsJSON };
		if(autofill){
			options.useBrowserAutofill	= true;
		}

		// 2. Start authentication
		const assertionResponse 	= await startAuthentication(options);

		sim.login.loadingScreen('Validating Passkey...');

		// 3. Send to server for validation
		let form 					= document.getElementById('loginform') ? document.getElementById('loginform') : undefined;
		formData					= new FormData(form);
		formData.append('publicKeyCredential', btoa(JSON.stringify(assertionResponse)));
		
		let response					= await FormSubmit.fetchRestApi('login/auth_finish', formData);
		if(!response || response.verified){
			throw new Error('Passkey Verification failed');
		}

		if(response){
			showMessage('Passkey Verification Succesfull');

			return await sim.login.requestLogin();
		}else{
			sim.login.reset();

			showMessage('Passkey Verification failed, try using your username and password');

			return false;
		}
	} catch (error) {
		// Ignore if the ceremony was aborted
		if (!autofill || !(error instanceof WebAuthnError && error.code === 'ERROR_CEREMONY_ABORTED')) {
			console.error('Passkey Verification Failed:', error);

			if (!autofill){
				showMessage(error);

				showStatusMessage('Passkey Verification Failed');
			}
		}

		return false;
	}
}

export async function checkWebauthnAvailable(){
	let webauthnSupported	= false;
	
	if (window.PublicKeyCredential) {
		let available	= await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
		if (available) {
			webauthnSupported = true;
		} else {
			console.log("WebAuthn supported, Platform Authenticator not supported.");
		}
	} else {
		console.log("Not supported.");
	}

	return webauthnSupported;
}

export async function checkImmediateMediationAvailability() {
	try {
		const capabilities = await PublicKeyCredential.getClientCapabilities();

		if (capabilities.immediateGet && window.PasswordCredential) {
		 	console.log("Immediate Mediation with passwords supported.");
			return true;
		} else if (capabilities.immediateGet) {
		 	console.log("Immediate Mediation without passwords supported.");

			return true;
		} else { 
			console.log("Immediate Mediation unsupported."); 

			return false;
		}
	} catch (error) {
		console.error("Error getting client capabilities:", error);

		return false;
	}
}

/**
 * Initiates and verifies a webauthentication login
 * @param {*} methods 
 * @returns 
 */
export async function verifyWebauthn(methods){	
	//show webauthn messages
	this.loadingScreen('Starting Passkey Verification');

	try{
		let result	= await webAuthVerification(this.username);

		if(!result){
			throw new Error( 'Passkey Verification failed' );
		}

		//authentication success
		this.requestLogin(false);
	}catch (error){		
		if(methods.length == 1){
			showMessage('Passkey Verification failed, please setup an additional login factor.');
			this.requestLogin(true);
		}else{
			console.error(error);
			let message;
			if(error['message'] == "No authenticator available"){
				message = "No biometric login for this device found. <br>Give verification code.";
			}else{
				message = 'Passkey Verification failed, please give verification code.';
			}
			showMessage(message);

			//Show other 2fa fields
			this.showTwoFaFields(methods);
		}
	}
}