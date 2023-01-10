<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
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
    public function initialize(array $config)
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

        $this->addBehavior('Restful.RestfulAccessControl', [
        'Results' => ['index']
        ]);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
        ->requirePresence('academic_period_id')
        ->allowEmpty('weight')
        ->add('weight', 'ruleIsDecimal', [
            'rule' => ['decimal', null],
        ])
        ->add('weight', 'ruleWeightRange', [
            'rule'  => ['range', 0, 2],
            'last' => true
        ])
        ->add('code', [
            'ruleUniqueCodeByForeignKeyAcademicPeriod' => [
                'rule' => ['uniqueCodeByForeignKeyAcademicPeriod', 'Assessments', 'assessment_id',  'academic_period_id'], //($foreignKeyModel, $foreignKeyField, $academicFieldName)
                'on' => function ($context) {
                    if ($this->action == 'edit') { //trigger this only during edit
                        $oldCode = $this->get($context['data']['id'])->code;
                        $newCode = $context['data']['code'];
                        return $oldCode != $newCode; //only trigger validation if there is any changes on the code value.
                    } else if ($this->action == 'add') { //during add, then validation always needed.
                        return true;
                    }
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
                return $query->count() == 0;
            }
        })
        ;
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.editAcademicTerm'] = 'editAcademicTerm';
        return $events;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('academic_term')) {

            if (trim($data['academic_term']) == '') {
                $data['academic_term'] = null;
            }
        }
    }

    public function onGetAssessmentPeriodsElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Name') , __('Start Date'), __('End Date'), __('Academic Term')];
        $assessmentPeriods = $entity->assessment_periods;
        $form = $event->subject()->Form;
        $tableRows = [];

        foreach ($assessmentPeriods as $key => $period) {
            $row = [];
            $row[] = $period->code . ' - ' . $period->name;
            $row[] = $this->formatDate($period->start_date);
            $row[] = $this->formatDate($period->end_date);
            $input = $form->input('assessment_periods.'.$key.'.academic_term', ['label' => false, 'value' => $period->academic_term]);
            $input .= $form->hidden('assessment_periods.'.$key.".id", ['value' => $period->id]);
            $input .= $form->hidden('assessment_periods.'.$key.".assessment_id", ['value' => $period->assessment_id]);
            $row[] = $input;
            $tableRows[] = $row;
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableRows;
        return $event->subject()->renderElement('Assessment.Assessments/assessment_terms', ['attr' => $attr, 'entity' => $entity]);
    }

    public function editAcademicTerm(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->Assessments;
        $request = $mainEvent->subject()->request;
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
        $assessmentId = $request->query('template');
        $academicPeriodId = $request->query('period');
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

            if ($request->is(['post', 'put'])) {
                $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
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
                        $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
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

                    $process = function ($model, $entity) {
                        return $model->save($entity);
                    };

                    $result = $process($model, $entity);


                    if (!$result) {
                        Log::write('debug', $entity->errors());
                    }

                    $errors = $entity->errors();
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        //echo '<pre>'; print_r($event); die;
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->query('period')));
        $extra['selectedPeriod'] = $selectedPeriod;

        list($templateOptions, $selectedTemplate) = array_values($this->getTemplateOptions($selectedPeriod, $this->request->query('template')));
        $extra['selectedTemplate'] = $selectedTemplate;

        $extra['elements']['control'] = [
            'name' => 'Assessment.controls',
            'data' => [
                'periodOptions'=> $periodOptions,
                'selectedPeriod'=> $selectedPeriod,
                'templateOptions'=> $templateOptions,
                'selectedTemplate' => $selectedTemplate
            ],
            'order' => 3
        ];

        $this->field('assessment_id', [
            'visible' => ['index'=>false]
        ]);

        $this->field('weight', [
            'visible' => ['index'=>false]
        ]);

        $this->setFieldOrder([
            'code', 'name', 'academic_term', 'start_date', 'end_date', 'date_enabled', 'date_disabled'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
         $query->contain(['EducationSubjects']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $dateEnabled =  $entity->date_enabled;
        $dateEnabled->format('d-m-Y');  
        $dateDisabled = $entity->date_disabled;
        $dateDisabled->format('d-m-Y');
    }


    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        //disable edit academic term if no period
        if (isset($extra['toolbarButtons']['editAcademicTerm']) && $data->count() < 1) {
            unset($extra['toolbarButtons']['editAcademicTerm']);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
       $this->field('education_subjects', [
            'type' => 'element',
            'element' => 'Assessment.assessment_periods',
            'attr' => [
                'label' => $this->getMessage('Assessments.subjects')
            ]
        ]);

        $this->field('weight', [
            'attr' => [
                'label' => $this->getMessage('Assessments.periodWeight')
            ]
        ]);

        $this->controller->set('assessmentGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp

        $this->setFieldOrder([
             'assessment_id', 'code', 'name', 'academic_term', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'weight', 'education_subjects'
        ]);

        //this is to sort array based on certain value on subarray, in this case based on education order value
        $educationSubjects = $entity->education_subjects;
        usort($educationSubjects, function ($a, $b) {
            return $a['order']-$b['order'];
        } );
        $entity->education_subjects = $educationSubjects;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->action == 'edit') {
            //this is to sort array based on certain value on subarray, in this case based on education order value
            $educationSubjects = $entity->education_subjects;
            usort($educationSubjects, function ($a, $b) {
                return $a['order']-$b['order'];
            } );
            $entity->education_subjects = $educationSubjects;
        }

        $this->setupFields($entity);
        $this->controller->set('assessmentGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //patch data to handle fail save because of validation error. this one to complete necessary field needed.
        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('education_subjects', $requestData[$this->alias()])) {
                foreach ($requestData[$this->alias()]['education_subjects'] as $key => $item) {
                    $requestData[$this->alias()]['education_subjects'][$key]['_joinData']['assessment_id'] = $requestData[$this->alias()]['assessment_id'];
                }
            }
        }

        $newOptions = ['associated' => ['EducationSubjects']];
        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);
    }
    
    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $options){
        
        if (!$entity->isNew()) { //for edit
            $id = $entity->id;
            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $AssessmentItemsGradingTypes->deleteAll(['assessment_period_id' => $id]);

            if ($entity->has('education_subjects')) {
                $educationSubjects = $entity->education_subjects;

                if (!empty($educationSubjects)) {
                    foreach ($educationSubjects as $educationSubject) {
                        $query = $AssessmentItemsGradingTypes->find()->where([
                            $AssessmentItemsGradingTypes->aliasField('education_subject_id') => $educationSubject->_joinData->assessment_item_id,
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
    
//    public function editAfterSave(Event $event, Entity $entity, ArrayObject $options) //cant use afterSave because it wont be detected by unit test case.
//    {
//        if (!$entity->isNew()) { //for edit
//            // can't save properly using associated method
//            // until we find a better solution, saving of assessment items grade types will be done in afterSave as of now
//            $id = $entity->id;
//            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
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

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->EducationSubjects->alias(),
            $this->GradingTypes->alias()
        ];
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->query('period')));

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

    public function addEditOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldAssessmentId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->query('period')));

        list($templateOptions, $selectedTemplate) = array_values($this->getTemplateOptions($selectedPeriod, $this->request->query('template')));

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

    public function onUpdateFieldAcademicTerm(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (array_key_exists('template', $request->query)) { //if all academic term is null, then hide
                $query = $this
                        ->find()
                        ->where([
                            $this->aliasField('assessment_id') => $request->query['template'],
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

    public function addEditOnChangeAssessmentId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        //remove default validation because of foreign key
        $options['associated'] = [
            'AssessmentItemsGradingTypes' => ['validate' => false]
        ];

        $request = $this->request;
        unset($request->query['template']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('assessment_id', $request->data[$this->alias()])) {
                    $request->query['template'] = $request->data[$this->alias()]['assessment_id'];

                    $educationSubjects = $this->Assessments->AssessmentItems->getAssessmentItemSubjects($request->data[$this->alias()]['assessment_id']);
                    $data[$this->alias()]['education_subjects'] = $educationSubjects;
                }
            }
        }
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->query('period')));

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

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->Assessments->getAcademicPeriodOptions($this->request->query('period')));

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
             'academic_period_id', 'assessment_id', 'code', 'name', 'academic_term', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'weight', 'education_subjects'
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
        $assessmentGradingType = TableRegistry::get('Assessment.AssessmentGradingTypes');
        $assessmentGradingTypeOptions = $assessmentGradingType->find('list')->toArray();
        return $assessmentGradingTypeOptions;
    }
}
