<?php
namespace Directory\Controller;

use Cake\Event\Event;
use Profile\Controller\CommentsController as BaseController;

class DirectoryCommentsController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $requestQuery = $this->request->query;
        $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        $userName = $this->Users->get($userId)->name;

        parent::beforeFilter($event);

        // setup
        $page->setHeader($userName . ' - ' . __('Comments'));
        $page->setQueryString('security_user_id', $userId);
        $this->setBreadCrumb(['userId' => $userId, 'userName' => $userName]);
        $this->setupTabElements(['userId' => $userId, 'userName' => $userName]);
    }

    public function add()
    {
        $page = $this->Page;
        $requestQuery = $this->request->query;

        $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        $page->get('security_user_id')->setValue($userId);

        parent::add();
    }
    
    public function setupTabElements($options)
    {
        $page = $this->Page;
        
        $session = $this->request->session();
        $guardianId = $session->read('Guardian.Guardians.id');
        $studentId = $session->read('Student.Students.id');
        $isStudent = $session->read('Directory.Directories.is_student');
        $isGuardian = $session->read('Directory.Directories.is_guardian');
        $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
        $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');

        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $nationalityId = $this->Users->get($userId)->nationality_id;
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);

        $tabElements = [
            'Directories' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];
        foreach ($tabElements as $action => $obj) {
            if (in_array($action, ['Directories', 'Accounts'])) {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $userId])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'DirectoryComments',
                    'action' => 'index',
                    'queryString' => $encodedUserId
                ];
            } elseif ($action == 'UserNationalities') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Nationalities',
                    'index',
                    'queryString' => $encodedUserAndNationalityId
                ];
            } else {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => $action,
                    'index',
                    'queryString' => $encodedUserId
                ];
            }
            $tabElements[$action]['url'] = $url;
        }
        if (!empty($guardianId) && !empty($isStudent) && !empty($studentToGuardian)) {
            $StudentGuardianId = $this->request->session()->read('Student.Guardians.primaryKey')['id'];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $guardianstabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('Overview')]
             ];
            $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => 'StudentGuardians', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => 'StudentGuardianUser', 'view', $this->paramsEncode(['id' => $guardianId, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($guardianstabElements, $tabElements);
            unset($tabElements['Directories']);
        } elseif (!empty($studentId) && !empty($isGuardian) && !empty($guardianToStudent)) {
            $StudentGuardianId = $this->request->session()->read('Student.Guardians.primaryKey')['id'];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $guardianstabElements = [
                'Students' => ['text' => __('Relation')],
                'StudentUser' => ['text' => __('Overview')]
             ];
            $guardianstabElements['Students']['url'] = array_merge($url, ['action' => 'GuardianStudents', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $guardianstabElements['StudentUser']['url'] = array_merge($url, ['action' => 'GuardianStudentUser', 'view', $this->paramsEncode(['id' => $studentId, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($guardianstabElements, $tabElements);
            unset($tabElements['Directories']);
        }

        foreach ($tabElements as $action => $obj) {
            $page->addTab($action)
                ->setTitle($obj['text'])
                ->setUrl($obj['url']);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }
}
