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

export function showMessage(message){
	document.querySelector("#login_wrapper .message").innerHTML= DOMPurify.sanitize(message);
}

//show loader
export async function requestLogin(){
	//hide everything
	document.querySelectorAll('.authenticator_wrapper:not(.hidden)').forEach(el=>{
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
		document.querySelector('#logging_in_wrapper .status_message').textContent='Succesfully logged in, redirecting...';

		if(!response.startsWith('http')){
			location.reload();
		}else{
			location.href = response;
		}

		return true;
	}else{
		document.getElementById('logging_in_wrapper').classList.add('hidden');

		document.querySelector('.current-method').classList.remove('hidden');

		return false;
	}
}