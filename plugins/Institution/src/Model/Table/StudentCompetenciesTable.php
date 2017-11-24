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
    private $competencyCriteriaCount = 0;

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
            ->autoFields(true)
            ;

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

    public function onGetCustomCompetencyPeriodElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $value = '';
        $form = $event->subject()->Form;

        $competencyPeriodCount = 0;
        $competencyPeriodOptions = [];

        $todayDate = Time::now();
        $today = $todayDate->format('Y-m-d');

        $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');
        $competencyPeriodResults = $CompetencyPeriods
            ->find()
            ->where([
                $CompetencyPeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                $CompetencyPeriods->aliasField('competency_template_id') => $this->competencyTemplateId
            ])
            ->all();

        if (!$competencyPeriodResults->isEmpty()) {
            $competencyPeriodCount = $competencyPeriodResults->count();

            $params = $this->getQueryString();
            // must reset
            unset($params['competency_item_id']);
            $this->competencyPeriodId = $this->getQueryString('competency_period_id');

            foreach ($competencyPeriodResults as $periodKey => $periodObj) {
                $periodData = [];
                $periodData['id'] = $periodObj->id;
                $periodData['code_name'] = $periodObj->code_name;

                $params['competency_period_id'] = $periodObj->id;
                $baseUrl = $this->url($action, false);
                $url = $this->setQueryString($baseUrl, $params);
                $periodData['url'] = $url;

                if (is_null($this->competencyPeriodId) || $this->competencyPeriodId == $periodObj->id) {
                    $periodData['checked'] = true;
                    $this->competencyPeriodId = $periodObj->id;
                } else {
                    $periodData['checked'] = false;
                }

                $competencyPeriodOptions[] = $periodData;
            }
        }

        $attr['competencyPeriodCount'] = $competencyPeriodCount;
        $attr['competencyPeriodOptions'] = $competencyPeriodOptions;

        if ($action == 'view' || $action == 'edit') {
            $value = $event->subject()->renderElement('Institution.StudentCompetencies/competency_period', ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function onGetCustomCompetencyItemElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $value = '';
        $form = $event->subject()->Form;

        $competencyItemCount = 0;
        $competencyItemOptions = [];

        if (!is_null($this->competencyPeriodId)) {
            $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');
            $competencyPeriodEntity = $CompetencyPeriods
                ->find()
                ->contain(['CompetencyItems'])
                ->where([
                    $CompetencyPeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                    $CompetencyPeriods->aliasField('competency_template_id') => $this->competencyTemplateId,
                    $CompetencyPeriods->aliasField('id') => $this->competencyPeriodId
                ])
                ->first();

            if ($competencyPeriodEntity->has('competency_items')) {
                $params = $this->getQueryString();
                $this->competencyItemId = $this->getQueryString('competency_item_id');

                foreach ($competencyPeriodEntity->competency_items as $itemKey => $itemObj) {
                    $competencyItemCount++;

                    $itemData = [];
                    $itemData['id'] = $itemObj->id;
                    $itemData['name'] = $itemObj->name;

                    $params['competency_period_id'] = $this->competencyPeriodId;
                    $params['competency_item_id'] = $itemObj->id;
                    $baseUrl = $this->url($action, false);
                    $url = $this->setQueryString($baseUrl, $params);
                    $itemData['url'] = $url;

                    if (is_null($this->competencyItemId) || $this->competencyItemId == $itemObj->id) {
                        $itemData['checked'] = true;
                        $this->competencyItemId = $itemObj->id;
                    } else {
                        $itemData['checked'] = false;
                    }

                    $competencyItemOptions[] = $itemData;
                }
            }
        }

        $attr['competencyItemCount'] = $competencyItemCount;
        $attr['competencyItemOptions'] = $competencyItemOptions;

        if ($action == 'view' || $action == 'edit') {
            $value = $event->subject()->renderElement('Institution.StudentCompetencies/competency_item', ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function onGetCustomStudentsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $gradingTypes = $this->getCompetencyGradingTypes();

        $value = '';
        $form = $event->subject()->Form;
        $fieldPrefix = $attr['model'] . '.institution_competency_results';

        $tableHeaders = [];
        $tableCells = [];
        $colOffset = 0;

        // Build table header
        $tableHeaders[] = __('OpenEMIS ID');
        $tableHeaders[] = __('Student Name');
        $tableHeaders[] = __('Student Status');

        $competencyItemEntity = null;
        if (!is_null($this->competencyItemId)) {
            $tableHeaders[] = __('Comments');
            $colOffset = 4; // 0 -> OpenEMIS ID, 1 -> Student Name, 2 -> Student Status, 3 -> Comments

            $CompetencyItems = TableRegistry::get('Competency.CompetencyItems');
            $competencyItemEntity = $CompetencyItems
                ->find()
                ->contain(['Criterias'])
                ->where([
                    $CompetencyItems->aliasField('academic_period_id') => $this->academicPeriodId,
                    $CompetencyItems->aliasField('competency_template_id') => $this->competencyTemplateId,
                    $CompetencyItems->aliasField('id') => $this->competencyItemId
                ])
                ->first();

            if ($competencyItemEntity->has('criterias')) {
                foreach ($competencyItemEntity->criterias as $colKey => $criteriaObj) {
                    $this->competencyCriteriaCount++;
                    if (strlen($criteriaObj['code']) > 0) {
                        $displayHeader = $criteriaObj['code'];
                        $displayHeader .= '&nbsp;&nbsp;<i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="top" uib-tooltip="'.$criteriaObj['name'].'" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
                    } else {
                        $displayHeader = $criteriaObj['name'];
                    }

                    $tableHeaders[$colKey + $colOffset] = $displayHeader;
                }
            }
        }

        if ($action == 'edit' && $this->competencyCriteriaCount == 0) {
            $this->Alert->warning('StudentCompetencies.noCriterias');
        }

        if (!is_null($this->classId)) {
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $Users = $ClassStudents->Users;
            $StudentStatuses = $ClassStudents->StudentStatuses;
            $CompetencyResults = TableRegistry::get('Institution.InstitutionCompetencyResults');
            $ItemComments = TableRegistry::get('Institution.InstitutionCompetencyItemComments');
            $students = $ClassStudents
                ->find()
                ->select([
                    $CompetencyResults->aliasField('competency_grading_option_id'),
                    $CompetencyResults->aliasField('competency_criteria_id'),
                    $ItemComments->aliasField('comments'),
                    $ClassStudents->aliasField('student_id'),
                    $ClassStudents->aliasField('student_status_id'),
                    $StudentStatuses->aliasField('name'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name')
                ])
                ->matching('Users')
                ->matching('StudentStatuses')
                ->leftJoin(
                    [$ItemComments->alias() => $ItemComments->table()],
                    [
                        $ItemComments->aliasField('student_id = ') . $ClassStudents->aliasField('student_id'),
                        $ItemComments->aliasField('institution_id') => $this->institutionId,
                        $ItemComments->aliasField('academic_period_id') => $this->academicPeriodId,
                        $ItemComments->aliasField('competency_template_id') => $this->competencyTemplateId,
                        $ItemComments->aliasField('competency_period_id') => $this->competencyPeriodId,
                        $ItemComments->aliasField('competency_item_id') => $this->competencyItemId
                    ]
                )
                ->leftJoin(
                    [$CompetencyResults->alias() => $CompetencyResults->table()],
                    [
                        $CompetencyResults->aliasField('student_id = ') . $ClassStudents->aliasField('student_id'),
                        $CompetencyResults->aliasField('institution_id') => $this->institutionId,
                        $CompetencyResults->aliasField('academic_period_id') => $this->academicPeriodId,
                        $CompetencyResults->aliasField('competency_template_id') => $this->competencyTemplateId,
                        $CompetencyResults->aliasField('competency_period_id') => $this->competencyPeriodId,
                        $CompetencyResults->aliasField('competency_item_id') => $this->competencyItemId
                    ]
                )
                ->where([$ClassStudents->aliasField('institution_class_id') => $this->classId])
                ->group([
                    $ClassStudents->aliasField('student_id'),
                    $CompetencyResults->aliasField('competency_criteria_id')
                ])
                ->order([
                    $Users->aliasField('first_name'),
                    $Users->aliasField('last_name')
                ])
                ->toArray();

            $studentId = null;
            $currentStudentId = null;
            $answerObj = null;
            $rowData = [];
            $rowInput = "";
            $rowCount = 0;

            foreach ($students as $rowKey => $studentObj) {
                $currentStudentId = $studentObj->student_id;
                $savedGradingOptionId = $studentObj->{$CompetencyResults->alias()}['competency_grading_option_id'];
                $savedCriteriaId = $studentObj->{$CompetencyResults->alias()}['competency_criteria_id'];
                $savedItemComments = $studentObj->{$ItemComments->alias()}['comments'];
                if (!is_null($savedCriteriaId) && !is_null($savedGradingOptionId)) {
                    $answerObj[$currentStudentId][$savedCriteriaId] = $savedGradingOptionId;
                }
                $comments = "";
                if (!is_null($savedItemComments)) {
                    $comments = $savedItemComments;
                }

                $userObj = $studentObj->_matchingData['Users'];
                $studentStatusObj = $studentObj->_matchingData['StudentStatuses'];

                if ($studentId != $currentStudentId) {
                    if ($studentId != null) {
                        $tableCells[$rowCount] = $rowData;
                        $rowCount++;
                    }

                    $rowPrefix = "$fieldPrefix.$currentStudentId";

                    $rowData = [];
                    $rowInput = "";

                    if ($action == 'view') {
                        // $rowData[] = $event->subject->Html->link($userObj->openemis_no, [
                        //     'plugin' => 'Institution',
                        //     'controller' => 'Institutions',
                        //     'action' => 'StudentUser',
                        //     'view',
                        //     $this->paramsEncode(['id' => $userObj->id])
                        // ]);
                        $rowData[] = $userObj->openemis_no;
                        $rowData[] = $userObj->name;
                        $rowData[] = $studentStatusObj->name;
                    } else if ($action == 'edit') {
                        $rowData[] = $userObj->openemis_no . $rowInput;
                        $rowData[] = $userObj->name;
                        $rowData[] = $studentStatusObj->name;
                    }
                    if (!is_null($competencyItemEntity)) {
                        $rowData[] = $comments;
                    }

                    $studentId = $currentStudentId;
                }

                if (!is_null($competencyItemEntity) && $competencyItemEntity->has('criterias')) {
                    foreach ($competencyItemEntity->criterias as $colKey => $criteriaObj) {
                        $competencyCriteriaId = $criteriaObj['id'];

                        $cellPrefix = "$rowPrefix.$competencyCriteriaId";
                        $cellInput = "";
                        $cellValue = "";

                        $cellOptions = ['label' => false, 'value' => ''];
                        $answerValue = null;
                        if (isset($answerObj[$currentStudentId][$competencyCriteriaId]) && !is_null($answerObj[$currentStudentId][$competencyCriteriaId])) {
                            $answerValue = $answerObj[$currentStudentId][$competencyCriteriaId];
                        }
                        $dropdownOptions = $gradingTypes[$criteriaObj['competency_grading_type_id']];
                        $dropdownDefault = null;

                        $dropdownOptions = ['' => '-- '.__('Select').' --'] + $dropdownOptions;

                        // for edit
                        $cellOptions['type'] = 'select';
                        $cellOptions['default'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                        $cellOptions['value'] = !is_null($answerValue) ? $answerValue : $dropdownDefault;
                        $cellOptions['options'] = $dropdownOptions;

                        // for view
                        $cellValue = !is_null($answerValue) ? $dropdownOptions[$answerValue] : '';

                        $cellInput .= $form->input($cellPrefix, $cellOptions);

                        if ($action == 'view') {
                            $rowData[$colKey+$colOffset] = $cellValue;
                        } else if ($action == 'edit') {
                            $rowData[$colKey+$colOffset] = $cellInput;
                        }
                    }
                }
            }

            if (!empty($rowData)) {
                $tableCells[$rowCount] = $rowData;
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view' || $action == 'edit') {
            $value = $event->subject()->renderElement('Institution.StudentCompetencies/students', ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    private function setupFields(Entity $entity)
    {
        $this->classId = $this->getQueryString('class_id');
        $this->institutionId = $this->getQueryString('institution_id');
        $this->academicPeriodId = $this->getQueryString('academic_period_id');
        $this->competencyTemplateId = $this->getQueryString('competency_template_id');
        $this->competencyPeriodId = null;
        $this->competencyItemId = null;

        $this->field('name', ['type' => 'readonly']);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('competency_template');
        $this->field('competency_period', [
            'type' => 'custom_competency_period'
        ]);
        $this->field('competency_item', [
            'type' => 'custom_competency_item'
        ]);
        $this->field('students', [
            'type' => 'custom_students'
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
