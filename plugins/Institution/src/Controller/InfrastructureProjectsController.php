<?php
namespace Institution\Controller;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Routing\Router;

use App\Controller\PageController;
use Page\Model\Entity\PageElement;
use Cake\ORM\TableRegistry;

class InfrastructureProjectsController extends PageController
{
    private $fundingSourceOptions = [];
    private $projectStatusesOptions = [];
    private $needsOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.InfrastructureProjectsNeeds');
        // to disable actions if institution is not active
        $this->loadComponent('Institution.InstitutionInactive');

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
        $this->needsOptions = $this->InfrastructureProjects->getNeedsOptions($institutionId);

        // set project status
        $page->get('status')
            ->setControlType('select')
            ->setOptions($this->projectStatusesOptions);

        // set field order
        $Users = TableRegistry::get('labels');
        $result = $Users
            ->find()
            ->where(['module' => 'InfrastructureProjects', 'field_name' => 'Funding Source'])
            ->toArray();
        if(isset($result[0]['name'])){
            $page->move('infrastructure_project_funding_source_id')->after('description')->setLabel($result[0]['name']);
        }else{
            $page->move('infrastructure_project_funding_source_id')->after('description')->setLabel(__('Funding Source'));
        }

        $Users = TableRegistry::get('labels');
        $result = $Users
            ->find()
            ->where(['module' => 'InfrastructureProjects', 'field_name' => 'Contract Date'])
            ->toArray();
        if(isset($result[0]['name'])){
            $page->get('contract_date')->setSortable(false)->setLabel($result[0]['name']);
        }else{
            $page->move('contract_date')->after('infrastructure_project_funding_source_id');
        }
    }

    public function index()
    {
        $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureProjects->aliasField('created') => 'DESC']);

        // set field
        $page->exclude(['description', 'funding_source_description', 'contract_amount', 'date_started', 'date_completed', 'file_name', 'file_content', 'comment', 'institution_id']);

        $page->get('infrastructure_project_funding_source_id')->setSortable(false);
        // $Users = TableRegistry::get('labels');
        // $result = $Users
        //     ->find()
        //     ->where(['module' => 'InfrastructureProjects', 'field_name' => 'Contract Date'])
        //     ->toArray();
        // if(isset($result[0]['name'])){
        //     $page->get('contract_date')->setSortable($result[0]['name']);
        // }else{
        //     $page->get('contract_date')->setSortable(false);
        // }
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
    }

    public function add()
    {
        $this->addEdit();
        parent::add();
    }

    public function edit($id)
    {
        $this->addEdit();
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

        $entity = $page->getData();

        // if have infrastructure_need association will show the link
        $associatedNeeds = $this->getAssociatedRecords($entity);

        if (!empty($associatedNeeds)) {
            $page->addNew('infrastructure_needs')
                ->setControlType('table')
                ->setAttributes('column', [
                    ['label' => __('Need Name')],
                    ['key' => 'link'],
                ])
                ->setAttributes('row', $associatedNeeds) // $associatedNeeds is an array
            ;

            $page->move('infrastructure_needs')->after('date_completed')->setLabel('Associated Needs');
        }
        // end if have infrastructure_need association will show the link
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

        // set funding source
        $page->get('infrastructure_project_funding_source_id')
            ->setControlType('select');

        // set infrastructure needs
        $page->addNew('infrastructure_needs')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Needs'))
            ->setOptions($this->needsOptions, false);

        $page->move('infrastructure_needs')->after('date_completed')->setLabel('Associated Needs');

        // set the file upload for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');
    }

    private function getAssociatedRecords($entity)
    {
        $page = $this->Page;
        $needData = $this->InfrastructureProjectsNeeds->find()
            ->contain(['InfrastructureNeeds'])
            ->where([$this->InfrastructureProjectsNeeds->aliasField('infrastructure_project_id') => $entity->id])
            ->all();

        $associatedRecords = [];
        if (count($needData)) {
            $institutionId = $entity->institution_id;
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

            foreach ($needData as $key => $need) {
                $encodedNeedId = $page->encode(['id' => $need->infrastructure_need_id]);
                $needName = $need->infrastructure_need->name;

                // build the url
                $url = Router::url([
                    'plugin' => 'Institution',
                    'controller' => 'InfrastructureNeeds',
                    'action' => 'view',
                    'institutionId' => $encodedInstitutionId,
                    $encodedNeedId
                ]);

                $associatedRecords[] = [
                    'need_name' => $needName,
                    'link' => '<a href=' . $url . ')> ' . $needName . '</a>'
                ];
            }
        }

        return $associatedRecords;
    }
}
