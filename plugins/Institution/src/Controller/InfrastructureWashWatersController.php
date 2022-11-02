<?php
namespace Institution\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class InfrastructureWashWatersController extends PageController
{
    private $academicPeriodOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('AcademicPeriod.AcademicPeriods');

        // to disable actions if institution is not active
        $this->loadComponent('Institution.InstitutionInactive');

        $this->Page->disable(['search']); // to disable the search function
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $requestQuery = $this->request->query;
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb(__('Water'));

        // set institution_id
        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);

        // set header
        $page->setHeader($institutionName . ' - ' . __('Water'));

        // set options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();

        // set fields
        $page->get('infrastructure_wash_water_type_id')->setLabel('Type');
        $page->get('infrastructure_wash_water_functionality_id')->setLabel('Functionality');
        $page->get('infrastructure_wash_water_proximity_id')->setLabel('Proximity');
        $page->get('infrastructure_wash_water_quantity_id')->setLabel('Quantity');
        $page->get('infrastructure_wash_water_quality_id')->setLabel('Quality');
        $page->get('infrastructure_wash_water_accessibility_id')->setLabel('Accessibility');

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
    }

    public function index()
    {
        $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureWashWaters->aliasField('created') => 'DESC']);

        // set field
        $page->exclude(['academic_period_id', 'institution_id']);

        // set filter academic period
        $page->addFilter('academic_period_id')
            ->setOptions($this->academicPeriodOptions);

        // set queryString
        $academicPeriodId = !empty($requestQuery['querystring']) ? $this->Page->decode($requestQuery['querystring']): $this->AcademicPeriods->getCurrent();
        $page->setQueryString('academic_period_id', $academicPeriodId);

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

    private function addEdit()
    {
        $page = $this->Page;

        // set academic
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions);

        // set type
        $page->get('infrastructure_wash_water_type_id')
            ->setControlType('select');

        // set functionality
        $page->get('infrastructure_wash_water_functionality_id')
            ->setControlType('select');

        // set proximity
        $page->get('infrastructure_wash_water_proximity_id')
            ->setControlType('select');

        // set quantity
        $page->get('infrastructure_wash_water_quantity_id')
            ->setControlType('select');

        // set quality
        $page->get('infrastructure_wash_water_quality_id')
            ->setControlType('select');

        // set accessibility
        $page->get('infrastructure_wash_water_accessibility_id')
            ->setControlType('select');
    }
}
