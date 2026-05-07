import {
    closeMobileMenu
} from './partials/shared.js';

console.log("logout.js loaded");

//Logout user
document.addEventListener("DOMContentLoaded",function() {

	document.querySelectorAll('.logout.hidden').forEach(el=>{
        el.addEventListener('click', logout);

        el.classList.remove('hidden');
    });
});

async function logout(event){
    event.stopPropagation();
    event.preventDefault();

	var target = event.target;
    
	if(target.matches(".logout")){
        closeMobileMenu();

        let options	= {
            title: `Logging out...`
        };

        new Main.Alert('', 'loader', options);

        var formData	= new FormData();

        var response    = await FormSubmit.fetchRestApi('login/logout', formData);

        if(response){
            Main.displayMessage(response);

            //redirect to homepage
            location.href	= tsjippy.baseUrl;
        }
    }
}


