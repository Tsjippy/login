import {webAuthVerification} from './partials/webauth.js';

console.log('Qr code login loaded');

document.addEventListener("DOMContentLoaded", async function() {
    if(await webAuthVerification(sim.userId, document.getElementById('message'))){
        location.href   =   sim.baseUrl+'?message=Login%20succesfully%20aproved';
    }

    document.querySelectorAll('.loadergif').forEach(el=>el.classList.add('hidden'));
});