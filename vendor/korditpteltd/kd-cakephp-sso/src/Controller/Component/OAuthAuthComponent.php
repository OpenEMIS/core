<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Google_Auth_Exception;
use SSO\OAuth\Custom_Client;

class OAuthAuthComponent extends Component
{

    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $client;
    private $authType;
    private $mapping;
    private $userInfoUri;
    private $createUser;

    public $components = ['Auth'];

    public function initialize(array $config)
    {
        $oAuthAttributes = $config['authAttribute'];
        $mappingAttributes = $config['mappingAttribute'];
        $this->clientId = $oAuthAttributes['client_id'];
        $this->clientSecret = $oAuthAttributes['client_secret'];
        $this->controller = $this->_registry->getController();

        $http = new Client();
        $responseBody = [];
        if (isset($oAuthAttributes['well_known_uri']) && !empty($oAuthAttributes['well_known_uri'])) {
            $responseBody[] = $http->get($oAuthAttributes['well_known_uri'], [], ['redirect' => 3]);
        }

        foreach ($responseBody as $response) {
            if ($response->getStatusCode() == 200) {
                $isChange = false;
                // Caching of openid configuration
                if (!empty($response->body())) {
                    $body = json_decode($response->body(), true);
                    if (isset($body['issuer'])) {
                        if ((isset($oAuthAttributes['issuer']) && $oAuthAttributes['issuer'] != $body['issuer']) || !isset($oAuthAttributes['issuer'])) {
                            $oAuthAttributes['issuer'] = $body['issuer'];
                            $isChange = true;
                        }
                    }
                    if (isset($body['authorization_endpoint'])) {
                        if ((isset($oAuthAttributes['auth_uri']) && $oAuthAttributes['auth_uri'] != $body['authorization_endpoint']) || !isset($oAuthAttributes['auth_uri'])) {
                            $oAuthAttributes['authorization_endpoint'] = $body['authorization_endpoint'];
                            $isChange = true;
                        }
                    }
                    if (isset($body['token_endpoint'])) {
                        if ((isset($oAuthAttributes['token_uri']) && $oAuthAttributes['token_uri'] != $body['token_endpoint']) || !isset($oAuthAttributes['token_uri'])) {
                            $oAuthAttributes['token_endpoint'] = $body['token_endpoint'];
                            $isChange = true;
                        }
                    }
                    if (isset($body['userinfo_endpoint'])) {
                        if ((isset($oAuthAttributes['userInfo_uri']) && $oAuthAttributes['userInfo_uri'] != $body['userinfo_endpoint']) || !isset($oAuthAttributes['userInfo_uri'])) {
                            $oAuthAttributes['userinfo_endpoint'] = $body['userinfo_endpoint'];
                            $isChange = true;
                        }
                    }
                    if (isset($body['jwks_uri'])) {
                        if ((isset($oAuthAttributes['jwk_uri']) && $oAuthAttributes['jwk_uri'] != $body['jwks_uri']) || !isset($oAuthAttributes['jwk_uri'])) {
                            $oAuthAttributes['jwks_uri'] = $body['jwks_uri'];
                            $isChange = true;
                        }
                    }

                    if ($isChange) {
                        $OAuthTable = TableRegistry::get('SSO.IdpOauth');
                        $entity = $OAuthTable->get(['system_authentication_id' => $oAuthAttributes['system_authentication_id']]);
                        $entity = $OAuthTable->patchEntity($entity, $oAuthAttributes);
                        $OAuthTable->save($entity);
                    }
                }
            }
        }

        $this->redirectUri = $oAuthAttributes['redirect_uri'];
        $this->userInfoUri = $oAuthAttributes['userinfo_endpoint'];

        $this->mapping['username'] = $mappingAttributes['mapped_username'];
        $this->mapping['firstName'] = $mappingAttributes['mapped_first_name'];
        $this->mapping['lastName'] = $mappingAttributes['mapped_last_name'];
        $this->mapping['dob'] = $mappingAttributes['mapped_date_of_birth'];
        $this->mapping['gender'] = $mappingAttributes['mapped_gender'];
        $this->mapping['email'] = $mappingAttributes['mapped_email'];
        $this->createUser = $mappingAttributes['allow_create_user'];

        $hashAttributes = $oAuthAttributes;
        unset($hashAttributes['redirect_uri']);
        $this->authType = Security::hash(serialize($hashAttributes), 'sha256');

        $this->session = $this->request->session();

        $client = new Custom_Client(null, $oAuthAttributes);
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setAccessType('offline');
        $this->client = $client;

        $this->retryMessage = 'Remote authentication failed. <br>Please try local login or <a href="'.$this->redirectUri.'?submit=retry">Click here</a> to try again';

        $this->Auth->config('authenticate', [
                'Form' => [
                    'userModel' => $this->_config['userModel'],
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
                'SSO.OAuth' => [
                    'userModel' => $this->_config['userModel'],
                    'mappingAttribute' => $mappingAttributes,
                    'authAttribute' => $oAuthAttributes,
                    'createUser' => $mappingAttributes['allow_create_user']
                ]
            ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    private function idpLogin()
    {
        $client = $this->client;
        /************************************************************************************************
          If we have a code back from the OAuth 2.0 flow, we need to exchange that with the authenticate()
          function. We store the resultant access token bundle in the session, and redirect to ourself.
         ************************************************************************************************/
        if ($this->request->query('code')) {
            try {
                $client->authenticate($this->request->query('code'));
            } catch (\Google_Auth_Exception $e) {
                return;
            }
            $this->session->write('OAuth.accessToken', $client->getAccessToken());
        }

        /************************************************************************************************
          If we have an access token, we can make requests, else we generate an authentication URL.
         ************************************************************************************************/
        if ($this->session->check('OAuth.accessToken') && $this->session->read('OAuth.accessToken')) {
            if ($this->Auth->user()) {
                $client->setAccessToken($this->session->read('OAuth.accessToken'));
            } else {
                // revoke the access token if the user is not authorised
                // $client->revokeToken($this->session->read('OAuth.accessToken'));
                $this->session->delete('OAuth.accessToken');
                $this->Auth->logout();

                if ($this->session->read('OAuth.reLogin')) {
                    $authUrl = $client->createAuthUrl();
                    $this->session->write('OAuth.reLogin', false);
                }
            }
        } else {
            $authUrl = $client->createAuthUrl();
        }
        /************************************************************************************************
          If we're signed in we can go ahead and retrieve the ID token, which is part of the bundle of
          data that is exchange in the authenticate step - we only need to do a network call if we have
          to retrieve the Google certificate to verify it, and that can be cached.
         ************************************************************************************************/

        if ($client->getAccessToken()) {
            // Check if the access token is expired, if it is expired reauthenticate
            if (!$client->isAccessTokenExpired()) {
                $accessToken = $client->getAccessToken();
                $this->session->write('OAuth.accessToken', $accessToken);
                return $this->checkLogin();
            } else {
                $authUrl = $client->createAuthUrl();
            }
        }

        if (isset($authUrl)) {
            $this->controller->redirect($authUrl);
        }

        return false;
    }

    public function authenticate(Event $event, ArrayObject $extra)
    {
        return $this->idpLogin();
    }

    private function checkLogin($username = null, $extra = [])
    {
        $user = $this->Auth->identify();
        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;
        if ($user) {
            if ($user[$this->_config['statusField']] != 1) {
                $extra['status'] = true;
            } else {
                $this->Auth->setUser($user);
                $this->session->delete('OAuth.remoteFail');
                $extra['loginStatus'] = true;
            }
        } else {
            $extra['loginStatus'] = false;
            if ($this->session->read('Auth.fallback') || $this->session->read('OAuth.remoteFail')) {
                $extra['fallback'] = true;
            }
        }
        $this->controller->dispatchEvent('Controller.Auth.afterCheckLogin', [$extra], $this);
        return $extra['loginStatus'];
    }
}
