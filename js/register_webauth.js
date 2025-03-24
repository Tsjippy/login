// Import the registration hook
import { checkWebauthnAvailable } from './partials/webauth.js';

import { preparePublicKeyOptions, preparePublicKeyCredentials } from './partials/shared.js';

import DeviceDetector from "device-detector-js";

console.log("Register webauthn is loaded");

//Start registration with button click
async function registerBiometric(){
    try {
        const deviceDetector    = new DeviceDetector();

        const device            = deviceDetector.parse(window.navigator.userAgent);

        let identifier          = `${device.device.type}_${device.device.brand}_${device.device.model}_${device.client.name}`;

        //show loader
        let loaderHtml = `
            <div id="loader_wrapper" style='margin-bottom:20px;'>
                <span class="message"></span>
                <img class="loadergif" src="${sim.loadingGif}" height="30px;">
                <span class='message'></span> 
            </div>`;

        let modalHtml   = `
            <div id='release_modal' class='modal'>
                <div class="modal-content" style='width:500px;'>
                    <h4>Please take a few seconds to setup your login token</h4>
                    ${loaderHtml}
                </div>
            </div>`;

        document.querySelector('body').insertAdjacentHTML('afterEnd', modalHtml);
        
        let message		= document.querySelector('#loader_wrapper .message');

		// Get biometric challenge
		let formData			= new FormData();
		formData.append('identifier', identifier);
		let response			= await FormSubmit.fetchRestApi('login/fingerprint_options', formData);
		if(!response){
			throw new Error('Options retrieval failed');
		}
		let publicKey 			= preparePublicKeyOptions(response);

		// Update the message
		message.textContent  	= 'Please authenticate...';

		// Ask user to verify
		let credentials 		= await navigator.credentials.create({publicKey});

		// Update the message
		message.textContent  	= 'Saving authenticator...';

		// Store result
		var publicKeyCredential = preparePublicKeyCredentials(credentials);
		
		formData			= new FormData();
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		response			= await FormSubmit.fetchRestApi('login/store_fingerprint', formData);
		if(!response){
			throw new Error('Storing biometric failed');
		}
  
		Main.displayMessage('Registration success');

        Main.hideModals();
	}catch(error){
		console.error(error);
		Main.displayMessage(error, 'error');
	}

    document.querySelector('#loader_wrapper').remove();
}

document.addEventListener("DOMContentLoaded", async function() {
	if( checkWebauthnAvailable()){
        registerBiometric();
    }
});