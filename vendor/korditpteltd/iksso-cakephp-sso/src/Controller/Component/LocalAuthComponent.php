<?php
namespace SSO\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Http\ServerRequest;

class LocalAuthComponent extends Component {
    public $components = ['Auth', 'Alert'];

    protected $_defaultConfig = [
        'homePageURL' => null,
        'loginPageURL' => null,
    ];

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        //$events['Controller.Auth.beforeAuthenticate'] = 'beforeAuthenticate';
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    public function beforeFilter(Event $event) {
        $controller = $this->_registry->getController();
        $controller->Auth->setConfig('authenticate', [
            'Form' => [
                'userModel' => $this->_config['userModel'],
                'passwordHasher' => [
                    'className' => 'Fallback',
                    'hashers' => ['Default', 'Legacy']
                ]
            ]
        ]);
    }

    public function authenticate(Event $event, ArrayObject $extra) {
        $controller = $this->_registry->getController();
        $request = $controller->getRequest();
        
        if ($request->is('post')) {
            if ($request->getData('submit') == 'login') {
                $username = $request->getData('username');
                return $this->checkLogin($username);
            } else if ($request->getData('submit') == 'reload') {
                $username = $request->getData['username'];
                $password = $request->getData['password'];
                $session = $this->request->session();
                $session->write('login.username', $username);
                $session->write('login.password', $password);
                return $controller->redirect($this->loginPageURL);
            }
        } else {
            return $controller->redirect($this->homePageURL);
            // return false;
        }
    }

    private function checkLogin($username = null, $extra = [])
    {

        $controller = $this->_registry->getController();
        $request = $controller->getRequest();
        $session = $this->getController()->getRequest()->getSession();
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
        }
        
        $user = $this->Auth->identify();

        $extra['status'] = true;
        $extra['loginStatus'] = false;
        $extra['fallback'] = false;
        if ($user) {
            if ($user[$this->_config['statusField']] != 1) {
                $extra['status'] = false;
            } else {
                $this->Auth->setUser($user);
                if ($this->Auth->authenticationProvider()->needsPasswordRehash()) {
                    $this->Users = TableRegistry::getTableLocator()->get($this->_config['userModel']);
                    $user = $this->Users->get($this->Auth->user('id'));
                    $user->password = $request->getData['password'];
                    $this->Users->save($user);
                }
                $extra['loginStatus'] = true;
            }

        }
        $controller->dispatchEvent('Controller.Auth.afterCheckLogin', [$extra], $this);
        return $extra['loginStatus'];

    }
}
