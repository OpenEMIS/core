<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;
use Profile\Controller\CommentsController as BaseController;

class GuardianCommentsController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $guardianName = $session->read('Guardian.Guardians.name');
        $guardianId = $session->read('Guardian.Guardians.id');
        $studentId = $session->read('Guardian.Students.id');

        parent::beforeFilter($event);

        // set Header
        $page->setHeader($guardianName . ' - Comments');

        // set QueryString (for findIndex)
        $page->setQueryString('security_user_id', $guardianId);

        // set Breadcrumb
        $this->setBreadCrumb([
            'userId' => $guardianId,
            'userName' => $guardianName,
            'userRole' => 'Guardian',
            'institutionId' => $institutionId,
            'institutionName' => $institutionName
        ]);
        $this->setupTabElements(['userId' => $guardianId, 'userName' => $guardianName]);
    }

    public function add()
    {
        $page = $this->Page;
        $session = $this->request->session();

        $guardianId = $session->read('Guardian.Guardians.id');
        $page->get('security_user_id')->setValue($guardianId);

        parent::add();
    }
    public function setupTabElements($options)
    {
        $session = $this->request->session();
        $guardianName = $session->read('Guardian.Guardians.name');
        $guardianID = $session->read('Guardian.Guardians.id');
        $studentId = $session->read('Guardian.Students.id'); 

        $page = $this->Page;
        $plugin = $this->plugin;

        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $userRole = array_key_exists('userRole', $options) ? $options['userRole'] : '';
        $institutionId = array_key_exists('institutionId', $options) ? $options['institutionId'] : 0;

        $nationalityId = $this->Users->get($userId)->nationality_id;
        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            $userRole.'Account' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'SpecialNeeds' =>['text' =>  __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

        foreach ($tabElements as $action => $obj) {
            if (in_array($action, [$userRole.'Account'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $userId])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                    'plugin' => 'Institution',
                    'institutionId' => $encodedInstitutionId,
                    'controller' => 'GuardianComments',
                    'action' => 'index',
                    'queryString' => $encodedUserId
                ];
            } else {
                $url = [
                    'plugin' => 'Student',
                    'controller' => 'Students',
                    'action' => $action,
                    'index',
                    'queryString' => $encodedUserId
                ];

                // exceptions
                if ($action == 'UserNationalities') {
                    $url['action'] = 'Nationalities';
                    $url['queryString'] = $encodedUserAndNationalityId;
                }
            }
            $tabElements[$action]['url'] = $url;
        }

        if (!empty($guardianID)) {

            $userId = $guardianID;
            $StudentGuardianID=$this->request->session()->read('Student.Guardians.primaryKey');
             $newStudentGuardianID=$StudentGuardianID['id'];
            $url = ['plugin' => 'Student', 'controller' => 'Students'];
            $guardianstabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('Overview')]
             ];
            $action = 'Guardians';
            $actionUser = 'GuardianUser';
            $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $newStudentGuardianID])]);
            $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $userId, 'StudentGuardians.id' => $newStudentGuardianID])]);
            $guardianId = $userId;
            $tabElements = array_merge($guardianstabElements, $tabElements);
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
