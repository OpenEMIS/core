<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Time;
// use Cake\I18n\Date;
use Cake\I18n\FrozenDate;
use Cake\Log\Log;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Collection\CollectionInterface; // POCOR-9243

class InstitutionSubjectsTable extends ControllerActionTable
{
    use MessagesTrait;
    private $enrolledStatus;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->hasMany('ClassSubjects', ['className' => 'Institution.InstitutionClassSubjects', 'saveStrategy' => 'replace']);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'saveStrategy' => 'replace']);
        $this->hasMany('SubjectStaff', ['className' => 'Institution.InstitutionSubjectStaff', 'saveStrategy' => 'replace']);
        $this->hasMany('QualityRubrics', ['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('QualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->belongsToMany('Classes', [
            'className' => 'Institution.InstitutionClasses',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_subject_id',
            'targetForeignKey' => 'institution_class_id',
            'dependent' => true
        ]);

        $this->belongsToMany('Teachers', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionSubjectStaff',
            'foreignKey' => 'institution_subject_id',
            'targetForeignKey' => 'staff_id',
            'dependent' => true
        ]);

        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionSubjectStudents',
            'foreignKey' => 'institution_subject_id',
            'targetForeignKey' => 'student_id',
            'dependent' => true
        ]);

        $this->belongsToMany('Rooms', [
            'className' => 'Institution.InstitutionRooms',
            'joinTable' => 'institution_subjects_rooms',
            'foreignKey' => 'institution_subject_id',
            'targetForeignKey' => 'institution_room_id',
            'through' => 'Institution.InstitutionSubjectsRooms',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        // this behavior restricts current user to see All Subjects or My Subjects
        $this->addBehavior('Security.SecurityAccess');
        $this->addBehavior('Security.InstitutionSubject');
        $this->addBehavior('User.MoodleCreateUser'); //POCOR-8706

        /**
         * Short cuts
         */
        $this->InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $this->InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'SubjectStudents' => ['view', 'edit'],
            'ReportCardComments' => ['index'],
            'StudentOutcomes' => ['index'],
            'ScheduleTimetable' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
        $this->addBehavior('SubjectExcel', ['excludes' => ['security_group_id', 'identity_number', 'identity_type', 'student_status', 'openEMIS_ID', 'gender', 'student_name'], 'pages' => ['view']]);
        //$Classes = $this->Classes;
        //$this->Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        //$this->ClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Subjects' =>['institution_subject_id','institution_id']
            ]
        ]);
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'institution_subject_create',
                'entity_delete' => 'institution_subject_delete',
                'entity_update' => 'institution_subject_update',
                'table_alias' => 'Institution.InstitutionSubjects',
                'contain' => []
            ]
        ); // for webhook
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';

        return $events;
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'education_grade_id';
        $searchableFields[] = 'education_subject_id';
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('name')
            ->notEmpty('education_grade_id')
            ->requirePresence('class_subjects')
            ->notEmpty('class_subjects');
        /*->add('class_subjects', 'ruleCheckDuplicateClassSubjects', [
                'rule' => function ($check, $global) {
                    if ($global['newRecord']) {
                        return true;
                    }
                    $institutionSubjectId = $global['data']['id'];
                    // die;
                    $ClassSubjectsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');

                    $conditions = [];
                    $conditions['OR'] = [];
                    foreach ($check as $record) {
                        $conditions['OR'][] = [
                            $ClassSubjectsTable->aliasField('institution_class_id') .' != ' => $record['institution_class_id'],
                            $ClassSubjectsTable->aliasField('institution_subject_id') .' != ' => $record['institution_subject_id']
                        ];
                    }

                    $educationSubjectId = $this->get($institutionSubjectId)->education_subject_id;

                    $anotherCondition = [];
                    $anotherCondition['OR'] = [];
                    foreach ($check as $record) {
                        $anotherCondition['OR'][] = [
                            $ClassSubjectsTable->aliasField('institution_class_id') => $record['institution_class_id']
                        ];
                    }

                    $recordFound = $ClassSubjectsTable->find()->innerJoinWith('InstitutionSubjects', function ($q) use ($educationSubjectId) {
                        return $q->where(['InstitutionSubjects.education_subject_id' => $educationSubjectId]);
                    })->where($conditions)->where($anotherCondition)->count();
                    return $recordFound == 0;
                },
                'message' => __('Institution Subject has already been added to one of the classes.')
            ]);*/
        return $validator;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $extra['institution_id'] = $this->getInstitutionID();
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $this->enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

        // $this->field('education_grade_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>false, 'add'=>true], 'onChangeReload' => true, 'sort' => ['field' => 'EducationGrades.name']]);
        $this->field('education_grade_id', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]]);
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true, 'add' => true], 'onChangeReload' => true]);
        $this->field('created', ['type' => 'string', 'visible' => false]);
        $this->field('created_user_id', ['type' => 'string', 'visible' => false]);
        $this->field('education_subject_code', ['type' => 'string', 'visible' => ['view' => true]]);
        $this->field('education_subject_id', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true]]);
        $this->field('modified', ['type' => 'string', 'visible' => false]);
        $this->field('modified_user_id', ['type' => 'string', 'visible' => false]);
        $this->field('name', ['type' => 'string', 'visible' => ['index' => true, 'view' => true, 'edit' => true], 'sort' => ['field' => 'EducationSubjects.name']]);
        $this->field('no_of_seats', ['type' => 'integer', 'attr' => ['min' => 1], 'visible' => false]);
        $this->field('class_name', ['type' => 'select', 'visible' => ['index' => true, 'view' => true, 'edit' => true], 'onChangeReload' => true]);

        $this->field('students', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Subjects/students',
            'data' => [
                'students' => [],
                'studentOptions' => [],
                'categoryOptions' => []
            ],
            'visible' => ['view' => true, 'edit' => true]
        ]);
        $this->field('subjects', [
            'label' => '',
            'type' => 'element',
            'element' => 'Institution.Subjects/subjects',
            'data' => [
                'subjects' => [],
                'teachers' => []
            ],
            'visible' => false
        ]);

        $this->field('teachers', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'teachers',
            'fieldName' => $this->getAlias() . '.teachers._ids',
            'placeholder' => $this->getMessage('Users.select_teacher'),
            'valueWhenEmpty' => '<span>&lt;' . __('No Teacher Assigned') . '&gt;</span>',
            'visible' => ['index' => true, 'view' => true, 'edit' => true]
        ]);

        $this->field('past_teachers', [
            'type' => 'element',
            'element' => 'Institution.Subjects/past_teachers',
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => false]
        ]);

        $this->field('rooms', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'rooms',
            'fieldName' => $this->getAlias() . '.rooms._ids',
            'placeholder' => $this->getMessage('Users.select_room'),
            'valueWhenEmpty' => '<span>&lt;' . __('No Room Allocated') . '&gt;</span>',
            'visible' => ['index' => true, 'view' => true, 'edit' => true]
        ]);

        $this->field('total_students', [
            'type' => 'integer',
            'visible' => ['index' => true]
        ]);

        $this->setFieldOrder([
            'name', 'education_grade_id', 'education_subject_id', 'class_name', 'teachers', 'rooms', 'total_male_students', 'total_female_students', 'total_students',
        ]);

        $academicPeriodOptions = $this->getAcademicPeriodOptions($extra['institution_id']);
        if (empty($academicPeriodOptions)) { //cant find programme which date range within available academic period
            $this->Alert->warning('InstitutionSubjects.noProgrammes');
            $extra['noProgrammes'] = true;
        }
        //POCOR-5852 starts
        if (empty($this->request->getQuery('academic_period_id'))) {
            $this->request = $this->request->withQueryParams( array_merge( $this->request->getQueryParams(), ['academic_period_id' => $this->AcademicPeriods->getCurrent()] ));//POCOR-8394
            //$this->request->getQuery('academic_period_id') = $this->AcademicPeriods->getCurrent();
            //$Classes = $this->Classes;
            $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $classOptions = $Classes->find('list')
                ->where([
                    $Classes->aliasField('academic_period_id') => $this->request->getQuery('academic_period_id'),
                    $Classes->aliasField('institution_id') => $extra['institution_id']
                ])
                ->toArray();
            $selectedClassId = $this->queryString('class_id', $classOptions);
            $this->request = $this->request->withQueryParams( array_merge( $this->request->getQueryParams(), ['class_id' => $selectedClassId] ));//POCOR-8394
            //$this->request->getQuery('class_id') = $selectedClassId;
        }
        //POCOR-5852 ends
        $extra['selectedAcademicPeriodId'] = $this->queryString('academic_period_id', $academicPeriodOptions);
        $extra['selectedClassId'] = 0;
        $className = $this->request->getQuery('class_id');
        $this->Session->write('is_className', $className);
    }


    /******************************************************************************************************************
     **
     ** index action methods
     **
     ******************************************************************************************************************/
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        //$Classes = $this->Classes;
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $Subjects = $this;

        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];

        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes->find()->where([
                    $Classes->aliasField('institution_id') => $institutionId,
                    $Classes->aliasField('academic_period_id') =>  $id
                ])->count();
            }
        ]);

        $AccessControl = $this->AccessControl;
        $userId = $this->Auth->user('id');
        $controller = $this->controller;

        $classOptions = $Classes->find('list')
            ->where([
                $Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                $Classes->aliasField('institution_id') => $institutionId
            ])
            ->toArray();

        if (!$this->Auth->user('super_admin')) {
            $Classes = $this->Classes;
            $classOptions = $Subjects
                ->find('list', ['keyField' => 'class_id', 'valueField' => 'class_name'])
                ->innerJoinWith('Classes')
                ->select(['class_id' => 'Classes.id', 'class_name' => 'Classes.name'])
                ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $controller])
                ->where([
                    $Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                    $Classes->aliasField('institution_id') => $institutionId
                ])
                ->group(['class_id'])
                ->enableHydration(false)
                ->toArray();
        }

        if (empty($classOptions) && !isset($extra['noProgrammes'])) {
            $this->Alert->warning('Institutions.noClassRecords');
        }
        $selectedClassId = $this->queryString('class_id', $classOptions);

        $this->advancedSelectOptions($classOptions, $selectedClassId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSubjects')),
            'callable' => function ($id) use ($Subjects, $institutionId, $selectedAcademicPeriodId, $AccessControl, $userId, $controller) {
                $query = $Subjects->find()
                    ->join([
                        [
                            'table' => 'institution_class_subjects',
                            'alias' => 'InstitutionClassSubjects',
                            'type' => 'INNER',
                            'conditions' => [
                                'InstitutionClassSubjects.institution_subject_id = ' . $Subjects->aliasField('id'),
                                'InstitutionClassSubjects.institution_class_id = ' . $id
                            ]
                        ]
                    ])
                    ->where([
                        $Subjects->aliasField('institution_id') => $institutionId,
                        $Subjects->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                    ]);
                return $query->count();
            }
        ]);

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Institution.Subjects/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'academicPeriodOptions' => $academicPeriodOptions,
                'classOptions' => $classOptions,
                'selectedClass' => $selectedClassId,
            ],
            'options' => [],
            'order' => 3
        ];
        $extra['selectedClassId'] = $selectedClassId;
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        //to remove 'add' button if no class or programmes set
        if (empty($classOptions) || isset($extra['noProgrammes'])) {
            $this->toggle('add', false);
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'All Subjects', 'Academic');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
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

    public function findTranslateItem(Query $query, array $options)
    {
        return $query // POCOR-9243 start
            ->formatResults(function (CollectionInterface $results) {
                return $results->map(function ( $row) {
                    if (!empty($row['subject_students']) && is_array($row['subject_students'])) {
                        // Keep only "Enrolled" students (status_id == 1)
                        $row['subject_students'] = array_values(array_filter(
                            $row['subject_students'],
                            function ($student) {
                                if ($student->student_status_id != 1) {
//                                    Log::debug('Skipping student: ' . print_r($student, true));
                                    return false;
                                }
                                return true;
                            }
                        ));
                    }
                    return $row;
                }); // POCOR-9243 end
            });
    }
    //6198 starts
    public function findBySubjectsInClass(Query $query, array $options)
    {
        $classId = $options['institution_class_id'];
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $gradeId = $options['education_grade_id'];
        $student_id = $options['student_id'];
        $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');

        return $query
            ->matching('ClassSubjects', function ($q) use ($classId) {
                return $q->where(['ClassSubjects.institution_class_id' => $classId]);
            })
            ->contain(['EducationSubjects'])
            ->leftJoin([$InstitutionSubjectStudents->getAlias() => $InstitutionSubjectStudents->getTable()], [

                $this->aliasField('id = ') . $InstitutionSubjectStudents->aliasField('institution_subject_id'),
                $this->aliasField('institution_id = ') . $InstitutionSubjectStudents->aliasField('institution_id'),
                $this->aliasField('education_grade_id = ') . $InstitutionSubjectStudents->aliasField('education_grade_id'),
                $this->aliasField('academic_period_id = ') . $InstitutionSubjectStudents->aliasField('academic_period_id')
            ])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('education_grade_id') => $gradeId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $InstitutionSubjectStudents->aliasField('student_id') => $student_id
            ])
            ->order('EducationSubjects.order');
    }
    //6198 ends

    public function findSubjectDetails(Query $query, array $options)
    {
        // POCOR-2547 sort list of staff and student by name
        // move the contain from institution.subject.student.ctrl.js since its using finder method
        $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        return $query
            ->find('translateItem')
            ->contain([
                'SubjectStaff.Users',
                'Rooms',
                'EducationSubjects',
                'AcademicPeriods',
                'SubjectStudents' => ['sort' => ['Users.first_name', 'Users.last_name']],
                'SubjectStudents.Users.Genders',
                'SubjectStudents.StudentStatuses',
                'ClassSubjects',
                'SubjectStudents.InstitutionClasses',
            ]);
    }

    public function findByClasses(Query $query, array $options)
    {
        return isset($options['selectedClassId']) && $options['selectedClassId'] ? $query
            ->join([
                [
                    'table' => 'institution_class_subjects',
                    'alias' => 'InstitutionClassSubjects',
                    'conditions' => [
                        'InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id',
                        'InstitutionClassSubjects.institution_class_id = ' . $options['selectedClassId']
                    ]
                ]
            ]) : $query;
    }

    // used for student report cards
    public function findTeacherEditPermissions(Query $query, array $options)
    {
        $reportCardId = $options['report_card_id'];
        $institutionId = $options['institution_id'];
        $classId = $options['institution_class_id'];
        $staffId = $options['staff_id'];

        $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $ReportCardSubjects = TableRegistry::getTableLocator()->get('ReportCard.ReportCardSubjects');

        return $query
            ->find('list', [
                'keyField' => 'education_subject_id',
                'valueField' => 'education_subject_id'
            ])
            ->select(['education_subject_id' => $this->aliasField('education_subject_id')])
            ->leftJoinWith('EducationSubjects.ReportCardSubjects')
            ->innerJoinWith('ClassSubjects')
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                'ClassSubjects.institution_class_id' => $classId,
                'ReportCardSubjects.report_card_id' => $reportCardId
            ]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->find('byClasses', ['selectedClassId' => $extra['selectedClassId']])
            ->contain(['Teachers', 'Rooms', 'EducationSubjects', 'EducationGrades', 'Classes'])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']]);

        // search function to search education grade and education subject
        $searchKey = $this->getSearchKey();
        if (!empty($searchKey)) {
            $extra['OR'] = [
                $this->EducationSubjects->aliasField('name') . ' LIKE' => '%' . $searchKey . '%',
                $this->EducationGrades->aliasField('name') . ' LIKE' => '%' . $searchKey . '%',
            ];
        }

        // sortWhiteList
        $sortList = ['EducationGrades.name', 'EducationSubjects.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // by default sorting by EducationSubjectsOrder followed by EducationGradesOrder
        $requestQuery = $this->request->getQuery();
        $sortable = isset($requestQuery['sort']) ? true : false;

        if (!$sortable) {
            $query
                ->order([
                    'EducationSubjects.order',
                    'EducationGrades.order'
                ]);
        }
    }

    public function afterSaveCommit(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $institutionClassId = $entity['class_subjects'][0]['institution_class_id'];
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');

        //Commented for V4
        // $institution_subject_id = $InstitutionClassSubjects->find()->select(['institution_subject_id'])->where(['education_grade_id' => $entity->education_grade_id, 'academic_period_id' => $entity->academic_period_id, 'education_subject_id' => $entity->education_subject_id, 'institution_class_id' => $institutionClassId, 'institution_subject_id NOT IN ' => $entity->id])->first();
        // $institution_subject_id = $institution_subject_id['institution_subject_id'];
        $institution_subject_id = $entity['class_subjects'][0]['institution_subject_id'];
        $id = $entity->id;

        $countMale = $this->SubjectStudents->getMaleCountBySubject($id);
        $countFemale = $this->SubjectStudents->getFemaleCountBySubject($id);
        $this->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);

        $countMale = $this->SubjectStudents->getMaleCountBySubject($institution_subject_id);
        $countFemale = $this->SubjectStudents->getFemaleCountBySubject($institution_subject_id);
        $this->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $institution_subject_id]);
        $this->dispatchEvent('Model.afterFullSave', compact('entity', 'options'));
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        if (isset($extra[$this->aliasField('notice')]) && !empty($extra[$this->aliasField('notice')])) {
            $this->Alert->warning($extra[$this->aliasField('notice')], ['reset' => true]);
            unset($extra[$this->aliasField('notice')]);
        }
    }


    /******************************************************************************************************************
     **
     ** view action methods
     **
     ******************************************************************************************************************/
    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        //POCOR-8481 starts
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Subjects',
            '0' => 'index',
            '1' => $this->paramsEncode(['id' => $extra['institution_id'], 'institution_id' => $extra['institution_id']])
        ];//POCOR-8481 ends

        if ($extra['selectedAcademicPeriodId'] == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'Subjects'
            ]);
        }

        $this->field('total_students', ['visible' => true]);

        $this->setFieldOrder([
            'academic_period_id', 'class_name', 'education_grade_id', 'name', 'education_subject_code', 'education_subject_id',
            'total_male_students', 'total_female_students', 'total_students', 'teachers', 'past_teachers', 'rooms', 'students',
        ]);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        /**POCOR-6768 starts - added innerjoin to get correct student records*/
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $encodedSubjectId = $this->request->getParam('pass')[1];
        $decodedSubjectId = $this->paramsDecode($encodedSubjectId);
        $subjectId = $decodedSubjectId['id'];
        $getClassesObj = $InstitutionClassSubjects->find()
            ->select(['class_id' => $InstitutionClassSubjects->aliasField('institution_class_id')])
            ->where([$InstitutionClassSubjects->aliasField('institution_subject_id') => $subjectId])
            ->toArray();
        $classIds = [];
        if (!empty($getClassesObj)) {
            foreach ($getClassesObj as $class) {
                $classIds[] = $class->class_id;
            }
        }

        $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $query->contain([
            'Classes.ClassesSecondaryStaff',
            'Teachers',
            'Rooms',
            'SubjectStudents'  => function ($q) use ($InstitutionClassStudents,  $classIds, $subjectId) {
                return $q
                    ->innerJoin([$InstitutionClassStudents->getAlias() => $InstitutionClassStudents->getTable()], [
                        //'SubjectStudents.student_id = ' . $InstitutionClassStudents->aliasField('student_id'),
                        'SubjectStudents.institution_id = ' . $InstitutionClassStudents->aliasField('institution_id'),
                        'SubjectStudents.academic_period_id = ' . $InstitutionClassStudents->aliasField('academic_period_id'),
                        'SubjectStudents.education_grade_id = ' . $InstitutionClassStudents->aliasField('education_grade_id'),
                        'SubjectStudents.institution_class_id = ' . $InstitutionClassStudents->aliasField('institution_class_id'),
                        'SubjectStudents.student_status_id = ' . $InstitutionClassStudents->aliasField('student_status_id')
                    ])
                    ->where([
                        'SubjectStudents.institution_class_id IN' => $classIds,
                        'SubjectStudents.institution_subject_id' => $subjectId
                    ])
                    ->contain([
                        'Users.Genders',
                        'InstitutionClasses',
                        'StudentStatuses'
                    ])
                    ->group(['SubjectStudents.student_id']);
            }
        ]);
        /**POCOR-6768 ends*/
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $entity->class_name = implode(', ', (new Collection($entity->classes))->extract('name')->toArray());
        $this->fields['students']['data']['students'] = $entity->subject_students;
        $this->fields['past_teachers']['data'] = $this->getPastTeachers($entity);

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $configureStudentName = $ConfigItems->value("configure_student_name");
        $this->fields['students']['data']['configure_student_name'] = $configureStudentName;
        return $entity;
    }


    /******************************************************************************************************************
     **
     ** add action methods
     **
     ******************************************************************************************************************/
    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        if ($selectedAcademicPeriodId == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'Subjects'
            ]);
        }

        $this->fields['name']['visible'] = false;
        $this->fields['teachers']['visible'] = false;
        $this->fields['rooms']['visible'] = false;
        $this->fields['students']['visible'] = false;
        $this->fields['education_subject_id']['visible'] = false;

        $this->fields['total_male_students']['visible'] = false;
        $this->fields['total_female_students']['visible'] = false;
        $this->fields['class_name']['visible'] = true;
        $this->fields['subjects']['visible'] = true;
        $this->fields['education_grade_id']['visible'] = true;
        $this->setFieldOrder([
            'academic_period_id', 'class_name', 'education_grade_id', 'subjects',
        ]);

        $Classes = $this->Classes;

        $institutionId = $extra['institution_id'];
        $periodOption = ['' => '-- ' . __('Select Period') . ' --'];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['withLevels' => false, 'isEditable' => true]);
        $academicPeriodOptions = $periodOption + $academicPeriodOptions;

        if ($this->request->is(['post', 'put']) && $this->request->getData()['InstitutionSubjects']['academic_period_id']) {
            $extra['selectedAcademicPeriodId'] = $this->request->getData()['InstitutionSubjects']['academic_period_id'];
            $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        }

        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes->find()->where([
                    $Classes->aliasField('institution_id') => $institutionId,
                    $Classes->aliasField('academic_period_id') =>  $id
                ])->count();
            }
        ]);

        $classOptions = $Classes->find('list')
            ->where([
                $Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                $Classes->aliasField('institution_id') => $institutionId
            ])
            ->toArray();

        $ClassGrades = $this->InstitutionClassGrades;
        $query = $this->request->getQuery();

        // $selectedClassId = isset($query['class_id']) && !empty($query['class_id']) ? $query['class_id'] : '';
        //POCOR-7110

        //POCOR-7099 start
        $pageWasRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
        if ($pageWasRefreshed == 1) {
            $selectedClassId = $this->postString('class_name', $classOptions);
        } else {
            $selectedClassId = isset($query['class_id']) && !empty($query['class_id']) ? $query['class_id'] : '';
        }

        //End of POCOR-7110

        //POCOR-7099 end

        $this->advancedSelectOptions($classOptions, $selectedClassId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
            'callable' => function ($id) use ($ClassGrades) {
                return $ClassGrades->find()->where([
                    $ClassGrades->aliasField('institution_class_id') => $id
                ])->count();
            }
        ]);
        $extra['selectedClassId'] = $selectedClassId;

        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->fields['class_name']['options'] = $classOptions;

        $this->fields['education_grade_id'];
    }


    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriodTable->getCurrent();
        if(!empty($this->request->getQuery('class_id')) || !empty($this->request->getData('InstitutionSubjects')['class_name'])){
            $className = (null !== $this->request->getQuery()) ? $this->request->getQuery('class_id') : $this->request->getData('InstitutionSubjects')['class_name'];
            if($this->request->getQuery()!=null && !empty($this->request->getData('InstitutionSubjects')['class_name'])){
                $className = $this->request->getData('InstitutionSubjects')['class_name'];
            }
            if($className == ''){
                $className = $this->request->getData('InstitutionSubjects')['class_name'];
            }
            list($levelOptions, $selectedLevel) = array_values($this->getEducationGradeOptions($className));

            $attr['options'] = $levelOptions;
            if ($action == 'add') {
                $attr['default'] = $selectedLevel;
            }
            return $attr;
        }else{
            $institutionid = $this->getInstitutionID();
            $institutionClass = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $getClassId = $institutionClass->find()->where(['institution_id' => $institutionid, 'academic_period_id' => $academicPeriodId])->first()->id;
            $className = $getClassId;
            //POCOR-8706 start
            $levelOptions = null;
            $selectedLevel = null;
            if($className)
              list($levelOptions, $selectedLevel) = array_values($this->getEducationGradeOptions($className));
            //POCOR-8706 end
            $attr['options'] = $levelOptions;
            if ($action == 'add') {
                $attr['default'] = $selectedLevel;
            }

            return $attr;
        }
    }

    public function getEducationGradeOptions($className)
    {

        $Grade = $this->InstitutionClassGrades;
        $levelOptions = $Grade
            ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'name'])
            ->innerJoin(
                ['educationGrades' => 'education_grades'],
                [
                    'educationGrades.id = InstitutionClassGrades.education_grade_id '
                ]
            )
            ->where([
                $Grade->aliasField('institution_class_id') => $className,
            ])
            ->toArray();
        $selectedLevel = '';
        return compact('levelOptions', 'selectedLevel');
    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        foreach ($data as $key => $value) { //loop each subject then unset education_subject_id if not selected (so no validation is done).
            if ($key == 'MultiSubjects') {
                foreach ($data[$key] as $key1 => $value1) {
                    if (isset($value1['education_subject_id'])) {
                        if (!$value1['education_subject_id']) {
                            unset($data[$key][$key1]['education_subject_id']);
                            //unset($data[$key][$key1]['name']);
                        }
                    }
                }
            }
        }
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        //POCOR-7674 start
        $classSubjects = $this->ClassSubjects
            ->find()
            ->contain([
                'InstitutionSubjects' => [
                    'EducationSubjects',
                    'Teachers' => function ($q) {
                        return $q
                            ->where([
                                'OR' => [
                                    ['end_date IS NULL'],
                                    ['end_date' . ' >= ' => FrozenDate::now()]
                                ]
                            ]);
                    },
                    'Teachers.Genders'
                ],
            ])
            ->where([
                $this->ClassSubjects->aliasField('institution_class_id') => $extra['selectedClassId'],
                $this->ClassSubjects->aliasField('status') => 1
            ])
            ->toArray();
        foreach ($data['MultiSubjects'] as $key => $value) {
            if ($value['education_subject_id']) {
                foreach ($classSubjects as $Key => $subject) {
                    if ($value['education_subject_id'] = $subject['institution_subject']['education_subject_id']) {
                        if ($value['name'] == $subject['institution_subject']['education_subject']['name']) {
                            $this->Alert->error('InstitutionSubjects.SubjectAlreadyExist', ['reset' => true]);
                            return true;
                        }
                    }
                }
            }
        }
        //POCOR-7674 end
        $process = function ($model, $entity) use ($data, $extra) {
            list($error, $subjects, $data) = $model->prepareEntityObjects($model, $data, $extra);
            if (!$error && $subjects) {
                foreach ($subjects as $subject) {
                    if ($subject->education_subject_id) {
                        $model->save($subject);
                    }
                }
                $extra[$this->aliasField('notice')] = 'passed';
                return true;
            } else {
                if ($error == $this->aliasField('allSubjectsAlreadyAdded')) {
                    $extra[$this->aliasField('notice')] = $this->aliasField('allSubjectsAlreadyAdded');
                    return true;
                } else {
                    // $model->log($error, 'debug');
                    $model->log(print_r($error, true), 'debug');
                    if (is_array($error)) { //this error is to validate "name" field, not for each subject name so not needed.
                        //$model->Alert->error('general.add.failed');
                    } else {
                        /**
                         * unset all field validation except for "institution_id" to trigger validation error in ControllerActionComponent
                         */
                        foreach ($model->fields as $value) {
                            if ($value['field'] != 'institution_id') {
                                $model->getValidator()->remove($value['field']);//POCOR-8324
                            }
                        }
                        $extra[$this->aliasField('notice')] = $error;
                        $model->Alert->error($error);
                    }
                    // $model->request->data = $data;
                    $model->request = $model->request->withParsedBody($data);
                    return false;
                }
            }
        };
        return $process;
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (isset($extra[$this->aliasField('notice')]) && !empty($extra[$this->aliasField('notice')])) {
            $notice = $extra[$this->aliasField('notice')];
            unset($extra[$this->aliasField('notice')]);
            if ($notice == 'passed') {
                $this->Alert->success('general.add.success', ['reset' => true]);
                $Url = $extra['redirect'];//POCOR-8324
                return $this->controller->redirect($Url);//POCOR-8324
                //return $this->controller->redirect($this->url('index', 'QUERY'));//POCOR-8324 comment because of redirection
            } else {
                $this->Alert->error($notice, ['reset' => true]);
            }
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('subject_students')) {
            if (!empty($data['subject_students'])) { //if not empty, then process save manually
                $data['subjectStudent'] = $data['subject_students'];
                $data->offsetUnset('subject_students');
            } else {
                $data['total_male_students'] = 0;
                $data['total_female_students'] = 0;
            }
        }

        if ($data->offsetExists('rooms')) {
            $data['rooms']['_ids'] = $data['rooms'];
        }
    }

    // public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    // {
    //     $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
    //     $institutionSubjectId = $entity->id;

    //     $query = $InstitutionClassSubjects
    //         ->find()
    //         ->where([
    //             'institution_subject_id' => $institutionSubjectId
    //         ])
    //         ->extract('institution_class_id')
    //         ->toArray();

    //     $options['originalClass'] = $query;
    // }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
//        POCOR-8391 start
        if (!$entity->isNew()) {
            //empty subject student is handled by beforeMarshal
            //in another case, it will be save manually to avoid unecessary queries during save by association
            if (isset($entity->subjectStudent)) {
                // $institutionClassId = 0;
                $newStudents = [];
                //decode string sent through form
                foreach ($entity->subjectStudent as $item) {
                    $student = json_decode($this->urlsafeB64Decode($item), true);
                    $newStudents[$student['student_id']] = $student;
                    // if ($institutionClassId == 0) {
                    //     $institutionClassId = $student['institution_class_id'];
                    // }
                }

                //find existing subject student to make comparison
                $educationGradeId = $entity->education_grade_id;
                $educationSubjectId = $entity->education_subject_id;
                $student_status_id = $entity->student_status_id;
                $institutionSubjectId = $entity->id;
                // $institutionClassIds = $options['originalClass'];
                // $institutionClassIds = $entity['class_subjects'][0]['institution_class_id'];   //Version4
                $SubjectStudents  =  TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
                $where = [
                    $SubjectStudents->aliasField('education_subject_id') => $educationSubjectId,
                    $SubjectStudents->aliasField('institution_subject_id') => $institutionSubjectId,
                    $SubjectStudents->aliasField('student_status_id = 1')
                ];
                $existingStudents = $SubjectStudents
                    ->find('all')
                    ->select([
                        'id', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'institution_id',
                        'student_status_id', 'institution_subject_id', 'education_subject_id'
                    ])
                    ->where($where)
                    ->toArray();
                foreach ($existingStudents as $key => $subjectStudentEntity) {
                    $student_id = $subjectStudentEntity->student_id;
                    if (!isset($newStudents[$student_id])) { // if current student does not exists in the new list of students
//                        Log::debug('- ' . strval($student_id));
                        $this->SubjectStudents->delete($subjectStudentEntity);
                    } else { // if student exists, then remove from the array to get the new student records to be added
//                        Log::debug('+ ' . strval($student_id));
                        unset($newStudents[$student_id]);
                    }
                }
                foreach ($newStudents as $key => $student) {
                    if (!is_array($student)) { // POCOR-8391
                        $student->student_status_id = 1;
                    }
                    $student['student_status_id'] = 1;
//                    Log::debug(print_r($student, true));
                    $subjectStudentEntity = $this->SubjectStudents->newEntity($student);
                    $this->SubjectStudents->save($subjectStudentEntity);
                }
            }


        }
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $query = $Staff->find('all')
            ->select([
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name'
            ])
            ->find('byInstitution', ['Institutions.id' => $extra['institution_id']])
            ->find('byPositions', ['Institutions.id' => $extra['institution_id'], 'type' => 1]) // refer to OptionsTrait for type options
            ->find('AcademicPeriod', ['academic_period_id' => $extra['selectedAcademicPeriodId']])
            ->contain(['Users'])
            ->where([
                $Staff->aliasField('institution_position_id'),
                'OR' => [ //check teacher end date
                    [$Staff->aliasField('end_date') . ' > ' => new FrozenDate()],
                    [$Staff->aliasField('end_date') . ' IS NULL']
                ]
            ])
            ->toArray();

        $teachers = [0 => '-- ' . __('Select Teacher or Leave Blank') . ' --'];
        foreach ($query as $key => $value) {
            //POCOR-8324 Starts
            // if ($value->has('Users')) {
            //     $teachers[$value->Users->id] = $value->Users->name;
            // }
            if ($value->has('user')) {
                $teachers[$value->user->id] = $value->user->name;
            }//POCOR-8324 ends
        }
        $subjects = $this->getSubjectOptions($extra['selectedClassId']);
        $existedSubjects = $this->getExistedSubjects($extra['selectedClassId'], true);
        $this->fields['subjects']['data'] = [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'existedSubjects' => $existedSubjects
        ];
    }
    //POCOR-8324 starts
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->checkRecordExists($entity)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }

    public function checkRecordExists($entity)
    {
        $InstitutionSubjectStaffs = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');//POCOR-8324
        $associatedInstitutionSubjectStaffCount = $InstitutionSubjectStaffs->find()
            ->where([
                $InstitutionSubjectStaffs->aliasField('institution_subject_id') => $entity->id,
                $InstitutionSubjectStaffs->aliasField('institution_id') => $entity->institution_id
            ])
            ->count();
        //POCOR-8481 starts
        // check student
        $SubjectStudents  =  TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $associatedExistingStudents = $SubjectStudents
            ->find('all')
            ->select([
                'id', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'institution_id',
                'student_status_id', 'institution_subject_id', 'education_subject_id'
            ])
            ->where([
                $SubjectStudents->aliasField('education_subject_id') => $entity->education_subject_id,
                $SubjectStudents->aliasField('institution_subject_id') => $entity->id
            ])
            ->count();
        //POCOR-8481 ends
        $InstitutionTextbooks = TableRegistry::getTableLocator()->get('Institution.InstitutionTextbooks');//POCOR-8324
        $associatedTextbooksCount = $InstitutionTextbooks->find()
            ->where([
                $InstitutionTextbooks->aliasField('education_subject_id') => $entity->education_subject_id,
                $InstitutionTextbooks->aliasField('academic_period_id') => $entity->academic_period_id
            ])
            ->count();

        $totalCount = $associatedInstitutionSubjectStaffCount + $associatedExistingStudents + $associatedTextbooksCount;//POCOR-8481
        return $totalCount;
    }//POCOR-8324 ends

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects'); //POCOR-6242
        $extra['excludedModels'] = [
            $this->ClassSubjects->getAlias(),
            $this->SubjectStudents->getAlias(),
            $this->SubjectStaff->getAlias(),
            $this->Classes->getAlias(),
            $InstitutionClassSubjects->getAlias() //POCOR-6242
        ];

        //check textbook
        $InstitutionTextbooks = TableRegistry::getTableLocator()->get('Institution.InstitutionTextbooks');//POCOR-8324
        $associatedTextbooksCount = $InstitutionTextbooks->find()
            ->where([
                $InstitutionTextbooks->aliasField('education_subject_id') => $entity->education_subject_id,
                $InstitutionTextbooks->aliasField('academic_period_id') => $entity->academic_period_id
            ])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Institution Textbooks', 'count' => $associatedTextbooksCount];
    }

    public function deleteAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

    }

    /******************************************************************************************************************
     **
     ** essential functions
     **
     ******************************************************************************************************************/
    public function prepareEntityObjects($model, ArrayObject $data, ArrayObject $extra)
    {
        $commonData = $data['InstitutionSubjects'];
        $error = false;
        $subjects = false;
        $subjectOptions = $this->getSubjectOptions($extra['selectedClassId']);
        $existedSubjects = $this->getExistedSubjects($extra['selectedClassId'], true);

        $subjectmatch = $this->getSubjectsMatchOrNot($data);

        if (isset($subjectmatch)) {
            if (count($subjectOptions) == count($existedSubjects)) {
                $error = $this->aliasField('allSubjectsAlreadyAdded');
            }
        }
        if (isset($data['MultiSubjects']) && count($data['MultiSubjects']) > 0) {
            foreach ($data['MultiSubjects'] as $key => $row) {
                // echo "<pre>"; print_r($data['InstitutionSubjects']['education_grade_id']); die();
                if (isset($row['education_subject_id']) && isset($row['subject_staff'])) {
                    $subjectSelected = true;
                    $subjects[$key] = [
                        'key' => $key,
                        'name' => $row['name'],
                        'education_grade_id' => !empty($data['InstitutionSubjects']['education_grade_id']) ? $data['InstitutionSubjects']['education_grade_id'] : $row['education_grade_id'],
                        'education_subject_id' => $row['education_subject_id'],
                        'academic_period_id' => $commonData['academic_period_id'],
                        'institution_id' => $commonData['institution_id'],
                        'class_subjects' => [
                            [
                                'status' => 1,
                                'institution_class_id' => $commonData['class_name'],
                                'institution_subject_id' =>  $row['education_subject_id'] //POCOR-8323 It is necessary to show array without institution_subject_id validation
                            ]
                        ]
                    ];
                    if ($row['subject_staff'][0]['staff_id'] != 0) {
                        $row['subject_staff'][0]['institution_id'] = $commonData['institution_id'];

                        $subjects[$key]['subject_staff'] = $row['subject_staff'];
                    }
                }
            }

            if (!$subjects) {
                $error = $this->aliasField('noSubjectSelected');
            } else {
                $subjects = $model->newEntities($subjects);
                /**
                 * check individual entity for any error
                 */
                foreach ($subjects as $subject) {
                    if ($subject->getErrors()) {
                        $error = $subject->getErrors();
                        $data['MultiSubjects'][$subject->key]['errors'] = $error;
                    }
                }
            }
        } else {
            // $this->log(__FILE__.' @ '.__LINE__.': noSubjectsInClass', 'debug');
            $error = $this->aliasField('noSubjectsInClass');
        }

        return [$error, $subjects, $data];
    }

    public function getSubjectsMatchOrNot($data)
    {
        $academicPeriodId = $data['InstitutionSubjects']['academic_period_id'];
        $className = $data['InstitutionSubjects']['class_name'];
        $institutionId = $data['InstitutionSubjects']['institution_id'];
        $educationGrade = $data['InstitutionSubjects']['education_grade_id'];
        if (isset($data['MultiSubjects'])) {
            foreach ($data['MultiSubjects'] as $key => $row) {
                if (isset($row['education_subject_id']) && isset($row['subject_staff'])) {
                    $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
                    $results = $InstitutionSubjects->find('all')
                        ->where([
                            $InstitutionSubjects->aliasField('academic_period_id') => $academicPeriodId,
                            $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                            $InstitutionSubjects->aliasField('education_subject_id') => $row['education_subject_id'],
                            // $InstitutionSubjects->aliasField('education_grade_id') => $row['education_grade_id'],
                            $InstitutionSubjects->aliasField('education_grade_id') => !empty($educationGrade) ? $educationGrade : $row['education_grade_id'],
                            $InstitutionSubjects->aliasField('name') . ' LIKE' => '%' . $row['name'] . '%',

                        ])
                        ->toArray();
                    if ($results) {
                        $subject = [];
                        foreach ($results as $k => $value) {
                            $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
                            $subject[] = $InstitutionClassSubjects->find('all')
                                ->where([
                                    $InstitutionClassSubjects->aliasField('institution_class_id') => $className,
                                    $InstitutionClassSubjects->aliasField('institution_subject_id') => $value['id'],
                                ])
                                ->first();
                            if (isset($subject[$k])) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
    }

    public function createVirtualEntity($id, $entity, $persona, $requestData = false)
    {
        if (isset($entity->toArray()['class_subjects'])) {
            $classId = $entity->toArray()['class_subjects'][0]['institution_class_id'];
        } else {
            $classId = $entity->toArray()['institution_classes'][0]['id'];
        }
        $data = [
            'id' => $this->getExistingRecordId($id, $entity, $persona),
            'student_id' => $id,
            'institution_subject_id' => $entity->id,
            'institution_class_id' => $classId,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'education_subject_id' => $entity->education_subject_id
        ];

        if (strtolower($persona) == 'students') {
            // find the latest student status id of student in the class
            $ClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
            $userData = $ClassStudents->find()
                ->contain(['Users.Genders', 'StudentStatuses'])
                ->where([
                    $ClassStudents->aliasField('student_id') => $id,
                    $ClassStudents->aliasField('institution_id') => $entity->institution_id,
                    $ClassStudents->aliasField('institution_class_id') => $entity->classes[0]->id,
                    $ClassStudents->aliasField('academic_period_id') => $entity->academic_period_id
                ])
                ->order([$ClassStudents->aliasField('created') => 'DESC'])
                ->first();

            if (empty($userData)) {
                $this->Alert->warning($this->getAlias() . ".studentRemovedFromInstitution");
            } else {
                $data['student_status_id'] = $userData->student_status_id;
                $data['education_grade_id'] = $userData->education_grade_id;
                $data['user'] = [];
                $data['student_status'] = []; // student status entity (to retrieve student status name)
            }
        } else {
            $userData = $this->Institutions->Staff->find()->contain(['Users' => ['Genders']])->where(['staff_id' => $id])->first();
            if (empty($userData)) {
                $this->Alert->warning($this->getAlias() . ".staffRemovedFromInstitution");
            } else {
                $data['user'] = [];
            }
        }
        if (isset($data['user'])) {
            $model = 'Subject' . ucwords(strtolower($persona));
            $newEntity = $this->{$model}->newEntity();
            $newEntity = $this->{$model}->patchEntity($newEntity, $data);
            $newEntity->user = $userData->user;
            $newEntity->student_status = $userData->student_status;
            return $newEntity;
        }
    }

    protected function getExistingRecordId($id, $entity, $persona)
    {
        $recordId = '';
        $relationKey = 'subject_' . strtolower($persona);
        foreach ($entity->{$relationKey} as $data) {
            if (strtolower($persona) == 'students') {
                if (is_object($data)) {
                    if ($data->student_id == $id) {
                        $recordId = $data->id;
                    }
                } elseif (isset($data['student_id'])) {
                    if ($data['student_id'] == $id) {
                        $recordId = $data['id'];
                    }
                }
            } else {
                if ($data->staff_id == $id) {
                    $recordId = $data->id;
                }
            }
        }
        return $recordId;
    }

    private function getAcademicPeriodOptions($institutionId)
    {
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $conditions = [$InstitutionGrades->aliasField('institution_id') => $institutionId];
        // pr($InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions));
        return $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
    }

    public function getSubjectOptions($selectedClassId, $listOnly = false)
    {
        $Grade = $this->InstitutionClassGrades;
        $gradeOptions = $Grade->find('list', [
            'keyField' => 'education_grade.id',
            'valueField' => 'education_grade.name'
        ])
            ->contain('EducationGrades')
            ->where([
                $Grade->aliasField('institution_class_id') => $selectedClassId
            ])
            ->toArray();
        $data = [];
        if (!empty($gradeOptions)) {
            $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
            /**
             * Do not check for the visible attribute in sql query,
             * message the data in the view file instead so that we could counter-check for
             * subjects that are already created in the institution.
             */
            $query = $EducationGradesSubjects
                ->find()
                ->contain(['EducationSubjects'])
                ->where([
                    'EducationGradesSubjects.education_grade_id IN ' => array_keys($gradeOptions),
                ]);
            $subjects = $query
                ->order('EducationSubjects.order')
                ->group('EducationSubjects.id')
                ->toArray();
            if ($listOnly) {
                $subjectList = [];
                foreach ($subjects as $key => $value) {
                    $subjectList[$value->id] = $value->education_subject->name;
                }
                $data = $subjectList;
            } else {
                $data = $subjects;
            }
        }
        if (empty($data)) {
            // $this->log(__FILE__.' @ '.__LINE__.': noSubjectsInClass', 'debug');
            // $this->Alert->warning('Institution.Institutions.noSubjectsInClass');
        }
        return $data;
    }

    private function getExistedSubjects($selectedClassId, $listOnly = false)
    {
        $classSubjects = $this->ClassSubjects
            ->find()
            ->contain([
                'InstitutionSubjects' => [
                    'EducationSubjects',
                    'Teachers' => function ($q) {
                        return $q
                            ->where([
                                'OR' => [
                                    ['end_date IS NULL'],
                                    ['end_date' . ' >= ' => FrozenDate::now()]
                                ]
                            ]);
                    },
                    'Teachers.Genders'
                ],
            ])
            ->where([
                $this->ClassSubjects->aliasField('institution_class_id') => $selectedClassId,
                $this->ClassSubjects->aliasField('status') => 1
            ])
            ->toArray();

        if ($listOnly) {
            $subjectList = [];
            foreach ($classSubjects as $key => $classSubject) {
                $subjectList[$classSubject->institution_subject->education_subject->id] = [
                    'name' => $classSubject->institution_subject->name,
                    'subject_name' => $classSubject->institution_subject->education_subject->name,
                    'teachers' => $classSubject->institution_subject->teachers
                ];
            }
            $data = $subjectList;
        } else {
            $data = $classSubjects;
        }

        return $data;
    }

    /**
     * @todo should have additional filter; by start_date, end_date,
     */
    protected function getTeacherOptions($entity)
    {
        $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $query = $Staff->find('all')
            ->select([
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name'
            ])
            ->find('byInstitution', ['Institutions.id' => $entity->institution_id])
            ->find('byPositions', ['Institutions.id' => $entity->institution_id, 'type' => 1]) // refer to OptionsTrait for type options
            ->find('AcademicPeriod', ['academic_period_id' => $entity->academic_period_id])
            ->contain(['Users'])
            ->where([
                $Staff->aliasField('institution_position_id'),
                'OR' => [ //check teacher end date
                    [$Staff->aliasField('end_date') . ' > ' => new FrozenDate()],
                    [$Staff->aliasField('end_date') . ' IS NULL']
                ]
            ]);
        $options = [];
        foreach ($query->toArray() as $key => $value) {
            if ($value->has('Users')) {
                $options[$value->Users->id] = $value->Users->name_with_id;
            }
        }
        return $options;
    }

    protected function getRoomOptions($entity)
    {
        $options = [];

        $rooms = $this->Rooms
            ->find('inUse', ['institution_id' => $entity->institution_id, 'academic_period_id' => $entity->academic_period_id])
            ->contain(['RoomTypes'])
            ->where(['RoomTypes.classification' => 1]) // classification 1 is equal to Classroom, 0 is Non_Classroom
            ->order(['RoomTypes.order', $this->Rooms->aliasField('code'), $this->Rooms->aliasField('name')])
            ->toArray();

        foreach ($rooms as $key => $obj) {
            $options[$obj->room_type->name][$obj->id] = $obj->code_name;
        }

        return $options;
    }

    /**
     * Changed in PHPOE-1780 test fail re-work. major modification.
     * Previously, the grades where populated based on a selected classId.
     * Those students who matched one of the grades will be included in the list.
     *
     * Since there will be more than one class where a subject could be linked to, the logic is changed to populate
     * students using a longer route to obtain the grades for the current academic period.
     * student_status_id = 1 is also included.
     * @var integer
     * @return array list of students
     *
     * @todo  modify the search to increase performance
     */
    protected function getStudentsOptions($entity, $includedStudents = [])
    {
        // from $entity, you can get the subject_id which you can use it to retrieve the list of grade_id from education_grades_subjects
        // from the list of grade_ids, you will use it to find the list of students from institution_class_students using grade_id and the class keys as conditions
        $classKeys = [];
        foreach ($entity->class_subjects as $classSubjects) {
            $classKeys[] = $classSubjects->institution_class_id;
        }
        $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
        $grades = $EducationGradesSubjects
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'education_grade_id'
            ])
            ->where([
                $EducationGradesSubjects->aliasField('education_subject_id') => $entity->education_subject_id,
                $EducationGradesSubjects->aliasField('visible') => 1
            ])
            ->toArray();

        $Students = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        if (!empty($grades)) {
            $conditions[$Students->aliasField('education_grade_id') . ' IN'] = $grades;
        }

        $conditions[$Students->aliasField('institution_class_id') . ' IN'] = $classKeys;
        /**
         * Attempt to improve performance by filtering out includedStudents in $studentOptions through SQL query
         */
        if (!empty($includedStudents)) {
            $conditions[$Students->aliasField('student_id') . ' NOT IN'] = $includedStudents;
        }

        $query = $Students
            ->find('all')
            ->matching('Users')
            ->where($conditions)
            ->toArray();

        /**
         * default $studentOptions options
         */
        $studentOptions = ['-1' => $this->getMessage('Users.select_student'), '0' => $this->getMessage('Users.add_all_student')];
        foreach ($query as $student) {
            if ($student->has('_matchingData')) {
                $user = $student->_matchingData['Users'];
                if (!$this->InstitutionStudents->exists([$this->InstitutionStudents->aliasField('student_id') => $user->id])) {
                    $this->log('Data corrupted with no institution student: ' . $student->id . ' @ ' . $this->registryAlias() . ': ' . __LINE__, 'debug');
                } else {
                    $studentOptions[$user->id] = $user->name_with_id;
                }
            } else {
                $this->log('Data corrupted with no security user for student: ' . $student->id, 'debug');
            }
        }
        return $studentOptions;
    }

    public function autoInsertSubjectsByClass(Entity $entity)
    {
        $errors = $entity->getErrors();
        if (empty($errors)) {
            /**
             * get the list of education_grade_id from the education_grades array
             */
            $grades = (new Collection($entity->education_grades))->extract('id')->toArray();
            $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
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
                                    'GradesSubjects.visible' => 1,
                                    //'GradesSubjects.auto_allocation' => 1
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
                    if (!empty($gradeSubject->education_subjects)) {
                        foreach ($gradeSubject->education_subjects as $subject) {
                            /*POCOR-6368 starts*/
                            $institutionProgramGradeSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionProgramGradeSubjects')
                                ->find()
                                ->where([
                                    'InstitutionProgramGradeSubjects.education_grade_id' => $gradeSubject->id,
                                    'InstitutionProgramGradeSubjects.institution_id' => $entity->institution_id
                                ])
                                ->toArray();
                            if (!empty($institutionProgramGradeSubjects)) {
                                foreach ($institutionProgramGradeSubjects as $subject) {
                                    $eduSubjects = $this->EducationSubjects->get($subject->education_grade_subject_id);
                                    $educationSubjects[$subject->education_grade_id . '_' . $subject->education_grade_subject_id] = [
                                        'id' => $eduSubjects->id,
                                        'education_grade_id' => $subject->education_grade_id,
                                        'name' => $eduSubjects->name
                                    ];
                                }
                            }
                            /*POCOR-6368 ends*/
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
                $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
                $institutionSubjects = $InstitutionSubjects->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'education_subject_id'
                ])
                    ->where([
                        $InstitutionSubjects->aliasField('academic_period_id') => $entity->academic_period_id,
                        $InstitutionSubjects->aliasField('institution_id') => $entity->institution_id,
                        $InstitutionSubjects->aliasField('education_subject_id') . ' IN' => array_column($educationSubjects, 'id')
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
                $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
                $newSchoolSubjects = [];

                foreach ($educationSubjects as $key => $educationSubject) {
                    $existingSchoolSubjects = false;
                    if (array_key_exists($key, $institutionSubjectsIds)) {
                        $existingSchoolSubjects = $InstitutionClassSubjects->find()
                            ->where([
                                $InstitutionClassSubjects->aliasField('institution_class_id') => $entity->id,
                                $InstitutionClassSubjects->aliasField('institution_class_id') . ' IN' => $institutionSubjectsIds[$key],
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
                            'academic_period_id' => $entity->academic_period_id,
                            'class_subjects' => [
                                [
                                    'status' => 1,
                                    'institution_class_id' => $entity->id,
                                    'institution_subject_id' =>  $educationSubject['id'] //POCOR-8323 It is necessary to show array without institution_subject_id validation
                                ]
                            ]
                        ];
                    }
                }

                if (!empty($newSchoolSubjects)) {
                    $programsubjects = 0;
                    $newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
                    foreach ($newSchoolSubjects as $subject) {    //POCOR 5001
                        //POCOR-5932 starts
                        /*$institutionProgramGradeSubjects =
                            TableRegistry::getTableLocator()->get('InstitutionProgramGradeSubjects')
                            ->find('list')
                            ->where(['InstitutionProgramGradeSubjects.education_grade_id' => $subject->education_grade_id,
                                'InstitutionProgramGradeSubjects.education_grade_subject_id' => $subject->education_subject_id,
                                'InstitutionProgramGradeSubjects.institution_id' => $subject->institution_id
                                ])
                            ->count();

                        if($institutionProgramGradeSubjects > 0){*/
                        $programsubjects++;
                        $InstitutionSubjects->save($subject);
                        //}//POCOR-5932 ends
                    }
                    unset($subject);
                    //POCOR-5932 starts
                    /*if ($programsubjects == 0) {
                        foreach ($newSchoolSubjects as $subject) {
                        $InstitutionSubjects->save($subject);
                        }
                    }*/ //POCOR-5932 ends
                }
                unset($newSchoolSubjects);
                unset($InstitutionSubjects);
                unset($InstitutionClassSubjects);
            }
        }
    }

    public function onGetEducationGradeId(EventInterface $event, Entity $entity)
    {
        return $entity->education_grade->name;
    }

    public function onGetTeachers(EventInterface $event, Entity $entity)
    {
        if ($entity->has('teachers')) {
            $resultArray = [];
            $todayDate = new FrozenDate();

            foreach ($entity->teachers as $key => $value) {
                $staffEndDate = $value->_joinData->end_date;

                if ($staffEndDate >= $todayDate || $staffEndDate == null || empty($staffEndDate)) {
                    switch ($this->action) {
                        case 'view':
                            $resultArray[] = $event->getSubject()->Html->link($value->name_with_id, [
                                'plugin' => 'Institution',
                                'controller' => 'Institutions',
                                'action' => 'StaffUser',
                                'view',
                                $this->paramsEncode(['id' => $value->id,  'institution_id'=> $entity->institution_id, 'staff_id' => $value->id])
                            ]);
                            break;

                        case 'index':
                            $resultArray[] = $value->name_with_id;
                            break;

                        default:
                            $resultArray = null;
                            break;
                    }
                } else {
                    unset($entity->teachers[$key]); //if teacher end date is earlier than today, then unset from entity
                }
            }
        }

        if (!empty($resultArray)) {
            return implode(', ', $resultArray);
        }
    }

    public function onGetRooms(EventInterface $event, Entity $entity)
    {
        if ($entity->has('rooms')) {
            $resultArray = [];

            foreach ($entity->rooms as $key => $obj) {
                $resultArray[] = $obj->code_name;
            }

            if (!empty($resultArray)) {
                return implode(', ', $resultArray);
            }
        }
    }

    /******************************************************************************************************************
     **
     ** field specific methods
     **
     ******************************************************************************************************************/

    public function onGetTotalStudents(EventInterface $event, Entity $entity)
    {
        /*POCOR-6463 starts*/
        $array_data = [];
        $subjectId = $entity->id;
        $institutionId = $entity->institution_id;
        $periodId = $entity->academic_period_id;
        $institutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $totalStudentCount = $institutionSubjectStudents->find()
            ->where([
                $institutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
                $institutionSubjectStudents->aliasField('institution_id') => $institutionId,
                $institutionSubjectStudents->aliasField('academic_period_id') => $periodId,
                $institutionSubjectStudents->aliasField('student_status_id') => 1,
            ])->group([$institutionSubjectStudents->aliasField('student_id')]) //POCOR-6768
            ->count();
        //echo "<pre>"; print_r($totalStudentCount); exit;
        // foreach ($entity->subject_students as $key => $data) {
        //     if ($data->student_status_id == 1) {
        //         $array_data[$data->student_status_id] = ++$array_data[$data->student_status_id];
        //     }
        // }

        //return $entity->classes[0]['total_male_students'] + $entity->classes[0]['total_female_students'];
        return $totalStudentCount;
        /*POCOR-6463 ends*/
    }

    /*POCOR-6463 starts*/
    public function onGetTotalMaleStudents(EventInterface $event, Entity $entity)
    {
        $subjectId = $entity->id;
        $genderId = 1; // male
        $institutionId = $entity->institution_id;
        $periodId = $entity->academic_period_id;
        $institutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $totalMaleStudentCount = $institutionSubjectStudents->find()
            ->leftJoin([$users->getAlias() => $users->getTable()], [
                $users->aliasField('id = ') . $institutionSubjectStudents->aliasField('student_id')
            ])
            ->where([
                $institutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
                $institutionSubjectStudents->aliasField('institution_id') => $institutionId,
                $institutionSubjectStudents->aliasField('academic_period_id') => $periodId,
                $institutionSubjectStudents->aliasField('student_status_id') => 1,
                $users->aliasField('gender_id') => $genderId
            ])->group([$institutionSubjectStudents->aliasField('student_id')]) //POCOR-6768
            ->count();

        return $totalMaleStudentCount;
    }

    public function onGetTotalFemaleStudents(EventInterface $event, Entity $entity)
    {
        $subjectId = $entity->id;
        $genderId = 2; // female
        $institutionId = $entity->institution_id;
        $periodId = $entity->academic_period_id;
        $institutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $users = TableRegistry::getTableLocator()->get('User.Users');
        $totalFemaleStudentCount = $institutionSubjectStudents->find()
            ->leftJoin([$users->getAlias() => $users->getTable()], [
                $users->aliasField('id = ') . $institutionSubjectStudents->aliasField('student_id')
            ])
            ->where([
                $institutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
                $institutionSubjectStudents->aliasField('institution_id') => $institutionId,
                $institutionSubjectStudents->aliasField('academic_period_id') => $periodId,
                $institutionSubjectStudents->aliasField('student_status_id') => 1,
                $users->aliasField('gender_id') => $genderId
            ])
            ->group([$institutionSubjectStudents->aliasField('student_id')]) //POCOR-6768
            ->count();

        return $totalFemaleStudentCount;
    }
    /*POCOR-6463 ends*/
    public function onExcelGetTotalStudents(EventInterface $event, Entity $entity)
    {
        return $entity->total_male_students + $entity->total_female_students;
    }

    //called by ControllerActionHelper incase extra search highlighted
    // public function getSearchableFields(EventInterface $event, $fields, ArrayObject $searchableFields) {
    //  $searchableFields[] = "education_subject_id";
    // }

    public function getPastTeachers($entity)
    {
        $todayDate = new FrozenDate();
        $data = [];
        if ($entity->has('teachers')) {
            foreach ($entity->teachers as $key => $value) {
                if ($value->has('_joinData')) {
                    if (!empty($value->_joinData->end_date)) {
                        $endDate = $value->_joinData->end_date;
                        if ($endDate < $todayDate) { //for end of assignment teachers
                            $data[$key]['id'] = $value->id;
                            $data[$key]['name'] = $value->name_with_id;
                            $data[$key]['start_date'] = $value->_joinData->start_date->format('d-m-Y');;
                            $data[$key]['end_date'] = $value->_joinData->end_date->format('d-m-Y');;
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function getSubjectsByClass($classId)
    {

        $classSubjects = $this->ClassSubjects
            ->find()
            ->contain(['InstitutionSubjects'])
            ->where([
                $this->ClassSubjects->aliasField('institution_class_id') => $classId,
                $this->ClassSubjects->aliasField('status') => 1
            ])
            ->toArray();
        return $classSubjects;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        //echo "<pre>";  print_r($cloneFields); exit;
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if ($value['field'] == 'rooms') {

                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_male_students',
                    'field' => 'total_male_students',
                    'type' => 'string',
                    'label' => 'Total Male Student'
                ];

                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_female_students',
                    'field' => 'total_female_students',
                    'type' => 'string',
                    'label' => 'Total Female Student'
                ];

                $newFields[] = [
                    'key' => '',
                    'field' => 'total_students',
                    'type' => 'integer',
                    'label' => 'Total Students'
                ];
            }
        }
        //print_r($newFields); exit;
        $fields->exchangeArray($newFields);
    }

    // POCOR-6128 start
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $extra, Query $query)
    {
        $institutionId = $this->getInstitutionID();
        $requestQuery = $this->request->getQuery();
        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $query
            ->select([
                'total_male_students' => 'InstitutionSubjects.total_male_students',
                'total_female_students' => 'InstitutionSubjects.total_female_students',
                'institution_subject_id' => 'InstitutionSubjects.id'
            ])
            ->where([
                $this->aliasField('academic_period_id = ') . $selectedAcademicPeriodId,
                $this->aliasField('institution_id = ') . $institutionId,
            ]);

        /**
         * added condition to make query on the bases on selected subject and exporting student's list
         * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
         * @ticket POCOR-6635 starts
         */
        //$encodedSubjectId = $this->request->getAttribute('params')['pass'][1];//POCOR-8324
        $checkEncodedSubjectId = $this->request->getAttribute('params')['pass'][1];//POCOR-8324
        $encodedSubjectId = $this->paramsDecode($checkEncodedSubjectId);//POCOR-8324
        if (isset($encodedSubjectId['institution_subject_id'])) {//POCOR-8324
            $query;
        } else {
            $query->group('InstitutionSubjects.id');
        }
        //POCOR-6635 ends
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                // GETTING ROOMS FOR EACH SUBJECT
                $institutionRooms = TableRegistry::getTableLocator()->get('Institution.InstitutionRooms');
                $institutionSubjectRooms = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectsRooms');
                $institutionRoomsRow = $institutionRooms
                                    ->find()
                                    ->select([
                                        $institutionRooms->aliasField('code'),
                                        $institutionRooms->aliasField('name')
                                    ])
                                    ->leftJoin(
                                        [$institutionSubjectRooms->getAlias() => $institutionSubjectRooms->getTable()],
                                        [$institutionRooms->aliasField('id') . ' = ' . $institutionSubjectRooms->aliasField('institution_room_id')]
                                    )
                                    ->where([$institutionSubjectRooms->getAlias() . '.institution_subject_id' => $row->institution_subject_id])
                                    ->first();

                if (!empty($institutionRoomsRow)) {
                    $row['rooms'] = $institutionRoomsRow->code . ' - ' . $institutionRoomsRow->name;
                } else {
                    $row['rooms'] = '';
                }
                // GETTING ROOMS FOR EACH SUBJECT

                // GET TEACHERS FOR EACH SUBJECT
                $institutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
                $staffTable = TableRegistry::getTableLocator()->get('Security.Users');

                $institutionStaffTeachers = $staffTable
                                        ->find()
                                        ->select([
                                            $staffTable->aliasField('openemis_no'),
                                            $staffTable->aliasField('first_name'),
                                            $staffTable->aliasField('last_name')
                                        ])
                                        ->innerJoin(
                                            [$institutionSubjectStaff->getAlias() => $institutionSubjectStaff->getTable()],
                                            [$staffTable->aliasField('id') . ' = ' . $institutionSubjectStaff->aliasField('staff_id')]
                                        )
                                        ->where([$institutionSubjectStaff->getAlias() . '.institution_subject_id' => $row->institution_subject_id])
                                        ->first();


                if (!empty($institutionStaffTeachers)) {
                    $row['teachers'] = $institutionStaffTeachers->openemis_no . ' - ' . $institutionStaffTeachers->first_name . ' ' . $institutionStaffTeachers->last_name;
                } else {
                    $row['teachers'] = '';
                }
                // GET TEACHERS FOR EACH SUBJECT

                return $row;
            });
        });
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'class_name') {
            return __('Class Name');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'education_subject_id') {
            return __('Subjects');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'total_students') {
            return __('Total Students');
        } elseif ($field == 'teachers') {
            return __('Teachers');
        } elseif ($field == 'rooms') {
            return __('Rooms');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    /**
     * Attach field names to the provided data entity by fetching associated details.
     *
     * This function queries the database to fetch related data for a given entity ID
     * and populates the `fieldNames` array with information like academic period name,
     * institution code, education grade details, class name, and subject code.
     *
     * @param \Cake\ORM\Entity $data The entity data to which field names will be attached.
     * @return \Cake\ORM\Entity The modified entity with the `fieldNames` attribute populated.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException If no record is found for the given ID.
     *
     * @author [Megha Gupta <barkha@madvit.com>]
     * @since 2024-12-30
     * @task POCOR-8706
     */
    public function attachFieldNames($data)
    {
        $fieldNames = [];
        $subjectData = $this->find('all', [
            'contain' => [
                'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems',
                'AcademicPeriods',
                'EducationSubjects',
                'Institutions',
                'Teachers',
                'Students',
                'Classes'
            ],
        ])->where([$this->aliasField('id') => $data->id])->first();

        if ($subjectData) {
            $fieldNames = [
                'academic_period_name' => $subjectData->academic_period->name ?? null,
                'institution_code' => $subjectData->institution->code ?? null,
                'education_grade_code' => $subjectData->education_grade->code ?? null,
                'education_grade_name' => $subjectData->education_grade->name ?? null,
                'class_name' => $subjectData->class_name ?? null,
                'subject_code' => $subjectData->education_subject_code ?? null,
            ];
        }

        $data['fieldNames'] = $fieldNames;
        return $data;
    }
}
