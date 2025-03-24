import {
	showMessage
} from './shared.js';

let intervalId;
let checkCount			= 5;

// Show qr code and start polling for login info
export function showLoginQrCode(){
	document.getElementById('usercred_wrapper').classList.add('hidden');

	showMessage('Fetching QR code...');

	Main.showLoader(document.getElementById(`qrcode-wrapper`).firstChild);

	// get the QR code
	refreshQrCode();
	
	 // Check for login permission every 5 seconds
	intervalId	= setInterval(refreshQrCode, 5000);
}

function login(response){
	clearInterval(intervalId);

	showMessage('Succesfully logged in, redirecting...', 'success');

	Main.showLoader(document.getElementById(`qrcode-wrapper`));

	if(!response.startsWith('http')){
		location.reload();
	}else{
		location.href = response;
	}
}

async function refreshQrCode(){
	checkCount++;

    let formData    = new FormData();
	let wrapper		= document.getElementById(`qrcode-wrapper`);
	let qrCodeImage = document.getElementById('login-qr-code');

	// refresh the qr code after 30 seconds
	if(checkCount == 6){
		console.log('Refreshing QR code');

        checkCount      = 0;

		// also check if previous qr code has been scanned
		if(qrCodeImage != null){
			formData.append('token', qrCodeImage.dataset.token);
			formData.append('key', qrCodeImage.dataset.key);
		}
		
		// Use AJAX to get the qr code
		let response    = await FormSubmit.fetchRestApi('login/get_login_qr_code', formData);

		if(response){
			wrapper.innerHTML=response;

			showMessage('Scan the QR code to login');

			console.log(`New token: ${document.getElementById('login-qr-code').dataset.token}`);
			console.log(`New key: ${document.getElementById('login-qr-code').dataset.key}`);
		}else{
			wrapperinnerHTML='';

			showMessage('QR Code Refresh Failed', 'error');
		}
	}else{
		console.log('Checking for login permission');
		
        if(qrCodeImage == null){
            return;
        }

		// Use AJAX to check if the code has been scanned
        formData.append('token', qrCodeImage.dataset.token);
        formData.append('key', qrCodeImage.dataset.key);
        formData.append('old-token', qrCodeImage.dataset.oldtoken);
		let response	= await FormSubmit.fetchRestApi('login/qr_code_scanned', formData);

		if(response){
			login(response);
		}
	}
}

export function hideQrCode(){
	clearInterval(intervalId);

	showMessage('');

	document.getElementById('usercred_wrapper').classList.remove('hidden');

	document.getElementById(`qrcode-wrapper`).innerHTML = '';
}