// Import the registration hook
import { checkWebauthnAvailable } from "@tsjippy/webauth";

import { startRegistration } from "@simplewebauthn/browser";

import { showMessage, showStatusMessage } from "@tsjippy/login-shared";

import{
  fetchRestApi
} from "@tsjippy/form_submit_functions";

export async function registerWebAuthn() {
  if (window.webauth_register == undefined) {
    window.webauth_register = "running";
    if (!checkWebauthnAvailable()) {
      return;
    }
  }

  showMessage("Please take a few seconds to setup your login token...");
  showStatusMessage("Preparing your Passkey...");

  // Get registration options from the endpoint
  const optionsJSON = await fetchRestApi(
    "login/fingerprint_options",
  );

  // Update the message
  showStatusMessage("Please authenticate...");

  let attResp;
  try {
    // Try to use the browser's auto register feature first
    attResp = await startRegistration({ optionsJSON, useAutoRegister: true });
  } catch (error) {
    try {
      // Pass the options to the authenticator and wait for a response
      attResp = await startRegistration({ optionsJSON });
    } catch (error) {
      // Handle different error types
      if (error.name === "NotAllowedError") {
        showStatusMessage("Operation cancelled or timed out");
      } else if (error.name === "InvalidStateError") {
        showStatusMessage("Authenticator already registered");
      } else if (error.name === "NotSupportedError") {
        showStatusMessage("WebAuthn not supported in this browser");
      } else if (error.name === "AbortError") {
        showStatusMessage("Operation was aborted");
      } else {
        showStatusMessage("Authentication failed: " + error.message);
      }

      return;
    }
  }

  showMessage("");

  showStatusMessage("Registering Authenticator");

  let formData = new FormData();
  formData.append("identifier", `${navigator.userAgentData.platform}-${navigator.appCodeName}`);
  formData.append("publicKeyCredential", btoa(JSON.stringify(attResp)));

  let response = await fetchRestApi(
    "login/store_fingerprint",
    formData,
  );
  if (!response) {
    return;
  }

  showStatusMessage("Registration success");
}
