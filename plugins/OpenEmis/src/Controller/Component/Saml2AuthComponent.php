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
        $samlAttributes = $AuthenticationTypeAttributesTable->find('list', [
                'groupField' => 'authentication_type',
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => 'Saml2'])
            ->hydrate(false)
            ->toArray();

        $setting['sp'] = [
            'entityId' => $samlAttributes['Saml2']['sp_entity_id'],
            'assertionConsumerService' => [
                'url' => $samlAttributes['Saml2']['sp_acs'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['Saml2']['sp_slo'],
            ],
            'NameIDFormat' => $samlAttributes['Saml2']['sp_name_id_format'],
        ];

        $setting['idp'] = [
            'entityId' => $samlAttributes['Saml2']['idp_entity_id'],
                'singleSignOnService' => [
                    'url' => $samlAttributes['Saml2']['idp_sso'],
                ],
                'singleLogoutService' => [
                    'url' => $samlAttributes['Saml2']['idp_slo'],
                ],
                'x509cert' =>   $samlAttributes['Saml2']['idp_x509cert'],
        ];

        $this->userNameField = $samlAttributes['Saml2']['saml_username_mapping'];

        $this->auth = new \OneLogin_Saml2_Auth($setting);
        $this->controller = $this->_registry->getController();
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
        $session = $this->request->session();
        if ($action == 'login' && !$session->read('Auth.fallback')) {
            $this->auth->login();
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

    public function processResponse() {
        $this->auth->processResponse();
    }

    public function getErrors() {
        return $this->auth->getErrors();
    }

    public function isAuthenticated() {
        return $this->auth->isAuthenticated();
    }

    public function getAttributes() {
        return $this->auth->getAttributes();
    }

    public function authenticate(Event $event, ArrayObject $extra) {
    	if ($this->request->is('post')) {
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
            if (!$this->session->read('Auth.fallback')) {
                $this->controller->Alert->error($this->retryMessage, ['type' => 'string', 'reset' => true]);
            } else {
                $this->controller->Alert->error('security.login.fail', ['reset' => true]);
            }
            
			return false;
		}
	}


}
