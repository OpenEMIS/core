<?php
namespace Assessment\Model\Table;

use ArrayObject;
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

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->hasMany('GradingOptions', ['className' => 'Assessment.AssessmentGradingOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
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
				]
			])
			->add('max', 'ruleIsDecimal', [
			    'rule' => ['decimal', null],
			])
			;
		return $validator;
	}


/******************************************************************************************************************
**
** cakephp events
**
******************************************************************************************************************/
	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('result_type', ['type' => 'select', 'options' => $this->getSelectOptions($this->aliasField('result_type'))]);
		$this->field('max', ['attr' => ['min' => 0]]);
		$this->field('pass_mark', ['attr' => ['min' => 0]]);
		$this->field('grading_options', [
			'type' => 'element',
			'element' => 'Assessment.Gradings/grading_options',
			'visible' => ['view'=>true, 'edit'=>true],
			'fields' => $this->GradingOptions->fields,
			'formFields' => []
		]);
	}


/******************************************************************************************************************
**
** addEdit action events
**
******************************************************************************************************************/
	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		if ($this->action=='edit') {
			$this->fields['visible']['visible'] = false;
		}
		$this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields());

		$this->setFieldOrder([
			'code', 'name', 'result_type', 'max', 'pass_mark', 'grading_options',
		]);
	}

	public function addEditOnReload(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {
		$groupOptionData = $this->GradingOptions->getFormFields();
		if (!empty($entity->id)) {
			$groupOptionData['assessment_grading_type_id'] = $entity->id;
		}
		$newGroupOption = $this->GradingOptions->newEntity($groupOptionData);
		$requestData[$this->alias()]['grading_options'][] = $newGroupOption->toArray();
		$newOptions = [$this->GradingOptions->alias() => ['validate'=>false]];
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
	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions) {

		$currentGradingOptionIds = (new Collection($entity->grading_options))->extract($this->GradingOptions->primaryKey())->toArray();
		$originalGradingOptionIds = (new Collection($entity->getOriginal('grading_options')))->extract($this->GradingOptions->primaryKey())->toArray();
		$removedGradingOptionIds = array_diff($originalGradingOptionIds, $currentGradingOptionIds);

		if (!empty($removedGradingOptionIds)) {
			$this->GradingOptions->deleteAll([
				$this->GradingOptions->aliasField($this->GradingOptions->primaryKey()) . ' IN ' => $removedGradingOptionIds
			]);
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
			$this->GradingOptions->alias()
		]);
	}


/******************************************************************************************************************
**
** viewEdit action events
**
******************************************************************************************************************/
	public function getCustomList($params = []) {
		if (array_key_exists('keyField', $params)) {
			$keyField = $params['keyField'];
		} else {
			$keyField = 'id';
		}
		if (array_key_exists('valueField', $params)) {
			$valueField = $params['valueField'];
		} else {
			$valueField = 'name';
		}
 		$query = $this->find('list', ['keyField' => $keyField, 'valueField' => $valueField]);
		return $this->getList($query);
	}

}
