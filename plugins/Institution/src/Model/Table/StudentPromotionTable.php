<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Datasource\ResultSetInterface;
use Cake\Validation\Validator;
use Cake\Log\Log;
use App\Model\Table\AppTable;
use Cake\Datasource\ConnectionManager;
class StudentPromotionTable extends AppTable
{
    private $InstitutionGrades = null;
    private $institutionId = null;
    private $currentPeriod = null;
    private $statuses = []; // Student Status

    public function initialize(array $config): void
    {
        $this->setTable('institution_students');
        parent::initialize($config);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('Institution.ClassStudents');
        $this->addBehavior('ControllerAction.QueryString');
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'student_create',
                'entity_delete' => 'student_delete',
                'entity_update' => 'student_update',
                'table_alias' => 'Institution.InstitutionStudents',
                'contain' => []
            ]
        ); // for webhook
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('from_academic_period_id')
            ->requirePresence('next_academic_period_id')
            ->requirePresence('grade_to_promote')
            ->requirePresence('class')
            ->allowEmpty('education_grade_id');
            /*->allowEmpty('education_grade_id', function ($context) {
                $studentStatusId = (!empty($context['data']['student_status_id']))? $context['data']['student_status_id']: '';
                return ($studentStatusId != $this->statuses['PROMOTED']);
            });*/
    }

    public function validationRemoveStudentPromotionValidation(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        return $validator
            ->requirePresence('from_academic_period_id', false)
            ->requirePresence('next_academic_period_id', false)
            ->requirePresence('grade_to_promote', false)
            ->requirePresence('class', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents'),
            TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents')
        ];
        $this->dispatchEventToModels('Model.Students.afterSave', [$entity], $this, $listeners);
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona=false)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
        $Navigation->substituteCrumb('Promotion', 'Students', $url);
        $Navigation->addCrumb('Promotion');
    }

    public function beforeAction(EventInterface $event)
    {
        //$params = $this->ControllerAction->getQueryString();
        $params = $this->getQueryString();
        if(!empty($params)) {
            //$this->institutionId = $this->ControllerAction->getQueryString('institution_id');
            $this->institutionId = $this->getQueryString('institution_id');
            $encodedQueryParams = $this->ControllerAction->paramsEncode($params);
        } else {
            $encodedQueryParams = $this->request->getParam('pass')[1];
            $this->institutionId = $this->paramsDecode($encodedQueryParams)['institution_id'];
        }
        $this->InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $institutionClassTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $this->institutionClasses = $institutionClassTable->find('list')
            ->where([$institutionClassTable->aliasField('institution_id') => $this->institutionId])
            ->toArray();
        $selectedPeriod = $this->AcademicPeriods->getCurrent();
        $this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
        $this->statuses = $this->StudentStatuses->findCodeList();
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        // To clear the query string from the previous page to prevent logic conflict on this page
        $this->request = $this->request->withQueryParams([]);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $this->fields = [];

        // all $this->ControllerAction->field() MUST set at addAfterAction
        $this->ControllerAction->field('from_academic_period_id', [
            'attr' => [
                'label' => __('From Academic Period') // POCOR-9399
            ],
            'entity' => $entity
        ]);
        $this->ControllerAction->field('next_academic_period_id', [
            'attr' => [
                'label' => __('To Academic Period') // POCOR-9399
            ],
            'entity' => $entity
        ]);
        $this->ControllerAction->field('grade_to_promote', [
            'attr' => [
                'label' => __('From Grade') // POCOR-9399
            ],
            'entity' => $entity
        ]);
        $this->ControllerAction->field('class', [
            'entity' => $entity
        ]);
        $this->ControllerAction->field('student_status_id', [
            'attr' => [
                'label' => __('Status') // POCOR-9399
            ],
            'entity' => $entity
        ]);
        $this->ControllerAction->field('education_grade_id', [
            'attr' => [
                'label' => __('To Grade') // POCOR-9399
            ],
            'entity' => $entity
        ]);

        $this->ControllerAction->field('next_class', [
            'attr' => [
                'label' => __('Next Class') // POCOR-9399
            ],
            'entity' => $entity
        ]);

        $this->ControllerAction->field('students', [
           'entity' => $entity
        ]);
        // end

        $this->ControllerAction->setFieldOrder([
            'from_academic_period_id',
            'next_academic_period_id',
            'grade_to_promote',
            'class',
            'student_status_id',
            'education_grade_id',
            'next_class',
            'students'
        ]);
    }

    public function onUpdateFieldFromAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $selectedAcademicPeriodId = $currentData['from_academic_period_id'];

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->AcademicPeriods->get($selectedAcademicPeriodId)->name;
                break;

            case 'add':
                $conditions = [
                    $this->AcademicPeriods->aliasField('order').' >= ' => $this->currentPeriod->order
                ];
                $academicPeriodList = $this->AcademicPeriods->getYearList([
                    'conditions' => $conditions,
                    'isEditable' => true
                ]);

                $attr['type'] = 'select';
                $attr['options'] = $academicPeriodList;
                $attr['onChangeReload'] = 'changeFromPeriod';
                break;

            default:
                // no implementation
                break;
        }
        return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                if ($currentData->has('next_academic_period_id')) {
                    $academicPeriodData = $this->AcademicPeriods
                        ->find()
                        ->where([$this->AcademicPeriods->aliasField($this->AcademicPeriods->getPrimaryKey()) => $currentData->next_academic_period_id])
                        ->select([$this->AcademicPeriods->aliasField('name')])
                        ->first();
                    $academicPeriodName = (!empty($academicPeriodData))? $academicPeriodData['name']: '';
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = (!empty($academicPeriodName))? $academicPeriodName: $this->getMessage($this->aliasField('noAvailableAcademicPeriod'));
                break;

            case 'add':
                $entity = $attr['entity'];
                $selectedPeriodId = $entity->has('from_academic_period_id') ? $entity->from_academic_period_id : null;

                $periodOptions = [];
                if (!empty($selectedPeriodId) && $selectedPeriodId != -1) {
                    $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
                    $conditions = [
                        $this->AcademicPeriods->aliasField('order').' < ' => $selectedPeriod->order,
                        $this->AcademicPeriods->aliasField('id').' <> ' => $selectedPeriodId
                    ];
                    $periodOptions = $this->AcademicPeriods->getYearList([
                        'conditions' => $conditions,
                        'isEditable' => true
                    ]);

                    $attr['type'] = 'select';
                }

                $attr['onChangeReload'] = 'changeNextPeriod';
                $attr['options'] = $periodOptions;
                break;

            default:
                // no implementation
                break;
        }

        return $attr;
    }

    public function onUpdateFieldNextGrade(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // used for reconfirm
        if ($action == 'reconfirm') {
            $sessionKey = $this->getRegistryAlias() . '.confirm';
            if ($this->Session->check($sessionKey)) {
                $currentData = $this->Session->read($sessionKey);
            }

            if ($currentData->has('education_grade_id')) {
                $gradeData = $this->EducationGrades
                    ->find()
                    ->where([$this->EducationGrades->aliasField($this->EducationGrades->getPrimaryKey()) => $currentData->education_grade_id])
                    ->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
                    ->first();
                $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));

                // to get the notEnrolled message for the reconfirm page
                $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($currentData->grade_to_promote);

                // list of grades available in the institution
                $institutionId = $this->institutionId;
                $listOfInstitutionGrades = $this->getListOfInstitutionGrades($institutionId);

                if ($currentData->student_status_id == $this->statuses['GRADUATED'] && array_key_exists(key($nextGrades), $listOfInstitutionGrades)) {
                    $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('notEnrolled'));
                }
                // end of getting the notEnrolled message

            } else if ($currentData->student_status_id == $this->statuses['REPEATED']) {
                $academicPeriodId = $currentData->next_academic_period_id ? $currentData->next_academic_period_id : null;

                $gradeData = $this->EducationGrades->getNextAvailableEducationGradesForRepeated($currentData->grade_to_promote, $academicPeriodId);
                $gradeName = (!empty($gradeData))? ($gradeData->programme. ' - ' . $gradeData->grade_name): $this->getMessage($this->aliasField('noAvailableGrades'));
            }

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = (!empty($gradeName))? $gradeName: '';
        }
        return $attr;
    }

    public function onUpdateFieldGradeToPromote(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                if ($currentData->has('grade_to_promote')) {
                    $gradeData = $this->EducationGrades
                        ->find()
                        ->where([$this->EducationGrades->aliasField($this->EducationGrades->getPrimaryKey()) => $currentData->grade_to_promote])
                        ->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
                        ->first();
                    $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = (!empty($gradeName))? $gradeName: '';
                break;

            case 'add':
                $entity = $attr['entity'];
                $selectedPeriod = $entity->has('from_academic_period_id') ? $entity->from_academic_period_id : null;
                $InstitutionTable = $this->Institutions;
                $InstitutionGradesTable = $this->InstitutionGrades;
                //echo $selectedPeriod;die;
                $gradeOptions = [];
                if (!empty($selectedPeriod) && $selectedPeriod != -1) {
                    $institutionId = $this->institutionId;
                    $statuses = $this->statuses;
                    $gradeOptions = $InstitutionGradesTable
                        ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
                        //->contain(['EducationGrades.EducationProgrammes', 'EducationGrades.EducationStages'])
                        ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems', 'EducationGrades.EducationStages'])
                        ->where([
                            'EducationSystems.academic_period_id' => $selectedPeriod
                        ])
                        ->where([$InstitutionGradesTable->aliasField('institution_id') => $institutionId])
                        //->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                        ->order(['EducationStages.order', 'EducationGrades.order'])
                        ->toArray();

                    $attr['type'] = 'select';
                    $selectedGrade = null;
                    $GradeStudents = $this;
                    $counter = 0;

                    $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
                        'selectOption' => false,
                        'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                        'callable' => function($id) use ($GradeStudents, $institutionId, $selectedPeriod, $statuses) {
                            $gradeStudentsCounter = $GradeStudents
                                ->find()
                                ->where([
                                    $GradeStudents->aliasField('institution_id') => $institutionId,
                                    $GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
                                    $GradeStudents->aliasField('education_grade_id') => $id,
                                    $GradeStudents->aliasField('student_status_id') => $statuses['CURRENT']
                                ])
                                ->count();

                            return $gradeStudentsCounter;
                        }
                    ]);

                    foreach ($gradeOptions as $key=>$value) {
                        $gradeStudentsCounter = $GradeStudents
                                ->find()
                                ->where([
                                    $GradeStudents->aliasField('institution_id') => $institutionId,
                                    $GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
                                    $GradeStudents->aliasField('education_grade_id') => $key,
                                    $GradeStudents->aliasField('student_status_id') => $statuses['CURRENT']
                                ])
                                ->count();
                        $counter += $gradeStudentsCounter;
                    }
                    if ($counter == 0) {
                    $attr['attr']['value'] = "";
                 }
                }

                $attr['onChangeReload'] = 'changeGradeToPromote';
                $attr['options'] = $gradeOptions;
                break;

            default:
                // no implementation
                break;
        }

        return $attr;
    }

    public function onUpdateFieldNextClass(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                if ($currentData->has('next_class')) {
                    $InstitutionClassesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

                    $nextClass = $InstitutionClassesTable
                        ->find()
                        ->where([$InstitutionClassesTable->aliasField('id') => $currentData->next_class])
                        ->select([$InstitutionClassesTable->aliasField('name')])
                        ->first();
                    $nextClassName = (!empty($nextClass)) ? $nextClass->name : 'No available classes';
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = (!empty($nextClassName)) ? $nextClassName : '';
                break;

            case 'add':
                $requestData = [];
                $nextClasses = [];

                $InstitutionClassesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
                $entity = $attr['entity'];
                $selectedNextPeriod = $entity->has('next_academic_period_id') ? $entity->next_academic_period_id : null;
                $selectedPeriod = $request->getData['StudentPromotion']['from_academic_period_id'];
                $selectedGrade = $entity->has('grade_to_promote') ? $entity->grade_to_promote : null;
                $selectedNextGrade = $entity->has('education_grade_id') ? $entity->education_grade_id : null;
                $selectedClass = $entity->has('class') ? $entity->class : null;
                $studentStatusId = $entity->has('student_status_id') ? $entity->student_status_id : null;

                $requestData = $request->getData();
                $institutionId = $this->institutionId;
                $statuses = $this->statuses;

                if (!is_null($selectedNextPeriod) && !is_null($selectedGrade) && !is_null($selectedClass)
                    && !is_null($studentStatusId) && !is_null($institutionId) && !is_null($statuses)) {
                    if ($selectedClass !== '-1') { //Not Student Without Class
                        $InstitutionClassesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

                        //Get back classes base on status of promoted or graduated or repeated
                        if (in_array($studentStatusId, [$statuses['PROMOTED'], $statuses['GRADUATED']])) {
                            if (!is_null($selectedNextGrade)) {
                                if (in_array($studentStatusId, [$statuses['PROMOTED']])) {
                                    $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $selectedNextGrade);
                                } else {
                                    $nextClasses = $InstitutionClassesTable->find('list')
                                                ->select([
                                                    $InstitutionClassesTable->aliasField('id'),
                                                    $InstitutionClassesTable->aliasField('name')
                                                ])
                                                ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()],[
                                                  $InstitutionClassGrades->aliasField('institution_class_id = ') . $InstitutionClassesTable->aliasField('id')
                                                ])
                                                ->where([
                                                $InstitutionClassesTable->aliasField('institution_id') => $institutionId,
                                                $InstitutionClassesTable->aliasField('academic_period_id') => $selectedNextPeriod,
                                                $InstitutionClassGrades->aliasField('education_grade_id') => $selectedNextGrade
                                                ])->toArray();
                                }
                            }
                        } else if (in_array($studentStatusId, [$statuses['REPEATED']])) {
                            $gradeId = $this->Session->read('grade_id');
                            $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $gradeId);
                        }
                    }
                    /*POCOR-5733 Starts Student Without Class*/
                    else {
                        if (in_array($studentStatusId, [$statuses['PROMOTED'], $statuses['GRADUATED']])) {
                            if (!is_null($selectedNextGrade)) {
                                /*POCOR-6312 starts*/
                                $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $selectedNextGrade);
                                /*POCOR-6312 ends*/
                            }
                        } elseif (in_array($studentStatusId, [$statuses['REPEATED']])) {
                            /*POCOR-6312 starts*/
                            $gradeId = $this->Session->read('grade_id');
                            $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $gradeId);
                            /*POCOR-6312 ends*/
                        }
                    }
                    /*POCOR-5733 Ends*/
                }

                $attr['onChangeReload'] = 'changeNextClass';
                $attr['options'] = (!empty($nextClasses)) ? ['' => '-- '.__('Select Next Class').' --'] + $nextClasses : ['' => $this->getMessage('general.select.noOptions')];

                $selectedNextClass = $entity->has('next_class') ? $entity->next_class : null;

                //Change all student classes to the selected class
                if (isset($requestData['StudentPromotion'])) {
                    if (array_key_exists('students', $requestData['StudentPromotion'])) {
                        $students = $this->request->getData('StudentPromotion.students');
                        if (!empty($students)) {
                            foreach ($students as &$student) {
                                $student['next_institution_class_id'] = (!empty($selectedNextClass)) ? $selectedNextClass : '';
                            }
                            $this->request->getData()[$this->getAlias()]['students'] = $students;
                        }
                    }
                }
                break;

            default:
                // no implementation
                break;
        }
        return $attr;
    }

    public function onUpdateFieldClass(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $institutionClass = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $institutionClassId = $currentData['class'];
                if ($institutionClassId == -1) {
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = __('Students without Class');
                } else {
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = $institutionClass->get($institutionClassId)->name;
                }
                break;

            case 'add':
                $entity = $attr['entity'];
                $institutionId = $this->institutionId;
                $selectedPeriod = $entity->has('from_academic_period_id') ? $entity->from_academic_period_id : null;
                $educationGradeId = $entity->has('grade_to_promote') ? $entity->grade_to_promote : null;

                $classes = [];
                $options = ['-1' => __('Students without Class')];
                if (!empty($selectedPeriod) && $selectedPeriod != -1 && !empty($educationGradeId) && $educationGradeId != -1) {
                    $classes = $institutionClass
                        ->find('list')
                        ->leftJoinWith('ClassGrades')
                        ->where([
                            $institutionClass->aliasField('academic_period_id') => $selectedPeriod,
                            $institutionClass->aliasField('institution_id') => $institutionId,
                            'ClassGrades.education_grade_id' => $educationGradeId
                        ])
                        ->toArray();

                    $options = $options + $classes;
                    $selectedClass = $entity->has('class') ? $entity->class : null;
                    if (empty($selectedClass)) {
                        if (!empty($classes)) {
                            $selectedClass = key($classes);
                        }
                    }
                    $studentStatuses = $this->statuses;
                    $model = $this;

                    $this->advancedSelectOptions($options, $selectedClass, [
                            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
                            'callable' => function($id) use ($model, $institutionId, $selectedPeriod, $educationGradeId, $studentStatuses) {
                                if ($id == -1) {
                                    return true;
                                }
                                return $model->find()
                                    ->innerJoin(['InstitutionClassStudents' => 'institution_class_students'],
                                        [
                                            'InstitutionClassStudents.education_grade_id = '.$model->aliasField('education_grade_id'),
                                            'InstitutionClassStudents.student_id = '.$model->aliasField('student_id'),
                                            'InstitutionClassStudents.institution_id = '.$model->aliasField('institution_id'),
                                            'InstitutionClassStudents.academic_period_id = '.$model->aliasField('academic_period_id'),
                                        ]
                                    )
                                    ->where([
                                        $this->aliasField('institution_id') => $institutionId,
                                        $this->aliasField('academic_period_id') => $selectedPeriod,
                                        $this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
                                        $this->aliasField('education_grade_id') => $educationGradeId,
                                        'InstitutionClassStudents.institution_class_id' => $id
                                    ])
                                    ->count();
                            }
                        ]);
                }

                $attr['options'] = $options;
                $attr['select'] = false;
                $attr['onChangeReload'] = 'changeClass';
                break;

            default:
                // no implementation
                break;
        }
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $entity = $attr['entity'];
            $educationGradeId = $entity->has('grade_to_promote') ? $entity->grade_to_promote : null;

            $studentStatusesList = $this->StudentStatuses->find('list')->toArray();
            $statusesCode = $this->statuses;
            $options = [];
            //POCOR-7715 start
            $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $institutionId = $this->institutionId;
            $EducationProgrammeResult = $EducationGrades->find()
                ->select(["same_grade_promotion"=>'EducationProgrammes.same_grade_promotion'])
                ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->LeftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()], [
                    $EducationGrades->aliasField('id') . ' = ' . $InstitutionGrades->aliasField('education_grade_id')
                ])
                ->where([
                    'EducationSystems.academic_period_id IS' => $entity->from_academic_period_id,
                    $InstitutionGrades->aliasField('institution_id') => $institutionId,
                    $EducationGrades->aliasField('id IS')=> $educationGradeId

                ])->first();
            //POCOR-7715 end
            if (!empty($educationGradeId) && $educationGradeId != -1) {
                $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId);
                $isLastGrade = $this->EducationGrades->isLastGradeInEducationProgrammes($educationGradeId);

                // If there is no more next grade in the same education programme then the student may be graduated
                //POCOR-8129--start
                if ($EducationProgrammeResult->same_grade_promotion == 1) {
                    if (count($nextGrades) == 0 || $isLastGrade) {
                        $options[$statusesCode['PROMOTED']] = __($studentStatusesList[$statusesCode['PROMOTED']]);
                        $options[$statusesCode['GRADUATED']] = __($studentStatusesList[$statusesCode['GRADUATED']]);
                        $options[$statusesCode['REPEATED']] = __($studentStatusesList[$statusesCode['REPEATED']]);
                    } else {
                        $options[$statusesCode['PROMOTED']] = __($studentStatusesList[$statusesCode['PROMOTED']]);
                        $options[$statusesCode['REPEATED']] = __($studentStatusesList[$statusesCode['REPEATED']]);
                    }
                } else {
                    // Check if count($nextGrades) == 0 or $isLastGrade
                    if (count($nextGrades) == 0 || $isLastGrade) {
                        $options[$statusesCode['GRADUATED']] = __($studentStatusesList[$statusesCode['GRADUATED']]);
                        $options[$statusesCode['REPEATED']] = __($studentStatusesList[$statusesCode['REPEATED']]);
                    } else {
                        $options[$statusesCode['PROMOTED']] = __($studentStatusesList[$statusesCode['PROMOTED']]);
                        $options[$statusesCode['REPEATED']] = __($studentStatusesList[$statusesCode['REPEATED']]);
                    }
                }
                //POCOR-8129--end
            }

            foreach ($options as $key => $value) {
                $options[$key] = __($value);
            }

            $attr['options'] = $options;
            $attr['onChangeReload'] = 'changeStudentStatus';
        }
        return $attr;
    }

    public function onUpdateFieldStudentStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // used for reconfirm
        if ($action == 'reconfirm') {
            $sessionKey = $this->getRegistryAlias() . '.confirm';
            if ($this->Session->check($sessionKey)) {
                $currentData = $this->Session->read($sessionKey);
            }

            if ($currentData->has('student_status_id')) {
                $statusData = $this->StudentStatuses
                    ->find()
                    ->where([$this->StudentStatuses->aliasField($this->StudentStatuses->getPrimaryKey()) => $currentData->student_status_id])
                    ->select([$this->StudentStatuses->aliasField('name')])
                    ->first();
                $statusName = (!empty($statusData))? $statusData->name: '';
            }

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = (!empty($statusName))? $statusName: '';
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        $entity           = $attr['entity'];
//        dd($entity);
        $studentStatusId  = $entity->has('student_status_id')       ? $entity->student_status_id       : null;
        $academicPeriodId = $entity->has('next_academic_period_id') ? $entity->next_academic_period_id : null;
        $educationGradeId = $entity->has('grade_to_promote')        ? $entity->grade_to_promote        : null;

        // No status selected yet — nothing to display.
        if (empty($studentStatusId)) {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = '';
            return $attr;
        }

        $statuses      = $this->statuses;
        $institutionId = $this->institutionId;

        // ── REPEATED ──────────────────────────────────────────────────────────
        // The student repeats the same grade; the target is fixed — display read-only.
        // POCOR-6319
        if ($studentStatusId == $statuses['REPEATED']) {
            $gradeData = $this->EducationGrades->getNextAvailableEducationGradesForRepeated($educationGradeId, $academicPeriodId);
            $gradeName = !empty($gradeData) ? $gradeData->programme . ' - ' . $gradeData->grade_name : '';
            $gradeId   = !empty($gradeData) ? $gradeData->id : '';

            $attr['type']          = 'readonly';
            $attr['attr']['value'] = $gradeName;
            $this->Session->write('grade_id', $gradeId);
            return $attr;
        }

        // ── PROMOTED or GRADUATED ──────────────────────────────────────────────
        // Load programme flags for this grade once — both branches below use them.
        $gradeObj  = $this->EducationGrades->get($educationGradeId, ['contain' => ['EducationProgrammes']]);
        $programme = $gradeObj->education_programme;

        // POCOR-4746: same_grade_promotion=1 means the student retakes the identical
        // grade name in the next period rather than advancing to the next sequential grade.
        $sameGradePromotion = $programme->same_grade_promotion ?? 0;

        // POCOR-9485: controls which grades of the next programme appear on graduation.
        //   1 = Show One Programme  → first grade of each linked next programme only.
        //   0 = Show All Programmes → all grades of every linked next programme.
        $nextProgrammeOption = $programme->next_programme_option_id ?? 1;

        $isLastGrade = $this->EducationGrades->isLastGradeInEducationProgrammes($educationGradeId);

        // ── Resolve "To Grade" options by status and grade position ───────────

        if ($studentStatusId == $statuses['GRADUATED'] && $isLastGrade) {
            // Graduate from the final grade → show grades from the linked next programme.
            // Scope controlled by next_programme_option_id (POCOR-9485 / POCOR-6257).
            Log::debug(sprintf(
                '[POCOR-9485] GRADUATED+isLastGrade: gradeId=%s periodId=%s institutionId=%s option=%s(%s) programmeId=%s programmeName=%s',
                $educationGradeId, $academicPeriodId, $institutionId,
                $nextProgrammeOption, ($nextProgrammeOption == 1 ? 'ShowOne' : 'ShowAll'),
                $programme->id, $programme->name
            ));
            $options = $this->getGradesForGraduation($educationGradeId, $academicPeriodId, $institutionId, $nextProgrammeOption);
            Log::debug(sprintf(
                '[POCOR-9485] FINAL result: count=%d grades=%s',
                count($options), json_encode($options)
            ));

        } elseif ($studentStatusId == $statuses['GRADUATED'] && !$isLastGrade) {
            // Graduate from a non-final grade (edge case) — prepend a "not enrolled" marker.
            // POCOR-6257
            $nextGrades        = $this->EducationGrades->getNextAvailableEducationGradesForPromoted($educationGradeId, $academicPeriodId, false);
            $institutionGrades = $this->getListOfInstitutionGrades($institutionId);
            $options = [0 => $this->getMessage($this->aliasField('notEnrolled'))] + array_intersect_key($institutionGrades, $nextGrades);

        } elseif ($studentStatusId == $statuses['PROMOTED'] && $sameGradePromotion == 1) {
            // Promoted with same-grade promotion: match the identical grade name in the next period.
            // POCOR-4746 (ORM replaces the raw SQL that was previously inline here)
            $options = $this->findMatchingGradeInPeriod($educationGradeId, $academicPeriodId);
            if (empty($options)) {
                // Fallback: standard next-grade list when no matching grade name is found.
                $nextGrades   = $this->EducationGrades->getNextAvailableEducationGradesForPromoted($educationGradeId, $academicPeriodId, false);
                $periodGrades = $this->EducationGrades->getEducationGradesByPeriod($academicPeriodId, $institutionId);
                $options      = array_intersect($nextGrades, $periodGrades);
            }

        } else {
            // Standard promotion: advance to the next sequential grade in the same programme.
            // POCOR-6257
            $nextGrades   = $this->EducationGrades->getNextAvailableEducationGradesForPromoted($educationGradeId, $academicPeriodId, false);
            $periodGrades = $this->EducationGrades->getEducationGradesByPeriod($academicPeriodId, $institutionId);
            $options      = array_intersect($nextGrades, $periodGrades);
        }

        $attr['type']           = 'select';
        $attr['options']        = ['0' => '-- ' . __('Select') . ' --'] + (array)$options;
        $attr['onChangeReload'] = 'changeToNextGrade';

        return $attr;
    }

    public function onUpdateFieldStudents(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $institutionId = $this->getInstitutionID();
        if(empty($institutionId)) {
            $encodedQueryParams = $this->request->getParam('pass')[1];
            $institutionId = $this->paramsDecode($encodedQueryParams)['institution_id'];
        }
        $currentData = null;
        $showNextClass = false;

        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                    $entity = $currentData;
                }
                $sessionKey = $this->getRegistryAlias() . '.confirmData';
                if ($this->Session->check($sessionKey)) {
                    $requestData = $this->Session->read($sessionKey);
                }

                $attr['selectedStudents'] = ($currentData->has('students'))? $currentData->students: [];
                $selectedPeriod = $currentData['from_academic_period_id'];
                $selectedStudentStatusId = $currentData['student_status_id'];
                break;

            case 'add':
                $entity = $attr['entity'];
                $requestData = $request->getData();
                $selectedPeriod = $entity->has('from_academic_period_id') ? $entity->from_academic_period_id : null;
                $selectedStudentStatusId = $entity->has('student_status_id') ? $entity->student_status_id : null;
                break;

            default:
                // no implementation
                break;
        }

        $students = [];
        $nextClasses = [];
        if (!empty($selectedPeriod) && $selectedPeriod != -1) {

            $studentStatuses = $this->statuses;
            $studentsPeriod = $this->find()
                    ->matching('Users')
                    ->matching('EducationGrades')
                    ->where([
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('student_status_id') => $studentStatuses['CURRENT']
                    ])
                    ->count();

                    if ($studentsPeriod == 0) {
                        $this->Alert->warning($this->aliasField('noData'));
                    }

            $selectedNextPeriod = $entity->has('next_academic_period_id') ? $entity->next_academic_period_id : null;
            $selectedGrade = $entity->has('grade_to_promote') ? $entity->grade_to_promote : null;
            $selectedNextGrade = $entity->has('education_grade_id') ? $entity->education_grade_id : null;

            if (!empty($selectedGrade)) {
                $studentStatuses = $this->statuses;
                $selectedClass = $entity->has('class') ? $entity->class : null;

                if (!is_null($selectedStudentStatusId) && $selectedClass != -1) {
                    $showNextClass = in_array($selectedStudentStatusId, [$studentStatuses['PROMOTED'], $studentStatuses['REPEATED'], $studentStatuses['GRADUATED']]);

                    if ($selectedStudentStatusId == $studentStatuses['REPEATED']) {
                        $selectedNextGrade = $selectedGrade;
                    }
                }/*POCOR-6312 Starts*/
                elseif ($selectedStudentStatusId == $studentStatuses['GRADUATED'] && $selectedClass == -1 ) {
                    $showNextClass = in_array($selectedStudentStatusId, [$studentStatuses['GRADUATED']]);
                }
                /*POCOR-6312 Ends*/
                /*POCOR-6319 starts*/
                elseif (!is_null($selectedStudentStatusId) && $selectedClass == -1) {
                    $showNextClass = in_array($selectedStudentStatusId, [$studentStatuses['PROMOTED'], $studentStatuses['REPEATED']]);
                }
                /*POCOR-6319 ends*/

                // to retain next class selection when validation failed
                $studentNextClassData = [];
                if (array_key_exists('students', $requestData[$this->getAlias()])) {
                    foreach ($requestData[$this->getAlias()]['students'] as $studentObj) {
                        if (isset($studentObj['next_institution_class_id'])) {
                            $studentId = $studentObj['student_id'];
                            $nextClassId = $studentObj['next_institution_class_id'];
                            $studentNextClassData[$studentId] = $nextClassId;
                        }
                    }
                }
                // end

                $students = $this->find()
                    ->matching('Users')
                    ->matching('EducationGrades')
                    ->where([
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
                        $this->aliasField('education_grade_id') => $selectedGrade
                    ])
                    ->find('studentClasses', ['institution_class_id' => $selectedClass])
                    ->select([
                        'institution_class_id' => 'InstitutionClassStudents.institution_class_id',
                        'next_institution_class_id' => 'InstitutionClassStudents.next_institution_class_id'
                    ])
                    ->order(['Users.first_name'])
                    ->formatResults(function (ResultSetInterface $results) use ($studentNextClassData) {
                        return $results->map(function ($row) use ($studentNextClassData) {
                            $studentId = $row->student_id;
                            if (array_key_exists($studentId, $studentNextClassData) && !empty($studentNextClassData[$studentId])) {
                                $row->next_institution_class_id = $studentNextClassData[$studentId];
                            }
                            return $row;
                        });
                    })
                    ->enableAutoFields(true);

                $InstitutionClassesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
                if ($students->count() > 0) {
                    if (!is_null($selectedNextPeriod) && !is_null($selectedNextGrade)) {
                        if ($selectedStudentStatusId == $studentStatuses['PROMOTED']) {
                            $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $selectedNextGrade);
                        } elseif ($selectedStudentStatusId == $studentStatuses['REPEATED']) {
                            $gradeId = $this->Session->read('grade_id');
                            $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $gradeId);
                        } else {
                            $nextClasses = $InstitutionClassesTable
                                                ->find('list')
                                                ->select([
                                                    $InstitutionClassesTable->aliasField('id'),
                                                    $InstitutionClassesTable->aliasField('name')
                                                ])
                                                ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()],[
                                                  $InstitutionClassGrades->aliasField('institution_class_id = ') . $InstitutionClassesTable->aliasField('id')
                                                ])
                                                ->where([
                                                $InstitutionClassesTable->aliasField('institution_id') => $institutionId,
                                                $InstitutionClassGrades->aliasField('education_grade_id') => $selectedNextGrade
                                                ])->toArray();
                        }
                    } elseif (is_null($selectedNextGrade) && $selectedStudentStatusId == $studentStatuses['REPEATED']) {
                        $gradeId = $this->Session->read('grade_id');
                        $nextClasses = $InstitutionClassesTable->getClassOptions($selectedNextPeriod, $institutionId, $gradeId);
                    }
                    $WorkflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
                    $StudentAdmissionTable = TableRegistry::getTableLocator()->get('Institution.StudentAdmission');
                    $StudentTransfersTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentTransfers');
                    $StudentWithdrawTable = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw');
                    $students = $students->toArray();

                    $pendingAdmissionStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentAdmission', 'PENDING');
                    $pendingWithdrawStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentWithdraw', 'PENDING');
                    $pendingTransferStatuses = $StudentTransfersTable->getStudentTransferWorkflowStatuses('PENDING');

                    // check if students have any pending requests
                    foreach ($students as $key => $value) {
                        $totalCount = 0;

                        // count pending admission requests
                        $conditions = [
                            'student_id' => $value->student_id,
                            'status_id IN ' => $pendingAdmissionStatus,
                            'education_grade_id' => $value->education_grade_id,
                            'institution_id' => $value->institution_id,
                            'academic_period_id' => $value->academic_period_id
                        ];
                        $admissionCount = $StudentAdmissionTable->find()
                            ->where($conditions)
                            ->count();
                        $totalCount += $admissionCount;

                        // count pending transfer requests
                        $conditions = [
                            'student_id' => $value->student_id,
                            'status_id IN ' => $pendingTransferStatuses,
                            'previous_education_grade_id' => $value->education_grade_id,
                            'previous_institution_id' => $value->institution_id,
                            'previous_academic_period_id' => $value->academic_period_id
                        ];
                        $transferCount = $StudentTransfersTable->find()
                            ->where($conditions)
                            ->count();
                        $totalCount += $transferCount;

                        // count pending withdraw requests
                        $conditions = [
                            'student_id' => $value->student_id,
                            'status_id IN ' =>  $pendingWithdrawStatus,
                            'education_grade_id' => $value->education_grade_id,
                            'institution_id' => $value->institution_id,
                            'academic_period_id' => $value->academic_period_id
                        ];
                        $withdrawCount = $StudentWithdrawTable->find()
                            ->where($conditions)
                            ->count();
                        $totalCount += $withdrawCount;

                        $students[$key]->pendingRequestsCount = $totalCount;
                    }
                }
            }
            /*if (empty($students)) {
                $this->Alert->warning($this->aliasField('noData'));
            }*/
        }
        if (empty($nextClasses)) {
            $nextClassOptions = ['' => $this->getMessage('general.select.noOptions')];
        } else {
            $nextClassOptions = ['0' => '-- '.__('Select Next Class').' --'] + $nextClasses;
        }

        $attr['type'] = 'element';
        $attr['element'] = 'Institution.StudentPromotion/students';
        $attr['data'] = $students;
        $attr['classOptions'] = $this->institutionClasses;
        $attr['nextClassOptions'] = $nextClassOptions;
        $attr['displayNextClassColumn'] = $showNextClass;
        return $attr;
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $params = $this->getQueryString();
        $encodedQueryParams = $this->ControllerAction->paramsEncode($params);
        switch ($action) {
            case 'add':
                $toolbarButtons['back'] = $buttons['back'];
                $toolbarButtons['back']['type'] = 'button';
                $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
                $toolbarButtons['back']['attr'] = $attr;
                $toolbarButtons['back']['attr']['title'] = __('Back');
                $toolbarButtons['back']['url']['action'] = 'Students';
                $toolbarButtons['back']['url']['0'] = 'index';
                $toolbarButtons['back']['url']['1'] = $encodedQueryParams;
                break;

            case 'reconfirm':
                unset($toolbarButtons['back']);
                break;

            default:
                # code...
                break;
        }
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        //$this->validator()->remove('education_grade_id', 'required');

        $process = function ($model, $entity) use ($event, $data) {
            $data = $data->getArrayCopy();
            // Removal of some fields that are not in use in the table validation
            $errors = $entity->getErrors();
            $studentStatus = $data[$this->getAlias()]['student_status_id'];

            if (isset($errors['student_id'])) {
                unset($errors['student_id']);
            }
            if (isset($errors['academic_period_id'])) {
                unset($errors['academic_period_id']);
            }
            if (isset($errors['institution_id'])) {
                unset($errors['institution_id']);
            }

            $statuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
            $repeatStatus = $statuses->getIdByCode('REPEATED');

            if (empty($errors)) {
                if (isset($data[$this->getAlias()])) { //POCOR-8490
                    $selectedStudent = false;
                    if (array_key_exists('students', $data[$this->getAlias()])) {
                        foreach ($data[$this->getAlias()]['students'] as $key => $value) {
                            if ($value['selected'] != 0) {
                                $selectedStudent = true;
                                break;
                            }
                        }
                    }
                    $nextAcademicPeriodId = isset($data[$this->getAlias()]['next_academic_period_id']) ? $data[$this->getAlias()]['next_academic_period_id'] : 0;
                    $educationGradeId = isset($data[$this->getAlias()]['education_grade_id']) ? $data[$this->getAlias()]['education_grade_id'] : 0;

                    if ($selectedStudent) {
                        //check students next classes have capcity
                        if ($this->checkIsOverStudentClassCapacity($entity->students)) {
                            return false;
                        }
                        //$params = $this->ControllerAction->getQueryString();
                        //$encodedQueryParams = $this->ControllerAction->paramsEncode($params);
                        $encodedQueryParams = $this->request->getParam('pass')[1];
                        // redirects to confirmation page
                        //$url = $this->ControllerAction->url('reconfirm');
                        $url = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'Promotion',
                            '0' => 'reconfirm',
                            '1' => $encodedQueryParams
                        ];

                        $this->currentEntity = $entity;
                        $session = $this->Session;
                        $session->write($this->getRegistryAlias().'.confirm', $entity);
                        $session->write($this->getRegistryAlias().'.confirmData', $data);
                        //print_r($session);die;
                        $this->currentEvent = $event;

                        $event->stopPropagation();

                        return $this->controller->redirect($url);
                    } else {
                        $this->Alert->warning($this->getAlias().'.noStudentSelected', ['reset' => true]);
                        return false;
                    }
                }
            } else {
                return false;
            }
        };

        return $process;
    }

    public function savePromotion(Entity $entity, ArrayObject $data)
    {
        //$params = $this->ControllerAction->getQueryString();
        //$institutionId = $this->ControllerAction->getQueryString('institution_id');
        $data = $data->getArrayCopy();
        $params = $this->getQueryString();
        $institutionId = $this->getQueryString('institution_id');
        $encodedQueryParams = $this->ControllerAction->paramsEncode($params);
        $url = $this->ControllerAction->url('index');

        $url['action'] = 'Students';

        $nextAcademicPeriodId = null;
        $nextEducationGradeId = null;
        $fromAcademicPeriod = null;
        $currentGrade = null;
        $statusToUpdate = null;
        $studentStatuses = $this->statuses;
        $institutionId = $this->institutionId;
        $saveAsDraft = !is_null($this->request->getData('submit')) && $this->request->getData('submit') == 'draft' ? true : false;

        if (array_key_exists('from_academic_period_id', $data[$this->getAlias()])) {
            $fromAcademicPeriod = $data[$this->getAlias()]['from_academic_period_id'];
        }
        if (array_key_exists('grade_to_promote', $data[$this->getAlias()])) {
            $currentGrade = $data[$this->getAlias()]['grade_to_promote'];
        }

        if (array_key_exists('next_academic_period_id', $data[$this->getAlias()])) {
            $nextAcademicPeriodId = $data[$this->getAlias()]['next_academic_period_id'];
        }
        if (array_key_exists('education_grade_id', $data[$this->getAlias()])) {
            $nextEducationGradeId = $data[$this->getAlias()]['education_grade_id'];
        }
        if (array_key_exists('student_status_id', $data[$this->getAlias()])) {
            $statusToUpdate = $data[$this->getAlias()]['student_status_id'];
        }
        if ($statusToUpdate == $studentStatuses['REPEATED']) {
            $gradeId = $this->Session->read('grade_id');
            $nextEducationGradeId = $gradeId;
        }
        if ($statusToUpdate == $studentStatuses['PROMOTED']) {
            $successMessage = $this->aliasField('success');
        } else if ($statusToUpdate == $studentStatuses['GRADUATED']) {
            $successMessage = $this->aliasField('successGraduated');
        } else {
            $successMessage = $this->aliasField('successOthers');
        }
        if (!empty($fromAcademicPeriod) && !empty($currentGrade)) {
            if (array_key_exists('students', $data[$this->getAlias()])) {
                foreach ($data[$this->getAlias()]['students'] as $key => $studentObj) {
                    if ($studentObj['selected']) {
                        unset($studentObj['selected']);
                        if ($saveAsDraft) {
                            // only save draft if current object is not graduating and next_institution_class_id is selected
                            //POCOR-5037
                            //if($statusToUpdate != $studentStatuses['GRADUATED']) {
                                $classStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
                                $classStudents
                                    ->query()
                                    ->update()
                                    ->set(['next_institution_class_id' => $studentObj['next_institution_class_id']])
                                    ->where([
                                        'institution_id' => $institutionId,
                                        'student_id' => $studentObj['student_id'],
                                        'education_grade_id' => $currentGrade,
                                        'academic_period_id' => $fromAcademicPeriod,
                                        'student_status_id' => $studentStatuses['CURRENT']
                                    ])
                                    ->execute();
                            //}
                            $this->Alert->success($this->aliasField('saveDraftSuccess'), ['reset' => true]);
                        } else {
                            if ($nextAcademicPeriodId != 0) {
                                $studentObj['academic_period_id'] = $nextAcademicPeriodId;
                                $studentObj['education_grade_id'] = $nextEducationGradeId;
                                $studentObj['institution_id'] = $institutionId;
                                $studentObj['student_status_id'] = $studentStatuses['CURRENT'];
                                $nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
                                $studentObj['start_date'] = $nextPeriod->start_date->format('Y-m-d');
                                $studentObj['end_date'] = $nextPeriod->end_date->format('Y-m-d');
                            }

                            $entity = $this->newEntity($studentObj, ['validate' => 'RemoveStudentPromotionValidation']);

                            $existingStudentEntity = $this->find()->where([
                                    $this->aliasField('institution_id') => $institutionId,
                                    $this->aliasField('student_id') => $studentObj['student_id'],
                                    $this->aliasField('academic_period_id') => $fromAcademicPeriod,
                                    $this->aliasField('education_grade_id') => $currentGrade,
                                    $this->aliasField('student_status_id') => $studentStatuses['CURRENT']
                                ])->first();
                            $existingStudentEntity->student_status_id = $statusToUpdate;
                            if(isset($entity->next_institution_class_id)){
                                $existingStudentEntity->next_institution_class_id = $entity->next_institution_class_id;
                            }
                            //POCOR-7170 start
                            $nextClassesId = $this->request->getData()['StudentPromotion']['next_class'];
                            $existingClassesId = $this->request->getData()['StudentPromotion']['class'];
                            if(!empty($nextClassesId)){
                                $classId = $nextClassesId;
                            }else{
                               $classId = $existingClassesId;
                            }

                           //POCOR-7170 end

                            if ($this->save($existingStudentEntity)) {
                                if ($nextEducationGradeId != 0 && $nextAcademicPeriodId != 0) {
                                    $entity->previous_institution_student_id = $existingStudentEntity->id;

                                    //registry the Institution.Students so it will call the afterSave in it.
                                    $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
                                    if ($InstitutionStudents->save($entity)) {
                                        $this->Alert->success($successMessage, ['reset' => true]);
                                    } else {
                                        $this->log($entity->getErrors(), 'debug');
                                    }
                                } else {
                                    //POCOR-6500 starts check student_status_id is graduate and next_education_grade is blank so remove the system will delete the student row in security_group_users table
                                    if(($statusToUpdate == $studentStatuses['GRADUATED']) && ($nextEducationGradeId == '')){
                                        //get student role
                                        $securityRolesTbl = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
                                        $securityRoles = $securityRolesTbl->find()
                                                                ->where([
                                                                    $securityRolesTbl->aliasField('code') => 'STUDENT',
                                                                ])
                                                                ->first();
                                        //get student institution
                                        $institutionTbl = TableRegistry::getTableLocator()->get('Institution.Institutions');
                                        $institutions = $institutionTbl->find()
                                                                ->where([
                                                                    $institutionTbl->aliasField('id') => $institutionId
                                                                ])
                                                                ->first();
                                        if(!empty($institutions) && $institutions->security_group_id !=''){
                                            $securityGroupUsersTbl = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                                            $securityGroupUsers = $securityGroupUsersTbl->find()
                                                                    ->where([
                                                                        $securityGroupUsersTbl->aliasField('security_group_id') => $institutions->security_group_id,
                                                                        $securityGroupUsersTbl->aliasField('security_user_id') => $studentObj['student_id'],
                                                                        $securityGroupUsersTbl->aliasField('security_role_id') => $securityRoles->id,
                                                                    ])->first();
                                            if(!empty($securityGroupUsers)){
                                                    $id = $securityGroupUsers->id;
                                                    $SecurityGroupUsersTable = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                                                    $SecurityGroupUsersTable->deleteAll(['id' => $id ]);
                                            }
                                        }
                                    }
                                    //POCOR-6500 ends
                                    $this->Alert->success($successMessage, ['reset' => true]);
                                }
                            } else {
                                $message = 'failed to update student status';
                                $this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
                                $this->log($message, 'debug');
                                $url['action'] = 'Promotion';
                                $url[0] = 'add';
                            }
                        }
                    }
                }
            } else {
                $message = 'students does not exists in data';
                $this->Alert->error($this->aliasField('noStudentSelected'), ['reset' => true]);
                $this->log($message, 'debug');
                $url['action'] = 'Promotion';
                $url[0] = 'add';
            }
        } else {
            $message = 'nextAcademicPeriodId && fromAcademicPeriod && currentGrade are empty';
            $this->Alert->error($this->aliasField('noNextGradeOrNextPeriod'), ['reset' => true]);
            $this->log($message, 'debug');
            $url['action'] = 'Promotion';
            $url[0] = 'add';
        }
        $url[0] = 'index';
        $url[1] = $encodedQueryParams;
        return $this->controller->redirect($url);
    }

    public function reconfirm()
    {
        $params = $this->ControllerAction->getQueryString();
        //$encodedQueryParams = $this->ControllerAction->paramsEncode($params);
        $encodedQueryParams = $this->request->getParam('pass')[1];
        $this->Alert->info($this->aliasField('reconfirm'), ['reset' => true]);

        $sessionKey = $this->getRegistryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentEntity = $this->Session->read($sessionKey);
            $currentData = $this->Session->read($sessionKey.'Data');
        } else {
            $this->Alert->warning('general.notExists');
            $url[0] = 'add';
            $url[1] = $encodedQueryParams;
            return $this->controller->redirect($url);
        }
        $academicPeriodData = $this->AcademicPeriods
            ->find()
            ->where([$this->AcademicPeriods->aliasField($this->AcademicPeriods->getPrimaryKey()) => $currentEntity->from_academic_period_id])
            ->select([$this->AcademicPeriods->aliasField('name')])
            ->first();
        $academicPeriodName = (!empty($academicPeriodData))? $academicPeriodData['name']: '';
        // preset all fields as invisble
        foreach ($this->fields as $key => $value) {
            $this->fields[$key]['visible'] = false;
        }

        $this->ControllerAction->field('from_academic_period_id', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('fromAcademicPeriod'))]]);
        $this->ControllerAction->field('grade_to_promote', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('fromGrade'))]]);
        $this->ControllerAction->field('class');
        $this->ControllerAction->field('next_academic_period_id', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('toAcademicPeriod'))]]);
        $this->ControllerAction->field('student_status', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('status'))]]);
        $statuses = $this->statuses;
        $this->ControllerAction->field('students', ['type' => 'readonly']);
        $this->ControllerAction->field('next_grade', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('toGrade'))]]);
        $this->ControllerAction->field('next_class', ['type' => 'readonly', 'attr' => ['label' => 'Next Class']]);
        $this->ControllerAction->setFieldOrder(['from_academic_period_id', 'next_academic_period_id', 'grade_to_promote', 'class', 'student_status', 'next_grade', 'next_class', 'students']);

        if ($currentEntity && !empty($currentEntity)) {
            if ($this->request->is(['post', 'put'])) {
                if ($currentData instanceOf ArrayObject) {
                    $currentData = $currentData->getArrayCopy();
                }
                $currentEntity = $this->patchEntity($currentEntity, $currentData, []);
                return $this->savePromotion($currentEntity, new ArrayObject($currentData));
            }
            /*POCOR-6566 starts*/
            $gradeId = $this->Session->read('grade_id');
            $currentEntity->grade_to_promote = $gradeId;
            /*POCOR-6566 ends*/
            $this->controller->set('data', $currentEntity);
        } else {
            $this->Alert->warning('general.notExists');
            $url[0] = 'add';
            $url[1] = $encodedQueryParams;
            return $this->controller->redirect($url);
        }

        $this->ControllerAction->renderView('/ControllerAction/edit');
    }
    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $params = $this->ControllerAction->getQueryString();
        $encodedQueryParams = $this->ControllerAction->paramsEncode($params);
        switch ($this->action) {
            case 'add':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
                $cancelButton = $buttons[1];
                break;

            case 'reconfirm':
                $saveAsDraftButton = $buttons[0];
                $confirmButton = $buttons[0];
                $cancelButton = $buttons[1];

                $saveAsDraftButton['attr']['value'] = 'draft';
                $saveAsDraftButton['name'] = '<i class="fa fa-check"></i> ' . __('Save as Draft');
                $confirmButton['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                $urlCancel = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Promotion',
                    '0' => 'add',
                    '1' => $encodedQueryParams
                ];
                // $cancelUrl = $this->ControllerAction->url($urlCancel);
                // $cancelUrl = array_diff_key($cancelUrl, $this->request->getQuery());
                $cancelButton['url'] = $urlCancel;

                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                    $studentStatusId = $currentData->student_status_id;

                    if (in_array($studentStatusId, [$this->statuses['PROMOTED'], $this->statuses['REPEATED'], $this->statuses['GRADUATED']])) {
                        $buttons[0] = $saveAsDraftButton;
                        $buttons[1] = $confirmButton;
                        $buttons[2] = $cancelButton;
                    } else {
                        $buttons[0] = $confirmButton;
                        $buttons[1] = $cancelButton;
                    }
                }
                break;

            default:
                # code...
                break;
        }
    }

    /**
     * Returns "To Grade" options when the student graduates from the final grade of a
     * programme. Options come from the linked next programme(s), filtered by the
     * next_programme_option_id flag (POCOR-9485) and intersected with grades actually
     * offered at this institution in the target academic period (POCOR-6257).
     *
     *   $nextProgrammeOption = 1 → Show One Programme  → first grade of each next programme only.
     *   $nextProgrammeOption = 0 → Show All Programmes → all grades of every next programme.
     */
    private function getGradesForGraduation(int $educationGradeId, $academicPeriodId, int $institutionId, int $nextProgrammeOption): array
    {
        $EducationGrades   = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');

        $firstGradeOnly = ($nextProgrammeOption == 1);

        // ── Step 1: linked next programmes for this grade's programme ──────────
        $EducationProgrammesNext = TableRegistry::getTableLocator()->get('Education.EducationProgrammesNextProgrammes');
        $gradeObj   = $EducationGrades->get($educationGradeId);
        $programmeId = $gradeObj->education_programme_id;
        $nextProgrammeList = $EducationProgrammesNext->getNextProgrammeList($programmeId);
        Log::debug(sprintf(
            '[POCOR-9485] Step1 next-programmes for programmeId=%s: count=%d ids=%s',
            $programmeId, count($nextProgrammeList), json_encode(array_values($nextProgrammeList))
        ));

        // ── Step 2: candidate grades from next programmes (no period filter) ───
        $nextProgrammeGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, true, $firstGradeOnly);
        Log::debug(sprintf(
            '[POCOR-9485] Step2 candidate grades (firstGradeOnly=%s): count=%d grades=%s',
            $firstGradeOnly ? 'true' : 'false', count($nextProgrammeGrades), json_encode($nextProgrammeGrades)
        ));

        // ── Step 3: all grades offered by this institution in the target period ─
        $periodInstitutionGrades = $EducationGrades->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
            ->find('visible')
            ->find('order')
            ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->leftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()], [
                $EducationGrades->aliasField('id') . ' = ' . $InstitutionGrades->aliasField('education_grade_id'),
            ])
            ->where([
                'EducationSystems.academic_period_id'             => $academicPeriodId,
                $InstitutionGrades->aliasField('institution_id')  => $institutionId,
            ])
            ->toArray();
        Log::debug(sprintf(
            '[POCOR-9485] Step3 institution(id=%s) grades in period %s: count=%d grades=%s',
            $institutionId, $academicPeriodId, count($periodInstitutionGrades), json_encode($periodInstitutionGrades)
        ));

        // ── Step 4: intersect — keep target-period grade IDs whose names appear in candidates ─
        $result = array_intersect($periodInstitutionGrades, $nextProgrammeGrades);
        Log::debug(sprintf(
            '[POCOR-9485] Step4 intersection: count=%d grades=%s',
            count($result), json_encode($result)
        ));

        // Keep grades from $periodInstitutionGrades (correct target-period grade IDs)
        // whose display names appear in $nextProgrammeGrades (the next programme candidates).
        // POCOR-6257 original logic: array_intersect by value (display name) ensures we return
        // the grade IDs the institution actually uses in the target period.
        return $result;
    }

    /**
     * For same-grade promotion (POCOR-4746): finds the grade that has the same name as
     * the given grade but belongs to the target academic period, using ORM queries.
     * This replaces the raw SQL that was previously embedded inline.
     *
     * Produces the same result as the old query:
     *   SELECT education_grades.id grade_id, education_grades.name grade_name,
     *          education_programmes.name programme_name
     *   FROM education_grades
     *   INNER JOIN education_programmes  ON ...
     *   INNER JOIN education_cycles      ON ...
     *   INNER JOIN education_levels      ON ...
     *   INNER JOIN education_systems     ON ...
     *   INNER JOIN academic_periods      ON ...
     *   WHERE academic_periods.id = :academicPeriodId
     *     AND education_grades.name = :currentGradeName
     *   ORDER BY academic_periods.order, education_levels.order,
     *            education_cycles.order, education_programmes.order,
     *            education_grades.order
     *   LIMIT 1
     *
     * Returns [gradeId => 'Programme - Grade'] or [] when nothing is found.
     */
    private function findMatchingGradeInPeriod(int $gradeId, $academicPeriodId): array
    {
        $EducationGrades     = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');

        // Fetch the current grade's name to use as the search anchor.
        $currentGrade = $EducationGrades->find()
            ->select(['id' => $EducationGrades->aliasField('id'), 'name' => $EducationGrades->aliasField('name')])
            ->where([$EducationGrades->aliasField('id') => $gradeId])
            ->first();

        if (empty($currentGrade)) {
            return [];
        }

        // Find a grade with the same name in the target academic period.
        $matched = $EducationGrades->find()
            ->select([
                'grade_id'       => $EducationGrades->aliasField('id'),
                'grade_name'     => $EducationGrades->aliasField('name'),
                'programme_name' => $EducationProgrammes->aliasField('name'),
            ])
            ->innerJoin([$EducationProgrammes->getAlias() => $EducationProgrammes->getTable()], [
                $EducationProgrammes->aliasField('id =') . $EducationGrades->aliasField('education_programme_id'),
            ])
            ->innerJoin(['EducationCycles'  => 'education_cycles'],  ['EducationCycles.id = '  . $EducationProgrammes->aliasField('education_cycle_id')])
            ->innerJoin(['EducationLevels'  => 'education_levels'],  ['EducationLevels.id = EducationCycles.education_level_id'])
            ->innerJoin(['EducationSystems' => 'education_systems'], ['EducationSystems.id = EducationLevels.education_system_id'])
            ->innerJoin(['AcademicPeriods'  => 'academic_periods'],  ['AcademicPeriods.id = EducationSystems.academic_period_id'])
            ->where([
                'AcademicPeriods.id'                 => $academicPeriodId,
                $EducationGrades->aliasField('name') => $currentGrade->name,
            ])
            ->order([
                'AcademicPeriods.order'                   => 'ASC',
                'EducationLevels.order'                   => 'ASC',
                'EducationCycles.order'                   => 'ASC',
                $EducationProgrammes->aliasField('order') => 'ASC',
                $EducationGrades->aliasField('order')     => 'ASC',
            ])
            ->first();

        if (empty($matched)) {
            return [];
        }

        return [$matched->grade_id => $matched->programme_name . ' - ' . $matched->grade_name];
    }

    public function getListOfInstitutionGrades($institutionId)
    {
        // list of grades available in the institution
        $today = date('Y-m-d');

        try {
            $listOfInstitutionGrades = $this->InstitutionGrades
                ->find('list', [
                    'keyField' => 'education_grade_id',
                    'valueField' => 'education_grade.programme_grade_name'])
                ->contain(['EducationGrades.EducationProgrammes'])
                ->where([
                    $this->InstitutionGrades->aliasField('institution_id') => $institutionId,
                    'OR' => [
                        [
                            $this->InstitutionGrades->aliasField('end_date IS NULL'),
                            $this->InstitutionGrades->aliasField('start_date <= ') => $today
                        ],
                        [
                            $this->InstitutionGrades->aliasField('end_date IS NOT NULL'),
                            $this->InstitutionGrades->aliasField('start_date <= ') => $today,
                            $this->InstitutionGrades->aliasField('end_date >= ') => $today
                        ]
                    ]
                ])
                ->order(['EducationProgrammes.order', 'EducationGrades.order'])
                ->toArray();
        } catch (RecordNotFoundException $e) {
            return [];
        }
        return $listOfInstitutionGrades;
    }

    private function checkIsOverStudentClassCapacity($entity)
    {
        if (!empty($entity)) {
            $nextClasses = [];

            //For each select student , store and count their next class for promotion to check
            foreach ($entity as $student) {
                if ($student['selected']) {
                    if (!(array_key_exists($student['next_institution_class_id'], $nextClasses))) {
                        $nextClasses[$student['next_institution_class_id']] = 1;
                    } else {
                        $nextClasses[$student['next_institution_class_id']] += 1;
                    }
                }
            }

            $institutionClassTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

            //Query to check if selected student and next class have capacity and return the classes that do not have
            $results = $institutionClassTable->find('all', array('fields' => array('id', 'name'), 'contain' => array()));
            $conditions['OR'] = [];
            foreach ($nextClasses as $class => $value) {
                $conditions['OR'][] = [
                        $institutionClassTable->aliasField('capacity') . '-' . $institutionClassTable->aliasField('total_male_students') . '-' . $institutionClassTable->aliasField('total_female_students') . ' < :value' . $class,
                        $institutionClassTable->aliasField('id =') => $class
                ];
                $results->bind(':value'. $class, $value, "integer");
            }
            $results->where($conditions);
            $overCapacityClasses = $results->toArray();

            if (!empty($overCapacityClasses)) {
                $this->Alert->clear();
                foreach ($overCapacityClasses as $class) {
                    $this->Alert->show( 'Next class ' . $class->name . ' does not have enough capacity for students.','error');
                }
                return true;
            }
        }
        return false;
    }


}
