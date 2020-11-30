<?php
namespace User\Controller;

use ArrayObject;
use DateTime;
use Exception;
use InvalidArgumentException;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Network\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\Datasource\ConnectionManager;

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

        $this->Auth->allow(['login', 'logout', 'postLogin', 'login_remote', 'patchPasswords', 'forgotPassword', 'forgotUsername', 'resetPassword', 'postForgotPassword', 'postForgotUsername', 'postResetPassword']);

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

    public function postForgotPassword()
    {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $userIdentifier = $this->request->data('username');

            if (strlen($userIdentifier) === 0) {
                $message = __('This field cannot be left empty');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'forgotPassword']);
            }

            $userEntity = $this->Users
                ->find()
                ->select([
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('email'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name')
                ])
                ->where([
                    'OR' => [
                        [$this->Users->aliasField('username') => $userIdentifier],
                        [$this->Users->aliasField('email') => $userIdentifier]
                    ]
                ])
                ->first();

            if (!is_null($userEntity) && !is_null($userEntity->email)) {
                $userId = $userEntity->id;
                $now = new DateTime();
                $expiry = (new DateTime())->modify('+ 1hour');
                $expiryFormat = $expiry->format('Y-m-d H:i:s');

                // remove any request that is passed expiry date
                $SecurityUserPasswordRequests = TableRegistry::get('User.SecurityUserPasswordRequests');
                $SecurityUserPasswordRequests->deleteAll([
                    $SecurityUserPasswordRequests->aliasField('expiry_date < ') => $now
                ]);

                // check if the user previously requested for reset password that is not expired. If requested before, reject the current request
                $userRequestCount = $SecurityUserPasswordRequests
                    ->find()
                    ->where([$SecurityUserPasswordRequests->aliasField('user_id') => $userId])
                    ->count();

                // user still have active reset request - redirect to login page with info message
                if ($userRequestCount > 0) {
                    $message = __('Please check your email for further instructions.');
                    $this->Alert->info($message, ['type' => 'string', 'reset' => true]);
                    return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                }
                
                try {
                    $checksum = Security::hash($userId . $expiryFormat, 'sha256');
                    $storedChecksum = Security::hash($checksum, 'sha256');

                    $passwordRequestData = [
                        'user_id' => $userId,
                        'expiry_date' => $expiry,
                        'id' => $storedChecksum
                    ];

                    $saveEntity = $SecurityUserPasswordRequests->newEntity($passwordRequestData);
                    $SecurityUserPasswordRequests->save($saveEntity);
                    
                    $userEmail = $userEntity->email;
                    $name = $userEntity->name;
                    $url = Router::url([
                        'plugin' => 'User',
                        'controller' => 'Users',
                        'action' => 'resetPassword',
                        'token' => $checksum
                    ], true);
                    /*
                    Subject: OpenEMIS - Password Reset Request
                    Message Body:
                        Dear <name>,

                        We received a password reset request for your account.
                        If you didn’t request a password reset, kindly ignore this email and your password will not be changed.

                        To reset your password, please click the link below:
                        <url link>

                        Thank you.
                    */
                    $email = new Email('openemis');
                    $emailSubject = __('OpenEMIS - Password Reset Request');
                    $emailMessage = "Dear " . $name . ",\n\nWe received a password reset request for your account.\nIf you didn’t request a password reset, kindly ignore this email and your password will not be changed.\n\nTo reset your password, please click the link below:\n" . $url . "\n\nThank you.";
                    $email
                        ->to($userEmail)
                        ->subject($emailSubject)
                        ->send($emailMessage);
                } catch (InvalidArgumentException $ex) {
                    Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
                    $message = __('An unexpected error has been encountered. Please contact the administrator for assistance.');
                    $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                    return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                }
            }

            $message = __('Please check your email for further instructions.');
            $this->Alert->info($message, ['type' => 'string', 'reset' => true]);
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
    }

    public function postForgotUsername()
    {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $userEmail = $this->request->data('username');
            $emailPattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i';

            // valid email format
            if (preg_match($emailPattern, $userEmail)) {
                $userEntity = $this->Users
                    ->find()
                    ->select([
                        $this->Users->aliasField('id'),
                        $this->Users->aliasField('email'),
                        $this->Users->aliasField('username'),
                        $this->Users->aliasField('first_name'),
                        $this->Users->aliasField('middle_name'),
                        $this->Users->aliasField('third_name'),
                        $this->Users->aliasField('last_name'),
                        $this->Users->aliasField('preferred_name')
                    ])
                    ->where([
                        $this->Users->aliasField('email') => $userEmail
                    ])
                    ->first();

                if (!is_null($userEntity) && !is_null($userEntity->email)) {
                    $userEmail = $userEntity->email;
                    $username = $userEntity->username;
                    $name = $userEntity->name;

                    try {
                        /*
                        Subject: OpenEMIS - Username Recovery Request
                        Message Body: 
                            Dear <name>,

                            We received a username recovery request for your account.
                            Your username is: <username>

                            Thank you.
                         */
                        $email = new Email('openemis');
                        $emailSubject = __('OpenEMIS - Username Recovery Request');
                        
                        $emailMessage = "Dear " . $name . ",\n\nWe received a username recovery request for your account.\nYour username is: " . $username . "\n\nThank you.";
                        $email
                            ->to($userEmail)
                            ->subject($emailSubject)
                            ->send($emailMessage);
                    } catch (InvalidArgumentException $ex) {
                        Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
                        $message = __('An unexpected error has been encountered. Please contact the administrator for assistance.');
                        $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                    }
                }

                $message = __('Please check your email for more information.');
                $this->Alert->info($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            } else {
                $message = __('Please enter a valid email address.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'forgotUsername', 'email' => $userEmail]);
            }
        }
    }

    public function postResetPassword()
    {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $token = $this->request->query('token');
            if (!is_null($token)) {
                $checksum = Security::hash($token, 'sha256');
                $SecurityUserPasswordRequests = TableRegistry::get('User.SecurityUserPasswordRequests');
                $passwordRequestEntity = $SecurityUserPasswordRequests
                    ->find()
                    ->where([$SecurityUserPasswordRequests->aliasField('id') => $checksum])
                    ->first();

                if (!is_null($passwordRequestEntity)) {
                    $userId = $passwordRequestEntity->user_id;

                    $Passwords = TableRegistry::get('User.Passwords');
                    $userEntity = $Passwords
                        ->find()
                        ->where([$Passwords->aliasField('id') => $userId])
                        ->first();

                    $requestData = $this->request->data;
                    $Passwords->patchEntity($userEntity, $requestData);
                    $errors = $userEntity->errors();
                    if (empty($errors)) {
                        if ($Passwords->save($userEntity)) {
                            $SecurityUserPasswordRequests->delete($passwordRequestEntity);
                            $message = __('Your password has been reset successfully.');
                            $this->Alert->success($message, ['type' => 'string', 'reset' => true]);
                            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                        } else {
                            $message = __('An unexpected error has been encountered. Please contact the administrator for assistance.');
                            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                        }
                    } else {
                        $message = '';
                        foreach ($errors as $field => $error) {
                            foreach ($error as $rule => $value) {
                                $message .= '<p>' . __($value) . '</p>';
                            }
                        }
                        $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'resetPassword', 'token' => $token]);
                    }
                } else {
                    $message = __('Sorry, there was an error. Please retry your request.');
                    $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                    return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                }
            } else {
                $message = __('Sorry, there was an error. Please retry your request.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }
        }
    }

    public function resetPassword()
    {
        $this->viewBuilder()->layout(false);
        $token = $this->request->query('token');
        if (!is_null($token)) {
            $checksum = Security::hash($token, 'sha256');
            $SecurityUserPasswordRequests = TableRegistry::get('User.SecurityUserPasswordRequests');
            $passwordRequestEntity = $SecurityUserPasswordRequests
                ->find()
                ->where([$SecurityUserPasswordRequests->aliasField('id') => $checksum])
                ->first();

            if (!is_null($passwordRequestEntity)) {
                $now = new DateTime();
                $expiry = $passwordRequestEntity->expiry_date;

                if ($now <= $expiry) {
                    $this->set('token', $token);
                } else {
                    $SecurityUserPasswordRequests->delete($passwordRequestEntity);
                    $message = __('Sorry, there was an error. Please retry your request.');
                    $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                    return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                }
            } else {
                $message = __('Sorry, there was an error. Please retry your request.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }
        } else {
            $message = __('Sorry, there was an error. Please retry your request.');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
    }

    public function forgotPassword()
    {
        $this->viewBuilder()->layout(false);
    }

    public function forgotUsername()
    {
        $this->viewBuilder()->layout(false);
        $userEmail = $this->request->query('email');
        
        if (isset($userEmail)) {
            $this->set('username', $userEmail);
        } else {
            $this->set('username', '');
        }
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
