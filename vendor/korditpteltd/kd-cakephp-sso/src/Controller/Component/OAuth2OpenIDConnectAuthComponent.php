<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Network\Http\Client;

require_once(ROOT . DS . 'vendor' . DS  . 'google' . DS . 'apiclient' . DS . 'src' . DS . 'Google' . DS . 'autoload.php');
require_once(dirname(__FILE__) . '/../../OAuth/Client.php');

class OAuth2OpenIDConnectAuthComponent extends Component {

    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $client;
    private $authType;
    private $mapping;
    private $userInfoUri;
    private $createUser;

    public $components = ['Auth'];

    public function initialize(array $config) {
        $AuthenticationTypeAttributesTable = TableRegistry::get('SSO.AuthenticationTypeAttributes');
        $oAuthAttributes = $AuthenticationTypeAttributesTable->getTypeAttributeValues('OAuth2OpenIDConnect');
        $this->clientId = $oAuthAttributes['client_id'];
        $this->clientSecret = $oAuthAttributes['client_secret'];
        $this->controller = $this->_registry->getController();

        if (!$this->controller->Auth->user()) {
            $http = new Client();
            $responseBody = [];
            $responseBody[] = $http->post($oAuthAttributes['openid_configuration']);
            $responseBody[] = $http->get($oAuthAttributes['openid_configuration']);

            foreach ($responseBody as $response) {
                if ($response->statusCode() == 200) {
                    // Caching of openid configuration
                    if (!empty($response->body())) {
                        $body = json_decode($response->body(), true);
                        if (isset($body['issuer'])) {
                            if ((isset($oAuthAttributes['issuer']) && $oAuthAttributes['issuer'] != $body['issuer']) || !isset($oAuthAttributes['issuer'])) {
                                $oAuthAttributes['issuer'] = $body['issuer'];
                                $AuthenticationTypeAttributesTable->updateAll([
                                    'value' => $body['issuer']
                                ],[
                                    'authentication_type' => 'OAuth2OpenIDConnect',
                                    'attribute_field' => 'issuer'
                                ]);
                            }
                        }
                        if (isset($body['authorization_endpoint'])) {
                            if ((isset($oAuthAttributes['auth_uri']) && $oAuthAttributes['auth_uri'] != $body['authorization_endpoint']) || !isset($oAuthAttributes['auth_uri'])) {
                                $oAuthAttributes['auth_uri'] = $body['authorization_endpoint'];
                                $AuthenticationTypeAttributesTable->updateAll([
                                    'value' => $body['authorization_endpoint']
                                ],[
                                    'authentication_type' => 'OAuth2OpenIDConnect',
                                    'attribute_field' => 'auth_uri'
                                ]);
                            }
                        }
                        if (isset($body['token_endpoint'])) {
                            if ((isset($oAuthAttributes['token_uri']) && $oAuthAttributes['token_uri'] != $body['token_endpoint']) || !isset($oAuthAttributes['token_uri'])) {
                                $oAuthAttributes['token_uri'] = $body['token_endpoint'];
                                $AuthenticationTypeAttributesTable->updateAll([
                                    'value' => $body['token_endpoint']
                                ],[
                                    'authentication_type' => 'OAuth2OpenIDConnect',
                                    'attribute_field' => 'token_uri'
                                ]);
                            }
                        }
                        if (isset($body['userinfo_endpoint'])) {
                            if ((isset($oAuthAttributes['userInfo_uri']) && $oAuthAttributes['userInfo_uri'] != $body['userinfo_endpoint']) || !isset($oAuthAttributes['userInfo_uri'])) {
                                $oAuthAttributes['userInfo_uri'] = $body['userinfo_endpoint'];
                                $AuthenticationTypeAttributesTable->updateAll([
                                    'value' => $body['userinfo_endpoint']
                                ],[
                                    'authentication_type' => 'OAuth2OpenIDConnect',
                                    'attribute_field' => 'userInfo_uri'
                                ]);
                            }
                        }
                        if (isset($body['jwks_uri'])) {
                            if ((isset($oAuthAttributes['jwk_uri']) && $oAuthAttributes['jwk_uri'] != $body['jwks_uri']) || !isset($oAuthAttributes['jwk_uri'])) {
                                $oAuthAttributes['jwk_uri'] = $body['jwks_uri'];
                                $AuthenticationTypeAttributesTable->updateAll([
                                    'value' => $body['jwks_uri']
                                ],[
                                    'authentication_type' => 'OAuth2OpenIDConnect',
                                    'attribute_field' => 'jwk_uri'
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $this->redirectUri = $oAuthAttributes['redirect_uri'];
        $this->userInfoUri = $oAuthAttributes['userInfo_uri'];

        $this->mapping['username'] = $oAuthAttributes['username_mapping'];
        $this->mapping['firstName'] = $oAuthAttributes['firstName_mapping'];
        $this->mapping['lastName'] = $oAuthAttributes['lastName_mapping'];
        $this->mapping['dob'] = $oAuthAttributes['dob_mapping'];
        $this->mapping['gender'] = $oAuthAttributes['gender_mapping'];
        $this->createUser = isset($oAuthAttributes['allow_create_user']) ?  $oAuthAttributes['allow_create_user'] : 0;

        $hashAttributes = $oAuthAttributes;
        unset($hashAttributes['redirect_uri']);
        $this->authType = Security::hash(serialize($hashAttributes), 'sha256');

        $this->session = $this->request->session();

        $client = new \Custom_Client(null, $oAuthAttributes);
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setAccessType('offline');
        $this->client = $client;

        $this->retryMessage = 'Remote authentication failed. <br>Please try local login or <a href="'.$this->redirectUri.'?submit=retry">Click here</a> to try again';
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    public function beforeFilter(Event $event) {
        if (!$this->session->read('OAuth2OpenIDconnect.remoteFail') && !$this->session->read('Auth.fallback')) {
            $this->controller->Auth->config('authenticate', [
                'Form' => [
                    'userModel' => $this->_config['userModel'],
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
                'SSO.OAuth2OpenIDConnect' => [
                    'userModel' => $this->_config['userModel'],
                    'mapping' => $this->mapping,
                    'userInfoUri' => $this->userInfoUri,
                    'createUser' => $this->createUser
                ]
            ]);
        }
    }

    public function startup(Event $event) {
        if (!$this->controller->Auth->user()) {
            $action = $this->request->params['action'];
            if ($action == $this->config('loginAction') && !$this->session->read('OAuth2OpenIDConnect.remoteFail') && !$this->session->read('Auth.fallback')) {
                $this->idpLogin();
            }
        }
    }

    private function idpLogin() {
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
            $this->session->write('OAuth2OpenIDConnect.accessToken', $client->getAccessToken());
        }

        /************************************************************************************************
          If we have an access token, we can make requests, else we generate an authentication URL.
         ************************************************************************************************/
        if ($this->session->check('OAuth2OpenIDConnect.accessToken') && $this->session->read('OAuth2OpenIDConnect.accessToken')) {
            if ($this->Auth->user()) {
                $client->setAccessToken($this->session->read('OAuth2OpenIDConnect.accessToken'));
            } else {
                // revoke the access token if the user is not authorised
                // $client->revokeToken($this->session->read('OAuth2OpenIDConnect.accessToken'));
                $this->session->delete('OAuth2OpenIDConnect.accessToken');
                $this->controller->Auth->logout();

                if ($this->session->read('OAuth2OpenIDConnect.reLogin')) {
                    $authUrl = $client->createAuthUrl();
                    $this->session->write('OAuth2OpenIDConnect.reLogin', false);
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
                $this->session->write('OAuth2OpenIDConnect.accessToken', $accessToken);
                if (isset(json_decode($accessToken, true)['id_token'])) {
                    // Exception will be thrown if the token signature does not match. This is to prevent
                    // man in the middle
                    $tokenData = $client->verifyIdToken()->getAttributes();
                }
            } else {
                $authUrl = $client->createAuthUrl();
            }
        }

        if (isset($authUrl)) {
            $this->controller->redirect($authUrl);
        }

        /************************************************************************************************
          We check if payload of the token data that was sent back to us. As an additional precaution, we
          verify if the hosted domain is the one that we have set. We will set the session for the token
          data only if the hosted domain matches our setting.
         ************************************************************************************************/
        if (isset($tokenData)) {
            $this->session->write('OAuth2OpenIDConnect.tokenData', $tokenData);
            $this->session->write('OAuth2OpenIDConnect.client', $client);
        } else {
            $this->session->delete('OAuth2OpenIDConnect.tokenData');
            $this->session->delete('OAuth2OpenIDConnect.client', $client);
        }
    }

    public function authenticate(Event $event, ArrayObject $extra) {
        $extra['authType'] = $this->authType;
        if ($this->request->is('get')) {
            if ($this->request->query('submit') == 'retry') {
                $this->session->delete('OAuth2OpenIDConnect.remoteFail');
                $this->session->write('OAuth2OpenIDConnect.reLogin', true);
                return $this->controller->redirect($this->redirectUri);
            }
            $username = 'Not Google Authenticated';
            if (!$this->controller->Auth->user() && !$this->session->read('OAuth2OpenIDConnect.remoteFail') && !$this->session->read('Auth.fallback')) {
               $this->idpLogin();
            } else {
                return $this->checkLogin();
            }
            if ($this->session->check('OAuth2OpenIDConnect.tokenData')) {
                $tokenData = $this->session->read('OAuth2OpenIDConnect.tokenData');
            }
            return $this->checkLogin();
        } else {
            if ($this->request->is('post') && isset($this->request->data['submit'])) {
                if ($this->request->data['submit'] == 'login') {
                    $extra['disableCookie'] = true;
                    $username = $this->request->data('username');
                    $checkLogin = $this->checkLogin($username);
                    if ($checkLogin) {
                        $this->session->write('Auth.fallback', true);
                    }
                    return $checkLogin;
                }
            } else {
                return $this->checkLogin();
            }
            return false;
        }

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
                $this->controller->Auth->setUser($user);
                $this->session->delete('OAuth2OpenIDConnect.remoteFail');
                $extra['loginStatus'] = true;
            }
        } else {
            $extra['loginStatus'] = false;
            if ($this->session->read('Auth.fallback') || $this->session->read('OAuth2OpenIDConnect.remoteFail')) {
                $extra['fallback'] = true;
            }
        }
        $this->controller->dispatchEvent('Controller.Auth.afterCheckLogin', [$extra], $this);
        return $extra['loginStatus'];
    }
}
