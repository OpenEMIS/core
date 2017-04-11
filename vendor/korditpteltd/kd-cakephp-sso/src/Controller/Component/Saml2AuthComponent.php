<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Security;

require_once( ROOT . DS . 'vendor' . DS . 'onelogin' . DS . 'php-saml' . DS . '_toolkit_loader.php');

class Saml2AuthComponent extends Component {

    public $components = ['Auth'];

    private $saml;
    private $clientId;
    private $authType;
    private $createUser;

    public function initialize(array $config) {
        $this->session = $this->request->session();
        $settings = [];
        $returnUrl = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
        $logout = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'logout'],true);

        $AuthenticationTypeAttributesTable = TableRegistry::get('SSO.AuthenticationTypeAttributes');
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
                'binding' => $samlAttributes['idp_sso_binding']
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['idp_slo'],
                'binding' => $samlAttributes['idp_slo_binding']
            ],
        ];

        $this->authType = Security::hash(serialize($setting['idp']), 'sha256');

        $this->addCertFingerPrintInformation('idp', $setting, $samlAttributes);

        $this->clientId = $samlAttributes['idp_entity_id'];

        $this->userNameField = $samlAttributes['saml_username_mapping'];

        $this->createUser = isset($samlAttributes['allow_create_user']) ?  $samlAttributes['allow_create_user'] : 0;

        $this->saml = new \OneLogin_Saml2_Auth($setting);
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
        if (!$this->session->read('Auth.fallback')) {
            $this->controller->Auth->config('authenticate', [
                'Form' => [
                    'userModel' => $this->_config['userModel'],
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
                'SSO.Saml2' => [
                    'userModel' => $this->_config['userModel'],
                    'createUser' => $this->createUser
                ]
            ]);
        }
    }

    public function startup(Event $event) {
        if (!$this->controller->Auth->user()) {
            $action = $this->request->params['action'];
            if ($action == $this->config('loginAction') && !$this->session->read('Auth.fallback') && !$this->session->read('Saml2.remoteFail')) {
                $this->login();
            }
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
        $this->saml->login($returnTo, $parameters, $forceAuthn, $isPassive);
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
        $this->saml->logout($returnTo, $parameters, $nameId, $sessionIndex);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * @param string $requestId The ID of the AuthNRequest sent by this SP to the IdP
     */
    public function processResponse($requestId = null) {
        $this->saml->processResponse($requestId);
    }

    /**
     * Returns if there were any error
     *
     * @return array  Errors
     */
    public function getErrors() {
        return $this->saml->getErrors();
    }

    /**
     * Checks if the user is authenticated or not.
     *
     * @return boolean  True if the user is authenticated
     */
    public function isAuthenticated() {
        return $this->saml->isAuthenticated();
    }

    /**
     * Returns the set of SAML attributes.
     *
     * @return array  Attributes of the user.
     */
    public function getAttributes() {
        return $this->saml->getAttributes();
    }

    /**
     * Returns the requested SAML attribute
     *
     * @param string $name The requested attribute of the user.
     *
     * @return NULL || array Requested SAML attribute ($name).
     */
    public function getAttribute($name) {
        return $this->saml->getAttribute($name);
    }

    public function authenticate(Event $event, ArrayObject $extra) {
        $extra['authType'] = $this->authType;
        if ($this->controller->Auth->user()) {
            return true;
        }
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
                $this->session->write('Auth.fallback', true);
                return false;
            }
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
            }
            return false;
        }

    }

    private function checkLogin($username = null, $extra = [])
    {
        $this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
        $user = $this->Auth->identify();
        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;
        if ($user) {
            if ($user[$this->_config['statusField']] != 1) {
                $this->session->write('Auth.fallback', true);
                $extra['status'] = false;
            } else {
                $this->controller->Auth->setUser($user);
                $extra['loginStatus'] = true;
            }
        } else {
            $this->session->write('Saml2.remoteFail', true);
        }

        if ($this->session->read('Auth.fallback')) {
            $extra['fallback'] = true;
        }

        $this->controller->dispatchEvent('Controller.Auth.afterCheckLogin', [$extra], $this);
        return $extra['loginStatus'];
    }


}
