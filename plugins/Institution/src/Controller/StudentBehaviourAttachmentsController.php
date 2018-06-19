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
        // pr($this->request);
        // pr($this->request->query['querystring']);die;
        // $this->setupTabElements();
    }

    public function index()
    {
        parent::index();
        
        // $page = $this->Page;
        // $page->exclude(['file_content']);
    }

    // public function view($id)
    // {
    //     parent::view($id);

        // $page = $this->Page;
        // $entity = $page->getData();

        // $page->get('file_name')
        //     ->setControlType('hidden');
        
    // }
    public function add()
    {
        parent::add();
        $page = $this->Page;
        // $this->addEdit();
        $studentBehaviourId = $this->paramsDecode($this->request->query['querystring']);
        // pr($studentBehaviourId);
        $page->get('student_behaviour_id')
             ->setValue($studentBehaviourId['student_behaviour_id']);
        $page->get('file_content')
            ->setLabel('Attachment');
        $page->get('file_name')
            ->setLabel('Name');
        
    }

    // public function edit($id)
    // {
    //     parent::edit($id);
        // $this->addEdit();
    // }

    // private function addEdit()
    // {
    //     $page = $this->Page;
        // $page->exclude(['file_name']);
        // $page->get('institution_committee_id')
        //     ->setControlType('hidden');
    // }

    // public function setupTabElements()
    // {
    //     $page = $this->Page;
    //     $tabElements = [];
       
    //     $tabElements = $this->InstitutionCommitteeTabs->getInstitutionCommitteeTabs();

    //     foreach ($tabElements as $tab => $tabAttr) {
    //         $page->addTab($tab)
    //             ->setTitle($tabAttr['text'])
    //             ->setUrl($tabAttr['url']);
    //     }
    //     // set active tab
    //     $page->getTab('Attachments')->setActive('true');
    // }

}