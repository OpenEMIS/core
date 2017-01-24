<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Text;

class StudentCompetencyResultsTable extends ControllerActionTable {
    public function initialize(array $config) {
        $this->table('competency_results');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('CompetencyTemplates', ['className' => 'Competency.Templates', 'foreignKey' => ['competency_template_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyItems', ['className' => 'Competency.Items', 'foreignKey' => ['competency_item_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyCriterias', ['className' => 'Competency.Criterias', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);
        $this->belongsTo('CompetencyPeriods', ['className' => 'Competency.Periods', 'foreignKey' => ['competency_period_id', 'academic_period_id']]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);

        // $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        // $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        

        // $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
        // $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
        // $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

        // $this->behaviors()->get('ControllerAction')->config('actions.add', false);
        // $this->behaviors()->get('ControllerAction')->config('actions.search', false);
        // $this->addBehavior('Excel', [
        //     'pages' => ['index'],
        //     'orientation' => 'landscape'
        // ]);

        // $this->toggle('edit', false);
        // $this->toggle('remove', false);

        $this->classId = $this->getQueryString('class_id');
        $this->competencyTemplateId = $this->getQueryString('competency_template_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');
        $this->competencyPeriodId = 1;

        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->StudentClasses = TableRegistry::get('Student.StudentClasses');
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);

        $this->field('marks', ['visible' => false]);
        $this->field('competency_grading_option_id', ['visible' => false]);

        // if ($this->action == 'add') {
        //     $this->field('code', ['visible' => false]);
        //     $this->field('comment', ['visible' => false]);
        //     $this->field('textbook_status_id', ['visible' => false]);
        //     $this->field('textbook_condition_id', ['visible' => false]);
        //     $this->field('student_id', ['visible' => false]);
        // } else {
        //     $this->field('textbooks_students', ['visible' => false]);
        // }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $attr['attr']['value'] = $this->AcademicPeriods->get($this->academicPeriodId)->name;
        $attr['value'] = $this->academicPeriodId;
            
        return $attr;
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
    {
        $attr['attr']['value'] = $this->CompetencyTemplates->get([$this->competencyTemplateId, $this->academicPeriodId])->code_name;
        $attr['value'] = $this->competencyTemplateId;
            
        return $attr;
    }

    public function onUpdateFieldCompetencyItemId(Event $event, array $attr, $action, Request $request)
    {
        $itemOptions = $this->CompetencyItems->getItemByTemplateAcademicPeriod($this->competencyTemplateId, $this->academicPeriodId);
        $attr['options'] = $itemOptions;
        $attr['onChangeReload'] = 'changeCompetencyItem';
        return $attr;
    }

    public function addEditOnChangeCompetencyItem(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['item'] = '-1';
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('competency_item_id', $request->data[$this->alias()])) {
                    $request->query['item'] = $request->data[$this->alias()]['competency_item_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $attr['attr']['value'] = $this->CompetencyPeriods->get([$this->competencyPeriodId, $this->academicPeriodId])->code_name;
        $attr['value'] = 1;
            
        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $attr['attr']['value'] = $this->Institutions->get([$this->institutionId])->code_name;
        $attr['value'] = $this->institutionId;
            
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {
        $institutionClass = $this->InstitutionClasses->find()
                            ->where([
                                $this->InstitutionClasses->aliasField('id') => $this->classId,
                                $this->InstitutionClasses->aliasField('institution_id') => $this->institutionId,
                                $this->InstitutionClasses->aliasField('academic_period_id') => $this->academicPeriodId
                            ])
                            ->first();
        // pr($institutionClass->toArray());
        $attr['attr']['value'] = $institutionClass['name'];
        $attr['value'] = $institutionClass['id'];

        return $attr;
    }

    public function onGetCustomCompetencyResultsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if (array_key_exists('item', $this->request->query) && ($this->request->query['item'])) {

            $selectedCompetencyItem = $this->request->query['item'];

            //get existing result so saved value can be maintained
            $existingCompetencyResult = $this->getExistingCompetencyResult($this->competencyTemplateId, $selectedCompetencyItem, $this->competencyPeriodId, $this->academicPeriodId, $this->institutionId);
            if (!empty($existingCompetencyResult)) {
                //massage array so can be accessed easier later.
                $existing_competency_results = [];
                foreach ($existingCompetencyResult as $key => $value) {
                    $existing_competency_results[$value->student_id][$value->competency_criteria_id] = $value->competency_grading_option_id;
                }
                // pr($existing_competency_results);
            }
            // die;

            $criteriaList = $this->CompetencyCriterias->getCompetencyCriterias($selectedCompetencyItem, $this->academicPeriodId);
            // pr($criteriaList);
            if (!empty($criteriaList)) {

                //fix header
                $tableHeaders[] = _('OpenEMIS ID');
                $tableHeaders[] = _('Student');
                //dynamic header based on the criterias set up.
                foreach ($criteriaList as $key => $value) {
                    $tableHeaders[] = 
                        substr(__($value->name), 0, 35) . '...' .
                        "<div class='tooltip-desc' style='display: inline-block;'>
                            <i class='fa fa-info-circle fa-lg table-tooltip icon-blue' tooltip-placement='top' uib-tooltip='" .  __($value->name) . "' tooltip-append-to-body='true' tooltip-class='tooltip-blue'></i>
                        </div>";
                }

                $alias = $this->alias();

                $fieldKey = 'competency_results';
                $Form = $event->subject()->Form;

                $studentList = $this->StudentClasses->getClassStudents($this->classId, $this->academicPeriodId, $this->institutionId);
                
                if (!empty($studentList)) {
                    $tableCells = [];
                    foreach ($studentList as $key => $value) { //loop through student
                        $studentId = $value->student_id;
                        $rowData = [];
                        
                        $rowData[] = $value->user->openemis_no;
                        $rowData[] = $value->user->name;
                        foreach ($criteriaList as $key1 => $value1) { //loop through criterias
                            $criteriaId = $value1->id;
                            $gradingOptions = $value1->grading_type->grading_options;
                            if (count($gradingOptions)){ //for grading type with option
                                $optionList = [];
                                foreach ($gradingOptions as $key2 => $value2) {
                                    $optionList[$value2->id] = $value2->name;
                                }
                                $selectedGradingOption = -1;
                                if (count($optionList)) {
                                    //check existing array for saved value, set default selected grading option
                                    if (array_key_exists($studentId, $existing_competency_results)) {
                                        if (array_key_exists($criteriaId, $existing_competency_results[$studentId])) {
                                            $selectedGradingOption = $existing_competency_results[$studentId][$criteriaId];
                                        }
                                    }
                                    $rowData[] = $Form
                                                ->input("$alias.$fieldKey.$studentId.$criteriaId.grading_option_id", [
                                                        'type' => 'select', 'label' => false, 
                                                        'options' => $optionList, 'default' => $selectedGradingOption
                                                ]);
                                } 
                            } else { //if no option declared, then show string input.
                                $rowData[] = $Form->input("$alias.$fieldKey.$studentId.$criteriaId.grading_option_id", ['type' => 'string', 'label' => false]);
                            }
                        }
                        $tableCells[] = $rowData;
                    }

                    $attr['tableHeaders'] = $tableHeaders;
                    $attr['tableCells'] = $tableCells;

                    return $event->subject()->renderElement('Institution.Competencies/'.$fieldKey, ['attr' => $attr]);
                } else {
                    $this->Alert->warning('Competencies.noClassStudents', ['reset'=>true]);
                }
                
                // //generate textbook condition and status
                // $textbookConditionOptions = $this->TextbookConditions->getTextbookConditionOptions();
                // $textbookStatusOptions = $this->TextbookStatuses->getSelectOptions();

                // if (!count($textbookConditionOptions) || !count($textbookStatusOptions)) { //if no condition / status created.
                //     $this->Alert->error('Textbooks.noTextbookStatusCondition');
                // } else {

                //     //generate `list`
                //     $studentOptions = $this->InstitutionSubjectStudents->getEnrolledStudentBySubject($entity->academic_period_id, $entity->institution_class_id, $entity->education_subject_id);

                //     $studentOptions = array('null' => __('-- Select --')) + $studentOptions; //additional default option

                //     if ($action == 'add' || $action == 'edit') {
                //         $tableHeaders[] = ''; // for delete column
                //         $Form = $event->subject()->Form;
                //         $Form->unlockField('InstitutionTextbooks.textbooks_students');

                //         // refer to addEditOnAddTextbooksStudents for http post
                //         if ($this->request->data("$alias.$fieldKey")) {
                //             $associated = $this->request->data("$alias.$fieldKey");

                //             foreach ($associated as $key => $obj) {
                //                 $code = $obj['code'];
                //                 $textbook_status_id = $obj['textbook_status_id'];
                //                 $textbook_condition_id = $obj['textbook_condition_id'];
                //                 $comment = $obj['comment'];
                //                 $student_id = $obj['student_id'];

                //                 $rowData = [];

                //                 //to insert error message if validation kicked in.
                //                 $tempRowData = $Form->input("$alias.$fieldKey.$key.code", ['label' => false]);

                //                 if ($entity->errors("textbooks_students.$key") && isset($entity->errors("textbooks_students.$key")['code'])) {

                //                     $tempRowData .= "<ul class='error-message'>";
                //                     foreach ($entity->errors("textbooks_students.$key")['code'] as $error) {
                //                         $tempRowData .= __($error);
                //                     }
                //                     $tempRowData .= "</ul>";

                //                 }   

                //                 $rowData[] = $tempRowData;
                //                 $rowData[] = $Form->input("$alias.$fieldKey.$key.textbook_status_id", ['type' => 'select', 'label' => false, 'options' => $textbookStatusOptions]);
                //                 $rowData[] = $Form->input("$alias.$fieldKey.$key.textbook_condition_id", ['type' => 'select', 'label' => false, 'options' => $textbookConditionOptions]);
                //                 $rowData[] = $Form->input("$alias.$fieldKey.$key.comment", ['type' => 'text', 'label' => false]);
                //                 $rowData[] = $Form->input("$alias.$fieldKey.$key.student_id", ['type' => 'select', 'label' => false, 'options' => $studentOptions]);
                               
                //                 $rowData[] = $this->getDeleteButton();
                                // $tableCells[] = $rowData;
                //             }
                //         }
                //     }

                
                // }

            } else {
                $this->Alert->warning('Competencies.noCompetencyCriterias', ['reset'=>true]);
            }
        }
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $options['validate'] = false; //remove all validation since insertion will be done manually.
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        // $textbookCode = '';
        // if ($data[$this->alias()]['textbook_id'] && $data[$this->alias()]['academic_period_id']) {
        //     $textbookCode = $this
        //                 ->Textbooks
        //                 ->get([
        //                     'textbook_id' => $data[$this->alias()]['textbook_id'],
        //                     'academic_period_id' => $data[$this->alias()]['academic_period_id']
        //                 ])->code;
        // // pr($textbookCode); 
        // }

        // pr($data);

        // pr($entity);die;

        //redefine after save redirect.
        $extra['redirect'] = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencies', 'index'];

        $process = function ($model, $entity) use ($data) {
            $newEntities = [];

            if (array_key_exists('competency_results', $data[$this->alias()])) {

                $dataResults = $data[$this->alias()];
                $results = $dataResults['competency_results'];

                if (count($results)) {

                    // pr($entity);

                    foreach ($results as $student => $result) {

                        foreach ($result as $criteria => $gradingOption) {
                            $obj['id'] = Text::uuid();
                            $obj['competency_grading_option_id'] = $gradingOption['grading_option_id'];
                            $obj['student_id'] = $student;
                            $obj['competency_template_id'] = $dataResults['competency_template_id'];
                            $obj['competency_item_id'] = $dataResults['competency_item_id'];
                            $obj['competency_criteria_id'] = $criteria;
                            $obj['competency_period_id'] = 1;
                            $obj['institution_id'] = $dataResults['institution_id'];
                            $obj['academic_period_id'] = $dataResults['academic_period_id'];
                            $newEntities[] = $obj;
                        }

                        // $obj['code'] = $textbook['code'];
                        // $obj['comment'] = $textbook['comment'];
                        // $obj['textbook_status_id'] = $textbook['textbook_status_id'];
                        // $obj['textbook_condition_id'] = $textbook['textbook_condition_id'];
                        
                        // $obj['student_id'] = $textbook['student_id'];

                        // $obj['institution_id'] = $entity->institution_id;
                        // $obj['academic_period_id'] = $entity->academic_period_id;
                        // $obj['education_grade_id'] = $entity->education_grade_id;
                        // $obj['education_subject_id'] = $entity->education_subject_id;
                        // $obj['textbook_id'] = $entity->textbook_id;
                        // $obj['counterNo'] = $key;

                        // $obj['competency_grading_option_id'] = $index
                        
                        // $newEntities[] = $obj;
                    }

                    $success = $this->connection()->transactional(function() use ($newEntities, $entity) {
                        $return = true;
                        foreach ($newEntities as $key => $newEntity) {

                            $studentCompetencyResultEntity = $this->newEntity($newEntity);

                            // if ($studentCompetencyResultEntity->errors('code')) {
                            //     $counterNo = $newEntity['counterNo'];
                            //     $entity->errors("competency_results.$counterNo", ['code' => $textbookStudentEntity->errors('code')]);
                            // }

                            //check whether student still on the class

                            if (!$this->save($studentCompetencyResultEntity)) {
                                $return = false;
                            } else { 
                                //this is to autofill book code when it was left empty.
                                //code is using textbook code - autonumber ID generated by database
                                // if ($newEntity['code']) {
                                //     $bookCode = $newEntity['code'];
                                // } else {
                                //     $bookCode = $textbookCode . '-' . $textbookStudentEntity->id;
                                // }
                                // $this->updateAll(
                                //     ['code' => $bookCode],
                                //     ['id' => $textbookStudentEntity->id]
                                // );
                            }
                        }
                        return $return;
                    });
                    // die;
                    return $success;
                }
            } else { //if no textbook student added and user try to save
                $entity->errors('competency_results', __('There are no results added'));
                $this->Alert->error('Competencies.noResultsAdded', ['reset'=>true]);
            }
        };
        return $process;
    }

    private function getExistingCompetencyResult($template, $item, $period, $academicPeriod, $institutionId)
    {
        return  $this->find()
                // ->contain([
                //     'CompetencyCriterias', 'Students'
                // ])
                ->where([
                    $this->aliasField('competency_template_id') => $template,
                    $this->aliasField('competency_item_id') => $item,
                    $this->aliasField('competency_period_id') => $period,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('academic_period_id') => $academicPeriod
                ])
                ->toArray();

        //pr($query->toArray());
    }

    private function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'readonly', 
            'entity' => $entity
        ]);

        $this->field('competency_template_id', [
            'type' => 'readonly',
            'entity' => $entity
        ]);

        $this->field('competency_item_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('competency_period_id', [
            'type' => 'readonly',
            'entity' => $entity
        ]);

        $this->field('institution_id', [
            'type' => 'readonly', 
            'entity' => $entity
        ]);

        $this->field('institution_class_id', [
            'type' => 'readonly', 
            'entity' => $entity
        ]);

        $this->field('competency_results', [
            'type' => 'custom_competency_results',
            'valueClass' => 'table-full-width'
        ]);

        // $this->field('student_id', [
        //     'type' => 'chosenSelect',
        //     'attr' => [
        //         'multiple' => false
        //     ],
        //     'select' => true,
        //     'entity' => $entity
        // ]);

        $fieldOrder = [
            'academic_period_id', 'competency_template_id', 'competency_item_id', 'institution_id', 'institution_class_id'
        ];
    }

    // public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    // {
    //     pr($entity);
    //     if ($entity->student_id) { //retrieve student grade and class
    //         $studentClassGrade = $this->InstitutionSubjectStudents->getStudentClassGradeDetails($entity->academic_period_id, $this->institutionId, $entity->student_id, $entity->education_subject_id);
    //         $entity->education_grade_id = $studentClassGrade[0]->education_grade_id;
    //         $entity->institution_class_id = $studentClassGrade[0]->institution_class_id;
    //         // pr($entity);
    //     } else { //if no student assigned to the book, then use the textbook details
    //         $entity->education_grade_id = $entity->textbook->education_grade_id;
    //         $entity->institution_class_id = '';
    //     }
    // }


    // public function editAfterAction(Event $event, Entity $entity) {
        // if ($entity->has('is_system_defined') && !empty($entity->is_system_defined)) {
        //     $this->Alert->info($this->aliasField('systemDefined'));
        // }
        // pr('111');die;
    // }

    // public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {
    //     $institutionId = $this->Session->read('Institution.Institutions.id');
    //     $institutionCode = $this->Institutions->get($institutionId)->code;
    //     $settings['file'] = str_replace($this->alias(), str_replace(' ', '_', $institutionCode).'_Results', $settings['file']);
    // }

    // public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
    //     $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
    //     $query = $InstitutionClassStudentsTable->find();

    //     // For filtering all classes and my classes
    //     $AccessControl = $this->AccessControl;
    //     $userId = $this->Session->read('Auth.User.id');
    //     $institutionId = $this->Session->read('Institution.Institutions.id');
    //     $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);

    //     $allSubjectsPermission = true;
    //     $mySubjectsPermission = true;
    //     $allClassesPermission = true;
    //     $myClassesPermission = true;

    //     if (!$AccessControl->isAdmin())
    //     {
    //         if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles) ) {
    //             $allSubjectsPermission = false;
    //             $mySubjectsPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
    //         }

    //         if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
    //             $allClassesPermission = false;
    //             $myClassesPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
    //         }
    //     }

    //     $assessmentId = $this->request->query('assessment_id');
    //     if($assessmentId) {
    //         $sheets[] = [
    //             'name' => $this->alias(),
    //             'table' => $InstitutionClassStudentsTable,
    //             'query' => $query,
    //             'assessmentId' => $assessmentId,
    //             'staffId' => $userId,
    //             'institutionId' => $institutionId,
    //             'mySubjectsPermission' => $mySubjectsPermission,
    //             'allSubjectsPermission' => $allSubjectsPermission,
    //             'allClassesPermission' => $allClassesPermission,
    //             'myClassesPermission' => $myClassesPermission,
    //             'orientation' => 'landscape'
    //         ];
    //     }
    // }

    // public function beforeAction(Event $event, ArrayObject $extra) {
    //     $this->field('class_number', ['visible' => false]);
    //     $this->field('staff_id', ['visible' => false]);
    //     $this->field('institution_shift_id', ['visible' => false]);
    // }

    // public function indexBeforeAction(Event $event, ArrayObject $extra) {
    //     $extra['elements']['controls'] = ['name' => 'Institution.Competencies/controls', 'data' => [], 'options' => [], 'order' => 1];

    //     $this->field('competency_template');
    //     $this->field('education_grade');
    //     // $this->field('subjects');
    //     // $this->field('male_students');
    //     // $this->field('female_students');

    //     $this->setFieldOrder(['name', 'assessment', 'academic_period_id', 'education_grade', 'subjects', 'male_students', 'female_students']);
    // }

    // public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
    //     $session = $this->request->session();
    //     $institutionId = $session->read('Institution.Institutions.id');

    //     $Classes = TableRegistry::get('Institution.InstitutionClasses');
    //     $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
    //     $Competencies = TableRegistry::get('Competency.Templates');
    //     $EducationGrades = TableRegistry::get('Education.EducationGrades');
    //     $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

    //     $query
    //         ->select([
    //             'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
    //             'education_grade_id' => $Competencies->aliasField('education_grade_id'),
    //             'competency_template_id' => $Competencies->aliasField('id'),
    //             'competency_template' => $query->func()->concat([
    //                 $Competencies->aliasField('code') => 'literal',
    //                 " - ",
    //                 $Competencies->aliasField('name') => 'literal'
    //             ])
    //         ])
    //         ->innerJoin(
    //             [$ClassGrades->alias() => $ClassGrades->table()],
    //             [$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
    //         )
    //         ->innerJoin(
    //             [$Competencies->alias() => $Competencies->table()],
    //             [
    //                 $Competencies->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
    //                 $Competencies->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
    //             ]
    //         )
    //         ->innerJoin(
    //             [$EducationGrades->alias() => $EducationGrades->table()],
    //             [$EducationGrades->aliasField('id = ') . $Competencies->aliasField('education_grade_id')]
    //         )
    //         ->innerJoin(
    //             [$EducationProgrammes->alias() => $EducationProgrammes->table()],
    //             [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
    //         )
    //         ->group([
    //             $ClassGrades->aliasField('institution_class_id'),
    //             $Competencies->aliasField('id')
    //         ])
    //         ->autoFields(true)
    //         ;

    //     $extra['options']['order'] = [
    //         $EducationProgrammes->aliasField('order') => 'asc',
    //         $EducationGrades->aliasField('order') => 'asc',
    //         $Competencies->aliasField('code') => 'asc',
    //         $Competencies->aliasField('name') => 'asc',
    //         $this->aliasField('name') => 'asc'
    //     ];

    //     // For filtering all classes and my classes
    //     // $AccessControl = $this->AccessControl;
    //     // $userId = $session->read('Auth.User.id');
    //     // $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
    //     // if (!$AccessControl->isAdmin())
    //     // {
    //     //     if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles) )
    //     //     {
    //     //         $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
    //     //         $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
    //     //         if (!$classPermission && !$subjectPermission)
    //     //         {
    //     //             $query->where(['1 = 0'], [], true);
    //     //         } else
    //     //         {
    //     //             $query->innerJoin(['InstitutionClasses' => 'institution_classes'], [
    //     //                 'InstitutionClasses.id = '.$ClassGrades->aliasField('institution_class_id'),
    //     //                 ])
    //     //                 ;

    //     //             // If only class permission is available but no subject permission available
    //     //             if ($classPermission && !$subjectPermission) {
    //     //                 $query->where(['InstitutionClasses.staff_id' => $userId]);
    //     //             } else {
    //     //                 $query
    //     //                     ->innerJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
    //     //                         'InstitutionClassSubjects.institution_class_id = InstitutionClasses.id',
    //     //                         'InstitutionClassSubjects.status =   1'
    //     //                     ])
    //     //                     ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
    //     //                         'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
    //     //                     ]);

    //     //                 // If both class and subject permission is available
    //     //                 if ($classPermission && $subjectPermission) {
    //     //                     $query->where([
    //     //                         'OR' => [
    //     //                             ['InstitutionClasses.staff_id' => $userId],
    //     //                             ['InstitutionSubjectStaff.staff_id' => $userId]
    //     //                         ]
    //     //                     ]);
    //     //                 }
    //     //                 // If only subject permission is available
    //     //                 else {
    //     //                     $query->where(['InstitutionSubjectStaff.staff_id' => $userId]);
    //     //                 }
    //     //             }
    //     //         }
    //     //     }
    //     // }

    //     // Academic Periods
    //     $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
    //     if (is_null($this->request->query('period'))) {
    //         // default to current Academic Period
    //         $this->request->query['period'] = $this->AcademicPeriods->getCurrent();
    //     }
    //     $selectedPeriod = $this->queryString('period', $periodOptions);
    //     // $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
    //     //     'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noAssessments')),
    //     //     'callable' => function($id) use ($Classes, $ClassGrades, $Assessments, $institutionId) {
    //     //         return $Classes
    //     //             ->find()
    //     //             ->innerJoin(
    //     //                 [$ClassGrades->alias() => $ClassGrades->table()],
    //     //                 [
    //     //                     $ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id')
    //     //                 ]
    //     //             )
    //     //             ->innerJoin(
    //     //                 [$Assessments->alias() => $Assessments->table()],
    //     //                 [
    //     //                     $Assessments->aliasField('academic_period_id = ') . $Classes->aliasField('academic_period_id'),
    //     //                     $Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
    //     //                 ]
    //     //             )
    //     //             ->where([
    //     //                 $Classes->aliasField('institution_id') => $institutionId,
    //     //                 $Classes->aliasField('academic_period_id') => $id
    //     //             ])
    //     //             ->count();
    //     //     }
    //     // ]);
    //     $this->controller->set(compact('periodOptions', 'selectedPeriod'));
    //     // End

    //     if (!empty($selectedPeriod)) {
    //         $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);

    //         // Assessments
    //         $competencyOptions = $Competencies
    //             ->find('list')
    //             ->where([$Competencies->aliasField('academic_period_id') => $selectedPeriod])
    //             ->toArray();
    //         $competencyOptions = ['-1' => __('All Competencies')] + $competencyOptions;
    //         $selectedCompetency = $this->queryString('competency', $competencyOptions);
    //         // $this->advancedSelectOptions($assessmentOptions, $selectedAssessment, [
    //         //     'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
    //         //     'callable' => function($id) use ($Classes, $ClassGrades, $Assessments, $institutionId, $selectedPeriod) {
    //         //         if ($id == -1) { return 1; }
    //         //         $selectedGrade = $Assessments->get($id)->education_grade_id;
    //         //         return $Classes
    //         //             ->find()
    //         //             ->innerJoin(
    //         //                 [$ClassGrades->alias() => $ClassGrades->table()],
    //         //                 [
    //         //                     $ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id'),
    //         //                     $ClassGrades->aliasField('education_grade_id') => $selectedGrade
    //         //                 ]
    //         //             )
    //         //             ->where([
    //         //                 $Classes->aliasField('institution_id') => $institutionId,
    //         //                 $Classes->aliasField('academic_period_id') => $selectedPeriod
    //         //             ])
    //         //             ->count();
    //         //     }
    //         // ]);
    //         $this->controller->set(compact('competencyOptions', 'selectedCompetency'));
    //         // End

    //         if ($selectedCompetency != '-1') {
    //             $query->where([$Competencies->aliasField('id') => $selectedCompetency]);
    //         }
    //     }

    //     // $assessmentId = $this->request->query('assessment_id');

    //     // if ($assessmentId == -1 || !$assessmentId || !$this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles)) {
    //     //     if (isset($extra['toolbarButtons']['export'])) {
    //     //         unset($extra['toolbarButtons']['export']);
    //     //     }
    //     // }
    // }

    // public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
    //     if ($field == 'name') {
    //         return __('Class Name');
    //     } else {
    //         return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    //     }
    // }

    // public function onGetEducationGrade(Event $event, Entity $entity) {
    //     $EducationGrades = TableRegistry::get('Education.EducationGrades');
    //     $grade = $EducationGrades->get($entity->education_grade_id);

    //     return $grade->programme_grade_name;
    // }

    // public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
    //     $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
    //     if (isset($buttons['view']['url'])) {
    //         $url = [
    //             'plugin' => $this->controller->plugin,
    //             'controller' => $this->controller->name,
    //             'action' => 'StudentCompetencyResults'
    //         ];

    //         $buttons['view']['url'] = $this->setQueryString($url, [
    //             'class_id' => $entity->institution_class_id,
    //             'competency_template_id' => $entity->competency_template_id,
    //             'institution_id' => $entity->institution_id,
    //             'academic_period_id' => $entity->academic_period_id
    //         ]);
    //     }

    //     return $buttons;
    // }
}
