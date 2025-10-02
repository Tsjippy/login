console.log("2fa.js loaded");

async function saveTwofaSettings(target){
	// Show loader
	let loader	= target.closest('.submit-wrapper').querySelector('.loader-wrapper');
	loader.classList.remove('hidden');

	let form		= target.closest('form');

	form.querySelectorAll('.hidden [required], select[required]').forEach(el=>{el.required = false});

	let validity	= form.reportValidity();

	if(validity){
		let formData	= new FormData(form);
		let response 	= await FormSubmit.fetchRestApi('login/save_2fa_settings', formData);

		if(response){
			form.querySelectorAll('[id^="setup-"]:not(.hidden)').forEach(el=>el.classList.add('hidden'));

			Main.displayMessage(response, 'success');

			//Show submit button
			target.closest('form').querySelector('.form-submit').classList.add('hidden');
		}
	}

	loader.classList.add('hidden');
}

function showTwofaSetup(target) {
	//hide all options
	document.querySelectorAll('.twofa_option').forEach(el=>el.classList.add('hidden'));

	// Show setup for this method
	var wrapper	= document.getElementById('setup-'+target.value);
	wrapper.classList.remove('hidden');

	if (Main.isMobileDevice()){
		wrapper.querySelectorAll('.mobile.hidden').forEach(el=>el.classList.remove('hidden'));
	}else{
		wrapper.querySelectorAll('.desktop.hidden').forEach(el=>el.classList.remove('hidden'));
	}

	if(target.value == 'authenticator'){
		//Show submit button
		target.closest('form').querySelector('.form-submit').classList.remove('hidden');
	}
}

async function removeWebAuthenticator(target){
	let table   = target.closest('table');
	let row     = target.closest('tr');

	let formData	= new FormData();
	formData.append('key',target.dataset.key);

	Main.showLoader(target, true);

	let response 	= await FormSubmit.fetchRestApi('login/remove_web_authenticator', formData);

	if(response){
		if(table.rows.length==2){
			table.remove();
		}else{
			row.remove();
		}

		Main.displayMessage(response);
	}
}

async function sendValidationEmail(target){
	// Request email code for 2fa login setup
	let loader				= Main.showLoader(target, false, 50, '', true);

	document.getElementById('email-message').innerHTML	= `Sending e-mail... ${loader}`;

	let username	= document.getElementById('username').value;
	let formData	= new FormData();
	formData.append('username', username);

	let response	= await FormSubmit.fetchRestApi('login/request_email_code', formData);

	if(response){
		document.getElementById('email-message').innerHTML	= response;
		document.getElementById('email-message').classList.add('success');

		document.getElementById('email-code-validation').classList.remove('hidden');

		target.classList.add('hidden');

		//Show submit button
		target.closest('form').querySelector('.form-submit').classList.remove('hidden');

		document.getElementById('email-code-validation').focus();
	}
}

document.addEventListener("DOMContentLoaded",function() {
	//hide the webauthn table if not possible
	var el = document.querySelector('#webauthn-wrapper.hidden');
	if(el != null){
		el.classList.remove('hidden');
	}	
});

document.addEventListener('click', ev =>{
	var target = ev.target;

	if(target.name == '2fa-methods[]'){
		showTwofaSetup(target);
	}

	if(target.matches('.remove_webauthn')){
		removeWebAuthenticator(target);
	}

	if(target.name == 'save2fa'){
		saveTwofaSettings(target);
	}

	if(target.id == 'email-code-button'){
		sendValidationEmail(target);
	}
})