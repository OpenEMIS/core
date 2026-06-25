<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class StudentMarkTypesTable extends ControllerActionTable
{
    private $defaultMarkType;

    public function initialize(array $config): void
    {
        $this->setTable('student_attendance_mark_types');
        parent::initialize($config);
        $this->hasMany('StudentMarkTypeStatuses', [
            'className' => 'Attendance.StudentMarkTypeStatuses',
            'foreignKey' => 'student_attendance_mark_type_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        //$this->toggle('add', false);
        $this->toggle('remove', true);//POCOR-7393 Case 2nd
        $this->toggle('reorder', false);

        // $this->removeBehavior('Reorder');
        $StudentAttendanceMarkTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
        $this->defaultMarkType = $StudentAttendanceMarkTypes->getDefaultMarkType();
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
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

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {    
        if (!is_null($requestData[$this->getAlias()]['student_attendance_type_id']) && !is_null($requestData[$this->getAlias()]['code'])
        ) {
            $code = $requestData[$this->getAlias()]['code'];
            $attendancePerDay = $requestData[$this->getAlias()]['attendance_per_day'];
            $attendanceTypeId = $requestData[$this->getAlias()]['student_attendance_type_id'];

            $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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
                    ->updateAll(['student_attendance_type_id' => $attendanceTypeId,'attendance_per_day' => $attendancePerDay], ['code' => $requestData[$this->getAlias()]['code']]);
                } 
            

            $studentMarkData = $this
                    ->find()
                    ->where([
                        $this->aliasField('code') => $requestData[$this->getAlias()]['code']
                    ])
                    ->all()
                    ->toArray();

            $student_attendance_mark_type_id = $studentMarkData[0]->id;
            
            $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
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
            $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
            $StudentAttendancePerDayPeriods->deleteAll(['student_attendance_mark_type_id' => $student_attendance_mark_type_id]);
        }
        }         
    }


    //POCOR-9353
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data) {

        $associatedRecordsExist = 
            $this->exists(['code ' => $data['StudentMarkTypes']['code']]);
        if ($associatedRecordsExist) {
            $message = __('This code already exists in the system');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $url = $this->request->referer();
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
        $student_attendance_type_id = $entity->student_attendance_type_id; 
        $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
        $attendanceType = $StudentAttendanceTypes
                        ->find()
                        ->select([
                            'code' => $StudentAttendanceTypes->aliasField('code'),
                            'id'   => $StudentAttendanceTypes->aliasField('id')
                        ])
                        ->where([
                            $StudentAttendanceTypes->aliasField('id') => $student_attendance_type_id
                        ])
                        ->toArray();
        if ($attendanceType[0]->code == 'SUBJECT') {
            $entity->attendance_per_day = 0;
        }
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        if (!is_null($requestData[$this->getAlias()]['name']) && !is_null($requestData[$this->getAlias()]['student_attendance_type_id']) && !is_null($requestData[$this->getAlias()]['code'])
        ) {             
            $attendancePerDay = $requestData[$this->getAlias()]['attendance_per_day'];
            $studentMarkData = $this
                    ->find()
                    ->where([
                        $this->aliasField('code') => $requestData[$this->getAlias()]['code']
                    ])
                    ->all()
                    ->toArray();

            $student_attendance_mark_type_id = $studentMarkData[0]->id;
            $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
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

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupField();
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Attendances','Attendances');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {        
        $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
        if (!empty($entity->attendance_per_day)) {
            $attendance_per_day = $entity->attendance_per_day;
        } else {
            $attendance_per_day = $this->defaultMarkType['attendance_per_day'];
        }
        $id = $entity->id;
        $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
        ->find('all')
        ->where([
            $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id = ') => $id
        ])
        
        ->order([$StudentAttendancePerDayPeriods->aliasField('order')=>'asc'])         
        ->all()
       ->toArray();

        $this->controller->set('StudentAttendancePerDayPeriodsData', $StudentAttendancePerDayPeriodsData);
        $this->controller->set('attendance_per_day', $attendance_per_day);
        $this->setupField($entity);
        $student_attendance_type_id = $entity->student_attendance_type_id;
        $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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
            $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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
            $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $student_attendance_type_id = $entity->student_attendance_type_id;
        $entity->student_attendance_type_id = $student_attendance_type_id;
        
        $id = $entity->id;

        $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
        $StudentAttendancePerDayPeriodsData = $StudentAttendancePerDayPeriods
                                        ->find('all')
                                        ->where([
                                            $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id = ') => $id
                                        ])
                                        ->order([$StudentAttendancePerDayPeriods->aliasField('order') => 'asc']) 
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
            $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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
            $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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

    public function onUpdateFieldName(EventInterface $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    }


    /* Public function onUpdateFieldCode(EventInterface $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            return $attr;
        }
    } */

    // public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action)
    {
        
    }


    // public function onUpdateFieldStudentAttendanceTypeId(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldStudentAttendanceTypeId(EventInterface $event, array $attr, $action)
    {
            $entity = $attr['entity'];
            if (!empty($entity)) {                
                $markTypeId = $entity->student_attendance_type_id;
            } else {
                $markTypeId = $this->defaultMarkType['student_attendance_type_id'];
            }           

            $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
            $attendanceOptions = $StudentAttendanceTypes
                ->find('list')
                ->toArray();

            $attr['type'] = 'select';
            $attr['attr']['options'] = $attendanceOptions;
            $attr['value'] = $markTypeId;
            $attr['onChangeReload'] = 'ChangeAttendanceType';
           
            return $attr;
    }

    // public function onUpdateFieldAttendancePerDay(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldAttendancePerDay(EventInterface $event, array $attr, $action)
    {
        $StudentAttendanceMarkTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
        
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
            
            if ($action == 'edit') { //POCOR-7277
                $attr['type'] = 'readonly';
            }          

            return $attr;           
    }    

    public function onGetStudentAttendanceTypeId(EventInterface $event, Entity $entity)
    {
        if (!empty($entity)) {
            $attendanceTypeEntity = $entity;
            $attendanceTypeId = $entity->student_attendance_type_id;
        } else {
            $attendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
        }

        $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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

    public function onGetAttendancePerDay(EventInterface $event, Entity $entity)
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

    public function addEditOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $period = $request->getQuery('period');
        if (isset($period)) {
            unset($request->query['period']);
        }


        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period_id', $request->getData($this->getAlias()))) {
                    $data = $request->getData($this->getAlias());
                    $request->query['period'] = $data['academic_period_id'];
                }
            }
        }
    }

    public function addEditOnChangeAttendancePerDay(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;

        $attendance_per_day = $request->getData($this->getAlias())['attendance_per_day'];
        $this->controller->set('attendance_per_day', $attendance_per_day);

        $entity->attendanceTypeId = $this->defaultMarkType['student_attendance_type_id'];
    }

    public function addEditOnChangeAttendanceType(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $attendanceTypeId = $data[$this->getAlias()]['student_attendance_type_id'];
        $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
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

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {   
        $id = $entity->id;
        if(!empty($id)){
            $studentAttendancePerDayPeriodsTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods');
            $studentAttendancePerDayPeriodsTable->deleteAll([
                $studentAttendancePerDayPeriodsTable->aliasField('student_attendance_mark_type_id') => $id
            ]);
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true) {
        if ($field == 'name') {
            return __('Name');
        }elseif ($field == 'code') {
            return __('Code');
        }elseif ($field == 'type') {
            return __('Type');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'attendance_per_day') {
            return __('Attendance Per Day');
        }elseif ($field == 'visible') {
            return __('Visible');
        }elseif ($field == 'periods') {
            return __('Periods');
        }elseif ($field == 'student_attendance_type_id') {
            return __('Student Attendance Type');
        }elseif ($field == 'hours_required') {
            return __('Hours Required');
        }elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }


    //POCOR-9353
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // Check if any associated records exist in related tables.
        $associatedRecordsExist = 
            $this->StudentMarkTypeStatuses->exists(['student_attendance_mark_type_id ' => $entity->id]);
        // If associated records exist, show alert message and abort deletion
        if ($associatedRecordsExist) {
            $message = __('Delete operation is not allowed as there are other information linked to this record.');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $url = $this->request->referer();
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }
}
