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
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);

        $page->exclude(['security_user_id']);
        $page->get('comment_date')->setLabel('Date');

        // setup Tabs with incomplete url
        $this->setupTabElements();
    }

    public function index()
    {
        $page = $this->Page;

        // set field order
        $page->move('comment_date')->first();
        $page->move('comment_type_id')->after('comment_date');

        // set default ordering
        $page->setQueryOption('order', [$this->UserComments->aliasField('comment_date') => 'DESC']);

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

    public function setupTabElements()
    {
        $page = $this->Page;

        $tabElements = [
            'Overview' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'Nationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'SpecialNeeds' =>['text' =>  __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')]
        ];

        foreach ($tabElements as $action => $options) {
            // only set subaction in url
            $subaction = ($action == 'Overview' || $action == 'Accounts') ? 'view' : 'index';
            $page->addTab($action)
                ->setTitle($options['text'])
                ->setUrl(['0' => $subaction]);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }
}
