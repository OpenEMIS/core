<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Controller\PageController;

class StudentBehaviourAttachmentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->disable(['search']);
        $this->Page->enable(['download']);
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Student Behaviours', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'StudentBehaviours','index']);
        $page->addCrumb('Attachments');

        // // set header
        $page->setHeader($institutionName . ' - ' . __('Attachments'));

        $query = $this->request->query['querystring'];

        $this->setupTabElements($encodedInstitutionId, $query);
    }

    public function index()
    {
        parent::index();
        $page = $this->Page;
        $page->exclude(['file_content']);
        $page->exclude(['student_behaviour_id']);
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;
        $page->exclude(['student_behaviour_id']);
        $page->exclude(['file_name']);
        $page->get('file_name')
            ->setControlType('hidden');
    }

    public function add()
    {
        parent::add();
        $page = $this->Page;
        $this->addEdit();
        $studentBehaviourId = $page->decode($this->request->query['querystring']);
        $page->get('student_behaviour_id')
             ->setValue($studentBehaviourId['student_behaviour_id']);
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit();
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    private function addEdit()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);
        $page->get('student_behaviour_id')
            ->setControlType('hidden');
        $page->get('file_content')
            ->setLabel('Attachment');
    }

    public function setupTabElements($encodedInstitutionId, $query)
    {
        $page = $this->Page;
        $studentBehaviourIdDecode = $page->decode($query);
        $studentBehaviourIdEncode = $this->paramsEncode(['id' => $studentBehaviourIdDecode['student_behaviour_id']]);
        $page = $this->Page;
        $tabElements = [];
       
        $tabElements = [
            'StudentBehaviours' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $encodedInstitutionId, 'action' => 'StudentBehaviours', 'view', $studentBehaviourIdEncode],
                'text' => __('Overview')
            ],
            'StudentBehaviourAttachments' => [
                'url' => ['plugin' => 'Institution','controller' => 'StudentBehaviourAttachments', 'institutionId' => $encodedInstitutionId, 'action' => 'index', 'querystring' => $query],
                'text' => __('Attachments')
            ]
        ];

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('StudentBehaviourAttachments')->setActive('true');
    }

}