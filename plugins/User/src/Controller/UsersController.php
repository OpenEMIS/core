<?php
namespace User\Controller;

use Exception;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use ArrayObject;
use Cake\Routing\Router;
use Firebase\JWT\JWT;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;

class UsersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->model('User.Users');
        $this->loadComponent('Paginator');
        $this->loadComponent('Cookie');
        $this->loadComponent('SSO.SLO');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->Auth->allow(['login', 'logout', 'postLogin', 'login_remote', 'patchPasswords']);

        $action = $this->request->params['action'];
        if ($action == 'login_remote' || ($action == 'login' && $this->request->is('put'))) {
            $this->eventManager()->off($this->Csrf);
            $this->Security->config('unlockedActions', [$action]);
        }
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $localLoginEnabled = $ConfigItems->value('enable_local_login');

        // To show local login
        $this->set('enableLocalLogin', $localLoginEnabled);

        $SystemAuthentications = TableRegistry::get('SSO.SystemAuthentications');
        $authentications = $SystemAuthentications->getActiveAuthentications();

        $authenticationOptions = [];

        foreach ($authentications as $auth) {
            $authenticationOptions[$auth['name']] = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', $auth['authentication_type'], $auth['code']]);
        }
        $authentication = [];
        if ($authenticationOptions) {
            $authentication[] = [
                'text' => __('Select Single Sign On Method'),
                'value' => 0
            ];
            foreach ($authenticationOptions as $key => $value) {
                $authentication[] = [
                    'text' => $key,
                    'value' => $value
                ];
            }
        }

        $this->set('authentications', $authentication);
    }

    public function patchPasswords()
    {
        $this->autoRender = false;
        $script = 'password';

        $consoleDir = ROOT . DS . 'bin' . DS;
        $cmd = sprintf("%scake %s %s", $consoleDir, $script, 'User.Users');
        $nohup = '%s > %slogs/'.$script.'.log & echo $!';
        $shellCmd = sprintf($nohup, $cmd, ROOT.DS);
        \Cake\Log\Log::write('debug', $shellCmd);
        exec($shellCmd);
    }

    public function login()
    {
        if ($this->request->is('put')) {
            $url = $this->request->data('url');
            $sessionId = $this->request->data('session_id');
            $username = $this->request->data('username');
            if (!empty($url) && !empty($sessionId) && !empty($username)) {
                TableRegistry::get('SSO.SingleLogout')->addRecord($url, $username, $sessionId);
            }
        } else {
            $this->viewBuilder()->layout(false);
            $username = '';
            $password = '';
            $session = $this->request->session();

            // SLO Login
            $this->SLO->login();

            if ($this->Auth->user()) {
                return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($session->check('login.username')) {
                $username = $session->read('login.username');
            }
            if ($session->check('login.password')) {
                $password = $session->read('login.password');
            }

            $this->set('username', $username);
            $this->set('password', $password);
        }
    }

    // this function exists so that the browser can auto populate the username and password from the website
    public function login_remote()
    {
        $this->autoRender = false;
        $session = $this->request->session();
        $username = $this->request->data('username');
        $password = $this->request->data('password');
        $session->write('login.username', $username);
        $session->write('login.password', $password);
        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    }

    public function postLogin($authenticationType = 'Local', $code = null)
    {
        if ($this->request->is('post') && $this->request->data('submit') == 'reload') {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        $this->autoRender = false;
        $enableLocalLogin = TableRegistry::get('Configuration.ConfigItems')->value('enable_local_login');
        $authentications = TableRegistry::get('SSO.SystemAuthentications')->getActiveAuthentications();
        if (!$enableLocalLogin && count($authentications) == 1) {
            $authenticationType = $authentications[0]['authentication_type'];
            $code = $authentications[0]['code'];
        } elseif (is_null($code)) {
            $authenticationType = 'Local';
        }
        $this->SSO->doAuthentication($authenticationType, $code);
    }

    public function logout($username = null)
    {
        if ($this->request->is('get')) {
            $username = empty($username) ? $this->Auth->user()['username'] : $username;
            $SecurityUserSessions = TableRegistry::get('SSO.SecurityUserSessions');
            $SecurityUserSessions->deleteEntries($username);
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $Webhooks->triggerShell('logout', ['username' => $username]);
            }
            return $this->redirect($this->Auth->logout());
        } else {
            throw new ForbiddenException();
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Auth.afterIdentify'] = 'afterIdentify';
        $events['Controller.Auth.afterAuthenticate'] = 'afterAuthenticate';
        $events['Controller.Auth.afterCheckLogin'] = 'afterCheckLogin';
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        if (in_array($action, ['login', 'logout', 'postLogin', 'login_remote'])) {
            return true;
        }
    }

    public function afterCheckLogin(Event $event, $extra)
    {
        if (!$extra['loginStatus']) {
            if (!$extra['status']) {
                $this->Alert->error('security.login.inactive', ['reset' => true]);
            } else if ($extra['fallback']) {
                $url = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'submit' => 'retry']);
                $retryMessage = 'Remote authentication failed. <br>Please try local login or <a href="'.$url.'">Click here</a> to try again';
                $this->Alert->error($retryMessage, ['type' => 'string', 'reset' => true]);
            } else {
                $this->Alert->error('security.login.fail', ['reset' => true]);
            }
            $event->stopPropagation();
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
    }

    public function afterAuthenticate(Event $event, ArrayObject $extra)
    {
        if ($this->Cookie->check('Restful.Call')) {
            $event->stopPropagation();
            return $this->redirect(['plugin' => null, 'controller' => 'Rest', 'action' => 'auth', 'payload' => $this->generateToken(), 'version' => '2.0']);
        } else {
            $user = $this->Auth->user();

            if (!empty($user)) {
                $listeners = [
                    $this->Users
                ];
                $this->Users->dispatchEventToModels('Model.Users.afterLogin', [$user], $this, $listeners);

                $SecurityUserSessions = TableRegistry::get('SSO.SecurityUserSessions');

                $SecurityUserSessions->addEntry($user['username'], $this->request->session()->id());

                // Labels
                $labels = TableRegistry::get('Labels');
                $labels->storeLabelsInCache();

                // Support Url
                $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
                $supportUrl = $ConfigItems->value('support_url');
                $this->request->session()->write('System.help', $supportUrl);
            }
        }
    }

    public function generateToken()
    {
        $user = $this->Auth->user();

        // Expiry change to 24 hours
        return JWT::encode([
                    'sub' => $user['id'],
                    'exp' =>  time() + 10800
                ], Configure::read('Application.private.key'), 'RS256');
    }

    public function afterIdentify(Event $event, $user)
    {
        $user = $this->Users->get($user['id']);



        $this->log('[' . $user->username . '] Login successfully.', 'debug');

        // To remove inactive staff security group users records
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $InstitutionStaffTable->removeIndividualStaffSecurityRole($user['id']);
        $this->startInactiveRoleRemoval();
        $this->shellErrorRecovery();
    }

    private function startInactiveRoleRemoval()
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake InactiveRoleRemoval';
        $logs = ROOT . DS . 'logs' . DS . 'RemoveInactiveRoles.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch (Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when removing inactive roles : '. $ex);
        }
    }

    private function shellErrorRecovery()
    {
        $SystemProcesses = TableRegistry::get('SystemProcesses');
        $processes = $SystemProcesses->getErrorProcesses();
        foreach ($processes as $process) {
            $id = $process['id'];
            $model = $process['model'];
            $params = $process['params'];
            $eventName = $process['callable_event'];
            $executedCount = $process['executed_count'];
            $modelTable = TableRegistry::get($model);
            if (!empty($eventName)) {
                $event = $modelTable->dispatchEvent('Shell.'.$eventName, [$id, $executedCount, $params]);
            }
        }
    }
}
