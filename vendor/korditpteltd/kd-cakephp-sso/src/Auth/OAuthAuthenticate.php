<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use SSO\OAuth\Custom_Client;

class OAuthAuthenticate extends BaseAuthenticate
{

    public function authenticate(Request $request, Response $response)
    {
        $oAuthAttributes = $this->config('authAttribute');
        $mappingAttributes = $this->config('mappingAttribute');
        $session = $request->session();
        if ($session->check('OAuth.accessToken')) {
            $client = new Custom_Client(null, $oAuthAttributes);
            $client->setClientId($oAuthAttributes['client_id']);
            $client->setClientSecret($oAuthAttributes['client_secret']);
            $client->setScopes(['openid', 'email', 'profile']);
            $accessToken = $session->read('OAuth.accessToken');
            $client->setAccessToken($accessToken);
            $tokenData = $client->verifyIdToken()->getAttributes();

            $accessToken = json_decode($accessToken, true);
            $userInfo = [];
            if (isset($tokenData['payload'])) {
                $userInfo = $tokenData['payload'];
            }

            if (!empty($oAuthAttributes['userinfo_endpoint'])) {
                $http = new Client();
                $responseBody = [];
                $responseBody[] = $http->get($oAuthAttributes['userinfo_endpoint'], [], ['headers' => ['authorization' => $accessToken['token_type'].' '.$accessToken['access_token']], 'redirect' => 3]);

                foreach ($responseBody as $response) {
                    if ($response->getStatusCode() == 200) {
                        $body = $response->body();
                        if (!empty($body)) {
                            $userInfo = array_merge(json_decode($body, true), $userInfo);
                        }
                    }
                }
            }

            $userName = $this->getUserInfo($userInfo, $mappingAttributes['mapped_username']);
            Log::write('debug', '[' . $userName . '] Attempt to login as ' . $userName . '@' . $_SERVER['REMOTE_ADDR']);
            if (empty($userName)) {
                return false;
            }

            $isFound = $this->_findUser($userName);

            // If user is found login, if not do create user logic
            if ($isFound) {
                return $isFound;
            } else {
                if ($this->config('createUser')) {
                    $userInfo = [
                        'firstName' => $this->getUserInfo($userInfo, $mappingAttributes['mapped_first_name']),
                        'lastName' => $this->getUserInfo($userInfo, $mappingAttributes['mapped_last_name']),
                        'gender' => $this->getUserInfo($userInfo, $mappingAttributes['mapped_gender']),
                        'dateOfBirth' => $this->getUserInfo($userInfo, $mappingAttributes['mapped_date_of_birth']),
                        'role' => $this->getUserInfo($userInfo, $mappingAttributes['mapped_role']),
                        'email' => $this->getUserInfo($userInfo, $mappingAttributes['mapped_email'])
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
    }

    private function getUserInfo($userInfo, $variable)
    {
        if (!empty($variable) && isset($userInfo[$variable])) {
            return $userInfo[$variable];
        }
        return '';
    }
}
