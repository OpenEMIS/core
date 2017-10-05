<?php
namespace Institution\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class InfrastructureNeedsController extends PageController
{
    private $needTypeOptions = [];
    private $needPrioritiesOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('InfrastructureNeedsTable');
        $this->Page->loadElementsFromTable($this->InfrastructureNeeds);

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
        $page->addCrumb(__('Needs'));

        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);

        // set header
        $page->setHeader($institutionName . ' - ' . __('Needs'));

        // set queryString
        $page->setQueryString('institution_id', $institutionId);

        // set options
        $this->needTypeOptions = $this->InfrastructureNeeds->getNeedTypesOptions();
        $this->needPrioritiesOptions = $this->InfrastructureNeeds->getNeedPrioritiesOptions();

        // set field order
        $page->move('infrastructure_need_type_id')->after('name')->setLabel(__('Need Type'));
        $page->move('priority')->after('description');
    }

    public function index()
    {
        $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureNeeds->aliasField('created') => 'DESC']);

        // set field
        $page->exclude(['description', 'date_determined', 'date_started', 'date_completed', 'file_name', 'file_content', 'comment', 'institution_id']);

        $page->get('infrastructure_need_type_id')->setSortable(false)->setLabel(__('Need Type'));
        $page->get('priority')->setSortable(false);

        // set need type filter
        $needTypeOptions = [null => __('All Need Types')] + $this->needTypeOptions;
        $page->addFilter('infrastructure_need_type_id')
            ->setOptions($needTypeOptions);

        // set need priority filter
        $needPrioritiesOptions = [null => __('All Priorities')] + $this->needPrioritiesOptions;
        $page->addFilter('priority')
            ->setOptions($needPrioritiesOptions);

        parent::index();

        $data = $page->getData();
        foreach ($data as $key => $entity) {
            $this->getIdName($entity);
        }
    }

    public function add()
    {
        $this->addEditNeeds();
        parent::add();
    }

    public function edit($id)
    {
        $this->addEditNeeds();
        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        $page->get('file_name')->setControlType('link')->setLabel(__('Attachment'));
        parent::view($id);

        $entity = $this->getIdName($page->getData());
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    private function addEditNeeds()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set need_type
        $page->get('infrastructure_need_type_id')
            ->setControlType('dropdown')
            ->setOptions($this->needTypeOptions);

        // set need_priority
        $page->get('priority')
            ->setControlType('dropdown')
            ->setOptions($this->needPrioritiesOptions);

        // set the file upload for attachment
        $page->get('file_content')
            ->set('fileName', 'file_name')
            ->set('fileSizeLimit', '2')
            ->setLabel(__('Attachment'));
    }

    private function getIdName($entity)
    {
        // get the name from provided id in entity, because the data is hardcoded, like onUpdateField function
        // priority
        if ($entity->has('priority') && !empty($entity->priority)) {
            $entity->priority = $this->needPrioritiesOptions[$entity->priority];
        }

        return $entity;
    }
}
