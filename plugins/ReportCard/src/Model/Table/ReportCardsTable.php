<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;

class ReportCardsTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST ALL_SUBJECTS = 2;
    CONST SELECT_SUBJECTS = 1;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ReportCardSubjects', ['className' => 'ReportCard.ReportCardSubjects', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);
        $this->hasMany('StudentReportCards', ['className' => 'Institution.InstitutionStudentsReportCards', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCardExcludedSecurityRoles', ['className' => 'ReportsCard.ReportCardExcludedSecurityRoles', 'foreignKey' => 'report_card_id']); //POCOR-7400
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->behaviors()->get('Download')->config(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->config(
            'content',
            'excel_template'
        );
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['view']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
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

    public function beforeAction(Event $event, ArrayObject $extra)
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['description']['visible'] = false;
        $this->fields['principal_comments_required']['visible'] = false;
        $this->fields['homeroom_teacher_comments_required']['visible'] = false;
        $this->fields['teacher_comments_required']['visible'] = false;
        $this->fields['pdf_page_number']['visible'] = false;
        $this->setFieldOrder(['code', 'name', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date', 'education_grade_id', 'excel_template']);

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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //End

        $extra['elements']['controls'] = ['name' => 'ReportCard.controls', 'data' => [], 'options' => [], 'order' => 1];
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
        
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->excel_template;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
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

    public function onGetSubjects(Event $event, Entity $entity)
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

    public function onGetExcelTemplate(Event $event, Entity $entity)
    {
        if ($entity->has('excel_template_name')) {
            return $entity->excel_template_name;
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        // to set template download button
        $downloadUrl = $this->url('downloadTemplate');
        $this->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");

        //POCOR-7400 start
        $SecurityRoles = TableRegistry::get('security_roles');
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

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->field('education_programme_id', ['type' => 'select']);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date','excluded_security_roles', 'education_programme_id', 'education_grade_id', 'principal_comments_required', 'homeroom_teacher_comments_required', 'teacher_comments_required', 'subjects', 'excel_template','pdf_page_number']);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
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

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $this->fields['code']['type'] = 'readonly';
       // $this->fields['name']['type'] = 'readonly';
        $this->field('education_programme_id', ['entity' => $entity]);
        $this->setFieldOrder(['code', 'name', 'description', 'academic_period_id', 'start_date', 'end_date', 'generate_start_date', 'generate_end_date', 'education_programme_id', 'education_grade_id', 'principal_comments_required', 'homeroom_teacher_comments_required', 'teacher_comments_required', 'subjects', 'excel_template','pdf_page_number']);
    }

    public function onUpdateFieldExcelTemplate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } else {
            // attr for template download button
            $attr['startWithOneLeftButton'] = 'download';
            $attr['type'] = 'binary';
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        if ($action == 'add') {
            
            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();                    
                
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

    public function addOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['education_grade_id']);
                }
                if (array_key_exists('subjects', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['subjects']);
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

    public function addOnChangeEducationGradeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('subjects', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['subjects']);
                }
            }
        }
    }

    public function onUpdateFieldTeacherCommentsRequired(Event $event, array $attr, $action, Request $request)
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

    public function addEditOnChangeTeacherCommentsRequired(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('subjects', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['subjects']);
                }
            }
        }
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $teacherComments = isset($request->data[$this->alias()]['teacher_comments_required']) ? $request->data[$this->alias()]['teacher_comments_required'] : 0;
                $selectedGrade = isset($request->data[$this->alias()]['education_grade_id']) ? $request->data[$this->alias()]['education_grade_id'] : null;

            } else if($action == 'edit') {
                $teacherComments = isset($request->data[$this->alias()]['teacher_comments_required']) ? $request->data[$this->alias()]['teacher_comments_required'] : $attr['entity']->teacher_comments_required;
                $selectedGrade = $attr['entity']->education_grade_id;
            }

            if (empty($teacherComments) || $teacherComments == self::ALL_SUBJECTS) {
                $attr['type'] = 'hidden';
                $attr['value'] = '';

            } else {
                $subjectOptions = [];

                if (!is_null($selectedGrade)) {
                    $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
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

            $attr['fieldName'] = $this->alias().'.subjects';
        }

        return $attr;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {   
        //POCOR-7860 :: Start
        $string = $data['ReportCards']['name'];
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $string))
        {
            // one or more of the 'special characters' found in $string
            $this->Alert->error('Templates.specialCharr', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('edit'));;
        }
        //POCOR-7860 :: End
        if (!empty($data[$this->alias()]['teacher_comments_required']) && !empty($data[$this->alias()]['education_grade_id'])) {
            $selectedGrade = $data[$this->alias()]['education_grade_id'];
            $teacherComments = $data[$this->alias()]['teacher_comments_required'];

            $subjects = [];
            if ($teacherComments == self::SELECT_SUBJECTS) {
                if (!empty($data[$this->alias()]['subjects'])) {
                    $subjects = $data[$this->alias()]['subjects'];
                }
                $options['validate'] = 'subjects';

            } else if ($teacherComments == self::ALL_SUBJECTS) {
                // option only available during add
                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                $subjects = $EducationSubjects->find()
                    ->find('visible')
                    ->innerJoinWith('EducationGrades')
                    ->where(['EducationGrades.id' => $selectedGrade])
                    ->order([$EducationSubjects->aliasField('order')])
                    ->extract('id');
            }

            if (!empty($subjects)) {
                foreach ($subjects as $subject) {
                    $data[$this->alias()]['report_card_subjects'][] = [
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

    public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (empty($entity->errors())) {
            if ($entity->teacher_comments_required == self::ALL_SUBJECTS) {
                $entity->teacher_comments_required = 1;
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (empty($entity->errors())) {
            // manually delete hasMany reportCardSubjects data
            $fieldKey = 'report_card_subjects';
            if (!array_key_exists($fieldKey, $data[$this->alias()])) {
                $data[$this->alias()][$fieldKey] = [];
            }

            $subjectIds = array_column($data[$this->alias()][$fieldKey], 'education_subject_id');
            $originalSubjects = $entity->extractOriginal([$fieldKey])[$fieldKey];
            foreach ($originalSubjects as $key => $subject) {
                if (!in_array($subject['education_subject_id'], $subjectIds)) {
                    $this->ReportCardSubjects->delete($subject);
                    unset($entity->report_card_subjects[$key]);
                }
            }
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [$this->ReportCardSubjects->alias()];
    }

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
    }

    // Added
    private function setupTabElements($entity)
    {
        $tabElements = $this->controller->getReportCardTab($entity->id);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!empty($entity->generate_start_date)) {
            $entity->generate_start_date = (new Date($entity->generate_start_date))->format('Y-m-d H:i:s');
        }

        if (!empty($entity->generate_end_date)) {
            $entity->generate_end_date = (new Date($entity->generate_end_date))->format('Y-m-d H:i:s');
        }        
    } 

    /**
     * * POCOR-6916
     * add number of pages print while pdf generate 
     */
    public function onUpdateFieldPdfPageNumber(Event $event, array $attr, $action, Request $request)
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
      public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $table=TableRegistry::get('report_cards');
        $entityData=$table->find()->where([$table->aliasField('code')=>$entity->code,
                                $table->aliasField('academic_period_id')=>$entity->academic_period_id
                                ])->first();
       
        $ReportCardExcludedSecurityRolesTable = TableRegistry::get('report_card_excluded_security_roles');
  
        if($this->request->params['pass'][0] == 'edit'){
           
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

    public function onGetExcludedSecurityRoles(Event $event, Entity $entity)
    {
        $table=TableRegistry::get('security_roles');
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
 
         $Staff = TableRegistry::get('Institution.Staff');
         $institutionSecurityGroupsIds = self::getInstitutionSecurityGroupsIds($institutionId);
 //        Log::debug('$institutionSecurityGroupsIds');
 //        Log::debug($institutionSecurityGroupsIds);
         $institutionsPositions = TableRegistry::get('Institution.InstitutionPosition');//POCOR-8093
         $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
         $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
         $where = [
             $Staff->aliasField('institution_id') => $institutionId,
             'InstitutionPositions.staff_position_title_id' => $staffPosnId, //POCOR-8193
             'SecurityGroupUsers.security_group_id IN (' . implode(',', $institutionSecurityGroupsIds) . ')',
             $Staff->aliasField('staff_status_id') => $assignedStatus
         ];
 
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
        $securityGroupInstitutions = TableRegistry::get('security_group_institutions');
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
}


