<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;

class StudentCompetenciesTable extends ControllerActionTable
{
    private $classId = null;
    private $institutionId = null;
    private $academicPeriodId = null;
    private $competencyTemplateId = null;
    private $competencyPeriodId = null;
    private $competencyItemId = null;
    private $studentId = null;
    private $studentStatusName = null;

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('SecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'foreignKey' => 'secondary_staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

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

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportCompetencyResults']);

        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action != 'index') {
            $tabElements = $this->controller->getCompetencyTabElements();
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', 'StudentCompetencies');
        }

        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['type' => 'hidden']);
        $this->field('secondary_staff_id', ['type' => 'hidden']);
        $this->field('institution_shift_id', ['type' => 'hidden']);
        $this->field('capacity', ['type' => 'hidden']);
        $this->field('modified_user_id', ['type' => 'hidden']);
        $this->field('modified', ['type' => 'hidden']);
        $this->field('created_user_id', ['type' => 'hidden']);
        $this->field('created', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Institution.Competencies/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->field('competency_template');
        $this->field('education_grade');

        $this->setFieldOrder(['name', 'academic_period_id', 'education_grade', 'competency_template']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session(); 
        if ($this->controller->name == 'Profiles') {
            $userData = $this->Session->read();
			$studentId = $userData['Auth']['User']['id'];
        } else {
            $studentId = $session->read('Student.Students.id');
        }
        
		
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Competencies = TableRegistry::get('Competency.CompetencyTemplates');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        $query
            ->select([
                'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
                'education_grade_id' => $Competencies->aliasField('education_grade_id'),
                'competency_template_id' => $Competencies->aliasField('id'),
                'competency_template' => $query->func()->concat([
                    $Competencies->aliasField('code') => 'literal',
                    " - ",
                    $Competencies->aliasField('name') => 'literal'
                ])
            ])
            ->innerJoin(
                [$ClassGrades->alias() => $ClassGrades->table()],
                [$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
            )
            ->innerJoin(
                [$Competencies->alias() => $Competencies->table()],
                [
                    $Competencies->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $Competencies->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
                ]
            )
            ->innerJoin(
                [$EducationGrades->alias() => $EducationGrades->table()],
                [$EducationGrades->aliasField('id = ') . $Competencies->aliasField('education_grade_id')]
            )
            ->innerJoin(
                [$EducationProgrammes->alias() => $EducationProgrammes->table()],
                [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
            )
            ->innerJoin(
                [$EducationProgrammes->alias() => $EducationProgrammes->table()],
                [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
            )
            ->innerJoin(
                [$InstitutionClassStudents->alias() => $InstitutionClassStudents->table()],
                [$InstitutionClassStudents->aliasField('institution_class_id = ') . $this->aliasField('id')]
            )
            ->where(['InstitutionClassStudents.student_id' => $studentId])
            ->group([
                $ClassGrades->aliasField('institution_class_id'),
                $Competencies->aliasField('id')
            ])
            ->autoFields(true);

        $extra['options']['order'] = [
            $EducationProgrammes->aliasField('order') => 'asc',
            $EducationGrades->aliasField('order') => 'asc',
            $Competencies->aliasField('code') => 'asc',
            $Competencies->aliasField('name') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        // For filtering all classes and my classes
        $AccessControl = $this->AccessControl;
        $userId = $session->read('Auth.User.id');

        // Academic Periods
        $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
        if (is_null($this->request->query('period'))) {
            // default to current Academic Period
            $this->request->query['period'] = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->queryString('period', $periodOptions);

        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        if (!empty($selectedPeriod)) {
            $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
            
            $InstitutionClassStudentGrade = $InstitutionClassStudents->find()->where([
                'student_id' =>$studentId,
                'academic_period_id' => $selectedPeriod
                ])->first();
            
            // Competencies
            $competencyOptions = $Competencies
                ->find('list')
                ->where([$Competencies->aliasField('academic_period_id') => $selectedPeriod,
                         $Competencies->aliasField('education_grade_id') => $InstitutionClassStudentGrade->education_grade_id
                        ])
                ->toArray();
            $competencyOptions = ['-1' => __('All Competencies')] + $competencyOptions;
            $selectedCompetency = $this->queryString('competency', $competencyOptions);
            $this->controller->set(compact('competencyOptions', 'selectedCompetency'));

            if ($selectedCompetency != '-1') {
                $query->where([$Competencies->aliasField('id') => $selectedCompetency]);
            }
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } else if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $grade = $EducationGrades->get($entity->education_grade_id);

        return $grade->programme_grade_name;
    }

    public function onGetCompetencyTemplate(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');
            $competencyEntity = $CompetencyTemplates->find()
                ->where([
                    $CompetencyTemplates->aliasField('id') => $this->competencyTemplateId,
                    $CompetencyTemplates->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();

            return $competencyEntity->code_name;
        }
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['AcademicPeriods'])
            ->where([
                $this->aliasField('id') => $this->getQueryString('class_id'),
                $this->aliasField('institution_id') => $this->getQueryString('institution_id'),
                $this->aliasField('academic_period_id') => $this->getQueryString('academic_period_id')
            ]);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        unset($extra['toolbarButtons']['edit']);
        $this->setupFields($entity);
    }

    private function getCompetencyPeriodOptions()
    {
        $competencyPeriodOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();
        unset($params['competency_item_id']); // item must be unset if new period is chosen

        $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');
        $results = $CompetencyPeriods->find()
            ->where([
                $CompetencyPeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                $CompetencyPeriods->aliasField('competency_template_id') => $this->competencyTemplateId
            ])
            ->toArray();

        if (!empty($results)) {
            foreach ($results as $period) {
                $params['competency_period_id'] = $period->id;
                $competencyPeriodOptions[$period->id] = [
                    'name' => $period->code_name,
                    'url' => $this->setQueryString($baseUrl, $params)
                ];
            }
        }

        if (!count($competencyPeriodOptions)) {
            // no options
            $params['competency_period_id'] = -1;
            $competencyPeriodOptions[-1] = [
                'name' => __('No Options'),
                'url' => $this->setQueryString($baseUrl, $params)
            ];
        } else {
            // set default period if no period selected yet
            if (is_null($this->competencyPeriodId)) {
                $this->competencyPeriodId = key($competencyPeriodOptions);
            }
        }

        return $competencyPeriodOptions;
    }

    private function getCompetencyItemOptions()
    {
        $competencyItemOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();

        if (!is_null($this->competencyPeriodId)) {
            $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');
            $results = $CompetencyPeriods->find()
                ->contain(['CompetencyItems'])
                ->where([
                    $CompetencyPeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                    $CompetencyPeriods->aliasField('competency_template_id') => $this->competencyTemplateId,
                    $CompetencyPeriods->aliasField('id') => $this->competencyPeriodId
                ])
                ->first();

            if (!empty($results) && $results->has('competency_items') && !empty($results->competency_items)) {
                foreach ($results->competency_items as $item) {
                    $params['competency_item_id'] = $item->id;
                    $competencyItemOptions[$item->id] = [
                        'name' => $item->name,
                        'url' => $this->setQueryString($baseUrl, $params)
                    ];
                }
            }
        }

        if (!count($competencyItemOptions)) {
            // no options
            $params['competency_item_id'] = -1;
            $competencyItemOptions[-1] = [
                'name' => __('No Options'),
                'url' => $this->setQueryString($baseUrl, $params)
            ];
        } else {
            // set default item if no item selected yet
            if (is_null($this->competencyItemId)) {
                $this->competencyItemId = key($competencyItemOptions);
            }
        }

        return $competencyItemOptions;
    }

    private function getStudentOptions()
    {
        $studentOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();

        if (!is_null($this->classId)) {
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $Users = $ClassStudents->Users;
            $StudentStatuses = $ClassStudents->StudentStatuses;

            $results = $ClassStudents->find()
                ->select([
                    $ClassStudents->aliasField('student_id'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name'),
                    $StudentStatuses->aliasField('name')
                ])
                ->matching('Users')
                ->matching('StudentStatuses')
                ->where([
                    $ClassStudents->aliasField('institution_class_id') => $this->classId
                ])
                ->order([$Users->aliasField('first_name'), $Users->aliasField('last_name')])
                ->toArray();

            if (!empty($results)) {
                foreach ($results as $student) {
                    $params['student_id'] = $student->student_id;
                    $studentOptions[$student->student_id] = [
                        'name' => $student->_matchingData['Users']->name_with_id,
                        'status' => $student->_matchingData['StudentStatuses']->name,
                        'url' => $this->setQueryString($baseUrl, $params)
                    ];
                }
            }
        }

        if (!count($studentOptions)) {
            // no options
            $params['student_id'] = -1;
            $studentOptions[-1] = [
                'name' => __('No Options'),
                'status' => '',
                'url' => $this->setQueryString($baseUrl, $params)
            ];
        } else {
            // set default student if no student selected yet
            if (is_null($this->studentId)) {
                $this->studentId = key($studentOptions);
            }
        }

        return $studentOptions;
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $session = $this->request->session();
        if ($this->controller->name == 'Profiles') {
            $studentId = $session->read('Auth.User.id');
        } else {
            $studentId = $session->read('Student.Students.id');
        }
        // set Competency Period filter
        $attr['period_options'] = $this->getCompetencyPeriodOptions();
        $attr['selected_period'] = $this->competencyPeriodId;

        // set Competency Item filter
        $attr['item_options'] = $this->getCompetencyItemOptions();
        $attr['selected_item'] = $this->competencyItemId;

        // set Student filter
        $attr['student_options'] = $this->getStudentOptions();
        $attr['selected_student'] = $studentId;

        $gradingTypes = $this->getCompetencyGradingTypes();
            $i = 0;
            $tableData = array();
            foreach ($attr['item_options'] as $key => $value) {
            // table headers
            
            $tableHeaders[] = $value['name'] . ' ' . __('Criteria');
            $tableHeaders[] = '';
            $tableHeaders[] = __('Comments');
            $CompetencyCriterias = TableRegistry::get('Competency.CompetencyCriterias');
            $CompetencyResults = TableRegistry::get('Institution.InstitutionCompetencyResults');
            $ItemComments = TableRegistry::get('Institution.InstitutionCompetencyItemComments');

            $criteriaResults = $CompetencyCriterias->find()
                ->select([
                    $CompetencyCriterias->aliasField('code'),
                    $CompetencyCriterias->aliasField('name'),
                    $CompetencyCriterias->aliasField('competency_grading_type_id'),
                    $CompetencyResults->aliasField('competency_grading_option_id'),
                    $CompetencyResults->aliasField('comments')
                ])
                ->leftJoin([$CompetencyResults->alias() => $CompetencyResults->table()], [
                    $CompetencyResults->aliasField('academic_period_id = ') . $CompetencyCriterias->aliasField('academic_period_id'),
                    $CompetencyResults->aliasField('competency_template_id = ') . $CompetencyCriterias->aliasField('competency_template_id'),
                    $CompetencyResults->aliasField('competency_item_id = ') . $CompetencyCriterias->aliasField('competency_item_id'),
                    $CompetencyResults->aliasField('competency_criteria_id = ') . $CompetencyCriterias->aliasField('id'),
                    $CompetencyResults->aliasField('institution_id') => $this->institutionId,
                    $CompetencyResults->aliasField('student_id') => $studentId
                ])
                ->where([
                    $CompetencyCriterias->aliasField('academic_period_id') => $this->academicPeriodId,
                    $CompetencyCriterias->aliasField('competency_item_id') => $key,
                    $CompetencyCriterias->aliasField('competency_template_id') => $this->competencyTemplateId
                ])
                ->toArray();

            if (!empty($criteriaResults)) {
                foreach ($criteriaResults as $criteriaObj) {
                    $name = !empty($criteriaObj->code) ? $criteriaObj->code . ' - ' . $criteriaObj->name : $criteriaObj->name;

                    $result = '';
                    if (!empty($criteriaObj->{$CompetencyResults->alias()}['competency_grading_option_id'])) {
                        $gradingTypeId = $criteriaObj->competency_grading_type_id;
                        $gradingOptionId = $criteriaObj->{$CompetencyResults->alias()}['competency_grading_option_id'];
                        $result = $gradingTypes[$gradingTypeId][$gradingOptionId];
                    }

                    $comments = '';
                    if (!is_null($criteriaObj->{$CompetencyResults->alias()}['comments'])) {
                        $comments = $criteriaObj->{$CompetencyResults->alias()}['comments'];
                    }

                    $rowData = [];
                    $rowData[] = $name;
                    
                    $rowData[] = $comments;
                    $rowData[] = $result;

                    // table cells
                    $tableCells[] = $rowData;
                }
            } else {
                // table cells
                $tableCells[] = __('No Competency Criterias');
                $tableCells[] = '';
            }

            $itemComment = $ItemComments->find()
                ->select([$ItemComments->aliasField('comments')])
                ->where([
                    $ItemComments->aliasField('student_id') => $this->studentId,
                    $ItemComments->aliasField('competency_template_id') => $this->competencyTemplateId,
                    $ItemComments->aliasField('competency_period_id') => $this->competencyPeriodId,
                    $ItemComments->aliasField('competency_item_id') => $key,
                    $ItemComments->aliasField('institution_id') => $this->institutionId,
                    $ItemComments->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();

            // table footers
            $overallComment = '';
            if (!empty($itemComment) && $itemComment->comments != '') {
                $overallComment = $itemComment->comments;
            }
            $tableFooters[] = '';
            $tableFooters[] = __('Overall Comment') . ':';
            $tableFooters[] = $overallComment;
            $tableData['tableHeaders'][$i] = $tableHeaders;
            $tableData['tableCells'][$i] = $tableCells;
            $tableData['tableFooters'][$i] = $tableFooters;
            $tableHeaders = array();
            $tableCells = array();
            $tableFooters = array();
            $i++;
        }
            $attr['tableHeaders'] =$tableData['tableHeaders'];
            $attr['tableCells'] = $tableData['tableCells'];
            $attr['tableFooters'] = $tableData['tableFooters'];
        $event->stopPropagation();
        return $event->subject()->renderElement('Student.Students/competency_student', ['attr' => $attr]);
    }

    private function setupFields(Entity $entity)
    {
        $this->classId = $this->getQueryString('class_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');
        $this->competencyTemplateId = $this->getQueryString('competency_template_id');
        $this->competencyPeriodId = $this->getQueryString('competency_period_id') ;
        $this->competencyItemId = $this->getQueryString('competency_item_id');
        $this->studentId = $this->getQueryString('student_id');

        $this->field('name', ['type' => 'readonly']);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_template');
        $this->field('student', [
            'type' => 'custom_criterias'
        ]);
        unset($this->fields['total_male_students']);
        unset($this->fields['total_female_students']);
        $this->setFieldOrder(['name', 'academic_period_id', 'competency_template', 'student']);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $params = [
            'class_id' => $entity->institution_class_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'competency_template_id' => $entity->competency_template_id
        ];

        if (isset($buttons['view']['url'])) {
            $url = $buttons['view']['url'];
            unset($url[1]);
            $buttons['view']['url'] = $this->setQueryString($url, $params);
        }
        return $buttons;
    }

    private function getCompetencyGradingTypes() {
        $CompetencyGradingTypes = TableRegistry::get('Competency.CompetencyGradingTypes');
        $competencyGradingTypeResults = $CompetencyGradingTypes
            ->find()
            ->contain(['GradingOptions'])
            ->toArray();

        $gradingTypes = [];
        foreach ($competencyGradingTypeResults as $gradingTypeEntity) {
            $gradingOptions = [];
            foreach ($gradingTypeEntity->grading_options as $gradingOptionEntity) {
                $gradingOptions[$gradingOptionEntity->id] = $gradingOptionEntity->code_name;
            }
            $gradingTypes[$gradingTypeEntity->id] = $gradingOptions;
        }

        return $gradingTypes;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Competencies');
    }
}
