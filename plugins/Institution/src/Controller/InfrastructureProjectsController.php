<?php
namespace Institution\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class InfrastructureProjectsController extends PageController
{
    private $fundingSourceOptions = [];
    private $projectStatusesOptions = [];
    private $needsOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->InfrastructureProjects);

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
        $page->addCrumb(__('Projects'));

        // set institution_id
        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);

        // set header
        $page->setHeader($institutionName . ' - ' . __('Projects'));

        // set queryString
        $page->setQueryString('institution_id', $institutionId);

        // set options
        $this->fundingSourceOptions = $this->InfrastructureProjects->getFundingSourceOptions();
        $this->projectStatusesOptions = $this->InfrastructureProjects->getProjectStatusesOptions();
        $this->needsOptions = $this->InfrastructureProjects->getNeedsOptions();

        // set field order
        $page->move('infrastructure_project_funding_source_id')->after('description')->setLabel(__('Funding source'));
        $page->move('infrastructure_need_id')->after('date_completed')->setLabel(__('Associated Needs'));
    }

    public function index()
    {
        $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureProjects->aliasField('created') => 'DESC']);

        // set field
        $page->exclude(['description', 'funding_source_description', 'contract_amount', 'date_started', 'date_completed', 'file_name', 'file_content', 'comment', 'infrastructure_need_id', 'institution_id']);

        $page->get('infrastructure_project_funding_source_id')->setSortable(false);
        $page->get('contract_date')->setSortable(false);
        $page->get('status')->setSortable(false);

        // set funding source filter
        $fundingSourceOptions = [null => __('All Funding Source')] + $this->fundingSourceOptions;
        $page->addFilter('infrastructure_project_funding_source_id')
            ->setOptions($fundingSourceOptions);

        // set project status filter
        $projectStatusesOptions = [null => __('All Statuses')] + $this->projectStatusesOptions;
        $page->addFilter('status')
            ->setOptions($projectStatusesOptions);

        parent::index();

        $data = $page->getData();
        foreach ($data as $key => $entity) {
            $this->getIdName($entity);
        }
    }

    public function add()
    {
        $this->addEditProjects();
        parent::add();
    }

    public function edit($id)
    {
        $this->addEditProjects();
        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file download for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');

        parent::view($id);

        $entity = $this->getIdName($page->getData());

        // if have infrastructure_need association will show the link
        if (!empty($entity->infrastructure_need_id)) {
            $institutionId = $entity->institution_id;
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

            $needId = $page->encode(['id' => $entity->infrastructure_need_id]);

            $url = ['
                plugin' => 'Institution',
                'controller' => 'InfrastructureNeeds',
                'action' => 'view',
                'institutionId' => $encodedInstitutionId,
                $needId
            ];

            $page->get('infrastructure_need_id')
                ->setControlType('link')
                ->setAttributes('href', $url)
                ->setValue($entity->infrastructure_need->name);
        }
        // end if have infrastructure_need association will show the link
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    private function addEditProjects()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set funding source
        $page->get('infrastructure_project_funding_source_id')
            ->setControlType('select')
            ->setOptions($this->fundingSourceOptions);

        // set project status
        $page->get('status')
            ->setControlType('select')
            ->setOptions($this->projectStatusesOptions);

        // set infrastructure needs
        $page->get('infrastructure_need_id')
            ->setControlType('select')
            ->setOptions($this->needsOptions);

        // set the file upload for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');
    }

    private function getIdName($entity)
    {
        // get the name from provided id in entity, because the data is hardcoded, like onUpdateField function
        // status
        if ($entity->has('status') && !empty($entity->status)) {
            $entity->status = $this->projectStatusesOptions[$entity->status];
        }

        return $entity;
    }
}
