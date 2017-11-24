<?php
namespace Institution\Model\Table;

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

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
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

        $this->setFieldOrder(['name', 'assessment', 'academic_period_id', 'education_grade', 'subjects', 'male_students', 'female_students']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Competencies = TableRegistry::get('Competency.CompetencyTemplates');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

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

            // Competencies
            $competencyOptions = $Competencies
                ->find('list')
                ->where([$Competencies->aliasField('academic_period_id') => $selectedPeriod])
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function getCompetencyPeriodOptions()
    {
        $competencyPeriodOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();
        $params['competency_item_id'] = -1; // item must be unset to -1 if new period is chosen

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

        // default select option
        $params['competency_period_id'] = -1;
        $defaultSelect[-1]['url'] = $this->setQueryString($baseUrl, $params);
        $defaultSelect[-1]['name'] = count($competencyPeriodOptions) > 0 ? '-- ' . __('Select') . ' --' : '-- ' . __('No Options') . ' --';
        $options = $defaultSelect + $competencyPeriodOptions;

        return $options;
    }

    private function getCompetencyItemOptions()
    {
        $competencyItemOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();

        if ($this->competencyPeriodId != -1) {
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

        // default select option
        $params['competency_item_id'] = -1;
        $defaultSelect[-1]['name'] = count($competencyItemOptions) > 0 ? '-- ' . __('Select') . ' --' : '-- ' . __('No Options') . ' --';
        $defaultSelect[-1]['url'] = $this->setQueryString($baseUrl, $params);
        $options = $defaultSelect + $competencyItemOptions;

        return $options;
    }

    private function getStudentOptions()
    {
        $studentOptions = [];
        $baseUrl = $this->url($this->action, false);
        $params = $this->getQueryString();

        if (!is_null($this->classId)) {
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $Users = $ClassStudents->Users;
            $results = $ClassStudents->find()
                ->select([
                    $ClassStudents->aliasField('student_id'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name')
                ])
                ->matching('Users')
                ->where([$ClassStudents->aliasField('institution_class_id') => $this->classId])
                ->order([$Users->aliasField('first_name'), $Users->aliasField('last_name')])
                ->toArray();

            if (!empty($results)) {
                foreach ($results as $student) {
                    $params['student_id'] = $student->student_id;
                    $studentOptions[$student->student_id] = [
                        'name' => $student->_matchingData['Users']->openemis_no . ' - ' . $student->_matchingData['Users']->name,
                        'url' => $this->setQueryString($baseUrl, $params)
                    ];
                }
            }
        }

        // default select option
        $params['student_id'] = -1;
        $defaultSelect[-1]['name'] = count($studentOptions) > 0 ? '-- ' . __('Select') . ' --' : '-- ' . __('No Options') . ' --';
        $defaultSelect[-1]['url'] = $this->setQueryString($baseUrl, $params);
        $options = $defaultSelect + $studentOptions;

        return $options;
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        // set Competency Period filter
        $attr['period_options'] = $this->getCompetencyPeriodOptions();
        $attr['selected_period'] = $this->competencyPeriodId;

        // set Competency Item filter
        $attr['item_options'] = $this->getCompetencyItemOptions();
        $attr['selected_item'] = $this->competencyItemId;

        // set Student filter
        $attr['student_options'] = $this->getStudentOptions();
        $attr['selected_student'] = $this->studentId;

        $gradingTypes = $this->getCompetencyGradingTypes();

        $value = '';
        $form = $event->subject()->Form;
        $fieldPrefix = $attr['model'] . '.institution_competency_results';

        $tableHeaders = [];
        $tableCells = [];
        $tableFooters = [];

        // Build table header
        $tableHeaders[] = __('Criteria');
        $tableHeaders[] = __('Result');

        // Build table footer for comments
        $tableFooters[] = __('Comments');

        if ($this->competencyPeriodId != -1 && $this->competencyItemId != -1 && $this->studentId != -1) {
            $CompetencyCriterias = TableRegistry::get('Competency.CompetencyCriterias');
            $CompetencyResults = TableRegistry::get('Institution.InstitutionCompetencyResults');
            $ItemComments = TableRegistry::get('Institution.InstitutionCompetencyItemComments');

            $criteriaResults = $CompetencyCriterias->find()
                ->select([
                    $CompetencyCriterias->aliasField('code'),
                    $CompetencyCriterias->aliasField('name'),
                    $CompetencyCriterias->aliasField('competency_grading_type_id'),
                    $CompetencyResults->aliasField('competency_grading_option_id')
                ])
                ->leftJoin([$CompetencyResults->alias() => $CompetencyResults->table()], [
                    $CompetencyResults->aliasField('academic_period_id = ') . $CompetencyCriterias->aliasField('academic_period_id'),
                    $CompetencyResults->aliasField('competency_template_id = ') . $CompetencyCriterias->aliasField('competency_template_id'),
                    $CompetencyResults->aliasField('competency_item_id = ') . $CompetencyCriterias->aliasField('competency_item_id'),
                    $CompetencyResults->aliasField('competency_criteria_id = ') . $CompetencyCriterias->aliasField('id'),
                    $CompetencyResults->aliasField('competency_period_id') => $this->competencyPeriodId,
                    $CompetencyResults->aliasField('institution_id') => $this->institutionId,
                    $CompetencyResults->aliasField('student_id') => $this->studentId
                ])
                ->where([
                    $CompetencyCriterias->aliasField('academic_period_id') => $this->academicPeriodId,
                    $CompetencyCriterias->aliasField('competency_item_id') => $this->competencyItemId,
                    $CompetencyCriterias->aliasField('competency_template_id') => $this->competencyTemplateId
                ])
                ->toArray();

            if (!empty($criteriaResults)) {
                foreach ($criteriaResults as $criteriaObj) {
                    if (!empty($criteriaObj->code)) {
                        $name = $criteriaObj->code . ' - ' . $criteriaObj->name;
                    } else {
                        $name = $criteriaObj->name;
                    }

                    $result = '';
                    if (!empty($criteriaObj->{$CompetencyResults->alias()}['competency_grading_option_id'])) {
                        $gradingTypeId = $criteriaObj->competency_grading_type_id;
                        $gradingOptionId = $criteriaObj->{$CompetencyResults->alias()}['competency_grading_option_id'];
                        $result = $gradingTypes[$gradingTypeId][$gradingOptionId];
                    }

                    $rowData = [];
                    $rowData[] = $name;
                    $rowData[] = $result;
                    $tableCells[] = $rowData;
                }
            } else {
                $tableCells[] = __('No Criterias');
            }

            $itemComment = $ItemComments->find()
                ->select([$ItemComments->aliasField('comments')])
                ->where([
                    $ItemComments->aliasField('student_id') => $this->studentId,
                    $ItemComments->aliasField('competency_template_id') => $this->competencyTemplateId,
                    $ItemComments->aliasField('competency_period_id') => $this->competencyPeriodId,
                    $ItemComments->aliasField('competency_item_id') => $this->competencyItemId,
                    $ItemComments->aliasField('institution_id') => $this->institutionId,
                    $ItemComments->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();

            $comments = '';
            if (!empty($itemComment) && !empty($itemComment->comments)) {
                $comments = $itemComment->comments;
            }
            $tableFooters[] = $comments;
        } else {
            $tableCells[] = __('No Competency Item or Student selected');
            $tableFooters[] = '';
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['tableFooters'] = $tableFooters;

        $value = $event->subject()->renderElement('Institution.StudentCompetencies/students', ['attr' => $attr]);
        $event->stopPropagation();
        return $value;
    }

    private function setupFields(Entity $entity)
    {
        $this->classId = $this->getQueryString('class_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');
        $this->competencyTemplateId = $this->getQueryString('competency_template_id');
        $this->competencyPeriodId = !is_null($this->getQueryString('competency_period_id')) ? $this->getQueryString('competency_period_id') : -1;
        $this->competencyItemId = !is_null($this->getQueryString('competency_item_id')) ? $this->getQueryString('competency_item_id') : -1;
        $this->studentId = !is_null($this->getQueryString('student_id')) ? $this->getQueryString('student_id') : -1;

        $this->field('name', ['type' => 'readonly']);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_template');
        $this->field('students', [
            'type' => 'custom_criterias'
        ]);
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

        if (isset($buttons['edit']['url'])) {
            $url = $buttons['edit']['url'];
            unset($url[1]);
            $buttons['edit']['url'] = $this->setQueryString($url, $params);
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
}
