<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use SSO\OAuth\Custom_Client;

class OAuthAuthenticate extends BaseAuthenticate
{
    public function authenticate(ServerRequest $request, Response $response): array|false
    {
        $oAuthAttributes = $this->getConfig('authAttribute');
        $mappingAttributes = $this->getConfig('mappingAttribute');
        $session = $request->getSession();

        if (!$session->check('OAuth.accessToken')) {
            return false;
        }

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

            $responseBody[] = $http->get(
                $oAuthAttributes['userinfo_endpoint'],
                [],
                [
                    'headers' => [
                        'authorization' => $accessToken['token_type'] . ' ' . $accessToken['access_token']
                    ],
                    'redirect' => 3
                ]
            );

            foreach ($responseBody as $response) {
                if ($response->getStatusCode() === 200) {
                    $body = $response->getStringBody();

                    if (!empty($body)) {
                        $decodedBody = json_decode($body, true);

                        if (is_array($decodedBody)) {
                            $userInfo = array_merge($decodedBody, $userInfo);
                        }
                    }
                }
            }
        }

        $userName = $this->getUserInfo(
            $userInfo,
            $mappingAttributes['mapped_username']
        );

        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        Log::write(
            'debug',
            '[' . $userName . '] Attempt to login as ' . $userName . '@' . $remoteAddress
        );

        if (empty($userName)) {
            return false;
        }

        $isFound = $this->_findUser($userName);

        // If user is found, login; if not, create user if configured.
        if ($isFound) {
            return $isFound;
        }

        if (!$this->getConfig('createUser', false)) {
            return false;
        }

        $userInfo = [
            'firstName' => $this->getUserInfo(
                $userInfo,
                $mappingAttributes['mapped_first_name']
            ),
            'lastName' => $this->getUserInfo(
                $userInfo,
                $mappingAttributes['mapped_last_name']
            ),
            'gender' => $this->getUserInfo(
                $userInfo,
                $mappingAttributes['mapped_gender']
            ),
            'dateOfBirth' => $this->getUserInfo(
                $userInfo,
                $mappingAttributes['mapped_date_of_birth']
            ),
            'role' => $this->getUserInfo(
                $userInfo,
                $mappingAttributes['mapped_role']
            ),
            'email' => $this->getUserInfo(
                $userInfo,
                $mappingAttributes['mapped_email']
            )
        ];

        $userModel = $this->getConfig('userModel');
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

    private function getUserInfo(array $userInfo, ?string $variable): string
    {
        if (!empty($variable) && isset($userInfo[$variable])) {
            return (string)$userInfo[$variable];
        }

        return '';
    }
}
