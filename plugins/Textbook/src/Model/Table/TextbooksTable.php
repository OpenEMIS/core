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
        $this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);

        $this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'foreignKey' => 'textbook_id']);

        $this->setDeleteStrategy('restrict');
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
            // ->requirePresence('assessment_items')
            // ->add('education_grade_id', [
            //     'ruleAssessmentExistByGradeAcademicPeriod' => [ //validate so only 1 assessment for each grade per academic period
            //         'rule' => ['assessmentExistByGradeAcademicPeriod'],
            //         'on' => function ($context) {
            //             return $this->action == 'add';
            //         }
            //     ]
            // ]);
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

        //education programmes filter
        $programmeOptions = $this->EducationProgrammes->getEducationProgrammesList();

        if ($programmeOptions) {
            $programmeOptions = array(-1 => __('All Education Programmes')) + $programmeOptions;
        }

        if ($request->query('programme')) {
            $selectedProgramme = $request->query('programme');
        } else {
            $selectedProgramme = -1;
        }

        $this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noTextbooks')),
            'callable' => function($id) use ($selectedPeriod) {
                $conditions[] = $this->aliasField('academic_period_id = ') . $selectedPeriod;

                if ($id > 0) {
                    $conditions[] = $this->aliasField('education_programme_id = ') . $id;
                }

                return $this->find()
                            ->where([
                                $conditions
                            ])
                            ->count();
            }
        ]);
        $extra['selectedProgramme'] = $selectedProgramme;
        $data['programmeOptions'] = $programmeOptions;
        $data['selectedProgramme'] = $selectedProgramme;

        //education grades filter

        if ($selectedPeriod && $selectedProgramme) {
            $gradeOptions = $this->EducationGrades->getEducationGradesByProgrammes($selectedProgramme);

            if ($gradeOptions) {
                $gradeOptions = array(-1 => __('All Education Grade')) + $gradeOptions;
            }

            if ($request->query('grade')) {
                $selectedGrade = $request->query('grade');
            } else {
                $selectedGrade = -1;
            }

            $this->advancedSelectOptions($gradeOptions, $selectedGrade, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noTextbooks')),
                'callable' => function($id) use ($selectedPeriod, $selectedProgramme) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $selectedPeriod;

                    if ($selectedProgramme > 0) {
                        $conditions[] = $this->aliasField('education_programme_id = ') . $selectedProgramme;
                    }

                    if ($id > 0) {
                        $conditions[] = $this->aliasField('education_grade_id = ') . $id;
                    }

                    return $this->find()
                                ->where([
                                    $conditions
                                ])
                                ->count();
                }
            ]);

            $extra['selectedGrade'] = $selectedGrade;
            $data['gradeOptions'] = $gradeOptions;
            $data['selectedGrade'] = $selectedGrade;
        }

        //education subjects filter

        if ($selectedPeriod && $selectedProgramme && $selectedGrade) {
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
                'callable' => function($id) use ($selectedPeriod, $selectedProgramme, $selectedGrade) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $selectedPeriod;

                    if ($selectedProgramme > 0) {
                        $conditions[] = $this->aliasField('education_programme_id = ') . $selectedProgramme;
                    }

                    if ($selectedGrade > 0) {
                        $conditions[] = $this->aliasField('education_grade_id = ') . $selectedGrade;
                    }

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

        //build up the control filter
        $extra['elements']['control'] = [
            'name' => 'Textbook.controls',
            'data' => $data,
            'order' => 3
        ];

        //hide fields on the index page.
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('education_programme_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('education_subject_id', ['visible' => false]);
        $this->field('author', ['visible' => false]);
        $this->field('publisher', ['visible' => false]);
        $this->field('year_published', ['visible' => false]);
        $this->field('ISBN', ['visible' => false]);
        $this->field('provider', ['visible' => false]);
        $this->field('previous_textbook_id', ['visible' => 'false']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //filter
        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod']) {
                $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
            }
        }

        if (array_key_exists('selectedProgramme', $extra)) {
            if ($extra['selectedProgramme'] > 0) {
                $conditions[] = $this->aliasField('education_programme_id = ') . $extra['selectedProgramme'];
            }
        }

        if (array_key_exists('selectedGrade', $extra)) {
            if ($extra['selectedGrade'] > 0) {
                $conditions[] = $this->aliasField('education_grade_id = ') . $extra['selectedGrade'];
            }
        }

        if (array_key_exists('selectedSubject', $extra)) {
            if ($extra['selectedSubject'] > 0) {
                $conditions[] = $this->aliasField('education_subject_id = ') . $extra['selectedSubject'];
            }
        }

        $query->where([$conditions]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('previous_textbook_id', ['visible' => 'false']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetEducationSubjectId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $educationSubject = $this->EducationSubjects->get($entity->education_subject_id);
            return $educationSubject->code . ' - ' . $educationSubject->name;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
        }
        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $programmeOptions = $this->EducationProgrammes->getEducationProgrammesList();

            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgramme';
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
            $selectedProgramme = $request->query('programme');
            $gradeOptions = [];
            if ($selectedProgramme) {
                $gradeOptions = $this->EducationGrades->getEducationGradesByProgrammes($selectedProgramme);
            }

            $attr['options'] = $gradeOptions;
            $attr['onChangeReload'] = 'changeEducationGrade';
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

            $selectedGrade = $request->query('grade');
            $subjectOptions = [];
            if ($selectedGrade) {
                $subjectOptions = $this->EducationSubjects->getEducationSubjectsByGrades($selectedGrade);
            }

            $attr['options'] = $subjectOptions;
        }

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
        $this->field('year_published', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'education_programme_id', 'education_grade_id', 'education_subject_id',
            'code', 'title', 'author', 'publisher', 'publisher' , 'year_published', 'isbn'
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
        return $this->find('visible')
                    ->select([
                        'textbook_id' => $this->aliasField('id'),
                        'textbook_code_title' => $this->find()->func()->concat([
                            $this->aliasField('code') => 'literal',
                            " - ",
                            $this->aliasField('title') => 'literal'
                        ])
                    ])
                    ->find('list', ['keyField' => 'textbook_id', 'valueField' => 'textbook_code_title'])
                    ->where([
                        $this->aliasField('academic_period_id') => $academicPeriodId,
                        $this->aliasField('education_grade_id') => $educationGradeId,
                        $this->aliasField('education_subject_id') => $educationSubjectId
                    ])
                    ->order([$this->aliasField('code') => 'ASC'])
                    ->toArray();
    }
}
