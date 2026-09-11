<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Google_Client;

class GoogleAuthenticate extends BaseAuthenticate
{
    public function authenticate(ServerRequest $request, Response $response): array|false
    {
        $fields = $this->getConfig('fields');
        $session = $request->getSession();

        if ($session->check('Google.accessToken')) {
            $authAttribute = $this->getConfig('authAttribute');
            $client = new Google_Client();
            $client->setClientId($authAttribute['client_id']);
            $client->setAccessToken($session->read('Google.accessToken'));

            // POCOR-8498 START
            $tokenData = $client->verifyIdToken();

            if (is_object($tokenData) && method_exists($tokenData, 'getAttributes')) {
                $tokenData = $tokenData->getAttributes();
                $email = $tokenData['payload']['email'];
            } else {
                $email = $tokenData['email'];
            }
            // POCOR-8498 END

            $emailArray = explode('@', $email);
            $userName = $email;
            $hostedDomain = $emailArray[1] ?? '';
            $configHD = $authAttribute['hd'] ?? '';

            // Additional check just in case the hosted domain check fails.
            if (!empty($configHD) && strtolower($hostedDomain) !== strtolower($configHD)) {
                return false;
            }

            $isFound = $this->_findUser($userName);

            // If user is found, login; if not, create user if configured.
            if ($isFound) {
                return $isFound;
            }

            if ($this->getConfig('createUser', false)) {
                $userInfo = [
                    'id' => $tokenData['iat'],
                    'firstName' => $tokenData['given_name'],
                    'lastName' => $tokenData['family_name'],
                    'gender' => '',
                    'email' => $tokenData['email'],
                    'verifiedEmail' => $tokenData['email_verified'],
                    'locale' => '',
                    'link' => '',
                    'picture' => $tokenData['picture'] ?? '',
                    'role' => ''
                ];

                $userModel = $this->getConfig('userModel');

                // CakePHP 5: use the table locator/fetchTable instead of
                // the deprecated TableRegistry::get() call.
                $user = $this->fetchTable($userModel);

                $event = $user->dispatchEvent(
                    'Model.Auth.createAuthorisedUser',
                    [$userName, $userInfo],
                    $this
                );

                if ($event->getResult() === false) {
                    return false;
                }

                return $this->_findUser($event->getResult());
            }

            return false;
        }

        return false;
    }
}
