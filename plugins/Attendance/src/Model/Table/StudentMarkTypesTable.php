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

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('code', [
                'unique' => [
                    'rule' => ['validateUnique'],
                    'provider' => 'table',
                    'message' => 'This code already exists in the system'
                ]
            ]);

        return $validator;
    }    

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {    
        if (!is_null($requestData[$this->alias()]['student_attendance_type_id']) && !is_null($requestData[$this->alias()]['code'])
        ) {
            $code = $requestData[$this->alias()]['code'];
            $attendancePerDay = $requestData[$this->alias()]['attendance_per_day'];
            $attendanceTypeId = $requestData[$this->alias()]['student_attendance_type_id'];

            $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
            $attendanceType = $StudentAttendanceTypes
                              ->find()
                              ->select([$StudentAttendanceTypes->aliasField('code')])
                              ->where([$StudentAttendanceTypes->aliasField('id') => $attendanceTypeId])
                              ->toArray();

            if ($attendanceType[0]->code == 'SUBJECT') {
                $attendancePerDay = 0;
            }
           
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
                } 
            

            $studentMarkData = $this
                    ->find()
                    ->where([
                        $this->aliasField('code') => $requestData[$this->alias()]['code']
                    ])
                    ->all()
                    ->toArray();

            $student_attendance_mark_type_id = $studentMarkData[0]->id;
            
            $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
            $dataWithOrder = array_keys($requestData['period']);
            $orderData = array_flip($dataWithOrder);

            for ($i=1;$i<=$attendancePerDay;$i++) { 
                $key= $orderData[$i];
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
                        ['name' => $requestData['period'][$i],
                         'student_attendance_mark_type_id' => $student_attendance_mark_type_id,'order' =>$key+1],
                    [
                        'id' => $id
                    ]
                    );
                
            } else {                    
                   $StudentAttendancePerDayPeriodsData = [
                        'name' => $requestData['period'][$i],
                        'student_attendance_mark_type_id' => $student_attendance_mark_type_id,
                        'period' => $i,
                    ];
                    $entity1 = $StudentAttendancePerDayPeriods->newEntity($StudentAttendancePerDayPeriodsData);
                    $StudentAttendancePerDayPeriods->save($entity1);
                }  
        } 
       
        if ($attendanceType[0]->code == 'SUBJECT') {
            $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
            $StudentAttendancePerDayPeriods->deleteAll(['student_attendance_mark_type_id' => $student_attendance_mark_type_id]);
        }
        }         
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
        $student_attendance_type_id = $entity->student_attendance_type_id; 
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $attendanceType = $StudentAttendanceTypes
                      ->find()
                      ->select([$StudentAttendanceTypes->aliasField('code')])
                      ->where([$StudentAttendanceTypes->aliasField('id') => $student_attendance_type_id])
                      ->toArray();
        if ($attendanceType[0]->code == 'SUBJECT') {
            $entity->attendance_per_day = 0;
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        if (!is_null($requestData[$this->alias()]['name']) && !is_null($requestData[$this->alias()]['student_attendance_type_id']) && !is_null($requestData[$this->alias()]['code'])
        ) {             
            $attendancePerDay = $requestData[$this->alias()]['attendance_per_day'];
            $studentMarkData = $this
                    ->find()
                    ->where([
                        $this->aliasField('code') => $requestData[$this->alias()]['code']
                    ])
                    ->all()
                    ->toArray();

            $student_attendance_mark_type_id = $studentMarkData[0]->id;
            $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
            $dataWithOrder = array_keys($requestData['period']);
            $orderData = array_flip($dataWithOrder);

            for ($i=1;$i<=$attendancePerDay;$i++) {         
                   $key= $orderData[$i];
                   $StudentAttendancePerDayPeriodsData = [
                        'name' => $requestData['period'][$i],
                        'student_attendance_mark_type_id' => $student_attendance_mark_type_id,
                        'period' =>  $i,
                        'order' =>  $key
                    ];
                    $entity1 = $StudentAttendancePerDayPeriods->newEntity($StudentAttendancePerDayPeriodsData);
                    $StudentAttendancePerDayPeriods->save($entity1);
                }
        }         
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupField();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {        
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        if (!empty($entity->attendance_per_day)) {
            $attendance_per_day = $entity->attendance_per_day;
        } else {
            $attendance_per_day = $this->defaultMarkType['attendance_per_day'];
        }
        $id = $entity->id;
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id = ') => $id
        ])
        
        ->order(['order'=>'asc'])         
        ->all()
       ->toArray();

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

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $student_attendance_type_id = $entity->student_attendance_type_id;
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

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $student_attendance_type_id = $entity->student_attendance_type_id;
        $entity->student_attendance_type_id = $student_attendance_type_id;
        
        $id = $entity->id;

        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id = ') => $id
        ])
        ->order(['order'=>'asc']) 
        ->all()
        ->toArray();
        if (!empty($entity->attendance_per_day)) {
                $attendance_per_day = $entity->attendance_per_day;
        } else {
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

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    }


    /* ublic function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    } */

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        
    }


    public function onUpdateFieldStudentAttendanceTypeId(Event $event, array $attr, $action, Request $request)
    {
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
        if (!empty($entity)) {
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

        $entity->attendanceTypeId = $attendanceTypeId;

        if (!is_null($markTypeEntity)) {
            return $markTypeEntity->name;
        }

    }

    public function onGetAttendancePerDay(Event $event, Entity $entity)
    {
        if (!empty($entity)) {
            $attendanceTypeId = $entity->attendanceTypeId;
            $defaultattendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
            if ($attendanceTypeId == $defaultattendanceTypeId) {
                return $entity->attendance_per_day;
            } else {
                return '-';
            }
        } else {
            $attendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
            return $this->defaultMarkType['attendance_per_day'];
        }
    }

    private function setupField(Entity $entity = null)
    {    
        $this->field('code');
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);   
        
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);

        $this->field('attendance_per_day', ['type' => 'select','entity' => $entity]);
        $this->field('student_attendance_type_id', ['type' => 'select','attr' => ['label' => __('Type'), 'required' => true,'entity' => $entity]]);
              
        if ($this->action == 'index') {
            $this->field('code', ['visible' => false]);
            $this->field('periods', ['visible' => false]);
        }

        $this->field('periods', [
                        'type' => 'element',
                        'element' => 'Attendance.periods',
                        
                    ]);
        $this->setFieldOrder(['name', 'code', 'student_attendance_type_id', 'attendance_per_day', 'periods']);
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
