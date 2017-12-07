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
        $page->get('infrastructure_wash_hygiene_total_male')->setLabel('Total Male');
        $page->get('infrastructure_wash_hygiene_total_female')->setLabel('Total Female');
        $page->get('infrastructure_wash_hygiene_total_mixed')->setLabel('Total Mixed');
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

        $page = $this->Page;
        $page->get('infrastructure_wash_hygiene_male_functional')->setValue(0);
        $page->get('infrastructure_wash_hygiene_male_nonfunctional')->setValue(0);
        $page->get('infrastructure_wash_hygiene_female_functional')->setValue(0);
        $page->get('infrastructure_wash_hygiene_female_nonfunctional')->setValue(0);
        $page->get('infrastructure_wash_hygiene_mixed_functional')->setValue(0);
        $page->get('infrastructure_wash_hygiene_mixed_nonfunctional')->setValue(0);
    }

    public function edit($id)
    {
        $this->addEdit();
        parent::edit($id);
        $page = $this->Page;
        $entity = $page->getData();

        $dataArr = $entity->infrastructure_wash_hygiene_quantities;

        foreach($dataArr as $key => $quantity)
        {
            if ($quantity['gender_id'] == 1 && $quantity['functional'] == 1 ) {
                $page->get('infrastructure_wash_hygiene_male_functional')->setValue($quantity['value']);
            }
            elseif ($quantity['gender_id'] == 1 && $quantity['functional'] == 0 ) {
                $page->get('infrastructure_wash_hygiene_male_nonfunctional')->setValue($quantity['value']);
            }
            elseif ($quantity['gender_id'] == 2 && $quantity['functional'] == 1 ) {
                $page->get('infrastructure_wash_hygiene_female_functional')->setValue($quantity['value']);
            }
            elseif ($quantity['gender_id'] == 2 && $quantity['functional'] == 0 ) {
                $page->get('infrastructure_wash_hygiene_female_nonfunctional')->setValue($quantity['value']);
            }
            elseif ($quantity['gender_id'] == 3 && $quantity['functional'] == 1 ) {
                $page->get('infrastructure_wash_hygiene_mixed_functional')->setValue($quantity['value']);
            }
            elseif ($quantity['gender_id'] == 3 && $quantity['functional'] == 0 ) {
                $page->get('infrastructure_wash_hygiene_mixed_nonfunctional')->setValue($quantity['value']);
            }
        }
    }

    private function addEdit()
    {   
        $page = $this->Page;

        $page->exclude(['infrastructure_wash_hygiene_total_male', 'infrastructure_wash_hygiene_total_female', 'infrastructure_wash_hygiene_total_mixed']);

        // set academic
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions);

        // set type
        $page->get('infrastructure_wash_hygiene_type_id')
           ->setControlType('select');

        // set soap/ash availability
        $page->get('infrastructure_wash_hygiene_soapash_availability_id')
            ->setControlType('select');

        // set hygiene education
        $page->get('infrastructure_wash_hygiene_education_id')
            ->setControlType('select');

        $page->addnew('infrastructure_wash_hygiene_male_functional')
            ->setLabel('Male (Functional)')
            ->setControlType('integer');

        $page->addnew('infrastructure_wash_hygiene_male_nonfunctional')
            ->setLabel('Male (Non-functional)')
            ->setControlType('integer');

        $page->addnew('infrastructure_wash_hygiene_female_functional')
            ->setLabel('Female (Functional)')
            ->setControlType('integer');

        $page->addnew('infrastructure_wash_hygiene_female_nonfunctional')
            ->setLabel('Female (Non-functional)')
            ->setControlType('integer');

        $page->addnew('infrastructure_wash_hygiene_mixed_functional')
            ->setLabel('Mixed (Functional)')
            ->setControlType('integer');

        $page->addnew('infrastructure_wash_hygiene_mixed_nonfunctional')
            ->setLabel('Mixed (Non-functional)')
            ->setControlType('integer');
    }

    public function view($id)
    {
        parent::view($id); 
        $page = $this->Page;
        $entity = $page->getData();
        $quantity = $this->getHygieneQuantity($entity);

        $page->exclude(['infrastructure_wash_hygiene_total_male', 'infrastructure_wash_hygiene_total_female', 'infrastructure_wash_hygiene_total_mixed']);

        $page->addNew('quantities')
             ->setLabel('Quantity')
             ->setControlType('table')
             ->setAttributes('column', [
                 ['label' => __('Gender'), 'key' => 'gender'],
                 ['label' => __('Functional'), 'key' => 'functional'],
                 ['label' => __('Non-functional'), 'key' => 'nonfunctional']
             ])
             ->setAttributes('row', $quantity);
        $page->move('quantities')->after('infrastructure_wash_hygiene_education_id'); 

    }

    private function getHygieneQuantity(Entity $entity)
    {
        $rows = [];
        if ($entity->has('infrastructure_wash_hygiene_quantities')) {
            foreach ($entity->infrastructure_wash_hygiene_quantities as $obj) {

                if ($obj->gender_id == 1 && $obj->functional == 1 ) {
                    $male_functional = $obj->value;
                }
                elseif ($obj->gender_id == 1 && $obj->functional == 0 ) {
                    $male_nonfunctional = $obj->value;
                }
                elseif ($obj->gender_id == 2 && $obj->functional == 1 ) {
                    $female_functional = $obj->value;
                }
                if ($obj->gender_id == 2 && $obj->functional == 0 ) {
                    $female_nonfunctional = $obj->value;
                }
                if ($obj->gender_id == 3 && $obj->functional == 1 ) {
                    $mixed_functional = $obj->value;
                }
                if ($obj->gender_id == 3 && $obj->functional == 0 ) {
                    $mixed_nonfunctional = $obj->value;
                }
            }
        }

        $rows[] = ['gender' => 'Male', 'functional' => $male_functional, 'nonfunctional' => $male_nonfunctional];
        $rows[] = ['gender' => 'Female', 'functional' => $female_functional, 'nonfunctional' => $female_nonfunctional];
        $rows[] = ['gender' => 'Mixed', 'functional' => $mixed_functional, 'nonfunctional' => $mixed_nonfunctional];
        return $rows;
    }
}
