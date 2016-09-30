<?php
namespace User\Controller;
use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use ArrayObject;
use Cake\Routing\Router;

class UsersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->model('User.Users');
        $this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->Auth->allow(['login', 'logout', 'postLogin', 'login_remote', 'patchPasswords']);

        $action = $this->request->params['action'];
        if ($action == 'login_remote') {
            $this->eventManager()->off($this->Csrf);
            $this->Security->config('unlockedActions', ['login_remote']);
        }
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
        $this->viewBuilder()->layout(false);
        $username = '';
        $password = '';
        $session = $this->request->session();

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

    public function postLogin()
    {
        $this->autoRender = false;
        $this->SSO->doAuthentication();
    }

    public function getUniqueOpenemisId($model)
    {
        $this->autoRender = false;
        $openemisId = TableRegistry::get('User.Users')->getUniqueOpenemisId(['model' => $model]);
        $openemis = ['openemis_no' => $openemisId];
        echo json_encode($openemis);
    }

    public function logout()
    {
        $this->request->session()->destroy();
        return $this->redirect($this->Auth->logout());
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Auth.afterIdentify'] = 'afterIdentify';
        $events['Controller.Auth.afterAuthenticate'] = 'afterAuthenticate';
        $events['Controller.Auth.afterCheckLogin'] = 'afterCheckLogin';
        return $events;
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
            return $this->controller->redirect(['plugin' => null, 'controller' => 'Rest', 'action' => 'auth', 'payload' => $this->generateToken(), 'version' => '2.0']);
        } else {
            // Labels
            $labels = TableRegistry::get('Labels');
            $labels->storeLabelsInCache();

            // Support Url
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $supportUrl = $ConfigItems->value('support_url');
            $this->request->session()->write('System.help', $supportUrl);
        }
    }

    public function generateToken() {
        $user = $this->controller->Auth->user();

        // Expiry change to 24 hours
        return JWT::encode([
                    'sub' => $user['id'],
                    'exp' =>  time() + 10800
                ],
                Security::salt());
    }

    public function afterIdentify(Event $event, $user)
    {
        $user = $this->Users->get($user['id']);
        $user->last_login = new DateTime();
        $this->Users->save($user);
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
        } catch(\Exception $ex) {
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
