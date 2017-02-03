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
        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['type' => 'hidden']);
        $this->field('institution_shift_id', ['type' => 'hidden']);
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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AcademicPeriods']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        return $this->processSave($entity, $data);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        return $this->processSave($entity, $data);
    }

    private function processSave(Entity $entity, ArrayObject $data)
    {
        $model = $this;
        $process = function($model, $entity) use ($data) {
            if (!empty($entity->competency_period) && !empty($entity->competency_item)) {
                $StudentCompetencyResults = TableRegistry::get('Institution.StudentCompetencyResults');

                $competencyTemplateId = $data[$model->alias()]['competency_template'];
                $competencyPeriodId = $data[$model->alias()]['competency_period'];
                $competencyItemId = $data[$model->alias()]['competency_item'];
                $institutionId = $data[$model->alias()]['institution_id'];
                $academicPeriodId = $data[$model->alias()]['academic_period_id'];

                if (array_key_exists('student_competency_results', $data[$model->alias()]) && !empty($data[$model->alias()]['student_competency_results'])) {
                    foreach ($data[$model->alias()]['student_competency_results'] as $studentId => $criteriaResults) {
                        foreach ($criteriaResults as $criteriaKey => $criteriaValue) {
                            $studentData = [
                                // 'competency_grading_option_id' => NULL,
                                'student_id' => $studentId,
                                'competency_template_id' => $competencyTemplateId,
                                'competency_period_id' => $competencyPeriodId,
                                'competency_item_id' => $competencyItemId,
                                'competency_criteria_id' => $criteriaKey,
                                'institution_id' => $institutionId,
                                'academic_period_id' => $academicPeriodId
                            ];

                            if (!empty($criteriaValue)) {
                                $studentData['competency_grading_option_id'] = $criteriaValue;
                                $studentEntity = $StudentCompetencyResults->newEntity($studentData);
                                $StudentCompetencyResults->save($studentEntity);
                            } else {
                                $studentEntity = $StudentCompetencyResults->newEntity($studentData);
                                $StudentCompetencyResults->delete($studentEntity);
                            }
                        }
                    }
                } else {
                    // delete all
                }

                return true;
            } else {
                return false;
            }
        };

        return $process;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }

        return $attr;
    }

    public function onUpdateFieldCompetencyTemplate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');
            $competencyEntity = $CompetencyTemplates->find()
                ->where([
                    $CompetencyTemplates->aliasField('id') => $this->competencyTemplateId,
                    $CompetencyTemplates->aliasField('academic_period_id') => $this->academicPeriodId
                ])
                ->first();

            $attr['type'] = 'readonly';
            $attr['value'] = $competencyEntity->id;
            $attr['attr']['value'] = $competencyEntity->code_name;
        }

        return $attr;
    }

    public function onGetCustomCompetencyPeriodElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $value = '';
        $form = $event->subject()->Form;

        $tableHeaders = [];
        $tableCells = [];

        $tableHeaders[] = __('Period');

        $todayDate = Time::now();
        $today = $todayDate->format('Y-m-d');

        $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');
        $competencyPeriodResults = $CompetencyPeriods
            ->find()
            ->where([
                $CompetencyPeriods->aliasField('academic_period_id') => $this->academicPeriodId,
                $CompetencyPeriods->aliasField('competency_template_id') => $this->competencyTemplateId,
                $CompetencyPeriods->aliasField('date_enabled < ') => $today,
                $CompetencyPeriods->aliasField('date_disabled > ') => $today
            ])
            ->all();

        if (!$competencyPeriodResults->isEmpty()) {
            $params = $this->getQueryString();
            // must reset
            unset($params['competency_item_id']);
            $this->competencyPeriodId = $this->getQueryString('competency_period_id');

            foreach ($competencyPeriodResults as $periodKey => $periodObj) {
                $rowData = [];
                if (is_null($this->competencyPeriodId) || $this->competencyPeriodId == $periodObj->id) {
                    $inputHtml = $periodObj->code_name;
                    $inputHtml .= $form->hidden($attr['model'].'.competency_period', ['value' => $periodObj->id]);

                    $rowData[] = $inputHtml;
                    $this->competencyPeriodId = $periodObj->id;
                } else {
                    $params['competency_period_id'] = $periodObj->id;
                    $baseUrl = $this->url($action, false);
                    $url = $this->setQueryString($baseUrl, $params);

                    $rowData[] = $event->subject->Html->link($periodObj->code_name, $url);
                }

                $tableCells[$periodKey] = $rowData;
            }
        } else {
            $rowData = [];
            $rowData[] = $this->getMessage('general.noRecords');

            $tableCells[] = $rowData;
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

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

        $tableHeaders = [];
        $tableCells = [];

        $tableHeaders[] = __('Item');

        if (is_null($this->competencyPeriodId)) {
            $rowData = [];
            $rowData[] = $this->getMessage('general.noRecords');

            $tableCells[] = $rowData;
        } else {
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
                    $rowData = [];
                    if (is_null($this->competencyItemId) || $this->competencyItemId == $itemObj->id) {
                        // $rowData[] = $itemObj->name;
                        $inputHtml = $itemObj->name;
                        $inputHtml .= $form->hidden($attr['model'].'.competency_item', ['value' => $itemObj->id]);

                        $rowData[] = $inputHtml;
                        $this->competencyItemId = $itemObj->id;
                    } else {
                        $params['competency_period_id'] = $this->competencyPeriodId;
                        $params['competency_item_id'] = $itemObj->id;
                        $baseUrl = $this->url($action, false);
                        $url = $this->setQueryString($baseUrl, $params);

                        $rowData[] = $event->subject->Html->link($itemObj->name, $url);
                    }

                    $tableCells[$itemKey] = $rowData;
                }
            } else {
                $rowData = [];
                $rowData[] = $this->getMessage('general.noRecords');

                $tableCells[] = $rowData;
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        if ($action == 'view' || $action == 'edit') {
            $value = $event->subject()->renderElement('Institution.StudentCompetencies/competency_item', ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function onGetCustomStudentsElement(Event $event, $action, $entity, $attr, $options=[])
    {
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

        $value = '';
        $form = $event->subject()->Form;
        $fieldPrefix = $attr['model'] . '.student_competency_results';

        $tableHeaders = [];
        $tableCells = [];

        // Build table header
        $tableHeaders[] = __('OpenEMIS ID');
        $tableHeaders[] = __('Student Name');
        $colOffset = 2; // 0 -> OpenEMIS ID, 1 -> Student Name

        $competencyItemEntity = null;
        if (!is_null($this->competencyItemId)) {
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
                    $tableHeaders[$colKey + $colOffset] = $criteriaObj['name'];
                }
            }
        }

        if (!is_null($this->classId)) {
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $students = $ClassStudents
                ->find()
                ->contain(['Users'])
                ->where([$ClassStudents->aliasField('institution_class_id') => $this->classId])
                ->toArray();

            foreach ($students as $rowKey => $studentObj) {
                $studentId = $studentObj->student_id;
                $rowPrefix = "$fieldPrefix.$studentId";
                
                $rowData = [];
                $rowInput = "";

                if ($action == 'view') {
                    $rowData[] = $event->subject->Html->link($studentObj->user->openemis_no, [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentUser',
                        'view',
                        $this->paramsEncode(['institution_id' => $this->institutionId, 'id' => $studentObj->user->id])
                    ]);
                    $rowData[] = $studentObj->user->name;
                } else if ($action == 'edit') {
                    $rowData[] = $studentObj->user->openemis_no . $rowInput;
                    $rowData[] = $studentObj->user->name;
                }

                if (!is_null($competencyItemEntity) && $competencyItemEntity->has('criterias')) {
                    foreach ($competencyItemEntity->criterias as $colKey => $criteriaObj) {
                        $competencyCriteriaId = $criteriaObj['id'];
                        $cellPrefix = "$rowPrefix.$competencyCriteriaId";
                        $cellInput = "";
                        $cellValue = "";

                        $cellOptions = ['label' => false, 'value' => ''];
                        $answerObj = null;

                        $answerValue = null;
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

                $tableCells[$rowKey] = $rowData;
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
}
