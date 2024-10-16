import {webAuthVerification} from './partials/webauth.js';

console.log('Qr code login loaded');

document.addEventListener("DOMContentLoaded", async function() {
    if(await webAuthVerification(sim.userId, document.getElementById('message'))){
        document.querySelector('main').innerHTML = `You can close this window now.<br><br> You will be redirected to the home page automatically in <span id="countdown">6</span> seconds.`;
        setInterval(function(){
            let counter = document.getElementById('countdown');

            let value   = parseInt(counter.textContent);

            // close the tab
            if(value < 1){
                location.href   =   sim.baseUrl+'?message=Login%20succesfully%20aproved';
            }else{
                value--;

                counter.textContent = value;
            }
        }, 1000);
    }

    document.querySelectorAll('.loadergif').forEach(el=>el.classList.add('hidden'));
});