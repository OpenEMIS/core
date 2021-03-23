<?php
namespace Institution\Model\Table;

use ArrayObject;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;

class InstitutionGradesTable extends ControllerActionTable
{
    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('institution_grades');
        parent::initialize($config);

        $this->belongsTo('EducationGrades',             ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions',                ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index']
        ]);

        $this->toggle('search', false);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
        ->allowEmpty('end_date')
        ->add('end_date', 'ruleCompareDateReverse', [
            'rule' => ['compareDateReverse', 'start_date', true]
        ])
        ->add('end_date', 'ruleCheckStudentInEducationProgrammes', [
            'rule' => ['checkStudentInEducationProgrammes']
        ])
        ->add('start_date', 'ruleCompareWithInstitutionDateOpened', [
            'rule' => ['compareWithInstitutionDateOpened']
        ])
        ->requirePresence('programme');
        
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $this->field('start_date', ['visible' => ['index'=>true, 'view'=>true, 'edit'=>true],'onChangeReload' => true,'sort' => ['field' => 'InstitutionGrades.start_date']]);
        $this->field('end_date', ['onChangeReload' => true,'sort' => ['field' => 'InstitutionGrades.end_date']]);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $today = new DateTime();
        $startDate = $today->format('d-m-Y');
        $this->field('level');
        $this->field('programme');        
        $this->field('end_date', ['default_date' => false]);
        $this->field('education_grade_id');
        $this->field('education_subject_id');

        if ($this->action == 'add') {
            $this->field('start_date', ['value' => $startDate]);
            $this->setFieldOrder([
                'level', 'programme','education_grade_id', 'start_date', 
                'end_date','education_subject_id'
            ]);
        } else if ($this->action == 'index') {
            $this->setFieldOrder([
                'education_grade_id', 'programme', 'level', 'start_date', 'end_date'
            ]);
        } else if ($this->action == 'view' || $this->action == 'edit') {
            $this->setFieldOrder([
                'level', 'programme', 'education_grade_id', 'start_date', 
                'end_date','education_subject_id'
            ]);
        }
    }


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
{
    $query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']);
    $sortList = ['InstitutionGrades.start_date','InstitutionGrades.end_date'];

    if (array_key_exists('sortWhitelist', $extra['options'])) {
        $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
    }

    $extra['options']['sortWhitelist'] = $sortList;
    $requestQuery = $this->request->query;
    $sortable = array_key_exists('sort', $requestQuery) ? true : false;

    if (!$sortable) {
        $query->order([
            'InstitutionGrades.start_date',
            'InstitutionGrades.end_date'
        ]);
    }
}


/******************************************************************************************************************
**
** viewEdit action methods
**
******************************************************************************************************************/
public function viewEditBeforeQuery(Event $event, Query $query)
{
    $query->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']);
}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
{        
    $errors = $entity->errors();
    $process = function($model, $entity) use ($data, $errors) {
            /**
             * PHPOE-2117
             * Remove       $this->field('institution_programme_id', ['type' => 'hidden']);
             *
             * education_grade_id will always be empty
             * so if errors array is more than 1, other fields are having an error
             */
            
            if (empty($errors) || count($errors)==1) {

                if ($data->offsetExists('grades')) {
                    $gradeIsSelected = false;
                    $error = true;
                    $gradeEntities = [];
                    $gradeSubjectEntities = [];
                    
                    if ($data['grades']['education_grade_id'] != 0 
                        && $data['grades']['education_grade_id'] != ''
                    ) {
                        $error = false;
                    $gradeIsSelected = true;
                    $grade['education_grade_id'] = $data['grades']['education_grade_id'];
                            // need to set programme value since it was marked as required in validationDefault()
                    $grade['programme'] = $entity->programme;
                    $grade['start_date'] = $entity->start_date;
                    $grade['institution_id'] = $entity->institution_id;
                    if ($entity->has('end_date')) {
                        $grade['end_date'] = $entity->end_date;
                    }

                    $gradeEntities[] = $this->newEntity($grade);
                    if ($gradeEntities[0]->errors()) {
                        $error = true;
                    }
                }

                if ($error && $gradeIsSelected) {
                    $model->Alert->error($this->aliasField('failedSavingGrades'));
                    return false;
                } else if (!$gradeIsSelected) {
                    $model->Alert->error($this->aliasField('noGradeSelected'));
                    return false;
                } else {
                    foreach ($gradeEntities as $grade) {
                        $entity->education_grade_id = $grade->education_grade_id;
                        $result = $this->save($grade);
                        $lastInsertId=$result->id;

                            // POCOR 5001  
                        if (count($data['grades']['education_grade_subject_id']) > 0
                    ) {
                            $gradeSubjectEntities = $data['grades']['education_grade_subject_id'];
                        $createdUserId = $this->Session->read('Auth.User.id');
                        $institutionProgramGradeSubjectID = [];
                        foreach($gradeSubjectEntities as $gradeSubjectId){ 
                            if($gradeSubjectId > 0){
                                $institutionProgramGradeSubject = TableRegistry::get('InstitutionProgramGradeSubjects');
                                $gradeSubject = $institutionProgramGradeSubject->newEntity();
                                $gradeSubject->institution_grade_id = $lastInsertId;
                                $gradeSubject->education_grade_subject_id = $gradeSubjectId;
                                $gradeSubject->education_grade_id = $data['grades']['education_grade_id'];                    
                                $gradeSubject->institution_id = $entity->institution_id;
                                $gradeSubject->created_user_id = $createdUserId;
                                $today = new DateTime();
                                $gradeSubject->created = $today->format('Y-m-d H:i:s');
                                $institutionProgramGradeSubject->save($gradeSubject);
                                array_push($institutionProgramGradeSubjectID,$gradeSubject->id);   
                            }
                        }

                        if(!empty($this->controllerAction) && ($this->controllerAction == 'Programmes')) {
                               $educationGrades = TableRegistry::get('Education.EducationGrades');

                               $bodyData = $educationGrades->find('all',
                                [ 'contain' => [
                                    'EducationProgrammes',
                                    'EducationProgrammes.EducationCycles.EducationLevels',
                                    'EducationProgrammes.EducationCycles',
                                    'EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'

                                ],
                            ])->where([
                                $educationGrades->aliasField('education_programme_id') => $entity->programme
                            ]);

                            $institution = $this->find('all',
                                [ 'contain' => [
                                    'Institutions',
                                    'Institutions.InstitutionClasses',
                                ],
                            ])->where([
                                $this->aliasField('institution_id') => $entity->institution_id
                            ]);

                            $institutionProgramGradeSubject = TableRegistry::get('Education.EducationSubjects');
                            $sub = $institutionProgramGradeSubject->find()
                            ->where([
                                'id IN' => $data['grades']['education_grade_subject_id']
                            ]);

                            $subject = [];
                            if (!empty($sub)) {
                             foreach ($sub as $key => $value) {
                                 $subject[] = $value['code'] . " - " . $value['name'];
                             }
                         }

                         if (!empty($bodyData)) { 
                            foreach ($bodyData as $key => $value) {
                                $education_system_name = $value->education_programme->education_cycle->education_level->education_system->name;
                                $education_level_name = $value->education_programme->education_cycle->education_level->name;
                                $education_cycle_name = $value->education_programme->education_cycle->name;
                                $education_programme_code = $value->education_programme->code;
                                $education_programme_name = $value->education_programme->name;
                                $education_programme_name = $value->education_programme->name;
                                $start_date = $entity->start_date;
                            }
                        }

                        if (!empty($institution)) { 
                            foreach ($institution as $key => $value) {
                                $institution_name = $value->institution->name;
                                $institution_code = $value->institution->code;
                                $institution_classes = [];
                            }
                        }

                        $body = [   
                            'education_system_name' => !empty($education_system_name) ? $education_system_name : NULL,
                            'education_level_name' => !empty($education_level_name) ? $education_level_name : NULL,
                            'education_cycle_name' => !empty($education_cycle_name) ? $education_cycle_name : NULL,
                            'education_programme_code' => !empty($education_programme_code) ? $education_programme_code : NULL,
                            'education_programme_name' => !empty($education_programme_name) ? $education_programme_name : NULL,
                            'institution_id' => !empty($entity->institution_id) ? $entity->institution_id : NULL,
                            'institution_name' => !empty($institution_name) ? $institution_name : NULL,
                            'institution_code' => !empty($institution_code) ? $institution_code : NULL,
                            'institution_grade_id' => !empty($lastInsertId) ? $lastInsertId : NULL,
                            'institution_programme_grade_subjects_id' => !empty($institutionProgramGradeSubjectID) ? $institutionProgramGradeSubjectID : NULL,
                            'institution_subject_name' => !empty($subject) ? $subject : NULL,
                            'start_date' => !empty($start_date) ? date("d-m-Y", strtotime($start_date)) : NULL
                        ];

                        $Webhooks = TableRegistry::get('Webhook.Webhooks');
                            if ($this->Auth->user()) {
                                $Webhooks->triggerShell('programme_create', ['username' => $username],$body);
                            }
                        } 
                    }
                }
                return true;
            }
        } else {
            $model->Alert->error($this->aliasField('noGradeSelected'));
            return false;
        }
    } else {
        $model->Alert->error($this->aliasField('noGradeSelected'));
        return false;
    }
};

if (empty($errors) || count($errors)==1) {

    $educationGradeId = ($data['grades']['education_grade_id'] =='')
    ?0:$data['grades']['education_grade_id'];
    $existingGradeCount = $this->find()
    ->select([$this->EducationGrades->aliasField('name')])
    ->contain([$this->EducationGrades->alias()])
    ->where([
        $this->EducationGrades->aliasField('education_programme_id') => $entity->programme,
        $this->aliasField('education_grade_id') => $educationGradeId,
        $this->aliasField('institution_id') => $entity->institution_id
    ])
    ->count();

    if ($existingGradeCount) {
        $this->Alert->warning($this->aliasField('gradesAlreadyAdded'));
        $event->stopPropagation();
        return $this->controller->redirect($this->url('index'));
    } else {
        return $process;
    }
}
}

public function editBeforeSave(Event $event, Entity $entity, 
    ArrayObject $data, 
    ArrayObject $extra
){
        // POCOR 5001    

    if (count($data['grades']['education_grade_subject_id']) > 0
) {
        $gradeSubjectEntities = $data['grades']['education_grade_subject_id'];
    $createdUserId = $this->Session->read('Auth.User.id');            
    $institutionClassGrades = TableRegistry::get('InstitutionClassGrades')
    ->find()->select([
        'InstitutionClassGrades.education_grade_id',
        'InstitutionClassGrades.institution_class_id',
        'InstitutionClasses.academic_period_id',
        'InstitutionClasses.institution_id'
    ])
    ->innerJoin(['InstitutionClasses' => 'institution_classes'],
        [                               
            'InstitutionClasses.id = InstitutionClassGrades.institution_class_id',
        ])
    ->where([
        'InstitutionClassGrades.education_grade_id'=>$entity->education_grade->id,
        'InstitutionClasses.institution_id = '.$entity->institution_id,
    ])
    ->first();

    $gradeSubjectEntities = array_values(
        array_filter($gradeSubjectEntities)
    );

    foreach($gradeSubjectEntities as $gradeSubjectId){ 

        if($gradeSubjectId > 0){
            $institutionProgramGradeSubject = TableRegistry::get('InstitutionProgramGradeSubjects');
            $gradeSubject = $institutionProgramGradeSubject->newEntity();

            $gradeSubject->institution_grade_id = $entity->id;
            $gradeSubject->education_grade_subject_id = $gradeSubjectId;
            $gradeSubject->education_grade_id = $entity->education_grade->id;                    
            $gradeSubject->institution_id = $entity->institution_id;
            $gradeSubject->created_user_id = $createdUserId;
            $today = new DateTime();
            $gradeSubject->created = $today->format('Y-m-d H:i:s');

            $institutionProgramGradeSubject->save($gradeSubject);   
        }
    }

    $academicPeriodId = $institutionClassGrades->InstitutionClasses['academic_period_id'];

    if (!empty($institutionClassGrades)) {
            /**
             * get the list of education_grade_id from the education_grades array
             */           
            $grades = $institutionClassGrades->education_grade_id;
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            /**
             * from the list of grades, find the list of subjects group by grades in (education_grades_subjects) where visible = 1
             */
            $educationGradeSubjects = $EducationGrades
            ->find()
            ->contain(['EducationSubjects' => function ($query) use ($grades) {
                return $query
                ->join([
                    [
                        'table' => 'education_grades_subjects',
                        'alias' => 'GradesSubjects',
                        'conditions' => [
                            'GradesSubjects.education_grade_id IN' => $grades,
                            'GradesSubjects.education_subject_id = EducationSubjects.id',
                            'GradesSubjects.visible' => 1
                        ]
                    ]
                ]);
            }])
            ->where([
                'EducationGrades.id IN' => $grades,
                'EducationGrades.visible' => 1
            ])
            ->toArray();

            unset($EducationGrades);
            unset($grades);

            $educationSubjects = [];
            
            if (count($educationGradeSubjects) > 0) {

                foreach ($educationGradeSubjects as $gradeSubject) {

                    foreach ($gradeSubject->education_subjects as $subject) {

                        if(in_array($subject->id, $gradeSubjectEntities)){

                            if (!isset($educationSubjects[$gradeSubject->id.'_'.$subject->id])) {
                                $educationSubjects[$gradeSubject->id.'_'.$subject->id] = [
                                    'id' => $subject->id,
                                    'education_grade_id' => $gradeSubject->id,
                                    'name' => $subject->name
                                ];
                            }
                            
                        }
                        
                    }
                    
                    unset($subject);
                    
                }
                
                unset($gradeSubject);
            }
            
            unset($educationGradeSubjects);

            if (!empty($educationSubjects)) {
                /**
                 * for each education subjects, find the primary key of institution_classes using (entity->academic_period_id and institution_id and education_subject_id)
                 */
                $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
                $institutionSubjects = $InstitutionSubjects->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'education_subject_id'
                ])
                ->where([
                    $InstitutionSubjects->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionSubjects->aliasField('institution_id') => $entity->institution_id,
                    $InstitutionSubjects->aliasField('education_subject_id').' IN' => array_column($educationSubjects, 'id')
                ])
                ->toArray();
                $institutionSubjectsIds = [];
                
                foreach ($institutionSubjects as $key => $value) {
                    $institutionSubjectsIds[$value][] = $key;
                }

                unset($institutionSubjects);

                /**
                 * using the list of primary keys, search institution_class_subjects (InstitutionClassSubjects) to check for existing records
                 * if found, don't insert,
                 * else create a record in institution_subjects (InstitutionSubjects)
                 * and link to the subject in institution_class_subjects (InstitutionClassSubjects) with status 1
                 */
                $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
                $newSchoolSubjects = [];

                foreach ($educationSubjects as $key => $educationSubject) {
                    $existingSchoolSubjects = false;
                    
                    if (array_key_exists($key, $institutionSubjectsIds)) {
                        $existingSchoolSubjects = $InstitutionClassSubjects->find()
                        ->where([
                            $InstitutionClassSubjects->aliasField('institution_class_id') => $institutionClassGrades->institution_class_id,
                            $InstitutionClassSubjects->aliasField('institution_class_id').' IN' => $institutionSubjectsIds[$key],
                        ])
                        ->select(['id'])
                        ->first();
                    }
                    
                    if (!$existingSchoolSubjects) {
                        $newSchoolSubjects[$key] = [
                            'name' => $educationSubject['name'],
                            'institution_id' => $entity->institution_id,
                            'education_grade_id' => $educationSubject['education_grade_id'],
                            'education_subject_id' => $educationSubject['id'],
                            'academic_period_id' => $academicPeriodId,
                            'class_subjects' => [
                                [
                                    'status' => 1,
                                    'institution_class_id' => $institutionClassGrades->institution_class_id
                                ]
                            ]
                        ];
                    }
                }

                if (!empty($newSchoolSubjects)) {
                    $newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
                    foreach ($newSchoolSubjects as $subject) {
                        $institutionProgramGradeSubjects = 
                        TableRegistry::get('InstitutionProgramGradeSubjects')
                        ->find('list')
                        ->where(['InstitutionProgramGradeSubjects.education_grade_id' => $subject->education_grade_id,
                            'InstitutionProgramGradeSubjects.education_grade_subject_id' => $subject->education_subject_id,
                            'InstitutionProgramGradeSubjects.institution_id' => $subject->institution_id
                        ])
                        ->count(); 
                        
                        if($institutionProgramGradeSubjects > 0){
                            $InstitutionSubjects->save($subject);
                        }
                    }
                    unset($subject);
                }
                unset($newSchoolSubjects);
                unset($InstitutionSubjects);
                unset($InstitutionClassSubjects);
            }
        }

    }
        //POCOR-5433-- start
    if(!empty($this->controllerAction) && ($this->controllerAction == 'Programmes')) {
        $bodyData = $this->find('all',
            [ 'contain' => [
                'Institutions',
                'Institutions.InstitutionClasses',
                'EducationGrades',
                'EducationGrades.EducationProgrammes',
                'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels',
                'EducationGrades.EducationProgrammes.EducationCycles',
                'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'
            ],
        ])->where([
            $this->aliasField('id') => $entity->id
        ]);
        
        $institutionProgramGradeSubject = TableRegistry::get('InstitutionProgramGradeSubjects');
        $programmeSubjects = $institutionProgramGradeSubject->find()
        ->select('education_grade_subject_id')
        ->where([
            'institution_grade_id IN' => $entity->id,
        ])->all();

        $program_subject = [];
        if (!empty($programmeSubjects)) {
           foreach ($programmeSubjects as $key => $value) {
            $program_subject[] = $value['education_grade_subject_id'];
        }
    }
    $educationSubject = TableRegistry::get('Education.EducationSubjects');
    if(!empty($program_subject)){
        $educationSubjectData = $educationSubject->find()->where([
            'id IN' => $program_subject
        ])->all();
    }
    
    $edu_subjects = [];
    if (!empty($educationSubjectData)) {
        foreach ($educationSubjectData as $key => $value) { 
           $edu_subjects[] = $value['code'] . " _ " . $value['name'];
       }
   }
   if (!empty($bodyData)) { 
    foreach ($bodyData as $key => $value) {
        $education_system_name = $value->education_grade->education_programme->education_cycle->education_level->education_system->name;
        $education_level_name = $value->education_grade->education_programme->education_cycle->education_level->name;
        $education_cycle_name = $value->education_grade->education_programme->education_cycle->name;
        $education_programme_code = $value->education_grade->education_programme->code;
        $education_programme_name = $value->education_grade->education_programme->name;
        $start_date = $entity->start_date;
        $institution_id = $value->institution->id;
        $institution_name = $value->institution->name;
        $institution_code = $value->institution->code;
    }
}
$body = array();

$body = [   
    'education_system_name' => !empty($education_system_name) ? $education_system_name : NULL,
    'education_level_name' => !empty($education_level_name) ? $education_level_name : NULL,
    'education_cycle_name' => !empty($education_cycle_name) ? $education_cycle_name : NULL,
    'education_programme_code' => !empty($education_programme_code) ? $education_programme_code : NULL,
    'education_programme_name' => !empty($education_programme_name) ? $education_programme_name : NULL,
    'institution_id' => !empty($institution_id) ? $institution_id : NULL,
    'institution_name' => !empty($institution_name) ? $institution_name : NULL,
    'institution_code' => !empty($institution_code) ? $institution_code : NULL,
    'institution_grade_id' => $entity->id,
    'institution_programme_grade_subjects_id' => !empty($program_subject) ? $program_subject : NULL,
    'institution_subject_name' => !empty($edu_subjects) ? $edu_subjects : NULL,
    'start_date' => !empty($start_date) ? date("d-m-Y", strtotime($start_date)) : NULL
];

$Webhooks = TableRegistry::get('Webhook.Webhooks');
if ($this->Auth->user()) {
    $Webhooks->triggerShell('programme_update', ['username' => $username], $body);
}   
        //POCOR-5433-- end
}
}

    // POCOR 5001
public function beforeDelete(Event $event, Entity $entity) {

        // Delete Institution Program Grade Subjects
    TableRegistry::get('InstitutionProgramGradeSubjects')
    ->deleteAll(['institution_grade_id' => $entity->id,
        'education_grade_id' => $entity->education_grade_id
    ]);
    if(!empty($this->controllerAction) && ($this->controllerAction == 'Programmes')) {
        $bodyData = $this->find('all',
            [ 'contain' => [
                'EducationGrades'
            ],
        ])->where([
            $this->aliasField('id') => $entity->id
        ]);

        $body = array();

        $body = [  
            'institution_grades_id' => !empty($entity->id) ? $entity->id : NULL,
        ];
        if($this->action == 'remove') {
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $username = $this->Auth->user()['username']; 
                $Webhooks->triggerShell('programme_delete', ['username' => $username], $body);
            } 
        }
        
    }
}

public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
{
    $Institution = TableRegistry::get('Institution.Institutions');
    $institution = $Institution->find()->where([$Institution->aliasField($Institution->primaryKey()) => $this->institutionId])->first();

    if (empty($institution->date_opened)) {
        $institution->date_opened = new Time('01-01-1970');
        $Institution->save($institution);
    }

    $dateOpened = $institution->date_opened;
    try{
        $yearOpened = 1970;
        if (!empty($institution->year_opened)) {
            $yearOpened = $institution->year_opened;
        }
        $year = $dateOpened->format('Y');

        if ($yearOpened != $year) {
            $month = $dateOpened->format('m');
            $day = $dateOpened->format('d');
            $dateOpened = new Time($yearOpened.'-'.$month.'-'.$day);
            $institution->date_opened = $dateOpened;
            $Institution->save($institution);
        }

        $formatDate = $dateOpened->format('d-m-Y');
    } catch (\Exception $e) {
        $institution->date_opened = new Time('01-01-1970');
        $Institution->save($institution);
        $dateOpened = $institution->date_opened;
    }

    $this->fields['start_date']['value'] = isset($entity->start_date) ? $entity->start_date : $dateOpened;
    $this->fields['start_date']['date_options']['startDate'] = $dateOpened->format('d-m-Y');
    $this->fields['end_date']['date_options']['startDate'] = $dateOpened->format('d-m-Y');
}

public function addOnChangeLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
{
    $data[$this->alias()]['programme'] = 0;
}

/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
{
    $level = $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
    $programme = $entity->education_grade->education_programme;
    $this->fields['level']['attr']['value'] = $level;

    $this->fields['programme']['attr']['value'] = $programme->cycle_programme_name;
    $this->fields['programme']['value'] = $programme->id;
    $this->fields['education_grade_id']['attr']['value'] = $entity->education_grade->name;

    $Institution = TableRegistry::get('Institution.Institutions');
    $institution = $Institution->find()->where([$Institution->aliasField($Institution->primaryKey()) => $this->institutionId])->first();
    $this->fields['start_date']['date_options']['startDate'] = $institution->date_opened->format('d-m-Y');
    $this->fields['end_date']['date_options']['startDate'] = $institution->date_opened->format('d-m-Y');
}

/******************************************************************************************************************
**
** specific field methods
**
******************************************************************************************************************/
public function onGetLevel(Event $event, Entity $entity)
{
    $level = $entity->education_grade->education_programme->education_cycle->education_level->system_level_name;
    return $level;
}

public function onGetProgramme(Event $event, Entity $entity)
{
    return $programme = $entity->education_grade->education_programme->cycle_programme_name;;
}

public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request)
{
    if ($action == 'add') {
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $levelOptions = $EducationLevels->find('list', ['valueField' => 'system_level_name'])
        ->find('visible')
        ->find('order')
        ->toArray();
        $attr['empty'] = true;
        $attr['options'] = $levelOptions;
        $attr['onChangeReload'] = 'changeLevel';
    } else if ($action == 'edit') {
        $attr['type'] = 'readonly';
    }
    return $attr;
}

public function onUpdateFieldProgramme(Event $event, array $attr, $action, Request $request)
{
    if ($action == 'add') {
        $attr['empty'] = true;
        $attr['options'] = [];

        if ($this->request->is(['post', 'put'])) {
            $levelId = $this->request->data($this->aliasField('level'));
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            $query = $EducationProgrammes->find('list', ['valueField' => 'cycle_programme_name'])
            ->find('visible')
            ->find('order')
            ->matching('EducationCycles', function($q) use ($levelId) {
                return $q->find('visible')->where(['EducationCycles.education_level_id' => $levelId]);
            });

            $programmeOptions = $query->toArray();
            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeProgramme';
        }

    } else if ($action == 'edit') {
        $attr['type'] = 'readonly';
    }
    return $attr;
}

public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
{
    if ($action == 'add') {
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.Programmes/grades';

        if ($request->is(['post', 'put'])) {
            $programmeId = $request->data($this->aliasField('programme'));

            if (empty($programmeId)) {
                $programmeId = 0;
            }

            $data = $this->EducationGrades->find('list')
            ->find('visible')
            ->find('order')
            ->where(['EducationGrades.education_programme_id' => $programmeId])
            ->toArray();

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $exists = $this->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
            ->where([$this->aliasField('institution_id') => $institutionId])
            ->toArray();

            $attr['data'] = $data;
            $attr['exists'] = $exists;
        }

    } else if ($action == 'edit') {
        $attr['type'] = 'readonly';
    }
    return $attr;
}

public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
{       
    if ($action == 'add') {
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.Programmes/subjects';

        if ($request->is(['post', 'put'])) {

            $educationGradeId = $request->data($this->aliasField('grades.education_grade_id'));

            if (!empty($educationGradeId)) {

                $existingSubjectsInGrade = 
                TableRegistry::get('Education.EducationGradesSubjects')
                ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'education_subject_id'
                ])
                ->where(['EducationGradesSubjects.education_grade_id' => $educationGradeId])
                ->toArray();

                $subjectQuery = TableRegistry::get('Education.EducationSubjects')
                ->find()
                //->find('visible') //POCOR-5931
                ->find('order');

                // only show subjects that have been added in the grade
                if (!empty($existingSubjectsInGrade)) {
                    $subjectQuery->where([
                        'EducationSubjects.id IN ' => $existingSubjectsInGrade
                    ]);
                }
                
                $subjectOptions = $subjectQuery->toArray();
            }

            $attr['data'] = $subjectOptions;
        }
    } else if ($action == 'edit') {
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.Programmes/subjects';
        $programmeId = $this->paramsDecode($this->request->pass[1])['id'];

        if (!empty($programmeId)) {                   

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $institutionGrade = TableRegistry::get('InstitutionGrades')
            ->find()
            ->where(['InstitutionGrades.id' => $programmeId,
                'InstitutionGrades.institution_id' => $institutionId
            ])
            ->first();

            $existingSubjectsInGrade = 
            TableRegistry::get('Education.EducationGradesSubjects')
            ->find('list', [
                'keyField' => 'education_subject_id',
                'valueField' => 'education_subject_id'
            ])
            ->where(['EducationGradesSubjects.education_grade_id' => $institutionGrade->education_grade_id])
            ->toArray();

            $subjectQuery = TableRegistry::get('Education.EducationSubjects')
            ->find()
            //->find('visible') //POCOR-5931
            ->find('order');

                    // only show subjects that have been added in the grade
            if (!empty($existingSubjectsInGrade)) {
                $subjectQuery->where([
                    'EducationSubjects.id IN' => $existingSubjectsInGrade
                ]);
            }

            $subjectOptions = $subjectQuery->toArray();

            $institutionProgramGradeSubjects = 
            TableRegistry::get('InstitutionProgramGradeSubjects')
            ->find('list', [
                'keyField' => 'education_grade_subject_id',
                'valueField' => 'education_grade_subject_id'
            ])
            ->where(['InstitutionProgramGradeSubjects.education_grade_id' => $institutionGrade->education_grade_id,
                'InstitutionProgramGradeSubjects.institution_grade_id' => $programmeId
            ])
            ->hydrate(false)
            ->toArray();                    
        }

        $attr['data'] = $subjectOptions;
        $attr['exists'] = $institutionProgramGradeSubjects;
    }else if ($action == 'view') {
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.Programmes/subjects';
        $programmeId = $this->paramsDecode($this->request->pass[1])['id'];

        if (!empty($programmeId)) {                   

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $institutionGrade = TableRegistry::get('InstitutionGrades')
            ->find()
            ->where(['InstitutionGrades.id' => $programmeId,
                'InstitutionGrades.institution_id' => $institutionId
            ])
            ->first();

            $existingSubjectsInGrade = 
            TableRegistry::get('InstitutionProgramGradeSubjects')
            ->find('list', [
                'keyField' => 'education_grade_subject_id',
                'valueField' => 'education_grade_subject_id'
            ])
            ->where(['InstitutionProgramGradeSubjects.education_grade_id' => $institutionGrade->education_grade_id,
                'InstitutionProgramGradeSubjects.institution_grade_id' => $programmeId
            ])
            ->hydrate(false)
            ->toArray(); 

                    // only show subjects that have been added in the grade
            if (!empty($existingSubjectsInGrade)) {
                $subjectQuery = TableRegistry::get('Education.EducationSubjects')
                ->find()
                //->find('visible') //POCOR-5931
                ->find('order');
                $subjectQuery->where([
                    'EducationSubjects.id IN' => $existingSubjectsInGrade
                ]);
                $subjectOptions = $subjectQuery->toArray();
            }   
            
        }

        $attr['data'] = $subjectOptions;
    }
    return $attr;
}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
public function getGradeOptionsForIndex($institutionsId, $academicPeriodId, $listOnly=true)
{
        /**
         * PHPOE-2090, changed to find by AcademicPeriod function in PeriodBehavior.php
         */
        /**
         * PHPOE-2132, changed to find by AcademicPeriod function in PeriodBehavior.php with extra parameter to exclude finding grades within date range.
         * Common statements with getGradeOptions() were moved to _gradeOptions().
         */
        $query = $this->find('all')
        ->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId, 'beforeEndDate' => $this->aliasField('start_date')]);
        return $this->_gradeOptions($query, $institutionsId, $listOnly);
    }

    public function getGradeOptions($institutionsId, $academicPeriodId, $listOnly=true)
    {
        /**
         * PHPOE-2090, changed to find by AcademicPeriod function in PeriodBehavior.php
         */
        /**
         * PHPOE-2132, Common statements with getGradeOptionsForIndex() were moved to _gradeOptions().
         */

        // Get the current time.
        $currTime = Time::now();

        $query = $this->find('all')
        ->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId])
        ->where([
            'OR' => [
                [$this->aliasField('end_date').' IS NULL'],
                [$this->aliasField('end_date') . " >= '" . $currTime->format('Y-m-d') . "'"]
            ]
        ])
        ;
        return $this->_gradeOptions($query, $institutionsId, $listOnly);
    }

    private function _gradeOptions(Query $query, $institutionsId, $listOnly)
    {
        $query->contain(['EducationGrades'])
        ->where(['InstitutionGrades.institution_id = ' . $institutionsId])
        ->order(['EducationGrades.education_programme_id', 'EducationGrades.order']);
        $data = $query->toArray();
        
        if($listOnly) {
            $list = [];
            foreach ($data as $key => $obj) {
                $list[$obj->education_grade->id] = $obj->education_grade->programme_grade_name;
            }
            return $list;
        } else {
            return $data;
        }
    }

    /**
     * Used by InstitutionClassesTable & InstitutionSubjectsTable.
     * This function resides here instead of inside AcademicPeriodsTable because the first query is to get 'start_date' and 'end_date'
     * of registered Programmes in the Institution.
     * @param  integer $model                    [description]
     * @param  array   $conditions               [description]
     * @return [type]                            [description]
     */
    public function getAcademicPeriodOptions($Alert, $conditions=[])
    {
        $query = $this->find('all')
        ->select(['start_date', 'end_date'])
        ->where($conditions);
        
        $result = $query->toArray();
        $startDateObject = null;
        
        foreach ($result as $key=>$value) {
            $startDateObject = $this->getLowerDate($startDateObject, $value->start_date);
        }
        
        if (is_object($startDateObject)) {
            $startDate = $startDateObject->toDateString();
        } else {
            $startDate = $startDateObject;
        }

        $endDateObject = null;
        
        foreach ($result as $key=>$value) {
            $endDateObject = $this->getHigherDate($endDateObject, $value->end_date);
        }
        
        if (is_object($endDateObject)) {
            $endDate = $endDateObject->toDateString();
        } else {
            $endDate = $endDateObject;
        }

        $conditions = array_merge(array('end_date IS NULL'), $conditions);
        $query = $this->find('all')
        ->where($conditions)
        ;
        $nullDate = $query->count();

        $academicPeriodConditions = [];
        $academicPeriodConditions['parent_id >'] = 0;
        $academicPeriodConditions['end_date >='] = $startDate;
        
        if($nullDate == 0) {
            $academicPeriodConditions['start_date <='] = $endDate;
        } else {
            $academicPeriodConditions['end_date >='] = $startDate;
        }

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $query = $AcademicPeriods->find('list')
        ->select(['id', 'name'])
        ->where($academicPeriodConditions)
        ->order('`order`')
        ;
        $result = $query->toArray();
        
        if (empty($result)) {
            $Alert->warning('Institution.Institutions.noProgrammes');
            return [];
        } else {
            return $result;
        }
    }

    /**
     * Used by $this->getAcademicPeriodOptions()
     * @param  Time $a Time object
     * @param  Time $b Time object
     * @return Time    Time object
     */
    private function getLowerDate($a, $b)
    {
        if (is_null($a)) {
            return $b;
        }
        
        if (is_null($b)) {
            return $a;
        }
        
        return (($a->toUnixString() <= $b->toUnixString()) ? $a : $b);
    }

    /**
     * Used by $this->getAcademicPeriodOptions()
     * @param  Time $a Time object
     * @param  Time $b Time object
     * @return Time    Time object
     */
    private function getHigherDate($a, $b)
    {
        if (is_null($a)) {
            return $b;
        }
        
        if (is_null($b)) {
            return $a;
        }
        
        return (($a->toUnixString() >= $b->toUnixString()) ? $a : $b);
    }

    public function findEducationGradeInCurrentInstitution(Query $query, array $options)
    {
        $academicPeriodId = (array_key_exists('academic_period_id', $options))? $options['academic_period_id']: null;
        $institutionId = (array_key_exists('institution_id', $options))? $options['institution_id']: null;

        $query->contain('EducationGrades.EducationProgrammes');
        $query->where([
            $this->aliasField('institution_id') => $institutionId
        ]);
        
        if (!is_null($academicPeriodId)) {
            $query->find('academicPeriod', ['academic_period_id' => $academicPeriodId]);
        }
        
        $query->group([$this->aliasField('education_grade_id')]);

        return $query;
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $educationGradeId = $entity->education_grade_id;
        $entity->name = $EducationGrades->get($educationGradeId)->name;
        $institutionId = $entity->institution_id;

        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $associatedStudentRecordsCount = $InstitutionStudents->find()
        ->where([
            $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
            $InstitutionStudents->aliasField('institution_id') => $institutionId
        ])
        ->count();
        $extra['associatedRecords'][] = ['model' => 'InstitutionStudents', 'count' => $associatedStudentRecordsCount];

        // to get the institution_class_id related to the education_grade_id
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $associatedClassObj = $InstitutionClassGrades->find()
        ->where([
            $InstitutionClassGrades->aliasField('education_grade_id') => $educationGradeId,
        ])
        ->toArray();

        // will check if the institution_class_id are in the Institutions.
        $associatedClassCount = 0;
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        
        foreach ($associatedClassObj as $key => $obj) {
            $institutionsClassId = $obj['institution_class_id'];
            $count = $InstitutionClasses->find()
            ->where([
                $InstitutionClasses->aliasField('id') => $institutionsClassId,
                $InstitutionClasses->aliasField('institution_id') => $institutionId
            ])
            ->count();
            
            if ($count > 0) {
                $associatedClassCount++;
            }
        }

        $extra['associatedRecords'][] = ['model' => 'InstitutionClasses', 'count' => $associatedClassCount];
    }
}
