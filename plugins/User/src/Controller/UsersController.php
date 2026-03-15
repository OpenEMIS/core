<?php

namespace User\Controller;

use ArrayObject;
use DateTime;
use Exception;
use InvalidArgumentException;
use Cake\Core\Configure;
//use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Mailer\Email;
//use Cake\Network\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventManager;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenTime;
use Firebase\JWT\Key;

class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->ControllerAction->model('User.Users');
        $this->loadComponent('Paginator');
        $this->loadComponent('Cookie');
        $this->loadComponent('SSO.SLO');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Auth->allow(['login', 'logout', 'postLogin', 'login_remote', 'patchPasswords', 'forgotPassword', 'forgotUsername', 'resetPassword', 'postForgotPassword', 'postForgotUsername', 'postResetPassword', 'twoFactorAuthentication', 'sendOtp', 'verifyOtp', 'verifyOtpView']);
        $request = $this->request;
        $action = $this->request->getParam('action');

        if ($action == 'login_remote' || ($action == 'login' && $this->request->is('put'))) {
            $this->getEventManager()->off($this->Csrf);
            $this->Security->setConfig('unlockedActions', [$action]);
        }
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $localLoginEnabled = $ConfigItems->value('enable_local_login');

        // To show local login
        $this->set('enableLocalLogin', $localLoginEnabled);

        $SystemAuthentications = TableRegistry::getTableLocator()->get('SSO.SystemAuthentications');
        $authentications = $SystemAuthentications->getActiveAuthentications();

        $authenticationOptions = [];

        foreach ($authentications as $auth) {
            $authenticationOptions[$auth['name']] = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', $auth['authentication_type'], $auth['code']]);
        }
        // echo "<pre>";print_r($authentications);die;
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
        $nohup = '%s > %slogs/' . $script . '.log & echo $!';
        $shellCmd = sprintf($nohup, $cmd, ROOT . DS);
        \Cake\Log\Log::write('debug', $shellCmd);
        exec($shellCmd);
    }

    public function login()
    {
        if ($this->request->is('put')) {
            $url = $this->request->getData('url');
            $sessionId = $this->request->getData('session_id');
            $username = $this->request->getData('username');
            if (!empty($url) && !empty($sessionId) && !empty($username)) {
                TableRegistry::getTableLocator()->get('SSO.SingleLogout')->addRecord($url, $username, $sessionId);
            }
        } else {
            //$this->viewBuilder()->layout(false);
            //$this->viewBuilder()->setLayout(false);
            $this->viewBuilder()->disableAutoLayout();
            $username = '';
            $password = '';
            $session = $this->getRequest()->getSession();
            // SLO Login
            $this->SLO->login();
            if ($this->Auth->user()) {
                //POCOR-7485 Start
                $rootPath = $_SERVER['REQUEST_URI'];
                $cookie = new \Cake\Http\Cookie\Cookie(
                            'my_base_url',
                            $rootPath
                        );
                $this->response = $this->response->withCookie($cookie);
                //POCOR-7485 End
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
        $session = $this->request->getSession();
        $username = $this->request->getData('username');
        $password = $this->request->getData('password');
        $session->write('login.username', $username);
        $session->write('login.password', $password);
        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    }

    public function postForgotPassword()
    {

        $this->autoRender = false;
        if ($this->request->is('post')) {
            $userIdentifier = $this->request->getData()['username'];

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
                        [$this->Users->aliasField('username') => trim($userIdentifier)],
                        [$this->Users->aliasField('email') => trim($userIdentifier)]
                    ]
                ])
                ->first();

            if (!is_null($userEntity) && !is_null($userEntity->email)) {
                //Log::write('debug', "1");
                $userId = $userEntity->id;
                $now = new DateTime();
                $expiry = (new DateTime())->modify('+ 1hour');
                $expiryFormat = $expiry->format('Y-m-d H:i:s');
                //Log::write('debug', "2");

                // remove any request that is passed expiry date
                $SecurityUserPasswordRequests = TableRegistry::getTableLocator()->get('User.SecurityUserPasswordRequests');
                $SecurityUserPasswordRequests->deleteAll([
                    $SecurityUserPasswordRequests->aliasField('expiry_date < ') => $now
                ]);
                //Log::write('debug', "3");

                // check if the user previously requested for reset password that is not expired. If requested before, reject the current request
                $userRequestCount = $SecurityUserPasswordRequests
                    ->find()
                    ->where([$SecurityUserPasswordRequests->aliasField('user_id') => $userId])
                    ->count();
                    //Log::write('debug', "4");

                // user still have active reset request - redirect to login page with info message
                if ($userRequestCount > 0) {
                    $message = __('Please check your email for further instructions.');
                    $this->Alert->info($message, ['type' => 'string', 'reset' => true]);
                    return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                }
                //Log::write('debug', "5");
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
                        '?' => ['token' => $checksum]
                    ], true);

                    /*POCOR-5284 Starts*/
                    $Themes = TableRegistry::getTableLocator()->get('Theme.Themes');
                    $getData = $Themes->find()
                                ->where([$Themes->aliasField('name') => 'Application Name'])
                                ->first();
                    if (!empty($getData) && !is_null($getData->value) && !empty($getData->value)) {
                        $emailSubject = $getData->value;
                    } else {
                        $emailSubject = $getData->default_value;
                    }

                $passwordRequestData = [
                    'user_id' => $userId,
                    'expiry_date' => $expiry,
                    'id' => $storedChecksum
                ];
                //Log::write('debug', "6");
                $saveEntity = $SecurityUserPasswordRequests->newEntity($passwordRequestData);
                $SecurityUserPasswordRequests->save($saveEntity);

                $userEmail = $userEntity->email;
                $name = $userEntity->name;
                $url = Router::url([
                    'plugin' => 'User',
                    'controller' => 'Users',
                    'action' => 'resetPassword',
                    '?' => ['token' => $checksum]
                ], true);

                /*POCOR-5284 Starts*/
                $Themes = TableRegistry::get('Theme.Themes');
                $getData = $Themes->find()
                    ->where([$Themes->aliasField('name') => 'Application Name'])
                    ->first();
                if (!empty($getData) && !is_null($getData->value) && !empty($getData->value)) {
                    $emailSubject = $getData->value;
                } else {
                    $emailSubject = $getData->default_value;
                }

                try {
                    $email = new Email('openemis');
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 1: ' . $exception->getMessage() . ": $userEmail");
                    }
                    try {
                        $emailSubject = $emailSubject . ' - Password Reset Request';
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 2: ' . $exception->getMessage() . ": $userEmail");
                    }
                    //$emailSubject = __('OpenEMIS - Password Reset Request');
                    try {
                        $emailMessage = "Dear " . $name . ",
                        \n\nWe received a password reset request for your account.
                        \n\nIf you didn’t request a password reset, kindly ignore
                        this email and your password will not be changed.
                        \n\nTo reset your password, please click the link below:
                        \n" . $url . "\n\nThank you.";
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 3: ' . $exception->getMessage() . ": $userEmail");
                    }
                    try {
                        $e = $email
                            ->setTo($userEmail)
                            ->setSubject($emailSubject)
                            ->send($emailMessage);

                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 4: ' . $exception->getMessage() . ": $userEmail");
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
            $userEmail = $this->request->getData('username');
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
                        $this->Users->aliasField('preferred_name'),
                        $this->Users->aliasField('password'),
                    ])
                    ->where([
                        $this->Users->aliasField('email') => $userEmail
                    ])
                    ->first();
                $userId = $userEntity->id;
                if (!is_null($userEntity) && !is_null($userEntity->email)) {
                    $userEmail = $userEntity->email;
                    $username = $userEntity->username;
                    $name = $userEntity->name;

                    try {
                        $updateUserName = $this->updateUserName($username, $userId); //POCOR-7159
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 1: ' . $exception->getMessage() . ": $userEmail");
                    }
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
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 1: ' . $exception->getMessage() . ": $userEmail");
                    }
                    try {
                        $emailSubject = __('OpenEMIS - Username Recovery Request');
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 2: ' . $exception->getMessage() . ": $userEmail");
                    }
                    try {
                        $emailMessage = "Dear " . $name . ",\n\nWe received a username recovery request for your account.\n\nYour username is: " . $username . "\n\nThank you."; //POCOR-8198 space added in second line
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 3: ' . $exception->getMessage() . ": $userEmail");
                    }
                    try {
                        $email
                            ->setTo($userEmail)
                            ->setSubject($emailSubject)
                            ->send($emailMessage);
                    } catch (\Exception $exception) {
                        Log::write('error', __METHOD__ . ' 4: ' . $exception->getMessage() . ": $userEmail");
                    }

//                    catch (InvalidArgumentException $ex) {
//                        Log::write('error', __METHOD__ . ': ' . $ex->getMessage());
//                        $message = __('An unexpected error has been encountered. Please contact the administrator for assistance.');
//                        $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
//                        return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
//                    }

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
            $token = $this->request->getQuery('token');
            if (!is_null($token)) {
                $checksum = Security::hash($token, 'sha256');
                $SecurityUserPasswordRequests = TableRegistry::getTableLocator()->get('User.SecurityUserPasswordRequests');
                $passwordRequestEntity = $SecurityUserPasswordRequests
                    ->find()
                    ->where([$SecurityUserPasswordRequests->aliasField('id') => $checksum])
                    ->first();

                if (!is_null($passwordRequestEntity)) {
                    $userId = $passwordRequestEntity->user_id;

                    $Passwords = TableRegistry::getTableLocator()->get('User.Passwords');
                    $userEntity = $Passwords
                        ->find()
                        ->where([$Passwords->aliasField('id') => $userId])
                        ->first();

                    $requestData = $this->request->getData();
                    $Passwords->patchEntity($userEntity, $requestData);
                    $errors = $userEntity->getErrors();
                    if (empty($errors)) {
                        if ($Passwords->save($userEntity)) {
                            $setdata = $this->updateUserPassword($userId); //POCOR-7159
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
                        //POCOR-8609
                        $this->set('token', $token);
                        $this->viewBuilder()->disableAutoLayout();
                        $this->render('reset_password');
                        //return $this->redirect($url);
                        // return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'resetPassword', '?' =>['token' => $token]]);
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
        // $this->viewBuilder()->layout(false);
        $this->viewBuilder()->disableAutoLayout();
        $token = $this->request->getQuery('token');
        if (!is_null($token)) {
            $checksum = Security::hash($token, 'sha256');
            $SecurityUserPasswordRequests = TableRegistry::getTableLocator()->get('User.SecurityUserPasswordRequests');
            $passwordRequestEntity = $SecurityUserPasswordRequests
                ->find()
                ->where([$SecurityUserPasswordRequests->aliasField('id') => $checksum])
                ->first();

            if (!is_null($passwordRequestEntity)) {
                $now = new DateTime();
                $expiry = $passwordRequestEntity->expiry_date;
                //POCOR-8609 Becuase did not get same timezone so we convert timezone for comparsion
                $now->setTimezone(new \DateTimeZone('UTC'));
                $expiry = $expiry->setTimezone(new \DateTimeZone('UTC'));;

                if ($now <= $expiry) {
                    $this->set('token', $token);
                    //POCOR-8609
                    $this->viewBuilder()->disableAutoLayout();
                    $this->render('reset_password');
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
        // $this->viewBuilder()->layout(false);
        $this->viewBuilder()->disableAutoLayout();
    }

    public function forgotUsername()
    {
        // $this->viewBuilder()->layout(false);
        $this->viewBuilder()->disableAutoLayout();
        $userEmail = $this->request->getQuery('email');

        if (isset($userEmail)) {
            $this->set('username', $userEmail);
        } else {
            $this->set('username', '');
        }
    }

    // public function postLogin($authenticationType = 'Local', $code = null)
    // {
    //     $request = new ServerRequest();
    //     if (($_SERVER['REQUEST_METHOD']=='POST' && $this->getRequest()->getData('submit') == 'reload')) {
    //         return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    //     }

    //     //POCOR-7156 starts
    //     $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
    //     $ConfigItemsEntity = $ConfigItems
    //         ->find()
    //         ->where([$ConfigItems->aliasField('code') => 'two_factor_authentication'])
    //         ->first();
    //     if ($_SERVER['REQUEST_METHOD']=='POST' && $this->getRequest()->getData('submit') == 'login' && $ConfigItemsEntity->value == 1) {
    //         if($this->getRequest()->getData('username') == '' || $this->getRequest()->getData('password') == ''){
    //             $this->Alert->error('security.login.fail', ['reset' => true]);
    //             return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    //         }
    //         $userEntity = $this->Users
    //             ->find()
    //             ->select([
    //                 $this->Users->aliasField('id'),
    //                 $this->Users->aliasField('username'),
    //                 $this->Users->aliasField('email'),
    //                 $this->Users->aliasField('first_name'),
    //                 $this->Users->aliasField('middle_name'),
    //                 $this->Users->aliasField('third_name'),
    //                 $this->Users->aliasField('last_name'),
    //                 $this->Users->aliasField('preferred_name')
    //             ])->where([
    //                 $this->Users->aliasField('username') => $this->getRequest()->getData('username')
    //             ])->first();
    //         if ($userEntity->email == "") {
    //             $message = __('An email address is not registered for this account. Please contact your system administrator.');

    //             //$this->Alert->error($message, ['type' => 'string', 'reset' => true]);
    //             return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
    //         }
    //     }//POCOR-7156 ends
    //     $this->autoRender = false;
    //     $enableLocalLogin = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('enable_local_login');
    //     $authentications = TableRegistry::getTableLocator()->get('SSO.SystemAuthentications')->getActiveAuthentications();
    //     if (!$enableLocalLogin && count($authentications) == 1) {
    //         $authenticationType = $authentications[0]['authentication_type'];
    //         $code = $authentications[0]['code'];
    //     } elseif (is_null($code)) {
    //         $authenticationType = 'Local';
    //     }
    //     //POCOR-7156 starts
    //     //print_r($this->getRequest()->getData('submit'));die;
    //     if($_SERVER['REQUEST_METHOD']=='POST' && $this->getRequest()->getData('submit') == 'login' && $ConfigItemsEntity->value == 1){
    //         $six_digit_random_number = random_int(100000, 999999);
    //         $encrypt_otp = base64_encode($six_digit_random_number);
    //         $SystemUserOtpTbl = TableRegistry::getTableLocator()->get('User.SecurityUserCodes');
    //         $SystemUserOtpEntity = $SystemUserOtpTbl
    //             ->find()
    //             ->where([$SystemUserOtpTbl->aliasField('security_user_id') => $userEntity->id])
    //             ->first();
    //         $now = new DateTime();
    //         $create_date = $now->format('Y-m-d H:i:s');
    //         if (!empty($SystemUserOtpEntity)) {
    //             $SystemUserOtpTbl->updateAll(
    //                 ['verification_otp' => $encrypt_otp, 'created' => $create_date],
    //                 ['id' => $SystemUserOtpEntity->id]
    //             );
    //         } else {
    //             $data = [
    //                 'security_user_id' => $userEntity->id,
    //                 'verification_otp' => $encrypt_otp,
    //                 'created' => $create_date
    //             ];
    //             $newEntity = $SystemUserOtpTbl->newEntity($data);
    //             $SystemUserOtpTbl->save($newEntity);
    //         }
    //         $userEmail = $userEntity->email;
    //         $name = $userEntity->name;
    //         $email = new Email('openemis');
    //         $emailSubject = __('OpenEMIS - One-time Password (OTP)');
    //         $emailMessage = "Dear " . $name . ",\n\nOne-time Password (OTP) is " . $six_digit_random_number . " . This OTP expires in 1 hour. \n\nBest regards,\nOpenEMIS Support\n\nThis is a system - generated email. Please do not reply to this email address.";
    //         $email
    //             ->setTo($userEmail)
    //             ->setSubject($emailSubject)
    //             ->send($emailMessage);
    //         $message = __('A verification code has been sent to your registered email address.');
    //         $this->Alert->success($message, ['type' => 'string', 'reset' => true]);
    //         $userName = $this->encrypt($userEntity->username, Security::getSalt());
    //         $userEmail = $this->encrypt($userEntity->email, Security::getSalt());
    //         $userPass = $this->encrypt($this->request->getData('password'), Security::getSalt());
    //         $encodedUserData = $this->paramsEncode(['username' => $userName, 'email'=>$userEmail, 'password' => $userPass]);
    //         return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'verifyOtp', $encodedUserData]);
    //     } else {//POCOR-7156 ends
    //         $this->SSO->doAuthentication($authenticationType, $code);
    //     }
    // }


    public function postLogin($authenticationType = 'Local', $code = null)
    {
        set_time_limit(3000);
        if ($this->request->is('post') && $this->request->getData('submit') == 'reload') {
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
        //POCOR-7156 starts
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $ConfigItemsEntity = $ConfigItems
            ->find()
            ->where([$ConfigItems->aliasField('code') => 'two_factor_authentication'])
            ->first();
        //POCOR-2976 start
        $LoginAttemptsEntity = $ConfigItems
            ->find()
            ->where([$ConfigItems->aliasField('code') => 'login_attempts'])
            ->first();
        $session = $this->request->getSession();
        $loginAttempts = isset($LoginAttemptsEntity->value) ? $LoginAttemptsEntity->value : $LoginAttemptsEntity->default_value;
        if (!($session->check('login.attempts'))) {
            $session->write('login.attempts', $loginAttempts);
        }
        //POCOR-2976 end
        //POCOR-8127 starts write session for API use
        //$session->write('auth_username', $this->request->getData('username'));
        //$session->write('auth_password', base64_encode($this->request->getData('password')));
        //POCOR-8127 ends
        if ($this->request->is('post') && $this->request->getData('submit') == 'login' && $ConfigItemsEntity->value == 1) {
            if ($this->request->getData()['username'] == '' || $this->request->getData()['password'] == '') {
                $this->Alert->error('security.login.fail', ['reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }

            $username = $this->request->getData()['username'];
            $SecurityUser = TableRegistry::getTableLocator()->get('User.Users');

            $userEntity = $SecurityUser->find()
                ->select([
                    'id', 'username', 'email', 'first_name', 'middle_name',
                    'third_name', 'last_name', 'preferred_name', 'status'
                ])
                ->where(['username' => $username])
                ->first();
            //POCOR-8680 Start
            if (!$userEntity) {
                $this->Alert->error('Account does not exist', ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }

            if ($userEntity->status == 0) {
                $this->Alert->error('security.login.locked_account', ['reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }

            // Attempt to identify user
            $user = $this->Auth->identify();
            $loginStatus = false;

            if ($user) {
                $this->Auth->setUser($user);
                $loginStatus = true;
            }

            // Handle login attempts
            $session = $this->request->getSession();
            $noOfPendingAttempts = $session->read('login.attempts') - 1;
            $session->write('login.attempts', $noOfPendingAttempts);
            if (!$loginStatus) {
                if ($noOfPendingAttempts <= 0) {
                    // Lock account after failed attempts
                    $SecurityUser->updateAll(['status' => 0], ['username' => $username]);
                    $this->Alert->error('security.login.locked_account', ['reset' => true]);
                } else {
                    $message = __("You have {$noOfPendingAttempts} more login attempts before your account will be locked.");
                    $this->Alert->warning($message, ['type' => 'string', 'reset' => true]);
                }
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }
            //POCOR-8680 End
            // Check if email is set
            if (empty($userEntity->email)) {
                $message = __('An email address is not registered for this account. Please contact your system administrator.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }
        }//POCOR-7156 ends
        $this->autoRender = false;
        $enableLocalLogin = TableRegistry::get('Configuration.ConfigItems')->value('enable_local_login');
        $authentications = TableRegistry::get('SSO.SystemAuthentications')->getActiveAuthentications();
        if (!$enableLocalLogin && count($authentications) == 1) {
            $authenticationType = $authentications[0]['authentication_type'];
            $code = $authentications[0]['code'];
        } elseif (is_null($code)) {
            $authenticationType = 'Local';
        }
        //POCOR-7156 starts
        if ($this->request->is('post') && $this->request->getData('submit') == 'login' && $ConfigItemsEntity->value == 1) {
            $six_digit_random_number = random_int(100000, 999999);
            $encrypt_otp = base64_encode($six_digit_random_number);
            $SystemUserOtpTbl =  TableRegistry::getTableLocator()->get('User.SecurityUserCodes');
            $SystemUserOtpEntity = $SystemUserOtpTbl
                ->find()
                ->where([$SystemUserOtpTbl->aliasField('security_user_id') => $userEntity->id])
                ->first();
            $now = new DateTime();
            $create_date = $now->format('Y-m-d H:i:s');
            if (!empty($SystemUserOtpEntity)) {
                $SystemUserOtpTbl->updateAll(
                    ['verification_otp' => $encrypt_otp, 'created' => $create_date],
                    ['id' => $SystemUserOtpEntity->id]
                );
            } else {
                $data = [
                    'security_user_id' => $userEntity->id,
                    'verification_otp' => $encrypt_otp,
                    'created' => $create_date
                ];
                $newEntity = $SystemUserOtpTbl->newEntity($data);
                $SystemUserOtpTbl->save($newEntity);
            }
            $userEmail = $userEntity->email;
            $name = $userEntity->name;
            $email = new Email('openemis');
            $emailSubject = __('OpenEMIS - One-time Password (OTP)');
            $emailMessage = "Dear " . $name . ",\n\nOne-time Password (OTP) is " . $six_digit_random_number . " . This OTP expires in 1 hour. \n\nBest regards,\nOpenEMIS Support\n\nThis is a system - generated email. Please do not reply to this email address.";
            $email
                ->setTo($userEmail)
                ->setSubject($emailSubject)
                ->send($emailMessage);
            $message = __('A verification code has been sent to your registered email address.');
            $this->Alert->success($message, ['type' => 'string', 'reset' => true]);
            $userName = $this->encrypt($userEntity->username, Security::getSalt());
            $userEmail = $this->encrypt($userEntity->email, Security::getSalt());
            $userPass = $this->encrypt($this->request->getData('password'), Security::getSalt());
            $id = $this->encrypt($userEntity->id, Security::getSalt());
            $encodedUserData = $this->paramsEncode(['username' => $userName, 'email'=>$userEmail, 'password' => $userPass]);
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'verifyOtp', $encodedUserData]);
        } else {//POCOR-7156 ends
            $this->SSO->doAuthentication($authenticationType, $code);
        }
        $this->getRequest()->getSession()->write('nbn', $this->request->getData()['password']);
        $this->getRequest()->getSession()->write('sbn', $this->request->getData()['username']);
    }

    //POCOR-7156 starts
    public function encrypt($pure_string, $secretHash)
    {
        $iv = substr($secretHash, 0, 16);
        $encryptedMessage = openssl_encrypt($pure_string, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $encrypted = base64_encode(
            $encryptedMessage
        );
        return $encrypted;
    }

    public function decrypt($encrypted_string, $secretHash)
    {
        $iv = substr($secretHash, 0, 16);
        $data = base64_decode($encrypted_string);
        $decryptedMessage = openssl_decrypt($data, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $decrypted = rtrim(
            $decryptedMessage
        );
        return $decrypted;
    }

    public function verifyOtp()
    {
        if(isset($this->request->getParam('pass')[0]) && !empty($this->request->getParam('pass')[0])){
            $userData = $this->paramsDecode($this->request->getParam('pass')[0]);

            $userData['username'] = $this->decrypt($userData['username'], Security::getSalt());
            $userData['email'] = $this->decrypt($userData['email'], Security::getSalt());
            $userData['password'] = $this->decrypt($userData['password'], Security::getSalt());
            $userEntity = $this->Users
                ->find()
                ->select([
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('username'),
                    $this->Users->aliasField('password'),
                    $this->Users->aliasField('email'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name')
                ])
                ->where([
                    $this->Users->aliasField('username') => $userData['username'],
                    $this->Users->aliasField('email') => $userData['email']
                ])
                ->first();
            if (empty($userEntity)) {
                $message = __('An email address is not registered for this account. Please contact your system administrator.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
            }else{
                $this->set('encryptdata', $this->request->getParam('pass')[0]);
                $this->set('username', $userData['username']);
                $this->set('password', $userData['password']);
                $this->set('id', $userData['id']);
                if ($this->request->is('post') && $this->request->getData('submit') == 'login') {
                    $SystemUserOtpTbl = TableRegistry::getTableLocator()->get('User.SecurityUserCodes');
                    $SystemUserOtpEntity = $SystemUserOtpTbl
                                ->find()
                                ->where([$SystemUserOtpTbl->aliasField('security_user_id') => $userEntity->id])
                                ->first();
                    if(!empty($SystemUserOtpEntity) && !empty($SystemUserOtpEntity->verification_otp)){
                        if(base64_decode($SystemUserOtpEntity->verification_otp) == trim($this->request->getData('otp'))){
                            $authenticationType = 'Local';
                            $code = null;
                            $this->SSO->doAuthentication($authenticationType, $code);
                        } else {
                            $message = __('Incorrect OTP code entered. Please try again.');
                            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'verifyOtp', $this->request->getParam('pass')[0]]); // POCOR-8972 getPass
                        }
                    }
                }
            }
        } else {
            $message = __('There was an error in sending the OTP. Please enter a valid email id or username.');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }

        $this->viewBuilder()->disableAutoLayout();
        //POCOR-8589 add render verify otp
        if($this->request->getQuery('type') != 'otp'){
            $this->render('verify_otp');
        }
    }

    public function logout($username = null)
    {
        if ($this->request->is('get')) {
            $authUser = $this->Auth->user();
            $username = $authUser['username'] ?? $username ?? null;

            $SecurityUserSessions = TableRegistry::getTableLocator()->get('SSO.SecurityUserSessions');
            $SecurityUserSessions->deleteEntries($username);
            $body = [
                'username'     => $username,
                'openemis_no'  => $authUser['openemis_no'] ?? null,
                'ip'           => $this->request->clientIp(),
                'logout_time'  => date('Y-m-d H:i:s'),
            ];

            // POCOR-9257: Queue logout webhook for async processing
            try {
                $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');
                $WebhookQueue = TableRegistry::getTableLocator()->get('Alert.WebhookQueue'); //POCOR-9257: moved to Alert plugin
                $user = $Webhooks->resolveCurrentUser();
                $result = $WebhookQueue->queueWebhook('logout', $body, $user);
                if ($result) {
                    // Log::debug("[UsersController::logout] ✓ Queued webhook for logout event");
                } else {
                    Log::warning("[UsersController::logout] Failed to queue logout webhook");
                }
            } catch (\Throwable $e) {
                Log::error("[UsersController::logout] Exception while queueing webhook: " . $e->getMessage());
            }

            return $this->redirect($this->Auth->logout());
        } else {
            throw new ForbiddenException();
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Auth.afterIdentify'] = 'afterIdentify';
        $events['Controller.Auth.afterAuthenticate'] = 'afterAuthenticate';
        $events['Controller.Auth.afterCheckLogin'] = 'afterCheckLogin';
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(EventInterface $event, $action)
    {
        if (in_array($action, ['login', 'logout', 'postLogin', 'login_remote'])) {
            return true;
        }
    }

    public function afterCheckLogin(EventInterface $event, $extra)
    {
        //POCOR-2976 start
        $SecurityUser = TableRegistry::getTableLocator()->get('User.Users');
        //POCOR-8498 Start
        $userData = '';
        if(!empty($this->request->getData()['username'])) {
            $userData = $SecurityUser->find()
            ->where(
                [$SecurityUser->aliasField('username') => $this->request->getData()['username']]
            )->first();
        }
        //POCOR-8498 End
        if (!$extra['loginStatus']) {
            if (!$extra['status']) {
                $this->Alert->error('security.login.inactive', ['reset' => true]);
            } else if ($extra['fallback']) {
                $url = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', 'submit' => 'retry']);
                $retryMessage = 'Remote authentication failed. <br>Please try local login or <a href="' . $url . '">Click here</a> to try again';
                $this->Alert->error($retryMessage, ['type' => 'string', 'reset' => true]);
            } else {
                //POCOR-2976 start
                if ($userData->status == 0) {
                    if (empty($userData)) {
                        $this->Alert->error('Account does not exist', ['type' => 'string', 'reset' => true]);
                    } else {
                        $this->Alert->error('security.login.locked_account', ['reset' => true]);
                    }
                    return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
                }
                // $this->Alert->error('security.login.fail', ['reset' => true]);
                $session = $this->request->getSession();
                $noOfPendingAttempts = $session->read('login.attempts');
                // echo "<pre>";print_r($noOfPendingAttempts);die;
                $noOfPendingAttempts--;
                $session->write('login.attempts', $noOfPendingAttempts);
                if ($noOfPendingAttempts <= 0) {
                    $SecurityUser->updateAll(['status' => 0],
                        ['username' => $this->request->getData('username')]);
                    if (empty($userData)) {
                        $this->Alert->error('Account does not exist', ['type' => 'string', 'reset' => true]);
                    } else {
                        $this->Alert->error('security.login.locked_account', ['reset' => true]);
                    }
                } else {
                    $message = "You have {$noOfPendingAttempts} more login attempts before your account will be locked.";
                    $this->Alert->warning($message, ['type' => 'string', 'reset' => true]);
                }
                //POCOR-2976 end
            }
            $event->stopPropagation();
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        }
    }

    public function afterAuthenticate(EventInterface $event, ArrayObject $extra)
    {
        //echo "<pre>"; print_r($_COOKIE['Restful']); die;
        //if ($this->Cookie->check('Restful')) {
        if (isset($_COOKIE['Restful'])) {
            //echo "<pre>"; print_r($this->generateToken()); die;
            $event->stopPropagation();
            //return $this->redirect(['plugin' => null, 'controller' => 'Rest', 'action' => 'auth', 'payload' => $this->generateToken(), 'version' => '2.0']);
            return $this->redirect([
                'plugin' => null,
                    'controller' => 'Rest',
                    'action' => 'auth',
                    '?' => [
                        'payload' => $this->generateToken(),
                        'version' => '2.0'
                    ]
                ]);
        } else {
            $user = $this->Auth->user();

            if (!empty($user)) {
                $listeners = [
                    $this->Users
                ];
                $this->Users->dispatchEventToModels('Model.Users.afterLogin', [$user], $this, $listeners);

                $SecurityUserSessions = TableRegistry::getTableLocator()->get('SSO.SecurityUserSessions');

                $SecurityUserSessions->addEntry($user['username'], $this->getRequest()->getSession()->id());

                // Labels
                $labels = TableRegistry::getTableLocator()->get('Labels');
                $labels->storeLabelsInCache();

                // Support Url
                $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
                $supportUrl = $ConfigItems->value('support_url');
                $this->getRequest()->getSession()->write('System.help', $supportUrl);
            }
        }
    }

    public function generateToken()
    {
        $user = $this->Auth->user();
        $privateKey = Configure::read('Application.private.key');

        try {
            $token = JWT::encode([
                'sub' => $user['id'],
                'exp' => time() + 86400 // 24 hours
            ], $privateKey, 'RS256');

            return $token;
        } catch (\Exception $e) {
            // Log the error or handle it accordingly
            error_log('JWT Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function afterIdentify(EventInterface $event, $user)
    {
        $user = $this->Users->get($user['id']);


        $this->log('[' . $user->username . '] Login successfully.', 'debug');

        // To remove inactive staff security group users records
        $InstitutionStaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
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
            Log::write('error', __METHOD__ . ' exception when removing inactive roles : ' . $ex);
        }
    }

    private function shellErrorRecovery()
    {
        $SystemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
        $processes = $SystemProcesses->getErrorProcesses();
        foreach ($processes as $process) {
            $id = $process['id'];
            $model = $process['model'];
            $params = $process['params'];
            $eventName = $process['callable_event'];
            $executedCount = $process['executed_count'];
            try {
                $modelTable = TableRegistry::getTableLocator()->get($model);

                if (!empty($eventName)) {
                    $event = $modelTable->dispatchEvent('Shell.' . $eventName, [$id, $executedCount, $params]);
                }
            } catch (\Cake\Core\Exception\MissingTableClassException $e) {
                Log::warning("⚠️ Model table '$model' not found: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("❌ Unexpected error loading model '$model': " . $e->getMessage());
            }
        }
    }

    /**
     * POCOR-7159
     * add data in user_activities table while updating password
     */
    public function updateUserPassword($userId)
    {
        $userActivities = TableRegistry::getTableLocator()->get('User.UserActivities'); //POCOR-8080 can not save entity to no-class table
        $currentTimeZone = date("Y-m-d H:i:s");
        $data = [
            'model' => 'Users',
            'model_reference' => $userId,
            'field' => 'password',
            'field_type' => 'string',
            'old_value' => '',
            'new_value' => '',
            'operation' => 'resetPass',
            'security_user_id' => $userId,
            'created_user_id' => $userId,
            'created' => $currentTimeZone,
        ];
        $entity = $userActivities->newEntity($data);
        $save = $userActivities->save($entity);
    }


    /**
     * POCOR-7159
     * add data in user_activities table while updating password
     */
    public function updateUserName($username, $userId)
    {
        $userActivities = TableRegistry::getTableLocator()->get('User.UserActivities');
        $currentTimeZone = date("Y-m-d H:i:s");
        $data = [
            'model' => 'Users',
            'model_reference' => $userId,
            'field' => 'username',
            'field_type' => 'string',
            'old_value' => $username,
            'new_value' => $username,
            'operation' => 'resetName',
            'security_user_id' => $userId,
            'created_user_id' => $userId,
            'created' => $currentTimeZone,
        ];
        $entity = $userActivities->newEntity($data);
        $save = $userActivities->save($entity);
    }
}
