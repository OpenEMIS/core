<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Utility\Security;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;
use Cake\Utility\Text;

class InstitutionTextbooksTable extends ControllerActionTable
{
    use HtmlTrait;

    private $studentOptions = [];
    private $availableStudent = [];

    // NOTE : studentoption used to retrive enrolled students only, however later for pocor-7362 assigned staff are also required and hence a method is written to get assigned staff and merged with $studentOptions. 

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Textbooks', ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('TextbookStatuses', ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions', ['className' => 'Textbook.TextbookConditions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->EducationLevels          = TableRegistry::get('Education.EducationLevels');
        $this->EducationProgrammes      = TableRegistry::get('Education.EducationProgrammes');
        $this->EducationGrades          = TableRegistry::get('Education.EducationGrades');
        $this->EducationGradeSubjects   = TableRegistry::get('Education.EducationGradeSubjects');

        $this->InstitutionSubjectStudents   = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $this->InstitutionGrades            = TableRegistry::get('Institution.InstitutionGrades');
        $this->InstitutionClasses           = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionSubjects          = TableRegistry::get('Institution.InstitutionSubjects');

        $this->setDeleteStrategy('restrict');

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportInstitutionTextbooks']);
        $this->addBehavior('InstitutionTextbookExcel', ['excludes' => ['security_group_id'], 'pages' => ['index']]); // POCOR-3627
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('code')
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['academic_period_id', 'institution_id']]],
                'provider' => 'table'
            ]);
    }

    public function implementedEvents() {
       $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields) {
        $searchableFields[] = 'textbook_id';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $this->institutionId = $session->read('Institution.Institutions.id');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;
        $searchKey = $this->getSearchKey();

        if (!strlen($searchKey)) { //during search, then hide the control filter
            //academic period filter
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noTextbooks')),
                'callable' => function($id) {
                    return $this
                            ->find()
                            ->where([
                                $this->aliasField('institution_id') => $this->institutionId,
                                $this->aliasField('academic_period_id') => $id
                            ])
                            ->count();
                }
            ]);
            $extra['selectedPeriod'] = $selectedPeriod;
            $data['periodOptions'] = $periodOptions;
            $data['selectedPeriod'] = $selectedPeriod;

            //education grade filter
            if ($selectedPeriod) {

                $gradeOptions = $this->InstitutionGrades->getGradeOptions($this->institutionId, $selectedPeriod);

                if ($gradeOptions) {
                    $gradeOptions = array(-1 => __('-- Select Education Grade --')) + $gradeOptions;
                }

                if ($request->query('grade')) {
                    $selectedGrade = $request->query('grade');
                } else {
                    $selectedGrade = -1;
                }

                $this->advancedSelectOptions($gradeOptions, $selectedEducationGradeId, [
                    'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
                    'callable' => function($id) use ($selectedPeriod) {

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

                        $query = $this->InstitutionClasses
                                ->find()
                                ->join([$join])
                                ->where([
                                    $this->InstitutionClasses->aliasField('institution_id') => $this->institutionId,
                                    $this->InstitutionClasses->aliasField('academic_period_id') => $selectedPeriod,
                                ]);
                        return $query->count();
                    }
                ]);
                $extra['selectedGrade'] = $selectedGrade;
                $data['gradeOptions'] = $gradeOptions;
                $data['selectedGrade'] = $selectedGrade;
            }

            //education subjects filter
            if ($selectedPeriod && $selectedGrade) {
                $subjectOptions = $this->EducationSubjects->getEducationSubjectsByGrades($selectedGrade);

                $subjectOptions = array(-1 => __('-- Select Education Subject --')) + $subjectOptions;

                if ($request->query('subject')) {
                    $selectedSubject = $request->query('subject');
                } else {
                    $selectedSubject = -1;
                }

                $this->advancedSelectOptions($subjectOptions, $selectedSubject, [
                    'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noTextbooks')),
                    'callable' => function($id) use ($selectedPeriod) {
                        $conditions[] = $this->aliasField('academic_period_id = ') . $selectedPeriod;

                        if ($id > 0) {
                            $conditions[] = $this->aliasField('education_subject_id = ') . $id;
                        }

                        return $this->find()
                                    ->where([
                                        $conditions
                                    ])
                                    ->count();
                    }
                ]);
                $extra['selectedSubject'] = $selectedSubject;
                $data['subjectOptions'] = $subjectOptions;
                $data['selectedSubject'] = $selectedSubject;
            }

            //textbook filter
            // if ($selectedPeriod && $selectedGrade && $selectedSubject) {

                $textbookOptions = $this->Textbooks->getTextbookOptions($selectedPeriod, $selectedGrade, $selectedSubject);

                // if ($textbookOptions) {
                    $textbookOptions = array(-1 => __('-- Select Textbooks --')) + $textbookOptions;
                // }

                if ($request->query('textbook')) {
                    $selectedTextbook = $request->query('textbook');
                } else {
                    $selectedTextbook = -1;
                }

                $this->advancedSelectOptions($textbookOptions, $selectedTextbook, [
                    'message' => '{{label}} - ' . $this->getMessage('general.noRecords'),
                    'callable' => function($id) use ($selectedPeriod, $selectedSubject) {
                        $conditions[] = $this->aliasField('academic_period_id = ') . $selectedPeriod;
                        $conditions[] = $this->aliasField('education_subject_id = ') . $selectedSubject;

                        if ($id > 0) {
                            $conditions[] = $this->aliasField('textbook_id = ') . $id;

                            return $this->find()
                                    ->where([
                                        $conditions
                                    ])
                                    ->count();
                        } else {
                            return 1;
                        }


                    }
                ]);
                $extra['selectedTextbook'] = $selectedTextbook;
                $data['textbookOptions'] = $textbookOptions;
                $data['selectedTextbook'] = $selectedTextbook;
            // }

            //build up the control filter
            $extra['elements']['control'] = [
                'name' => 'Institution.Textbooks/controls',
                'data' => $data,
                'order' => 3
            ];
        }

        $this->field('academic_period_id', ['type' => 'string']);
        $this->field('comment', ['visible' => false]);
        $this->field('education_subject_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('student_status', ['visible' => false]);
        $this->field('student_status');
        $this->field('openemis_no');

        $this->setFieldOrder([
            'academic_period_id', 'code', 'textbook_id', 'textbook_condition_id', 'textbook_status_id', 'openemis_no', 'security_user_id'
        ]);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Textbooks','Academic');       
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $searchKey = $this->getSearchKey();

        if (strlen($searchKey)) {
            $query->matching('Textbooks'); //to enable search by textbook title
            $extra['OR'] = [
                $this->Textbooks->aliasField('title').' LIKE' => '%' . $searchKey . '%',
                $this->Textbooks->aliasField('code').' LIKE' => '%' . $searchKey . '%',
            ];
        } else { //if no search key specified, then search is by filter.
            //filter
            if (array_key_exists('selectedPeriod', $extra)) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
                }
            }

            if (array_key_exists('selectedSubject', $extra)) {
                if ($extra['selectedSubject']) {
                    $conditions[] = $this->aliasField('education_subject_id = ') . $extra['selectedSubject'];
                }
            }

            if (array_key_exists('selectedTextbook', $extra)) {
                if ($extra['selectedTextbook'] > 0) {
                    $conditions[] = $this->aliasField('textbook_id = ') . $extra['selectedTextbook'];
                }
            }

            $conditions[] = $this->aliasField('institution_id = ') . $this->institutionId;

            $query->where([$conditions]);
        }
    }

    public function onGetOpenEmisNo(Event $event, Entity $entity)
    {
        if (($this->action == 'index')) {
            return $entity->user->openemis_no;
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Users',
            'Textbooks',
            'EducationSubjects.EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);

        $this->field('textbooks_students', ['visible' => false]);

        if ($entity->security_user_id) {
            $studentClassGrade = $this->InstitutionSubjectStudents->getStudentClassGradeDetails($entity->academic_period_id, $this->institutionId, $entity->security_user_id, $entity->education_subject_id);
            $entity->education_grade_id = $studentClassGrade[0]->education_grade_id;
            $entity->institution_class_id = $studentClassGrade[0]->institution_class_id;
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $textbookCode = '';
        if ($data[$this->alias()]['textbook_id'] && $data[$this->alias()]['academic_period_id']) {
            $textbookCode = $this
                ->Textbooks
                ->get([
                    'textbook_id' => $data[$this->alias()]['textbook_id'],
                    'academic_period_id' => $data[$this->alias()]['academic_period_id']
                ])->code;
        }

        $process = function ($model, $entity) use ($data, $textbookCode) {
            $newEntities = [];

            if (array_key_exists('textbooks_students', $data[$this->alias()])) {

                $textbooks = $data[$this->alias()]['textbooks_students'];

                if (count($textbooks)) {

                    foreach ($textbooks as $key => $textbook) {

                        $obj['code'] = $textbook['code'];
                        $obj['comment'] = $textbook['comment'];
                        $obj['textbook_status_id'] = $textbook['textbook_status_id'];
                        $obj['textbook_condition_id'] = $textbook['textbook_condition_id'];

                        $obj['security_user_id'] = $textbook['security_user_id'];

                        $obj['institution_id'] = $entity->institution_id;
                        $obj['academic_period_id'] = $entity->academic_period_id;
                        $obj['education_grade_id'] = $entity->education_grade_id;
                        $obj['education_subject_id'] = $entity->education_subject_id;
                        $obj['textbook_id'] = $entity->textbook_id;
                        $obj['counterNo'] = $key;

                        $newEntities[] = $obj;
                    }

                    $success = $this->connection()->transactional(function() use ($newEntities, $entity, $textbookCode) {
                        $return = true;
                        foreach ($newEntities as $key => $newEntity) {

                            $textbookStudentEntity = $this->newEntity($newEntity);

                            if ($textbookStudentEntity->errors('code')) {
                                $counterNo = $newEntity['counterNo'];
                                $entity->errors("textbooks_students.$counterNo", ['code' => $textbookStudentEntity->errors('code')]);
                            }
                            if (!$this->save($textbookStudentEntity)) {
                                $return = false;
                            } else {
                                //this is to autofill book code when it was left empty.
                                //code is using textbook code - autonumber ID generated by database
                                if ($newEntity['code']) {
                                    $bookCode = $newEntity['code'];
                                } else {
                                    $bookCode = $textbookCode . '-' . $textbookStudentEntity->id;
                                }
                                $this->updateAll(
                                    ['code' => $bookCode],
                                    ['id' => $textbookStudentEntity->id]
                                );
                            }
                        }
                        return $return;
                    });
                    // die;
                    return $success;
                }
            } else { //if no textbook student added and user try to save
                $entity->errors('textbooks_students', __('There are no textbook added'));
                $this->Alert->error('Textbooks.noTextbookStudent', ['reset'=>true]);
            }
        };
        return $process;
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->security_user_id) { //retrieve student and staff POCOR-7362 

            $studentOptions = $this->InstitutionSubjectStudents->getEnrolledStudent($entity->academic_period_id, $entity->education_subject_id, $entity->education_grade_id); 
            $staffOptions = $this->getAssignedStaffForInstitution($this->institutionId, $entity->education_subject_id, $entity->education_grade_id);
            $studentOptions = $studentOptions + $staffOptions;
            $entity->institution_class_id = $studentOptions;
            // pr($entity);
        } 
        else { //if no user assigned to the book, then use the textbook details
            $entity->education_grade_id = $entity->textbook->education_grade_id;
            $entity->institution_class_id = '';
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->action == 'add') {
            $this->field('allocated_to', ['entity' => $entity]);
            $this->field('available_student', ['entity' => $entity]);
            $this->setupFields($entity);
            $this->field('code', ['visible' => false]);
            $this->field('comment', ['visible' => false]);
            $this->field('textbook_status_id', ['visible' => false]);
            $this->field('textbook_condition_id', ['visible' => false]);
            $this->field('security_user_id', ['visible' => false]);
            $this->setFieldOrder(['academic_period_id', 'education_level_id', 'education_programme_id', 'education_grade_id', 'education_subject_id', 'textbook_id', 'institution_class_id', 'allocated_to']);
        } else {
            $this->setupFields($entity);
            $this->field('textbooks_students', ['visible' => false]);
        }
        $this->field('student_status', ['visible' => false]);
    }

    public function onUpdateFieldAllocatedTo(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        $studentOptions = [];
        if ($request->data($this->aliasField('textbook_id'))) {
            $textbookStudents = $this->find('list', [
                    'keyField' => 'security_user_id',
                    'valueField' => 'security_user_id'
                ])
                ->where([$this->aliasField('textbook_id') => $request->data($this->aliasField('textbook_id'))])
                ->select([
                    $this->aliasField('security_user_id')
                ])
                ->distinct(['security_user_id'])
                ->hydrate(false)
                ->toArray();
            $textbookId = $entity->textbook_id;
            $studentOptions = $this->InstitutionSubjectStudents->getEnrolledStudent($entity->academic_period_id, $entity->education_subject_id, $entity->education_grade_id);

            $staffOptions = $this->getAssignedStaffForInstitution($this->institutionId, $entity->education_subject_id, $entity->education_grade_id); //POCOR-7362
            $studentOptions = $studentOptions + $staffOptions; //POCOR-7362
            
            $this->studentOptions = $studentOptions;
            $studentOptions = array_diff_key($studentOptions, $textbookStudents);
            $textbooksStudents = is_array($request->data($this->aliasField('textbooks_students'))) ? array_column($request->data($this->aliasField('textbooks_students')), 'security_user_id') : [];
            $studentOptions = array_diff_key($studentOptions, array_flip($textbooksStudents));
            $this->availableStudent = $studentOptions; //to pass remaining students
        }
        if (!empty($studentOptions)) {
            $studentOptions = [null => $this->getMessage('Users.select_users'), 'all' => $this->getMessage('Users.add_all_users')] + $studentOptions;
        } else {
            $studentOptions = [null => $this->getMessage('general.select.noOptions')];
        }
        
        $attr['options'] = $studentOptions;
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = false;
        return $attr;
    }

    // POCOR-7362 starts

    public function getAssignedStaffForInstitution($institutionId, $educationSubjectId, $educationGradeId){

        // echo $educationGradesId;
        // exit;
        $staff = TableRegistry::get('institution_staff');
        $query = $staff->find()
                ->select([
                   'su.openemis_no',
                    'su.first_name',
                    'su.middle_name',
                    'su.third_name',
                    'su.last_name',
                    'su.id'
                ])
                ->join([
                    'table' => 'security_users',
                    'alias' => 'su',
                    'type' => 'INNER',
                    'conditions' => 'institution_staff.staff_id = su.id'
                ])
                ->join([
                    'table' => 'staff_statuses',
                    'alias' => 'ss',
                    'type' => 'INNER',
                    'conditions' => 'institution_staff.staff_status_id = ss.id'
                ])
                ->join([
                    'table' => 'institution_subject_staff',
                    'alias' => 'iss',
                    'type' => 'INNER',
                    'conditions' => 'institution_staff.staff_id = iss.staff_id'
                ])
                ->join([
                    'table' => 'institution_subjects',
                    // 'alias' => 'iss',
                    'type' => 'INNER',
                    'conditions' => 'iss.institution_subject_id = institution_subjects.id'
                ])
                ->where([
                    'institution_staff.institution_id' => $institutionId,
                    'ss.id' => 1,
                    'institution_subjects.education_subject_id' => $educationSubjectId,
                    'institution_subjects.education_grade_id' => $educationGradeId
                ])
                ->hydrate(false);

        $result = $query->toArray();

        // echo "<pre>";
        // print_r($result);
        // exit;

        $staffList =[];

        foreach ($result as $key => $value) {
            $user = $value['su'];
            $staffList[$user['id']] = $user['openemis_no'] .  " - " .  $user['first_name'] ." ". $user['middle_name']." ". $user['third_name']." ". $user['last_name'];
        }

        return $staffList;
        }

    // POCOR-7362 ends

    public function onUpdateFieldAvailableStudent(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'hidden';
        $attr['attr']['value'] = implode(',', array_keys($this->availableStudent));
        return $attr;
    }
    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $entity->name = $entity->code;

        //manually add condition during restrict delete
        $PreviousInstitutionTextbooks = $this
                                        ->find()
                                        ->where([
                                            $this->aliasField('id') => $entity->id,
                                            $this->aliasField('academic_period_id <> ') => $entity->academic_period_id
                                        ])
                                        ->count();

        $extra['associatedRecords'][] = ['model' => 'InstitutionTextbooks', 'count' => $PreviousInstitutionTextbooks];
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        if (($this->action == 'view') || ($this->action == 'index')) {
            return $entity->academic_period->name;
        }
    }

    public function onGetEducationLevelId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_subject->education_grades[0]->education_programme->education_cycle->education_level->system_level_name;
        }
    }

    public function onGetEducationProgrammeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_subject->education_grades[0]->education_programme->cycle_programme_name;
        }
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_subject->education_grades[0]->name;
        }
    }

    public function onGetInstitutionClassId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            // pr($entity);
            if ($entity->institution_class_id) {
                return $this->InstitutionClasses->get($entity->institution_class_id)->name;
            }
        }
    }

    public function onGetEducationSubjectId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_subject->code_name;
        }
    }

    public function onGetTextbookId(Event $event, Entity $entity)
    {
        return $entity->textbook->code_title;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            if ($action == 'add') {
                $attr['default'] = $selectedPeriod;
                $attr['options'] = $periodOptions;
                $attr['onChangeReload'] = 'changeAcademicPeriod';
            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $periodOptions[$attr['entity']->academic_period_id];
                $attr['value'] = $attr['entity']->academic_period_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['programme'] = -1;
        $request->query['grade'] = '-1';
        $request->query['class'] = '-1';
        $request->query['subject'] = '-1';
        $request->query['textbook'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
                if (isset($data[$this->alias()]['textbooks_students'])) {
                    unset($data[$this->alias()]['textbooks_students']);
                }
                if (isset($data[$this->alias()]['education_level_id'])) {
                    unset($data[$this->alias()]['education_level_id']);
                }
                if (isset($data[$this->alias()]['education_programme_id'])) {
                    unset($data[$this->alias()]['education_programme_id']);
                }
                if (isset($data[$this->alias()]['education_grade_id'])) {
                    unset($data[$this->alias()]['education_grade_id']);
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
				$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
				$academicPeriodId = !is_null($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

				$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
				$EducationGrades = TableRegistry::get('Education.EducationGrades');
				$EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

				$list = $InstitutionGrades
					->find()
					->select(['level_id' => 'EducationGrades.id', 'level_name' => 'EducationGrades.name'])
					->matching('EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems')
					->where([
						'EducationSystems.academic_period_id' => $academicPeriodId,
						$InstitutionGrades->aliasField('institution_id') => $this->institutionId
					])
					->toArray();

				$returnList = [];
				foreach ($list as $key => $value) {
					$returnList[$value->level_id] =  $value->level_name;
				}
				
				$gradeOptions = $returnList;
		
                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
                $attr['value'] = $attr['entity']->education_grade_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['class'] = '-1';
        $request->query['subject'] = '-1';
        $request->query['textbook'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                }

                if (isset($data[$this->alias()]['textbooks_students'])) {
                    unset($data[$this->alias()]['textbooks_students']);
                }
            }
        }
    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
                $gradeId = $request->data($this->aliasField('education_grade_id'));
                $subjectOptions = [];
                if ($gradeId) {
                    $subjectOptions = $this->EducationSubjects->getEducationSubjectsByGrades($gradeId);
                }

                $attr['options'] = $subjectOptions;
                $attr['onChangeReload'] = 'changeEducationSubject';

            } else if ($action == 'edit') {

                $educationSubject = $this->EducationSubjects->get($attr['entity']->education_subject_id);
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $educationSubject->code . "-" . $educationSubject->name;
                $attr['value'] = $attr['entity']->education_subject_id;

            }
        }
        return $attr;
    }

    public function addEditOnChangeEducationSubject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                }
                if (array_key_exists('institution_class_id', $request->data[$this->alias()])) {
                    $request->query['class'] = $request->data[$this->alias()]['institution_class_id'];
                }
                if (array_key_exists('education_subject_id', $request->data[$this->alias()])) {
                    $request->query['subject'] = $request->data[$this->alias()]['education_subject_id'];
                }

                if (isset($data[$this->alias()]['textbooks_students'])) {
                    unset($data[$this->alias()]['textbooks_students']);
                }
            }
        }
    }

    public function onUpdateFieldTextbookId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($request->data($this->aliasField('academic_period_id'))));

                $selectedGrade = $request->data($this->aliasField('education_grade_id'));
                $selectedSubject = $request->data($this->aliasField('education_subject_id'));

                $textbookOptions = [];
                if ($selectedPeriod && $selectedGrade && $selectedSubject) {
                    $textbookOptions = $this->Textbooks->getTextbookOptions($selectedPeriod, $selectedGrade, $selectedSubject);
                }
                $attr['options'] = $textbookOptions;
                $attr['onChangeReload'] = 'changeTextbook';
            } else if ($action == 'edit') {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->Textbooks
                        ->get([
                            'textbook_id' => $attr['entity']->textbook_id,
                            'academic_period_id' => $attr['entity']->academic_period_id,
                        ])->title;
                $attr['value'] = $attr['entity']->textbook_id;

            }
        }
        return $attr;
    }

    public function addEditOnChangeTextbook(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                }
                if (array_key_exists('institution_class_id', $request->data[$this->alias()])) {
                    $request->query['class'] = $request->data[$this->alias()]['institution_class_id'];
                }
                if (array_key_exists('education_subject_id', $request->data[$this->alias()])) {
                    $request->query['subject'] = $request->data[$this->alias()]['education_subject_id'];
                }

                if (array_key_exists('textbook_id', $request->data[$this->alias()])) {
                    $request->query['textbook'] = $request->data[$this->alias()]['textbook_id'];
                }
                if (isset($data[$this->alias()]['textbooks_students'])) {
                    unset($data[$this->alias()]['textbooks_students']);
                }
            }
        }
    }

    public function onGetCustomTextbooksStudentsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        // $tableHeaders = [$this->getMessage($this->aliasField('trainer_type')), $this->getMessage($this->aliasField('trainer'))];

        $header[] = [
            'title' => 'Textbook ID',
            'desc' => 'Textbook ID is unique to each book within the school, Leave empty for autogenerated code.'
        ];
        $header[] = [
            'title' => 'Status',
            'desc' => 'Available means physically book exists while Not Available means it is missing.'
        ];
        $header[] = [
            'title' => 'Condition',
            'desc' => 'Condition of each available book.'
        ];
        $header[] = [
            'title' => 'Comment',
            'desc' => 'Comments about each book regardless of availability.'
        ];
        $header[] = [
            'title' => 'Allocated To',
            'desc' => 'Each book can be optionally allocated to an individual student.'
        ];

        $header[] = [];

        foreach ($header as $key => $value) {
            if (isset($value['title'])) {
                $tableHeaders[] =
                    __($value['title']) . "
                    <div class='tooltip-desc' style='display: inline-block;'>
                        <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' tooltip-placement='top' uib-tooltip='" .  __($value['desc']) . "' tooltip-append-to-body='true' tooltip-class='tooltip-blue'></i>
                    </div>";

            } else {
                $tableHeaders[] = '';
            }
        }

        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'textbooks_students';

        //generate textbook condition and status
        $textbookConditionOptions = $this->TextbookConditions->getTextbookConditionOptions();
        $textbookStatusOptions = $this->TextbookStatuses->getSelectOptions();

        if (!count($textbookConditionOptions) || !count($textbookStatusOptions)) { //if no condition / status created.
            $this->Alert->error('Textbooks.noTextbookStatusCondition');
        } else {

            //generate `list`
            $studentOptions = $this->studentOptions;

            if ($action == 'add' || $action == 'edit') {
                $tableHeaders[] = ''; // for delete column
                $Form = $event->subject()->Form;
                $Form->unlockField('InstitutionTextbooks.textbooks_students');

                // refer to addEditOnAddTextbooksStudents for http post
                if ($this->request->data("$alias.$fieldKey")) {
                    $associated = $this->request->data("$alias.$fieldKey");

                    foreach ($associated as $key => $obj) {
                        $code = $obj['code'];
                        $textbook_status_id = $obj['textbook_status_id'];
                        $textbook_condition_id = $obj['textbook_condition_id'];
                        $comment = $obj['comment'];
                        $security_user_id = $obj['security_user_id'];

                        $rowData = [];

                        //to insert error message if validation kicked in.
                        $tempRowData = $Form->input("$alias.$fieldKey.$key.code", ['label' => false]);

                        if ($entity->errors("textbooks_students.$key") && isset($entity->errors("textbooks_students.$key")['code'])) {

                            $tempRowData .= "<ul class='error-message'>";
                            foreach ($entity->errors("textbooks_students.$key")['code'] as $error) {
                                $tempRowData .= __($error);
                            }
                            $tempRowData .= "</ul>";

                        }

                        $rowData[] = $tempRowData;
                        $rowData[] = $Form->input("$alias.$fieldKey.$key.textbook_status_id", ['type' => 'select', 'label' => false, 'options' => $textbookStatusOptions]);
                        $rowData[] = $Form->input("$alias.$fieldKey.$key.textbook_condition_id", ['type' => 'select', 'label' => false, 'options' => $textbookConditionOptions]);
                        $rowData[] = $Form->input("$alias.$fieldKey.$key.comment", ['type' => 'text', 'label' => false]);
                        $rowData[] = isset($studentOptions[$this->request->data("$alias.$fieldKey.$key.security_user_id")]) ? $studentOptions[$this->request->data("$alias.$fieldKey.$key.security_user_id")] : __('No Allocation');
                        $rowData[] = $Form->hidden("$alias.$fieldKey.$key.security_user_id");
                        $rowData[] = $this->getDeleteButton(['onclick' => 'jsTable.doRemove(this); $(\'#reload\').click();']);
                        $tableCells[] = $rowData;
                    }
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;

            return $event->subject()->renderElement('Institution.Textbooks/'.$fieldKey, ['attr' => $attr]);
        }
    }

    public function addEditOnAddTextbooksStudents(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'textbooks_students';
        
        if ($data['submit'] == 'addTextbooksStudents') { //during the add books, need to ensure that class and subject has value.

            if ($data[$alias]['education_subject_id'] && $data[$alias]['textbook_id']) {

                if ($data[$this->alias()]['allocated_to'] == 'all') { //for all student
                    $studentOptions = explode(',', $data[$alias]['available_student']);
                    foreach ($studentOptions as $key => $value) {
                        $data[$alias][$fieldKey][] = [
                            'code' => '',
                            'textbook_status_id' => '',
                            'textbook_condition_id' => '',
                            'comment' => '',
                            'security_user_id' => $value
                        ];
                    }
                } else {
                    $data[$alias][$fieldKey][] = [
                        'code' => '',
                        'textbook_status_id' => '',
                        'textbook_condition_id' => '',
                        'comment' => '',
                        'security_user_id' => !empty($data[$this->alias()]['allocated_to']) ? $data[$this->alias()]['allocated_to'] : ''
                    ];
                }
            } else {
                $this->Alert->error('Textbooks.noClassSubjectSelected');
            }
        }
    }

    public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {

            $selectedPeriod= $attr['entity']->academic_period_id;
            $selectedSubject = $attr['entity']->education_subject_id;
            $selectedGrade = $attr['entity']->education_grade_id;
            // $selectedGrade = $request->data($this->aliasField('education_grade_id'));

            $selectedClass = $attr['entity']->institution_class_id;

            $textbookStudents = $this->find('list', [
                    'keyField' => 'security_user_id',
                    'valueField' => 'security_user_id'
                ])
                ->where([
                    $this->aliasField('textbook_id') => $attr['entity']->textbook_id,
                ])
                ->select([
                    $this->aliasField('security_user_id')
                ])
                ->distinct(['security_user_id']);

            if ($attr['entity']->security_user_id) {
                $textbookStudents = $textbookStudents->where([$this->aliasField('security_user_id').' <> ' => $attr['entity']->security_user_id]);
            }

            $studentOptions = [];
            if ($selectedPeriod && $selectedClass && $selectedSubject) {
                $studentOptions = $this->InstitutionSubjectStudents->getEnrolledStudent($selectedPeriod, $selectedSubject, $selectedGrade);
                $staffOptions = $this->getAssignedStaffForInstitution($this->institutionId, $selectedSubject, $selectedGrade); //POCOR-7362
                $studentOptions = $studentOptions + $staffOptions; //POCOR-7362
                $studentOptions = array_diff_key($studentOptions, $textbookStudents->toArray());
            }
            $attr['options'] = $studentOptions;

        }
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('education_subject_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('textbook_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('textbooks_students', [
            'type' => 'custom_textbooks_students',
            'valueClass' => 'table-full-width'
        ]);

        $this->field('code', [
            'entity' => $entity
        ]);

        $this->field('textbook_status_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('textbook_condition_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('comment', [
            'entity' => $entity
        ]);

        $this->field('security_user_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'multiple' => false
            ],
            'select' => true,
            'entity' => $entity
        ]);

        // $this->field('student_status');
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'code':
                return __('Textbook ID');
            case 'textbook_condition_id':
                return __('Condition');
            case 'textbook_status_id':
                return __('Status');
            case 'security_user_id':
                return __('Name');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
