import { closeMobileMenu } from "@tsjippy/login-shared";

import{
  fetchRestApi
} from "@tsjippy/form_submit_functions";

import { 
  displayMessage 
} from "@tsjippy/display_message";

import { 
  Alert 
} from "@tsjippy/alert";

console.log("logout.js loaded");

//Logout user
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".logout.hidden").forEach((el) => {
    el.addEventListener("click", logout);

    el.classList.remove("hidden");
  });
});

async function logout(event) {
  event.stopPropagation();
  event.preventDefault();

  var target = event.target;

  if (target.matches(".logout")) {
    closeMobileMenu();

    let options = {
      title: `Logging out...`,
    };

    new Alert("", "loader", options);

    var formData = new FormData();

    var response = await fetchRestApi("login/logout", formData);

    if (response) {
      displayMessage(response);

      //redirect to homepage
      location.href = location.href;
    }
  }
}
