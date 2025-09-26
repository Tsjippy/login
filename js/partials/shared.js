export function closeMobileMenu(){
	//close mobile menu
	document.querySelectorAll('#site-navigation, #mobile-menu-control-wrapper').forEach(el=>el.classList.remove('toggled'));
	document.querySelector('body').classList.remove('mobile-menu-open');
	document.querySelectorAll("#mobile-menu-control-wrapper > button").forEach(el=>el.ariaExpanded = 'false');
}

// Decodes a Base64Url string
const base64UrlDecode = (input) => {
input = input
	.replace(/-/g, '+')
	.replace(/_/g, '/');

const pad = input.length % 4;
if (pad) {
	if (pad === 1) {
	throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
	}
	input += new Array(5-pad).join('=');
}

return window.atob(input);
};

// Converts an array of bytes into a Base64Url string
const arrayToBase64String = (a) => btoa(String.fromCharCode(...a));

// Prepares the public key options object returned by the Webauthn Framework
export const preparePublicKeyOptions = publicKey => {
	//Convert challenge from Base64Url string to Uint8Array
	publicKey.challenge = Uint8Array.from(
		base64UrlDecode(publicKey.challenge),
		c => c.charCodeAt(0)
	);

	//Convert the user ID from Base64 string to Uint8Array
	if (publicKey.user !== undefined) {
		publicKey.user = {
		...publicKey.user,
		id: Uint8Array.from(
			window.atob(publicKey.user.id),
			c => c.charCodeAt(0)
		),
		};
	}

	//If excludeCredentials is defined, we convert all IDs to Uint8Array
	if (publicKey.excludeCredentials !== undefined) {
		publicKey.excludeCredentials = publicKey.excludeCredentials.map(
			data => {
			return {
				...data,
				id: Uint8Array.from(
					base64UrlDecode(data.id),
					c => c.charCodeAt(0)
				),
			};
			}
		);
	}

	if (publicKey.allowCredentials !== undefined) {
		publicKey.allowCredentials = publicKey.allowCredentials.map(
			data => {
			return {
				...data,
				id: Uint8Array.from(
					base64UrlDecode(data.id),
					c => c.charCodeAt(0)
				),
			};
			}
		);
	}

	return publicKey;
};

// Prepares the public key credentials object returned by the authenticator
export const preparePublicKeyCredentials = data => {
	const publicKeyCredential = {
		id: data.id,
		type: data.type,
		rawId: arrayToBase64String(new Uint8Array(data.rawId)),
		response: {
		clientDataJSON: arrayToBase64String(
			new Uint8Array(data.response.clientDataJSON)
		),
		},
	};

	if (data.response.attestationObject !== undefined) {
		publicKeyCredential.response.attestationObject = arrayToBase64String(
			new Uint8Array(data.response.attestationObject)
		);
	}

	if (data.response.authenticatorData !== undefined) {
		publicKeyCredential.response.authenticatorData = arrayToBase64String(
			new Uint8Array(data.response.authenticatorData)
		);
	}

	if (data.response.signature !== undefined) {
		publicKeyCredential.response.signature = arrayToBase64String(
			new Uint8Array(data.response.signature)
		);
	}

	if (data.response.userHandle !== undefined) {
		publicKeyCredential.response.userHandle = arrayToBase64String(
			new Uint8Array(data.response.userHandle)
		);
	}

	return publicKeyCredential;
};

export function showMessage(message, type=''){
	let el = document.querySelector("#message");
	el.innerHTML= DOMPurify.sanitize(message);

	el.classList.remove('success');
	el.classList.remove('warning');
	el.classList.remove('error');

	if(type == 'success'){
		el.classList.add('success');
	}

	if(type == 'warning'){
		el.classList.add('warning');
	}

	if(type == 'error'){
		el.classList.add('error');
	}
}

//show loader
export async function requestLogin(){
	//hide everything
	document.querySelectorAll('.authenticator-wrapper:not(.hidden)').forEach(el=>{
		el.classList.add('hidden');
		el.classList.add('current-method');
	});
	
	//show login message
	document.getElementById('logging_in_wrapper').classList.remove('hidden');

	let form 		= document.getElementById('loginform');
	let formData	= new FormData(form);
	form.querySelectorAll('.hidden [required]').forEach(el=>{el.required = false});
	let validity	= form.reportValidity();
	//if not valid return
	if(!validity){
		return false;
	}

	await Main.waitForInternet();

	let response	= await FormSubmit.fetchRestApi('login/request_login', formData);

	if(response){
		console.log(response);
		// We are logging in from an iframe
		if(window.self !== window.top){

			// change message
			console.log(window.parent.document.getElementById('iframe-loader'));
			console.log(window.parent.document);
			console.log(window.parent);
			window.parent.document.getElementById('iframe-loader').textContent	= 'Succesfully logged in, you may now close this popup';

			// Refresh the rest api nonce
			window.parent.sim.restNonce	= response.nonce;

			// Update user id
			window.parent.sim.userId	= response.id;

			console.log(window.parent.document.getElementById('iframe-loader'));

			// close all iframes
			window.parent.document.querySelectorAll('iframe').forEach(el=>el.remove());
		}else{
			document.querySelector('#logging_in_wrapper .status_message').textContent='Succesfully logged in, redirecting...';

			if(response.redirect == ''){
				// refresh the page
				location.reload();
			}else{
				// go to the redirect page
				location.href = response.redirect;
			}
		}

		return true;
	}else{
		document.getElementById('logging_in_wrapper').classList.add('hidden');

		document.querySelector('.current-method').classList.remove('hidden');

		return false;
	}
}