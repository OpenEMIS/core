<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Controller\PageController;

class StaffBehaviourAttachmentsController extends PageController
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
        $page->addCrumb('Staff Behaviours', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'StaffBehaviours','index']);
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
        $page->exclude(['staff_behaviour_id']);
    }

    public function view($id)
    {
        parent::view($id);
        $page = $this->Page;
        $page->exclude(['staff_behaviour_id']);
        $page->exclude(['file_name']);
        $page->get('file_name')
            ->setControlType('hidden');
    }

    public function add()
    {
        parent::add();
        $page = $this->Page;
        $this->addEdit();
        $staffBehaviourId = $page->decode($this->request->query['querystring']);
        $page->get('staff_behaviour_id')
             ->setValue($staffBehaviourId['staff_behaviour_id']);
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
        $page->get('staff_behaviour_id')
            ->setControlType('hidden');
        $page->get('file_content')
            ->setLabel('Attachment');
    }

    public function setupTabElements($encodedInstitutionId, $query)
    {
        $page = $this->Page;
        $staffBehaviourIdDecode = $page->decode($query);
        $staffBehaviourIdEncode = $this->paramsEncode(['id' => $staffBehaviourIdDecode['staff_behaviour_id']]);
        $page = $this->Page;
        $tabElements = [];
       
        $tabElements = [
            'StaffBehaviours' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $encodedInstitutionId, 'action' => 'StaffBehaviours', 'view', $staffBehaviourIdEncode],
                'text' => __('Overview')
            ],
            'StaffBehaviourAttachments' => [
                'url' => ['plugin' => 'Institution','controller' => 'StaffBehaviourAttachments', 'institutionId' => $encodedInstitutionId, 'action' => 'index', 'querystring' => $query],
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
        $page->getTab('StaffBehaviourAttachments')->setActive('true');
    }

}