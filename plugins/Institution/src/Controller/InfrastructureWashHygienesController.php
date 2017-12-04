<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Controller\PageController;

class InfrastructureWashHygienesController extends PageController
{
    private $academicPeriodOptions = [];

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('AcademicPeriod.AcademicPeriods');        
        $this->Page->loadElementsFromTable($this->InfrastructureWashHygienes);        
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
        $page->addCrumb(__('Hygiene'));

        // set institution_id
        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);

        // set header
        $page->setHeader($institutionName . ' - ' . __('Hygiene'));

        // set options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();

        // set fields
        $page->get('infrastructure_wash_hygiene_type_id')->setLabel('Type');
        $page->get('infrastructure_wash_hygiene_soapash_availability_id')->setLabel('Soap/Ash Availability');
        $page->get('infrastructure_wash_hygiene_education_id')->setLabel('Hygiene Education');
        // $page->get('infrastructure_wash_hygiene_total_male')->setLabel('Total Male');
        // $page->get('infrastructure_wash_hygiene_total_female')->setLabel('Total Female');
        // $page->get('infrastructure_wash_hygiene_total_mixed')->setLabel('Total Mixed');
        $page->get('infrastructure_wash_hygiene_male_functional')->setLabel('Male (Functional)');
        $page->get('infrastructure_wash_hygiene_male_nonfunctional')->setLabel('Male (Non-functional)');
        $page->get('infrastructure_wash_hygiene_female_functional')->setLabel('Female (Functional)');
        $page->get('infrastructure_wash_hygiene_female_nonfunctional')->setLabel('Female (Non-functional)');
        $page->get('infrastructure_wash_hygiene_mixed_functional')->setLabel('Mixed (Functional)');
        $page->get('infrastructure_wash_hygiene_mixed_nonfunctional')->setLabel('Mixed (Non-functional)');
        // set queryString
        $page->setQueryString('institution_id', $institutionId);
    }

    public function index()
    {   $page = $this->Page;

        // set default ordering
        $page->setQueryOption('order', [$this->InfrastructureWashHygienes->aliasField('created') => 'DESC']);

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
    {   $page = $this->Page;

        // set academic
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions);

        // set type
        $page->get('infrastructure_wash_hygiene_type_id')
           ->setControlType('select');

        // set soapash availability
        $page->get('infrastructure_wash_hygiene_soapash_availability_id')
            ->setControlType('select');

        // set hygiene education
        $page->get('infrastructure_wash_hygiene_education_id')
            ->setControlType('select');

        $page->exclude(['infrastructure_wash_hygiene_total_male', 'infrastructure_wash_hygiene_total_female', 'infrastructure_wash_hygiene_total_mixed']);
    }
}
