<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Google_Client;
use Google_Service_Oauth2;

class GoogleAuthenticate extends BaseAuthenticate
{
    public function authenticate(ServerRequest $request, Response $response)
    {
        $fields = $this->_config['fields'];
        $session = $request->getSession();

        if ($session->check('Google.accessToken')) {
            $authAttribute = $this->getConfig('authAttribute');
            $client = new Google_Client();
            $client->setClientId($authAttribute['client_id']);
            $client->setAccessToken($session->read('Google.accessToken'));
            //POCOR-8498 START
            //$tokenData = $client->verifyIdToken()->getAttributes();
            $tokenData = $client->verifyIdToken();
        
            if (is_object($tokenData) && method_exists($tokenData, 'getAttributes')) {
                $tokenData = $tokenData->getAttributes();
                $email = $tokenData['payload']['email'];
            } else {
                $email = $tokenData['email'];
            }//POCOR-8498 End
            $emailArray = explode('@', $email);
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
                    if ($this->getConfig('createUser')) {
                        //POCOR-8498 Start
                        /*$ServiceOAuth2Object = new Google_Service_Oauth2($client);
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
                        ];*/
                        $userInfo = [
                            'id' => $tokenData['iat'],
                            'firstName' => $tokenData['given_name'],
                            'lastName' => $tokenData['family_name'],
                            'gender' => '',
                            'email' => $tokenData['email'],
                            'verifiedEmail' => $tokenData['email_verified'],
                            'locale' => '',
                            'link' => '',
                            'picture' =>  $tokenData['picture'],
                            'role' => ''
                        ];
                        //POCOR-8498 End
                        $User = TableRegistry::get($this->_config['userModel']);
                        $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
                        if ($event->getResult() === false) {
                            return false;
                        } else {
                            return $this->_findUser($event->getResult());
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
