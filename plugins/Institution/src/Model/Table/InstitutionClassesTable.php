<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionClassesTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);


        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'saveStrategy' => 'replace', 'cascadeCallbacks' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'saveStrategy' => 'replace']);
        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id',
        ]);

        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);

        /**
         * Shortcuts
         */
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

        // this behavior restricts current user to see All Classes or My Classes
        $this->addBehavior('Security.SecurityAccess');
        $this->addBehavior('Security.InstitutionClass');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'ClassStudents' => ['view', 'edit'],
            'StudentCompetencies' => ['view'],
            'StudentCompetencyComments' => ['view'],
            'OpenEMIS_Classroom' => ['index', 'view'],
            'StudentOutcomes' => ['view'],
            'SubjectStudents' => ['index'],
            'Results'=> ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        $this->setDeleteStrategy('restrict');
		
		$this->addBehavior('ClassExcel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('staff_id')
            ->requirePresence('name')
            ->add('name', 'ruleUniqueNamePerAcademicPeriod', [
                'rule' => 'uniqueNamePerAcademicPeriod',
                'provider' => 'table',
            ])
            ->add('staff_id', 'ruleCheckHomeRoomTeachers', [
                'rule' => ['checkHomeRoomTeachers', 'classes_secondary_staff'],
                'provider' => 'table',
            ])
            ->add('capacity', 'ruleCheckMaxStudentsPerClass', [
                'rule' => ['checkMaxStudentsPerClass'],
                'provider' => 'table',
            ]);

        return $validator;
    }

    public static function uniqueNamePerAcademicPeriod($field, array $globalData)
    {
        $data = $globalData['data'];
        $model = $globalData['providers']['table'];
        $exists = $model->find('all')
            ->select(['id'])
            ->where([
                $model->aliasField('academic_period_id') => $globalData['data']['academic_period_id'],
                $model->aliasField('institution_id') => $globalData['data']['institution_id'],
                $model->aliasField('name') => $field,
            ])
            ->toArray();
        if (!empty($exists)) {
            foreach ($exists as $value) {
                if (array_key_exists('id', $data) && $value->id == $data['id']) {
                    // if editing an existing value
                    return true;
                    break;
                }
            }
            return false;
        } else {
            return true;
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.delete.afterAction'] = ['callable' => 'deleteAfterAction', 'priority' => 10];
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'classes_secondary_staff') {
            return $this->getMessage($this->aliasField($field));
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->query;
        
        if(!empty($this->request->data['InstitutionClasses']['institution_shift_id'])){
            $extra['institution_shift_id'] = $this->request->data['InstitutionClasses']['institution_shift_id'];
        }

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $extra['institution_id'] = $institutionId;
        $academicPeriodOptions = $this->getAcademicPeriodOptions($institutionId);
        $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();

        if ($this->action == 'index') {
            if (empty($query['academic_period_id'])) {
                $query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedGradeType = 'single';
            if (array_key_exists('grade_type', $query)) {
                $selectedGradeType = $query['grade_type'];
            }
            $gradeBehaviors = ['Institution.SingleGrade', 'Institution.MultiGrade'];
            foreach ($gradeBehaviors as $behavior) {
                if ($this->hasBehavior($behavior)) {
                    $this->removeBehavior($behavior);
                }
            }
            if ($selectedGradeType == 'single') {
                $this->addBehavior('Institution.SingleGrade');
            } else {
                $this->addBehavior('Institution.MultiGrade');
            }
            $extra['selectedGradeType'] = $selectedGradeType;
        }
        if (array_key_exists($this->alias(), $this->request->data)) {
            $selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
        } elseif ($this->action == 'edit' && isset($this->request->pass[1])) {
            $id = $this->paramsDecode($this->request->pass[1]);
            if ($this->exists($id)) {
                $selectedAcademicPeriodId = $this->get($id)->academic_period_id;
            }
        }

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        //POCOR-5852 starts
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $selectedAcademicPeriodId;
            $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
            if (!empty($gradeOptions)) {
                $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
            }

            $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
            $this->request->query['education_grade_id'] = $selectedEducationGradeId;
        }
        //POCOR-5852 ends
        $this->field('class_number', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('institution_shift_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);

        $this->field('total_students', ['type' => 'integer', 'visible' => ['index' => true]]);
        $this->field('subjects', ['override' => true, 'type' => 'integer', 'visible' => ['index' => true]]);

        $this->field('students', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Classes/students',
            'data' => [
                'students' => [],
                'studentOptions' => []
            ],
            'visible' => ['view' => true, 'edit' => true]
        ]);
        $this->field('education_grades', [
            'type' => 'element',
            'element' => 'Institution.Classes/multi_grade',
            'data' => [
                'grades'=>[]
            ],
            'visible' => ['view' => true]
        ]);

        $this->field('staff_id', [
            'type' => 'select', 
            'options' => [], 
            'visible' => ['index' => true, 'view' => true, 'edit' => true], 
            'attr' => [
                'label' => $this->getMessage($this->aliasField('staff_id'))
            ]
        ]);
        $this->field('classes_secondary_staff');

        $this->field('multigrade');
        $this->field('capacity', [
            'attr' => ['label' => __('Capacity'). $this->tooltipMessage()]
        ]);

        $this->setFieldOrder([
            'name','staff_id', 'classes_secondary_staff', 'multigrade', 'capacity', 'total_male_students', 'total_female_students', 'total_students', 'subjects'
        ]);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $action = $this->action;
        $institutionShiftId = $extra['entity']->institution_shift_id;
        if ($action != 'add') {
            $staffOptions = [];
            $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
            $institutionId = $extra['institution_id'];
            if ($selectedAcademicPeriodId > -1) {
                if ($action == 'index') {
                    $action = 'view';
                }
                $staffOptions = $this->getStaffOptions($institutionId, $action, $selectedAcademicPeriodId);
            }
            $this->fields['staff_id']['options'] = $staffOptions;
            $this->fields['staff_id']['select'] = false;
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('classStudents') && empty($data['classStudents'])) { //only utilize save by association when class student empty.
            $data['class_students'] = [];
            $data['total_male_students'] = 0;
            $data['total_female_students'] = 0;
            $data->offsetUnset('classStudents');
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {      
        if ($entity->isNew()) {
            $this->InstitutionSubjects->autoInsertSubjectsByClass($entity);

             if(!empty($this->controllerAction) && ($this->controllerAction == 'Classes')) {
                // POCOR-5435 ->Webhook Feature class (create)
            
                $bodyData = $this->find('all',
                            [ 'contain' => [
                                'Institutions',
                                'EducationGrades',
                                'Staff', 
                                'AcademicPeriods', 
                                'InstitutionShifts', 
                                'InstitutionShifts.ShiftOptions', 
                                'ClassesSecondaryStaff.SecondaryStaff', 
                                'Students'
                            ],
                ])->where([
                    $this->aliasField('id') => $entity->id
                ]);
                
                $grades = $gradeId = $secondaryTeachers = $students = [];

                if (!empty($bodyData)) { 
                    foreach ($bodyData as $key => $value) { 
                        $capacity = $value->capacity;
                        $shift = $value->institution_shift->shift_option->name;
                        $academicPeriod = $value->academic_period->name;
                        $homeRoomteacher = $value->staff->openemis_no;
                        $institutionId = $value->institution->id;
                        $institutionName = $value->institution->name;
                        $institutionCode = $value->institution->code;
                        
                        if(!empty($value->education_grades)) {
                            foreach ($value->education_grades as $key => $gradeOptions) {
                                $grades[] = $gradeOptions->name;
                                $gradeId[] = $gradeOptions->id;
                            }
                        }
                        
                        if(!empty($value->classes_secondary_staff)) {
                            foreach ($value->classes_secondary_staff as $key => $secondaryStaffs) {
                                $secondaryTeachers[] = $secondaryStaffs->secondary_staff->openemis_no;
                            }
                        }

                        $maleStudents = 0;
                        $femaleStudents = 0;
                        if(!empty($value->students)) {
                            foreach ($value->students as $key => $studentsData) {
                                $students[] = $studentsData->openemis_no;
                                if($studentsData->gender->code == 'M') {
                                    $maleStudents = $maleStudents + 1;
                                }
                                if($studentsData->gender->code == 'F') {
                                    $femaleStudents = $femaleStudents + 1;
                                }
                            }
                        }
                        
                    }
                }

                $body = array();
               
                $body = [   
                    'institutions_id' => !empty($institutionId) ? $institutionId : NULL,
                    'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                    'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                    'institutions_classes_id' => $entity->id,
                    'institutions_classes_name' => $entity->name,
                    'academic_periods_name' => !empty($academicPeriod) ? $academicPeriod : NULL,
                    'shift_options_name' => !empty($shift) ? $shift : NULL,
                    'institutions_classes_capacity' => !empty($capacity) ? $capacity : NULL,
                    'education_grades_id' => !empty($gradeId) ? $gradeId :NULL,
                    'education_grades_name' => !empty($grades) ? $grades : NULL, 
                    'institution_classes_total_male_students' => !empty($maleStudents) ? $maleStudents : 0,
                    'institution_classes_total_female_studentss' => !empty($femaleStudents) ? $femaleStudents : 0,
                    'total_students' => !empty($students) ? count($students) : 0,
                    'institution_classes_staff_openemis_no' => !empty($homeRoomteacher) ? $homeRoomteacher : NULL,
                    'institution_classes_secondary_staff_openemis_no' => !empty($secondaryTeachers) ? $secondaryTeachers : NULL,
                    'institution_class_students_openemis_no' => !empty($students) ? $students : NULL
                ];
                
                if($this->action == 'add') {
                   
                    $Webhooks = TableRegistry::get('Webhook.Webhooks');
                    if ($this->Auth->user()) { 
                        $Webhooks->triggerShell('class_create', ['username' => $username], $body);
                    }
                }
                // POCOR-5435 ->Webhook Feature class (create) -- end
            }
        } else { 

            $editAction  = json_decode(json_encode($options), true);
            $webhook_action = $editAction['extra']['action'];
            
            //empty class student is handled by beforeMarshal
            //in another case, it will be save manually to avoid unecessary queries during save by association
            if ($entity->has('classStudents') && !empty($entity->classStudents)) {
                $newStudents = [];
                //decode string sent through form
                foreach ($entity->classStudents as $item) {
                    $student = json_decode($this->urlsafeB64Decode($item), true);
                    $newStudents[$student['student_id']] = $student;
                }
                $institutionClassId = $entity->id;

                $existingStudents = $this->ClassStudents
                    ->find('all')
                    ->select([
                        'id', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'institution_id', 'student_status_id'
                    ])
                    ->matching('StudentStatuses', function ($q) {
                        return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
                    })
                    ->where([
                        $this->ClassStudents->aliasField('institution_class_id') => $institutionClassId
                    ])
                    ->toArray();

                foreach ($existingStudents as $key => $classStudentEntity) {
                    if (!array_key_exists($classStudentEntity->student_id, $newStudents)) { // if current student does not exists in the new list of students
                        $this->ClassStudents->delete($classStudentEntity);
                    } else { // if student exists, then remove from the array to get the new student records to be added
                        unset($newStudents[$classStudentEntity->student_id]);
                    }
                }
                    
                foreach ($newStudents as $key => $student) {
                    $newClassStudentEntity = $this->ClassStudents->newEntity($student);
                    $this->ClassStudents->save($newClassStudentEntity);
                }
            }
            
            // POCOR-5436 ->Webhook Feature class (update) -- start
            $bodyData = $this->find('all',
                        [ 'contain' => [
                            'Institutions',
                            'EducationGrades',
                            'Staff', 
                            'AcademicPeriods', 
                            'InstitutionShifts', 
                            'InstitutionShifts.ShiftOptions', 
                            'ClassesSecondaryStaff.SecondaryStaff', 
                            'Students',
                            'Students.Genders'
                        ],
                        ])->where([
                            $this->aliasField('id') => $entity->id
                        ]);
        
            $grades = $gradeId = $secondaryTeachers = $students = [];

            if (!empty($bodyData)) { 
                foreach ($bodyData as $key => $value) { 
                    $capacity = $value->capacity;
                    $shift = $value->institution_shift->shift_option->name;
                    $academicPeriod = $value->academic_period->name;
                    $homeRoomteacher = $value->staff->openemis_no;
                    $institutionId = $value->institution->id;
                    $institutionName = $value->institution->name;
                    $institutionCode = $value->institution->code;
                    
                    if(!empty($value->education_grades)) {
                        foreach ($value->education_grades as $key => $gradeOptions) {
                            $grades[] = $gradeOptions->name;
                            $gradeId[] = $gradeOptions->id;
                        }
                    }
                    
                    if(!empty($value->classes_secondary_staff)) {
                        foreach ($value->classes_secondary_staff as $key => $secondaryStaffs) {
                            $secondaryTeachers[] = $secondaryStaffs->secondary_staff->openemis_no;
                        }
                    }

                    $maleStudents = 0;
                    $femaleStudents = 0;
                    if(!empty($value->students)) {
                        foreach ($value->students as $key => $studentsData) {
                            $students[] = $studentsData->openemis_no;
                            if($studentsData->gender->code == 'M') {
                                $maleStudents = $maleStudents + 1;
                            }
                            if($studentsData->gender->code == 'F') {
                                $femaleStudents = $femaleStudents + 1;
                            }
                        }
                    }
                    
                }
            }
    
            $body = array();
    
            $body = [   
                'institutions_id' => !empty($institutionId) ? $institutionId : NULL,
                'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                'institutions_classes_id' => $entity->id,
                'institutions_classes_name' => $entity->name,
                'academic_periods_name' => !empty($academicPeriod) ? $academicPeriod : NULL,
                'shift_options_name' => !empty($shift) ? $shift : NULL,
                'institutions_classes_capacity' => !empty($capacity) ? $capacity : NULL,
                'education_grades_id' => !empty($gradeId) ? $gradeId :NULL,
                'education_grades_name' => !empty($grades) ? $grades : NULL, 
                'institution_classes_total_male_students' => !empty($maleStudents) ? $maleStudents : 0,
                'institution_classes_total_female_studentss' => !empty($femaleStudents) ? $femaleStudents : 0,
                'total_students' => !empty($students) ? count($students) : 0,
                'institution_classes_staff_openemis_no' => !empty($homeRoomteacher) ? $homeRoomteacher : NULL,
                'institution_classes_secondary_staff_openemis_no' => !empty($secondaryTeachers) ? $secondaryTeachers : NULL,
                'institution_class_students_openemis_no' => !empty($students) ? $students : NULL
            ];
            
            if($webhook_action == 'edit') {
                $Webhooks = TableRegistry::get('Webhook.Webhooks');
                if (!empty($entity->modified_user_id)) {
                    $Webhooks->triggerShell('class_update', ['username' => ''], $body);
                }
            }
            // POCOR-5436 ->Webhook Feature class (update) -- end   
        }
    }

    /******************************************************************************************************************
    **
    ** delete action methods
    **
    ******************************************************************************************************************/
    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        // only show the student and the subject of the class.
        $extra['excludedModels'] = [
            $this->ClassGrades->alias(),
            // $this->ClassStudents->alias(),
            // $this->SubjectStudents->alias(),
            $this->EducationGrades->alias(),
            $this->Students->alias(),
            $this->InstitutionSubjects->alias()
        ];
    }

    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $errorMessage = $this->aliasField('stopDeleteWhenStudentExists');
        if (isset($extra['errorMessage']) && $extra['errorMessage']==$errorMessage) {
            $this->Alert->warning($errorMessage, ['reset'=>true]);
        }
        if(!empty($this->controllerAction) && ($this->controllerAction == 'Classes')) {
           $bodyData = $this->find()->where([
                            $this->aliasField('id') => $entity->id
                        ]);
            $body = [];

            $body = [
                'institutions_class_id' => $entity->id,
            ];
            if($this->action == 'remove') {
                $Webhooks = TableRegistry::get('Webhook.Webhooks');
                if ($this->Auth->user()) {
                    $username = $this->Auth->user()['username'];
                    $Webhooks->triggerShell('class_delete', ['username' => $username], $body);
                }
            }
        }
    }

    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $Students = $this->ClassStudents;
        $conditions = [$Students->aliasField($Students->foreignKey()) => $entity->id];
        if ($Students->exists($conditions)) {
            $extra['errorMessage'] = $this->aliasField('stopDeleteWhenStudentExists');
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
    }


    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        if (array_key_exists('grade_type', $query)) {
            $action = $this->url('index');
            unset($action['grade_type']);
            $this->controller->redirect($action);
        }

        $Classes = $this;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes->find()
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
        if (!empty($gradeOptions)) {
            $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
        }

        $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
        $this->advancedSelectOptions($gradeOptions, $selectedEducationGradeId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId, $selectedAcademicPeriodId) {
                /**
                 * If statement added on PHPOE-1762 for PHPOE-1766
                 * If $id is -1, get all classes under the selected academic period
                 */

                $join = [
                    'table' => 'institution_class_grades',
                    'alias' => 'InstitutionClassGrades',
                    'conditions' => [
                        'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
                    ]
                ];

                if ($id > 0) {
                    $join['conditions']['InstitutionClassGrades.education_grade_id'] = $id;
                }

                $query = $Classes->find()
                        ->join([$join])
                        ->where([
                            $Classes->aliasField('institution_id') => $institutionId,
                            $Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                        ]);
                return $query->count();
            }
        ]);
        $extra['selectedEducationGradeId'] = $selectedEducationGradeId;

        $extra['elements']['control'] = [
            'name' => 'Institution.Classes/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId,
                'gradeOptions'=>$gradeOptions,
                'selectedGrade'=>$selectedEducationGradeId,
            ],
            'options' => [],
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $sortable = !is_null($this->request->query('sort')) ? true : false;

        $query
            ->find('byGrades', [
                'education_grade_id' => $extra['selectedEducationGradeId'],
            ])
            ->select([
                'id',
                'name',
                'class_number',
                'capacity',
                'staff_id',
                'total_male_students',
                'total_female_students',
                'institution_shift_id',
                'institution_id',
                'academic_period_id',
                'modified_user_id',
                'modified',
                'created_user_id',
                'created',
                'education_stage_order' => $query->func()->min('EducationStages.order')
            ])
            ->contain([
                'ClassesSecondaryStaff.SecondaryStaff',
                'Staff' => [
                    'fields' => ['openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name']
                ]
            ])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
            ->group([$this->aliasField('id')]);

        if (!$sortable) {
            $query
                ->order([
                    'education_stage_order',
                    $this->aliasField('name') => 'ASC'
                ]);
        }
    }


    public function findHomeOrSecondary(Query $query, array $options)
    {
        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');

            $classId = $options['class_id'];
            $staffId = $options['staff_id'];

            $query
                ->select(['staff_id' => $this->aliasField('staff_id')])
                ->where([
                    $this->aliasField('id') => $classId,
                    'OR' => [
                        [$this->aliasField('staff_id') => $staffId]
                    ]
                ])
                ->union(
                    $InstitutionClassesSecondaryStaff
                        ->find()
                        ->select(['staff_id' => $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')])
                        ->where([
                            $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $staffId,
                            $InstitutionClassesSecondaryStaff->aliasField('institution_class_id') => $classId
                        ])
                );

            return $query;
        }
    }
    public function findTranslateItem(Query $query, array $options)
    {
        return $query
            ->formatResults(function ($results) {
                $arrResults = $results->toArray();
                foreach ($arrResults as &$value) {
                    if (isset($value['class_students']) && is_array($value['class_students'])) {
                        foreach ($value['class_students'] as $student) {
                            $student['student_status']['name'] = __($student['student_status']['name']);
                        }
                    }
                }
                return $arrResults;
            });
    }

    public function findClassDetails(Query $query, array $options)
    {
        // POCOR-2547 sort list of staff and student by name
        // move the contain from institution.class.student.ctrl.js since its using finder method
        return $query
            ->find('translateItem')
            ->contain([
                'ClassStudents' => [
                    'sort' => ['Users.first_name', 'Users.last_name']
                ],
                'ClassStudents.StudentStatuses' => function ($q) {
                    return $q->where([('StudentStatuses.code NOT IN ') => ['TRANSFERRED', 'WITHDRAWN']]);
                },
                'ClassStudents.Users.Genders',
                'ClassStudents.EducationGrades',
                'AcademicPeriods',
                'ClassesSecondaryStaff.SecondaryStaff'
            ]);
    }

    public function findByGrades(Query $query, array $options)
    {
        $sortable = array_key_exists('sort', $options) ? $options['sort'] : false;

        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationStages = TableRegistry::get('Education.EducationStages');

        $gradeId = $options['education_grade_id'];
        $join = [
            'table' => 'institution_class_grades',
            'alias' => 'InstitutionClassGrades',
            'conditions' => [
                'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
            ]
        ];

        if ($gradeId > 0) {
            $join['conditions']['InstitutionClassGrades.education_grade_id'] = $gradeId;
        }

        $query = $query
            ->join([$join])

            ->innerJoin(
                [$EducationGrades->alias() => $EducationGrades->table()],
                [$EducationGrades->aliasField('id = ') . 'InstitutionClassGrades.education_grade_id']
            )
            ->innerJoin(
                [$EducationStages->alias() => $EducationStages->table()],
                [$EducationStages->aliasField('id = ') . 'EducationGrades.education_stage_id']
            );

        return $query;
    }


    /******************************************************************************************************************
    **
    ** view action methods
    **
    ******************************************************************************************************************/
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($extra['selectedAcademicPeriodId'] == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Classes'
            ]);
        }

        $query = $this->request->query;
        if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
            $action = $this->url('view');
            if (array_key_exists('academic_period_id', $query)) {
                unset($action['academic_period_id']);
            }
            if (array_key_exists('education_grade_id', $query)) {
                unset($action['education_grade_id']);
            }
            //$this->controller->redirect($action);
        }

        $this->field('total_students', ['visible' => true]);

        $this->setFieldOrder([
            'academic_period_id', 'name', 'institution_shift_id', 'education_grades', 'capacity', 'total_male_students', 'total_female_students',
            'total_students', 'staff_id', 'classes_secondary_staff', 'multigrade', 'students'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['selectedGrade'] = -1;
        $extra['selectedStatus'] = -1;
        $extra['selectedGender'] = -1;
        if (array_key_exists('queryString', $this->request->query)) {
            $queryString = $this->paramsDecode($this->request->query['queryString']);

            if (!empty($queryString) && array_key_exists('grade', $queryString)) {
                $extra['selectedGrade'] = $queryString['grade'];
            }

            if (!empty($queryString) && array_key_exists('status', $queryString)) {
                $extra['selectedStatus'] = $queryString['status'];
            }


            if (!empty($queryString) && array_key_exists('gender', $queryString)) {
                $extra['selectedGender'] = $queryString['gender'];
            }

            if (!empty($queryString) && array_key_exists('sort', $queryString)) {
                $extra['sort'] = $queryString['sort'];
            }

            if (!empty($queryString) && array_key_exists('direction', $queryString)) {
                $extra['direction'] = $queryString['direction'];
            }
        }

        $sortConditions = '';
        if (!empty($extra['sort'])) {
            if ($extra['sort'] == 'name') {
                $sortConditions = 'Users.first_name ' .  $extra['direction'];
            } elseif ($extra['sort'] == 'openemis_no') {
                $sortConditions = 'Users.openemis_no ' .  $extra['direction'];
            }
        }

        if ($sortConditions) {
            $query->contain([
                'AcademicPeriods',
                'InstitutionShifts.ShiftOptions',
                'EducationGrades',
                'Staff',
                'ClassesSecondaryStaff.SecondaryStaff',
                'ClassStudents' => [
                    'Users.Genders',
                    'Users.SpecialNeeds',
                    'EducationGrades',
                    'StudentStatuses',
                    'sort' => [$sortConditions]
                ],
            ]);
        } else {
            $query->contain([
                'AcademicPeriods',
                'InstitutionShifts.ShiftOptions',
                'EducationGrades',
                'Staff',
                'ClassesSecondaryStaff.SecondaryStaff',
                'ClassStudents' => [
                    'Users.Genders',
                    'Users.SpecialNeeds',
                    'EducationGrades',
                    'StudentStatuses'
                ],
            ]);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        //generate student filter.
        $params = $this->getQueryString();
        $baseUrl = $this->url($this->action, true);

        $this->fields['students']['data']['baseUrl'] = $baseUrl;
        $this->fields['students']['data']['params'] = $params;

        $gradeOptions = [];
        $statusOptions = [];
        $genderOptions = [];

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $configureStudentName = $ConfigItems->value("configure_student_name");

        foreach ($entity->class_students as $key => $value) {
            if (!empty($value->education_grade)) { //grade filter
                $gradeOptions[$value->education_grade->id]['name'] = $value->education_grade->name;
                $gradeOptions[$value->education_grade->id]['order'] = $value->education_grade->order;

                $params['grade'] = $value->education_grade->id;
                $params['status'] = $extra['selectedStatus']; //maintain current status selection
                $params['gender'] = $extra['selectedGender'];
                $url = $this->setQueryString($baseUrl, $params);

                $gradeOptions[$value->education_grade->id]['url'] = $url;
            }

            if (!empty($value->student_status)) { //status filter
                $statusOptions[$value->student_status->id]['name'] = $value->student_status->name;
                $statusOptions[$value->student_status->id]['order'] = $value->student_status->id;

                $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
                $params['status'] = $value->student_status->id;
                $params['gender'] = $extra['selectedGender'];
                $url = $this->setQueryString($baseUrl, $params);

                $statusOptions[$value->student_status->id]['url'] = $url;
            }

            if (!empty($value->user) && !empty($value->user->gender)) { //gender filter
                $genderOptions[$value->user->gender->id]['name'] = $value->user->gender->name;
                $genderOptions[$value->user->gender->id]['order'] = $value->user->gender->id;

                $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
                $params['status'] = $extra['selectedStatus'];
                $params['gender'] = $value->user->gender->id;
                $url = $this->setQueryString($baseUrl, $params);

                $genderOptions[$value->user->gender->id]['url'] = $url;
            }

            //if student does not fullfil the filter, then unset from array
            if ($extra['selectedGrade'] != -1 && $value->education_grade->id != $extra['selectedGrade']) {
                unset($entity->class_students[$key]);
            }

            if ($extra['selectedStatus'] != -1 && $value->student_status->id != $extra['selectedStatus']) {
                unset($entity->class_students[$key]);
            }

            if ($extra['selectedGender'] != -1 && $value->user->gender->id != $extra['selectedGender']) {
                unset($entity->class_students[$key]);
            }
        }

        //for all grades / no option
        $gradeOptions[-1]['name'] = count($gradeOptions) > 0 ? '-- ' . __('All Grades') . ' --' : '-- ' . __('No Options') . ' --';
        $gradeOptions[-1]['id'] = -1;
        $gradeOptions[-1]['order'] = 0;

        $params['grade'] = -1;
        $params['status'] = $extra['selectedStatus']; //maintain current status selection
        $params['gender'] = $extra['selectedGender'];
        $url = $this->setQueryString($baseUrl, $params);

        $gradeOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($gradeOptions, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        //for all statuses option
        $statusOptions[-1]['name'] = count($statusOptions) > 0 ? '-- ' . __('All Statuses') . ' --' : '-- ' . __('No Options') . ' --';
        $statusOptions[-1]['id'] = -1;
        $statusOptions[-1]['order'] = 0;

        $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
        $params['status'] = -1;
        $params['gender'] = $extra['selectedGender'];
        $url = $this->setQueryString($baseUrl, $params);

        $statusOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($statusOptions, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        //for all gender option
        $genderOptions[-1]['name'] = count($genderOptions) > 0 ? '-- ' . __('All Genders') . ' --' : '-- ' . __('No Options') . ' --';
        $genderOptions[-1]['id'] = -1;
        $genderOptions[-1]['order'] = 0;

        $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
        $params['status'] = $extra['selectedStatus'];
        $params['gender'] = -1;
        $url = $this->setQueryString($baseUrl, $params);

        $genderOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($genderOptions, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        //set option and selected filter value
        $this->fields['students']['data']['filter']['education_grades']['options'] = $gradeOptions;
        $this->fields['students']['data']['filter']['education_grades']['selected'] = $extra['selectedGrade'];

        $this->fields['students']['data']['filter']['student_status']['options'] = $statusOptions;
        $this->fields['students']['data']['filter']['student_status']['selected'] = $extra['selectedStatus'];

        $this->fields['students']['data']['filter']['genders']['options'] = $genderOptions;
        $this->fields['students']['data']['filter']['genders']['selected'] = $extra['selectedGender'];
        $this->fields['students']['data']['configure_student_name'] = $configureStudentName;

        $this->fields['education_grades']['data']['grades'] = $entity->education_grades;

        $this->fields['students']['data']['students'] = $entity->class_students;

        $academicPeriodOptions = $this->getAcademicPeriodOptions($entity->institution_id);
    }


    /******************************************************************************************************************
    **
    ** add action methods
    **
    ******************************************************************************************************************/
    // selected grade_type behavior's addBeforeAction will be called later
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
            $action = $this->url('add');
            if (array_key_exists('academic_period_id', $query)) {
                unset($action['academic_period_id']);
            }
            if (array_key_exists('education_grade_id', $query)) {
                unset($action['education_grade_id']);
            }
            //$this->controller->redirect($action);
        }
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        if (array_key_exists($this->alias(), $this->request->data)) {
            $academicPeriodOptions = $this->getAcademicPeriodOptions($extra['institution_id']);
            $selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
        }
        if ($selectedAcademicPeriodId == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Classes'
            ]);
        }
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['selectedEducationGradeId'] = 0;

        $this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)).' '.ucwords(strtolower($extra['selectedGradeType'])).' Grade');

        $tabElements = [
            'single' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', 'grade_type'=>'single'],
                'text' => $this->getMessage($this->aliasField('singleGrade'))
            ],
            'multi' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', 'grade_type'=>'multi'],
                'text' => $this->getMessage($this->aliasField('multiGrade'))
            ],
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);

        $this->field('multigrade', ['visible' => false]);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];

        if ($selectedAcademicPeriodId > -1) {
            $shiftOptions = $this->InstitutionShifts->getShiftOptions($institutionId, $selectedAcademicPeriodId);
        } else {
            $shiftOptions = [];
        }

        $this->fields['institution_shift_id']['options'] = $shiftOptions;
    $this->fields['institution_shift_id']['onChangeReload'] = true;

        if (empty($shiftOptions)) {
            $this->Alert->warning($this->aliasField('noShift'));
        }

        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable'=>true]);
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->fields['academic_period_id']['onChangeReload'] = true;
        $this->fields['academic_period_id']['default'] = $this->AcademicPeriods->getCurrent();

        $this->controller->set('selectedAction', $extra['selectedGradeType']);
    }

    /******************************************************************************************************************
    **
    ** field specific methods
    **
    ******************************************************************************************************************/
    public function onGetInstitutionShiftId(Event $event, Entity $entity)
    {
        if ($entity->institution_shift->institution_id != $entity->institution_id) { //if the current institution is not the owner of the shift.
            $ownerInfo = $this->Institutions->get($entity->institution_shift->institution_id)->toArray(); //show more information of the shift owner
            return $ownerInfo['code_name'] . ' - ' . $entity->institution_shift->shift_option->name;
        } else {
            return $entity->institution_shift->shift_option->name;
        }
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            if ($entity->has('staff')) {
                return $event->subject()->Html->link($entity->staff->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StaffUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->staff->id])
                ]);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        } else {
            if ($entity->has('staff')) {
                return $entity->staff->name_with_id;
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        }
    }

    public function onGetClassesSecondaryStaff(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            if ($entity->has('classes_secondary_staff') && !empty($entity->classes_secondary_staff)) {
                $staffList = [];
                foreach ($entity->classes_secondary_staff as $classStaffEntity) {
                    if ($classStaffEntity->has('secondary_staff')) {
                        $staffLink = $event->subject()->Html->link($classStaffEntity->secondary_staff->name_with_id, [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffUser',
                            'view',
                            $this->paramsEncode(['id' => $classStaffEntity->secondary_staff->id])
                        ]);

                        $staffList[] = $staffLink;
                    }
                } 
                return implode(', ', $staffList);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        } else {
            if ($entity->has('classes_secondary_staff') && !empty($entity->classes_secondary_staff)) {
                $staffList = [];
                foreach ($entity->classes_secondary_staff as $classStaffEntity) {
                    if ($classStaffEntity->has('secondary_staff')) {
                        $staffList[] = $classStaffEntity->secondary_staff->name_with_id;
                    }
                }
                return implode(', ', $staffList);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        }
    }

    public function onGetTotalStudents(Event $event, Entity $entity)
    {
        return $entity->total_male_students + $entity->total_female_students;
    }

    public function onGetSubjects(Event $event, Entity $entity)
    {
        if (!empty($entity->id) ) {
            $table = TableRegistry::get('Institution.InstitutionClassSubjects');
            $count = $table
                    ->find()
                    ->where([$table->aliasField('institution_class_id') => $entity->id])
                    ->count();

            $institutionClass = $table
                    ->find('all')
                    ->where([$table->aliasField('institution_class_id') => $entity->id])
                    ->toArray();

            if($institutionClass[0]->institution_class_id != $entity->id) {
                $ProgGradeSubjects = TableRegistry::get('Institution.InstitutionProgramGradeSubjects');
                $count = $ProgGradeSubjects
                        ->find()
                        ->where([$ProgGradeSubjects->aliasField('education_grade_id') => $entity->education_stage_order,
                                $ProgGradeSubjects->aliasField('institution_id') => $entity->institution_id])
                        ->count();
            }

            return $count;
        } 
    }

    public function onGetMultigrade(Event $event, Entity $entity)
    {
        if (empty($entity->class_number)) {
            return __('Yes');
        } else {
            return __('No');
        }
    }
    /******************************************************************************************************************
    **
    ** essential functions
    **
    ******************************************************************************************************************/
    public function getClassGradeOptions($institutionClassId)
    {
        $Grade = $this->ClassGrades;
        $gradeOptions = $Grade->find()
                            ->contain('EducationGrades')
                            ->where([
                                $Grade->aliasField('institution_class_id') => $institutionClassId
                            ])
                            ->toArray();
        $options = [];
        foreach ($gradeOptions as $value) {
            $options[] = $value->education_grade->id;
        }
        return $options;
    }



    /**
     * [getStudentsOptions description]
     * @param  [type] $classEntity [description]
     * @return [type]                [description]
     */
    private function getStudentsOptions($classEntity)
    {
        $academicPeriodId = $classEntity->academic_period_id;
        $academicPeriodObj = $this->AcademicPeriods->get($academicPeriodId);
        $classGradeObjects = $classEntity->education_grades;
        $classGrades = [];
        foreach ($classGradeObjects as $value) {
            $classGrades[] = $value->id;
        }

        /**
         * Modified this query in PHPOE-1780. Use PeriodBehavior which is loaded InstitutionStudents, by adding ->find('AcademicPeriod', ['academic_period_id'=> $academicPeriodId])
         * This is inline with how InstitutionClassesTable populate getStudentOptions.
         */
        $students = $this->Institutions->Students;

        //logic to get enrolled students from institution which has not been assigned to class
        //the institution student also validated based on the academic period
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        $query = $students
            ->find('all')
            ->leftJoin([
                'ClassStudents' => 'institution_class_students'], [
                    'ClassStudents.student_id = ' . $students->aliasfield('student_id'),
                    'AND' => [
                        'ClassStudents.student_status_id = ' . $enrolled,
                        'ClassStudents.academic_period_id = ' . $academicPeriodId
                    ]
                ])
            ->contain([
                'Users' => function ($q) {
                    return $q->select(['id', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name']);
                },
                'EducationGrades'
            ])
            ->where([
                $students->aliasField('institution_id') => $classEntity->institution_id,
                $students->aliasField('student_status_id') => $enrolled,
                $students->aliasField('education_grade_id') . ' IN' => $classGrades,
                $students->aliasField('academic_period_id')  => $academicPeriodId,
                'ClassStudents.id IS NULL' //dont have class assigned
            ])
            ->order([
                'EducationGrades.order'
            ])
            ->toArray();

        $studentOptions = [$this->getMessage('Users.select_student')];
        if (!empty($query)) {
            $studentOptions[-1] = $this->getMessage('Users.add_all_student');
        }
        foreach ($query as $obj) {
            /**
             * Modified this filter in PHPOE-1799.
             * Use institution_students table through $this->Institutions->Students where Students being the table alias.
             */
            if (in_array($obj->education_grade_id, $classGrades)) {
                if (isset($obj->user)) {
                    $studentOptions[$obj->education_grade->name][$obj->user->id] = $obj->user->name_with_id;
                } else {
                    $this->log('Data corrupted with no security user for student: '. $obj->id, 'debug');
                }
            }
        }
        $studentOptions = $this->attachClassInfo($classEntity, $studentOptions);
        return $studentOptions;
    }

    private function attachClassInfo($classEntity, $studentOptions)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        if (!empty($studentOptions)) {
            $query = $this->ClassStudents->find()
                        ->contain(['InstitutionClasses'])
                        ->where([
                            $this->aliasField('institution_id') => $classEntity->institution_id,
                            $this->aliasField('academic_period_id') => $classEntity->academic_period_id,
                        ])
                        ->where([
                                $this->ClassStudents->aliasField('student_id').' IN' => array_keys($studentOptions),
                                $this->ClassStudents->aliasField('academic_period_id') => $classEntity->academic_period_id,
                                $this->ClassStudents->aliasField('student_status_id') => $enrolled
                            ]);
            $classesWithStudents = $query->toArray();

            foreach ($classesWithStudents as $student) {
                if ($student->institution_class_id != $classEntity->id) {
                    if (!isset($studentOptions[$student->institution_class->name])) {
                        $studentOptions[$student->institution_class->name] = ['text' => 'Class '.$student->institution_class->name, 'options' => [], 'disabled' => true];
                    }
                    $studentOptions[$student->institution_class->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
                    unset($studentOptions[$student->student_id]);
                }
            }
        }
        return $studentOptions;
    }

    public function getStaffOptions($institutionId, $action = 'edit', $academicPeriodId = 0, $staffIds = [], $institutionShiftId = 0,$homeTeacher = null)
    {
        if (in_array($action, ['edit', 'add'])) {
            $options = [0 => '-- ' . $this->getMessage($this->aliasField('selectTeacherOrLeaveBlank')) . ' --'];
        } else {
            $options = [0 => $this->getMessage($this->aliasField('noTeacherAssigned'))];
        }

        if (empty($staffIds)) {
            $staffIds = [0];
        }

        if (!empty($academicPeriodId)) {
            $academicPeriodObj = $this->AcademicPeriods->get($academicPeriodId);
            $startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
            $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);
            $todayDate = new Date();
            // where condition for shift 
            if(!empty($institutionShiftId) && $institutionShiftId!=0) {
                $where = ['InstitutionStaffShifts.shift_id' => $institutionShiftId];
            }
            $Staff = $this->Institutions->Staff;
            $query = $Staff->find('all')
                            ->select([
                                $Staff->Users->aliasField('id'),
                                $Staff->Users->aliasField('openemis_no'),
                                $Staff->Users->aliasField('first_name'),
                                $Staff->Users->aliasField('middle_name'),
                                $Staff->Users->aliasField('third_name'),
                                $Staff->Users->aliasField('last_name'),
                                $Staff->Users->aliasField('preferred_name')
                            ])
                            ->contain(['Users'])
                           
                            ->find('byInstitution', ['Institutions.id'=>$institutionId])
                            ->find('AcademicPeriod', ['academic_period_id'=>$academicPeriodId])
                            ->join(
                                ['InstitutionStaffShifts' => 'institution_staff_shifts'],
                                ['InstitutionStaffShifts.staff_id = ' . $Staff->aliasField('staff_id')]
                            )
                            ->where($where)
                            ->where([
                                $Staff->aliasField('staff_id NOT IN') => $staffIds,
                                $Staff->aliasField('start_date <= ') => $todayDate,
                                'OR' => [
                                    [$Staff->aliasField('end_date >= ') => $todayDate],
                                    [$Staff->aliasField('end_date IS NULL')]
                                ]
                            ])

                            ->order([
                                $Staff->Users->aliasField('first_name')
                            ]);
                            if($homeTeacher) {
                                $query  ->matching('Positions', function ($q) {
                                    return $q->where(['Positions.is_homeroom' => 1]);
                                });
                            }
                            $query->formatResults(function ($results) {
                                $returnArr = [];
                                foreach ($results as $result) {
                                    if ($result->has('Users')) {
                                        $returnArr[$result->Users->id] = $result->Users->name_with_id;
                                    }
                                }
                                return $returnArr;
                            });
            $options = $options + $query->toArray();
        }

        return $options;
    }

    public function getExistedClasses($institutionId, $academicPeriodId, $educationGradeId)
    {
        $data = $this->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->join([
                [
                    'table' => 'institution_class_grades',
                    'alias' => 'InstitutionClassGrades',
                    'conditions' => [
                        'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('id'),
                        'InstitutionClassGrades.education_grade_id = ' . $educationGradeId
                    ]
                ]
            ])
            ->where([
                /**
                 * If class_number is null, it is considered as a multi-grade class
                 */
                $this->aliasField('class_number').' IS NOT NULL',
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->toArray()
            ;
        return $data;
    }

    public function createVirtualStudentEntity($id, $entity)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        if ($entity->has('education_grades')) { //build grades array to cater for multi grade class
            foreach ($entity->education_grades as $value) {
                $educationGrades[] = $value->id;
            }
        }

        $InstitutionStudentsTable = $this->Institutions->Students;
        $userData = $InstitutionStudentsTable->find()
            ->contain(['Users' => ['Genders'], 'StudentStatuses', 'EducationGrades'])
            ->where([
                $InstitutionStudentsTable->aliasField('student_id') => $id,
                $InstitutionStudentsTable->aliasField('institution_id') => $entity->institution_id,
                $InstitutionStudentsTable->aliasField('academic_period_id') => $entity->academic_period_id,
                //this is to ensure that student have the correct education grade accordingly.
                $InstitutionStudentsTable->aliasField('education_grade_id IN ') => $educationGrades
            ])
            ->first();

        if ($userData) {
            $data = [
                'id' => $this->getExistingRecordId($id, $entity),
                'student_id' => $id,
                'institution_class_id' => $entity->id,
                'education_grade_id'=>  $userData->education_grade_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'student_status_id' => $userData->student_status_id,
                'education_grade' => [],
                'student_status' => [],
                'user' => []
            ];
            $student = $this->ClassStudents->newEntity();
            $student = $this->ClassStudents->patchEntity($student, $data);
            $student->user = $userData->user;
            $student->student_status = $userData->student_status;
            $student->education_grade = $userData->education_grade;
            return $student;
        } else {
            return null;
        }
    }

    public function getExistingRecordId($securityId, $entity)
    {
        $id = Text::uuid();
        foreach ($entity->class_students as $student) {
            if ($student->student_id == $securityId) {
                $id = $student->id;
            }
        }
        return $id;
    }

    private function getAcademicPeriodOptions($institutionId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $conditions = [$InstitutionGrades->aliasField('institution_id') => $institutionId];
        return $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
    }

    public function findClassOptions(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options)? $options['institution_id']: null;
        $academicPeriodId = array_key_exists('academic_period_id', $options)? $options['academic_period_id']: null;
        $gradeId = array_key_exists('grade_id', $options)? $options['grade_id']: null;

        if (!is_null($academicPeriodId) && !is_null($institutionId) && !is_null($gradeId)) {
            $query->select(['InstitutionClasses.id', 'InstitutionClasses.name']);
            $query->where([
                'InstitutionClasses.academic_period_id' => $academicPeriodId,
                'InstitutionClasses.institution_id' => $institutionId
            ]);
            if ($gradeId != false) {
                $query->join(
                    [
                        [
                            'table' => 'institution_class_grades',
                            'alias' => 'InstitutionClassGrades',
                            'conditions' => [
                                'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
                                'InstitutionClassGrades.education_grade_id = ' . $gradeId
                            ]
                        ]
                    ]
                );
                $query->group(['InstitutionClasses.id']);
            }
        } else {
            // incomplete data return nothing
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findSubjectClassOptions(Query $query, array $options)
    {
        $institutionId = array_key_exists('institution_id', $options)? $options['institution_id']: null;
        $academicPeriodId = array_key_exists('academic_period_id', $options)? $options['academic_period_id']: null;
        $gradeId = array_key_exists('grade_id', $options)? $options['grade_id']: null;
        $institutionSubjectId = array_key_exists('institution_subject_id', $options)? $options['institution_subject_id']: null;

        if (!is_null($academicPeriodId) && !is_null($institutionId) && !is_null($gradeId)) {
            $query
                ->select(['InstitutionClasses.id', 'InstitutionClasses.name'])
                ->where([
                    'InstitutionClasses.academic_period_id' => $academicPeriodId,
                    'InstitutionClasses.institution_id' => $institutionId
                ])
                ->join(
                    [
                        [
                            'table' => 'institution_class_grades',
                            'alias' => 'InstitutionClassGrades',
                            'conditions' => [
                                'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
                                'InstitutionClassGrades.education_grade_id = ' . $gradeId
                            ]
                        ]
                    ]
                )
                ->group(['InstitutionClasses.id']);
        } else {
            // incomplete data return nothing
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    /**
     * Used by Institution/UserBehavior && Institution/InstitutionStudentsTable
     * @param  [integer]  $academicPeriodId [description]
     * @param  [integer]  $institutionId    [description]
     * @param  boolean $gradeId          [description]
     * @return [type]                    [description]
     */
    public function getClassOptions($academicPeriodId, $institutionId, $gradeId = false)
    {
        $multiGradeOptions = [
            'fields' => ['InstitutionClasses.id', 'InstitutionClasses.name'],
            'conditions' => [
                'InstitutionClasses.academic_period_id' => $academicPeriodId,
                'InstitutionClasses.institution_id' => $institutionId
            ],
            'order' => ['InstitutionClasses.name']
        ];

        if ($gradeId != false) {
            $multiGradeOptions['join'] = [
                [
                    'table' => 'institution_class_grades',
                    'alias' => 'InstitutionClassGrades',
                    'conditions' => [
                        'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
                        'InstitutionClassGrades.education_grade_id = ' . $gradeId
                    ]
                ]
            ];
            $multiGradeOptions['group'] = ['InstitutionClasses.id'];
        }

        $multiGradeData = $this->find('list', $multiGradeOptions);
        return $multiGradeData->toArray();
    }

    public function findClassesByInstitutionAndAcademicPeriod(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $staffId = $options['user']['id'];
        $isStaff = $options['user']['is_staff'];

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name')
            ])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order([$this->aliasField('name')]);

             if ($options['user']['super_admin'] == 0) { 
                $query                 
                    ->select([
                            'SecurityRoleFunctions._view',
                            'SecurityRoleFunctions._edit'
                        ]);
                $allclassesPermission = $this->getRolePermissionAccessForAllClasses($staffId, $institutionId);
                $mySubjectsPermission = $this->getRolePermissionAccessForMySubjects($staffId, $institutionId);
                $myClassesPermission = $this->getRolePermissionAccessForMyClasses($staffId, $institutionId);
                if (!$allclassesPermission) {
                if ($mySubjectsPermission && !$myClassesPermission) {
                    $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
                    $query
                    ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                            [
                                'InstitutionClassSubjects.institution_class_id = '.$this->aliasField('id')
                            ]
                        ])
                    ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            [
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id',
                                'InstitutionSubjectStaff.staff_id' => $staffId
                            ]
                        ]);
                } else if ($myClassesPermission && !$mySubjectsPermission) {
                        $query
                        ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            [
                                'InstitutionClassesSecondaryStaff.institution_class_id = '.$this->aliasField('id')
                            ]
                        ])
                        ->where([
                        'OR' => [
                                    $this->aliasField('staff_id') => $staffId,
                                    'InstitutionClassesSecondaryStaff.secondary_staff_id' => $staffId
                                ]
                        ]);
                    
                } else if ($myClassesPermission && $mySubjectsPermission) {
                    $query
                    ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                            [
                                'InstitutionClassSubjects.institution_class_id = '.$this->aliasField('id')
                            ]
                        ])
                    ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            [
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id',
                                'InstitutionSubjectStaff.staff_id' => $staffId
                            ]
                        ])
                        ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            [
                                'InstitutionClassesSecondaryStaff.institution_class_id = '.$this->aliasField('id')
                            ]
                        ])
                        ->where([
                        'OR' => [
                                $this->aliasField('staff_id') => $staffId,
                                'InstitutionClassesSecondaryStaff.secondary_staff_id' => $staffId,
                                'InstitutionSubjectStaff.staff_id' => $staffId
                                ]
                        ]);
                    }
                }                
            }
            
        return $query;
    }

    public function getSubjectClasses($institutionId, $academicPeriodId, $gradeId, $subjectId)
    {
        return $this->find('list')->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->innerJoinWith('EducationGrades', function ($q) use ($gradeId) {
                return $q->where(['EducationGrades.id' => $gradeId]);
            })
            ->innerJoinWith('InstitutionSubjects', function ($q) use ($subjectId) {
                return $q->where(['InstitutionSubjects.education_subject_id' => $subjectId]);
            })
            ->toArray();
    }

    protected function tooltipMessage()
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $maxCapacity = $ConfigItems->value('max_students_per_class');

        $message =  "Capacity must not exceed " . $maxCapacity . " students per class";
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    public function getRolePermissionAccessForMyClasses($userId, $institutionId)
    {
        $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId); 
        $QueryResult = TableRegistry::get('SecurityRoleFunctions')->find()              
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityRoleFunctions.security_role_id IN'=>$roles,
                    'AND' => [ 'OR' => [ 
                                        "SecurityFunctions.`_view` LIKE 'Classes.index%'",
                                        "SecurityFunctions.`_view` LIKE 'Classes.view%'"
                                    ]
                              ],
                    'SecurityRoleFunctions._view' => 1
                ])
                ->toArray();
        if(!empty($QueryResult)){
            return true;
        }
          
        return false;
    }

    public function getRolePermissionAccessForMySubjects($userId, $institutionId)
    {
        $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId); 
        $QueryResult = TableRegistry::get('SecurityRoleFunctions')->find()              
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityRoleFunctions.security_role_id IN'=>$roles,
                    'AND' => [ 'OR' => [ 
                                        "SecurityFunctions.`_view` LIKE 'Subjects.index%'",
                                        "SecurityFunctions.`_view` LIKE 'Subjects.view%'"
                                    ]
                              ],
                    'SecurityRoleFunctions._view' => 1
                ])
                ->toArray();
        if(!empty($QueryResult)){
            return true;
        }
          
        return false;
    }

    public function getRolePermissionAccessForAllClasses($userId, $institutionId)
    {
        $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId); 
        $QueryResult = TableRegistry::get('SecurityRoleFunctions')->find()              
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityRoleFunctions.security_role_id IN'=>$roles,
                    'AND' => [ 'OR' => [ 
                                        "SecurityFunctions.`_view` LIKE 'AllClasses.index%'",
                                        "SecurityFunctions.`_view` LIKE 'AllClasses.view%'"
                                    ]
                              ],
                    'SecurityRoleFunctions._view' => 1
                ])
                ->toArray();
        if(!empty($QueryResult)){
            return true;
        }
          
        return false;
    }
    
    public function findGradesByInstitutionAndAcademicPeriodAndInstitutionClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $institutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        
        $query->select([
            'id' => $EducationGrades->aliasField('id'),
            'name' => $EducationGrades->aliasField('name')
        ])
        ->innerJoin(
            [$institutionClassGrades->alias() => $institutionClassGrades->table()],
            [$this->aliasField('id = ') . $institutionClassGrades->aliasField('institution_class_id')]
        )->innerJoin(
            [$EducationGrades->alias() => $EducationGrades->table()],
            [$EducationGrades->aliasField('id = ') . $institutionClassGrades->aliasField('education_grade_id')]
        )
        ->where([
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $institutionClassGrades->aliasField('institution_class_id') => $institutionClassId
        ])
        ->group([$EducationGrades->aliasField('id')])
        ->order([$EducationGrades->aliasField('name')]);
        
        return $query;
        
    }
}
