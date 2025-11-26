import {
    preparePublicKeyCredentials,
    preparePublicKeyOptions,
	showMessage
} from './shared.js';

let credParsing			        = false;
let abortController;

window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(
	result => {
	  if (!result) {
		console.log("No platform authenticator found. If your OS does not come with one, try using devtools to set one up.");
	  }
	}
);

/**
 * 
 * @param {string} username The user name to authenticate
 * @param {*} messageEl the html onject to display messages in
 */
export async function webAuthVerification(username, messageEl=null){
	if(messageEl != null){
		messageEl.classList.remove('success');
		messageEl.classList.remove('error');
	}

	try{
		// Get the challenge
		let formData			= new FormData();
		formData.append('username', username);

		let response			= await FormSubmit.fetchRestApi('login/auth_start', formData);
		if(!response){
			throw new Error('Fetching Server Challenge failed');
		}

		let publicKey			= preparePublicKeyOptions(response);

		// Update message
		if(messageEl != null){
			messageEl.textContent	= 'Waiting for biometric';
		}

		// Verify on device
		let credentials			= await navigator.credentials.get({	publicKey });

		// Update message
		if(messageEl != null){
			messageEl.textContent	= 'Verifying...';
		}

		// Verify on the server
		const publicKeyCredential 	= preparePublicKeyCredentials(credentials);
		let form 					= document.getElementById('loginform') ? document.getElementById('loginform') : undefined;
		formData					= new FormData(form);
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		response					= await FormSubmit.fetchRestApi('login/auth_finish', formData);
		if(!response){
			throw new Error('Verification failed');
		}

		if(messageEl != null){
			messageEl.textContent	= 'Verification successfull';
		}else{
			Main.displayMessage('Verification successfull');
		}

		return true;
	}catch(error){
		console.error(error);

		if(messageEl != null){
			messageEl.textContent	= error;
		}else{
			Main.displayMessage(error, 'error');
		}

		return false;
	}
}

export async function processCredential(credential){
	if(credParsing){
		return;
	}

	if (credential) {
		credParsing	= true;

		sim.login.loadingScreen('Verifying credentials...');

		// Verify on the server
		const publicKeyCredential 	= preparePublicKeyCredentials(credential);
		let formData				= new FormData(document.getElementById('loginform'));
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		let response				= await FormSubmit.fetchRestApi('login/auth_finish', formData, false);

		if(response){
			showMessage('Passkey login succesfull');
		}else{
			sim.login.reset();

			showMessage('Passkey login failed, try using your username and password');

			return false;
		}

		//authentication success
		return await sim.login.requestLogin();

	} else {
		console.log("Credential returned null");

		sim.login.resetForm();

		showMessage('Passkey login failed');

		return false;
	}
}

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

	if(abortController != undefined){
		console.log('Cancelling previous request');
		abortController.abort('aborted');
	}
	
	abortController	= new AbortController();
		
	abortController.onAbort	= function(ev){
		console.log(ev);
	}
	abortController.signal.onAbort	= function(ev){
		console.log(ev);
	}

	if(mediation != 'conditional'){
		sim.login.loadingScreen('Performing passkey login');
	}

	try {
		let formData			= new FormData();
		formData.append('username', '');

		let response			= await FormSubmit.fetchRestApi('login/auth_start', formData);
		if(!response){
			throw new Error('auth_start failed');
		}

		let publicKey			= preparePublicKeyOptions(response);

		let credential = await navigator.credentials.get({
			signal: abortController.signal,
			publicKey: {
				challenge: publicKey.challenge
			},
			//mediation: 'silent',
			//mediation: 'conditional',
			//mediation: 'required',
			mediation: mediation
		});

		if(mediation == 'conditional'){	
			sim.login.loadingScreen('Performing passkey login');
		}
		
		return await processCredential(credential);
	} catch (error) {
		if (error == "aborted") {
			console.log("request aborted");
			return false;
		}

		if(error.message.includes('A request is already pending.')){
			startConditionalRequest(mediation);
		}

		// only do when login modal is open
		if(sim.login != null){
			sim.login.resetForm();

			showMessage('Passkey login failed, try using your username and password');
		}

		console.log(error);

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