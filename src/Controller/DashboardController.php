<?php
namespace App\Controller;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use App\Controller\AppController;
use Cake\Log\Log;
use Cake\I18n\Time;
use App\Model\Table\AlertsTable;
use Cake\Controller\Controller;
use Cake\Http\Client;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Cache\Cache;

class DashboardController extends AppController
{
    private const SKIPPABLE_ALERTS = [
        'UnmarkedAttendance'];
    private bool $useCacheInCallAlerts = false;
    public function initialize(): void
    {
        parent::initialize();

         $this->ControllerAction->model('Notices');
         $this->loadComponent('Paginator');

        $this->attachAngularModules();
        $this->fetchTable('Workflow.WorkflowRules');
//        $workflowRules = $this->WorkflowRules->find()->where(['feature' => 'StudentUnmarkedAttendances'])->toArray();
//        if (!empty($workflowRules)) {
//            //$this->triggerUnmarkedAttendanceShell(); //POCOR-7489 comment it for taking time and utlized the max cpu memory on server
//        }

        //$this->triggerAutomatedStudentWithdrawalShell();
        //$this->triggerInstitutionClassSubjectsShell(); // By Anand Stop the InstitutionClassSubjects shell

        //POCOR-9509: start - stop triggering alerts from Dashboard and rely on scheduled Laravel commands instead
//        $user = $this->Auth->user();
//        if(!$user){
//            return;
//        }
//        $userId = $user['id'];
//        $isSuperAdmin = !empty($user['super_admin']);
//        if (!$this->useCacheInCallAlerts) {
//            $this->callAlerts($userId, $isSuperAdmin);
//            return;
//        }
//
//        $cacheKey = 'alerts_triggered_' . $userId . '_' . FrozenTime::now()->format('Y-m-d-H'); // e.g. every hour
//        if (!Cache::read($cacheKey)) {
//            $this->callAlerts($userId, $isSuperAdmin);
//            Cache::write($cacheKey, true, 'default');
//        }
        //POCOR-9509: end - stop triggering alerts from Dashboard and rely on scheduled Laravel commands instead

    }

    // CAv4
    public function StudentWithdraw()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentWithdraw']);
    }
    // end of CAv4

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(EventInterface $event, $action)
    {
        return true;
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $user = $this->Auth->user();
//      POCOR-8972 start
        if (is_array($user) && (empty($user['last_login']))) {

            $header = __('Home Page');
            $this->set('contentHeader', $header);
            $userInfo = TableRegistry::getTableLocator()->get('User.Users')->get($user['id']);
            if ($userInfo->password) {
                $changePasswordUrl = ['plugin' => 'Profile',
                    'controller' => 'Profiles',
                    'action' => 'Accounts',
                    '0' => 'edit',
                    '1' => $this->ControllerAction->paramsEncode(['id' => $user['id']])];
                $check = $this->AccessControl->check($changePasswordUrl);
                $lastLogin = $userInfo->last_login;
                $this->request->getSession()->write('Auth.User.last_login', $lastLogin);
                if ($check) {
                    Log::debug('Redirecting to change password page');
                    $this->Alert->warning('security.login.changePassword');
                    $this->redirect($changePasswordUrl);
                }else{
//                    Log::debug('No rights to Redirecting to change password page');
                }
            }else{
//                Log::debug('No password to Redirecting to change password page');
            }
        }else{
//            Log::debug('No user or user has logged to Redirecting to change password page');
        }
//      POCOR-8972 end
        $header = __('Home Page');
        $this->set('contentHeader', $header);

        $rootPath = dirname($_SERVER['REQUEST_URI']);
        //$expirationTime = (new FrozenTime())->addDay();
        $cookie = new \Cake\Http\Cookie\Cookie(
            'my_base_url',
            $rootPath/*,
            $expirationTime,
            $fullBaseUrl,
            $httpHost,
            true,
            true */
        );

        // Write the cookie
        $this->response = $this->response->withCookie($cookie);

    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        // set header
        $header = $model->getHeader($model->alias);
        $this->set('contentHeader', $header);
    }

    public function index()
    {
        $user = $this->Auth->user();
        /*POCOR-6395 starts*/
        if (!$this->AccessControl->isAdmin()) {
            $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
            $SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
            $SecurityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
            $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
            $roleIds = [];
            $functionId = $SecurityFunctions->find()
                ->where([
                    $SecurityFunctions->aliasField('name') => 'User Profile Completeness',
                    $SecurityFunctions->aliasField('module') => 'Personal',
                ])->first()->id;
            if (!empty($functionId)) {
                $userRole = $SecurityGroupUsers
                    ->find()
                    ->select([
                        $SecurityGroupUsers->aliasField('security_role_id'),
                    ])
                    ->where(['security_user_id' => $user['id']])
                    ->toArray();
                if (!empty($userRole)) {
                    foreach ($userRole as $role) {
                        $roleIds[] = $role->security_role_id;
                    }

                    $functionAccess = $SecurityRoleFunctions->find()
                        ->where([
                            $SecurityRoleFunctions->aliasField('security_role_id IN') => $roleIds,
                            $SecurityRoleFunctions->aliasField('security_function_id') => $functionId,
                            $SecurityRoleFunctions->aliasField('_view') => 1
                        ])->toArray();
                    if (!empty($functionAccess)) {
                        $hasPermission = true;
                    } else {
                        $hasPermission = false;
                    }
                }

                $this->set('hasPermission', $hasPermission);
            }
        }

        /*POCOR-6395 ends*/
        //POCOR-9509: checkRequireUpdate does nothing (triggerUpdateStudentStatusShell is commented out) but scans student_status_updates on every dashboard load — skip it
//        $StudentStatusUpdates = TableRegistry::getTableLocator()->get('Institution.StudentStatusUpdates');
//        $StudentStatusUpdates->checkRequireUpdate();

        $this->set('ngController', 'DashboardCtrl as DashboardController');
        if ($this->AccessControl->isAdmin()) {
            $this->set('isAdmin', true);
        }
//        $profileData = $this->getProfileCompletnessData($user['id']); //POCOR-8074-6
//        $this->set('profileCompletness', $profileData);
        $this->set('noBreadcrumb', true);

    }

    private function attachAngularModules()
    {
        $action = $this->getRequest()->getAttribute('params')['action'];
        switch ($action) {
            case 'index':
                $this->Angular->addModules([
                   // 'alert.svc',
                    'dashboard.ctrl',
                    'dashboard.svc'
                ]);
                break;
        }
    }


    private function triggerUnmarkedAttendanceShell()
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateStudentUnmarkedAttendances';
        $logs = ROOT . DS . 'logs' . DS . 'GenerateStudentUnmarkedAttendances.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);

    }

    private function triggerInstitutionClassSubjectsShell()
    {
        $script = 'InstitutionClassSubjects';
        $consoleDir = ROOT . DS . 'bin' . DS;
        $logs = ROOT . DS . 'logs' . DS . 'InstitutionClassSubjects.log & echo $!';
        $cmd = ROOT . DS . 'bin' . DS . 'cake InstitutionClassSubjects';
        $nohup = 'nohup ' . $cmd . '> /dev/null 2>/dev/null &';
        exec($nohup);
        Log::write('debug', $nohup);
    }

    public function getProfileCompletnessData($userId)
    {

        $data = array();
        //$data['percentage'] = 0;//POCOR-6395
        $profileComplete = 0;
        $securityUsers = TableRegistry::getTableLocator()->get('User.Users');
        $securityUsersData = $securityUsers->find()
            ->select([
                'created' => 'Users.created',
                'modified' => 'Users.modified',
            ])
            ->where([$securityUsers->aliasField('id') => $userId])
            ->order(['Users.modified' => 'desc'])
            ->limit(1)
            ->first();

        $userDemographics = TableRegistry::getTableLocator()->get('User.Demographic');
        $userDemographicsData = $userDemographics->find()
            ->select([
                'created' => 'Demographic.created',
                'modified' => 'Demographic.modified',
            ])
            ->where([$userDemographics->aliasField('security_user_id') => $userId])
            ->order(['Demographic.modified' => 'desc'])
            ->limit(1)
            ->first();

        $userIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        $userIdentitiesData = $userIdentities->find()
            ->select([
                'created' => 'Identities.created',
                'modified' => 'Identities.modified',
            ])
            ->where([$userIdentities->aliasField('security_user_id') => $userId])
            ->order(['Identities.modified' => 'desc'])
            ->limit(1)
            ->first();
        ;
        $userNationalities = TableRegistry::getTableLocator()->get('User.Nationalities');
        $userNationalitiesData = $userNationalities->find()
            ->select([
                'created' => 'Nationalities.created',
                'modified' => 'Nationalities.modified',
            ])
            ->where([$userNationalities->aliasField('security_user_id') => $userId])
            ->order(['Nationalities.modified' => 'desc'])
            ->limit(1)
            ->first();

        $userContacts = TableRegistry::getTableLocator()->get('User.Contacts');
        $userContactsData = $userContacts->find()
            ->select([
                'created' => 'Contacts.created',
                'modified' => 'Contacts.modified',
            ])
            ->where([$userContacts->aliasField('security_user_id') => $userId])
            ->order(['Contacts.modified' => 'desc'])
            ->limit(1)
            ->first();

        $userLanguages = TableRegistry::getTableLocator()->get('User.UserLanguages');
        $userLanguagesData = $userLanguages->find()
            ->select([
                'created' => 'UserLanguages.created',
                'modified' => 'UserLanguages.modified',
            ])
            ->where([$userLanguages->aliasField('security_user_id') => $userId])
            ->order(['UserLanguages.modified' => 'desc'])
            ->limit(1)
            ->first();
        // config
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $enabledTypeList = $ConfigItem
            ->find()
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1, $ConfigItem->aliasField('value') => 1, $ConfigItem->aliasField('type') => 'User Data Completeness']) //POCOR-6022
            ->toArray();
        foreach ($enabledTypeList as $key => $enabled) {
            $data[$key]['feature'] = $enabled->name;
            if ($enabled->name == 'Overview') {
                if (!empty($securityUsersData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($securityUsersData->modified) ? date("F j,Y", strtotime($securityUsersData->modified)) : date("F j,Y", strtotime($securityUsersData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Demographic') {
                if (!empty($userDemographicsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($userDemographicsData->modified) ? date("F j,Y", strtotime($userDemographicsData->modified)) : date("F j,Y", strtotime($userDemographicsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Identities') {
                if (!empty($userIdentitiesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($userIdentitiesData->modified) ? date("F j,Y", strtotime($userIdentitiesData->modified)) : date("F j,Y", strtotime($userIdentitiesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Nationalities') {
                if (!empty($userNationalitiesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($userNationalitiesData->modified) ? date("F j,Y", strtotime($userNationalitiesData->modified)) : date("F j,Y", strtotime($userNationalitiesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Contacts') {
                if (!empty($userContactsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($userContactsData->modified) ? date("F j,Y", strtotime($userContactsData->modified)) : date("F j,Y", strtotime($userContactsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Languages') {
                if (!empty($userLanguagesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($userLanguagesData->modified) ? date("F j,Y", strtotime($userLanguagesData->modified)) : date("F j,Y", strtotime($userLanguagesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
        }

        $totalProfileComplete = count($data);
        $profilePercentage = 100 / $totalProfileComplete * $profileComplete;
        $profilePercentage = round($profilePercentage);
        $data['percentage'] = $profilePercentage;
        return $data;
    }

    public function getProfileCompletnessDataBAK($userId)
    {
        $data = array();
        $profileComplete = 0;
        //$totalProfileComplete = 6;
        $securityUsers = TableRegistry::getTableLocator()->get('security_users');
        $securityUsersData = $securityUsers->find()
            ->select([
                'created' => 'security_users.created',
                'modified' => 'security_users.modified',
            ])
            ->where([$securityUsers->aliasField('id') => $userId])
            ->order(['security_users.modified' => 'desc'])
            ->limit(1)
            ->first();


        $data[0]['feature'] = 'Overview';
        if (!empty($securityUsersData)) {
            $profileComplete = $profileComplete + 1;
            $data[0]['complete'] = 'yes';
            $data[0]['profileComplete'] = $profileComplete;
            $data[0]['modifiedDate'] = ($securityUsersData->modified) ? date("F j,Y", strtotime($securityUsersData->modified)) : date("F j,Y", strtotime($securityUsersData->created));
        } else {
            $data[0]['complete'] = 'no';
            $data[0]['profileComplete'] = 0;
            $data[0]['modifiedDate'] = 'Not updated';
        }

        $userDemographics = TableRegistry::getTableLocator()->get('user_demographics');
        $userDemographicsData = $userDemographics->find()
            ->select([
                'created' => 'user_demographics.created',
                'modified' => 'user_demographics.modified',
            ])
            ->where([$userDemographics->aliasField('security_user_id') => $userId])
            ->order(['user_demographics.modified' => 'desc'])
            ->limit(1)
            ->first();
        ;
        $data[1]['feature'] = 'Demographic';
        if (!empty($userDemographicsData)) {
            $profileComplete = $profileComplete + 1;
            $data[1]['complete'] = 'yes';
            $data[1]['profileComplete'] = $profileComplete;
            $data[1]['modifiedDate'] = ($userDemographicsData->modified) ? date("F j,Y", strtotime($userDemographicsData->modified)) : date("F j,Y", strtotime($userDemographicsData->created));
        } else {
            $data[1]['complete'] = 'no';
            $data[1]['profileComplete'] = 0;
            $data[1]['modifiedDate'] = 'Not updated';
        }

        $userIdentities = TableRegistry::getTableLocator()->get('user_identities');
        $userIdentitiesData = $userIdentities->find()
            ->select([
                'created' => 'user_identities.created',
                'modified' => 'user_identities.modified',
            ])
            ->where([$userIdentities->aliasField('security_user_id') => $userId])
            ->order(['user_identities.modified' => 'desc'])
            ->limit(1)
            ->first();
        ;
        $data[2]['feature'] = 'Identities';
        if (!empty($userIdentitiesData)) {
            $profileComplete = $profileComplete + 1;
            $data[2]['complete'] = 'yes';
            $data[2]['profileComplete'] = $profileComplete;
            $data[2]['modifiedDate'] = ($userIdentitiesData->modified) ? date("F j,Y", strtotime($userIdentitiesData->modified)) : date("F j,Y", strtotime($userIdentitiesData->created));
        } else {
            $data[2]['complete'] = 'no';
            $data[2]['profileComplete'] = 0;
            $data[2]['modifiedDate'] = 'Not updated';
        }

        $userNationalities = TableRegistry::getTableLocator()->get('user_nationalities');
        $userNationalitiesData = $userNationalities->find()
            ->select([
                'created' => 'user_nationalities.created',
                'modified' => 'user_nationalities.modified',
            ])
            ->where([$userNationalities->aliasField('security_user_id') => $userId])
            ->order(['user_nationalities.modified' => 'desc'])
            ->limit(1)
            ->first();
        $data[3]['feature'] = 'Nationalities';
        if (!empty($userNationalitiesData)) {
            $profileComplete = $profileComplete + 1;
            $data[3]['complete'] = 'yes';
            $data[3]['profileComplete'] = $profileComplete;
            $data[3]['modifiedDate'] = ($userNationalitiesData->modified) ? date("F j,Y", strtotime($userNationalitiesData->modified)) : date("F j,Y", strtotime($userNationalitiesData->created));
        } else {
            $data[3]['complete'] = 'no';
            $data[3]['profileComplete'] = 0;
            $data[3]['modifiedDate'] = 'Not updated';
        }

        $userContacts = TableRegistry::getTableLocator()->get('user_contacts');
        $userContactsData = $userContacts->find()
            ->select([
                'created' => 'user_contacts.created',
                'modified' => 'user_contacts.modified',
            ])
            ->where([$userContacts->aliasField('security_user_id') => $userId])
            ->order(['user_contacts.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[4]['feature'] = 'Contacts';
        if (!empty($userContactsData)) {
            $profileComplete = $profileComplete + 1;
            $data[4]['complete'] = 'yes';
            $data[4]['profileComplete'] = $profileComplete;
            $data[4]['modifiedDate'] = ($userContactsData->modified) ? date("F j,Y", strtotime($userContactsData->modified)) : date("F j,Y", strtotime($userContactsData->created));
        } else {
            $data[4]['complete'] = 'no';
            $data[4]['profileComplete'] = 0;
            $data[4]['modifiedDate'] = 'Not updated';
        }

        $userLanguages =TableRegistry::getTableLocator()->get('user_languages');
        $userLanguagesData = $userLanguages->find()
            ->select([
                'created' => 'user_languages.created',
                'modified' => 'user_languages.modified',
            ])
            ->where([$userLanguages->aliasField('security_user_id') => $userId])
            ->order(['user_languages.modified' => 'desc'])
            ->limit(1)
            ->first();
        $data[5]['feature'] = 'Languages';
        if (!empty($userLanguagesData)) {
            $profileComplete = $profileComplete + 1;
            $data[5]['complete'] = 'yes';
            $data[5]['profileComplete'] = $profileComplete;
            $data[5]['modifiedDate'] = ($userLanguagesData->modified) ? date("F j,Y", strtotime($userLanguagesData->modified)) : date("F j,Y", strtotime($userLanguagesData->created));
        } else {
            $data[5]['complete'] = 'no';
            $data[5]['profileComplete'] = 0;
            $data[5]['modifiedDate'] = 'Not updated';
        }


        // $profilePercentage = 100/$totalProfileComplete * $profileComplete;
        // $profilePercentage = round($profilePercentage);
        //$data['percentage'] = $profilePercentage;
        // config
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $typeList = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1, $ConfigItem->aliasField('value') => 1, $ConfigItem->aliasField('type') => 'User Data Completeness']) //POCOR-6022
            ->toArray();

        $typeOptions = array_keys($typeList);
        $totalProfileComplete = count($data);
        $typeListDisable = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1, $ConfigItem->aliasField('value') => 0, $ConfigItem->aliasField('type') => 'User Data Completeness']) //POCOR-6022
            ->toArray();
        if ($typeListDisable) {
            $countList = count($typeListDisable);
            if ($profileComplete != 1 && $profileComplete != 0) {
                $profileComplete = $profileComplete - $countList;
            }
        }
        foreach ($data as $key => $featureData) {
            if (!in_array($featureData['feature'], $typeOptions)) {
                unset($data[$key]);
                //$data = array_values($data);
                $totalProfileComplete = count($data);
            }
        }
        $profilePercentage = 100 / $totalProfileComplete * $profileComplete;
        $profilePercentage = round($profilePercentage);
        $data['percentage'] = $profilePercentage;
        // end config
        return $data;
    }

    /* private function triggerAutomatedStudentWithdrawalShell()
    {
        $script = 'AutomatedStudentWithdrawal';
        $consoleDir = ROOT . DS . 'bin' . DS;
        $logs = ROOT . DS . 'logs' . DS . 'AutomatedStudentWithdrawal.log & echo $!';
        $cmd = ROOT . DS . 'bin' . DS . 'cake AutomatedStudentWithdrawal';
        $nohup = 'nohup ' . $cmd . '> /dev/null 2>/dev/null &';
        exec($nohup);
        Log::write('debug', $nohup);
    }*/

    //    private function triggerInstitutionClassSubjectsShell()
//    {
//        $script = 'InstitutionClassSubjects';
//        $consoleDir = ROOT . DS . 'bin' . DS;
//        $logs = ROOT . DS . 'logs' . DS . 'InstitutionClassSubjects.log & echo $!';
//        $cmd = ROOT . DS . 'bin' . DS . 'cake InstitutionClassSubjects';
//        $nohup = 'nohup ' . $cmd . '> /dev/null 2>/dev/null &';
//        exec($nohup);
//        Log::write('debug', $nohup);
//    }


    ////bin/cake/InstitutionClassSubjects 123
//bin/cake AutomatedStudentWithdrawalShell migrate


    //POCOR-7558 start
//    private function callAlerts()
//    {
//        $AlertsTable = TableRegistry::getTableLocator()->get('Alert.Alerts');
//        $AlertsData = $AlertsTable->find('all')
//            ->where(['frequency !=' => 'Never']) // POCOR-8533-C3
//            ->toArray();
////        Log::debug(print_r($AlertsData, true));
//        $lastRunDates = TableRegistry::getTableLocator()->get('Alert.AlertRules')->getLastRunDate();
//        $mainAlerts = [];
//        foreach ($AlertsData as $key => $value) {
//            $currentDate = FrozenTime::now()->format('Y-m-d');
//            $otherDate = null;
//            if(!empty($lastRunDates[$value['name']])) {
//                   $otherDate = $lastRunDates[$value['name']];
//            }
//            $finalDate = null;
//            if (!empty($otherDate)) {
//                if($value['frequency'] == "Weekly") {
//                     $finalDate = $otherDate->modify('+1 week')->format('Y-m-d');
//                }else if ($value['frequency'] == "Weekly") {
//                     $finalDate = $otherDate->modify('+1 month')->format('Y-m-d');
//                }else if ($value['frequency'] == "Monthly") {
//                     $finalDate = $otherDate->modify('+1 year')->format('Y-m-d');
//                }else {
//                     $finalDate = $otherDate->format('Y-m-d');
//                }
//            }
//            if ($currentDate > $finalDate || empty($otherDate)) {
//                $AlertRulesTable = TableRegistry::getTableLocator()->get('Alert.AlertRules');
//                $AlertRules = $AlertRulesTable->find()->
//                    where([
//                        $AlertRulesTable->aliasField('feature') => $value['name'],
//                        $AlertRulesTable->aliasField('enabled') => 1
//                    ])->toArray();
//                if (!empty($AlertRules)) {
//                    foreach ($AlertRules as $data) {
//                        $mainAlerts[$value['process_name']] = $data;
//                    }
//                }
//            }}
//
//        foreach ($mainAlerts as $key => $value) {
//            $user = $this->Auth->user();
//            $systemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');
//            $systemProcessEntity = $systemProcesses->newEntity([
//                'name' => $value['feature'],
//                'status' => 1,
//                'start_date' => Time::now(),
//                'model' => $key,
//                'end_date' => null,
//                'created_user_id' => $user['id']
//
//            ]);
//            $saveData = $systemProcesses->save($systemProcessEntity);
//            $AlertsTable->triggerAlertFeatureShell($key);
//            if (!empty($saveData)) {
//                $systemProcesses->updateAll([
//                    'status' => 3,
//                    'end_date' => Time::now(),
//                    'modified' => Time::now(),
//                    'modified_user_id' => $user['id']
//                ], ['name' => $value['feature']]);
//            }
//        }
//    }


    //POCOR-7558 end
    private function callAlerts($userId, $isSuperAdmin): void
    {
        //         $this->sendSystemUpdateAlerts(); //POCOR-7559
        //        $this->sendRetirementWarningAlerts(); //POCOR-8341

        $alertsTable = TableRegistry::getTableLocator()->get('Alert.Alerts');
        $alertRulesTable = TableRegistry::getTableLocator()->get('Alert.AlertRules');
        $systemProcessesTable = TableRegistry::getTableLocator()->get('SystemProcesses');


        $userRoleIds = $isSuperAdmin ? [] : $this->getUserSecurityRoleIds($userId);

        $alerts = $this->getUserAccessibleAlerts($alertsTable, $userRoleIds, $isSuperAdmin);

        foreach ($alerts as $alert) {
            if(!is_array($alert)){
                $alert = $alert->toArray();
            }
            $alert_name = $alert['name'];
            if (in_array($alert_name, self::SKIPPABLE_ALERTS, true)) {
                continue;
            }

            if ($this->shouldTriggerAlert($alert)) {
                $activeRules = $alertRulesTable->find()
                    ->where([
                        $alertRulesTable->aliasField('feature') => $alert['name'],
                        $alertRulesTable->aliasField('enabled') => 1
                    ])
                    ->toArray();

                foreach ($activeRules as $rule) {
                    if(!is_array($rule)){
                        $rule = $rule->toArray();
                    }
                    self::triggerSystemProcess($systemProcessesTable, $rule, $alert['process_name'], $userId);
                }
            }else{
                Log::debug('Should Not Trigger Alert');
            }
        }

    }

    public function getUserSecurityRoleIds(int $userId): array
    {
        $roles = $this->AccessControl->getRolesByUser($userId)->toArray();
        return collection($roles)->extract('security_role_id')->toArray();
    }

    private function getUserAccessibleAlerts($alertsTable, array $userRoleIds, bool $isSuperAdmin)
    {
        if ($isSuperAdmin) {
            return $alertsTable->find()
                ->where([
                    'frequency IS NOT' => null,
                    'frequency NOT IN' => ['Never', 'Once']
                ]);
        }
        //POCOR-9429 start
        if(empty($userRoleIds)){
            return [];
        }
        //POCOR-9429 end

        return $alertsTable->find()
            ->distinct(['Alerts.id'])
            ->matching('AlertRules.SecurityRoles', function ($q) use ($userRoleIds) {
                return $q->where(['SecurityRoles.id IN' => $userRoleIds]);
            })
            ->where([
                'Alerts.frequency IS NOT' => null,
                'Alerts.frequency NOT IN' => ['Never', 'Once']
            ]);
    }

    private function shouldTriggerAlert($alert): bool
    {
        $currentDate = FrozenTime::now()->format('Y-m-d');
        $frequency = $alert['frequency'];

        $lastRunDate = $this->getLastRunDateForAlert($alert['process_name']);

        if ($lastRunDate == -1 ) {
            Log::debug("[Alert: {$alert['name']}] Not eligible for run – still active or never completed.");
            return false;
        }else if (empty($lastRunDate)) {
            Log::debug("[Alert: {$alert['name']}] Not triggered yet");
            return true;
        }
        Log::debug("[Alert: {$alert['name']}] $lastRunDate");
        $nextRunDate = $this->calculateNextRunDate($frequency, clone $lastRunDate);

        if ($nextRunDate === null) {
            Log::debug("[Alert: {$alert->name}] Should run once, skipping.");
            return false;
        }

        return $currentDate >= $nextRunDate;
    }

    private function getLastRunDateForAlert(string $processName)
    {
        $systemProcesses = TableRegistry::getTableLocator()->get('SystemProcesses');

        // Check for active processes first
        $active = $systemProcesses->find()
            ->where([
                'name' => $processName,
                'status IN' => [1, 2]
            ])
            ->first();

        if ($active) {
            return -1; // Signal active/running, not eligible
        }

        // Then fetch the last completed run
        $completed = $systemProcesses->find()
            ->select(['end_date'])
            ->where([
                'name' => $processName,
                'status' => 3
            ])
            ->orderDesc('end_date')
            ->limit(1)
            ->first();

        return $completed?->end_date;
    }

    private function calculateNextRunDate(string $frequency, FrozenTime $date): ?string
    {
        switch ($frequency) {
            case 'Weekly':
                return $date->modify('+1 week')->format('Y-m-d');
            case 'Monthly':
                return $date->modify('+1 month')->format('Y-m-d');
            case 'Yearly':
                return $date->modify('+1 year')->format('Y-m-d');
            case 'Daily':
                return $date->modify('+1 day')->format('Y-m-d');
            case 'Once':
                return null; // never run
            default:
                return '9999-12-31'; // never run
        }
    }


    public static function triggerSystemProcess($systemProcessesTable, $rule, string $processName, int $userId, array $extraOptions = []): void
    {
        $now = FrozenTime::now();
        // POCOR-9391 start
        $extraRuleOptions = $extraOptions;
        $extraRuleOptions['rule_id'] = $rule['id'];
        $paramsJson = json_encode($extraRuleOptions);
        // POCOR-9391 end
        // Check for an existing process with same model, name, rule and params
        $feature = $rule['feature'];
        $existing = $systemProcessesTable->find()
            ->where([
                'model' => $processName,
                'name' => $feature,
                'params' => $paramsJson,
                'created_user_id' => $userId,
//                'status IN' => [1, 2, 3] // Start, Running, Finished
            ])
            ->first();

        if ($existing) {
            // 🦕 Skip creating duplicate process
            Log::debug(print_r([
                __FUNCTION__ => ' Already Present',
                'params' => $paramsJson], true));
            return;
        }

        $processValues = [
            'name' => $feature,
            'status' => 1,
            'start_date' => $now,
            'model' => $processName,
            'created_user_id' => $userId,
            'params' => $paramsJson
        ];

        $process = $systemProcessesTable->newEntity($processValues);
        if ($systemProcessesTable->save($process)) {
            Log::debug(__FUNCTION__ . 'Fired!');
            self::triggerAlertCommand($processName, $userId, $rule['id'], $process->id, $extraOptions);
        } else {
            Log::debug(__FUNCTION__ . 'Could Not Fire');
        }
    }


    public static function triggerAlertCommand(string $processName, int $userId, int $ruleId, int $processId, array $extraOptions = []): void
    {
        $command = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $processName)); // CamelCase to snake_case

        $argsArray = [
            '--user_id=' . $userId,
            '--rule_id=' . $ruleId,
            '--process_id=' . $processId
        ];

        foreach ($extraOptions as $key => $value) {
            $argsArray[] = '--' . $key . '=' . escapeshellarg($value);
        }

        $args = implode(' ', $argsArray);

        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $command . ' ' . $args;
        $logPath = ROOT . DS . 'logs' . DS . $command . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logPath;

        exec($shellCmd);
        Log::write('debug', '[AlertCommand] ' . $shellCmd);
    }

}
