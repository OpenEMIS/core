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
        $this->loadModel('Education.EducationGrades');
        $this->loadModel('Institution.InstitutionClasses');

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

        $entity = $page->getData();

        $page->addNew('information')
            ->setControlType('section');

        $page->addNew('capacity')
            ->setControlType('integer');

        $page->addNew('days')
            ->setControlType('select')
            ->setAttributes('multiple', true);

        $page->addNew('passengers')
            ->setControlType('section');

        $assignedStudents = $this->getAssignedStudents($entity);
        $page->addNew('assigned_students')
            ->setControlType('table')
            ->setAttributes('column', [
                ['label' => __('OpenEMIS ID'), 'key' => 'openemis_no'],
                ['label' => __('Student'), 'key' => 'student'],
                ['label' => __('Education Grade'), 'key' => 'education_grade'],
                ['label' => __('Class'), 'key' => 'class']
            ])
            ->setAttributes('row', $assignedStudents);

        $this->reorderFields();
        $page->move('assigned_students')->after('passengers');
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

        $entity = $page->getData();

        $institutionId = $page->getQueryString('institution_id');

        $page->addNew('information')
            ->setControlType('section');

        // Academic Period
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $page->get('academic_period_id')
            ->setId('academic_period_id')
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

        $page->addNew('education_grade_id')
            ->setId('education_grade_id')
            ->setLabel('Education Grade')
            ->setControlType('select')
            ->setDependentOn('academic_period_id')
            ->setParams('EducationGrades');

        $page->addNew('institution_class_id')
            ->setLabel('Class')
            ->setControlType('select')
            ->setDependentOn(['academic_period_id', 'education_grade_id'])
            ->setParams('InstitutionClasses');

        $assignedStudents = [];
        $page->addNew('assigned_students')
            ->setControlType('table')
            ->setAttributes('column', [
                ['label' => __('OpenEMIS ID'), 'key' => 'openemis_no'],
                ['label' => __('Student'), 'key' => 'student'],
                ['label' => __('Education Grade'), 'key' => 'education_grade'],
                ['label' => __('Class'), 'key' => 'class']
            ])
            ->setAttributes('row', $assignedStudents);

        $this->setBusOptions($entity);
        $this->setDaysValue($entity);

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

    private function setBusOptions(Entity $entity)
    {
        $page = $this->Page;

        // show empty list when add and show bus option list filter by transport provider when edit
        if ($entity->isNew()) {
            $page->get('institution_bus_id')
                ->setOptions(false);
        } else {
            if ($entity->has('institution_transport_provider_id')) {
                $busOptions = $this->InstitutionBuses
                    ->find('optionList')
                    ->where([
                        $this->InstitutionBuses->aliasField('institution_transport_provider_id') => $entity->institution_transport_provider_id
                    ])
                    ->toArray();
            } else {
                $busOptions = [];
            }

            $page->get('institution_bus_id')
                ->setOptions($busOptions);
        }
    }

    private function setDaysValue(Entity $entity)
    {
        $days = [];

        if ($entity->has('institution_trip_days')) {
            foreach ($entity->institution_trip_days as $obj) {
                $obj->id = $obj->day;
                $days[] = $obj;
            }
        }

        $entity->days = $days;
    }

    private function getAssignedStudents(Entity $entity)
    {
        $students = [];

        if ($entity->has('institution_trip_passengers')) {
            foreach ($entity->institution_trip_passengers as $obj) {
                $students[] = [
                    'openemis_no' => $obj->student->openemis_no,
                    'student' => $obj->student->name,
                    'education_grade' => $obj->education_grade->name,
                    'class' => $obj->institution_class->name
                ];
            }
        }

        return $students;
    }
}
