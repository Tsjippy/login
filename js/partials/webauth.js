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
export async function webAuthVerification(username){
	try {
		// 1. Fetch authentication options from server
		let formData				= new FormData();
		formData.append('username', username);

		const optionsJSON			= await FormSubmit.fetchRestApi('login/auth_start', formData);
		if(!optionsJSON){
			throw new Error('Fetching Server Challenge failed');
		}

		// Update message
		sim.login.loadingScreen('Verifying credentials...');

		// 2. Start authentication
		const assertionResponse 	= await startAuthentication({ ...optionsJSON, useBrowserAutofill: true });

		// 3. Send to server for validation
		let form 					= document.getElementById('loginform') ? document.getElementById('loginform') : undefined;
		formData					= new FormData(form);
		formData.append('publicKeyCredential', btoa(JSON.stringify(assertionResponse)));
		
		let response					= await FormSubmit.fetchRestApi('login/auth_finish', formData);
		if(!response || response.verified){
			throw new Error('Verification failed');
		}

		showMessage('Verification successfull');

		return true;
	} catch (error) {
		console.error('Authentication failed:', error);

		showMessage(error);

		return false;
	}
}

/**
 * Start passkey login without username
 * 
 * @param {string} mediation the type of request
 * 
 * @returns 
 */
export let startConditionalRequest = async (mediation) => {
	if (window.PublicKeyCredential && PublicKeyCredential.isConditionalMediationAvailable) {
		console.log("Conditional UI is understood by the browser");
		if (!await window.PublicKeyCredential.isConditionalMediationAvailable()) {
			console.log("Conditional UI is understood by your browser but not available");
			return;
		}
	} else {
		if (!navigator.credentials.conditionalMediationSupported) {
			console.log("Your browser does not implement Conditional UI (are you running the right chrome/safari version with the right flags?)");
			return;
		} else {
			console.log("This browser understand the old version of Conditional UI feature detection");
		}
	}

	sim.login.loadingScreen('Performing passkey login');

	let webauthResult =  await webAuthVerification('');

	if(webauthResult){
		showMessage('Passkey login succesfull');

		return await sim.login.requestLogin();
	}else{
		sim.login.reset();

		showMessage('Passkey login failed, try using your username and password');

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