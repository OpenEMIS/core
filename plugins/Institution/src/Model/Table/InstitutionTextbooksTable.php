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
        
        $this->belongsTo('Textbooks', ['className' => 'Textbook.Textbooks']);
        $this->belongsTo('TextbookStatuses', ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions', ['className' => 'Textbook.TextbookConditions']);
        
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        $this->InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->EducationGradeSubjects = TableRegistry::get('Education.EducationGradeSubjects');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxClassStudentsAutocomplete'] = 'ajaxClassStudentsAutocomplete';
        return $events;
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

        //education programmes filter
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

        //education subjects filter
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

        //textbook filter
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

        //build up the control filter
        $extra['selectedPeriod'] = $selectedPeriod;
        $extra['selectedGrade'] = $selectedGrade;
        $extra['selectedSubject'] = $selectedSubject;
        $extra['selectedTextbook'] = $selectedTextbook;
        $extra['elements']['control'] = [
            'name' => 'Institution.Textbooks/controls',
            'data' => [
                'periodOptions'=> $periodOptions,
                'selectedPeriod'=> $selectedPeriod,
                'gradeOptions'=> $gradeOptions,
                'selectedGrade'=> $selectedGrade,
                'subjectOptions'=> $subjectOptions,
                'selectedSubject'=> $selectedSubject,
                'textbookOptions'=> $textbookOptions,
                'selectedTextbook'=> $selectedTextbook,
            ],
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //filter
        if ($extra['selectedPeriod']) {
            $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
        }

        if ($extra['selectedSubject'] > 0) {
            $conditions[] = $this->aliasField('education_subject_id = ') . $extra['selectedSubject'];
        }

        if ($extra['selectedTextbook'] > 0) {
            $conditions[] = $this->aliasField('textbook_id = ') . $extra['selectedTextbook'];
        }

        $query->where([$conditions]);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->student_id) { //retrieve student grade and class
            $studentClassGrade = $this->InstitutionSubjectStudents->getStudentClassGradeDetails($entity->academic_period_id, $this->institutionId, $entity->student_id, $entity->education_subject_id);
            $entity->education_grade_id = $studentClassGrade[0]->education_grade_id;
            $entity->institution_class_id = $studentClassGrade[0]->institution_class_id;
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

    public function onGetTextbookId(Event $event, Entity $entity)
    {
        return $entity->textbook->code . " - " . $entity->textbook->title;
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

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {   
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

                if ($selectedPeriod) {
                    $gradeOptions = $this->InstitutionGrades->getGradeOptions($this->institutionId, $selectedPeriod);
                }
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
                $attr['attr']['value'] = $this->Textbooks->get($attr['entity']->textbook_id)->title;
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
        $tableHeaders = ['Code', 'Status', 'Condition', 'Comment', 'Student'];
        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'textbooks_students';
        
        //generate textbook condition and status
        $textbookConditionOptions = $this->TextbookConditions->getSelectOptions();
        $textbookStatusOptions = $this->TextbookStatuses->getSelectOptions();

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
                    
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.code", ['label' => false]);
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.textbook_status_id", ['type' => 'select', 'label' => false, 'options' => $textbookStatusOptions]);
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.textbook_condition_id", ['type' => 'select', 'label' => false, 'options' => $textbookConditionOptions]);
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.comment", ['label' => false]);
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

    public function addEditOnAddTextbooksStudents(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'textbooks_students';

        if ($data['submit'] == 'addTextbooksStudents') { //during the add books, need to ensure that class and subject has value.

            if ($data[$alias]['institution_class_id'] && $data[$alias]['education_subject_id'] && $data[$alias]['textbook_id']) {

                $textbookCode = $this->Textbooks->get($data[$alias]['textbook_id'])->code;
                
                //generate code autonumber
                if (!array_key_exists($fieldKey, $data[$alias])) { //no record
                    $textbookStudentCounter = 1;
                } else {
                    $textbookStudentCounter = count($data[$alias][$fieldKey]) + 1;
                }

                if ($textbookStudentCounter < 10) {
                    $zeroPrefix = '00';
                } else if ($textbookStudentCounter < 100) {
                    $zeroPrefix = '0';
                }
                $textbookCode .= "-" . $zeroPrefix . $textbookStudentCounter;

                $data[$alias][$fieldKey][] = [
                    'code' => $textbookCode,
                    'textbook_status_id' => '',
                    'textbook_condition_id' => '',
                    'comment' => '',
                    'student_id' => ''
                ];
            } else {
                $this->Alert->error('Textbooks.cantAddTextbook');
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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Users', 'Textbooks'
        ]);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $process = function ($model, $entity) use ($data) {
            $newEntities = [];

            $textbooks = $data[$this->alias()]['textbooks_students'];
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

                $newEntities[] = $obj;
            }

            $success = $this->connection()->transactional(function() use ($newEntities, $entity) {
                $return = true;
                foreach ($newEntities as $key => $newEntity) {
                    $textbookStudentEntity = $this->newEntity($newEntity);

                    if (!$this->save($textbookStudentEntity)) {
                        $return = false;
                    }
                }
                return $return;
            });
            return $success;
        };
        return $process;
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

        if ($entity->institution_class_id) {
            $institution_class_id = $entity->institution_class_id;
        } else if ($this->request->query){ //if no class during edit, it means no student assigned, then must get from the querystring
            $institution_class_id = $this->request->query['class'];
        }

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
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }
}
