<?php
namespace Profile\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class CommentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Security.Users');
        $this->loadModel('User.UserComments');
        $this->Page->loadElementsFromTable($this->UserComments);
        $this->Page->disable(['add', 'delete', 'edit']);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $securityUserId = $this->Auth->user('id');
        $securityUserName = $this->Auth->user('name');

        parent::beforeFilter($event);

        $page->exclude(['security_user_id']);
        $page->get('comment_date')->setLabel('Date');

        // set Breadcrumb
        $this->setBreadCrumb($securityUserId, $securityUserName, $plugin);

        // set header
        $header = $page->getHeader();
        $page->setHeader($securityUserName . ' - ' . $header);

        // set TabElement
        $this->setupTabElements();

        // set queryString
        $page->setQueryString('security_user_id', $securityUserId);
    }

    public function index()
    {
        $page = $this->Page;

        // set field order
        $page->move('comment_date')->first();
        $page->move('comment_type_id')->after('comment_date');

        // set default ordering
        // $page->setQueryOption('order', [$this->Comments->aliasField('date') => 'DESC']);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;

        // set field order
        $page->move('comment_type_id')->first();
        $page->move('comment_date')->after('comment');

        parent::view($id);
    }

    private function setBreadCrumb($securityUserId, $securityUserName, $plugin)
    {
        $page = $this->Page;
// pr('setBreadCrumb ' .$plugin);
        if ($plugin == 'Institution') {
            // got institution id (student and staff)
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $institutionName = $session->read('Institution.Institutions.name');
            $studentId = $session->read('Student.Students.id');
            $studentName = $session->read('Student.Students.name');

            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            $encodedStudentId = $this->paramsEncode(['id' => $studentId]);

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
            $page->addCrumb('Students', [
                'plugin' => $this->plugin,
                'controller' => 'Institutions',
                'action' => 'Students',
                'institutionId' => $encodedInstitutionId
            ]);
            $page->addCrumb($studentName, [
                'plugin' => $this->plugin,
                'controller' => 'Institutions',
                'action' => 'StudentUser',
                'view',
                $encodedStudentId
            ]);
            $page->addCrumb('Comments');
        } else if ($plugin == 'Profile') {
            // no institution id (Profile and Directory)
            $encodedSecurityUserId = $this->paramsEncode(['id' => $securityUserId]);

            $page->addCrumb('Profile', ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'index']);
            $page->addCrumb($securityUserName, ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $encodedSecurityUserId]);
            $page->addCrumb('Comments');
        }
    }

    private function setupTabElements()
    {
        // set tabElement
        $page = $this->Page;
        // $tabPlugin = $this->plugin;
        $tabPlugin = 'Profile';
        $tabController = 'Profiles';

        $loginUserId = $this->Auth->user('id');
        $loginUserData = $this->Users->get($loginUserId);
        $loginUserNationalityId = $loginUserData->nationality_id;

        $encodedLoginUserId = $this->paramsEncode(['id' => $loginUserId]);

        // overviewTab
        if ($this->AccessControl->check([$tabController, 'Profiles', 'index'])) {
            $overviewTab = $page->addTab('Overview');
            $overviewTab->setTitle('Overview');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Profiles',
                'view',
                $encodedLoginUserId
            ];
            $overviewTab->setUrl($url);
        }

        // accountTab
        if ($this->AccessControl->check([$tabController, 'Accounts', 'index'])) {
            $accountTab = $page->addTab('Account');
            $accountTab->setTitle('Account');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Accounts',
                'view',
                $encodedLoginUserId
            ];
            $accountTab->setUrl($url);
        }

        // identitiesTab
        if ($this->AccessControl->check([$tabController, 'Identities', 'index'])) {
            $identitiesTab = $page->addTab('Identities');
            $identitiesTab->setTitle('Identities');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Identities',
                'index',
                $encodedLoginUserId
            ];
            $identitiesTab->setUrl($url);
        }

        // nationalitiesTab
        if ($this->AccessControl->check([$tabController, 'Nationalities', 'index'])) {
            $nationalitiesTab = $page->addTab('Nationalities');
            $nationalitiesTab->setTitle('Nationalities');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Nationalities',
                'index',
                $this->paramsEncode([
                    'security_user_id' => $loginUserId,
                    'nationality_id' => $loginUserNationalityId
                ])
            ];
            $nationalitiesTab->setUrl($url);
        }

        // contactsTab
        if ($this->AccessControl->check([$tabController, 'Contacts', 'index'])) {
            $contactsTab = $page->addTab('Contacts');
            $contactsTab->setTitle('Contacts');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Contacts',
                'index',
                $encodedLoginUserId
            ];
            $contactsTab->setUrl($url);
        }

        // languagesTab
        if ($this->AccessControl->check([$tabController, 'Languages', 'index'])) {
            $languagesTab = $page->addTab('Languages');
            $languagesTab->setTitle('Languages');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Languages',
                'index',
                $encodedLoginUserId
            ];
            $languagesTab->setUrl($url);
        }

        // commentsTab
        $commentsTab = $page->addTab('Comments');
        $commentsTab->setTitle('Comments');
        $url = [
                'plugin' => $tabPlugin,
                'controller' => 'Comments',
                'action' => 'index',
                $encodedLoginUserId
            ];
        $commentsTab->setUrl($url);
        $commentsTab->setActive('true');

        // attachmentsTab
        if ($this->AccessControl->check([$tabController, 'Attachments', 'index'])) {
            $attachmentsTab = $page->addTab('Attachments');
            $attachmentsTab->setTitle('Attachments');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'Attachments',
                'index',
                $encodedLoginUserId
            ];
            $attachmentsTab->setUrl($url);
        }

        // specialneedsTab
        if ($this->AccessControl->check([$tabController, 'SpecialNeeds', 'index'])) {
            $specialneedsTab = $page->addTab('SpecialNeeds');
            $specialneedsTab->setTitle('Special Needs');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'SpecialNeeds',
                'index',
                $encodedLoginUserId
            ];
            $specialneedsTab->setUrl($url);
        }

        // historyTab
        if ($this->AccessControl->check([$tabController, 'Histories', 'index'])) {
            $historyTab = $page->addTab('History');
            $historyTab->setTitle('History');
            $url = [
                'plugin' => $tabPlugin,
                'controller' => $tabController,
                'action' => 'History',
                'index',
                $encodedLoginUserId
            ];
            $historyTab->setUrl($url);
        }
        // end of set Tab
    }
}