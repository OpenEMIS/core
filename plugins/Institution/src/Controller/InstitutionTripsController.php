<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class InstitutionTripsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Transport.TripTypes');
        $this->loadModel('Institution.InstitutionTransportProviders');
        $this->loadModel('Institution.InstitutionBuses');

        $this->Page->loadElementsFromTable($this->InstitutionTrips);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderDays'] = 'onRenderDays';
        return $event;
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
        $page->addCrumb('Trips');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Trips'));

        // to filter by institution_id
        $page->setQueryString('institution_id', $institutionId);

        // to rename field label
        $page->get('institution_transport_provider_id')
            ->setLabel('Transport Provider');
        $page->get('institution_bus_id')
            ->setLabel('Bus')
            ->setDisplayFrom('institution_bus.plate_number');

        // set institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);

        $repeatOptions = [1 => __('Yes'), 0 => __('No')];
        $page->get('repeat')
            ->setControlType('select')
            ->setOptions($repeatOptions, false);
    }

	public function index()
    {
        parent::index();

        $page = $this->Page;
        $page->exclude(['comment', 'institution_id']);

        // Academic Periods
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $page->addFilter('academic_period_id')
            ->setOptions($academicPeriodOptions);

        // to filter by academic_period_id
        $academicPeriodId = !is_null($page->getQueryString('academic_period_id')) ? $page->getQueryString('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $page->setQueryString('academic_period_id', $academicPeriodId);
        // end Academic Periods

        // Trip Types
        $tripTypes = $this->TripTypes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $tripTypeOptions = [null => __('All Trip Types')] + $tripTypes;
        $page->addFilter('trip_type_id')
            ->setOptions($tripTypeOptions);
        // end Trip Types

        $page->addNew('days')
            ->setControlType('select')
            ->setAttributes('multiple', true);

        // reorder fields
        $page->move('academic_period_id')->first();
        $page->move('repeat')->after('institution_bus_id');
        $page->move('days')->after('repeat');
        // end reorder fields
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;

        $page->addNew('information')
            ->setControlType('section');

        $page->addNew('capacity')
            ->setControlType('integer');

        $page->addNew('days')
            ->setControlType('select')
            ->setAttributes('multiple', true);

        $page->addNew('passengers')
            ->setControlType('section');

        $this->reorderFields();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit($id);
    }

    public function onRenderDays(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            if ($entity->has('institution_trip_days')) {
                $dayOptions = $this->AcademicPeriods->getWorkingDaysOfWeek();
                $list = [];
                foreach ($entity->institution_trip_days as $obj) {
                    $list[$obj->day] = $dayOptions[$obj->day];
                }

                $value = implode(", ", $list);
                return $value;
            }
        }
    }

    private function addEdit($id=0)
    {
        $page = $this->Page;

        $institutionId = $page->getQueryString('institution_id');

        $page->addNew('information')
            ->setControlType('section');

        // Academic Period
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($academicPeriodOptions, false);
        // end Academic Period

        $page->get('trip_type_id')
            ->setControlType('select');

        $page->get('institution_transport_provider_id')
            ->setId('institution_transport_provider_id')
            ->setControlType('select');

        $page->get('institution_bus_id')
            ->setControlType('select')
            ->setOptions(false)
            ->setDependentOn('institution_transport_provider_id')
            ->setParams('InstitutionBuses');

        $page->addNew('capacity')
            ->setControlType('integer')
            ->setDisabled(true);

        $dayOptions = $this->AcademicPeriods->getWorkingDaysOfWeek();
        $page->addNew('days')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Days'))
            ->setOptions($dayOptions, false);

        $page->addNew('passengers')
            ->setControlType('section');

        // set days to entity
        $entity = $page->getData();

        $days = [];
        if ($entity->has('institution_trip_days')) {
            foreach ($entity->institution_trip_days as $tripDayEntity) {
                $tripDayEntity->id = $tripDayEntity->day;

                $days[] = $tripDayEntity;
            }
        }

        $entity->days = $days;
        // end set days to entity

        $this->reorderFields();
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('information')->first();
        $page->move('academic_period_id')->after('information');
        $page->move('capacity')->after('institution_bus_id');
        $page->move('repeat')->after('capacity');
        $page->move('days')->after('repeat');
        $page->move('comment')->after('days');
        $page->move('passengers')->after('comment');
    }
}
