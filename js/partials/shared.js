export function closeMobileMenu(){
	//close mobile menu
	document.querySelectorAll('#site-navigation, #mobile-menu-control-wrapper').forEach(el=>el.classList.remove('toggled'));
	document.querySelector('body').classList.remove('mobile-menu-open');
	document.querySelectorAll("#mobile-menu-control-wrapper > button").forEach(el=>el.ariaExpanded = 0);
}

export function showMessage(message){
	let el = document.querySelector("#message");
	el.innerHTML= DOMPurify.sanitize(message);
}

export function togglePassworView(ev){
	ev.stopImmediatePropagation();
	
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