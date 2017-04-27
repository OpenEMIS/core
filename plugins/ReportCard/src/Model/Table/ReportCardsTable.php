<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ReportCardsTable extends ControllerActionTable
{
    CONST PRINCIPAL_COMMENT = 1;
    CONST HOMEROOM_TEACHER_COMMENT = 2;
    CONST SUBJECT_TEACHER_COMMENT = 3;

    private $roleOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ReportCardSubjects', ['className' => 'ReportCard.ReportCardSubjects', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['view']
        ]);
        $this->behaviors()->get('Download')->config(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->config(
            'content',
            'excel_template'
        );

        $this->toggle('edit', false);
        $this->setDeleteStrategy('restrict');

        $this->roleOptions = [
            self::PRINCIPAL_COMMENT => __('Principal'),
            self::HOMEROOM_TEACHER_COMMENT => __('Homeroom Teacher'),
            self::SUBJECT_TEACHER_COMMENT => __('Subject Teachers')
        ];
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            ->add('start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', false]
                ]
            ]);
    }

    private function setupFields()
    {
        $this->field('code');
        $this->field('name');
        $this->field('description');
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('education_grade_id', ['type' => 'select']);
        $this->field('subjects', ['type' => 'chosenSelect']);
        $this->field('comments_required', ['type' => 'chosenSelect']);
        $this->field('excel_template');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['principal_comments_required']['visible'] = false;
        $this->fields['homeroom_teacher_comments_required']['visible'] = false;
        $this->fields['subject_teacher_comments_required']['visible'] = false;
        $this->fields['excel_template_name']['visible'] = false;

        $this->field('start_date', ['type' => 'date']);
        $this->field('end_date', ['type' => 'date']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['description']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->setFieldOrder(['code', 'name', 'start_date', 'end_date', 'education_grade_id', 'excel_template']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['elements']['controls'] = ['name' => 'ReportCard.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupFields();
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'education_grade_id', 'subjects', 'comments_required', 'excel_template']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain('ReportCardSubjects.EducationSubjects');
    }

    public function onGetCommentsRequired(Event $event, Entity $entity)
    {
        $obj = [];
        if ($entity->principal_comments_required) {
            $obj[] = $this->roleOptions[self::PRINCIPAL_COMMENT];
        }
        if ($entity->homeroom_teacher_comments_required) {
            $obj[] = $this->roleOptions[self::HOMEROOM_TEACHER_COMMENT];
        }
        if ($entity->subject_teacher_comments_required) {
            $obj[] = $this->roleOptions[self::SUBJECT_TEACHER_COMMENT];
        }

        $values = implode(', ', $obj);
        return $values;
    }

    public function onGetSubjects(Event $event, Entity $entity)
    {
        $obj = [];
        if ($entity->has('report_card_subjects')) {
            foreach ($entity->report_card_subjects as $subject) {
                $obj[] = $subject->education_subject->name;
            }
        }

        $values = implode(', ', $obj);
        return $values;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupFields();
        $this->field('education_programme_id', ['type' => 'select']);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'education_programme_id', 'education_grade_id', 'subjects', 'comments_required', 'excel_template']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        // if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
                $attr['options'] = $periodOptions;
            }
            //  else {

            //     $attr['type'] = 'readonly';
            //     $attr['value'] = $attr['entity']->academic_period_id;
            //     $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
            // }
        // }
        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        if ($action == 'add') {
            $programmeOptions = $EducationProgrammes
                ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                ->find('visible')
                ->contain(['EducationCycles'])
                ->order(['EducationCycles.order', $EducationProgrammes->aliasField('order')])
                ->toArray();

            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';

        }
        //  else {
        //     //since programme_id is not stored, then during edit need to get from grade
        //     $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
        //     $attr['type'] = 'readonly';
        //     $attr['value'] = $programmeId;
        //     $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
        // }

        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('subjects', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['subjects']);
                }
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['education_grade_id']);
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $gradeOptions = [];

            if (isset($request->data[$this->alias()]['education_programme_id']) && !empty($request->data[$this->alias()]['education_programme_id'])) {
                $selectedProgramme = $request->data[$this->alias()]['education_programme_id'];
                $gradeOptions = $this->EducationGrades
                    ->find('list')
                    ->find('visible')
                    ->contain(['EducationProgrammes'])
                    ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                    ->order(['EducationProgrammes.order', $this->EducationGrades->aliasField('order')])
                    ->toArray();
            }

            $attr['options'] = $gradeOptions;
            $attr['onChangeReload'] = 'changeEducationGrade';
        }
        // else {

        //     $attr['type'] = 'readonly';
        //     $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
        // }

        return $attr;
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $subjectOptions = [];

            if (isset($request->data[$this->alias()]['education_grade_id']) && !empty($request->data[$this->alias()]['education_grade_id'])) {
                $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
                $selectedGrade = $request->data[$this->alias()]['education_grade_id'];

                $subjectOptions = $EducationGradesSubjects
                    ->find('list', [
                        'keyField' => 'education_subject.id',
                        'valueField' => 'education_subject.code_name'
                    ])
                    ->find('visible')
                    ->contain('EducationSubjects')
                    ->where([$EducationGradesSubjects->aliasField('education_grade_id') => $selectedGrade])
                    ->order(['EducationSubjects.order'])
                    ->toArray();

                if (!empty($subjectOptions)) {
                    $subjectOptions = ['-1' => __('All Subjects')] + $subjectOptions;
                }
            }



            $attr['options'] = $subjectOptions;
        }
        // else {

        //     $attr['type'] = 'readonly';
        //     $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
        // }

        return $attr;
    }

    public function onUpdateFieldCommentsRequired(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->roleOptions;
        return $attr;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if (!empty($data['subjects']['_ids']) && !empty($data['education_grade_id'])) {
                $subjects = $data['subjects']['_ids'];

                if (is_array($subjects)) {
                    $obj = [];
                    foreach ($subjects as $subject) {
                        $obj[] = [
                            'education_subject_id' => $subject,
                            'education_grade_id' => $data['education_grade_id']
                        ];
                    }
                    $data['report_card_subjects'] = $obj;

                    // needed to save hasMany data
                    $options['associated'] = [
                         'ReportCardSubjects' => [
                            'validate' => false
                        ]
                    ];
                }
            }

            if (!empty($data['comments_required']['_ids'])) {
                $commentsRequired = $data['comments_required']['_ids'];

                if (is_array($commentsRequired)) {
                    foreach ($commentsRequired as $role) {
                        switch ($role) {
                            case self::PRINCIPAL_COMMENT:
                                $data['principal_comments_required'] = 1;
                                break;
                            case self::HOMEROOM_TEACHER_COMMENT:
                                $data['homeroom_teacher_comments_required'] = 1;
                                break;
                            case self::SUBJECT_TEACHER_COMMENT:
                                $data['subject_teacher_comments_required'] = 1;
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [$this->ReportCardSubjects->alias()];
    }
}
