<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use Cake\ORM\TableRegistry;

class InstitutionTripsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Transport.TripTypes');
        $this->loadModel('Institution.InstitutionTransportProviders');
        $this->loadModel('Institution.InstitutionBuses');
        $this->loadModel('Institution.Students');
        $this->loadModel('Student.StudentStatuses');

        // to disable actions if institution is not active
        $this->loadComponent('Institution.InstitutionInactive');
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
            ->setLabel('Provider');
        $page->get('institution_bus_id')
            ->setLabel('Bus')
            ->setDisplayFrom('institution_bus.plate_number');

        // set institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);

        $repeatOptions = [
            1 => __('Yes'),
            0 => __('No')
        ];
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
        $Users = TableRegistry::get('labels');
        $result = $Users
            ->find()
            ->where(['module' => 'InstitutionTrips', 'field_name' => 'Repeat'])
            ->toArray();
        if(isset($result[0]['name'])){
            $page->get('repeat')->setSortable(false)->setLabel($result[0]['name']);
        }else{
            $page->move('repeat')->after('institution_bus_id');
        }
        $page->move('repeat')->after('institution_bus_id');
        $page->move('days')->after('repeat');
        $result = $Users
            ->find()
            ->where(['module' => 'InstitutionTrips', 'field_name' => 'Trip Type'])
            ->toArray();
        if(isset($result[0]['name'])){
            $page->get('trip_type_id')->setSortable(false)->setLabel($result[0]['name']);
        }else{
            $page->move('trip_type_id')->after('name');
        }
        $result = $Users
            ->find()
            ->where(['module' => 'InstitutionTrips', 'field_name' => 'Bus'])
            ->toArray();
        if(isset($result[0]['name'])){
            $page->get('institution_bus_id')->setSortable(false)->setLabel($result[0]['name']);
        }else{
            $page->move('repeat')->after('institution_bus_id');
        }
        // end reorder fields
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;

        $entity = $page->getData();

        $page->addNew('information')
            ->setControlType('section');

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
                ['label' => __('Status'), 'key' => 'status']
            ])
            ->setAttributes('row', $assignedStudents);

        $this->reorderFields();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
        $page = $this->Page;

        if ($this->request->is(['get'])) {
            // set default academic period to current year
            $academicPeriodId = !is_null($page->getQueryString('academic_period_id')) ? $page->getQueryString('academic_period_id') : $this->AcademicPeriods->getCurrent();
            $page->get('academic_period_id')->setValue($academicPeriodId);
        }
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
                $dayOptions = $this->InstitutionTrips->getDays();
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

        $page->get('trip_type_id')
            ->setControlType('select');

        $page->get('institution_transport_provider_id')
            ->setId('institution_transport_provider_id')
            ->setControlType('select');

        $page->get('institution_bus_id')
            ->setControlType('select')
            ->setDependentOn('institution_transport_provider_id')
            ->setParams('InstitutionBuses');

        $dayOptions = $this->InstitutionTrips->getDays();
        $page->addNew('days')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Days'))
            ->setRequired(true)
            ->setOptions($dayOptions, false);

        $this->setBusOptions($entity);
        $this->setDaysValue($entity);

        if ($entity->isNew()) {
            // Academic Period
            $academicPeriodOptions = $this->AcademicPeriods->getYearList();
            $page->get('academic_period_id')
                ->setControlType('select')
                ->setOptions($academicPeriodOptions, false);
            // end Academic Period

            // reorder fields
            $page->move('academic_period_id')->first();
            $page->move('repeat')->after('institution_bus_id');
            $page->move('days')->after('repeat');
            $page->move('comment')->after('days');
            // end reorder fields
        } else {
            $page->addNew('information')
                ->setControlType('section');

            $page->get('academic_period_id')
                ->setDisabled(true);

            $page->addNew('passengers')
                ->setControlType('section');

            $institutionId = $entity->institution_id;
            $academicPeriodId = $entity->academic_period_id;
            $enrolledStatus = $this->StudentStatuses->getIdByCode('CURRENT');

            $studentOptions = $this->Students
                ->find()
                ->select([
                    $this->Students->aliasField('id'),
                    $this->Students->aliasField('student_id'),
                    $this->Students->aliasField('education_grade_id'),
                    $this->Students->aliasField('academic_period_id'),
                    $this->Students->aliasField('institution_id'),
                    $this->Students->Users->aliasField('openemis_no'),
                    $this->Students->Users->aliasField('first_name'),
                    $this->Students->Users->aliasField('middle_name'),
                    $this->Students->Users->aliasField('third_name'),
                    $this->Students->Users->aliasField('last_name'),
                    $this->Students->Users->aliasField('preferred_name')
                ])
                ->contain([$this->Students->Users->alias()])
                ->where([
                    $this->Students->aliasField('institution_id') => $institutionId,
                    $this->Students->aliasField('academic_period_id') => $academicPeriodId,
                    $this->Students->aliasField('student_status_id') => $enrolledStatus
                ])
                ->group([
                    $this->Students->aliasField('student_id')
                ])
                ->order([
                    $this->Students->Users->aliasField('first_name'),
                    $this->Students->Users->aliasField('last_name')
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    $returnResult = [];

                    foreach ($results as $result) {
                        $encodedKeys = $this->paramsEncode([
                            'student_id' => $result->student_id,
                            'education_grade_id' => $result->education_grade_id,
                            'academic_period_id' => $result->academic_period_id,
                            'institution_id' => $result->institution_id
                        ]);

                        $returnResult[] = [
                            'value' => $encodedKeys,
                            'text' => $result->user->name_with_id
                        ];
                    }

                    return $returnResult;
                })
                ->toArray();

            $page->addNew('assigned_students')
                ->setControlType('select')
                ->setAttributes('multiple', true)
                ->setAttributes('placeholder', __('Select Students'))
                ->setOptions($studentOptions, false);

            $this->setAssignedStudentsValue($entity);

            $this->reorderFields();
        }
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('information')->first();
        $page->move('academic_period_id')->after('information');
        $page->move('repeat')->after('institution_bus_id');
        $page->move('days')->after('repeat');
        $page->move('comment')->after('days');
        $page->move('passengers')->after('comment');
        $page->move('assigned_students')->after('passengers');
    }

    private function setBusOptions(Entity $entity)
    {
        $page = $this->Page;

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

    private function setDaysValue(Entity $entity)
    {
        if ($this->request->is(['get'])) {
            $days = [];

            if ($entity->has('institution_trip_days')) {
                foreach ($entity->institution_trip_days as $obj) {
                    $obj->id = $obj->day;
                    $days[] = $obj;
                }
            }

            $entity->days = $days;
        }
    }

    private function setAssignedStudentsValue(Entity $entity)
    {
        if ($this->request->is(['get'])) {
            $assignedStudents = [];

            if ($entity->has('institution_trip_passengers')) {
                foreach ($entity->institution_trip_passengers as $obj) {
                    $encodedKeys = $this->paramsEncode([
                        'student_id' => $obj->student_id,
                        'education_grade_id' => $obj->education_grade_id,
                        'academic_period_id' => $obj->academic_period_id,
                        'institution_id' => $obj->institution_id
                    ]);

                    $obj->id = $encodedKeys;

                    $assignedStudents[] = $obj;
                }
            }

            $entity->assigned_students = $assignedStudents;
        }
    }

    private function getAssignedStudents(Entity $entity)
    {
        $students = [];

        if ($entity->has('institution_trip_passengers')) {
            foreach ($entity->institution_trip_passengers as $obj) {
                $institutionStudentEntity = $this->Students
                    ->find()
                    ->select([
                        $this->Students->Users->aliasField('openemis_no'),
                        $this->Students->Users->aliasField('first_name'),
                        $this->Students->Users->aliasField('middle_name'),
                        $this->Students->Users->aliasField('third_name'),
                        $this->Students->Users->aliasField('last_name'),
                        $this->Students->Users->aliasField('preferred_name'),
                        $this->Students->EducationGrades->aliasField('code'),
                        $this->Students->EducationGrades->aliasField('name'),
                        $this->Students->EducationGrades->aliasField('education_programme_id'),
                        $this->Students->StudentStatuses->aliasField('name')
                    ])
                    ->contain(['Users', 'EducationGrades', 'StudentStatuses'])
                    ->where([
                        'student_id' => $obj->student_id,
                        'education_grade_id' => $obj->education_grade_id,
                        'academic_period_id' => $obj->academic_period_id,
                        'institution_id' => $obj->institution_id
                    ])
                    ->first();

                if (!empty($institutionStudentEntity)) {
                    $students[] = [
                        'openemis_no' => $institutionStudentEntity->Users->openemis_no,
                        'student' => $institutionStudentEntity->Users->name,
                        'education_grade' => $institutionStudentEntity->EducationGrades->name,
                        'status' => $institutionStudentEntity->StudentStatuses->name
                    ];
                }
            }
        }

        return $students;
    }
}
