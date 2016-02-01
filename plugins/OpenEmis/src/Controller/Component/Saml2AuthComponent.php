<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\Routing\Router;

require_once( ROOT . DS . 'vendor' . DS . 'php-saml' . DS . '_toolkit_loader.php');

class Saml2AuthComponent extends Component {

	public $components = ['Auth'];

    private $auth;

	public function initialize(array $config) {
        $this->session = $this->request->session();
        $settings = [];
        $returnUrl = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
        $logout = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'logout'],true);

        $AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
        $samlAttributes = $AuthenticationTypeAttributesTable->getTypeAttributeValues('Saml2');

        $setting['sp'] = [
            'entityId' => $samlAttributes['sp_entity_id'],
            'assertionConsumerService' => [
                'url' => $samlAttributes['sp_acs'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['sp_slo'],
            ],
            'NameIDFormat' => $samlAttributes['sp_name_id_format'],
        ];

        $setting['idp'] = [
            'entityId' => $samlAttributes['idp_entity_id'],
            'singleSignOnService' => [
                'url' => $samlAttributes['idp_sso'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['idp_slo'],
            ],
        ];

        $this->addCertFingerPrintInformation('idp', $setting, $samlAttributes);

        $this->userNameField = $samlAttributes['saml_username_mapping'];

        $this->auth = new \OneLogin_Saml2_Auth($setting);
        $this->controller = $this->_registry->getController();
    }

    private function addCertFingerPrintInformation($type, &$setting, $attributes) {
        $arr = [
            'certFingerprint',
            'certFingerprintAlgorithm',
            'x509cert',
            'privateKey'
        ];

        foreach ($arr as $cert) {
            if (!empty($attributes[$type.'_'.$cert])) {
                $setting[$type][$cert] = $attributes[$type.'_'.$cert];
            }
        }
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
    		'Saml2' => [
				'userModel' => 'User.Users'
			]
		]);
    }

    public function startup(Event $event) {
        $action = $this->request->params['action'];
        if ($action == 'login' && !$this->session->read('Auth.fallback') && !$this->session->read('Saml2.remoteFail')) {
            $this->login();
        }
    }

	private function idpLogin() {
		$this->processResponse();
        if ($this->isAuthenticated()) {
            return true;
        } else {
            return false;
        }
	}

    /**
     * Initiates the SSO process.
     *
     * @param string $returnTo   The target URL the user should be returned to after login.
     * @param array  $parameters Extra parameters to be added to the GET
     * @param bool   $forceAuthn When true the AuthNReuqest will set the ForceAuthn='true'
     * @param bool   $isPassive  When true the AuthNReuqest will set the Ispassive='true'
     *  
     */
    public function login($returnTo = null, $parameters = [], $forceAuthn = false, $isPassive = false) {
        $this->auth->login($returnTo, $parameters, $forceAuthn, $isPassive);
    }

    /**
     * Initiates the SLO process.
     *
     * @param string $returnTo      The target URL the user should be returned to after logout.
     * @param array  $parameters    Extra parameters to be added to the GET
     * @param string $nameId        The NameID that will be set in the LogoutRequest.
     * @param string $sessionIndex  The SessionIndex (taken from the SAML Response in the SSO process).
     */
    public function logout($returnTo = null, $parameters = array(), $nameId = null, $sessionIndex = null) {
        $this->auth->logout($returnTo, $parameters, $nameId, $sessionIndex);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * @param string $requestId The ID of the AuthNRequest sent by this SP to the IdP
     */
    public function processResponse($requestId = null) {
        $this->auth->processResponse($requestId);
    }

    /**
     * Returns if there were any error
     *
     * @return array  Errors
     */
    public function getErrors() {
        return $this->auth->getErrors();
    }

    /**
     * Checks if the user is authenticated or not.
     *
     * @return boolean  True if the user is authenticated
     */
    public function isAuthenticated() {
        return $this->auth->isAuthenticated();
    }

    /**
     * Returns the set of SAML attributes.
     *
     * @return array  Attributes of the user.
     */
    public function getAttributes() {
        return $this->auth->getAttributes();
    }

    /**
     * Returns the requested SAML attribute
     *
     * @param string $name The requested attribute of the user.
     *
     * @return NULL || array Requested SAML attribute ($name).
     */
    public function getAttribute($name) {
        return $this->auth->getAttribute($name);
    }

    public function authenticate(Event $event, ArrayObject $extra) {
    	if ($this->request->is('post') && !$this->session->read('Saml2.remoteFail')) {
            if ($this->idpLogin()) {
                $userData = $this->getAttributes();
                if (isset($userData[$this->userNameField][0])) {
                    $userName = $userData[$this->userNameField][0];
                    $this->session->write('Saml2.userAttribute', $userData);
                    return $this->checkLogin($userName);
                } else {
                    $this->session->write('Auth.fallback', true);
                    return false;
                }
            } else {
                $this->controller->Alert->error('security.login.remoteFail', ['reset' => true]);
                return false;
            }
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
            
			return false;
		}

    }

    private function checkLogin($username) {
		$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
		$user = $this->Auth->identify();
		if ($user) {
			if ($user['status'] != 1) {
                $this->session->write('Auth.fallback', true);
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
			// End
			return true;
		} else {
            $this->session->write('Saml2.remoteFail', true);
            $this->controller->Alert->error('security.login.remoteFail', ['reset' => true]);
			return false;
		}
	}


}
