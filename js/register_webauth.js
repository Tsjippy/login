// Import the registration hook
import { checkWebauthnAvailable } from './partials/webauth.js';

import { startRegistration } from '@simplewebauthn/browser'

import DeviceDetector from "device-detector-js";

console.log("Register webauthn is loaded");

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

	let response			= await FormSubmit.fetchRestApi('login/store_fingerprint', formData);
	if(!response){
		return;
	}

	Main.displayMessage('Registration success');
}

document.addEventListener("DOMContentLoaded", async function() {
	if(window.webauth_register == undefined){
		window.webauth_register	= 'running';
		if( checkWebauthnAvailable()){
			try{
				await register();
				Main.hideModals();
			}catch(error){
				console.error(error);
			}
		}
	}
});