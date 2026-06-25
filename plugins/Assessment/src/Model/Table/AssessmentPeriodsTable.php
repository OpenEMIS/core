<?php

namespace Assessment\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Utility\Text;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class AssessmentPeriodsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentItemsGradingTypes', ['className' => 'Assessment.AssessmentItemsGradingTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);

        $this->belongsToMany('GradingTypes', [
            'className' => 'Assessment.AssessmentGradingTypes',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_period_id',
            'targetForeignKey' => 'assessment_grading_type_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_period_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('AssessmentPeriodExcludedSecurityRoles', ['className' => 'Assessment.AssessmentPeriodExcludedSecurityRoles', 'foreignKey' => 'assessment_period_id']); //POCOR-7400
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index']
        ]);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('academic_period_id')
            ->allowEmpty('weight')
            ->add('weight', 'ruleIsDecimal', [
                'rule' => ['decimal', null],
            ])
            ->add('weight', 'ruleWeightRange', [
                'rule' => ['range', 0, 2],
                'last' => true
            ])
            ->requirePresence('assessment_id')
            ->requirePresence('name')
            ->add('code', [
                'ruleUniqueCodeByForeignKeyAcademicPeriod' => [
                    'rule' => ['uniqueCodeByForeignKeyAcademicPeriod', 'Assessments', 'assessment_id', 'academic_period_id'],
                    'on' => function ($context) {
                        if ($this->action == 'edit') {
                            $oldCode = $this->get($context['data']['id'])->code;
                            $newCode = $context['data']['code'];
                            return $oldCode != $newCode;
                        } else if ($this->action == 'add') {
                            return true;
                        }
                        return false; // Ensure the callback always returns a boolean
                    }
                ]
            ])
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
            ->add('date_enabled', 'ruleCompareDate', [
                'rule' => ['compareDate', 'date_disabled', true]
            ])
            ->allowEmpty('academic_term', function ($context) {
                if (array_key_exists('assessment_id', $context['data'])) {
                    $query = $this
                        ->find()
                        ->where([
                            $this->aliasField('assessment_id') => $context['data']['assessment_id'],
                            $this->aliasField('academic_term IS NOT NULL')
                        ]);
                    return $query->count() == 0; // This will return true or false
                }
                return true; // Default to true if 'assessment_id' is not in context['data']
            });
    }

    public function findUniqueAssessmentTerms(Query $query, array $options)
    {
        return $query
            ->distinct('academic_term')
            ->where([$this->aliasField('academic_term IS NOT NULL')])
            ->formatResults(function ($results) {
                $results = $results->toArray();
                $returnArr = [];
                foreach ($results as $result) {
                    $returnArr[] = ['id' => $result['academic_term'], 'name' => $result['academic_term']];
                }
                return $returnArr;
            });
    }

    public function findAcademicTerm(Query $query, array $options)
    {
        if (isset($options['academic_term'])) {
            $query = $query->where([$this->aliasField('academic_term') => $options['academic_term']]);
        }
        return $query;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.editAcademicTerm'] = 'editAcademicTerm';
        return $events;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('academic_term')) {

            if (trim($data['academic_term']) == '') {
                $data['academic_term'] = null;
            }
        }
    }

    //Start:POCOR-7387
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        if (preg_match('/\ & \b/', $entity['academic_term'])) {
            $this->Alert->warning('general.specialChar', ['reset' => true]);
            return false;
        }
        if (preg_match('/\&\b/', $entity['academic_term'])) {
            $this->Alert->warning('general.specialChar', ['reset' => true]);
            return false;
        }
    }

    //End:POCOR-7387

    public function onGetAssessmentPeriodsElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Name'), __('Start Date'), __('End Date'), __('Academic Term')];
        $assessmentPeriods = $entity->assessment_periods;
        $form = $event->getSubject()->Form;
        $tableRows = [];

        foreach ($assessmentPeriods as $key => $period) {
            $row = [];
            $row[] = $period->code . ' - ' . $period->name;
            $row[] = $this->formatDate($period->start_date);
            $row[] = $this->formatDate($period->end_date);
            $input = $form->input('assessment_periods.' . $key . '.academic_term', ['label' => false, 'value' => $period->academic_term]);
            $input .= $form->hidden('assessment_periods.' . $key . ".id", ['value' => $period->id]);
            $input .= $form->hidden('assessment_periods.' . $key . ".assessment_id", ['value' => $period->assessment_id]);
            $row[] = $input;
            $tableRows[] = $row;
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableRows;
        return $event->getSubject()->renderElement('Assessment.Assessments/assessment_terms', ['attr' => $attr, 'entity' => $entity]);
    }

    public function editAcademicTerm(EventInterface $mainEvent, ArrayObject $extra)
    {
        $model = $this->Assessments;
        $request = $mainEvent->getSubject()->request;
        $extra['config']['form'] = true;
        $extra['elements']['editAcademicTerm'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $extra['toolbarButtons']['back'] = [
            'url' => $this->url('index', 'QUERY'),
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Back')
            ]
        ];
        $extra['patchEntity'] = true;

        // Before action logic
        $assessmentId = $this->request->getQuery('template');
        $academicPeriodId = $this->request->getQuery('period');
        $entity = false;

        if ($model->exists($assessmentId)) {
            $entity = $model->get($assessmentId, ['contain' => ['AcademicPeriods', 'AssessmentPeriods']]);
        } else {
            $mainEvent->stopPropagation();
            $this->controller->redirect($model->url('view'));
        }

        foreach ($this->fields as $key => $value) {
            unset($this->fields[$key]);
        }
        $this->field('assessment_id', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->code_name, 'required' => true]]);
        $this->field('academic_period_id', ['type' => 'disabled', 'attr' => ['value' => $entity->academic_period->name]]);
        $this->field('assessment_periods', ['attr' => ['required' => true], 'type' => 'assessment_periods', 'valueClass' => 'table-full-width']);

        if ($entity) {

            if ($this->request->is(['post', 'put'])) {
                $submit = $this->request->getData('submit') !== null ? $this->request->getData('submit') : 'save';
                $patchOptions = new ArrayObject(['validate' => false, 'associated' => ['AssessmentPeriods' => ['validate' => false]]]);
                if ($submit == 'save') {

                    //logic to check if all empty / filled based on the 1st field.
                    $emptyMode = false;
                    foreach ($request->data['assessment_periods'] as $key => $value) {
                        if (empty($value['academic_term'])) {
                            $emptyMode = true;
                        }
                        break;
                    }

                    $patchOptionsArray = $patchOptions->getArrayCopy();

                    if ($extra['patchEntity']) {
                        $entity = $model->patchEntity($entity, $this->request->getData(), $patchOptionsArray);
                    }

                    foreach ($entity->assessment_periods as $key => $value) {
                        if ($emptyMode) {
                            if (!empty($value->academic_term)) {
                                $entity->assessment_periods[$key]->errors('academic_term', [__('Please remove an academic term for this record')]);
                            }
                        } else {
                            if (empty($value->academic_term)) {
                                $entity->assessment_periods[$key]->errors('academic_term', [__('Please enter an academic term for this record')]);
                            }
                        }
                    }
                    //POCOR-8814[START]
                    if(!isset($entity->id)){
                        $entity->id = $assessmentId;
                    }
                    //POCOR-8814[END]
                    $process = function ($model, $entity) {
                        return $model->save($entity);
                    };

                    $result = $process($model, $entity);


                    if (!$result) {
                        Log::write('debug', (string)$entity->getErrors());
                    }

                    $errors = $entity->getErrors();
                    if (empty($errors)) {
                        $this->Alert->success('general.edit.success');
                    } else {
                        $this->Alert->error('general.edit.failed');
                        if (isset($errors['assessment_periods']['ruleNotEmptyAcademicTerm'])) {
                            $this->Alert->error('Assessments.academic_term', ['reset' => true]);
                        }
                    }

                    if ($result) {
                        $mainEvent->stopPropagation();
                        return $this->controller->redirect($this->url('index'));
                    }
                }
            }
            $this->controller->set('data', $entity);
        }

        if (!$entity) {
            $mainEvent->stopPropagation();
            return $this->controller->redirect($this->url('index', 'QUERY'));
        } elseif (empty($entity->assessment_periods)) {
            $mainEvent->stopPropagation();
            return $this->controller->redirect($this->url('index', 'QUERY'));
        }
        $this->controller->set('data', $entity);
        return $entity;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($serverRequest->getQuery('period')));
        $extra['selectedPeriod'] = $selectedPeriod;

        list($templateOptions, $selectedTemplate) = array_values($this->getTemplateOptions($selectedPeriod, $serverRequest->getQuery('template')));
        $extra['selectedTemplate'] = $selectedTemplate;

        $extra['elements']['control'] = [
            'name' => 'Assessment.controls',
            'data' => [
                'periodOptions' => $periodOptions,
                'selectedPeriod' => $selectedPeriod,
                'templateOptions' => $templateOptions,
                'selectedTemplate' => $selectedTemplate
            ],
            'order' => 3
        ];

        $this->field('assessment_id', [
            'visible' => ['index' => false]
        ]);

        $this->field('editable_student_statuses', [//POCOR-7550
            'visible' => ['index' => false]
        ]);
        $this->field('weight', [
            'visible' => ['index' => false]
        ]);
        $this->setFieldOrder([
            'code', 'name', 'academic_term', 'start_date', 'end_date', 'date_enabled', 'date_disabled'
        ]);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'Assessment Periods', 'Assessments');
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
        // echo '<pre>'; print_r($event); die;
        $query->where([$this->aliasField('assessment_id') => $extra['selectedTemplate']]); //show assessment period based on the selected assessment.
        if ($extra['selectedTemplate'] != 'empty') {
            $extra['toolbarButtons']['editAcademicTerm'] = [
                'url' => [
                    'plugin' => 'Assessment',
                    'controller' => 'Assessments',
                    'action' => 'AssessmentPeriods',
                    '0' => 'editAcademicTerm',
                    'template' => $extra['selectedTemplate'],
                    'period' => $extra['selectedPeriod']
                ],
                'type' => 'button',
                'label' => '<i class="kd-edit"></i>',
                'attr' => [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'title' => __('Edit Academic Term')
                ]
            ];
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationSubjects']);
        //POCOR-7400 start
        $query->contain(['AssessmentPeriodExcludedSecurityRoles']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $arr = [];
                foreach ($row->assessment_period_excluded_security_roles as $key => $role) {
                    $arr[$key] = ['id' => $role['security_role_id']];
                }
                $row['excluded_security_roles'] = $arr;

                return $row;
            });
        });
        //POCOR-7400 end

    }

    public function editAfterAction(EventInterface $event, Entity $entity)
    {
        $dateEnabled = $entity->date_enabled;
        $dateEnabled->format('d-m-Y');
        $dateDisabled = $entity->date_disabled;
        $dateDisabled->format('d-m-Y');
    }


    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        //disable edit academic term if no period
        //POCOR-8814[START]
        if (isset($extra['toolbarButtons']['editAcademicTerm']) && $data->count() < 1) {
            unset($extra['toolbarButtons']['editAcademicTerm']);
        }
        //POCOR-8814[END]
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $getAssessment_id = $this->request->getAttribute('params')['pass'][1];
        $entityId = $this->ControllerAction->paramsDecode($getAssessment_id)['id'];
        $checNewSubjecAdded = $this->gradingSubjectAdd($entity, $entityId);//POCOR-7322
        $this->field('education_subjects', [
            'type' => 'element',
            'element' => 'Assessment.assessment_periods',
            'attr' => [
                'label' => $this->getMessage('Assessments.subjects')
            ]
        ]);
        $this->field('excluded_security_roles');//POCOR-7400
        $this->field('editable_student_statuses');//POCOR-7400
        $this->field('weight', [
            'attr' => [
                'label' => $this->getMessage('Assessments.periodWeight')
            ]
        ]);

        $this->controller->set('assessmentGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp

        $this->setFieldOrder([
            'assessment_id', 'code', 'name', 'academic_term', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'excluded_security_roles', 'editable_student_statuses', 'weight', 'education_subjects'
        ]);

        //this is to sort array based on certain value on subarray, in this case based on education order value
        $educationSubjects = $entity->education_subjects;
        usort($educationSubjects, function ($a, $b) {
            return $a['order'] - $b['order'];
        });
        $entity->education_subjects = $educationSubjects;
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->action == 'edit') {
            //this is to sort array based on certain value on subarray, in this case based on education order value
            $educationSubjects = $entity->education_subjects;
            usort($educationSubjects, function ($a, $b) {
                return $a['order'] - $b['order'];
            });
            $entity->education_subjects = $educationSubjects;
        }

        $this->setupFields($entity);
        $this->controller->set('assessmentGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp
    }

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //patch data to handle fail save because of validation error. this one to complete necessary field needed.

        if ($requestData->offsetExists($this->getAlias())) {
            $aliasData = $requestData[$this->getAlias()];

            if (isset($aliasData['education_subjects'])) {
                foreach ($aliasData['education_subjects'] as $key => $item) {
                    $requestData[$this->getAlias()]['education_subjects'][$key]['_joinData']['assessment_id'] = $aliasData['assessment_id'];
                }
                $this->request = $this->request->withData($this->getAlias(), $requestData[$this->getAlias()]); //POCOR-8520
            }
        }

        $newOptions = ['associated' => ['EducationSubjects']];
        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);
    }

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $getAssessment_id = $this->request->getAttribute('params')['pass'][1];
        $entityId = $this->ControllerAction->paramsDecode($getAssessment_id)['id'];
        $checNewSubjecAdded = $this->gradingSubjectAdd($entity, $entityId); //POCOR-7322
        if (!$entity->isNew()) { //for edit
            $id = $entityId;
            $AssessmentItemsGradingTypes = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
            $AssessmentItemsGradingTypes->deleteAll(['assessment_period_id' => $id]);

            if ($entity->has('education_subjects')) {
                $educationSubjects = $entity->education_subjects;

                if (!empty($educationSubjects)) {
                    foreach ($educationSubjects as $educationSubject) {
                        $query = $AssessmentItemsGradingTypes->find()->where([
                            $AssessmentItemsGradingTypes->aliasField('education_subject_id') => $educationSubject->_joinData->education_subject_id, //POCOR-8520
                            $AssessmentItemsGradingTypes->aliasField('assessment_id') => $educationSubject->_joinData->assessment_id,
                            $AssessmentItemsGradingTypes->aliasField('assessment_grading_type_id') => $educationSubject->_joinData->assessment_grading_type_id,
                            $AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $id
                        ]);

                        if ($query->count() == 0) {
                            $newEntity = $AssessmentItemsGradingTypes->newEntity([
                                'assessment_id' => $educationSubject->_joinData->assessment_id,
                                'education_subject_id' => $educationSubject->_joinData->education_subject_id,
                                'assessment_grading_type_id' => $educationSubject->_joinData->assessment_grading_type_id,
                                'assessment_period_id' => $id
                            ]);

                            $AssessmentItemsGradingTypes->save($newEntity);
                        }
                    }
                }
            }
        }

        unset($entity->education_subjects);
    }

//    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $options) //cant use afterSave because it wont be detected by unit test case.
//    {
//        if (!$entity->isNew()) { //for edit
//            // can't save properly using associated method
//            // until we find a better solution, saving of assessment items grade types will be done in afterSave as of now
//            $id = $entity->id;
//            $AssessmentItemsGradingTypes = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
//            $AssessmentItemsGradingTypes->deleteAll(['assessment_period_id' => $id]);
//
//            if ($entity->has('education_subjects')) {
//                $educationSubjects = $entity->education_subjects;
//                if (!empty($educationSubjects)) {
//                    foreach ($educationSubjects as $educationSubject) {
//                        $query = $AssessmentItemsGradingTypes->find()->where([
//                            $AssessmentItemsGradingTypes->aliasField('education_subject_id') => $educationSubject->_joinData->assessment_item_id,
//                            $AssessmentItemsGradingTypes->aliasField('assessment_id') => $educationSubject->_joinData->assessment_id,
//                            $AssessmentItemsGradingTypes->aliasField('assessment_grading_type_id') => $educationSubject->_joinData->assessment_grading_type_id,
//                            $AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $id
//                        ]);
//
//                        if ($query->count() == 0) {
//                            $newEntity = $AssessmentItemsGradingTypes->newEntity([
//                                'assessment_id' => $educationSubject->_joinData->assessment_id,
//                                'education_subject_id' => $educationSubject->_joinData->education_subject_id,
//                                'assessment_grading_type_id' => $educationSubject->_joinData->assessment_grading_type_id,
//                                'assessment_period_id' => $id
//                            ]);
//
//                            $AssessmentItemsGradingTypes->save($newEntity);
//                        }
//                    }
//                }
//            }
//        }
//    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->EducationSubjects->getAlias(),
            $this->GradingTypes->getAlias()
        ];
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->getQuery('period')));

        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;
                $attr['onChangeReload'] = 'changeAcademicPeriodID';
            } else {
                $assessment = $this->Assessments->get($attr['entity']->assessment_id);

                $attr['type'] = 'readonly';
                $attr['value'] = $assessment->academic_period_id;
                $attr['attr']['value'] = $this->Assessments->AcademicPeriods->get($assessment->academic_period_id)->name;
            }
        }

        return $attr;
    }

    public function addEditOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        $request = $request->withQueryParams($request->getQueryParams());
        unset($request->getQueryParams()['period']);


        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period_id', $request->getData()[$this->getAlias()])) {
                    $academicPeriodId = $request->getData()[$this->getAlias()]['academic_period_id'];
                    $this->request = $request->withQueryParams(['period' => $academicPeriodId]);

                }
            }
        }
    }

    public function onUpdateFieldAssessmentId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->getQuery('period')));

        list($templateOptions, $selectedTemplate) = array_values($this->getTemplateOptions($selectedPeriod, $this->request->getQuery('template')));

        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $attr['options'] = $templateOptions;
                $attr['onChangeReload'] = 'changeAssessmentID';
            } else {
                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->assessment_id;
                $attr['attr']['value'] = $this->Assessments->get($attr['entity']->assessment_id)->name;
            }
        }

        return $attr;
    }

    public function onUpdateFieldAcademicTerm(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (array_key_exists('template', $request->getQuery())) { //if all academic term is null, then hide
                $query = $this
                    ->find()
                    ->where([
                        $this->aliasField('assessment_id') => $request->getQuery('template'),
                        $this->aliasField('academic_term IS NOT NULL')
                    ])
                    ->count();

                if ($query < 1) {
                    $attr['visible'] = false;
                }
            }
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function addEditOnChangeAssessmentId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        //remove default validation because of foreign key
        $options['associated'] = [
            'AssessmentItemsGradingTypes' => ['validate' => false]
        ];

        $request = $this->request;
        $queryParams = $request->getQueryParams();
        unset($queryParams['template']);
        $request = $request->withQueryParams($queryParams);


        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('assessment_id', $request->getData()[$this->getAlias()])) {
                    //$request->getQuery['template'] = $request->getData()[$this->getAlias()]['assessment_id'];
                    $queryParams['template'] = $request->getData()[$this->getAlias()]['assessment_id'];
                    $this->request = $request->withQueryParams($queryParams);

                    $educationSubjects = $this->Assessments->AssessmentItems->getAssessmentItemSubjects($request->getData()[$this->getAlias()]['assessment_id']);
                    $data[$this->getAlias()]['education_subjects'] = $educationSubjects;
                }
            }
        }
    }

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->getQuery('period')));

        $academicPeriod = $this->Assessments->AcademicPeriods->get($selectedPeriod);
        $periodStartDate = $academicPeriod->start_date;
        $periodEndDate = $academicPeriod->end_date;

        $todayDate = Time::now();

        if (!$request->is(['post', 'put'])) { //only apply before user submit / validation
            if ($action == 'add') {
                if ($periodStartDate <= $todayDate && $periodEndDate >= $todayDate) { //if today's date inside the academic period range, then put today as default value.
                    $attr['value'] = $todayDate->format('d-m-Y');
                } else {
                    $attr['value'] = $periodStartDate->format('d-m-Y');
                }
            }
        }

        $attr['date_options'] = ['startDate' => $periodStartDate->format('d-m-Y'), 'endDate' => $periodEndDate->format('d-m-Y')];
        $attr['date_options']['todayBtn'] = false; //since we limit the start date, should as well hide the 'today' button so no extra checking function needed

        return $attr;
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->getQuery('period')));

        $academicPeriod = $this->Assessments->AcademicPeriods->get($selectedPeriod);
        $periodStartDate = $academicPeriod->start_date;
        $periodEndDate = $academicPeriod->end_date;

        $todayDate = Time::now();

        if (!$request->is(['post', 'put'])) { //only apply before user submit / validation
            if ($action == 'add') {
                if ($periodStartDate <= $todayDate && $periodEndDate >= $todayDate) { //if today's date inside the academic period range, then put today as default value.
                    $attr['value'] = $todayDate->format('d-m-Y');
                } else {
                    $attr['value'] = $periodEndDate->format('d-m-Y');
                }
            }
        }

        $attr['date_options'] = ['startDate' => $periodStartDate->format('d-m-Y'), 'endDate' => $periodEndDate->format('d-m-Y')];
        $attr['date_options']['todayBtn'] = false; //since we limit the start date, should as well hide the 'today' button so no extra checking function needed

        return $attr;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->field('assessment_id', [
            'type' => 'select',
            'attr' => [
                'label' => $this->getMessage('Assessments.templates')
            ],
            'entity' => $entity
        ]);

        $this->field('academic_term');

        $this->field('education_subjects', [
            'type' => 'element',
            'element' => 'Assessment.assessment_periods',
            'attr' => [
                'label' => $this->getMessage('Assessments.subjects')
            ]
        ]);

        $this->field('start_date');
        $this->field('end_date');

        $this->field('date_enabled');
        $this->field('date_disabled');

        $this->field('weight', [
            'type' => 'decimal',
            'attr' => [
                'label' => $this->getMessage('Assessments.periodWeight')
            ]
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'assessment_id', 'code', 'name', 'academic_term', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'excluded_security_roles', 'editable_student_statuses', 'weight', 'education_subjects'
        ]);
    }

    public function getTemplateOptions($period, $templateQuerystring)
    {
        $templateOptions = $this->Assessments
            ->find('list')
            ->where([
                $this->Assessments->aliasField('academic_period_id') => $period
            ])
            ->order([$this->Assessments->aliasField('created') => 'DESC'])
            ->toArray();

        if (empty($templateOptions) && $this->action == 'index') { //show no template option on index page only.
            $templateOptions['empty'] = $this->getMessage('Assessments.noTemplates');
        }

        if ($templateQuerystring) {
            $selectedTemplate = $templateQuerystring;
        } else {
            $selectedTemplate = key($templateOptions);
        }

        return compact('templateOptions', 'selectedTemplate');
    }

    public function getGradingTypeOptions()
    {
        $assessmentGradingType = TableRegistry::getTableLocator()->get('Assessment.AssessmentGradingTypes');
        $assessmentGradingTypeOptions = $assessmentGradingType->find('list')->toArray();
        return $assessmentGradingTypeOptions;
    }


    //POCOR=7322
    public function gradingSubjectAdd($entity, $entityId)
    {
        $assesmentPeriod = $entityId;
        $assessmentId = $entity->assessment_id;
        $currentTimeZone = date("Y-m-d H:i:s");
        $AssessmentItemsGradingTypes = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
        $assessmentItems = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
        $checkAssessment = $assessmentItems->find()->where([$assessmentItems->aliasField('assessment_id') => $assessmentId])->count();
        $checkGrading = $AssessmentItemsGradingTypes->find()->where([$AssessmentItemsGradingTypes->aliasField('assessment_id IS') => $assessmentId, $AssessmentItemsGradingTypes->aliasField('assessment_period_id IS') => $assesmentPeriod])->count();
        if ($checkAssessment != $checkGrading && $checkAssessment > $checkGrading) {
            $getRecord = $checkAssessment - $checkGrading;
            $assessment_grading_type_id = $AssessmentItemsGradingTypes->find()->where([$AssessmentItemsGradingTypes->aliasField('assessment_id') => $assessmentId, $AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $assesmentPeriod])->first()->assessment_grading_type_id;
            $assessment = $assessmentItems->find()
                    ->select([
                        'assessment_id' => $assessmentItems->aliasField('assessment_id'),
                        'education_subject' => $assessmentItems->aliasField('education_subject_id') // Corrected field name
                    ])
                    ->where([$assessmentItems->aliasField('assessment_id') => $assessmentId])
                    ->order([$assessmentItems->aliasField('created') => 'DESC'])
                    ->limit($getRecord)
                    ->toArray();
            foreach ($assessment as $val) {
                $id_id = Text::uuid();
                $assessmentId_id = $val->assessment_id;
                $education_subjectId = $val->education_subject;
                $data = [
                    'id' => $id_id,
                    'education_subject_id' => $education_subjectId,
                    'assessment_grading_type_id' => $assessment_grading_type_id,
                    'assessment_id' => $assessmentId_id,
                    'assessment_period_id' => $assesmentPeriod,
                    'created_user_id' => 1,
                    'created' => $currentTimeZone,
                ];
                $entity = $AssessmentItemsGradingTypes->newEntity($data);
                $save = $AssessmentItemsGradingTypes->save($entity);

            }
        }
    }

    //POCOR-7400 start
    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $SecurityRoleOptions = $SecurityRoles->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        $tooltipMessage = "The security roles chosen here will not be affected by the date enabled and date disabled.";
        $this->field('excluded_security_roles', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => [
                    'text' => __('Excluded Security Roles') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue"  tooltip-placement="bottom" uib-tooltip="' . $tooltipMessage . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false,
                    'class' => 'tooltip-desc'
                ]
            ]]);
        $this->fields['excluded_security_roles']['options'] = $SecurityRoleOptions;
        $this->field('editable_student_statuses', ['type' => 'select']);//POCOR-7550
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {

        $table = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $entityData = [];//POCOR-7550
        if (empty($entity->id)) {//POCOR-7550
            $entityData = $table->find()->where([$table->aliasField('code') => $entity->code,
                $table->aliasField('assessment_id') => $entity->assessment_id,
                $table->aliasField('academic_term') => $entity->academic_term,
            ]);
        }//POCOR-7550
        else {
            $entityData = $entity;
        }//POCOR-7550

        $AssessmentPeriodExcludedSecurityRolesTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriodExcludedSecurityRoles');

        if ($this->request->getParam('pass')[0] == 'edit') {

            $ExcludedSecurityRoleData = $AssessmentPeriodExcludedSecurityRolesTable->find()->where(['assessment_period_id' => $entityData->id])->toArray();
            if ($ExcludedSecurityRoleData) {
                foreach ($ExcludedSecurityRoleData as $ExcludedSecurityRoleEntity) {
                    $deleteEntity = $AssessmentPeriodExcludedSecurityRolesTable->delete($ExcludedSecurityRoleEntity);
                }
            }
        }

        foreach ($entity->excluded_security_roles['_ids'] as $one) {

            $ExcludedSecurityRoleEntity = ['assessment_period_id' => $entityData->id,
                'security_role_id' => $one
            ];
            $ExcludedSecurityRoles = $AssessmentPeriodExcludedSecurityRolesTable->newEntity($ExcludedSecurityRoleEntity);
            $ExcludedSecurityRoleResult = $AssessmentPeriodExcludedSecurityRolesTable->save($ExcludedSecurityRoles);

        }
    }

    public function onGetExcludedSecurityRoles(EventInterface $event, Entity $entity)
    {
        $table = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $obj = [];
        if ($entity->has('excluded_security_roles')) {

            foreach ($entity->excluded_security_roles as $role) {
                $res = $table->find('list')->where(['id' => $role['id']])->first();
                $obj[] = $res;
            }
        }

        $values = !empty($obj) ? implode(', ', $obj) : __('No Excluded Security Roles ');
        return $values;
    }
    //POCOR-7400 end
    //POCOR-7550 start
    public function onUpdateFieldEditableStudentStatuses(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = ['Enrolled', 'All Statuses'];
        $attr['onChangeReload'] = 'changeCurrent';

        return $attr;
    }

    //POCOR-8266
    public function onUpdateFieldWeight(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';

            return $attr;
        }
    }

    //POCOR-8554
    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        $associatedModels = [
            'AssessmentItemResults',
            'AssessmentItemsGradingTypes',
            'AssessmentPeriodExcludedSecurityRoles'
        ];

        foreach ($associatedModels as $model) {
            if ($this->{$model}->exists(['assessment_period_id' => $entity->id])) {
                $message = __('Delete operation is not allowed as there are other information linked to this record.');
                $this->Alert->error($message, ['type' => 'string', 'reset' => true]);

                $url = $this->request->referer();
                $event->stopPropagation();
                return $this->controller->redirect($url);
            }
        }
    }



}
