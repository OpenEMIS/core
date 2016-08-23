<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Utility\Text;

use App\Model\Table\ControllerActionTable;

class AssessmentPeriodsTable extends ControllerActionTable {

    public function initialize(array $config) {
        parent::initialize($config);
        
        $this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
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

        $this->belongsToMany('AssessmentItems', [
            'className' => 'Assessment.AssessmentItems',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_period_id',
            'targetForeignKey' => 'assessment_item_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
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
                        $oldCode = $this->get($context['data']['id'])->code;
                        $newCode = $context['data']['code'];
                        return $oldCode != $newCode; //only trigger validation if there is any changes on the code value.
                    }
                ]
            ])
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
            ->add('date_enabled', 'ruleCompareDate', [
                'rule' => ['compareDate', 'date_disabled', true]
            ]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) 
    {
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
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) 
    {
        $query->where([$this->aliasField('assessment_id') => $extra['selectedTemplate']]); //show assessment period based on the selected assessment.
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AssessmentItems.EducationSubjects']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('assessment_items', [
            'type' => 'element',
            'element' => 'Assessment.assessment_periods',
            'attr' => [
                'label' => $this->getMessage('Assessments.subjects')
            ]
        ]);

        $this->controller->set('assessmentGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp

        $this->setFieldOrder([
             'assessment_id', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'weight', 'assessment_items' 
        ]);
    }

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);

		$this->controller->set('assessmentGradingTypeOptions', $this->getGradingTypeOptions()); //send to ctp
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        //patch data to handle fail save because of validation error. this one to complete necessary field needed.
        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('assessment_items', $requestData[$this->alias()])) {
                foreach ($requestData[$this->alias()]['assessment_items'] as $key => $item) {
                    $requestData[$this->alias()]['assessment_items'][$key]['_joinData']['assessment_id'] = $requestData[$this->alias()]['assessment_id'];
                }
            }
        }

        $newOptions = ['associated' => ['AssessmentItems']];

        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)  

    {
        if (!$entity->isNew()) { //for edit
            // can't save properly using associated method
            // until we find a better solution, saving of assessment items grade types will be done in afterSave as of now
            $id = $entity->id;
            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $AssessmentItemsGradingTypes->deleteAll(['assessment_period_id' => $id]);

            if ($entity->has('assessment_items')) {
                $assessmentItems = $entity->assessment_items;
                if (!empty($assessmentItems)) {
                    foreach ($assessmentItems as $assessmentItem) {
                        $query = $AssessmentItemsGradingTypes->find()->where([
                            $AssessmentItemsGradingTypes->aliasField('assessment_item_id') => $assessmentItem->_joinData->assessment_item_id,
                            $AssessmentItemsGradingTypes->aliasField('assessment_id') => $assessmentItem->_joinData->assessment_id,
                            $AssessmentItemsGradingTypes->aliasField('assessment_grading_type_id') => $assessmentItem->_joinData->assessment_grading_type_id,
                            $AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $id
                        ]);

                        if ($query->count() == 0) {
                            $newEntity = $AssessmentItemsGradingTypes->newEntity([
                                'assessment_id' => $assessmentItem->_joinData->assessment_id,
                                'assessment_item_id' => $assessmentItem->_joinData->assessment_item_id,
                                'assessment_grading_type_id' => $assessmentItem->_joinData->assessment_grading_type_id,
                                'assessment_period_id' => $id
                            ]);

                            $AssessmentItemsGradingTypes->save($newEntity);
                        }
                    }
                }
            }
        }
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AssessmentItems->alias(),
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
                $attr['default'] = $selectedPeriod;
                $attr['onChangeReload'] = 'changeAssessmentID';

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->assessment_id;
                $attr['attr']['value'] = $this->Assessments->get($attr['entity']->assessment_id)->name;

            }
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

					$assessmentItems = $this->Assessments->AssessmentItems->getAssessmentItemSubjects($request->data[$this->alias()]['assessment_id']);
                    $data[$this->alias()]['assessment_items'] = $assessmentItems;
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

        if ($action == 'add') {
            if ($periodStartDate <= $todayDate && $periodEndDate >= $todayDate) { //if today's date inside the academic period range, then put today as default value.
                $attr['value'] = $todayDate->format('d-m-Y');
            } else {
                $attr['value'] = $periodStartDate->format('d-m-Y');
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

        if ($action == 'add') {
            if ($periodStartDate <= $todayDate && $periodEndDate >= $todayDate) { //if today's date inside the academic period range, then put today as default value.
                $attr['value'] = $todayDate->format('d-m-Y');
            } else {
                $attr['value'] = $periodEndDate->format('d-m-Y');
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

        $this->field('assessment_items', [
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
            'type' => 'decimal'
        ]);

        $this->setFieldOrder([
             'academic_period_id', 'assessment_id', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'weight', 'assessment_items' 
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

        if (empty($templateOptions) && $this->action == 'index'){ //show no template option on index page only.
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
