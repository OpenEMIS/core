<?php 
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class ExternalDataSourceBehavior extends Behavior {

	private $alias;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->alias = $this->_table->alias();
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.beforeAction' => 'beforeAction',
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
			'ControllerAction.Model.edit.afterSave'	=> 'editAfterSave',
			'ControllerAction.Model.afterAction'	=> 'afterAction',
			'ControllerAction.Model.view.beforeAction'	=> 'viewBeforeAction',
			'ControllerAction.Model.index.beforeAction'	=> ['callable' => 'indexBeforeAction', 'priority' => 100],
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($this->_table->action == 'view') {
			$key = isset($this->_table->request->pass[0]) ? $this->_table->request->pass[0] : null;
			if (!empty($key)) {
				$configItem = $this->_table->get($key);
				if ($configItem->type == 'External Data Source' && $configItem->code == 'external_data_source_type') {
					if (isset($toolbarButtons['back'])) {
						unset($toolbarButtons['back']);
					}
				}
			}
		}
	}

	public function indexBeforeAction(Event $event) {
		if ($this->_table->request->query['type_value'] == 'External Data Source') {
			$urlParams = $this->_table->ControllerAction->url('view');
			$externalDataSourceId = $this->_table->find()
				->where([
					$this->_table->aliasField('type') => 'External Data Source', 
					$this->_table->aliasField('code') => 'external_data_source_type'])
				->first()
				->id;
			$urlParams[0] = $externalDataSourceId;
			if (isset($this->_table->request->pass[0]) && $this->request->pass[0] == $externalDataSourceId) {
			} else {
				$this->_table->controller->redirect($urlParams);
			}
		}
	}

	public function beforeAction(Event $event) {
		if ($this->_table->action == 'view' || $this->_table->action == 'edit') {
			$key = isset($this->_table->request->pass[0]) ? $this->_table->request->pass[0] : null;
			if (!empty($key)) {
				$configItem = $this->_table->get($key);
				if ($configItem->type == 'External Data Source' && $configItem->code == 'external_data_source_type') {
					if (isset($this->_table->request->data[$this->alias]['value']) && !empty($this->_table->request->data[$this->alias]['value'])) {
						$value = $this->_table->request->data[$this->alias]['value'];
					} else {
						$value = $configItem->value;
						$this->_table->request->data[$this->alias]['value'] = $value;
					}
					if ($value != 'None') {
						$this->_table->ControllerAction->field('custom_data_source', ['type' => 'external_data_source_type', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
					}
				}
			}
		}
	}

	public function afterAction(Event $event) {
		if ($this->_table->action == 'view' || $this->_table->action == 'edit') {
			$key = isset($this->_table->request->pass[0]) ? $this->_table->request->pass[0] : null;
			if (!empty($key)) {
				$configItem = $this->_table->get($key);
				if ($configItem->type == 'External Data Source' && $configItem->code == 'external_data_source_type') {
					$this->_table->ControllerAction->field('default_value', ['visible' => false]);
					$value = $this->_table->request->data[$this->alias]['value'];
					if ($value != 'None') {
						$this->_table->ControllerAction->setFieldOrder(['type', 'label', 'value', 'custom_data_source']);
					}
				}
			} else {
				if ($this->_table->action == 'view') {
					$urlParams = $this->_table->ControllerAction->url('index');
					$this->_table->controller->redirect($urlParams);
				}
			}
		}
	}

	public function viewBeforeAction(Event $event) {
		if (isset($this->_table->request->query['type_value']) && $this->_table->request->query['type_value'] == 'External Data Source') {
			$this->_table->buildSystemConfigFilters();
		}
	}

	protected function processAuthentication(&$attribute, $authenticationType) {
		$ExternalDataSourceAttributesTable = TableRegistry::get('ExternalDataSourceAttributes');
		$attributesArray = $ExternalDataSourceAttributesTable->find()->where([$ExternalDataSourceAttributesTable->aliasField('external_data_source_type') => $authenticationType])->toArray();
		$attributeFieldsArray = $this->_table->array_column($attributesArray, 'attribute_field');
		foreach ($attribute as $key => $values) {
			$attributeValue = '';
			if (array_search($key, $attributeFieldsArray) !== false) {
				$attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
			}
			if (method_exists($this, lcfirst(Inflector::camelize($authenticationType, ' ')).'ModifyValue')) {
				$method = lcfirst(Inflector::camelize($authenticationType, ' ')).'ModifyValue';
				$result = $this->$method($key, $attributeValue);
				if ($result !== false) {
					$attributeValue = $result;
				}
			}
			$attribute[$key]['value'] = $attributeValue;
		}
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$ExternalDataSourceAttributesTable = TableRegistry::get('ExternalDataSourceAttributes');
		if ($data[$this->alias]['value'] != 'None' && $data[$this->alias]['type'] == 'External Data Source') {
			$externalDataSourceType = $data[$this->alias]['value'];
			$ExternalDataSourceAttributesTable->deleteAll(
				['external_data_source_type' => $externalDataSourceType]
			);

			foreach ($data['ExternalDataSourceTypeAttributes'] as $key => $value) {
				$entityData = [
					'external_data_source_type' => $externalDataSourceType,
					'attribute_field' => $key,
					'attribute_name' => $value['name'],
					'value' => $value['value']
				];
				$entity = $ExternalDataSourceAttributesTable->newEntity($entityData);
				$ExternalDataSourceAttributesTable->save($entity);
			}

			if (method_exists($this, lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'AfterSave')) {
				$method = lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'AfterSave';
				$this->$method($data['ExternalDataSourceTypeAttributes']);
			}
		}
	}

	public function openemisIdentitiesExternalSource(&$attribute)
	{
		$attribute['authentication_uri'] = ['label' => 'Authentication URI', 'type' => 'text'];
		$attribute['refresh_token'] = ['label' => 'Refresh Token', 'type' => 'text'];
		$attribute['client_id'] = ['label' => 'Client ID', 'type' => 'text'];
		$attribute['client_secret'] = ['label' => 'Client Secret', 'type' => 'text'];
		// $attribute['redirect_uri'] = ['label' => 'Redirect URI', 'type' => 'text', 'readonly' => true];
		// $attribute['hd'] = ['label' => 'Hosted Domain', 'type' => 'text', 'required' => false];
		$attribute['user_record_uri'] = ['label' => 'User Record URI', 'type' => 'text'];
	}

	// public function openemisIdentitiesModifyValue($key, $attributeValue) {
	// 	if ($key == 'redirect_uri') {
	// 		return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
	// 	}
	// 	return false;
	// }

	public function onGetExternalDataSourceTypeElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "view":			
				$externalDataSourceType = $this->_table->request->data[$this->alias]['value'];
				$attribute = [];
				$methodName = lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'ExternalSource';
				if (method_exists($this, $methodName)) {
					$this->$methodName($attribute);
					$this->processAuthentication($attribute, $externalDataSourceType);
				}

				$tableHeaders = [__('Attribute Name'), __('Value')];
				$tableCells = [];
				foreach ($attribute as $value) {
					$row = [];
					$row[] = $value['label'];
					$row[] = $value['value'];
					$tableCells[] = $row;
				}
				$attr['tableHeaders'] = $tableHeaders;
		    	$attr['tableCells'] = $tableCells;
				break;

			case "edit":
				$externalDataSourceType = $this->_table->request->data[$this->alias]['value'];
				$attribute = [];
				$methodName = lcfirst(Inflector::camelize($externalDataSourceType, ' ')).'ExternalSource';
				if (method_exists($this, $methodName)) {
					$this->$methodName($attribute);
					$this->processAuthentication($attribute, $externalDataSourceType);
				}

				$attr = $attribute;
				break;
		}
		return $event->subject()->renderElement('Configurations/external_data_source', ['attr' => $attr]);
	}
}
