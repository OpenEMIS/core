<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionSubjectsTable extends ControllerActionTable
{
    use MessagesTrait;
    private $enrolledStatus;

    public function initialize(array $config)
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
            'through' => 'InstitutionClassSubjects',
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

        /**
         * Short cuts
         */
        $this->InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $this->InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'SubjectStudents' => ['view', 'edit'],
            'ReportCardComments' => ['index'],
            'StudentOutcomes' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';

        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'education_grade_id';
        $searchableFields[] = 'education_subject_id';
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('name')
            ->requirePresence('class_subjects')
            ->notEmpty('class_subjects')
            ->add('class_subjects', 'ruleCheckDuplicateClassSubjects', [
                'rule' => function ($check, $global) {
                    if ($global['newRecord']) {
                        return true;
                    }
                    $institutionSubjectId = $global['data']['id'];
                    // die;
                    $ClassSubjectsTable = TableRegistry::get('Institution.InstitutionClassSubjects');

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
            ]);
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['institution_id'] = $this->Session->read('Institution.Institutions.id');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $this->enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

        $this->field('education_grade_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>false, 'add'=>true], 'onChangeReload' => true, 'sort' => ['field' => 'EducationGrades.name']]);
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true, 'add'=>true], 'onChangeReload' => true]);
        $this->field('created', ['type' => 'string', 'visible' => false]);
        $this->field('created_user_id', ['type' => 'string', 'visible' => false]);
        $this->field('education_subject_code', ['type' => 'string', 'visible' => ['view'=>true]]);
        $this->field('education_subject_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('modified', ['type' => 'string', 'visible' => false]);
        $this->field('modified_user_id', ['type' => 'string', 'visible' => false]);
        $this->field('name', ['type' => 'string', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'sort' => ['field' => 'EducationSubjects.name']]);
        $this->field('no_of_seats', ['type' => 'integer', 'attr'=>['min' => 1], 'visible' => false]);
        $this->field('class_name', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true], 'onChangeReload' => true]);

        $this->field('students', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Subjects/students',
            'data' => [
                'students'=>[],
                'studentOptions'=>[],
                'categoryOptions'=>[]
            ],
            'visible' => ['view'=>true, 'edit'=>true]
        ]);
        $this->field('subjects', [
            'label' => '',
            'type' => 'element',
            'element' => 'Institution.Subjects/subjects',
            'data' => [
                'subjects'=>[],
                'teachers'=>[]
            ],
            'visible' => false
        ]);

        $this->field('teachers', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'teachers',
            'fieldName' => $this->alias() . '.teachers._ids',
            'placeholder' => $this->getMessage('Users.select_teacher'),
            'valueWhenEmpty' => '<span>&lt;'.__('No Teacher Assigned').'&gt;</span>',
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
            'fieldName' => $this->alias() . '.rooms._ids',
            'placeholder' => $this->getMessage('Users.select_room'),
            'valueWhenEmpty' => '<span>&lt;'.__('No Room Allocated').'&gt;</span>',
            'visible' => ['index' => true, 'view' => true, 'edit' => true]
        ]);

        $this->field('total_students', [
            'type' => 'integer',
            'visible' => ['index'=>true]
        ]);

        $this->setFieldOrder([
            'name', 'education_grade_id', 'education_subject_id', 'class_name', 'teachers', 'rooms', 'total_male_students', 'total_female_students','total_students',
        ]);

        $academicPeriodOptions = $this->getAcademicPeriodOptions($extra['institution_id']);
        if (empty($academicPeriodOptions)) { //cant find programme which date range within available academic period
            $this->Alert->warning('InstitutionSubjects.noProgrammes');
            $extra['noProgrammes'] = true;
        }

        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $extra['selectedAcademicPeriodId'] = $this->queryString('academic_period_id', $academicPeriodOptions);
        $extra['selectedClassId'] = 0;
    }


    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $Classes = $this->Classes;
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
                ->hydrate(false)
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
                            'conditions' => [
                                'InstitutionClassSubjects.institution_subject_id = ' . $Subjects->aliasField('id'),
                                'InstitutionClassSubjects.institution_class_id' => $id
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

        $extra['elements']['control'] = [
            'name' => 'Institution.Subjects/controls',
            'data' => [
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
    }

    public function findTranslateItem(Query $query, array $options)
    {
        return $query
            ->formatResults(function ($results) {
                $arrResults = $results->toArray();
                foreach ($arrResults as &$value) {
                    if (isset($value['subject_students']) && is_array($value['subject_students'])) {
                        foreach ($value['subject_students'] as $student) {
                            $student['student_status']['name'] = __($student['student_status']['name']);
                        }
                    }
                }
                return $arrResults;
            });
    }

    public function findBySubjectsInClass(Query $query, array $options)
    {
        $classId = $options['institution_class_id'];
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $gradeId = $options['education_grade_id'];

        return $query
            ->matching('ClassSubjects', function ($q) use ($classId) {
                return $q->where(['ClassSubjects.institution_class_id' => $classId]);
            })
            ->contain(['EducationSubjects'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('education_grade_id') => $gradeId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order('EducationSubjects.order');
    }

    public function findSubjectDetails(Query $query, array $options)
    {

        // POCOR-2547 sort list of staff and student by name
        // move the contain from institution.subject.student.ctrl.js since its using finder method
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
                'SubjectStudents.InstitutionClasses'
            ]);
    }

    public function findByClasses(Query $query, array $options)
    {
        return $query
            ->join([
                [
                    'table' => 'institution_class_subjects',
                    'alias' => 'InstitutionClassSubjects',
                    'conditions' => [
                        'InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id',
                        'InstitutionClassSubjects.institution_class_id' => $options['selectedClassId']
                    ]
                ]
            ])
            ;
    }

    // used for student report cards
    public function findTeacherEditPermissions(Query $query, array $options)
    {
        $reportCardId = $options['report_card_id'];
        $institutionId = $options['institution_id'];
        $classId = $options['institution_class_id'];
        $staffId = $options['staff_id'];

        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $ReportCardSubjects = TableRegistry::get('ReportCards.ReportCardSubjects');

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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->find('byClasses', ['selectedClassId' => $extra['selectedClassId']])
            ->contain(['Teachers', 'Rooms', 'EducationSubjects', 'EducationGrades', 'Classes'])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']]);

        // search function to search education grade and education subject
        $searchKey = $this->getSearchKey();
        if (!empty($searchKey)) {
            $extra['OR'] = [
                $this->EducationSubjects->aliasField('name').' LIKE' => '%' . $searchKey . '%',
                $this->EducationGrades->aliasField('name').' LIKE' => '%' . $searchKey . '%',
            ];
        }

        // sortWhiteList
        $sortList = ['EducationGrades.name', 'EducationSubjects.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // by default sorting by EducationSubjectsOrder followed by EducationGradesOrder
        $requestQuery = $this->request->query;
        $sortable = array_key_exists('sort', $requestQuery) ? true : false;

        if (!$sortable) {
            $query
                ->order([
                    'EducationSubjects.order',
                    'EducationGrades.order'
                ]);
        }
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        $id = $entity->id;
        $countMale = $this->SubjectStudents->getMaleCountBySubject($id);
        $countFemale = $this->SubjectStudents->getFemaleCountBySubject($id);
        $this->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        if (isset($extra[$this->aliasField('notice')]) && !empty($extra[$this->aliasField('notice')])) {
            $this->Alert->warning($extra[$this->aliasField('notice')], ['reset'=>true]);
            unset($extra[$this->aliasField('notice')]);
        }
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
                'action' => 'Subjects'
            ]);
        }

        $this->field('total_students', ['visible' => true]);

        $this->setFieldOrder([
            'academic_period_id', 'class_name', 'education_grade_id', 'name', 'education_subject_code', 'education_subject_id',
            'total_male_students', 'total_female_students', 'total_students', 'teachers', 'past_teachers', 'rooms', 'students',
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Classes',
            'Teachers',
            'Rooms',
            'SubjectStudents' => [
                'Users.Genders',
                'StudentStatuses',
                'InstitutionClasses',
                'sort' => ['Users.first_name', 'Users.last_name'] // POCOR-2547 sort list of staff and student by name
            ]
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity->class_name = implode(', ', (new Collection($entity->classes))->extract('name')->toArray());
        $this->fields['students']['data']['students'] = $entity->subject_students;
        $this->fields['past_teachers']['data'] = $this->getPastTeachers($entity);
        return $entity;
    }


    /******************************************************************************************************************
    **
    ** add action methods
    **
    ******************************************************************************************************************/
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        if ($selectedAcademicPeriodId == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
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
        $this->setFieldOrder([
            'academic_period_id', 'class_name', 'subjects',
        ]);

        $Classes = $this->Classes;

        $institutionId = $extra['institution_id'];
        $periodOption = ['' => '-- ' . __('Select Period') .' --'];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['withLevels' => false, 'isEditable' => true]);
        $academicPeriodOptions = $periodOption + $academicPeriodOptions;

        if ($this->request->is(['post', 'put']) && $this->request->data($this->aliasField('academic_period_id'))) {
            $extra['selectedAcademicPeriodId'] = $this->request->data($this->aliasField('academic_period_id'));
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
        $selectedClassId = $this->postString('class_name', $classOptions);
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
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        foreach ($data as $key => $value) { //loop each subject then unset education_subject_id if not selected (so no validation is done).
            if ($key == 'MultiSubjects') {
                foreach ($data[$key] as $key1 => $value1) {
                    if (array_key_exists('education_subject_id', $value1)) {
                        if (!$value1['education_subject_id']) {
                            unset($data[$key][$key1]['education_subject_id']);
                            //unset($data[$key][$key1]['name']);
                        }
                    }
                }
            }
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
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
                    $model->log($error, 'debug');
                    if (is_array($error)) { //this error is to validate "name" field, not for each subject name so not needed.
                        //$model->Alert->error('general.add.failed');
                    } else {
                        /**
                         * unset all field validation except for "institution_id" to trigger validation error in ControllerActionComponent
                         */
                        foreach ($model->fields as $value) {
                            if ($value['field'] != 'institution_id') {
                                $model->validator()->remove($value['field']);
                            }
                        }
                        $extra[$this->aliasField('notice')] = $error;
                        $model->Alert->error($error);
                    }
                    $model->request->data = $data;
                    return false;
                }
            }
        };
        return $process;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (isset($extra[$this->aliasField('notice')]) && !empty($extra[$this->aliasField('notice')])) {
            $notice = $extra[$this->aliasField('notice')];
            unset($extra[$this->aliasField('notice')]);
            if ($notice=='passed') {
                $this->Alert->success('general.add.success', ['reset'=>true]);
                return $this->controller->redirect($this->url('index', 'QUERY'));
            } else {
                $this->Alert->error($notice, ['reset'=>true]);
            }
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
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

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $institutionSubjectId = $entity->id;

        $query = $InstitutionClassSubjects
                    ->find()
                    ->where([
                        'institution_subject_id' => $institutionSubjectId
                    ])
                    ->extract('institution_class_id')
                    ->toArray();

        $options['originalClass'] = $query;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew()) {
            //empty subject student is handled by beforeMarshal
            //in another case, it will be save manually to avoid unecessary queries during save by association
            if ($entity->has('subjectStudent') && !empty($entity->subjectStudent)) {
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
                $institutionSubjectId = $entity->id;
                $institutionClassIds = $options['originalClass'];

                $existingStudents = $this->SubjectStudents
                    ->find('all')
                    ->select([
                        'id', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'institution_id',
                        'student_status_id', 'institution_subject_id', 'education_subject_id'
                    ])
                    ->where([
                        $this->SubjectStudents->aliasField('institution_class_id') . ' IN ' => $institutionClassIds,
                        $this->SubjectStudents->aliasField('education_grade_id') => $educationGradeId,
                        $this->SubjectStudents->aliasField('education_subject_id') => $educationSubjectId,
                        $this->SubjectStudents->aliasField('institution_subject_id') => $institutionSubjectId
                    ])
                    ->toArray();

                foreach ($existingStudents as $key => $subjectStudentEntity) {
                    if (!array_key_exists($subjectStudentEntity->student_id, $newStudents)) { // if current student does not exists in the new list of students
                        $this->SubjectStudents->delete($subjectStudentEntity);
                    } else { // if student exists, then remove from the array to get the new student records to be added
                        unset($newStudents[$subjectStudentEntity->student_id]);
                    }
                }

                foreach ($newStudents as $key => $student) {
                    $subjectStudentEntity = $this->SubjectStudents->newEntity($student);
                    $this->SubjectStudents->save($subjectStudentEntity);
                }
            }
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $Staff = TableRegistry::get('Institution.Staff');
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
            ->find('AcademicPeriod', ['academic_period_id'=> $extra['selectedAcademicPeriodId']])
            ->contain(['Users'])
            ->where([
                $Staff->aliasField('institution_position_id'),
                'OR' => [ //check teacher end date
                    [$Staff->aliasField('end_date').' > ' => new Date()],
                    [$Staff->aliasField('end_date').' IS NULL']
                ]
            ])
            ->toArray();

        $teachers = [0 => '-- ' . __('Select Teacher or Leave Blank') . ' --'];
        foreach ($query as $key => $value) {
            if ($value->has('Users')) {
                $teachers[$value->Users->id] = $value->Users->name;
            }
        }
        $subjects = $this->getSubjectOptions($extra['selectedClassId']);
        $existedSubjects = $this->getExistedSubjects($extra['selectedClassId'], true);
        $this->fields['subjects']['data'] = [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'existedSubjects' => $existedSubjects
        ];
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->ClassSubjects->alias(),
            $this->SubjectStudents->alias(),
            $this->SubjectStaff->alias(),
            $this->Classes->alias()
        ];

        //check textbook
        $InstitutionTextbooks = TableRegistry::get('Textbook.InstitutionTextbooks');
        $associatedTextbooksCount = $InstitutionTextbooks->find()
            ->where([
                $InstitutionTextbooks->aliasField('education_subject_id') => $entity->education_subject_id,
                $InstitutionTextbooks->aliasField('academic_period_id') => $entity->academic_period_id
            ])
            ->count();
        $extra['associatedRecords'][] = ['model' => 'Institution Textbooks', 'count' => $associatedTextbooksCount];
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
        if (count($subjectOptions) == count($existedSubjects)) {
            $error = $this->aliasField('allSubjectsAlreadyAdded');
        } elseif (isset($data['MultiSubjects']) && count($data['MultiSubjects'])>0) {
            foreach ($data['MultiSubjects'] as $key => $row) {
                if (isset($row['education_subject_id']) && isset($row['subject_staff'])) {
                    $subjectSelected = true;
                    $subjects[$key] = [
                        'key' => $key,
                        'name' => $row['name'],
                        'education_grade_id' => $row['education_grade_id'],
                        'education_subject_id' => $row['education_subject_id'],
                        'academic_period_id' => $commonData['academic_period_id'],
                        'institution_id' => $commonData['institution_id'],
                        'class_subjects' => [
                            [
                                'status' => 1,
                                'institution_class_id' => $commonData['class_name']
                            ]
                        ]
                    ];
                    if ($row['subject_staff'][0]['staff_id']!=0) {
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
                    if ($subject->errors()) {
                        $error = $subject->errors();
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

        if (strtolower($persona)=='students') {
            // find the latest student status id of student in the class
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
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
                $this->Alert->warning($this->alias().".studentRemovedFromInstitution");
            } else {
                $data['student_status_id'] = $userData->student_status_id;
                $data['education_grade_id'] = $userData->education_grade_id;
                $data['user'] = [];
                $data['student_status'] = []; // student status entity (to retrieve student status name)
            }
        } else {
            $userData = $this->Institutions->Staff->find()->contain(['Users'=>['Genders']])->where(['staff_id'=>$id])->first();
            if (empty($userData)) {
                $this->Alert->warning($this->alias().".staffRemovedFromInstitution");
            } else {
                $data['user'] = [];
            }
        }
        if (array_key_exists('user', $data)) {
            $model = 'Subject'.ucwords(strtolower($persona));
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
        $relationKey = 'subject_'.strtolower($persona);
        foreach ($entity->{$relationKey} as $data) {
            if (strtolower($persona)=='students') {
                if (is_object($data)) {
                    if ($data->student_id == $id) {
                        $recordId = $data->id;
                    }
                } elseif (array_key_exists('student_id', $data)) {
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
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
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
            $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
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
                                    ['end_date' . ' >= ' => Date::now()]
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
        $Staff = TableRegistry::get('Institution.Staff');
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
            ->find('AcademicPeriod', ['academic_period_id'=>$entity->academic_period_id])
            ->contain(['Users'])
            ->where([
                $Staff->aliasField('institution_position_id'),
                'OR' => [ //check teacher end date
                    [$Staff->aliasField('end_date').' > ' => new Date()],
                    [$Staff->aliasField('end_date').' IS NULL']
                ]
            ])
            ;
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
        $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
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

        $Students = TableRegistry::get('Institution.InstitutionClassStudents');
        if (!empty($grades)) {
            $conditions[$Students->aliasField('education_grade_id').' IN'] = $grades;
        }

        $conditions[$Students->aliasField('institution_class_id').' IN'] = $classKeys;
        /**
         * Attempt to improve performance by filtering out includedStudents in $studentOptions through SQL query
         */
        if (!empty($includedStudents)) {
            $conditions[$Students->aliasField('student_id').' NOT IN'] = $includedStudents;
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
                    $this->log('Data corrupted with no institution student: '. $student->id . ' @ '. $this->registryAlias() .': '. __LINE__, 'debug');
                } else {
                    $studentOptions[$user->id] = $user->name_with_id;
                }
            } else {
                $this->log('Data corrupted with no security user for student: '. $student->id, 'debug');
            }
        }
        return $studentOptions;
    }

    public function autoInsertSubjectsByClass(Entity $entity)
    {
        $errors = $entity->errors();
        if (empty($errors)) {
            /**
             * get the list of education_grade_id from the education_grades array
             */
            $grades = (new Collection($entity->education_grades))->extract('id')->toArray();
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
                        if (!isset($educationSubjects[$gradeSubject->id.'_'.$subject->id])) {
                            $educationSubjects[$gradeSubject->id.'_'.$subject->id] = [
                                'id' => $subject->id,
                                'education_grade_id' => $gradeSubject->id,
                                'name' => $subject->name
                            ];
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
                        $InstitutionSubjects->aliasField('academic_period_id') => $entity->academic_period_id,
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
                                $InstitutionClassSubjects->aliasField('institution_class_id') => $entity->id,
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
                            'academic_period_id' => $entity->academic_period_id,
                            'class_subjects' => [
                                [
                                    'status' => 1,
                                    'institution_class_id' => $entity->id
                                ]
                            ]
                        ];
                    }
                }

                if (!empty($newSchoolSubjects)) {
                    $newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
                    foreach ($newSchoolSubjects as $subject) {
                        $InstitutionSubjects->save($subject);
                    }
                    unset($subject);
                }
                unset($newSchoolSubjects);
                unset($InstitutionSubjects);
                unset($InstitutionClassSubjects);
            }
        }
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        return $entity->education_grade->name;
    }

    public function onGetTeachers(Event $event, Entity $entity)
    {
        if ($entity->has('teachers')) {
            $resultArray = [];
            $todayDate = new Date();

            foreach ($entity->teachers as $key => $value) {
                $staffEndDate = $value->_joinData->end_date;

                if ($staffEndDate >= $todayDate || $staffEndDate == null || empty($staffEndDate)) {
                    switch ($this->action) {
                        case 'view':
                            $resultArray[] = $event->subject()->Html->link($value->name_with_id, [
                                'plugin' => 'Institution',
                                'controller' => 'Institutions',
                                'action' => 'StaffUser',
                                'view',
                                $this->paramsEncode(['id' => $value->id])
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

    public function onGetRooms(Event $event, Entity $entity)
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

    public function onGetTotalStudents(Event $event, Entity $entity)
    {
        return $entity->total_male_students + $entity->total_female_students;
    }

    //called by ControllerActionHelper incase extra search highlighted
    // public function getSearchableFields(Event $event, $fields, ArrayObject $searchableFields) {
    //  $searchableFields[] = "education_subject_id";
    // }

    public function getPastTeachers($entity)
    {
        $todayDate = new Date();
        $data = [];
        if ($entity->has('teachers')) {
            foreach ($entity->teachers as $key => $value) {
                if ($value->has('_joinData')) {
                    if (!empty($value->_joinData->end_date)) {
                        $endDate = $value->_joinData->end_date;
                        if ($endDate < $todayDate) { //for end of assignment teachers
                            $data[$key]['id'] = $value->id;
                            $data[$key]['name'] = $value->name_with_id;
                            $data[$key]['start_date'] = $value->_joinData->start_date->format('d-m-Y');
                            ;
                            $data[$key]['end_date'] = $value->_joinData->end_date->format('d-m-Y');
                            ;
                        }
                    }
                }
            }
        }
 
        return $data;
    }
}
