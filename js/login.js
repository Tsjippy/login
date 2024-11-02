import {
	closeMobileMenu,
	showMessage,
	requestLogin
} from './partials/shared.js';

import {
	showLoginQrCode,
	hideQrCode
} from './partials/qr_login.js';

import {
	verifyWebauthn,
	startConditionalRequest,
	checkWebauthnAvailable,
	showTwoFaFields,
	webauthnSupported
} from './partials/webauth.js';

//Add an event listener to the login or register button
console.log("Login.js loaded");

let modal;

// Check if a valid username and password is submitted
async function verifyCreds(){
	var username	= document.getElementById('username').value;
	var password	= document.getElementById('password').value;

	// Check if the fields are filled
	if(username != '' && password != ''){
		document.querySelector('#usercred_wrapper .loadergif').classList.remove('hidden');
	}else{
		showMessage('Please give an username and password!');
		return;
	}

	// Make sure we have a internet connection
	await Main.waitForInternet();

	var formData	= new FormData();
	formData.append('username',username);
	formData.append('password',password);

	var response	= await FormSubmit.fetchRestApi('login/check_cred', formData);

 	if(response){
		if(response == 'false') {
			showMessage('Invalid login, try again');
			
			// hide loader
			document.querySelector('#usercred_wrapper .loadergif').classList.add('hidden');
		} else {
			addMethods(response);
		}
	}
}

//request password reset e-mail
async function resetPassword(form){
	var username	= document.getElementById('username').value;

	if(username == ''){
		Main.displayMessage('Specify your username first','error');
		return;
	}

	let button	= form.querySelector('#lost_pwd_link');
	
	button.classList.add('hidden');
	let loader = Main.showLoader(button, false, 'Sending e-mail...   ');

	let formData	= new FormData(form);
	formData.append('username', username);
	
	let response	= await FormSubmit.fetchRestApi('login/request_pwd_reset', formData);

	if (response) {
		Main.displayMessage(response,'success');
	}

	loader.remove();
	button.classList.remove('hidden');
}

// request a new user account
async function requestAccount(target){
	var form 		= target.closest('form');

	// Show loader
	form.querySelector('.loadergif').classList.remove('hidden');

	var formData	= new FormData(form);

	var response	= await FormSubmit.fetchRestApi('login/request_user_account', formData);
	
	if(response){
		Main.displayMessage(response);
	}

	// reset form 
	form.reset();

	// Hide loader
	form.querySelector('.loadergif').classList.add('hidden');
}

function addMethods(result){
	document.querySelector('#usercred_wrapper .loadergif').classList.add('hidden');
	
	if(typeof(result) == 'string' && result){
		//hide login form
		document.querySelectorAll("#usercred_wrapper").forEach(el=>el.classList.add('hidden'));

		document.getElementById('logging_in_wrapper').classList.remove('hidden');

		if(location.href != result){
			//redirect to the returned webpage
			location.href	= result;
		}else{
			//close login modal
			Main.hideModals();
		}
	}else if(!result){
		//incorrect creds add message, but only once
		showMessage('Invalid username or password!');
	}else if(typeof(result) == 'object'){
		//hide cred fields
		document.querySelectorAll("#usercred_wrapper").forEach(el=>el.classList.add('hidden'));

		//hide messsages
		showMessage('');

		if(result.find(element => element == 'webauthn')){
			if(webauthnSupported){
				//correct creds and webauthn enabled
				verifyWebauthn(result);
			}else if(result.length == 1){
				showMessage('You do not have a valid second login method for this device, please add one.');
				requestLogin();
			}else{
				showTwoFaFields(result);
			}
		}else{
			//correct creds and 2fa enabled
			showTwoFaFields(result);
		}
	}else{
		//something went wrong, reload the page
		location.reload();
	}
}

document.addEventListener('keypress', function (e) {
    if (e.key === 'Enter' && document.querySelector("#usercred_wrapper") != null){
		if(!document.querySelector("#usercred_wrapper").classList.contains('hidden')) {
			verifyCreds();
		}else if(!document.querySelector("#submit_login_wrapper").classList.contains('hidden')){
			requestLogin();
		}
	}
});

export function togglePassworView(ev){
	var target	= ev.target;

	if(ev.target.tagName == 'IMG'){
		target	= ev.target.parentNode;
	}

	if(target.dataset.toggle == '0'){
		target.title								= 'Hide password';
		target.dataset.toggle						= '1';
		target.innerHTML							= target.innerHTML.replace('invisible', 'visible');
		target.closest('.password').querySelector('input[type="password"]').type	= 'text';
	}else{
		target.title								= 'Show password';
		target.dataset.toggle						= '0';
		target.innerHTML							= target.innerHTML.replace('visible', 'invisible');
		target.closest('.password').querySelector('input[type="text"]').type	= 'password';
	}
}

// Show the modal with the login form
function openLoginModal(){
	// Make sure the menu is closed
	closeMobileMenu();

	//prevent page scrolling
	document.querySelector('body').style.overflowY = 'hidden';

	modal	= document.getElementById('login_modal');
	modal.style.display = 'block';

	//reset form
	modal.querySelectorAll('.authenticator_wrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
	modal.querySelector('#usercred_wrapper').classList.remove('hidden');

	modal.classList.remove('hidden');

	window.setTimeout(() => modal.querySelector('#username').focus(), 0);
}

document.addEventListener('DOMContentLoaded', () => {
	//check if the current browser supports webauthn
	checkWebauthnAvailable();

	document.querySelectorAll('.login.hidden').forEach(el=>{
		el.classList.remove('hidden');
	});	
});

document.addEventListener("click", async function(event){
	var target = event.target;

	if(target.matches('.login')){
		openLoginModal();

		console.log('Trying silent login');
		let result	= await startConditionalRequest('silent');

		// Show modal with login form
		if(!result){
			showMessage('Automatic passkey login failed, try using your username and password');

			openLoginModal();
		}

	}else if(target.id == 'check_cred'){
		// Check if a valid username and password is submitted
		verifyCreds();
	}else if(target.id == "login_button"){
		// Submit the login form when averything is ok
		requestLogin();
	}else if(target.closest('.toggle_pwd_view') != null){
		togglePassworView(event);
	}else if(target.id == 'password-reset-form' || target.id == "lost_pwd_link"){
		resetPassword(target.closest('form'));
	}else if(target.id == 'retry_webauthn'){
		showMessage('');
		verifyWebauthn([]);
	}else if(target.name == 'request_account'){
		requestAccount(target);
	}else if(target.closest(`[name='fingerprintpicture']`) != null){
		startConditionalRequest('silent');
	}else if(target.matches('.show-login-qr')){
		showLoginQrCode();
	}else if(target.matches('.close-qr')){
		hideQrCode();
	}
});

//prevent zoom in on login form on a iphone
const addMaximumScaleToMetaViewport = () => {
	const el = document.querySelector('meta[name=viewport]');
  
	if (el !== null) {
	  let content = el.getAttribute('content');
	  let re = /maximum\-scale=[0-9\.]+/g;
  
	  if (re.test(content)) {
		  content = content.replace(re, 'maximum-scale=1.0');
	  } else {
		  content = [content, 'maximum-scale=1.0'].join(', ')
	  }
  
	  el.setAttribute('content', content);
	}
};

const checkIsIOS = () =>/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

if (checkIsIOS()) {
	addMaximumScaleToMetaViewport();
}

let params = new Proxy(new URLSearchParams(window.location.search), {
	get: (searchParams, prop) => searchParams.get(prop),
});

if(params['showlogin'] != null){
	console.log('Trying silent login');
	startConditionalRequest('silent');
}