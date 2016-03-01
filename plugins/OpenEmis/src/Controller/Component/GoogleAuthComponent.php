<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;

require_once(ROOT . DS . 'vendor' . DS  . 'googlephpapi' . DS . 'src' . DS . 'Google' . DS . 'autoload.php');

class GoogleAuthComponent extends Component {

	private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $hostedDomain;
    private $client;

	public $components = ['Auth'];

	public function initialize(array $config) {
		$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');

        $googleAttributes = $AuthenticationTypeAttributesTable->getTypeAttributeValues('Google');
		$this->clientId = $googleAttributes['client_id'];
		$this->clientSecret = $googleAttributes['client_secret'];
		$this->redirectUri = $googleAttributes['redirect_uri'];
		$this->hostedDomain = $googleAttributes['hd'];
        $this->session = $this->request->session();
		$this->session->write('Google.hostedDomain', $this->hostedDomain);

        $client = new \Google_Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setAccessType('offline');
        $client->setHostedDomain($this->hostedDomain);
        $this->client = $client;
        $this->controller = $this->_registry->getController();

        $this->retryMessage = 'Remote authentication failed. <br>Please try local login or <a href="'.$this->redirectUri.'?submit=retry">Click here</a> to try again';
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    public function beforeFilter(Event $event) {
    	$this->controller->Auth->config('authenticate', [
            'Form' => [
                'userModel' => 'User.Users',
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => ['Default', 'Legacy']
                ]
            ],
    		'Google' => [
				'userModel' => 'User.Users'
			]
		]);
    }

    public function startup(Event $event) {
        $client = $this->client;
        $authUrl = $client->createAuthUrl();

        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; 
        } else {
            $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        }
        if (isset(get_headers($authUrl, 1)['Set-Cookie'])) {
            $cookieCounter = count(get_headers($authUrl, 1)['Set-Cookie']);
            if ((empty($this->hostedDomain) && $cookieCounter != 3) || $cookieCounter < 2) {
                $this->session->write('Auth.fallback', true);
                $this->session->delete('Auth.remoteLogin');
            } else {
                $this->session->delete('Auth.fallback');
            }
        }
    	$action = $this->request->params['action'];
    	if ($action == 'login' && !$this->session->read('Google.remoteFail') && !$this->session->read('Auth.fallback')) {
    		$this->idpLogin();
    	} else if ($this->session->read('Google.remoteFail')) {
            $this->controller->Alert->error($this->retryMessage, ['type' => 'string', 'reset' => true]);
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
            $this->session->write('Google.accessToken', $client->getAccessToken());
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
                $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; 
            } else {
                $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
            }
            $this->controller->redirect($redirect);
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
                    } else {
                        $this->session->write('Google.remoteFail', true);
                    }
                } else {
                    $this->session->write('Google.remoteFail', true);
                }
            } else {
                $this->session->write('Google.tokenData', $tokenData);
                $this->session->write('Google.client', $client);
            } 
        } else {
        	$this->session->delete('Google.tokenData');
        	$this->session->delete('Google.client', $client);
        }
	}

    public function authenticate(Event $event, ArrayObject $extra) {
    	if ($this->request->is('get')) {
            if ($this->request->query('submit') == 'retry') {
                $this->session->delete('Google.remoteFail');
                $this->session->write('Google.reLogin', true);
                return $this->controller->redirect($this->redirectUri);
            }
    		$username = 'Not Google Authenticated';
    		$this->idpLogin();
			if ($this->session->check('Google.tokenData')) {	
				$tokenData = $this->session->read('Google.tokenData');
				$email = $tokenData['payload']['email'];
				$username = explode('@', $tokenData['payload']['email'])[0];
	        }
			return $this->checkLogin($username);
		} else {
            if ($this->request->is('post') && isset($this->request->data['submit'])) {
                if ($this->request->data['submit'] == 'login') {
                    $username = $this->request->data('username');
                    $checkLogin = $this->checkLogin($username);
                    if ($checkLogin) {
                        $this->session->write('Auth.fallback', true);
                    }
                    return $checkLogin;
                }
            }
            $this->controller->Alert->error('security.login.remoteFail', ['reset' => true]);
			return false;
		}

    }

    private function checkLogin($username) {
		$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
		$user = $this->Auth->identify();
		if ($user) {
			if ($user['status'] != 1) {
                $this->controller->Alert->error('security.login.inactive', ['reset' => true]);
				return false;
			}
			$this->controller->Auth->setUser($user);
			$labels = TableRegistry::get('Labels');
			$labels->storeLabelsInCache();
			// Support Url
			$ConfigItems = TableRegistry::get('ConfigItems');
			$supportUrl = $ConfigItems->value('support_url');
			$this->session->write('System.help', $supportUrl);
            $this->session->delete('Google.remoteFail');
            $this->controller->Alert->clear();
			// End
			return true;
		} else {
            if (!$this->session->read('Auth.fallback')) {
                $this->controller->Alert->error($this->retryMessage, ['type' => 'string', 'reset' => true]);
            } else {
                $this->controller->Alert->error('security.login.fail', ['reset' => true]);
            }
            
			return false;
		}
	}
}
