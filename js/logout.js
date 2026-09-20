import { closeMobileMenu } from "./partials/shared.js";

import{
  fetchRestApi
} from "../../tsjippy-forms/js/form_submit_functions.js";

import { 
  displayMessage 
} from "../../tsjippy-shared-functionality/js/partials/display_message.js";

import { 
  Alert 
} from "../../tsjippy-shared-functionality/js/partials/alert.js";

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
