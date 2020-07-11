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
        //echo "<pre>";print_r($requestData[$this->alias()]);die;
        if (!is_null($requestData[$this->alias()]['student_attendance_type_id']) &&
            !is_null($requestData[$this->alias()]['attendance_per_day']) &&
            !is_null($requestData[$this->alias()]['id']) &&
            !is_null($requestData[$this->alias()]['academic_period_id'])
        ) {
            $educationGradeId = $requestData[$this->alias()]['id'];
            $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
            $attendancePerDay = $requestData[$this->alias()]['attendance_per_day'];
            $attendanceTypeId = $requestData[$this->alias()]['student_attendance_type_id'];
           // echo $this->StudentAttendanceMarkTypes->isDefaultType($attendancePerDay, $attendanceTypeId);die;
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
                //echo "asdkjd";die;
                $studentMarkTypeData = [
                    'student_attendance_type_id' => $attendanceTypeId,
                    'attendance_per_day' => $attendancePerDay,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId
                ];

                $entity = $this->StudentAttendanceMarkTypes->newEntity($studentMarkTypeData);
                $this->StudentAttendanceMarkTypes->save($entity);
            }

            $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
            for ($i=1;$i<=$attendancePerDay;$i++) {

               
                $id = $requestData['p'.$i];
                
                $PeriodsData = $StudentAttendancePerDayPeriods
                ->find('all')
                ->where([
                    $StudentAttendancePerDayPeriods->aliasField('id = ') => $id
                ])
                ->all()
                ->toArray();
                if (!empty($PeriodsData)) {

                    $StudentAttendancePerDayPeriods->updateAll(
                        ['name' => $requestData['period'][$i]],
                    [
                        'id' => $id
                    ]
                    );
                } else {
                    
                   $StudentAttendancePerDayPeriodsData = [
                        'academic_period_id' => $requestData[$this->alias()]['academic_period_id'],
                        'education_grade_id' => $requestData[$this->alias()]['id'],
                        'name' => $requestData['period'][$i]
                    ];
                    $entity1 = $StudentAttendancePerDayPeriods->newEntity($StudentAttendancePerDayPeriodsData);
                    $StudentAttendancePerDayPeriods->save($entity1);
                }
            }
        } else {

            $educationGradeId = $requestData[$this->alias()]['id'];
            $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
            
            $attendanceTypeId = $requestData[$this->alias()]['student_attendance_type_id'];
            $resultSet = $this->StudentAttendanceMarkTypes
                    ->find()
                    ->where([
                        $this->StudentAttendanceMarkTypes->aliasField('academic_period_id') => $academicPeriodId,
                        $this->StudentAttendanceMarkTypes->aliasField('education_grade_id') => $educationGradeId
                    ])
                    ->toArray();
            if (!empty($resultSet)) {
                /*echo "<prE>";
                print_r($this->StudentAttendanceMarkTypes);die;*/
                $this->StudentAttendanceMarkTypes
                ->updateAll(['student_attendance_type_id' => $attendanceTypeId], ['education_grade_id' => $requestData[$this->alias()]['id'], 'academic_period_id' => $requestData[$this->alias()]['academic_period_id']]);
            }
                    
                  }

                /*if (!$resultSet->isEmpty()) {
                    // delete any records if set attendance type back to default
                    $entity = $resultSet->first();
                    $this->StudentAttendanceMarkTypes->delete($entity);
                }*/

        
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupField();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $entity->academic_period_id = $selectedAcademicPeriod;
        $education_grade_id = $entity->id;
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('academic_period_id = ') => $selectedAcademicPeriod,
            $StudentAttendancePerDayPeriods->aliasField('education_grade_id = ') => $education_grade_id
        ])
        ->all()
        ->toArray();
        if (!empty($entity->student_attendance_mark_types[0]->attendance_per_day)) {
            $attendance_per_day = $entity->student_attendance_mark_types[0]->attendance_per_day;
        } else {
            $attendance_per_day = 1;
        }
        $this->controller->set('StudentAttendancePerDayPeriodsData', $StudentAttendancePerDayPeriodsData);
        $this->controller->set('attendance_per_day', $attendance_per_day);
        $this->setupField($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        //echo "<pre>";print_r($this->fields);die;
        $student_attendance_type_id = $entity->student_attendance_mark_types[0]->student_attendance_type_id;
        $entity->student_attendance_type_id = $student_attendance_type_id;
        $selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $entity->academic_period_id = $selectedAcademicPeriod;
        $education_grade_id = $entity->id;

        /*if ($student_attendance_type_id == 2) {
            $this->fields['attendance_per_day']['visible'] = false;
            $this->fields['periods']['visible'] = false;
        } else {
            /*$this->fields['attendance_per_day']['visible'] = true;
            $this->fields['periods']['visible'] = true;*/
        //////}*/
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('academic_period_id = ') => $selectedAcademicPeriod,
            $StudentAttendancePerDayPeriods->aliasField('education_grade_id = ') => $education_grade_id
        ])
        ->all()
        ->toArray();
        if (!empty($entity->attendance_per_day)) {
                $attendance_per_day = $entity->attendance_per_day;
        } else if (!empty($entity->student_attendance_mark_types[0]->attendance_per_day) && empty($entity->attendance_per_day)) {
            $attendance_per_day = $entity->student_attendance_mark_types[0]->attendance_per_day;
        } else {
            $attendance_per_day = 1;
        }
        $this->controller->set('StudentAttendancePerDayPeriodsData', $StudentAttendancePerDayPeriodsData);
        $this->controller->set('attendance_per_day', $attendance_per_day);
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
            //$attr['type'] = 'readonly';
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
                ->where([$AcademicPeriods->aliasField('id') => $selectedAcademicPeriod])
                ->first();
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions =  $AcademicPeriods->getYearList();

            //$attr['type'] = 'select';
            $attr['options'] = $academicPeriodOptions;
            //echo "<prE>";print_r($attr['attr']);die;
            if (!is_null($periodEntity->name)) {
                //echo "asldnl";die;
               // $attr['type'] = 'readonly';
                $attr['attr']['value'] = $periodEntity->id;
                $attr['attr']['required'] = true;
            }
            //echo "<prE>";print_r($attr);die;
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
                //echo "<pre>";
                //var_dump($attendanceOptions);die;

            //$attr['type'] = 'readonly';
            $attr['type'] = 'select';
            $attr['attr']['options'] = $attendanceOptions;
          // echo "<pre>";print_r($attr);die;
            $attr['value'] = $markTypeId;
            $attr['onChangeReload'] = 'ChangeAttendanceType';
            //$attr['attr']['value'] = $attendanceOptions[$markTypeId];
           
            return $attr;
        }
    }

    public function onUpdateFieldAttendancePerDay(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            if (!empty($entity->attendance_per_day)) {
               $attendancePerDay = $entity->attendance_per_day; 
            } else if (!empty($entity->student_attendance_mark_types) && empty($entity->attendance_per_day)) {
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
            $attr['onChangeReload'] = 'ChangeAttendancePerDay';
            

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

        $this->field('attendance_per_day', ['type' => 'select','entity' => $entity]);
        $this->field('student_attendance_type_id', ['attr' => ['label' => __('Type'), 'required' => true,'entity' => $entity]]);
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity
        ]);
        
        
        if ($this->action == 'edit' || $this->action == 'view') {
        $this->field('periods', [
                        'type' => 'element',
                        'element' => 'Attendance.periods',
                        
                    ]);
        }
        $this->setFieldOrder(['academic_period_id', 'name', 'student_attendance_type_id', 'attendance_per_day']);
       // echo "<pre>";print_r($this->fields);die;
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

    public function addEditOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function addEditOnChangeAttendancePerDay(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {

        $request = $this->request;

        $attendance_per_day = $request->data[$this->alias()]['attendance_per_day'];
        $this->controller->set('attendance_per_day', $attendance_per_day);
    }

    public function addEditOnChangeAttendanceType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
      ////  print_r($data[$this->alias()]['student_attendance_type_id']);die;
        if ($data[$this->alias()]['student_attendance_type_id']==2) {
            $this->fields['attendance_per_day']['visible'] = false;
            $this->fields['periods']['visible'] = false;
        } else {
            // //echo "hello";exit();
            $this->fields['attendance_per_day']['visible'] = true;
            $this->fields['periods']['visible'] = true;
        }
    }
}
