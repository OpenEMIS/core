<?php
namespace Textbook\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;
use Cake\I18n\Time;

use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class TextbooksTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);

        $this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'foreignKey' => ['textbook_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallBack' => true]);

        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportTextbooks']);

        $this->EducationLevels = TableRegistry::get('Education.EducationLevels');
        $this->EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noTextbooks')),
            'callable' => function($id) {
                return $this->find()
                    ->where([
                        $this->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);
        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;

        //education level filter
        $levelOptions = $this->EducationLevels->getLevelOptions();


        if ($levelOptions) {
            $levelOptions = array(-1 => __('-- Select Education Level --')) + $levelOptions;
        }

        if ($request->query('level')) {
            $selectedLevel = $request->query('level');
        } else {
            $selectedLevel = -1;
        }

        $this->advancedSelectOptions($levelOptions, $selectedLevel, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammes')),
            'callable' => function($id) {
                if ($id > 0) {
                    return count($this->EducationProgrammes->getEducationProgrammesList($id));
                } else { //for select all.
                    return true;
                }
            }
        ]);
        $extra['selectedLevel'] = $selectedLevel;
        $data['levelOptions'] = $levelOptions;
        $data['selectedLevel'] = $selectedLevel;

        // education programmes filter
        if ($selectedPeriod && $selectedLevel) {

            $programmeOptions = $this->EducationProgrammes->getEducationProgrammesList($selectedLevel);

            $programmeOptions = array(-1 => __('-- Please Select Education Programme --')) + $programmeOptions;

            if ($request->query('programme')) {
                $selectedProgramme = $request->query('programme');
            } else {
                $selectedProgramme = -1;
            }

            $this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
                'callable' => function($id) use ($selectedPeriod) {

                    if ($id > 0) {
                        return count($this->EducationGrades->getEducationGradesByProgrammes($id));
                    } else {
                        return true;
                    }
                }
            ]);
            $extra['selectedProgramme'] = $selectedProgramme;
            $data['programmeOptions'] = $programmeOptions;
            $data['selectedProgramme'] = $selectedProgramme;
        }

        //education subjects filter
        if ($selectedPeriod && $selectedProgramme) {
            $gradeSubjects = $this->EducationGrades->find('GradeSubjectsByProgramme', ['education_programme_id' => $selectedProgramme])->all();
            $subjectOptions = [];
            foreach ($gradeSubjects as $grade) {
                foreach ($grade->education_subjects as $subject) {
                    $key = $grade->id . '-' . $subject->id;
                    $subjectOptions[$key] = $grade->name . ' - ' . $subject->name;
                }
            }

            $subjectOptions = array(-1 => __('-- All Education Subject --')) + $subjectOptions;

            if ($request->query('subject')) {
                $selectedSubject = $request->query('subject');
            } else {
                $selectedSubject = -1;
            }

            $extra['selectedSubject'] = $selectedSubject;
            $data['subjectOptions'] = $subjectOptions;
            $data['selectedSubject'] = $selectedSubject;
        }

        //build up the control filter
        $extra['elements']['control'] = [
            'name' => 'Textbook.controls',
            'data' => $data,
            'order' => 3
        ];

        //hide fields on the index page.
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('education_subject_id', ['visible' => false]);
        $this->field('author', ['visible' => false]);
        $this->field('year_published', ['visible' => false]);
        $this->field('expiry_date', ['visible' => false]);

        $this->setFieldOrder([
            'code', 'title', 'ISBN', 'publisher'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $hasSearchKey = $this->request->session()->read($this->registryAlias().'.search.key');

        $conditions = [];

        if (!$hasSearchKey) {
            //filter
            if (array_key_exists('selectedPeriod', $extra)) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
                }
            }

            if (array_key_exists('selectedProgramme', $extra)) {
                if ($extra['selectedProgramme']) {
                    $query->innerJoinWith('EducationGrades.EducationProgrammes');
                    // pr($query);
                    $conditions[] = 'EducationProgrammes.id = ' . $extra['selectedProgramme'];
                }
            }

            if (array_key_exists('selectedGrade', $extra)) {
                if ($extra['selectedGrade'] > 0) {
                    $conditions[] = $this->aliasField('education_grade_id = ') . $extra['selectedGrade'];
                }
            }

            if (array_key_exists('selectedSubject', $extra)) {
                if ($extra['selectedSubject'] && $extra['selectedSubject'] > 0) {
                    $gradeSubject = explode('-', $extra['selectedSubject']);
                    $conditions[] = $this->aliasField('education_grade_id = ') . $gradeSubject[0];
                    $conditions[] = $this->aliasField('education_subject_id = ') . $gradeSubject[1];
                }
            }

            $query->where([$conditions]);
        }

    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'AcademicPeriods',
            'EducationSubjects.EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'
        ]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $entity->name = $entity->code . ' - ' . $entity->title;
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

    public function onGetEducationSubjectId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->education_subject->code_name;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }
        return $attr;
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
        $request->query['subject'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
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

                $selectedProgramme = $request->query('programme');
                $gradeOptions = [];
                if ($selectedProgramme) {
                    $gradeOptions = $this->EducationGrades->getEducationGradesByProgrammes($selectedProgramme);
                }

                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_subject->education_grades[0]->name;
                $attr['value'] = $attr['entity']->education_subject->education_grades[0]->id;

            }
        }

        return $attr;
    }

    public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request->query['subject'] = -1;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }

                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                $selectedGrade = $request->query('grade');
                $subjectOptions = [];
                if ($selectedGrade) {
                    $subjectOptions = $this->EducationSubjects->getEducationSubjectsByGrades($selectedGrade);
                }

                $attr['options'] = $subjectOptions;

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $attr['entity']->education_subject->code_name;
                $attr['value'] = $attr['entity']->education_subject->id;

            }
        }

        return $attr;
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $attr['entity']->code;
            $attr['value'] = $attr['entity']->code;

        }

        return $attr;
    }

    public function onUpdateFieldExpiryDate(Event $event, array $attr, $action, Request $request)
    {
        $attr['default_date'] = false;
        return $attr;
    }

    public function onUpdateFieldYearPublished(Event $event, array $attr, $action, Request $request)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $lowestYear = $ConfigItems->value('lowest_year');

        if ($action == 'add' || $action == 'edit') {
            $now = Time::now();
            for ($i = $now->year; $i >= $lowestYear; $i--) {
                $yearOptions[$i] = $i;
            }

            $attr['options'] = $yearOptions;
        }

        return $attr;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
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
        $this->field('education_subject_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('code', [
            'entity' => $entity
        ]);
        $this->field('year_published', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('expiry_date', [
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'education_level_id', 'education_programme_id', 'education_grade_id', 'education_subject_id',
            'code', 'title', 'author', 'publisher' , 'year_published', 'ISBN', 'expiry_date'
        ]);
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

    public function getTextbookOptions($academicPeriodId, $educationGradeId, $educationSubjectId)
    {
        $todayDate = Time::now()->format('Y-m-d');

        return  $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_title'])
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('education_grade_id') => $educationGradeId,
                    $this->aliasField('education_subject_id') => $educationSubjectId,
                    'OR' => [
                        [$this->aliasField('expiry_date') .' IS NULL'],
                        [$this->aliasField('expiry_date') .' > ' . "'$todayDate'"]
                    ]
                ])
                ->order([$this->aliasField('code') => 'ASC'])
                ->toArray();
    }
}
