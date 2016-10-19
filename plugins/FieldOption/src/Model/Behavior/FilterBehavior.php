<?php
namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\Utility\Inflector;
use Cake\ORM\Query;

class FilterBehavior extends DisplayBehavior {
	use UtilityTrait;

	private $parentFieldOptionInfo;
	private $parentFieldOptions;
	private $fieldOptionName;
	private $parentFieldOptionList;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
		$events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
		$events['ControllerAction.Model.delete.beforeAction'] = 'deleteBeforeAction';
		$events['ControllerAction.Model.delete.onInitialize'] = 'deleteOnInitialize';
		return $events;
	}

	public function initialize(array $config) {
		parent::initialize($config);
		$this->fieldOptionName = $config['fieldOptionName'];
		$this->parentFieldOptionList = $config['parentFieldOptionList'];

		$parentFieldOptionInfo = $this->parentFieldOptionList[$this->fieldOptionName];
		$this->parentFieldOptionInfo = $parentFieldOptionInfo;

		if(!empty($parentFieldOptionInfo['parentModel']) && !empty($parentFieldOptionInfo['foreignKey'])) {
			$parentFieldOptionTable = TableRegistry::get($parentFieldOptionInfo['parentModel']);
			$parentFieldOptions = $parentFieldOptionTable->find('list')->where([$parentFieldOptionTable->aliasField('visible') => 1])->toArray();
			$this->parentFieldOptions = $parentFieldOptions;
		}
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		parent::indexBeforeAction($event, $settings);
        $query = $settings['query'];

		$this->_table->controller->set('parentFieldOptions', $this->parentFieldOptions);
		$selectedParentFieldOption = $this->_table->queryString('parent_field_option_id', $this->parentFieldOptions);

		$this->_table->controller->set('selectedParentFieldOption', $selectedParentFieldOption);
		$this->_table->controller->set('foreignKey', $this->parentFieldOptionInfo['foreignKey']);

		$parentFieldOptionValue = $this->parentFieldOptions[$selectedParentFieldOption];
		$foreignKey =  $this->parentFieldOptionInfo['foreignKey'];

		$this->_table->ControllerAction->field('parent_field_option_id', [
			'type' => 'readonly',
			'options' => $this->parentFieldOptions,
			'visible' => ['index' => false, 'view' => false, 'edit' => false]
		]);

		$table = TableRegistry::get($this->fieldOptionName);
		$query = $table->find();

		$selectedParentFieldOption = $this->_table->ControllerAction->getVar('selectedParentFieldOption');
		$foreignKey = $this->_table->ControllerAction->getVar('foreignKey');
		if (!empty($selectedParentFieldOption) && !empty($foreignKey)) {
			$query->where([$foreignKey => $selectedParentFieldOption]);
		}

		$this->displayParentFields($table);
		return $query;
	}

	public function viewBeforeAction(Event $event) {
		parent::viewBeforeAction($event);

		$this->_table->fields['field_option_id']['value'] = $this->_table->fields['field_option_id']['attr']['value'];
		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayParentFields($table);
		return $table;
	}

	public function addEditBeforeAction(Event $event) {
		parent::addEditBeforeAction($event);

		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayParentFields($table);
		return $table;
	}

	public function displayParentFields($table) {
		$selectedParentFieldOption = $this->_table->queryString('parent_field_option_id', $this->parentFieldOptions);
		$selectedParentFieldOptionValue = $this->parentFieldOptions[$selectedParentFieldOption];

		if (!empty($this->parentFieldOptionInfo['foreignKey'])) {
			$model = $this->_table->ControllerAction->getModel($this->parentFieldOptionInfo['parentModel']);

			/**
			 * assign $table's associations to $this->_table, which is the FieldOptionValues
			 */
			$this->_table->belongsTo($model['model'], ['className' => $this->parentFieldOptionInfo['parentModel'], 'foreignKey' => $this->parentFieldOptionInfo['foreignKey']]);
			/**
			 * end assignment
			 */

			$this->_table->ControllerAction->field($this->parentFieldOptionInfo['foreignKey'], [
														'type' => 'readonly',
														'visible' => ['index' => false, 'add' => true, 'edit' => true, 'view' => true],
														'model' => $table->alias(),
														'value' => $selectedParentFieldOption,
														'className' => $this->fieldOptionName,
														'attr' => ['value' => $selectedParentFieldOptionValue],
														'order' => array_search('parent_field_option_id', $this->defaultFieldOrder),
													]);
		}

	}

	public function deleteBeforeAction(Event $event, ArrayObject $settings) {
		$settings['deleteStrategy'] = 'transfer';
		$settings['model'] = $this->fieldOptionName;
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
		$table = TableRegistry::get($this->fieldOptionName);
		$foreignKey = $this->parentFieldOptionList[$this->fieldOptionName]['foreignKey'];

		if(!empty($foreignKey)) {

			$selectedParentFieldOption = $this->_table->queryString('parent_field_option_id', $this->parentFieldOptions);
			$availFieldOptions = $table->find()->where([$foreignKey => $selectedParentFieldOption])->count();
			$query->where([$foreignKey => $selectedParentFieldOption]);

		} else {
			$availFieldOptions = $table->find()->count();
		}

		if($availFieldOptions == 1) {
			$this->_table->Alert->warning('general.notTransferrable');
			$event->stopPropagation();
			return $this->_table->controller->redirect($this->_table->ControllerAction->url('index'));
		}

		return $query;
	}
}
