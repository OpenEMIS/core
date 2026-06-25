<?php

namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;
use Cake\Routing\Router;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Text;
use Cake\Http\ServerRequest;
use Cake\Log\Log;

class AssessmentsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => false]);

        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'excel_template_name',
            'content' => 'excel_template',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'document',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'view'],
            'OpenEMIS_Classroom' => ['index']
        ]);
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );
        $this->behaviors()->get('Download')->setConfig(
            'name',
            'excel_template_name'
        );
        $this->behaviors()->get('Download')->setConfig(
            'content',
            'excel_template'
        );
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('assessment_items')
            ->add('education_grade_id', [
                'ruleAssessmentExistByGradeAcademicPeriod' => [ //validate so only 1 assessment for each grade per academic period
                    'rule' => ['assessmentExistByGradeAcademicPeriod'],
                    'on' => function ($context) {
                        return $this->action == 'add';
                    }
                ]
            ])
            ->allowEmpty('excel_template');
    }

    public function validationUpdateAcademicTerm(Validator $validation)
    {
        return $validation
            ->add('assessment_periods', 'ruleNotEmptyAcademicTerm', [
                'rule' => ['notEmptyAcademicTerm']
            ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => true]);
        $this->setFieldOrder(['code',
            'name',
            'description',
            'excel_template_name',
            'excel_template',
            'academic_period_id',
            'education_grade_id']);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($serverRequest->getQuery('period')));
        $extra['selectedPeriod'] = $selectedPeriod;
        $extra['elements']['control'] = [
            'name' => 'Assessment.controls',
            'data' => [
                'periodOptions' => $periodOptions,
                'selectedPeriod' => $selectedPeriod
            ],
            'order' => 3
        ];

        $this->field('type', [
            'visible' => false
        ]);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'Assessments', 'Assessments');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
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
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedPeriod']]);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AssessmentItems.EducationSubjects']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $assessmentItems = $entity->assessment_items;

        //this is to sort array based on certain value on subarray, in this case based on education order value
        // POCOR-7999 for readibility
        usort($assessmentItems,
            function ($a, $b) {
                return $a['education_subject']['order'] - $b['education_subject']['order'];
            });

        $entity->assessment_items = $assessmentItems;

        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->excel_template;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($event, $entity);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadTemplate'] = 'downloadTemplate';
        return $events;
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        // to set template download button
        $downloadUrl = $this->url('downloadTemplate');
        $this->controller->set('downloadOnClick', "javascript:window.location.href='" . Router::url($downloadUrl) . "'");
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $class = __CLASS__;
        $line = __LINE__;
        //$entity = $this->setIdEntityFromQueryString($class, $line, $entity);//POCOR-8520
        $this->setupFields($event, $entity); // POCOR-8074-3 entity needed for dependant select field
        // POCOR-7999 refactured
        if ($this->action == 'edit') {
            $assessmentItems = $entity->assessment_items;
            $education_grade_id = $entity['education_grade_id'];
            //this is to sort array based on certain value on subarray, in this case based on education order value
            usort($assessmentItems,
                function ($a, $b) {
                    return $a['education_subject']['order'] - $b['education_subject']['order'];
                });
            $getAssessment_id = $this->request->getAttribute('params')['pass'][1];
            $entityID = $this->ControllerAction->paramsDecode($getAssessment_id)['id'];
            $entity->assessment_items = $assessmentItems;
            $entity->present_assessment_items = $assessmentItems;
            $entity->assessment_id = $entity->id;
            $EducationGradeSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
            $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
            $assessmentItems = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
            $query = $EducationGradeSubjects->find()
                    ->select([
                        'id' => $EducationSubjects->aliasField('id'),
                        'name' => $EducationSubjects->aliasField('name'),
                        'code' => $EducationSubjects->aliasField('code'),
                        'assessment_item_id' => $assessmentItems->aliasField('id'),
                        'assessment_item_weight' => $assessmentItems->aliasField('weight'),
                        'assessment_item_classification' => $assessmentItems->aliasField('classification'),
                    ])
                    ->leftJoin(
                        [$EducationSubjects->getAlias() => $EducationSubjects->getTable()],
                        [
                            $EducationSubjects->aliasField('id') . ' = ' . $EducationGradeSubjects->aliasField('education_subject_id')
                        ]
                    )
                    ->leftJoin(
                        [$assessmentItems->getAlias() => $assessmentItems->getTable()],
                        [
                            $assessmentItems->aliasField('education_subject_id') . ' = ' . $EducationGradeSubjects->aliasField('education_subject_id'),
                            $assessmentItems->aliasField('assessment_id') . ' = ' . $entityID,
                        ]
                    )
                    ->where([$EducationGradeSubjects->aliasField('education_grade_id') => $education_grade_id])
                    ->order([$EducationSubjects->aliasField('order')])->toArray();//POCOR-7122
            $all_subjects = [];
            foreach ($query as $subject) {
                $grade_education_subject = $subject;
                $key = $subject['id'];
                $value = $subject['code'] . '-' . $subject['name'];
                $results[$key] = $value;
                $grade_education_subject['label'] = $subject['code'] . '-' . $subject['name'];
                $grade_education_subject['present'] = isset($subject['assessment_item_id']) ? true : false;
                $all_subjects[] = $grade_education_subject;
            }
            $entity->assessment_subject = $results;
            $entity->grade_education_subjects = $all_subjects;

        }

        $this->setupFields($event, $entity);
    }

    /**
     * POCOR-6780
     * add edit education subject based on assessment item
     */
    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        // POCOR-7999 refactured
        if ($this->action == 'edit') {
            $getAssessment_id = $this->request->getAttribute('params')['pass'][1];
            $assessmentId = $this->ControllerAction->paramsDecode($getAssessment_id)['id'];
            $currentTimeZone = date("Y-m-d H:i:s");
            //$assessment_id = $entity->id;
            $assessment_id = $assessmentId;
            $this_alias = $this->getAlias();
            if (!isset($requestData[$this_alias])) {
                return;
            }
            $entity->assessment_items = [];
            $assessment_items = $requestData[$this_alias]['assessment_items'];
            if (!isset($assessment_items)) { //logic to capture error if no subject inside the grade.
                $errorMessage = $this->aliasField('noSubjects');
                $requestData['errorMessage'] = $errorMessage;
                return;
            }
            foreach ($assessment_items as $key => $assessment_item) {
                $education_subject_check = $assessment_item['education_subject_check'];
                if ($education_subject_check != 1) {
                    continue;
                }

                $subject_id = $assessment_item['education_subject_id'];
                $weight = $assessment_item['weight'];
                $classification = $assessment_item['classification'];
                $is_new = $assessment_item['id_check'];
                $assessmentItems = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
                $weight = preg_replace('/\.(?=.*\.)/', '', $weight);

                $floatValue = filter_var($weight, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
                if ($floatValue === false || $floatValue === '') {
                    $floatValue =  '0.00';
                }
                $weight =  number_format((float)$floatValue, 2, '.', '');
                if(!empty($weight) && $weight > 2){
                    $weight = 0.00;
                }
                if (!$is_new) {
                    $assessmentData = $assessmentItems->
                    find()
                    ->select(['id' => $assessmentItems->aliasField('id')])->
                    where([
                        $assessmentItems->aliasField('assessment_id') => $assessment_id,
                        $assessmentItems->aliasField('education_subject_id') => $subject_id
                    ])
                        ->toArray();
                    $assessment_item_id = $assessmentData[0]['id'];
                    $assesmentItem = $assessmentItems->updateAll(
                        ['weight' => is_null($weight) ? 0.00 : $weight,
                            'classification' => $classification],    //field
                        ['id' => $assessment_item_id,
                        ] //condition
                    );
                }

                if ($is_new) { //new assessment assessment_item
                    $assessmenItemId = Text::uuid();
                    $assessment_data = [
                        'id' => $assessmenItemId,
                        'weight' => is_null($weight) ? 0.00 : $weight,
                        'classification' => $classification,
                        'assessment_id' => $assessment_id,
                        'education_subject_id' => $is_new,
                        'created_user_id' => 1,
                        'created' => $currentTimeZone,
                    ];
                    $assesmentEntity = $assessmentItems->newEntity($assessment_data);
                    $assesmentItem = $assessmentItems->save($assesmentEntity); // comment cakephp4
                }
                $data[$this->getAlias()]['assessment_items'] = $assessmentItems;
            }
        }
    }



    public
    function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if ($requestData->offsetExists($this->getAlias())) {
        $assessmentItems = $requestData[$this->getAlias()]['assessment_items'] ?? null;
        if ($assessmentItems) {
                $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
                foreach ($assessmentItems as $key => $item) {
                    try {
                        $subjectId = $item['education_subject_id'];
                        $subject = $EducationSubjects->get($subjectId);
                        $requestData[$this->getAlias()]['assessment_items'][$key]['education_subject'] = $subject;
                    } catch (RecordNotFoundException $e) {
                        // Handle missing subject, maybe log or set another error
                        $requestData['errorMessage'] = 'Subject not found for id ' . $subjectId;
                    }
                }
            } else { //logic to capture error if no subject inside the grade.
                $errorMessage = $this->aliasField('noSubjects');
                $requestData['errorMessage'] = $errorMessage;
            }
        }

    }

    public
    function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $errors = $entity->getErrors();
        if (!empty($errors)) {
            if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
                $this->Alert->error($requestData['errorMessage'], ['reset' => true]);
            }
        }
    }

    public
    function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->AssessmentItems->getAlias(),
            //$this->GradingTypes->getAlias()
        ];
    }

    public
    function onGetExcelTemplate(EventInterface $event, Entity $entity)
    {
        if ($entity->has('excel_template_name')) {
            return $entity->excel_template_name;
        }
    }

    public
    function onUpdateFieldExcelTemplate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'index' || $action == 'view') {
            $attr['type'] = 'string';
        } elseif($action == 'edit') {
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

    public
    function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));

                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;
                $attr['onChangeReload'] = true;

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->academic_period_id;
                $attr['attr']['value'] = $this->AcademicPeriods->get($attr['entity']->academic_period_id)->name;

            }
        }
        return $attr;
    }

    public
    function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
            $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

            $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');

            if ($action == 'add') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('availableProgrammes')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                    ->toArray();

                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';

            } else {
                //since programme_id is not stored, then during edit need to get from grade
                $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
                $attr['type'] = 'readonly';
                $attr['value'] = $programmeId;
                $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
            }
        }
        return $attr;
    }

    public
    function addEditOnChangeEducationProgrammeId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->getQueryParams()['programme']); // Corrected to use getQueryParams()

        // Remove assessment_items from data
        unset($data[$this->getAlias()]['assessment_items']);

        if ($request->is(['post', 'put'])) {
            $requestData = $request->getData();
            if (isset($requestData[$this->getAlias()]['education_programme_id'])) { // Use isset() instead of array_key_exists()
                $selectedProgrammeId = $requestData[$this->getAlias()]['education_programme_id'];
                $request->getQueryParams()['programme'] = $selectedProgrammeId; // Corrected to use getQueryParams()
            }
        }
    }

    public
    function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {

            if ($action == 'add') {

                // $selectedProgramme = $request->getQuery('programme'); //POCOR-7485
                $selectedProgramme = $request->getData('Assessments')['education_programme_id'];
                $gradeOptions = [];
                if (!is_null($selectedProgramme)) {
                    $gradeOptions = $this->EducationGrades
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
                        ->toArray();
                }

                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
            }
        }

        return $attr;
    }

    public function addEditOnChangeEducationGrade(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request; // Use getRequest() method to get the request object
        unset($request->getQueryParams()['grade']); // Use getQueryParams() method

        if ($request->is(['post', 'put'])) {
            $requestData = $request->getData();
            if (array_key_exists($this->getAlias(), $requestData)) {
                if (array_key_exists('education_grade_id', $requestData[$this->getAlias()])) {
                    $selectedGrade = $requestData[$this->getAlias()]['education_grade_id'];
                    $request->getQueryParams()['grade'] = $selectedGrade; // Use getQueryParams() method

                    $assessmentItems = $this->AssessmentItems->populateAssessmentItemsArray($selectedGrade);
                    $data[$this->getAlias()]['assessment_items'] = $assessmentItems;
                }
            }
        }
    }

    public function setupFields(EventInterface $event, Entity $entity)
    {
        $this->field('type', [
            'type' => 'hidden',
            'value' => 2,
            'attr' => ['value' => 2]
        ]);

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
        $this->field('assessment_items', [
            'type' => 'element',
            'element' => 'Assessment.assessment_items'
        ]);

        $this->setFieldOrder([
            'code',
            'name',
            'description',
            'academic_period_id',
            'education_programme_id',
            'education_grade_id',
            'assessment_items'
        ]);
    }

    public
    function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public
    function checkIfHasTemplate($assessmentId = 0)
    {
        $hasTemplate = false;

        if (!empty($assessmentId)) {
            $entity = $this->get($assessmentId);
            $hasTemplate = !empty($entity->excel_template) ? true : false;
        }

        return $hasTemplate;
    }

    public
    function findByClass(Query $query, array $options)
    {
        if (isset($options['institution_class_id']) && !empty($options['institution_class_id'])) {
            $classId = $options['institution_class_id'];
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $classResults = $InstitutionClasses
                ->find()
                ->contain(['ClassGrades'])
                ->where([$InstitutionClasses->aliasField('id') => $classId])
                ->all();

            if (!$classResults->isEmpty()) {
                $where = [];
                $classEntity = $classResults->first();
                $where[$this->aliasField('academic_period_id')] = $classEntity->academic_period_id;

                $gradeIds = [];
                foreach ($classEntity->class_grades as $key => $obj) {
                    $gradeIds[$obj->education_grade_id] = $obj->education_grade_id;
                }
                if (!empty($gradeIds)) {
                    $where[$this->aliasField('education_grade_id IN ')] = $gradeIds;
                }

                if (!empty($where)) {
                    $query->where([$where]);
                }
            }
        }
    }

    public
    function downloadTemplate()
    {
        $filename = 'assessment_report_template';
        $fileType = 'xlsx';
        $filepath = WWW_ROOT . 'export' . DS . 'customexcel' . DS . 'default_templates' . DS . $filename . '.' . $fileType;

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=" . basename($filepath));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($filepath));
        echo file_get_contents($filepath);
        exit(); //POCOR-7027
    }

    public
    function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (isset($entity['assessment_items']) && $this->action == 'edit') {
            $entity['assessment_items'] = array();
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'excel_template') {
            return __('Excel Template');
        } elseif ($field == 'education_programme_id') {
            return __('Education Programme');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    private function setIdEntityFromQueryString(string $class, int $line, Entity $entity): Entity
    {
        $queryString = $this->getQueryString();
                $id = $queryString['id'];
                if (isset($id)) {
                    $this->id = $id;
                    $entity = $this->get($id);
                    $this->entity = $entity;
                }
        return $entity;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            $entityId = $data['id'];
            $queryString = $this->getQueryString();
            $data['id'] = $queryString['id'];
        }
    }
    
    //POCOR-8554
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $associatedRecordsExist = 
            $this->AssessmentPeriods->exists(['assessment_id' => $entity->id]) ;

            //|| $this->AssessmentItems->exists(['assessment_id' => $entity->id]);

        if ($associatedRecordsExist) { 
                $message = __('Delete operation is not allowed as there are other information linked to this record.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
                
                $url = $this->request->referer();
                $event->stopPropagation();
                return $this->controller->redirect($url);
        }
    }

}
