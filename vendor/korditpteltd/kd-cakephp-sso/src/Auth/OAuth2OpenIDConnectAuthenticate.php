<?php

namespace SSO\Auth;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Auth\BaseAuthenticate;
use Cake\ORM\TableRegistry;
use Cake\Network\Http\Client;
use Cake\Log\Log;

require_once(ROOT . DS . 'vendor' . DS  . 'google' . DS . 'apiclient' . DS . 'src' . DS . 'Google' . DS . 'autoload.php');

class OAuth2OpenIDConnectAuthenticate extends BaseAuthenticate
{

    public function authenticate(Request $request, Response $response)
    {
        $fields = $this->_config['fields'];
        $mapping = $this->config('mapping');
        $session = $request->session();
        if ($session->check('OAuth2OpenIDConnect.tokenData')) {
            $tokenData = $session->read('OAuth2OpenIDConnect.tokenData');
            // Remove session for the token data after it has been used.
            $session->delete('OAuth2OpenIDConnect.tokenData');
            $accessToken = $session->read('OAuth2OpenIDConnect.accessToken');

            $accessToken = json_decode($accessToken, true);
            $userInfo = [];

            if (isset($tokenData['payload'])) {
               $userInfo = $tokenData['payload'];
            }

            if (!empty($this->config('userInfoUri'))) {
                $http = new Client();
                $response = $http->post($this->config('userInfoUri').'?access_token='.$accessToken['access_token']);

                if ($response->statusCode() != 200) {
                    return false;
                }
                $body = $response->body();
                if (!empty($body)) {
                    $userInfo = array_merge(json_decode($body, true), $userInfo);
                }
            }

            $userName = $this->getUserInfo($userInfo, $mapping['username']);
            Log::write('debug', '[' . $userName . '] Attempt to login as ' . $userName . '@' . $_SERVER['REMOTE_ADDR']);

            if (empty($userName)) {
                return false;
            }

            $isFound = $this->_findUser($userName);

            // If user is found login, if not do create user logic
            if ($isFound) {
                return $isFound;
            } else {
                $userInfo = [
                    'firstName' => $this->getUserInfo($userInfo, $mapping['firstName']),
                    'lastName' => $this->getUserInfo($userInfo, $mapping['lastName']),
                    'gender' => $this->getUserInfo($userInfo, $mapping['gender']),
                    'email' => $this->getUserInfo($userInfo, $mapping['email']),
                    'dateOfBirth' => $this->getUserInfo($userInfo, $mapping['dob'])
                ];

                $User = TableRegistry::get($this->_config['userModel']);
                $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
                if ($event->result === false) {
                    return false;
                } else {
                    return $this->_findUser($event->result);
                }
            }
        }
    }

    private function getUserInfo($userInfo, $variable)
    {
        if (!empty($variable) && isset($userInfo[$variable])) {
            return $userInfo[$variable];
        }
        return '';
    }
}
