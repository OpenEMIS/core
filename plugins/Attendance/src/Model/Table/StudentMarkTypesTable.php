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
        $this->table('student_attendance_mark_types');
        parent::initialize($config);

        //$this->toggle('add', false);
        $this->toggle('remove', false);
        $this->toggle('reorder', false);

        $this->removeBehavior('Reorder');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $this->defaultMarkType = $StudentAttendanceMarkTypes->getDefaultMarkType();
    }

    

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        
        if (!is_null($requestData[$this->alias()]['student_attendance_type_id']) && !is_null($requestData[$this->alias()]['attendance_per_day']) && !is_null($requestData[$this->alias()]['code'])
        ) {
            //echo "<pre>";print_r($requestData[$this->alias()]);die;
            $code = $requestData[$this->alias()]['code'];
            $attendancePerDay = $requestData[$this->alias()]['attendance_per_day'];
            $attendanceTypeId = $requestData[$this->alias()]['student_attendance_type_id'];
           
            $resultSet = $this
                    ->find()
                    ->where([
                        $this->aliasField('code') => $code
                    ])
                    ->all();

                if (!$resultSet->isEmpty()) {
                    // delete any records if set attendance type back to default
                    $this
                    ->updateAll(['student_attendance_type_id' => $attendanceTypeId,'attendance_per_day' => $attendancePerDay], ['code' => $requestData[$this->alias()]['code']]);
                } else {
                $studentMarkTypeData = [
                    'student_attendance_type_id' => $attendanceTypeId,
                    'attendance_per_day' => $attendancePerDay,
                    'code' => $code
                ];

                $entity = $this->newEntity($studentMarkTypeData);
                $this->save($entity);
                }
            }

            $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
            for ($i=1;$i<=$attendancePerDay;$i++) { 

                $id = $requestData['p'.$i];
               // echo $id;die;
                if (!empty($id)) {
                $PeriodsData = $StudentAttendancePerDayPeriods
                ->find('all')
                ->where([
                    $StudentAttendancePerDayPeriods->aliasField('id = ') => $id
                ])
                ->all()
                ->toArray();
                }
               // echo "<pre>";print_r($PeriodsData);die;

                if (!empty($PeriodsData)) {

                    $StudentAttendancePerDayPeriods->updateAll(
                        ['name' => $requestData['period'][$i], 'period' => $i],
                    [
                        'id' => $id
                    ]
                    );
                } else {
                    
                   $StudentAttendancePerDayPeriodsData = [
                        'name' => $requestData['period'][$i],
                        'period' => $i
                    ];
                    $entity1 = $StudentAttendancePerDayPeriods->newEntity($StudentAttendancePerDayPeriodsData);
                    $StudentAttendancePerDayPeriods->save($entity1);
                }
            
        } /*else {
            $educationGradeId = $requestData[$this->alias()]['id'];
            $academicPeriodId = $requestData[$this->alias()]['academic_period_id'];
            $attendancePerDay = $this->defaultMarkType['attendance_per_day'];
            $attendanceTypeId = $requestData[$this->alias()]['student_attendance_type_id'];
            $resultSet = $this->StudentAttendanceMarkTypes
                    ->find()
                    ->where([
                        $this->StudentAttendanceMarkTypes->aliasField('academic_period_id') => $academicPeriodId,
                        $this->StudentAttendanceMarkTypes->aliasField('education_grade_id') => $educationGradeId
                    ])
                    ->toArray();
            if (!empty($resultSet)) {
                $this->StudentAttendanceMarkTypes
                ->updateAll(['student_attendance_type_id' => $attendanceTypeId,'attendance_per_day' => $attendancePerDay], ['education_grade_id' => $requestData[$this->alias()]['id'], 'academic_period_id' => $requestData[$this->alias()]['academic_period_id']]);
            } else {
                    $studentMarkTypeData = [
                    'attendance_per_day' => $attendancePerDay,
                    'student_attendance_type_id' => $attendanceTypeId,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId
                ];

                $entity = $this->StudentAttendanceMarkTypes->newEntity($studentMarkTypeData);
                $this->StudentAttendanceMarkTypes->save($entity);             
            }           */         
        //}
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $attendanceType = $StudentAttendanceTypes
                          ->find()
                          ->select([$StudentAttendanceTypes->aliasField('code')])
                          ->where([$StudentAttendanceTypes->aliasField('id') => $attendanceTypeId])
                          ->toArray();

       
        if ($attendanceType[0]->code == 'SUBJECT') {
            $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
            //$StudentAttendancePerDayPeriods->deleteAll(['education_grade_id' => $educationGradeId, 'academic_period_id' => $academicPeriodId]);
        }
        
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupField();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        /*$StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('academic_period_id = ') => $selectedAcademicPeriod,
            $StudentAttendancePerDayPeriods->aliasField('education_grade_id = ') => $education_grade_id
        ])
        ->all()
        ->toArray();*/
        //echo "<pre>";print_r($entity);die;
        if (!empty($entity->attendance_per_day)) {
            $attendance_per_day = $entity->attendance_per_day;
        } else {
            $attendance_per_day = $this->defaultMarkType['attendance_per_day'];
        }
        $this->controller->set('StudentAttendancePerDayPeriodsData', $StudentAttendancePerDayPeriodsData);
        $this->controller->set('attendance_per_day', $attendance_per_day);
        $this->setupField($entity);
        $student_attendance_type_id = $entity->student_attendance_type_id;
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $attendanceType = $StudentAttendanceTypes
                          ->find()
                          ->select([$StudentAttendanceTypes->aliasField('code')])
                          ->where([$StudentAttendanceTypes->aliasField('id') => $student_attendance_type_id])
                          ->toArray();
        $isMarkableSubjectAttendance = false;
        if ($attendanceType[0]->code == 'SUBJECT') {
            $isMarkableSubjectAttendance = true;            
        } else {
            $isMarkableSubjectAttendance = false;            
        }
        if ($isMarkableSubjectAttendance == true) {
            $this->fields['attendance_per_day']['visible'] = false;
            $this->fields['periods']['visible'] = false;
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $student_attendance_type_id = $entity->student_attendance_type_id;
        $entity->student_attendance_type_id = $student_attendance_type_id;
        

        /*$StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('academic_period_id = ') => $selectedAcademicPeriod,
            $StudentAttendancePerDayPeriods->aliasField('education_grade_id = ') => $education_grade_id
        ])
        ->all()
        ->toArray();*/
        if (!empty($entity->attendance_per_day)) {
            $attendance_per_day = $entity->attendance_per_day;
        }  else {
            $attendance_per_day = 1;
        }
        $this->controller->set('StudentAttendancePerDayPeriodsData', $StudentAttendancePerDayPeriodsData);
        $this->controller->set('attendance_per_day', $attendance_per_day);
        $this->setupField($entity);

        if (!empty($student_attendance_type_id)) {
            $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
            $attendanceType = $StudentAttendanceTypes
                              ->find()
                              ->select([$StudentAttendanceTypes->aliasField('code')])
                              ->where([$StudentAttendanceTypes->aliasField('id') => $student_attendance_type_id])
                              ->toArray();

            if ($attendanceType[0]->code == 'SUBJECT') {
                $this->fields['attendance_per_day']['visible'] = false;
                $this->fields['periods']['visible'] = false;
            } else {
                $this->fields['attendance_per_day']['visible'] = true;
                $this->fields['periods']['visible'] = true;
            }
        }

        if (!empty($entity->attendanceTypeId)) {
            $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
            $attendanceTypeEntity = $StudentAttendanceTypes
                              ->find()
                              ->select([$StudentAttendanceTypes->aliasField('code')])
                              ->where([$StudentAttendanceTypes->aliasField('id') => $entity->attendanceTypeId])
                              ->toArray();
            if ($attendanceTypeEntity[0]->code == 'DAY') {
            $this->fields['attendance_per_day']['visible'] = true;
            $this->fields['periods']['visible'] = true;
            } else if ($attendanceTypeEntity[0]->code == 'SUBJECT') {
                $this->fields['attendance_per_day']['visible'] = false;
                $this->fields['periods']['visible'] = false;
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        /*$selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $selectedEducationProgramme = $this->request->query('programme');
        
        $query
            ->contain([
                'StudentAttendanceMarkTypes' => function ($q) use ($selectedAcademicPeriod) {
                    return $q->where(['StudentAttendanceMarkTypes.academic_period_id' => $selectedAcademicPeriod]);
                }
            ])
            ->where([
                $this->aliasField('education_programme_id') => $selectedEducationProgramme,
            ]);*/
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        /*$selectedAcademicPeriod = $this->getSelectedAcademicPeriod();
        $query
            ->contain([
                'StudentAttendanceMarkTypes' => function ($q) use ($selectedAcademicPeriod) {
                    return $q->where(['StudentAttendanceMarkTypes.academic_period_id' => $selectedAcademicPeriod]);
                }
            ]);*/
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        /*$selectedAcademicPeriod = $this->getSelectedAcademicPeriod();

        $query          
            ->contain([
                'StudentAttendanceMarkTypes' => function ($q) use ($selectedAcademicPeriod) {
                    return $q->where(['StudentAttendanceMarkTypes.academic_period_id' => $selectedAcademicPeriod]);
                }
            ]);*/
    }

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        
    }

    public function onUpdateFieldStudentAttendanceTypeId(Event $event, array $attr, $action, Request $request)
    {
            if ($this->action == 'edit') {
            $entity = $attr['entity'];

            if (!empty($entity)) {                
                $markTypeId = $entity->student_attendance_type_id;
            } else {
                $markTypeId = $this->defaultMarkType['student_attendance_type_id'];
            }
            

            $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
            $attendanceOptions = $StudentAttendanceTypes
                ->find('list')
                ->toArray();

            $attr['type'] = 'select';
            $attr['attr']['options'] = $attendanceOptions;
            $attr['value'] = $markTypeId;
            $attr['onChangeReload'] = 'ChangeAttendanceType';
           
            return $attr;
        }
    }

    public function onUpdateFieldAttendancePerDay(Event $event, array $attr, $action, Request $request)
    {
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        
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
            $attr['options'] = $StudentAttendanceMarkTypes->getAttendancePerDayOptions();
            $attr['select'] = false;
            $attr['value'] = $attendancePerDay;
            $attr['attr']['value'] = $attendancePerDay;
            $attr['onChangeReload'] = 'ChangeAttendancePerDay';
            

            return $attr;
        
        
    }    

    public function onGetStudentAttendanceTypeId(Event $event, Entity $entity)
    {
        if (!empty($entity->student_attendance_mark_types)) {
            $attendanceTypeEntity = $entity;
            $attendanceTypeId = $entity->student_attendance_type_id;
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
        /*if (!empty($entity)) {
            $attendanceTypeId = $entity->student_attendance_type_id;
            $defaultattendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
            if ($attendanceTypeId == $defaultattendanceTypeId) {
                return $entity->attendance_per_day;
            } else {
                return '-';
            }
        } else {
            $attendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
            return $this->defaultMarkType['attendance_per_day'];
        }*/
    }

    private function setupField(Entity $entity = null)
    {    

        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);

        $this->field('attendance_per_day', ['type' => 'select','entity' => $entity]);
        $this->field('student_attendance_type_id', ['attr' => ['label' => __('Type'), 'required' => true,'entity' => $entity]]);
              
        
        $this->field('periods', [
                        'type' => 'element',
                        'element' => 'Attendance.periods',
                        
                    ]);
        $this->setFieldOrder(['name', 'code', 'student_attendance_type_id', 'attendance_per_day']);
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

        $entity->attendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
    }

    public function addEditOnChangeAttendanceType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $attendanceTypeId = $data[$this->alias()]['student_attendance_type_id'];
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $entity->attendanceTypeId = $attendanceTypeId;
        $attendanceType = $StudentAttendanceTypes
                          ->find()
                          ->select([$StudentAttendanceTypes->aliasField('code')])
                          ->where([$StudentAttendanceTypes->aliasField('id') => $attendanceTypeId])
                          ->toArray();
        $isMarkableSubjectAttendance = false;
        if ($attendanceType[0]->code == 'SUBJECT') {
            $isMarkableSubjectAttendance = true;            
        } else {
            $isMarkableSubjectAttendance = false;            
        }

        if ($isMarkableSubjectAttendance == true) {
            $this->fields['attendance_per_day']['visible'] = false;
            $this->fields['periods']['visible'] = false;
        } else {
            $this->fields['attendance_per_day']['visible'] = true;
            $this->fields['periods']['visible'] = true;
        }
    }

}
