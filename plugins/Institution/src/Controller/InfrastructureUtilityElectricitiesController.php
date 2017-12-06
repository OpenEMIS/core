<?php
namespace Institution\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class InfrastructureUtilityElectricitiesController extends PageController
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
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb(__('Electricity'));

        // set header
        $page->setHeader($institutionName . ' - ' . __('Electricity'));

        // set institution_id
        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);

        // set queryString
        $page->setQueryString('institution_id', $institutionId);

        // set options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();

        // set fields
        $page->get('utility_electricity_type_id')->setLabel('Type');
        $page->get('utility_electricity_condition_id')->setLabel('Condition');

        // set fields order
        $page->move('academic_period_id')->first();
        $page->move('comment')->after('utility_electricity_condition_id');
    }

    public function index()
    {
        $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureUtilityElectricities->aliasField('created') => 'DESC']);

        // set field
        $page->exclude(['comment', 'academic_period_id', 'institution_id']);

        // set filter academic period
        $page->addFilter('academic_period_id')
            ->setOptions($this->academicPeriodOptions);

        // set queryString
        $requestQuery = $this->request->query;
        $queryString = $page->decode($requestQuery['querystring']);
        $academicPeriodId = array_key_exists('academic_period_id', $queryString) ? $queryString['academic_period_id']: $this->AcademicPeriods->getCurrent();
        $page->setQueryString('academic_period_id', $academicPeriodId);

        parent::index();
    }

    public function add()
    {
        $this->addEditElectricity();
        parent::add();
    }

    public function edit($id)
    {
        $this->addEditElectricity();
        parent::edit($id);
    }

    private function addEditElectricity()
    {
        $page = $this->Page;

        // set academic
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions, false);

        // set type
        $page->get('utility_electricity_type_id')
            ->setControlType('select');

        // set condition
        $page->get('utility_electricity_condition_id')
            ->setControlType('select');
    }
}
