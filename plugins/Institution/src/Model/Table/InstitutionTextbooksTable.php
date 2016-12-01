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

    public function initialize(array $config)
    {
        parent::initialize($config);
        
        $this->belongsTo('Textbooks', ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('TextbookStatuses', ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions', ['className' => 'Textbook.TextbookConditions']);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->EducationLevels          = TableRegistry::get('Education.EducationLevels');
        $this->EducationProgrammes      = TableRegistry::get('Education.EducationProgrammes');
        $this->EducationGrades          = TableRegistry::get('Education.EducationGrades');
        $this->EducationGradeSubjects   = TableRegistry::get('Education.EducationGradeSubjects');

        $this->InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $this->InstitutionGrades            = TableRegistry::get('Institution.InstitutionGrades');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');

        $this->setDeleteStrategy('restrict');
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

    public function beforeAction(Event $event, ArrayObject $extra) 
    {
        $session = $this->request->session();
        $this->institutionId = $session->read('Institution.Institutions.id');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

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
                $gradeOptions = array(-1 => __('All Education Grade')) + $gradeOptions;
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

            if ($subjectOptions) {
                $subjectOptions = array(-1 => __('All Education Subject')) + $subjectOptions;
            }

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
        if ($selectedPeriod && $selectedGrade && $selectedSubject) {

            $textbookOptions = $this->Textbooks->getTextbookOptions($selectedPeriod, $selectedGrade, $selectedSubject);

            if ($textbookOptions) {
                $textbookOptions = array(-1 => __('All Textbooks')) + $textbookOptions;
            }

            if ($request->query('textbook')) {
                $selectedTextbook = $request->query('textbook');
            } else {
                $selectedTextbook = -1;
            }

            $this->advancedSelectOptions($textbookOptions, $selectedTextbook, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noRecord')),
                'callable' => function($id) use ($selectedPeriod, $selectedSubject) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $selectedPeriod;
                    $conditions[] = $this->aliasField('education_subject_id = ') . $selectedSubject;

                    if ($id > 0) {
                        $conditions[] = $this->aliasField('textbook_id = ') . $id;
                    }

                    return $this->find()
                                ->where([
                                    $conditions
                                ])
                                ->count();
                }
            ]);
            $extra['selectedTextbook'] = $selectedTextbook;
            $data['textbookOptions'] = $textbookOptions;
            $data['selectedTextbook'] = $selectedTextbook;
        }

        //build up the control filter
        $extra['elements']['control'] = [
            'name' => 'Institution.Textbooks/controls',
            'data' => $data,
            'order' => 3
        ];

        $this->field('comment', ['visible' => false]);
        $this->field('education_subject_id', ['visible' => false]);

        $this->setFieldOrder([
            'code', 'textbook_id', 'textbook_condition_id', 'textbook_status_id', 'student_id'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //filter
        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod'] > 0) {
                $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
            }
        }

        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedSubject'] > 0) {
                $conditions[] = $this->aliasField('education_subject_id = ') . $extra['selectedSubject'];
            }
        }   

        if (array_key_exists('selectedTextbook', $extra)) {
            if ($extra['selectedTextbook'] > 0) {
                $conditions[] = $this->aliasField('textbook_id = ') . $extra['selectedTextbook'];
            }
        }

        $query->where([$conditions]);
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

        if ($entity->student_id) {
            $studentClassGrade = $this->InstitutionSubjectStudents->getStudentClassGradeDetails($entity->academic_period_id, $this->institutionId, $entity->student_id, $entity->education_subject_id);
            $entity->education_grade_id = $studentClassGrade[0]->education_grade_id;
            $entity->institution_class_id = $studentClassGrade[0]->institution_class_id;
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $textbookCode = $this
                        ->Textbooks
                        ->get([
                            'textbook_id' => $data[$this->alias()]['textbook_id'],
                            'academic_period_id' => $data[$this->alias()]['academic_period_id']
                        ])->code;
        // pr($textbookCode);

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
                        
                        $obj['student_id'] = $textbook['student_id'];

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
        if ($entity->student_id) { //retrieve student grade and class
            $studentClassGrade = $this->InstitutionSubjectStudents->getStudentClassGradeDetails($entity->academic_period_id, $this->institutionId, $entity->student_id, $entity->education_subject_id);
            $entity->education_grade_id = $studentClassGrade[0]->education_grade_id;
            $entity->institution_class_id = $studentClassGrade[0]->institution_class_id;
            // pr($entity);
        } else { //if no student assigned to the book, then use the textbook details
            $entity->education_grade_id = $entity->textbook->education_grade_id;
            $entity->institution_class_id = '';
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);

        if ($this->action == 'add') {
            $this->field('code', ['visible' => false]);
            $this->field('comment', ['visible' => false]);
            $this->field('textbook_status_id', ['visible' => false]);
            $this->field('textbook_condition_id', ['visible' => false]);
            $this->field('student_id', ['visible' => false]);
        } else {
            $this->field('textbooks_students', ['visible' => false]);
        }
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
        if ($this->action == 'view') {
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
        $request->query['grade'] = '-1';
        $request->query['class'] = '-1';
        $request->query['subject'] = '-1';
        $request->query['textbook'] = '-1';
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationLevelId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {
            
                $educationLevelOptions = $this->EducationLevels->getLevelOptions();

                $attr['options'] = $educationLevelOptions;
                $attr['onChangeReload'] = 'changeEducationLevel';

            } else if ($action == 'edit') {
                
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_subject->education_grades[0]->education_programme->education_cycle->education_level->system_level_name;
                $attr['value'] = $attr['entity']->education_subject->education_grades[0]->education_programme->education_cycle->education_level->id;
                // pr($attr['entity']);

            }
        }
        return $attr;
    }

    public function addEditOnChangeEducationLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['programme'] = -1;
        $request->query['grade'] = -1;
        $request->query['class'] = -1;
        $request->query['subject'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('education_level_id', $request->data[$this->alias()])) {
                    $request->query['level'] = $request->data[$this->alias()]['education_level_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                $selectedLevel = $request->query('level');

                $programmeOptions = [];
                if ($selectedLevel) {
                    $programmeOptions = $this->EducationProgrammes->getEducationProgrammesList($selectedLevel);
                }
                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgramme';

            } else if ($action == 'edit') {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_subject->education_grades[0]->education_programme->cycle_programme_name;
                $attr['value'] = $attr['entity']->education_subject->education_grades[0]->education_programme->id;
            }
            
        }
        return $attr;
    }

    public function addEditOnChangeEducationProgramme(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['grade'] = -1;
        $request->query['class'] = -1;
        $request->query['subject'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {   
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $selectedProgramme = $request->query('programme');
                $gradeOptions = [];
                if ($selectedProgramme) {
                    $gradeOptions = $this->EducationGrades->getEducationGradesByProgrammes($selectedProgramme);
                }

                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

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
            }
        }
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'edit' && $attr['entity']->institution_class_id && !$request->is(['post', 'put'])) {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->InstitutionClasses->get($attr['entity']->institution_class_id)->name;
                $attr['value'] = $attr['entity']->institution_class_id;

            } else { //this is for "add" and also "edit" that not assigned to any student

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                if ($action == 'edit') {
                    $selectedGrade = $attr['entity']->education_grade_id;
                } else {
                    $selectedGrade = $request->query('grade');
                }

                $classOptions = [];
                if ($selectedGrade) {
                    $classOptions = $this->InstitutionClasses->getClassOptions($selectedPeriod, $this->institutionId, $selectedGrade);
                }

                $attr['options'] = $classOptions;
                $attr['onChangeReload'] = 'changeInstitutionClass';
            }
        }
        return $attr;
    }

    public function addEditOnChangeInstitutionClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['subject'] = '-1';
        $request->query['textbook'] = '-1';
        $request->query['student'] = '-1';
        
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
            }
        }
    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {  
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $selectedClass = $request->query('class');

                $subjectOptions = [];
                if ($selectedClass) {
                    $subjectOptionsTemp = $this->InstitutionSubjects->getSubjectOptions($selectedClass);
                    foreach ($subjectOptionsTemp as $key => $value) {
                        $subjectOptions[$value->education_subject->id] = $value->education_subject->code . ' - ' . $value->education_subject->name;
                    }
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
        $request->query['textbook'] = '-1';
        
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
            }
        }
    }

    public function onUpdateFieldTextbookId(Event $event, array $attr, $action, Request $request)
    {  
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                $selectedGrade = $request->query('grade');
                $selectedSubject = $request->query('subject');

                $textbookOptions = [];
                if ($selectedPeriod && $selectedGrade && $selectedSubject) {
                    $textbookOptions = $this->Textbooks->getTextbookOptions($selectedPeriod, $selectedGrade, $selectedSubject);
                }
                $attr['options'] = $textbookOptions;
                // $attr['onChangeReload'] = 'changeTextbook';
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
            }
        }
    }

    public function onGetCustomTextbooksStudentsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        // $tableHeaders = [$this->getMessage($this->aliasField('trainer_type')), $this->getMessage($this->aliasField('trainer'))];
        $tableHeaders = [
            __('Textbook ID') . "&nbsp;
                        <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' data-toggle='tooltip' data-placement='bottom' title='" . 
                            __('Textbook ID is unique number assigned to each book within the school, Leave it empty for autogenerated Code.') . 
                        "'></i>",
            __('Status') . "&nbsp;
                        <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' data-toggle='tooltip' data-placement='bottom' title='" . 
                            __('Available means physically book exists while Not Available means it is missing') . 
                        "'></i>",
            __('Condition') . "&nbsp;
                        <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' data-toggle='tooltip' data-placement='bottom' title='" . 
                            __('Condition of each available book') . 
                        "'></i>",
            __('Comment') . "&nbsp;
                        <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' data-toggle='tooltip' data-placement='bottom' title='" . 
                            __('Comments about each book regardless of availability') . 
                        "'></i>", 
            __('Allocated To') . "&nbsp;
                        <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' data-toggle='tooltip' data-placement='bottom' title='" . 
                            __('Each book can be optionally allocated to an individual student') . 
                        "'></i>"
        ];
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
            $studentOptions = $this->InstitutionSubjectStudents->getStudentList($entity->academic_period_id, $entity->institution_class_id, $entity->education_subject_id);

            $studentOptions = array('null' => __('-- Select --')) + $studentOptions; //additional default option

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
                        $student_id = $obj['student_id'];

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
                        $rowData[] = $Form->input("$alias.$fieldKey.$key.student_id", ['type' => 'select', 'label' => false, 'options' => $studentOptions]);
                       
                        $rowData[] = $this->getDeleteButton();
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

            if ($data[$alias]['institution_class_id'] && $data[$alias]['education_subject_id'] && $data[$alias]['textbook_id']) {

                // $textbookCode = $this
                //                 ->Textbooks
                //                 ->get([
                //                     'textbook_id' => $data[$alias]['textbook_id'],
                //                     'academic_period_id' => $data[$alias]['academic_period_id']
                //                 ])->code;

                //count current counter on database
                // $textbookCounter = $this
                //                     ->find()
                //                     ->where([
                //                         $this->aliasField('textbook_id') => $data[$alias]['textbook_id'],
                //                         $this->aliasField('institution_id') => $this->institutionId,
                //                         $this->aliasField('academic_period_id') => $data[$alias]['academic_period_id']
                //                     ])
                //                     ->count();
                
                //generate code autonumber
                // if (!array_key_exists($fieldKey, $data[$alias])) { //no record
                //     $textbookStudentCounter = $textbookCounter + 1;
                // } else {
                //     $textbookStudentCounter = $textbookCounter + count($data[$alias][$fieldKey]) + 1;
                // }

                // if ($textbookStudentCounter < 10) {
                //     $zeroPrefix = '00';
                // } else if ($textbookStudentCounter < 100) {
                //     $zeroPrefix = '0';
                // }
                // $textbookCode .= "-" . $zeroPrefix . $textbookStudentCounter;

                $data[$alias][$fieldKey][] = [
                    'code' => '',
                    'textbook_status_id' => '',
                    'textbook_condition_id' => '',
                    'comment' => '',
                    'student_id' => ''
                ];
            } else {
                $this->Alert->error('Textbooks.noClassSubjectSelected');
            }
        }
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {  
        if ($action == 'edit') {

            $selectedPeriod= $attr['entity']->academic_period_id;
            $selectedSubject = $attr['entity']->education_subject_id;

            $selectedClass = $attr['entity']->institution_class_id;

            $studentOptions = [];
            if ($selectedPeriod && $selectedClass && $selectedSubject) {
                $studentOptions = $this->InstitutionSubjectStudents->getStudentList($selectedPeriod, $selectedClass, $selectedSubject);
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

        $this->field('education_level_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('education_grade_id', [
            'type' => 'select', 
            'entity' => $entity
        ]);

        $this->field('institution_class_id', [
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

        $this->field('student_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'multiple' => false
            ],
            'select' => true,
            'entity' => $entity
        ]);

        $fieldOrder = [
            'academic_period_id', 'education_grade_id', 'institution_class_id', 'education_subject_id', 'textbook_id', 'textbook_id',
            'code', 'textbook_status_id', 'textbook_condition_id', 'comment', 'student_id'
        ];
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
}
