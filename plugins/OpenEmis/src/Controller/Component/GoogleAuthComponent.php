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

	public $components = ['Auth', 'Alert'];

	public function initialize(array $config) {
		$ConfigItems = TableRegistry::get('ConfigItems');
		$this->clientId = $ConfigItems->value('client_id');
		$this->clientSecret = $ConfigItems->value('client_secret');
		$this->redirectUri = $ConfigItems->value('redirect_uri');
		$this->hostedDomain = $ConfigItems->value('hd');
		$session = $this->request->session();
		$session->write('Google.hostedDomain', $this->hostedDomain);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
        // $events['Controller.Auth.beforeAuthenticate'] = 'beforeAuthenticate';
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    public function beforeFilter(Event $event) {
    	$controller = $this->_registry->getController();
    	$controller->Auth->config('authenticate', [
    		'Google' => [
				'userModel' => 'User.Users'
			]
		]);
    }

    public function startup(Event $event) {
    	$action = $this->request->params['action'];
    	if ($action == 'login') {
    		$this->idpLogin();
    	}
    }

	private function idpLogin() {
		$session = $this->request->session();
		$client = new \Google_Client();
    	$client->setClientId($this->clientId);
    	$client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setAccessType('offline');
        $client->setHostedDomain($this->hostedDomain);
        $controller = $this->_registry->getController();

        /************************************************
          If we have a code back from the OAuth 2.0 flow,
          we need to exchange that with the authenticate()
          function. We store the resultant access token
          bundle in the session, and redirect to ourself.
         ************************************************/
        if (($this->request->query('code'))) {
        	try {
        		$client->authenticate($this->request->query('code'));
        	} catch (\Google_Auth_Exception $e) {
        		return $controller->redirect(['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index']);
        	}
            $session->write('Google.accessToken', $client->getAccessToken());
            $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
            $controller->redirect($redirect);
        }

        /************************************************
          If we have an access token, we can make
          requests, else we generate an authentication URL.
         ************************************************/
        if ($session->check('Google.accessToken') && $session->read('Google.accessToken')) {
            if ($this->Auth->user()) {
                pr($session->read('Google.accessToken'));die;
                $client->setAccessToken($session->read('Google.accessToken'));
            } else {
                $client->revokeToken($session->read('Google.accessToken'));
                $session->delete('Google.accessToken');
                $authUrl = $client->createAuthUrl();
            }
        } else {
            $authUrl = $client->createAuthUrl();
        }
		/************************************************
          If we're signed in we can go ahead and retrieve
          the ID token, which is part of the bundle of
          data that is exchange in the authenticate step
          - we only need to do a network call if we have
          to retrieve the Google certificate to verify it,
          and that can be cached.
         ************************************************/
        if ($client->getAccessToken()) {
            $session->write('Google.accessToken', $client->getAccessToken());
			$tokenData = $client->verifyIdToken()->getAttributes();
        }

        if (isset($authUrl)) {
            $controller->redirect($authUrl);
        }

        if (isset($tokenData)) {
            if (isset($tokenData['payload']['hd'])) {
                if ($tokenData['payload']['hd'] == $this->hostedDomain) {
                    $session->write('Google.tokenData', $tokenData);
                    $session->write('Google.client', $client);
                }
            }
        } else {
        	$session->delete('Google.tokenData');
        	$session->delete('Google.client', $client);
        }
	}

    public function authenticate(Event $event, ArrayObject $extra) {
    	$controller = $this->_registry->getController();
    	if ($this->request->is('get')) {
    		$username = 'Not Google Authenticated';
    		$this->idpLogin();
    		$session = $this->request->session();
			if ($session->check('Google.tokenData')) {	
				$tokenData = $session->read('Google.tokenData');
				$email = $tokenData['payload']['email'];
				$username = explode('@', $tokenData['payload']['email'])[0];
	        }
			return $this->checkLogin($username);
		} else {
			return $controller->redirect($controller->Auth->logout());
		}
    }

    private function checkLogin($username) {
    	$controller = $this->_registry->getController();
    	$session = $this->request->session();
		$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
		$user = $this->Auth->identify();
		if ($user) {
			if ($user['status'] != 1) {
				$this->Alert->error('security.login.inactive');
				return $controller->redirect(['action' => 'login']);
			}
			$controller->Auth->setUser($user);
			$labels = TableRegistry::get('Labels');
			$labels->storeLabelsInCache();
			// Support Url
			$ConfigItems = TableRegistry::get('ConfigItems');
			$supportUrl = $ConfigItems->value('support_url');
			$session->write('System.help', $supportUrl);
			// End
			return true;
		} else {
			$controller->Alert->error('security.login.fail');
			return false;
		}
	}
}
