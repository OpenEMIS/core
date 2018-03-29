<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class StudentOutcomesTable extends ControllerActionTable
{
    private $classId = null;
    private $institutionId = null;
    private $academicPeriodId = null;
    private $outcomeTemplateId = null;
    private $gradeId = null;
    private $outcomePeriodId = null;
    private $subjectId = null;
    private $studentId = null;

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('SecondaryStaff', ['className' => 'User.Users', 'foreignKey' => 'secondary_staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents']);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id'
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id'
        ]);
        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportOutcomeResults']);

        $this->toggle('add', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $params = [
            'class_id' => $entity->institution_class_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'outcome_template_id' => $entity->outcome_template_id,
            'education_grade_id' => $entity->education_grade_id
        ];

        if (isset($buttons['view']['url'])) {
            $url = $buttons['view']['url'];
            $buttons['view']['url'] = $this->setQueryString($url, $params);
        }

        if (isset($buttons['edit']['url'])) {
            $url = $buttons['edit']['url'];
            unset($url[1]);
            $buttons['edit']['url'] = $this->setQueryString($url, $params);
        }

        return $buttons;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('class_number', ['type' => 'hidden']);
        $this->field('staff_id', ['type' => 'hidden']);
        $this->field('secondary_staff_id', ['type' => 'hidden']);
        $this->field('institution_shift_id', ['type' => 'hidden']);
        $this->field('modified_user_id', ['type' => 'hidden']);
        $this->field('modified', ['type' => 'hidden']);
        $this->field('created_user_id', ['type' => 'hidden']);
        $this->field('created', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('outcome_template');
        $this->field('education_grade');
        $this->setFieldOrder(['name', 'academic_period_id', 'education_grade', 'outcome_template', 'total_male_students', 'total_female_students']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $Outcomes = TableRegistry::get('Outcome.OutcomeTemplates');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $query
            ->select([
                'institution_class_id' => $this->aliasField('id'),
                'education_grade_id' => $Outcomes->aliasField('education_grade_id'),
                'outcome_template_id' => $Outcomes->aliasField('id'),
                'outcome_template' => $query->func()->concat([
                    $Outcomes->aliasField('code') => 'literal',
                    " - ",
                    $Outcomes->aliasField('name') => 'literal',
                ])
            ])
            ->innerJoin([$this->ClassGrades->alias() => $this->ClassGrades->table()], [
                $this->ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')
            ])
            ->innerJoin([$Outcomes->alias() => $Outcomes->table()], [
                $Outcomes->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                $Outcomes->aliasField('education_grade_id = ') . $this->ClassGrades->aliasField('education_grade_id')
            ])
            ->innerJoin([$this->EducationGrades->alias() => $this->EducationGrades->table()], [
                $this->EducationGrades->aliasField('id = ') . $Outcomes->aliasField('education_grade_id')
            ])
            ->innerJoin([$EducationProgrammes->alias() => $EducationProgrammes->table()], [
                $EducationProgrammes->aliasField('id = ') . $this->EducationGrades->aliasField('education_programme_id')
            ])
            ->group([
                $this->aliasField('id'),
                $Outcomes->aliasField('id')
            ])
            ->autoFields(true);

        $extra['options']['order'] = [
            $EducationProgrammes->aliasField('order') => 'asc',
            $this->EducationGrades->aliasField('order') => 'asc',
            $Outcomes->aliasField('code') => 'asc',
            $Outcomes->aliasField('name') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        // For filtering all classes and my classes
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $AccessControl = $this->AccessControl;
        $userId = $session->read('Auth.User.id');
        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
        if (!$AccessControl->isAdmin()) {
            if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
                $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                if (!$classPermission && !$subjectPermission) {
                    $query->where(['1 = 0'], [], true);
                } else {
                    $query->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                        'InstitutionClasses.id = '.$this->ClassGrades->aliasField('institution_class_id'),
                        ])
                        ;

                    // If only class permission is available but no subject permission available
                    if ($classPermission && !$subjectPermission) {
                        $query->where([
                                'OR' => [
                                    ['InstitutionClasses.staff_id' => $userId],
                                    ['InstitutionClasses.secondary_staff_id' => $userId]
                                ]
                            ]);
                    } else {
                        $query
                            ->innerJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                                'InstitutionClassSubjects.institution_class_id = InstitutionClasses.id',
                                'InstitutionClassSubjects.status = 1'
                            ])
                            ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                            ]);

                        // If both class and subject permission is available
                        if ($classPermission && $subjectPermission) {
                            $query->where([
                                'OR' => [
                                    ['InstitutionClasses.staff_id' => $userId],
                                    ['InstitutionClasses.secondary_staff_id' => $userId],
                                    ['InstitutionSubjectStaff.staff_id' => $userId]
                                ]
                            ]);
                        }
                        // If only subject permission is available
                        else {
                            $query->where(['InstitutionSubjectStaff.staff_id' => $userId]);
                        }
                    }
                }
            }
        }

        // Academic period filter
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
        // End

        // Outcome template filter
        $educationGrades = $InstitutionGrades->find()
            ->where([$InstitutionGrades->aliasField('institution_id') => $institutionId])
            ->extract('education_grade_id')
            ->toArray();

        $outcomeOptions = [];
        if (!empty($educationGrades)) {
            $outcomeOptions = $Outcomes
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->where([
                    $Outcomes->aliasField('academic_period_id') => $selectedPeriod,
                    $Outcomes->aliasField('education_grade_id IN') => $educationGrades
                ])
                ->order([$Outcomes->aliasField('code')])
                ->toArray();

            if (!empty($outcomeOptions)) {
                $outcomeOptions = ['0' => '-- '.__('All Outcomes').' --'] + $outcomeOptions;
            }
        }

        $selectedOutcome = !is_null($this->request->query('outcome')) ? $this->request->query('outcome') : 0;
        $this->controller->set(compact('outcomeOptions', 'selectedOutcome'));
        if (!empty($selectedOutcome)) {
            $query->where([$Outcomes->aliasField('id') => $selectedOutcome]);
        }
        // End

        $extra['elements']['controls'] = ['name' => 'Institution.StudentOutcomes/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } elseif ($field == 'total_male_students') {
            return  __('Male Students');
        } elseif ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity)
    {
        $grade = $this->EducationGrades->get($entity->education_grade_id);
        return $grade->programme_grade_name;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // from action button
        $this->classId = $this->getQueryString('class_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');
        $this->outcomeTemplateId = $this->getQueryString('outcome_template_id');
        $this->gradeId = $this->getQueryString('education_grade_id');
        // filters
        $this->outcomePeriodId = $this->getQueryString('outcome_period_id') ;
        $this->subjectId = $this->getQueryString('education_subject_id');
        $this->studentId = $this->getQueryString('student_id');

        $this->field('outcome_template');
        $this->field('student', ['type' => 'custom_criterias']);

        $this->setFieldOrder(['name', 'academic_period_id', 'outcome_template', 'total_male_students', 'total_female_students', 'student']);
    }

    public function onGetOutcomeTemplate(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
            $template = $OutcomeTemplates->find()
                ->where([
                    $OutcomeTemplates->aliasField('id') => $this->outcomeTemplateId,
                    $OutcomeTemplates->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();
            return $template->code_name;
        }
    }

    private function getOutcomePeriodOptions()
    {
        $outcomePeriodOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();

        if (!is_null($this->academicPeriodId) && !is_null($this->outcomeTemplateId)) {
            $OutcomePeriods = TableRegistry::get('Outcome.OutcomePeriods');
            $results = $OutcomePeriods->find()
                ->where([
                    $OutcomePeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                    $OutcomePeriods->aliasField('outcome_template_id') => $this->outcomeTemplateId
                ])
                ->order([$OutcomePeriods->aliasField('start_date')])
                ->toArray();

            if (!empty($results)) {
                foreach ($results as $period) {
                    $params['outcome_period_id'] = $period->id;
                    $outcomePeriodOptions[$period->id] = [
                        'name' => $period->code_name,
                        'url' => $this->setQueryString($baseUrl, $params)
                    ];
                }
            }
        }

        if (!count($outcomePeriodOptions)) {
            // no options
            $params['outcome_period_id'] = -1;
            $outcomePeriodOptions[-1] = [
                'name' => __('No Options'),
                'url' => $this->setQueryString($baseUrl, $params)
            ];
        } else {
            // set default period if no period selected yet
            if (is_null($this->outcomePeriodId)) {
                $this->outcomePeriodId = key($outcomePeriodOptions);
            }
        }

        return $outcomePeriodOptions;
    }

    private function getSubjectOptions()
    {
        $subjectOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();
        $session = $this->request->session();
        $AccessControl = $this->AccessControl;
        
        $userId = $session->read('Auth.User.id');
        $gradeId = $this->gradeId;
        $academicPeriodId = $this->academicPeriodId;
        $institutionId = $this->institutionId;
        $classId = $this->classId;

        if (!is_null($gradeId)) {
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
            $subjectList = $InstitutionSubjects
                ->find()
                ->find('byAccess', [
                    'userId' => $userId,
                    'accessControl' => $AccessControl,
                    'controller' => $this->controller
                ])
                ->contain(['EducationGrades', 'EducationSubjects'])
                ->matching('ClassSubjects', function ($q) use ($classId) {
                    return $q->where(['ClassSubjects.institution_class_id' => $classId]);
                })
                ->where([
                    'EducationGrades.id' => $gradeId,
                    'InstitutionSubjects.institution_id' => $institutionId,
                    'InstitutionSubjects.academic_period_id' => $academicPeriodId,
                ])
                ->order('EducationSubjects.order')
                ->toArray();

            if (!empty($subjectList)) {
                foreach ($subjectList as $subject) {
                    $params['education_subject_id'] = $subject->education_subject->id;
                    $subjectOptions[$subject->education_subject->id] = [
                        'name' => $subject->education_subject->code_name,
                        'url' => $this->setQueryString($baseUrl, $params)
                    ];
                }
            }
        }

        if (!count($subjectOptions)) {
            // no options
            $params['education_subject_id'] = -1;
            $subjectOptions[-1] = [
                'name' => __('No Options'),
                'url' => $this->setQueryString($baseUrl, $params)
            ];
        } else {
            // set default item if no item selected yet
            if (is_null($this->subjectId)) {
                $this->subjectId = key($subjectOptions);
            }
        }

        return $subjectOptions;
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
        // set Outcome Period filter
        $attr['period_options'] = $this->getOutcomePeriodOptions();
        $attr['selected_period'] = $this->outcomePeriodId;

        // set Subject filter
        $attr['subject_options'] = $this->getSubjectOptions();
        $attr['selected_subject'] = $this->subjectId;

        // set Student filter
        $attr['student_options'] = $this->getStudentOptions();
        $attr['selected_student'] = $this->studentId;

        $gradingTypes = $this->getOutcomeGradingTypes();

        $tableHeaders = [];
        $tableCells = [];
        $tableFooters = [];

        if (!is_null($this->outcomePeriodId) && !is_null($this->subjectId) && !is_null($this->studentId)) {
            // table headers
            $tableHeaders[] = $attr['subject_options'][$this->subjectId]['name'] . ' ' . __('Criteria');
            $tableHeaders[] = $attr['student_options'][$this->studentId]['name'];

            $OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
            $OutcomeResults = TableRegistry::get('Institution.InstitutionOutcomeResults');
            $SubjectComments = TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');

            $criteriaResults = $OutcomeCriterias->find()
                ->select([
                    $OutcomeCriterias->aliasField('code'),
                    $OutcomeCriterias->aliasField('name'),
                    $OutcomeCriterias->aliasField('outcome_grading_type_id'),
                    $OutcomeResults->aliasField('outcome_grading_option_id')
                ])
                ->leftJoin([$OutcomeResults->alias() => $OutcomeResults->table()], [
                    $OutcomeResults->aliasField('outcome_template_id = ') . $OutcomeCriterias->aliasField('outcome_template_id'),
                    $OutcomeResults->aliasField('education_grade_id = ') . $OutcomeCriterias->aliasField('education_grade_id'),
                    $OutcomeResults->aliasField('education_subject_id = ') . $OutcomeCriterias->aliasField('education_subject_id'),
                    $OutcomeResults->aliasField('outcome_criteria_id = ') . $OutcomeCriterias->aliasField('id'),
                    $OutcomeResults->aliasField('academic_period_id = ') . $OutcomeCriterias->aliasField('academic_period_id'),
                    $OutcomeResults->aliasField('student_id') => $this->studentId,
                    $OutcomeResults->aliasField('outcome_period_id') => $this->outcomePeriodId,
                    $OutcomeResults->aliasField('institution_id') => $this->institutionId
                ])
                ->where([
                    $OutcomeCriterias->aliasField('academic_period_id') => $this->academicPeriodId,
                    $OutcomeCriterias->aliasField('outcome_template_id') => $this->outcomeTemplateId,
                    $OutcomeCriterias->aliasField('education_grade_id') => $this->gradeId,
                    $OutcomeCriterias->aliasField('education_subject_id') => $this->subjectId
                ])
                ->toArray();

            if (!empty($criteriaResults)) {
                foreach ($criteriaResults as $criteriaObj) {
                    $result = '';
                    if (!empty($criteriaObj->{$OutcomeResults->alias()}['outcome_grading_option_id'])) {
                        $gradingTypeId = $criteriaObj->outcome_grading_type_id;
                        $gradingOptionId = $criteriaObj->{$OutcomeResults->alias()}['outcome_grading_option_id'];
                        $result = $gradingTypes[$gradingTypeId][$gradingOptionId];
                    }

                    $rowData = [];
                    $rowData[] = $criteriaObj->code_name;
                    $rowData[] = $result;

                    // table cells
                    $tableCells[] = $rowData;
                }
            } else {
                // table cells
                $tableCells[] = __('No Outcome Criterias');
                $tableCells[] = '';
            }

            $subjectComment = $SubjectComments->find()
                ->select([$SubjectComments->aliasField('comments')])
                ->where([
                    $SubjectComments->aliasField('student_id') => $this->studentId,
                    $SubjectComments->aliasField('outcome_template_id') => $this->outcomeTemplateId,
                    $SubjectComments->aliasField('outcome_period_id') => $this->outcomePeriodId,
                    $SubjectComments->aliasField('education_grade_id') => $this->gradeId,
                    $SubjectComments->aliasField('education_subject_id') => $this->subjectId,
                    $SubjectComments->aliasField('institution_id') => $this->institutionId,
                    $SubjectComments->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();

            // table footers
            $comments = '';
            if (!empty($subjectComment) && $subjectComment->comments != '') {
                $comments = $subjectComment->comments;
            }
            $tableFooters[] = __('Comments');
            $tableFooters[] = $comments;
        } else {
            // table headers
            $tableHeaders[] = __('Outcome Criteria');
            $tableHeaders[] = __('Result');

            // table cells
            $tableCells[] = __('No Outcome Period, Subject or Student selected');
            $tableCells[] = '';
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['tableFooters'] = $tableFooters;

        $event->stopPropagation();
        return $event->subject()->renderElement('Institution.StudentOutcomes/outcome_criterias', ['attr' => $attr]);
    }
 
    private function getOutcomeGradingTypes()
    {
        $OutcomeGradingTypes = TableRegistry::get('Outcome.OutcomeGradingTypes');
        $results = $OutcomeGradingTypes->find()
            ->contain('GradingOptions')
            ->toArray();

        $gradingTypes = [];
        foreach ($results as $gradingTypeEntity) {
            $gradingOptions = [];
            foreach ($gradingTypeEntity->grading_options as $gradingOptionEntity) {
                $gradingOptions[$gradingOptionEntity->id] = $gradingOptionEntity->code_name;
            }

            $gradingTypes[$gradingTypeEntity->id] = $gradingOptions;
        }
        return $gradingTypes;
    }
}
