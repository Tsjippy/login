// Import the registration hook
import { checkWebauthnAvailable } from './webauth.js';

import { startRegistration } from '@simplewebauthn/browser'

import DeviceDetector from "device-detector-js";

import {
	showMessage,
	showStatusMessage
} from './shared.js';

export async function registerWebAuthn(){
	if(window.webauth_register == undefined){
		window.webauth_register	= 'running';
		if( !checkWebauthnAvailable()){
			return;
		}
	}

	const deviceDetector    = new DeviceDetector();

	const device            = deviceDetector.parse(window.navigator.userAgent);

	let identifier          = `${device.device.type}_${device.device.brand}_${device.device.model}_${device.client.name}`;

	showMessage( 'Please take a few seconds to setup your login token...' );

	showStatusMessage( 'Preparing biometric registration...');

	// Get registration options from the endpoint
	const optionsJSON			= await FormSubmit.fetchRestApi('login/fingerprint_options');

	// Update the message
	showStatusMessage('Please authenticate...');

	let attResp;
	try {
		// Pass the options to the authenticator and wait for a response
		attResp = await startRegistration({ optionsJSON });
	} catch (error) {
        // Handle different error types
        if (error.name === 'NotAllowedError') {
            showStatusMessage('Operation cancelled or timed out');
        } else if (error.name === 'InvalidStateError') {
            showStatusMessage('Authenticator already registered');
        } else if (error.name === 'NotSupportedError') {
            showStatusMessage('WebAuthn not supported in this browser');
        } else if (error.name === 'AbortError') {
            showStatusMessage('Operation was aborted');
        } else {
            showStatusMessage('Authentication failed: ' + error.message);
        }
		
		return;
	}

	showMessage( '' );

	showStatusMessage( 'Registering Authenticator' );

	let formData			= new FormData();
	formData.append('identifier', identifier);
	formData.append('publicKeyCredential', btoa(JSON.stringify(attResp)));

	let response			= await FormSubmit.fetchRestApi('login/store_fingerprint', formData);
	if(!response){
		return;
	}

	showStatusMessage('Registration success');
}