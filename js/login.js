import {
	closeMobileMenu,
	showMessage,
	togglePassworView
} from './partials/shared.js';

import {
	showLoginQrCode,
	hideQrCode
} from './partials/qr_login.js';

import {
	startConditionalRequest,
	checkWebauthnAvailable,
	webAuthVerification
} from './partials/webauth.js';

//Add an event listener to the login or register button
console.log("Login.js loaded");

const login = class{
	constructor() {
		this.init();

		let params = new Proxy(new URLSearchParams(window.location.search), {
			get: (searchParams, prop) => searchParams.get(prop),
		});

		if(params['showlogin'] != null){
			console.log('Trying silent login');
			startConditionalRequest('silent');
		}

		if(this.checkIsIOS){
			this.addMaximumScaleToMetaViewport();
		}

		this.eventListeners();
	};

	eventListeners(){
		document.addEventListener('keypress', (e) => {
			if (e.key === 'Enter' && this.creds != null){
				e.stopImmediatePropagation();

				if(this.curScreen == this.creds && document.getElementById('check-cred').disabled == false){
					this.verifyCreds();
				}else if(this.curScreen == this.email || this.curScreen == this.twofa){
					this.requestLogin();
				}
			}
		});

		document.addEventListener("click", async (event) => {
			let target = event.target;

			if(target.matches('.login')){
				this.openLoginModal();

				console.log('Trying silent login');
			}else if(target.id == 'check-cred'){
				// Check if a valid username and password is submitted
				this.verifyCreds();
			}else if(target.id == "login-button"){
				// Submit the login form when averything is ok
				this.requestLogin();
			}else if(target.closest('.toggle-pwd-view') != null){
				togglePassworView(event);
			}else if(target.id == 'password-reset-form' || target.id == "lost-pwd-link"){
				this.resetPassword(target);
			}else if(target.id == 'retry_webauthn'){
				this.showMessage('');
				this.verifyWebauthn([]);
			}else if(target.name == 'request_account'){
				this.requestAccount(target);
			}else if(target.closest(`[name='fingerprintpicture']`) != null){
				startConditionalRequest('silent');
			}else if(target.matches('.show-login-qr')){
				showLoginQrCode();
			}else if(target.matches('.close-qr')){
				hideQrCode();
			}else{
				return;
			}

			event.stopImmediatePropagation();
		});
	};

	checkIsIOS(){
		return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
	};

	init(){
		this.form			= document.getElementById('loginform');
		this.msgScreen		= document.getElementById('message-wrapper');
		this.creds			= document.getElementById('credentials-wrapper');
		this.twofa			= document.getElementById('authenticator-wrapper');
		this.email			= document.getElementById('email-wrapper');
		this.login			= document.getElementById('login-button-wrapper');
		this.passwordReset 	= document.getElementById('password-reset-form');

		this.curScreen		= this.creds;
		
		if(this.msgScreen.querySelector('.loader') == null){
			Main.showLoader(this.msgScreen.querySelector('.status-message'), false, 75);
		}
	}

	/**
	 * Clear all inputs and shows the first screen
	 */
	resetForm(){		
		this.reset();

		this.showScreen(this.creds);
		
		this.curScreen		= this.creds;
	}

	/**
	 * Show the loading screen
	 */
	loadingScreen(message){
		this.msgScreen.querySelector('.status-message').textContent	= message;

		this.curScreen.classList.add('hidden');
		this.login.classList.add('hidden');
		this.passwordReset.classList.add('hidden');

		this.msgScreen.classList.remove('hidden');
	}

	/**
	 * Resets the screen to the original login screen
	 */
	reset(){
		if(this.curScreen == undefined){
			return;
		}

		this.curScreen.classList.remove('hidden');
		this.msgScreen.classList.add('hidden');

		this.passwordReset.classList.remove('hidden');
	}

	/**
	 * Shows a particular screen
	 */
	showScreen(screen){
		if(this.curScreen != undefined){
			this.curScreen.classList.add('hidden');
			this.msgScreen.classList.add('hidden');
		}

		screen.classList.remove('hidden');

		if(screen == this.twofa || screen == this.email){
			this.login.classList.remove('hidden');
		}else{
			this.login.classList.add('hidden');
		}

		screen.querySelectorAll('input').forEach(el=>window.setTimeout(() => el.focus(), 0));

		this.curScreen	= screen;
		
		//hide messsages
		showMessage('');
	}
	
	/**
	 * Check if a valid username and password is submitted
	 */
	async verifyCreds(){
		this.username	= document.getElementById('username').value;
		let password	= document.getElementById('password').value;

		// Check if the fields are filled
		if(this.username != '' && password != ''){
			this.passwordReset.classList.add('hidden');

			this.loadingScreen( 'Verifying Credentials');
		}else{
			showMessage('Please give an username and password!');
			return;
		}	

		// Make sure we have a internet connection
		await Main.waitForInternet();

		let formData	= new FormData(this.form);

		let response	= await FormSubmit.fetchRestApi('login/check-cred', formData);

		if(response){
			if(response  && response != false){
				response	= this.addMethods(response);
			}else{
				response 	= false;
			}

			if(!response) {
				this.reset();

				showMessage('Invalid login, try again');
			}
		}else{
			this.reset();
		}
	}

	addMethods(result){			
		if(typeof(result) != 'object'){
			//something went wrong, reload the page
			location.reload();
		}

		if('redirect' in result){
			//redirect to the returned webpage
			location.href	= result.redirect;

			return true;
		}

		if(result instanceof Array){

			if(result.find(element => element == 'webauthn')){
				if(checkWebauthnAvailable()){
					//correct creds and webauthn enabled
					this.verifyWebauthn(result);
				}else if(result.length == 1){
					showMessage('You do not have a valid second login method for this device, please add one.');
					this.requestLogin();
				}else{
					this.showTwoFaFields(result);
				}
			}else{
				//correct creds and 2fa enabled
				this.showTwoFaFields(result);
			}

			return true;
		}
	}

	/**
	 * Initiates and verifies a webauthentication login
	 * @param {*} methods 
	 * @returns 
	 */
	async verifyWebauthn(methods){	
		//show webauthn messages
		this.loadingScreen('Starting Webauthentication');
	
		try{
			let result	= await webAuthVerification(this.username, this.msgScreen.querySelector('.status-message'));
	
			if(!result){
				throw new Error( 'Webauthentication failed' );
			}
	
			//authentication success
			await this.requestLogin();
		}catch (error){	
			let response	= await FormSubmit.fetchRestApi('login/mark_bio_as_failed', '', false);
			
			console.log(response);
	
			if(methods.length == 1){
				showMessage('Authentication failed, please setup an additional login factor.');
				this.requestLogin();
			}else{
				console.error(error);
				let message;
				if(error['message'] == "No authenticator available"){
					message = "No biometric login for this device found. <br>Give verification code.";
				}else{
					message = 'Web authentication failed, please give verification code.';
				}
				showMessage(message);
	
				//Show other 2fa fields
				this.showTwoFaFields(methods);
			}
		}
	}

	/**
	 * Performs the login action
	 */
	//show loader
	async requestLogin(){
		this.loadingScreen('Logging in...');

		let formData	= new FormData(this.form);
		this.form.querySelectorAll('.hidden [required]').forEach(el => {el.required = false});
		let validity	= this.form.reportValidity();

		//if not valid return
		if(!validity){
			this.reset();

			return false;
		}

		await Main.waitForInternet();

		let response	= await FormSubmit.fetchRestApi('login/request_login', formData);

		if(!response){
			this.reset();

			return false;
		}

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
			this.loadingScreen('Succesfully logged in, redirecting...');

			if(response.redirect == ''){
				// refresh the page
				location.reload();
			}else{
				// go to the redirect page
				location.href = response.redirect;
			}
		}

		return true;
	}

	/**
	 * Display the form for the 2fa email or authenticator code
	 */
	showTwoFaFields(methods){
		if(methods.includes('email')){
			this.requestEmailCode();
		}

		//show 2fa fields
		for(const method of methods){
			//do not show webauthn
			if(method == 'webauthn'){
				continue;
			}

			if(method == 'email'){
				this.showScreen(this.email);
			}else{
				this.showScreen(this.twofa);
			}
		}
	}

	/**
	 * Request email code for 2fa login
	 */
	async requestEmailCode(){
		// Show the email screen
		this.showScreen(this.email);

		let loader				= Main.showLoader(null, false, 20, '', true);
		showMessage(`Sending e-mail... ${loader}`);

		let formData	= new FormData();
		formData.append('username', this.username);
	
		let response	= await FormSubmit.fetchRestApi('login/request_email_code', formData, false);
		
		if(response){
			showMessage(response);
		}else{
			showMessage(`Sending e-mail failed`);
		}
	}

	//request password reset e-mail
	async resetPassword(target){		
		let form 			= target.closest('form');

		let button			= form.querySelector('#lost-pwd-link');

		let extraElements	= form.querySelector('div.form-elements');

		// If the extra elements are not present, create them
		if(extraElements != null && extraElements.innerHTML != '' && extraElements.classList.contains('hidden')){
			// Hide the login form
			document.getElementById('loginform').classList.add('hidden');

			if(form.querySelector(`[name='username']`) == null){
				extraElements.innerHTML	= `<label class="form-label">
					Username<br>
					<input type="text" name="username" id="username" class="form-control" value="${this.username}" required>
				</label><br>`+extraElements.innerHTML;
			}

			// Show the form
			extraElements.classList.remove('hidden');

			button.dataset.orghtml	= button.innerHTML;
			button.innerHTML	= 'Request Password Reset';

			button.classList.add('button');

			return;
		}

		if(this.username == ''){
			Main.displayMessage('Specify your username first', 'error');
			return;
		}
		
		button.classList.add('hidden');
		let loader = Main.showLoader(button, false, 50, 'Requesting Password Reset...   ');

		let formData	= new FormData(form);
		formData.append('username', this.username);
		
		let response	= await FormSubmit.fetchRestApi('login/request_pwd_reset', formData);

		if (response) {
			Main.displayMessage(response, 'success');
			
			// Show the login form
			document.getElementById('loginform').classList.remove('hidden');

			extraElements.classList.add('hidden');

			button.innerHTML	= button.dataset.orghtml;
			button.classList.remove('button');
		}

		loader.remove();
		button.classList.remove('hidden');

	}

	// request a new user account
	async requestAccount(target){
		let form 		= target.closest('form');

		// Show loader
		form.querySelector('.loader-wrapper').classList.remove('hidden');

		let formData	= new FormData(form);

		let response	= await FormSubmit.fetchRestApi('login/request_user_account', formData);
		
		if(response){
			Main.displayMessage(response);
		}

		// reset form 
		form.reset();

		// Hide loader
		form.querySelector('.loader-wrapper').classList.add('hidden');
	}

	// Show the modal with the login form
	openLoginModal(){
		// Make sure the menu is closed
		closeMobileMenu();

		//prevent page scrolling
		document.querySelector('body').style.overflowY = 'hidden';

		this.modal	= document.getElementById('login-modal');
		this.modal.style.display = 'block';

		this.modal.classList.remove('hidden');

		this.resetForm();		
	}
	
	//prevent zoom in on login form on a iphone
	addMaximumScaleToMetaViewport(){
		let el = document.querySelector('meta[name=viewport]');
	
		if (el !== null) {
			let content	= el.getAttribute('content');
			let re 		= /maximum\-scale=[0-9\.]+/g;
		
			if (re.test(content)) {
				content = content.replace(re, 'maximum-scale=1.0');
			} else {
				content = [content, 'maximum-scale=1.0'].join(', ')
			}
		
			el.setAttribute('content', content);
		}
	};
}

// SHow the login button
document.addEventListener('DOMContentLoaded', () => {
	//check if the current browser supports webauthn
	checkWebauthnAvailable();

	document.querySelectorAll('.login.hidden').forEach(el=>{
		el.classList.remove('hidden');
	});	

	sim.login = new login();
});