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
    private $spBaseUrl = 'http://localhost:8080';
    // private $returnUrl = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);

	public function initialize(array $config) {
        $this->session = $this->request->session();
        $settings = [];
        $returnUrl = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
        $logout = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'logout'],true);

        $settings['sp'] = [
            'entityId' => $this->spBaseUrl.'/openemis-phpoe',
            'assertionConsumerService' => [
                'url' => $returnUrl,
            ],
            'singleLogoutService' => [
                'url' => $logout,
            ],
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:emailAddress',
        ];

        $settings['idp'] = [
            'entityId' => 'https://app.onelogin.com/saml/metadata/513327',
                'singleSignOnService' => [
                    'url' => 'https://app.onelogin.com/trust/saml2/http-post/sso/513327',
                ],
                'singleLogoutService' => [
                    'url' => 'https://app.onelogin.com/trust/saml2/http-redirect/slo/513327',
                ],
                'x509cert' =>   'MIIELDCCAxSgAwIBAgIUZFHHsPaL+Z7p7BKAa48gqrLjmPYwDQYJKoZIhvcNAQEF
                                BQAwXzELMAkGA1UEBhMCVVMxGDAWBgNVBAoMD0tvcmQgSVQgUHRlIEx0ZDEVMBMG
                                A1UECwwMT25lTG9naW4gSWRQMR8wHQYDVQQDDBZPbmVMb2dpbiBBY2NvdW50IDc3
                                MDk3MB4XDTE2MDEyODA5MjEzMloXDTIxMDEyOTA5MjEzMlowXzELMAkGA1UEBhMC
                                VVMxGDAWBgNVBAoMD0tvcmQgSVQgUHRlIEx0ZDEVMBMGA1UECwwMT25lTG9naW4g
                                SWRQMR8wHQYDVQQDDBZPbmVMb2dpbiBBY2NvdW50IDc3MDk3MIIBIjANBgkqhkiG
                                9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyMi+YL4cNVzrEI93vN5ZDV/ruHJN5rNHIq0d
                                HAe48QbP81quask9da3gWZtqVTKeVlXHnOBx0kwoJpE66+Xo/dMa2nrgaf1c0rqA
                                1JtwvG6CiX8TsA/W/6oTucnK2NvG7ZJBN664YbfPcWEtsv9Zp68m23kHQO6DV1HJ
                                ZW6u53nxaDDo3uBrBJBZWDpwM273E2GpXrEQNHiJ7DrSdof3SI7nMPCYqFjEKpec
                                IYUSPRUedOG1medxi4WS48vJHXRv38Vgw20mE9CH56EsROXmSwyZhh7x+BknA1NF
                                Bnt1/k6bsDoVYbm0Q+MnqJby9YCGXjbHpoF3+hhTSwB699ml8wIDAQABo4HfMIHc
                                MAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFK7EN5it2oKKGpMyXv70AI08ueixMIGc
                                BgNVHSMEgZQwgZGAFK7EN5it2oKKGpMyXv70AI08ueixoWOkYTBfMQswCQYDVQQG
                                EwJVUzEYMBYGA1UECgwPS29yZCBJVCBQdGUgTHRkMRUwEwYDVQQLDAxPbmVMb2dp
                                biBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgNzcwOTeCFGRRx7D2i/me
                                6ewSgGuPIKqy45j2MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA
                                YAcnP4wsR7Ns28ZDKsP/I5byWeIWy5lFRcg4Jkk7MSBMoThNM6QaTg5m6Tb98LLT
                                FFGU8RlWQ7GnYukT0pvCwjM+lfj4pn3ebR5MAo1hL/mnLYAo3WVVYivmZZssztgr
                                16+whEFQjOEHcWL0IU+Qb1ONINFtfBWPbMrfzNGAImXaeU9Kn5GqGma3NGlbYCpQ
                                VcH1yt5CH6AvtK6POAGe4tLCgDAvL4NyVxXegmH5eaCCBE8Ku/VRJr6QxxfrGZOt
                                UAKibTQBd+KJUM2RMgMCYBs+fRdm/bH1cKVJKy3Oxo3HmleD2l2NZLk04nLKPl2u
                                FTCQmT1aJppEydYQArg+Mg==',
        ];

        $this->auth = new \OneLogin_Saml2_Auth($settings);
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
        $url = Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
        $params = [
            'ReturnTo' => $url,
        ];
        $action = $this->request->params['action'];
        if ($action == 'login') {
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

    private function processResponse() {
        $this->auth->processResponse();
    }

    private function getErrors() {
        return $this->auth->getErrors();
    }

    private function isAuthenticated() {
        return $this->auth->isAuthenticated();
    }

    private function getAttributes() {
        return $this->auth->getAttributes();
    }

    public function authenticate(Event $event, ArrayObject $extra) {
    	if ($this->request->is('post')) {
            if ($this->idpLogin()) {
                $userData = $this->getAttributes();         
                $emailArray = explode('@', $userData['User.email'][0]);
                $userName = $emailArray[0];
                $this->session->write('Saml2.userAttribute', $this->getAttributes());
                return $this->checkLogin($userName);
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
