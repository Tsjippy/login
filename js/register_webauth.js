// Import the registration hook
import { checkWebauthnAvailable } from './partials/webauth.js';

import { preparePublicKeyOptions, preparePublicKeyCredentials } from './partials/shared.js';

import { startRegistration } from '@simplewebauthn/browser'

import DeviceDetector from "device-detector-js";

console.log("Register webauthn is loaded");

function base64urlEncode(buffer) {
    const base64 = btoa(String.fromCharCode(...new Uint8Array(buffer)));
    return base64
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

function base64urlDecode(base64url) {
    const base64 = base64url
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const padding = '='.repeat((4 - base64.length % 4) % 4);
    const binary = atob(base64 + padding);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

async function register(){
	const deviceDetector    = new DeviceDetector();

	const device            = deviceDetector.parse(window.navigator.userAgent);

	let identifier          = `${device.device.type}_${device.device.brand}_${device.device.model}_${device.client.name}`;

	//show loader
	let loaderHtml 			= Main.showLoader(null, false, 50, 'Preparing biometric registration...', true);

	let modalHtml   		= `
		<div id='register-biometrics-modal' class='modal hidden'>
			<div class="modal-content" style='width:500px;padding-bottom:20px'>
				<h4>Please take a few seconds to setup your login token</h4>
				${loaderHtml}
			</div>
		</div>`;

	document.querySelector('body').insertAdjacentHTML('afterEnd', modalHtml);

	let message		= document.querySelector('#register-biometrics-modal .loader-text');

	// Get registration options from the endpoint
	const optionsJSON			= await FormSubmit.fetchRestApi('login/fingerprint_options');

	// Update the message
	message.textContent  	= 'Please authenticate...';

	// Show the modal
	document.getElementById(`register-biometrics-modal`).classList.remove('hidden');


 // testing code
 const publicKeyCredentialCreationOptions = {
    ...optionsJSON,
    challenge: base64urlDecode(optionsJSON.challenge),
    user: {
        ...optionsJSON.user,
        id: base64urlDecode(optionsJSON.user.id)
    },
    excludeCredentials: optionsJSON.excludeCredentials?.map(cred => ({
        ...cred,
        id: base64urlDecode(cred.id)
    }))
};

// 3. Call the WebAuthn API
const credential = await navigator.credentials.create({
    publicKey: publicKeyCredentialCreationOptions
});

// 4. Encode response for server
const attestationResponse = {
    id: credential.id,
    rawId: base64urlEncode(credential.rawId),
    type: credential.type,
    response: {
        clientDataJSON: base64urlEncode(credential.response.clientDataJSON),
        attestationObject: base64urlEncode(credential.response.attestationObject)
    }
};

let formData			= new FormData();
	formData.append('identifier', identifier);
	formData.append('publicKeyCredential', btoa(JSON.stringify(attestationResponse)));

	let response				= await FormSubmit.fetchRestApi('login/store_fingerprint', formData);
	if(!response){
		return;
	}
	
 // prod code
	let attResp;
	try {
		// Pass the options to the authenticator and wait for a response
		attResp = await startRegistration({ optionsJSON });
	} catch (error) {
    // Handle different error types
    if (error.name === 'NotAllowedError') {
        alert('Operation cancelled or timed out');
    } else if (error.name === 'InvalidStateError') {
        alert('Authenticator already registered');
    } else if (error.name === 'NotSupportedError') {
        alert('WebAuthn not supported in this browser');
    } else if (error.name === 'AbortError') {
        alert('Operation was aborted');
    } else {
        alert('Authentication failed: ' + error.message);
    }

		return;
	}

	message.textContent  	= 'Registering Authenticator';

	let formData			= new FormData();
	formData.append('identifier', identifier);
	formData.append('publicKeyCredential', btoa(JSON.stringify(attResp)));

	let response				= await FormSubmit.fetchRestApi('login/store_fingerprint', formData);
	if(!response){
		return;
	}

	Main.displayMessage('Registration success');
}

document.addEventListener("DOMContentLoaded", async function() {
	if( checkWebauthnAvailable()){
		await register();
		Main.hideModals();
    }
});