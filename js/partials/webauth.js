import {
    preparePublicKeyCredentials,
    preparePublicKeyOptions,
	showMessage,
	requestLogin
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
			messageEl.classList.add('success');
		}else{
			Main.displayMessage('Verification successfull');
		}

		return true;
	}catch(error){
		console.error(error);

		if(messageEl != null){
			messageEl.textContent	= error;
			messageEl.classList.add('error');
		}else{
			Main.displayMessage(error, 'error');
		}

		return false;
	}
}

// Send request to start webauthn
export async function verifyWebauthn(methods){	
	//show webauthn messages
	document.getElementById('webauthn_wrapper').classList.remove('hidden');

	let username	= document.getElementById('username').value;

	try{
		let result	= await webAuthVerification(username, document.querySelector('#webauthn_wrapper .status_message'));

		if(!result){
			throw new Error( 'Webauthentication failed' );
		}

		//authentication success
		await requestLogin();
	}catch (error){
		if(document.getElementById('logging_in_wrapper').classList.add('hidden'));

		//authentication failed
		document.querySelector('#webauthn_wrapper').classList.add('hidden');

		let response	= await FormSubmit.fetchRestApi('login/mark_bio_as_failed', '', false);
		
		console.log(response);

		if(methods.length == 1){
			showMessage('Authentication failed, please setup an additional login factor.', 'error');
			requestLogin();
		}else{
			console.error(error);
			var message;
			if(error['message'] == "No authenticator available"){
				message = "No biometric login for this device found. <br>Give verification code.";
			}else{
				message = 'Web authentication failed, please give verification code.';
			}
			showMessage(message, 'error');

			//Show other 2fa fields
			showTwoFaFields(methods);
		}
	}
}

// Request email code for 2fa login
export async function requestEmailCode(){
	//add new one
	let loader				= Main.showLoader(null, false, 50, '', true);
	showMessage(`Sending e-mail... ${loader}`);

	let username	= document.getElementById('username').value;
	let formData	= new FormData();
	formData.append('username',username);

	let response	= await FormSubmit.fetchRestApi('login/request_email_code', formData, false);
	
	if(response){
		showMessage(response, 'success');
	}else{
		showMessage(`Sending e-mail failed`, 'error');
	}
}

export async function processCredential(credential){
	if(credParsing){
		return;
	}

	if (credential) {
		credParsing	= true;
		let username = String.fromCodePoint(...new Uint8Array(credential.response.userHandle));

		document.querySelector('#webauthn_wrapper .status_message').textContent='Verifying credentials...';

		// Verify on the server
		const publicKeyCredential 	= preparePublicKeyCredentials(credential);
		let formData				= new FormData(document.getElementById('loginform'));
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		let response				= await FormSubmit.fetchRestApi('login/auth_finish', formData, false);

		if(response){
			showMessage('Passkey login succesfull', 'success');
		}else{
			document.querySelector('#webauthn_wrapper .status_message').textContent='Please authenticate';

			document.querySelectorAll('#usercred_wrapper').forEach(el=>el.classList.remove('hidden'));
			document.querySelectorAll('#webauthn_wrapper').forEach(el=>el.classList.add('hidden'));

			showMessage('Passkey login failed, try using your username and password', 'error');

			return false;
		}

		//authentication success
		return await requestLogin();

	} else {
		console.log("Credential returned null");

		document.getElementById('usercred_wrapper').classList.remove('hidden');
		document.getElementById('webauthn_wrapper').classList.add('hidden');

		showMessage('Passkey login failed', 'error');

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
		document.getElementById('usercred_wrapper').classList.add('hidden');
		document.getElementById('webauthn_wrapper').classList.remove('hidden');

		showMessage('Performing passkey login');
	}

	let usercredWrapper	= document.getElementById('usercred_wrapper');

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
			usercredWrapper.classList.add('hidden');
			document.getElementById('webauthn_wrapper').classList.remove('hidden');
	
			showMessage('Performing passkey login');
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
		if(usercredWrapper != null){
			usercredWrapper.classList.remove('hidden');
			document.getElementById('webauthn_wrapper').classList.add('hidden');

			showMessage('Passkey login failed, try using your username and password', 'error');
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

// Display the form for the 2fa email or authenticator code
export function showTwoFaFields(methods){
	if(methods.includes('email')){
		requestEmailCode();
	}

	//show 2fa fields
	for(const element of methods){
		if(element == 'webauthn'){
			//do not show webauthn
			continue;
		}
		var wrapper	= document.getElementById(element+'_wrapper');
		if(wrapper != null){
			wrapper.classList.remove('hidden');
			wrapper.querySelectorAll('input').forEach(el=>window.setTimeout(() => el.focus(), 0));
		}
	}

	//enable login button
	document.querySelector("#login_button").disabled			= '';
	//show login button
	document.querySelector('#submit_login_wrapper').classList.remove('hidden');
}