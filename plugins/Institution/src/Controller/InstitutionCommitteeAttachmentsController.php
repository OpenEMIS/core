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
        $page->addCrumb('Committees', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Committees']);
        $page->addCrumb('Attachments');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Committee Attachments'));

        $query = $this->request->query['querystring'];
        
        $this->setupTabElements($encodedInstitutionId, $query);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        // $queryString = $this->paramsDecode($this->request->query['querystring']);
        // //echo '<pre>';print_r($queryString);die;
        // $institutionCommitteeId = $queryString['institution_committee_id'];
        // $page->setQueryString('institution_committee_id', $institutionCommitteeId);
        $page->setQueryOption('order', [$this->InstitutionCommitteeAttachments->aliasField('created') => 'DESC']);
        parent::index();
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;
        $page->exclude(['file_name']);
        $page->exclude(['institution_committee_id']);
        $page->get('file_name')
            ->setControlType('hidden');
    }
    public function add()
    {
        parent::add();
        $page = $this->Page;
        $this->addEdit();
        //$institutionCommitteeId = $page->decode($this->request->query['querystring']);
        $institutionCommitteeId = $this->paramsDecode($this->request->query['querystring']);
         
        $page->get('institution_committee_id')
             ->setValue($institutionCommitteeId['institution_committee_id']);
        $page->get('file_content')
            ->setLabel('Attachment');
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit();
    }

    private function addEdit()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);
        $page->get('institution_committee_id')
            ->setControlType('hidden');
    }

    public function delete($id)
    {
        parent::delete($id);
        $page = $this->Page;
        $page->exclude(['file_content']);
    }

    public function setupTabElements($encodedInstitutionId, $query)
    {
        $page = $this->Page;
        $tabElements = [];
        $decodeCommitteeId = $this->paramsDecode($query);
        $committeeId = $decodeCommitteeId['institution_committee_id'];
        $encodeCommitteeId = $this->paramsEncode(['id' => $committeeId]);
        $tabElements = [
            'InstitutionCommittees' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Committees','view', $encodeCommitteeId],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'InstitutionCommitteeAttachments', 'action' => 'index', 'querystring' => $query],
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
        $page->getTab('Attachments')->setActive('true');
    }
}
