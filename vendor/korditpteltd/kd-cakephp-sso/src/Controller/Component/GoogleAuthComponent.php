<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Security;
use Google_Client;
use Google_Auth_Exception;

class GoogleAuthComponent extends Component
{

    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $hostedDomain;
    private $client;
    private $authType;
    private $createUser;

    public $components = ['Auth'];

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->session = $this->request->session();
        $IdpGoogleTable = TableRegistry::get('SSO.IdpGoogle');
        $googleAttributes = $IdpGoogleTable->getAttributes($this->config('recordKey'));
        $this->clientId = $googleAttributes['client_id'];
        $this->clientSecret = $googleAttributes['client_secret'];
        $this->redirectUri = $googleAttributes['redirect_uri'];
        $this->hostedDomain = $googleAttributes['hd'];
        $this->createUser = $googleAttributes['system_authentication']['allow_create_user'];

        $hashAttributes = $googleAttributes;
        unset($hashAttributes['redirect_uri']);
        $this->authType = Security::hash(serialize($hashAttributes), 'sha256');
        $this->session->write('Google.hostedDomain', $this->hostedDomain);

        $client = new Google_Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setAccessType('offline');
        $client->setHostedDomain($this->hostedDomain);
        $this->client = $client;
        $this->controller = $this->_registry->getController();
        $this->controller->Auth->config('authenticate', [
                'Form' => [
                    'userModel' => $config['userModel'],
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
                'SSO.Google' => [
                    'userModel' => $config['userModel'],
                    'createUser' => $this->createUser
                ]
            ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    public function idpLogin()
    {


        $this->retryMessage = 'Remote authentication failed. <br>Please try local login or <a href="'.$this->redirectUri.'?submit=retry">Click here</a> to try again';
        $client = $this->client;

        /************************************************************************************************
          If we have a code back from the OAuth 2.0 flow, we need to exchange that with the authenticate()
          function. We store the resultant access token bundle in the session, and redirect to ourself.
         ************************************************************************************************/
        if ($this->request->query('code')) {
            try {
                $client->authenticate($this->request->query('code'));
            } catch (Google_Auth_Exception $e) {
                return;
            }
            $this->session->write('Google.accessToken', $client->getAccessToken());
        }

        /************************************************************************************************
          If we have an access token, we can make requests, else we generate an authentication URL.
         ************************************************************************************************/
        if ($this->session->check('Google.accessToken') && $this->session->read('Google.accessToken')) {
            if ($this->Auth->user()) {
                $client->setAccessToken($this->session->read('Google.accessToken'));
            } else {
                // revoke the access token if the user is not authorised
                $client->revokeToken($this->session->read('Google.accessToken'));
                $this->session->delete('Google.accessToken');
                $this->controller->Auth->logout();

                if ($this->session->read('Google.reLogin')) {
                    $authUrl = $client->createAuthUrl();
                    $this->session->write('Google.reLogin', false);
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
                $this->session->write('Google.accessToken', $client->getAccessToken());
                $tokenData = $client->verifyIdToken()->getAttributes();
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
            if (!empty($this->hostedDomain)) {
                if (isset($tokenData['payload']['hd'])) {
                    if ($tokenData['payload']['hd'] == $this->hostedDomain) {
                        $this->session->write('Google.tokenData', $tokenData);
                        $this->session->write('Google.client', $client);
                        $email = $tokenData['payload']['email'];
                        $username = explode('@', $tokenData['payload']['email'])[0];
                        return $this->checkLogin($username);
                    } else {
                        $this->session->write('Google.remoteFail', true);
                    }
                } else {
                    $this->session->write('Google.remoteFail', true);
                }
            } else {
                $this->session->write('Google.tokenData', $tokenData);
                $this->session->write('Google.client', $client);
                $email = $tokenData['payload']['email'];
                $username = explode('@', $tokenData['payload']['email'])[0];
                return $this->checkLogin($username);
            }
        } else {
            $this->session->delete('Google.tokenData');
            $this->session->delete('Google.client', $client);
        }
        return false;
    }

    public function authenticate(Event $event, ArrayObject $extra)
    {
        return $this->idpLogin();
    }

    private function checkLogin($username = null, $extra = [])
    {
        $this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
        $user = $this->controller->Auth->identify();
        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;
        if ($user) {
            if ($user[$this->_config['statusField']] != 1) {
                $extra['status'] = true;
            } else {
                $this->controller->Auth->setUser($user);
                $this->session->delete('Google.remoteFail');
                $extra['loginStatus'] = true;
            }
        } else {
            $extra['loginStatus'] = false;
            if ($this->session->read('Auth.fallback') || $this->session->read('Google.remoteFail')) {
                $extra['fallback'] = true;
            }
        }
        $this->controller->dispatchEvent('Controller.Auth.afterCheckLogin', [$extra], $this);
        return $extra['loginStatus'];
    }
}
