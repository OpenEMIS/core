<?php
namespace Institution\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class InfrastructureWashSewagesController extends PageController
{
    private $academicPeriodOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->Page->loadElementsFromTable($this->InfrastructureWashSewages);
        $this->Page->disable(['search']); // to disable the search function
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $this->getInstitutionID();
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Institutions',
            'index']);
        $page->addCrumb($institutionName, [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'dashboard',
            'institutionId' => $encodedInstitutionId,
            $encodedInstitutionId]);
        $page->addCrumb(__('Sewage'));

        // set institution_id
        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);

        // set header
        $page->setHeader($institutionName . ' - ' . __('Sewage'));

        // set options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();

        // set fields
        $page->get('infrastructure_wash_sewage_type_id')->setLabel('Type');
        $page->get('infrastructure_wash_sewage_functionality_id')->setLabel('Functionality');

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
    }

    public function index()
    {
        $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureWashSewages->aliasField('created') => 'DESC']);

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
        $page->get('infrastructure_wash_sewage_type_id')
            ->setControlType('select');

        // set functionality
        $page->get('infrastructure_wash_sewage_functionality_id')
            ->setControlType('select');
    }

    private function getInstitutionID()
    {
        $session = $this->request->session();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->request->params['institutionId']) ?
            $this->request->params['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }
}
