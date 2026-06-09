<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\FrozenTime;

class ReportCardsTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST ALL_SUBJECTS = 2;
    CONST SELECT_SUBJECTS = 1;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ReportCardSubjects', ['className' => 'ReportCard.ReportCardSubjects', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);
        $this->hasMany('StudentReportCards', ['className' => 'Institution.InstitutionStudentsReportCards', 'dependent' => true, 'cascadeCallbacks' => true]);
        // $this->hasMany('ReportCardExcludedSecurityRoles', ['className' => 'ReportsCard.ReportCardExcludedSecurityRoles', 'foreignKey' => 'report_card_id']); //POCOR-7400
        $this->hasMany('ReportCardExcludedSecurityRoles', ['className' => 'ReportCard.ReportCardExcludedSecurityRoles', 'foreignKey' => 'report_card_id']); //POCOR-8521
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->behaviors()->get('Download')->setConfig(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->setConfig(
            'content',
            'excel_template'
        );
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['view']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);//POCOR-8529
        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            ->notEmpty('name')
            ->notEmpty('academic_period_id')
            ->notEmpty('education_grade_id')
            ->notEmpty('principal_comments_required')
            ->notEmpty('homeroom_teacher_comments_required')
            ->notEmpty('teacher_comments_required')
            ->notEmpty('regenerate_gpa') //POCOR-9629
            ->notEmpty('regenerate_cumulative_gpa') //POCOR-9629
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
            ])
            ->add('generate_start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('generate_end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'generate_start_date', false]
                ]
            ])
            ->allowEmpty('excel_template');
    }

    public function validationSubjects(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator = $validator->requirePresence('subjects');
        return $validator;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupNewTabElements();
        $this->fields['excel_template_name']['visible'] = false;
        $this->field('start_date', ['type' => 'date']);
        $this->field('end_date', ['type' => 'date']);
        $this->field('generate_start_date', ['type' => 'date']);
        $this->field('generate_end_date', ['type' => 'date']);
        $this->field('excel_template');
    }

    private function setupNewTabElements()
    {
        $tabElements = $this->controller->getReportTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Templates');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['description']['visible'] = false;
        $this->fields['principal_comments_required']['visible'] = false;
        $this->fields['homeroom_teacher_comments_required']['visible'] = false;
        $this->fields['teacher_comments_required']['visible'] = false;
        $this->fields['pdf_page_number']['visible'] = false;
        $this->fields['overall_result']['visible'] = false;
        $this->fields['regenerate_cumulative_gpa']['visible'] = false;
        $this->setFieldOrder(['code', 'name', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date', 'education_grade_id', 'excel_template','regenerate_gpa']);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration','Templates','Report Cards');
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        // Academic Period filter
        $serverRequest = $this->request;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($serverRequest->getQuery('academic_period_id')) ? $serverRequest->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End
        //POCOR-9284 start
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $EducationGrades=TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $EducationGradeOptions = [];
        $educationGradeList = [];
        $EducationGradeOptions = $EducationGrades
                            ->find('list')
                            ->select([
                                'education_grade_id' => $EducationGrades->aliasField('id'),
                                'education_grade' => $EducationGrades->aliasField('name')
                            ])
                            ->InnerJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()], [
                                $EducationGrades->aliasField('id') . ' = ' . $InstitutionGrades->aliasField('education_grade_id')
                            ])
                            ->where([
                                $InstitutionGrades->aliasField('academic_period_id') => $selectedAcademicPeriod
                            ])
                            ->enableHydration(false)
                            ->order([$EducationGrades->aliasField('id') => 'DESC'])
                            ->toArray();

        $EducationGradeOptionsKey = [];
        $EducationGradeOptionsList=$EducationGradeOptions;
        $list=[];
        if(!empty($EducationGradeOptions)){
            foreach($EducationGradeOptions AS $key => $value){
                $EducationGradeOptionsKey[$key] = $key ;

            }
        }

        $EducationGradeOptions = ['-1' => __('All Education Grades')] + $EducationGradeOptions;
        $selectedEducationGrade = !is_null($this->request->getQuery('education_grade_id')) ? $this->request->getQuery('education_grade_id') : -1;
      //  $EducationGradeOptions = array_unique($EducationGradeOptions);
        $this->controller->set(compact('EducationGradeOptions', 'selectedEducationGrade'));
        $extra['elements']['controls'] = ['name' => 'ReportCard.controls', 'data' => [], 'options' => [], 'order' => 1];
        if(!empty($selectedEducationGrade) && $selectedEducationGrade != -1){
            $where[$this->aliasField('education_grade_id')] = $selectedEducationGrade;
        } //POCOR-9284 end
        $query->where($where);
    }

    private function setupFields($entity)
    {
        $this->field('code');
        $this->field('name');
        $this->field('description');
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['entity' => $entity]);
        $this->field('subjects', ['entity' => $entity]);
        $this->field('pdf_page_number');
        $this->field('principal_comments_required', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('homeroom_teacher_comments_required', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('teacher_comments_required', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('overall_result', ['options' => $this->getSelectOptions('general.overallresult')]);
        $this->field('regenerate_gpa', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('regenerate_cumulative_gpa', ['options' => $this->getSelectOptions('general.yesno')]);

    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->excel_template;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->getConfig(
            'actions.download.show',
            $showFunc
        );
        // End
        $this->field('excluded_security_roles');//POCOR-7400
        $this->setupFields($entity);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date',  'excluded_security_roles','education_grade_id', 'principal_comments_required', 'homeroom_teacher_comments_required', 'teacher_comments_required', 'subjects', 'excel_template','pdf_page_number']);

        // Added
        $this->setupTabElements($entity);
    }

    //POCOR-8521[START]

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //POCOR-7400 start
        $query->contain(['ReportCardSubjects.EducationSubjects','ReportCardExcludedSecurityRoles']);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $arr =[];
                foreach($row->report_card_excluded_security_roles as $key=> $role){
                    $arr[$key] = ['id'=>$role['security_role_id']];
                }
                $row['excluded_security_roles'] = $arr;

                return $row;
            });
        });
        //POCOR-7400 end
    }

    public function onGetSubjects(EventInterface $event, Entity $entity)
    {
        $obj = [];
        if ($entity->has('report_card_subjects')) {
            foreach ($entity->report_card_subjects as $subject) {
                $obj[] = $subject->education_subject->name;
            }
        }

        $values = !empty($obj) ? implode(', ', $obj) : __('No Subjects');
        return $values;
    }

    public function onGetExcelTemplate(EventInterface $event, Entity $entity)
    {
        if ($entity->has('excel_template_name')) {
            return $entity->excel_template_name;
        }
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // to set template download button
        $downloadUrl = $this->url('downloadTemplate');
        $this->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");

        //POCOR-7400 start
        $SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $SecurityRoleOptions = $SecurityRoles->find('list',['keyField' => 'id', 'valueField' => 'name']);
        $tooltipMessage="The security roles chosen here will not be affected by the date enabled and date disabled.";
        $this->field('excluded_security_roles', [
             'type' => 'chosenSelect',
             'attr' => [
                  'label' => [
                     'text' => __('Excluded Security Roles') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue"  tooltip-placement="bottom" uib-tooltip="' .$tooltipMessage . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                     'escape' => false,
                     'class' => 'tooltip-desc'
                 ]
        ]]);
        $this->fields['excluded_security_roles']['options'] =  $SecurityRoleOptions;
        //POCOR-7400 end
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->field('education_programme_id', ['type' => 'select']);
        $this->field('regenerate_gpa', ['type' => 'select']);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date','excluded_security_roles', 'education_programme_id', 'education_grade_id', 'overall_result', 'regenerate_gpa', 'regenerate_cumulative_gpa','principal_comments_required', 'homeroom_teacher_comments_required', 'teacher_comments_required', 'subjects', 'excel_template','pdf_page_number']);
    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        // Added
        $this->setupTabElements($entity);

        // populate subjects data
        if ($entity->has('report_card_subjects') && !empty($entity->report_card_subjects)) {
            foreach ($entity->report_card_subjects as $subject) {
                $subjectsArr[] = $subject->education_subject_id;
            }
            $entity->subjects = isset($subjectsArr) ? $subjectsArr : [];
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->fields['code']['type'] = 'readonly';
       // $this->fields['name']['type'] = 'readonly';
        $this->field('education_programme_id', ['entity' => $entity]);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date', 'education_programme_id', 'education_grade_id', 'overall_result', 'regenerate_gpa','regenerate_cumulative_gpa','principal_comments_required', 'homeroom_teacher_comments_required', 'teacher_comments_required', 'subjects', 'excel_template','pdf_page_number']);
    }

    public function onUpdateFieldExcelTemplate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } elseif($action == 'edit') { //POCOR-8903
            $requestId = $this->request->getParam('pass')[1];
            $paramsDecode = $this->paramsDecode($requestId);
            $recordId = $paramsDecode['id']; // Added semicolon

            $record = $this->find()
                ->where([$this->aliasField('id') => $recordId])
                ->first();
            $excelName = $record ? $record->excel_template_name : null;
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
            $attr['value'] = $excelName;
            $attr['attr']['value'] = $excelName;
        }else{
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $periodOptions;

        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->academic_period_id;
            $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;
        }
        return $attr;
    }
    //POCOR-8521[START]
    public function onUpdateFieldGenerateStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if(!empty( $request->getData('ReportCards')['generate_start_date'])){
                $attr['type'] = 'date';
                $attr['value'] = $request->getData('ReportCards')['generate_start_date'];
            }
        }
        return $attr;
    }

    public function onUpdateFieldGenerateEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if(!empty($request->getData('ReportCards')['generate_end_date'])){
                $attr['type'] = 'date';
                $attr['value'] = $request->getData('ReportCards')['generate_end_date'];
            }
        }
        return $attr;
    }
    //POCOR-8521[END]

    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');

        if ($action == 'add') {

            $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

            $programmeOptions = $EducationProgrammes
                ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                ->find('visible')
                ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                ->order(['EducationCycles.order', $EducationProgrammes->aliasField('order')])
                ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                ->toArray();
            $attr['type'] = 'select';
            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';

        } else if ($action == 'edit') {
            //since programme_id is not stored, then during edit need to get from grade
            $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
        }

        return $attr;
    }

    public function addOnChangeEducationProgrammeId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('education_grade_id', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['education_grade_id']);
                }
                if (array_key_exists('subjects', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['subjects']);
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $gradeOptions = [];

            if (isset($request->getData()[$this->getAlias()]['education_programme_id']) && !empty($request->getData()[$this->getAlias()]['education_programme_id'])) {
                $selectedProgramme = $request->getData()[$this->getAlias()]['education_programme_id'];
                $gradeOptions = $this->EducationGrades
                    ->find('list')
                    ->find('visible')
                    ->contain(['EducationProgrammes'])
                    ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                    ->order(['EducationProgrammes.order', $this->EducationGrades->aliasField('order')])
                    ->toArray();
            }
            $attr['type'] = 'select';
            $attr['options'] = $gradeOptions;
            $attr['onChangeReload'] = 'changeEducationGradeId';

        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->education_grade_id;
            $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
        }

        return $attr;
    }

    public function addOnChangeEducationGradeId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('subjects', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['subjects']);
                }
            }
        }
    }

    public function onUpdateFieldTeacherCommentsRequired(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $options = [
                    self::ALL_SUBJECTS => __('Yes') . ' - ' . __('All Subjects'),
                    self::SELECT_SUBJECTS => __('Yes') . ' - ' . __('Select Subjects'),
                    0 => __('No')
                ];
            } else if ($action == 'edit') {
                $options = [
                    self::SELECT_SUBJECTS => __('Yes'),
                    0 => __('No')
                ];
            }

            $attr['options'] = $options;
            $attr['onChangeReload'] = 'changeTeacherCommentsRequired';
        }
        return $attr;
    }

    public function onUpdateFieldTotalMark(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function addEditOnChangeTeacherCommentsRequired(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('subjects', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['subjects']);
                }
            }
        }
    }

    public function onUpdateFieldSubjects(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $data = $request->getData(); // POCOR-8969
            if ($action == 'add') {
                $teacherComments = isset($data[$this->getAlias()]['teacher_comments_required']) ? $data[$this->getAlias()]['teacher_comments_required'] : 0;
                $selectedGrade = isset($data[$this->getAlias()]['education_grade_id']) ? $data[$this->getAlias()]['education_grade_id'] : null;

            } else if($action == 'edit') {
                $teacherComments = isset($data[$this->getAlias()]['teacher_comments_required']) ? $data[$this->getAlias()]['teacher_comments_required'] : $attr['entity']->teacher_comments_required;
                $selectedGrade = $attr['entity']->education_grade_id;
            }

            if (empty($teacherComments) || $teacherComments == self::ALL_SUBJECTS) {
                $attr['type'] = 'hidden';
                $attr['value'] = '';

            } else {
                $subjectOptions = [];

                if (!is_null($selectedGrade)) {
                    $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
                    $subjectOptions = $EducationSubjects
                        ->find('list')
                        ->find('visible')
                        ->innerJoinWith('EducationGrades')
                        ->where(['EducationGrades.id' => $selectedGrade])
                        ->order([$EducationSubjects->aliasField('order')])
                        ->toArray();
                }

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $subjectOptions;
            }

            $attr['fieldName'] = $this->getAlias().'.subjects';
        }

        return $attr;
    }

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {

        //POCOR-7860 :: Start
        $string = $data['ReportCards']['name'];
        if (preg_match('/[\'\^£$%&*()}{@#~?><>,|=_+¬-]/u', $string))
        {
            // one or more of the 'special characters' found in $string
            $this->Alert->error('Templates.specialCharr', ['reset' => true]);
            $event->stopPropagation();
            //POCOR-9639: redirect back to the current action (add or edit) instead of hardcoding 'edit'
            //Previously always redirected to /edit with no entity ID, causing SecurityException:
            //"Wrong number of segments" in paramsDecode() when pass[1] is null on Edit page load
            if ($entity->isNew()) {
                return $this->controller->redirect($this->url('add'));
            }
            return $this->controller->redirect($this->url('edit'));
        }
        //POCOR-7860 :: End
        if (!empty($data[$this->getAlias()]['teacher_comments_required']) && !empty($data[$this->getAlias()]['education_grade_id'])) {
            $selectedGrade = $data[$this->getAlias()]['education_grade_id'];
            $teacherComments = $data[$this->getAlias()]['teacher_comments_required'];

            $subjects = [];
            if ($teacherComments == self::SELECT_SUBJECTS) {
                if (!empty($data[$this->getAlias()]['subjects'])) {
                    $subjects = $data[$this->getAlias()]['subjects'];
                }
                $options['validate'] = 'subjects';

            } else if ($teacherComments == self::ALL_SUBJECTS) {
                // option only available during add
                $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
                $subjects = $EducationSubjects->find()
                    ->find('visible')
                    ->innerJoinWith('EducationGrades')
                    ->where(['EducationGrades.id' => $selectedGrade])
                    ->order([$EducationSubjects->aliasField('order')])
                    ->extract('id');
            }

            if (!empty($subjects)) {
                foreach ($subjects as $subject) {
                    $data[$this->getAlias()]['report_card_subjects'][] = [
                        'education_subject_id' => $subject,
                        'education_grade_id' => $selectedGrade
                    ];
                }

                // needed to save hasMany data
                $options['associated'] = [
                     'ReportCardSubjects' => [
                        'validate' => false
                    ]
                ];
            }
        }
    }

    public function addAfterPatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (empty($entity->getErrors())) {
            if ($entity->teacher_comments_required == self::ALL_SUBJECTS) {
                $entity->teacher_comments_required = 1;
            }
        }
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (empty($entity->getErrors())) {
            // manually delete hasMany reportCardSubjects data
            $fieldKey = 'report_card_subjects';
            if (!array_key_exists($fieldKey, $data[$this->getAlias()])) {
                $data[$this->getAlias()][$fieldKey] = [];
            }

            $subjectIds = array_column($data[$this->getAlias()][$fieldKey], 'education_subject_id');
            $originalSubjects = $entity->extractOriginal([$fieldKey])[$fieldKey];
            foreach ($originalSubjects as $key => $subject) {
                if (!in_array($subject['education_subject_id'], $subjectIds)) {
                    $this->ReportCardSubjects->delete($subject);
                    unset($entity->report_card_subjects[$key]);
                }
            }
        }
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [$this->ReportCardSubjects->getAlias()];
    }

    // POCOR-8572 Start
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra) {
        $extra['excludedModels'] = [$this->ReportCardSubjects->getAlias()];

        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }
    // POCOR-8572 End


    public function checkIfHasTemplate($reportCardId=0)
    {
        $hasTemplate = false;

        if (!empty($reportCardId)) {
            $entity = $this->get($reportCardId);
            $hasTemplate = !empty($entity->excel_template) ? true : false;
        }

        return $hasTemplate;
    }

    public function downloadTemplate()
    {
        $filename = 'report_card_template';
        $fileType = 'xlsx';
        $filepath = WWW_ROOT . 'export' . DS . 'customexcel'. DS . 'default_templates'. DS . $filename . '.' . $fileType;

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".basename($filepath));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filepath));
        echo file_get_contents($filepath);
        die;
    }

    // Added
    private function setupTabElements($entity)
    {
        $tabElements = $this->controller->getReportCardTab($entity->id);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

// POCOR-8969
//    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
//    {
//        $entity->generate_start_date =  (new Date($this->request->getData('ReportCards')['generate_start_date']))->modify('+1 day')->format('Y-m-d H:i:s');
//        $entity->generate_end_date =  (new Date($this->request->getData('ReportCards')['generate_end_date']))->modify('+1 day')->format('Y-m-d H:i:s');
//    }

    /**
     * * POCOR-6916
     * add number of pages print while pdf generate
     */
    public function onUpdateFieldPdfPageNumber(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $pdfPage = array(-1 =>'All',1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10);
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $pdfPage;
            $attr['attr']['label'] =  __('Number sheets in PDF');//POCOR-7064
        } else if ($action == 'edit') {
            $attr['type'] = 'select';
            $attr['value'] = $attr['entity']->pdf_page_number;
            $attr['options'] = $pdfPage;
            $attr['attr']['label'] =  __('Number sheets in PDF');//POCOR-7064
        }else if ($action == 'view') {
            $attr['attr']['label'] =  __('Number sheets in PDF');//POCOR-7064
        }
        return $attr;
    }

    //POCOR-7400 start
      public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $table=TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
        $entityData=$table->find()->where([$table->aliasField('code')=>$entity->code,
                                $table->aliasField('academic_period_id')=>$entity->academic_period_id
                                ])->first();

        $ReportCardExcludedSecurityRolesTable = TableRegistry::getTableLocator()->get('ReportCard.ReportCardExcludedSecurityRoles');

        if($this->request->getParam('pass')[0] == 'edit'){

        $ExcludedSecurityRoleData =  $ReportCardExcludedSecurityRolesTable->find()->where(['report_card_id'=>$entityData->id])->toArray();
        if($ExcludedSecurityRoleData){
           foreach($ExcludedSecurityRoleData as $ExcludedSecurityRoleEntity){
               $deleteEntity =  $ReportCardExcludedSecurityRolesTable->delete($ExcludedSecurityRoleEntity);
           }}
        }

        foreach($entity->excluded_security_roles['_ids'] as $one){

            $ExcludedSecurityRoleEntity = [ 'report_card_id' => $entityData->id,
                                            'security_role_id'=> $one
                                          ];
            $ExcludedSecurityRoles = $ReportCardExcludedSecurityRolesTable ->newEntity($ExcludedSecurityRoleEntity);
            $ExcludedSecurityRoleResult = $ReportCardExcludedSecurityRolesTable->save($ExcludedSecurityRoles);

        }
    }

    public function onGetExcludedSecurityRoles(EventInterface $event, Entity $entity)
    {
        $table=TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $obj = [];
        if ($entity->has('excluded_security_roles')) {

            foreach ($entity->excluded_security_roles as $role) {
               $res= $table->find('list')->where(['id'=>$role['id']])->first();
               $obj[] = $res;
            }
        }

        $values = !empty($obj) ? implode(', ', $obj) : __('No Excluded Security Roles ');
        return $values;
    }

    public static function getInstitutionSecurityStaff($institutionId, $staffPosnId)
     {

         $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
         $institutionSecurityGroupsIds = self::getInstitutionSecurityGroupsIds($institutionId);
 //        Log::debug('$institutionSecurityGroupsIds');
 //        Log::debug($institutionSecurityGroupsIds);
         $institutionsPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPosition');//POCOR-8093
         $StaffStatuses = TableRegistry::getTableLocator()->get('Staff.StaffStatuses');
         $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
         //POCOR-9598: start - getPrincipalRoleId() returns an array of IDs; use IN() so CakePHP doesn't try to cast array as string
         $staffPosnCondition = is_array($staffPosnId)
             ? ['InstitutionPositions.staff_position_title_id IN' => $staffPosnId]
             : ['InstitutionPositions.staff_position_title_id' => $staffPosnId];
         //POCOR-9598: end
         $where = array_merge([
             $Staff->aliasField('institution_id') => $institutionId,
             'SecurityGroupUsers.security_group_id IN (' . implode(',', $institutionSecurityGroupsIds) . ')',
             $Staff->aliasField('staff_status_id') => $assignedStatus
         ], $staffPosnCondition);

         $staffQuery = $Staff
             ->find()
             ->select([
                 $Staff->aliasField('id'),
                 $Staff->aliasField('FTE'),
                 $Staff->aliasField('start_date'),
                 $Staff->aliasField('start_year'),
                 $Staff->aliasField('end_date'),
                 $Staff->aliasField('end_year'),
                 $Staff->aliasField('staff_id'),
                 $Staff->aliasField('security_group_user_id'),
                 $Staff->aliasField('institution_position_id')//POCOR-8093
             ])
             ->innerJoin(
                 ['InstitutionPositions' => 'institution_positions'],
                     ['InstitutionPositions.id = Staff.institution_position_id']
             )
             ->innerJoinWith('SecurityGroupUsers')
             ->contain([
                 'Users' => [
                     'fields' => [
                         'openemis_no',
                         'first_name',
                         'middle_name',
                         'third_name',
                         'last_name',
                         'preferred_name',
                         'email',
                         'address',
                         'postal_code',
                         'gender_id' // POCOR-7033
                     ]
                 ]
             ])
             ->where($where);
         $entity = $staffQuery
             ->first();

         // POCOR-7033[START]
         if (!empty($entity)) {
             if ($entity->user->gender_id == '1') {
                 $entity->user->gender_id = "Male";
                 $entity->gender = "Male";
             } else {
                 $entity->user->gender_id = "Female";
                 $entity->gender = "Male";
             }
             $username = $entity->user->name;
             if(empty($username) || $username = ""){
                 $entity->user->name = $entity->user->first_name . ' ' . $entity->user->last_name;
             }
         }
         // POCOR-7033[END]
         return $entity;
    }

    /**
     * @param $institution_id
     * @return array
     */
    private static function getInstitutionSecurityGroupsIds($institution_id)
    {
        $securityGroupInstitutions = TableRegistry::getTableLocator()->get('security_group_institutions');
        $distinctResults = $securityGroupInstitutions
            ->find('all')
            ->select(['security_group_id'])
            ->distinct(['security_group_id'])
            ->where(['institution_id' => $institution_id])
            ->toArray();
        $distinctResultsValues = array_column($distinctResults, 'security_group_id');
        if (sizeof($distinctResultsValues) > 0) {
            $uniqu_array = array_unique($distinctResultsValues);
        } else {
            $uniqu_array = [0];
        };
        return $uniqu_array;
    }

    //POCOR-9629
    public function onGetRegenerateGpa(EventInterface $event, Entity $entity)
    {
        if ($entity->has('regenerate_gpa') && $entity->regenerate_gpa == 1) {
            return __('Yes');
        }

        return __('No');
    }

    //POCOR-9629
    public function onGetRegenerateCumulativeGpa(EventInterface $event, Entity $entity)
    {
        if ($entity->has('regenerate_cumulative_gpa') && $entity->regenerate_cumulative_gpa == 1) {
            return __('Yes');
        }

        return __('No');
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'start_date') {
            return __('Start Date');
        } elseif ($field == 'end_date') {
            return __('End Date');
        } elseif ($field == 'generate_start_date') {
            return __('Generate Start Date');
        } elseif ($field == 'generate_end_date') {
            return __('Generate End Date');
        } elseif ($field == 'education_programme_id') {
            return __('Education Programme');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'principal_comments_required') {
            return __('Principal Comments Required');
        } elseif ($field == 'homeroom_teacher_comments_required') {
            return __('Homeroom Teacher Comments Required');
        } elseif ($field == 'teacher_comments_required') {
            return __('Teacher Comments Required');
        } elseif ($field == 'excel_template') {
            return __('Excel Template');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}


