<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\OptionsTrait;

class AssessmentGradingTypesTable extends ControllerActionTable {
	use MessagesTrait;
	use OptionsTrait;

	public function initialize(array $config): void {
		parent::initialize($config);

		$this->hasMany('GradingOptions', ['className' => 'Assessment.AssessmentGradingOptions', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => 'assessment_grading_type_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

		$this->belongsToMany('AssessmentPeriods', [
			'className' => 'Assessment.AssessmentPeriods',
			'joinTable' => 'assessment_items_grading_types',
			'foreignKey' => 'assessment_grading_type_id',
			'targetForeignKey' => 'assessment_period_id',
			'through' => 'Assessment.AssessmentItemsGradingTypes',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

		$this->belongsToMany('Assessments', [
			'className' => 'Assessment.Assessments',
			'joinTable' => 'assessment_items_grading_types',
			'foreignKey' => 'assessment_grading_type_id',
			'targetForeignKey' => 'assessment_id',
			'through' => 'Assessment.AssessmentItemsGradingTypes',
			'dependent' => true,
			'cascadeCallbacks' => true
		]);

		$this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index']
        ]);

		$this->setDeleteStrategy('restrict');
	}

	public function validationDefault(Validator $validator): Validator {
		$validator->setProvider('custom', $this);
		$validator = parent::validationDefault($validator);

		return $validator
			->allowEmpty('code')
			->add('code', 'ruleUniqueCode', [
			    'rule' => ['checkUniqueCode', null]
			])
			->add('pass_mark', [
				'ruleNotMoreThanMax' => [
			    	'rule' => ['checkMinNotMoreThanMax'],
				],
				'ruleIsDecimal' => [
				    'rule' => ['decimal', null],
				],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99]
                ]
			])
			->add('max', [
                'ruleIsDecimal' => [
                    'rule' => ['decimal', null],
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99]
                ]
            ])
			->requirePresence('name')
			->requirePresence('result_type')
			->requirePresence('grading_options');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('result_type', ['type' => 'select', 'options' => $this->getSelectOptions($this->aliasField('result_type'))]);
		$this->field('max', ['length' => 7, 'attr' => ['min' => 0]]);
		$this->field('pass_mark', ['length' => 7, 'attr' => ['min' => 0]]);
		$this->field('grading_options', [
			'type' => 'element',
			'element' => 'Assessment.Gradings/grading_options',
			'visible' => ['view'=>true, 'edit'=>true],
			'fields' => $this->GradingOptions->fields,
			'formFields' => []
		]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->setFieldOrder(['visible', 'code', 'name', 'result_type', 'max', 'pass_mark']);

		// Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Grading Types','Assessments');
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


/******************************************************************************************************************
**
** addEdit action events
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
		if ($this->action=='edit') {
			$this->fields['visible']['visible'] = false;
		}
		$this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields());

		$this->setFieldOrder([
			'code', 'name', 'result_type', 'max', 'pass_mark', 'grading_options',
		]);
	}

	public function addEditAfterAction (Event $event, Entity $entity, ArrayObject $extra)
	{
		// $gradingOptions will contain the GradeOptionId and the association.(1 for true and 0 for false)
		$AssessmentGradingOptions = TableRegistry::get('Assessment.AssessmentGradingOptions');
		$gradingOptions = [];
		if (!is_null($entity->grading_options)) {
			foreach ($entity->grading_options as $key => $gradingOption) {
				$gradingOptionId = $gradingOption->id;
				$gradingOptions[$gradingOptionId] = 0;
				if (!empty($gradingOptionId) && $this->hasAssociatedRecords($AssessmentGradingOptions, $gradingOption, $extra)) {
					$gradingOptions[$gradingOptionId] = 1;
				}
			}
		}

		// to passed the array of the association to the view (grading_options.php).
		$this->controller->set('gradingOptions', $gradingOptions);
	}

   public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->getAlias()]['grading_options']) || empty($data[$this->getAlias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        }
    }

	public function addEditOnReload(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {
		$groupOptionData = $this->GradingOptions->getFormFields();
		if (!empty($entity->id)) {
			$groupOptionData['assessment_grading_type_id'] = $entity->id;
		}
		$newGroupOption = $this->GradingOptions->newEmptyEntity($groupOptionData);
		$requestData[$this->getAlias()]['grading_options'][] = $newGroupOption->toArray();
		$newOptions = [$this->GradingOptions->getAlias() => ['validate'=>false]];
		if (isset($patchOptions['associated'])) {
			$patchOptions['associated'] = array_merge($patchOptions['associated'], $newOptions);
		} else {
			$patchOptions['associated'] = $newOptions;
		}
	}


/******************************************************************************************************************
**
** edit action events
**
******************************************************************************************************************/
	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->getAlias()]['grading_options']) || empty($data[$this->getAlias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        }
    }

    //POCOR 8001 starts
    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $extra['excludedModels'] = [ //this will exclude checking during remove restrict
            $this->GradingOptions->getAlias()
        ]; // POCOR 8009
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
	{
		// get the array of the original gradeOptions
		$AssessmentGradingOptions = TableRegistry::get('Assessment.AssessmentGradingOptions');
		$query = $AssessmentGradingOptions
			->find()
			->where(['assessment_grading_type_id' => $entity->id])
			->toArray();

		if (!empty($query)) {
			$gradingOptions = [];
			foreach ($query as $key => $gradingOption) {
				$gradingOptionId = $gradingOption->id;
				$gradingOptions[$gradingOptionId] = 0;
				if ($this->hasAssociatedRecords($AssessmentGradingOptions, $gradingOption, $extra)) {
					$gradingOptions[$gradingOptionId] = 1;
				}
			}

			// it will check if there are any in-used gradeOption, can't delete all the gradeOptions.
			$allowedDeleteAll = max($gradingOptions);

			$currentGradingOptionIds = (new Collection($entity->grading_options))->extract($this->GradingOptions->getPrimaryKey())->toArray();
			$originalGradingOptionIds = (new Collection($entity->getOriginal('grading_options')))->extract($this->GradingOptions->getPrimaryKey())->toArray();
			$tempRemovedGradingOptionIds = array_diff($originalGradingOptionIds, $currentGradingOptionIds);

			// get the array of gradeOption that will be deleted, if the gradeOption was in-used it will be excluded from this array.
			$removedGradingOptionIds = [];
			foreach ($tempRemovedGradingOptionIds as $key => $value) {
				if (!$gradingOptions[$value]) {
					$removedGradingOptionIds[$key] = $value;
				}
			}

			// remove the gradeOption inside the removed gradeOptions array.
			// remove all the gradeOptions if no in-use gradeOption.
			if (!empty($removedGradingOptionIds)) {
				$this->GradingOptions->deleteAll([
					$this->GradingOptions->aliasField($this->GradingOptions->getPrimaryKey()) . ' IN ' => $removedGradingOptionIds
				]);
			} else if ((!array_key_exists('grading_options', $requestData['AssessmentGradingTypes'])) && (!$allowedDeleteAll)){
				$this->GradingOptions->deleteAll([
					$this->GradingOptions->aliasField('assessment_grading_type_id') => $entity->id
				]);
			}
		}
	}

/******************************************************************************************************************
**
** view action events
**
******************************************************************************************************************/
	public function viewBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields('view'));

		$this->setFieldOrder([
			'code', 'name', 'pass_mark', 'max', 'result_type', 'grading_options', 'visible',
		]);
	}


/******************************************************************************************************************
**
** viewEdit action events
**
******************************************************************************************************************/
	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			$this->GradingOptions->getAlias()
		]);
	}

/******************************************************************************************************************
**
** delete action events
**
******************************************************************************************************************/
	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->GradingOptions->getAlias()
        ];
    }


/******************************************************************************************************************
**
** viewEdit action events
**
******************************************************************************************************************/
	public function getCustomList($params = []) {
		if (isset($params['keyField'])) {
			$keyField = $params['keyField'];
		} else {
			$keyField = 'id';
		}
		if (isset($params['valueField'])) {
			$valueField = $params['valueField'];
		} else {
			$valueField = 'name';
		}
 		$query = $this->find('list', ['keyField' => $keyField, 'valueField' => $valueField]);
		return $this->getList($query);
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'outcome_template_id') {
            return __('Outcome Template');
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
        }elseif ($field == 'pass_mark') {
            return __('Pass Mark');
        }elseif ($field == 'max') {
            return __('Max');
        }elseif ($field == 'result_type') {
            return __('Result Type');
        }elseif ($field == 'grading_options') {
            return __('Grading Options');
        }elseif ($field == 'visible') {
            return __('visible');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8554
	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
	{
	    $checkRecord = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
	    $data = $checkRecord->find()
	        ->where(['assessment_grading_type_id' => $entity->id])
	        ->toArray();

	    $associatedRecordsExist = 
	        $this->GradingOptions->exists(['assessment_grading_type_id' => $entity->id]);

	    if ($associatedRecordsExist && !empty($data)) {
	        $message = __('Delete operation is not allowed as there are other information linked to this record.');
	        $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
	        
	        // Redirect to the referring URL
	        $url = $this->request->referer();
	        $event->stopPropagation();
	        return $this->controller->redirect($url);
	    }

	}
}