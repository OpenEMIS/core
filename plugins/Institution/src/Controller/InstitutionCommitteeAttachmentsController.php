<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Controller\PageController;

class InstitutionCommitteeAttachmentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->InstitutionCommitteeAttachments);
        $this->loadComponent('Institution.InstitutionCommitteeTabs');

        $this->Page->disable(['search']);
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
        $page->addCrumb('Committees', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'InstitutionCommittees', 'action' => 'index']);
        $page->addCrumb('Attachments');

        $this->setupTabElements();

        // set header
        $page->setHeader($institutionName . ' - ' . __('Committee Attachments'));

        $institutionCommitteId = $this->Page->decode($this->request->query['querystring']);
    }

    public function index()
    {
        parent::index();

        $page = $this->Page;
        $page->exclude(['file_content']);
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;
        $entity = $page->getData();
        
    }
    public function add()
    {
        parent::add();
        $institutionCommitteId = $this->Page->decode($this->request->query['querystring']);
        $page = $this->Page;
        $page->get('institution_committee_id')
             ->setControlType('hidden')
             ->setValue($institutionCommitteId['institution_committee_id']);
        $page->get('file_content')
            ->setLabel('Attachment');
        $page->get('file_name')
            ->setLabel('Name');
        
    }

    public function setupTabElements()
    {
        $page = $this->Page;
        $name = $this->name;

        $tabElements = [];
       
        $tabElements = $this->InstitutionCommitteeTabs->getInstitutionCommitteeTabs();

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('Attachments')->setActive('true');
    }

}
