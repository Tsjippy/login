// Import the registration hook
import { checkWebauthnAvailable } from './partials/webauth.js';

import { preparePublicKeyOptions, preparePublicKeyCredentials } from './partials/shared.js';

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
		// Some basic error handling
		if (error.name === 'InvalidStateError') {
			console.log('Error: Authenticator was probably already registered by user');
		} else {
			console.error(error);
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