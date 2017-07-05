<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Google_Client;
use Google_Service_Oauth2;

class GoogleAuthenticate extends BaseAuthenticate
{
    public function authenticate(Request $request, Response $response)
    {
        $fields = $this->_config['fields'];
        $session = $request->session();

        if ($session->check('Google.accessToken')) {
            $authAttribute = $this->config('authAttribute');
            $client = new Google_Client();
            $client->setClientId($authAttribute['client_id']);
            $client->setAccessToken($session->read('Google.accessToken'));
            $tokenData = $client->verifyIdToken()->getAttributes();
            $email = $tokenData['payload']['email'];
            $emailArray = explode('@', $tokenData['payload']['email']);
            $userName = $email;
            $hostedDomain = $emailArray[1];
            $configHD = $authAttribute['hd'];
            // Additional check just in case the hosted domain check fail
            if (!empty($configHD) && strtolower($hostedDomain) != strtolower($configHD)) {
                return false;
            } else {
                $isFound = $this->_findUser($userName);
                // If user is found login, if not do create user logic
                if ($isFound) {
                    return $isFound;
                } else {
                    if ($this->config('createUser')) {
                        $ServiceOAuth2Object = new Google_Service_Oauth2($client);
                        $me = $ServiceOAuth2Object->userinfo->get();
                        $userInfo = [
                            'id' => $me->getId(),
                            'firstName' => $me->getGivenName(),
                            'lastName' => $me->getFamilyName(),
                            'gender' => $me->getGender(),
                            'email' => $me->getEmail(),
                            'verifiedEmail' => $me->getVerifiedEmail(),
                            'locale' => $me->getLocale(),
                            'link' => $me->getLink(),
                            'picture' => $me->getPicture(),
                            'role' => ''
                        ];
                        $User = TableRegistry::get($this->_config['userModel']);
                        $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
                        if ($event->result === false) {
                            return false;
                        } else {
                            return $this->_findUser($event->result);
                        }
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }
}
