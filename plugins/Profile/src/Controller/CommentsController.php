<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use App\Controller\PageController;

class CommentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Security.Users');
        $this->loadModel('User.Comments');
        $this->Page->loadElementsFromTable($this->Comments);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        // set label
        $page->get('comment_date')->setLabel('Date');

        // set field order
        $page->move('comment_type_id')->first();
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['security_user_id']);

        // set sortable
        $page->get('comment_type_id')->setSortable(true);
        $page->get('comment')->setSortable(false);

        // set field order
        $page->move('comment_date')->first();
        $page->move('comment_type_id')->after('comment_date');

        // set default ordering
        $page->setQueryOption('order', [$this->Comments->aliasField('comment_date') => 'DESC']);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['security_user_id']);

        parent::view($id);
    }

    public function add()
    {
        $page = $this->Page;
        $page->get('security_user_id')->setControlType('hidden');
        $page->get('comment_type_id')->setControlType('select');

        parent::add();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('security_user_id')->setControlType('hidden');
        $page->get('comment_type_id')->setControlType('select');

        parent::edit($id);
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->get('security_user_id')->setControlType('hidden');

        parent::delete($id);
    }

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $encodedUserId = $this->paramsEncode(['id' => $userId]);

        // for Institution Staff and Institution Students
        if ($plugin == 'Institution') {
            $userRole = array_key_exists('userRole', $options) ? $options['userRole'] : '';
            $institutionId = array_key_exists('institutionId', $options) ? $options['institutionId'] : 0;
            $institutionName = array_key_exists('institutionName', $options) ? $options['institutionName'] : '';

            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            $pluralUserRole = Inflector::pluralize($userRole);

            $page->addCrumb('Institutions', [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Institutions',
                'index'
            ]);
            $page->addCrumb($institutionName, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'dashboard',
                'institutionId' => $encodedInstitutionId,
                $encodedInstitutionId
            ]);
            $page->addCrumb($pluralUserRole, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $pluralUserRole,
                'institutionId' => $encodedInstitutionId
            ]);
            $page->addCrumb($userName, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $userRole.'User',
                'view',
                $encodedUserId
            ]);
            $page->addCrumb('Comments');
        } else if ($plugin == 'Profile') {
            $page->addCrumb('Profile', [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'Profiles',
                'view',
                $encodedUserId
            ]);
            $page->addCrumb($userName);
            $page->addCrumb('Comments');
        } else if ($plugin == 'Directory') {
            $page->addCrumb('Directory', [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'Directories'
            ]);
            $session = $this->request->session();
            $guardianId = $session->read('Guardian.Guardians.id');
            $studentId = $session->read('Student.Students.id');
            $isStudent = $session->read('Directory.Directories.is_student');
            $isGuardian = $session->read('Directory.Directories.is_guardian');            
            if (!empty($guardianId) && !empty($isStudent)) {
                    $studentId = $session->read('Directory.Directories.id');
                    $userName = $this->Users->get($studentId)->name;
                    $encodedUserId = $this->paramsEncode(['id' => $studentId]);
                    $page->addCrumb($userName, [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Directories',
                    'view',
                    $encodedUserId
                ]);
                $page->addCrumb('Guardian Comments');
            } elseif (!empty($studentId) && !empty($isGuardian)) {
                    $guardianId = $session->read('Directory.Directories.id');
                    $userName = $this->Users->get($guardianId)->name;
                    $encodedUserId = $this->paramsEncode(['id' => $guardianId]);
                    $page->addCrumb($userName, [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Directories',
                    'view',
                    $encodedUserId
                ]);
                $page->addCrumb('Student Comments');                
            } else {
                $page->addCrumb($userName, [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Directories',
                    'view',
                    $encodedUserId
                ]);
                $page->addCrumb('Comments');
            }
        } else if ($plugin == 'Guardian') {
            $User = TableRegistry::get('User.Users');
            $session = $this->request->session();
            $institutionName = $session->read('Institution.Institutions.name');
            $institutionId = $session->read('Institution.Institutions.id');
            $studentId = $session->read('Student.Students.id');
            $entity = $User->get($studentId);
            $name = $entity->name;

            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            $page->addCrumb('Institutions', [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Institutions',
                'index'
            ]);
            $page->addCrumb($institutionName, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'dashboard',
                'institutionId' => $encodedInstitutionId,
                $encodedInstitutionId
            ]);
            $page->addCrumb('Students',[
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Students']);
            $page->addCrumb($name, [
                    'plugin' => 'Institution', 
                    'controller' => 'Institutions', 
                    'action' => 'StudentUser', 
                    'view', $this->paramsEncode(['id' => $studentId])]);
            $page->addCrumb('Guardian Comments');
        }
    }

    // for Directories and Profiles
    public function setupTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';

        $nationalityId = $this->Users->get($userId)->nationality_id;
        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            $pluralPlugin => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'SpecialNeeds' =>['text' =>  __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')]
        ];

        foreach ($tabElements as $action => $obj) {
            if (in_array($action, [$pluralPlugin, 'Accounts'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $userId])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $plugin.'Comments',
                    'action' => 'index',
                    'queryString' => $encodedUserId
                ];
            } else {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
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

        if ($plugin == 'Directory') {
            if (isset($tabElements['History'])) {
                unset($tabElements['History']);
            }
        }
        
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $action => $obj) {
            $page->addTab($action)
                ->setTitle($obj['text'])
                ->setUrl($obj['url']);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }

    // for Institution Staff and Institution Students
    public function setupInstitutionTabElements($options)
    {
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
        $pluralUserRole = Inflector::pluralize($userRole);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            $userRole.'User' => ['text' => __('Overview')],
            $userRole.'Account' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

        // extra student tabs
        if ($userRole == 'Student') {
            $studentTabElements = [
                'Guardians' => ['text' => __('Guardians')],
                'StudentTransport' => ['text' => __('Transport')]
            ];
            $tabElements = array_merge($tabElements, $studentTabElements);
        }

        foreach ($tabElements as $action => $obj) {
            if (in_array($action, [$userRole.'User', $userRole.'Account'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $userId])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'institutionId' => $encodedInstitutionId,
                    'controller' => $userRole.'Comments',
                    'action' => 'index',
                    'queryString' => $encodedUserId
                ];
            } else {
                $url = [
                    'plugin' => $userRole,
                    'controller' => $pluralUserRole,
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

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        if (Configure::read('schoolMode')) {
            if (isset($tabElements['StudentSurveys'])) {
                unset($tabElements['StudentSurveys']);
            }
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
