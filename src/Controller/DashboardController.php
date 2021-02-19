<?php
namespace App\Controller;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use App\Controller\AppController;
use Cake\Log\Log;

class DashboardController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        // $this->ControllerAction->model('Notices');
        // $this->loadComponent('Paginator');

        $this->attachAngularModules();
        $this->triggerUnmarkedAttendanceShell();
        $this->triggerAutomatedStudentWithdrawalShell();
        //$this->triggerInstitutionClassSubjectsShell(); // By Anand Stop the InstitutionClassSubjects shell
		
    }

    // CAv4
    public function StudentWithdraw()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentWithdraw']);
    }
    // end of CAv4

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        return true;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $user = $this->Auth->user();
        if (is_array($user) && array_key_exists('last_login', $user) && is_null($user['last_login'])) {
            $userInfo = TableRegistry::get('User.Users')->get($user['id']);
            if ($userInfo->password) {
                $this->Alert->warning('security.login.changePassword');
                $lastLogin = $userInfo->last_login;
                $this->request->session()->write('Auth.User.last_login', $lastLogin);
                $this->redirect(['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Accounts', 'edit', $this->ControllerAction->paramsEncode(['id' => $user['id']])]);
            }

        }
        $header = __('Home Page');
        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        // set header
        $header = $model->getHeader($model->alias);
        $this->set('contentHeader', $header);
    }

    public function index()
    {
        $user = $this->Auth->user();
        $StudentStatusUpdates = TableRegistry::get('Institution.StudentStatusUpdates');
        $StudentStatusUpdates->checkRequireUpdate();
        $this->set('ngController', 'DashboardCtrl as DashboardController');
        if ($this->AccessControl->isAdmin()) {
            $this->set('isAdmin',true);
        }
        $profileData = $this->getProfileCompletnessData($user['id']);
        $this->set('profileCompletness',$profileData);
        $this->set('noBreadcrumb', true);
    }

    private function attachAngularModules()
    {
        $action = $this->request->action;

        switch ($action) {
            case 'index':
                $this->Angular->addModules([
                    'alert.svc',
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

    public function getProfileCompletnessData ($userId) {
        $data = array();
        $profileComplete = 0;
		$totalProfileComplete = 6;
        $securityUsers = TableRegistry::get('security_users');
		$securityUsersData = $securityUsers->find()		
				->select([
					'created' => 'security_users.created',
					'modified' => 'security_users.modified',
				])
				->where([$securityUsers->aliasField('id') => $userId])
                ->order(['security_users.modified'=>'desc'])
				->limit(1)
				->first();
		
		
		$data[0]['feature'] = 'Overview';
		if(!empty($securityUsersData)) {
			$profileComplete = $profileComplete + 1;
		    $data[0]['complete'] = 'yes';
		    $data[0]['modifiedDate'] = ($securityUsersData->modified)?date("F j,Y",strtotime($securityUsersData->modified)):date("F j,Y",strtotime($securityUsersData->created));
		} else {
            $data[0]['complete'] = 'no';
            $data[0]['modifiedDate'] = 'Not updated';
        }
		
		$userDemographics = TableRegistry::get('user_demographics');
		$userDemographicsData = $userDemographics->find()		
				->select([
					'created' => 'user_demographics.created',
					'modified' => 'user_demographics.modified',
				])
				->where([$userDemographics->aliasField('security_user_id') => $userId])
                ->order(['user_demographics.modified'=>'desc'])
				->limit(1)
				->first();
				;
        $data[1]['feature'] = 'Demographic';
		if(!empty($userDemographicsData)) {
			$profileComplete = $profileComplete + 1;
            $data[1]['complete'] = 'yes';
		    $data[1]['modifiedDate'] = ($userDemographicsData->modified)?date("F j,Y",strtotime($userDemographicsData->modified)):date("F j,Y",strtotime($userDemographicsData->created));
		} else {
            $data[1]['complete'] = 'no';
            $data[1]['modifiedDate'] = 'Not updated';
        }
		
		$userIdentities = TableRegistry::get('user_identities');
		$userIdentitiesData = $userIdentities->find()		
				->select([
					'created' => 'user_identities.created',
					'modified' => 'user_identities.modified',
				])
				->where([$userIdentities->aliasField('security_user_id') => $userId])
                ->order(['user_identities.modified'=>'desc'])
				->limit(1)
				->first();
				;
        $data[2]['feature'] = 'Identities';
		if(!empty($userIdentitiesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[2]['complete'] = 'yes';
		    $data[2]['modifiedDate'] = ($userIdentitiesData->modified)?date("F j,Y",strtotime($userIdentitiesData->modified)):date("F j,Y",strtotime($userIdentitiesData->created));
		} else {
            $data[2]['complete'] = 'no';
            $data[2]['modifiedDate'] = 'Not updated';
        }
		
		$userNationalities = TableRegistry::get('user_nationalities');
		$userNationalitiesData = $userNationalities->find()		
				->select([
					'created' => 'user_nationalities.created',
					'modified' => 'user_nationalities.modified',
				])
				->where([$userNationalities->aliasField('security_user_id') => $userId])
                ->order(['user_nationalities.modified'=>'desc'])
				->limit(1)
				->first();
		$data[3]['feature'] = 'Nationalities';
		if(!empty($userNationalitiesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[3]['complete'] = 'yes';
		    $data[3]['modifiedDate'] = ($userNationalitiesData->modified)?date("F j,Y",strtotime($userNationalitiesData->modified)):date("F j,Y",strtotime($userNationalitiesData->created));
		} else {
            $data[3]['complete'] = 'no';
            $data[3]['modifiedDate'] = 'Not updated';
        }
		
		$userContacts = TableRegistry::get('user_contacts');
		$userContactsData = $userContacts->find()		
				->select([
					'created' => 'user_contacts.created',
					'modified' => 'user_contacts.modified',
				])
				->where([$userContacts->aliasField('security_user_id') => $userId])
                ->order(['user_contacts.modified'=>'desc'])
				->limit(1)
				->first();
		
        $data[4]['feature'] = 'Contacts';
		if(!empty($userContactsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[4]['complete'] = 'yes';
		    $data[4]['modifiedDate'] = ($userContactsData->modified)?date("F j,Y",strtotime($userContactsData->modified)):date("F j,Y",strtotime($userContactsData->created));
		} else {
            $data[4]['complete'] = 'no';
            $data[4]['modifiedDate'] = 'Not updated';
        }
		
		$userLanguages = TableRegistry::get('user_languages');
		$userLanguagesData = $userLanguages->find()		
				->select([
					'created' => 'user_languages.created',
					'modified' => 'user_languages.modified',
				])
				->where([$userLanguages->aliasField('security_user_id') => $userId])
                ->order(['user_languages.modified'=>'desc'])
				->limit(1)
				->first();
		$data[5]['feature'] = 'Languages';
		if(!empty($userLanguagesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[5]['complete'] = 'yes';
		    $data[5]['modifiedDate'] = ($userLanguagesData->modified)?date("F j,Y",strtotime($userLanguagesData->modified)):date("F j,Y",strtotime($userLanguagesData->created));
		} else {
            $data[5]['complete'] = 'no';
            $data[5]['modifiedDate'] = 'Not updated';
        }
	
		
		$profilePercentage = 100/$totalProfileComplete * $profileComplete;
		$profilePercentage = round($profilePercentage);
		$data['percentage'] = $profilePercentage;
        return $data;
    }

    private function triggerAutomatedStudentWithdrawalShell()
   {
       $script = 'AutomatedStudentWithdrawal';
       $consoleDir = ROOT . DS . 'bin' . DS;
       $logs = ROOT . DS . 'logs' . DS . 'AutomatedStudentWithdrawal.log & echo $!';
       $cmd = ROOT . DS . 'bin' . DS . 'cake AutomatedStudentWithdrawal';
       $nohup = 'nohup ' . $cmd . '> /dev/null 2>/dev/null &';
       exec($nohup);
       Log::write('debug', $nohup); 
   }

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
    
}
////bin/cake/InstitutionClassSubjects 123
//bin/cake AutomatedStudentWithdrawalShell migrate