import { showMessage } from './shared.js';

import { startAuthentication } from '@simplewebauthn/browser';

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
 * @param {*} messageEl the html onject to display messages in
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
		if(sim.login != undefined){
			sim.login.loadingScreen('Preparing Passkey Login...');
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
			throw new Error('Passkey Login failed');
		}

		if(response){
			showMessage('Passkey login succesfull');

			return await sim.login.requestLogin();
		}else{
			sim.login.reset();

			showMessage('Passkey login failed, try using your username and password');

			return false;
		}
	} catch (error) {
		console.error('Passkey Login failed:', error);

		showMessage(error);

		showStatusMessage('Passkey Login failed');

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