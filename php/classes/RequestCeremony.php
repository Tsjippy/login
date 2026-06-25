<?php

namespace TSJIPPY\LOGIN;

use TSJIPPY;

use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\CredentialRecord;
use Webauthn\AuthenticatorAssertionResponse;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register a webauthn method
 */
class RequestCeremony extends WebAuthCeremony
{
    public $ceremonyRequestManager;

    public function __construct()
    {
        parent::__construct();

        $this->ceremonyRequestManager = $this->factory->requestCeremony();
    }

    /**
     * Creates and stores
     */
    public function createOptions()
    {

        $allowedCredentials = [];

        if (!empty($_POST['username'])) {
            if (is_numeric($_POST['username'])) {
                $this->user = get_user_by('ID', TSJIPPY\sanitize($_POST['username']));
            } else {
                $this->user = get_user_by('login', TSJIPPY\sanitize($_POST['username']));
            }

            // List of registered PublicKeyCredentialDescriptor classes associated to the user
            $registeredAuthenticators = $this->getOSCredentials();

            $allowedCredentials = array_map(
                static function (CredentialRecord $credential): PublicKeyCredentialDescriptor {
                    return $credential->getPublicKeyCredentialDescriptor();
                },
                $registeredAuthenticators
            ); // should be null for login without username
        }

        // Public Key Credential Request Options
        $publicKeyCredentialRequestOptions =
            PublicKeyCredentialRequestOptions::create(
                $this->getChallenge(),
                $this->domain,
                allowCredentials: $allowedCredentials,
                userVerification: $this->verificationType
            );

        TSJIPPY\storeInTransient('publicKeyCredentialRequestOptions', $publicKeyCredentialRequestOptions, 5 * MINUTE_IN_SECONDS);

        $jsonObject = $this->serializer->serialize(
            $publicKeyCredentialRequestOptions,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
            ]
        );

        return json_decode($jsonObject);
    }

    public function passkeyLogin()
    {
        // get all passkey login users
        $usedIds    = get_option('tsjippy-webauth-user-handles', []);

        // Find the user id by credential userhandle
        $userId     = false;

        if (!empty($usedIds[$this->publicKeyCredential->response->userHandle])) {
            $userId     = $usedIds[$this->publicKeyCredential->response->userHandle];
        }

        if (empty($userId)) {
            return new \WP_Error('webauthn', "Authenticator id not found");
        }

        $this->user           = get_userdata($userId);

        if (empty($this->user)) {

            return new \WP_Error('webauthn', "User not found");
        }

        $userNameAuth   = $this->user->user_login;

        TSJIPPY\storeInTransient("username", $userNameAuth, 5 * MINUTE_IN_SECONDS);
        TSJIPPY\storeInTransient("allow_passwordless_login", true, 5 * MINUTE_IN_SECONDS);
    }

    public function verifyResponse($response, $isPassKeyLogin)
    {
        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyRequestManager
        );

        $this->loadPublicKey($response);

        if (!$this->publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            //e.g. process here with a redirection to the public key login/MFA page.
            return new WP_Error('tsjippy-login', 'Credential not found!');
        }

        if ($isPassKeyLogin) {
            $this->passkeyLogin();
        } else {
            $this->user = get_user_by('login', TSJIPPY\sanitize($_POST['username']));
        }

        $prevCredential = $this->getCredential($this->publicKeyCredential->rawId);

        if (empty($prevCredential)) {
            // Throw an exception if the credential is not found.
            // It can also be rejected depending on your security policy (e.g. disabled by the user because of loss)
            return new WP_Error('tsjippy-login', 'Credential not found!');
        }

        // Needed after the upgrade to v5.2
        if (empty($prevCredential->aaguid)) {

            $prevCredential->aaguid         = new \Symfony\Component\Uid\UuidV4();

            $prevCredential->uvInitialized  = true;

            $prevCredential->backupEligible = false;

            $prevCredential->backupStatus   = false;
        }

        try {
            $publicKeyCredentialRequestOptions   = TSJIPPY\getFromTransient('publicKeyCredentialRequestOptions');

            $credentialRecord = $authenticatorAssertionResponseValidator->check(
                clone $prevCredential,
                $this->publicKeyCredential->response,
                $publicKeyCredentialRequestOptions,
                $this->domain,
                $this->getUserIdentity()?->id // Should be `null` if the user entity is not known before this step
            );

            /** @disregard P1080 */
            if ($credentialRecord->counter < $prevCredential->counter) {
                /** @disregard P1080 */
                TSJIPPY\printArray("Current counter: $credentialRecord->counter, previous counter: $prevCredential->counter");
                //return new WP_Error('tsjippy-login', 'You cannot use this again, please refresh the page');
            }

            // Update the credential to keep track of the count
            $this->updateUserMeta("tsjippy_2fa_webautn_cred", $credentialRecord, $prevCredential);

            /** @disregard P1080 */
            TSJIPPY\storeInTransient('last-used-cred-id', $credentialRecord->publicKeyCredentialId);

            // Update the last used
            foreach ($this->getCredentialMetas() as $meta) {
                /** @disregard P1080 */
                if ($meta['cred_id'] == $credentialRecord->publicKeyCredentialId) {
                    $newMeta    = $meta;

                    $newMeta['last_used']   = gmdate('Y-m-d H:i:s', current_time('timestamp'));

                    // Update the credential to keep track of the count
                    $this->updateUserMeta("tsjippy_2fa_webautn_cred_meta", $newMeta, $meta);
                }
            }

            return "Verified";
        } catch (\Exception $e) {
            return new \WP_Error('tsjippy-login', $e->getMessage());
        }
    }
}
