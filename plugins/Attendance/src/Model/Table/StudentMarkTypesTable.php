<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class StudentMarkTypesTable extends ControllerActionTable
{
    private $defaultMarkType;

    public function initialize(array $config)
    {
        $this->table('education_grades');
        parent::initialize($config);

        $this->hasMany('StudentAttendanceMarkTypes', ['className' => 'Attendance.StudentAttendanceMarkTypes', 'foreignKey' => 'education_grade_id']);

        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->toggle('reorder', false);

        $this->removeBehavior('Reorder');

        $this->defaultMarkType = $this->StudentAttendanceMarkTypes->getDefaultMarkType();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        // Academic period filter
        $academicPeriodOptions =  $AcademicPeriods->getYearList();
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();

        // Education programme filter
        $educationProgrammeOptions = $EducationProgrammes
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'cycle_programme_name'
            ])
            ->find('availableProgrammes')
            ->toArray();

        if (isset($this->request->query) && array_key_exists('programme', $this->request->query)) {
            $selectedEducationProgramme = $this->request->query['programme'];
        } else {
            $defaultProgramme = key($educationProgrammeOptions);
            $this->request->query['programme'] = $defaultProgramme;
            $selectedEducationProgramme = $defaultProgramme;
        }

        $extra['elements']['control'] = [
            'name' => 'Attendance./controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $selectedAcademicPeriod,
                'programmeOptions'=> $educationProgrammeOptions,
                'selectedProgrammeOptions'=> $selectedEducationProgramme
            ],
            'order' => 1
        ];
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        if (!is_null($requestData[$this->alias()]['student_attendance_type_id']) &&
            !is_null($requestData[$this->alias()]['attendance_per_day']) &&
            !is_null($requestData[$this->alias()]['id']) &&
            !is_null($requestData[$this->alias()]['academic_period_id'])
        ) {
            $educationGradeId = $requestData[$this->alias()]['id'];
            $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
            $attendancePerDay = $requestData[$this->alias()]['attendance_per_day'];
            $attendanceTypeId = $requestData[$this->alias()]['student_attendance_type_id'];

            if ($this->StudentAttendanceMarkTypes->isDefaultType($attendancePerDay, $attendanceTypeId)) {
                $resultSet = $this->StudentAttendanceMarkTypes
                    ->find()
                    ->where([
                        $this->StudentAttendanceMarkTypes->aliasField('academic_period_id') => $academicPeriodId,
                        $this->StudentAttendanceMarkTypes->aliasField('education_grade_id') => $educationGradeId
                    ])
                    ->all();

                if (!$resultSet->isEmpty()) {
                    // delete any records if set attendance type back to default
                    $entity = $resultSet->first();
                    $this->StudentAttendanceMarkTypes->delete($entity);
                }
            } else {
                $studentMarkTypeData = [
                    'student_attendance_type_id' => $attendanceTypeId,
                    'attendance_per_day' => $attendancePerDay,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId
                ];

                $entity = $this->StudentAttendanceMarkTypes->newEntity($studentMarkTypeData);
                $this->StudentAttendanceMarkTypes->save($entity);
            }
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupField();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupField($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $entity->academic_period_id = $selectedAcademicPeriod;
        $this->setupField($entity);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $selectedEducationProgramme = $this->request->query('programme');
        
        $query
            ->contain([
                'StudentAttendanceMarkTypes' => function ($q) use ($selectedAcademicPeriod) {
                    return $q->where(['StudentAttendanceMarkTypes.academic_period_id' => $selectedAcademicPeriod]);
                }
            ])
            ->where([
                $this->aliasField('education_programme_id') => $selectedEducationProgramme,
            ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $query
            ->contain([
                'StudentAttendanceMarkTypes' => function ($q) use ($selectedAcademicPeriod) {
                    return $q->where(['StudentAttendanceMarkTypes.academic_period_id' => $selectedAcademicPeriod]);
                }
            ]);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();

        $query
            ->contain([
                'StudentAttendanceMarkTypes' => function ($q) use ($selectedAcademicPeriod) {
                    return $q->where(['StudentAttendanceMarkTypes.academic_period_id' => $selectedAcademicPeriod]);
                }
            ]);
    }

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
            $periodEntity = $AcademicPeriods
                ->find()
                ->select([$AcademicPeriods->aliasField('name')])
                ->where([$AcademicPeriods->aliasField('id') => $selectedAcademicPeriod])
                ->first();

            if (!is_null($periodEntity->name)) {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $periodEntity->name;
                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldStudentAttendanceTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            if (!empty($entity->student_attendance_mark_types)) {
                $attendanceTypeEntity = $entity->student_attendance_mark_types[0];
                $markTypeId = $attendanceTypeEntity->student_attendance_type_id;
            } else {
                $markTypeId = $this->defaultMarkType['student_attendance_type_id'];
            }

            $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
            $attendanceOptions = $StudentAttendanceTypes
                ->find('list')
                ->toArray();

            $attr['type'] = 'readonly';
            $attr['value'] = $markTypeId;
            $attr['attr']['value'] = $attendanceOptions[$markTypeId];
           
            return $attr;
        }
    }

    public function onUpdateFieldAttendancePerDay(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            if (!empty($entity->student_attendance_mark_types)) {
                $attendanceTypeEntity = $entity->student_attendance_mark_types[0];
                $attendancePerDay = $attendanceTypeEntity->attendance_per_day;
            } else {
                $attendancePerDay = $this->defaultMarkType['attendance_per_day'];
            }

            $attr['type'] = 'select';
            $attr['options'] = $this->StudentAttendanceMarkTypes->getAttendancePerDayOptions();
            $attr['select'] = false;
            $attr['value'] = $attendancePerDay;
            $attr['attr']['value'] = $attendancePerDay;

            return $attr;
        }
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $periodEntity = $AcademicPeriods
            ->find()
            ->select([$AcademicPeriods->aliasField('name')])
            ->where([$AcademicPeriods->aliasField('id') => $selectedAcademicPeriod])
            ->first();

        if (!is_null($periodEntity)) {
            return $periodEntity->name;
        }
    }

    public function onGetStudentAttendanceTypeId(Event $event, Entity $entity)
    {
        if (!empty($entity->student_attendance_mark_types)) {
            $attendanceTypeEntity = $entity->student_attendance_mark_types[0];
            $attendanceTypeId = $attendanceTypeEntity->student_attendance_type_id;
        } else {
            $attendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
        }

        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $markTypeEntity = $StudentAttendanceTypes
            ->find()
            ->select([$StudentAttendanceTypes->aliasField('name')])
            ->where([$StudentAttendanceTypes->aliasField('id') => $attendanceTypeId])
            ->first();

        if (!is_null($markTypeEntity)) {
            return $markTypeEntity->name;
        }
    }

    public function onGetAttendancePerDay(Event $event, Entity $entity)
    {
        if (!empty($entity->student_attendance_mark_types)) {
            $attendanceTypeEntity = $entity->student_attendance_mark_types[0];
            return $attendanceTypeEntity->attendance_per_day;
        } else {
            return $this->defaultMarkType['attendance_per_day'];
        }
    }

    private function setupField(Entity $entity = null)
    {
        $this->field('visible', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('admission_age', ['visible' => false]);
        $this->field('education_stage_id', ['visible' => false]);
        $this->field('education_programme_id', ['visible' => false]);
        $this->field('name', ['attr' => ['label' => __('Education Grade')]]);

        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);

        $this->field('attendance_per_day', ['entity' => $entity, 'attr' => ['required' => true]]);
        $this->field('student_attendance_type_id', ['entity' => $entity, 'attr' => ['label' => __('Type'), 'required' => true]]);
        $this->field('academic_period_id', ['visible' => [
            'index' => false, 'view' => true, 'edit' => true
        ]]);
        
        $this->setFieldOrder(['academic_period_id', 'name', 'student_attendance_type_id', 'attendance_per_day']);
    }

    private function getSelectedAcademicPeriod()
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        if (isset($this->request->query) && array_key_exists('period', $this->request->query)) {
            $selectedAcademicPeriod = $this->request->query['period'];
        } else {
            $current = $AcademicPeriods->getCurrent();
            $this->request->query['period'] = $current;
            $selectedAcademicPeriod = $current;
        }

        return $selectedAcademicPeriod;
    }
}
