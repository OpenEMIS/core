<?php 
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;

class AuthenticationBehavior extends Behavior {

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
				if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
					if (isset($toolbarButtons['back'])) {
						unset($toolbarButtons['back']);
					}
				}
			}
		}
	}

	public function indexBeforeAction(Event $event) {
		if ($this->_table->request->query['type_value'] == 'Authentication') {
			$urlParams = $this->_table->ControllerAction->url('view');
			$authenticationTypeId = $this->_table->find()
				->where([
					$this->_table->aliasField('type') => 'Authentication', 
					$this->_table->aliasField('code') => 'authentication_type'])
				->first()
				->id;
			$urlParams[0] = $authenticationTypeId;
			if (isset($this->_table->request->pass[0]) && $this->request->pass[0] == $authenticationTypeId) {
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
				if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
					if (isset($this->_table->request->data[$this->alias]['value']) && !empty($this->_table->request->data[$this->alias]['value'])) {
						$value = $this->_table->request->data[$this->alias]['value'];
					} else {
						$value = $configItem->value;
						$this->_table->request->data[$this->alias]['value'] = $value;
					}
					if ($value != 'Local') {
						$this->_table->ControllerAction->field('custom_authentication', ['type' => 'authentication_type', 'valueClass' => 'table-full-width', 'visible' => [ 'edit' => true, 'view' => true ]]);
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
				if ($configItem->type == 'Authentication' && $configItem->code == 'authentication_type') {
					$this->_table->ControllerAction->field('default_value', ['visible' => false]);
					$value = $this->_table->request->data[$this->alias]['value'];
					if ($value != 'Local') {
						$this->_table->ControllerAction->setFieldOrder(['type', 'label', 'value', 'custom_authentication']);
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
		if (isset($this->_table->request->query['type_value']) && $this->_table->request->query['type_value'] == 'Authentication') {
			$this->_table->buildSystemConfigFilters();
		}
	}

	public function onGetAuthenticationTypeElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "index":
				// No implementation
				break;

			case "view":
				$tableHeaders = [__('Attribute Name'), __('Value')];
				$tableCells = [];

				$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
				$authenticationType = $this->_table->request->data[$this->alias]['value'];
				$attribute = [];

				switch ($authenticationType) {
					case 'Google':
						$attribute['client_id'] = ['name' => 'Client ID'];
						$attribute['client_secret'] = ['name' => 'Client Secret'];
						$attribute['redirect_uri'] = ['name' => 'Redirect URI'];
						$attribute['hd'] = ['name' => 'Hosted Domain'];
						break;
				}
				$attributesArray = $AuthenticationTypeAttributesTable->find()->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => $authenticationType])->toArray();
				$attributeFieldsArray = $this->_table->Navigation->array_column($attributesArray, 'attribute_field');
				foreach ($attribute as $key => $values) {
					$attributeValue = '';
					if (array_search($key, $attributeFieldsArray) !== false) {
						$attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
					}
					$attribute[$key]['value'] = $attributeValue;
				}

				foreach ($attribute as $key => $value) {
					$row = [];
					$row[] = $value['name'];
					$row[] = $value['value'];
					$tableCells[] = $row;
				}
				
				$attr['tableHeaders'] = $tableHeaders;
		    	$attr['tableCells'] = $tableCells;
				break;

			case "edit":
				$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
				$authenticationType = $this->_table->request->data[$this->alias]['value'];
				$attribute = [];

				switch ($authenticationType) {
					case 'Google':
						$attribute['client_id'] = ['name' => 'Client ID'];
						$attribute['client_secret'] = ['name' => 'Client Secret'];
						$attribute['redirect_uri'] = ['name' => 'Redirect URI'];
						$attribute['hd'] = ['name' => 'Hosted Domain'];
						break;
				}
				$attributesArray = $AuthenticationTypeAttributesTable->find()->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => $authenticationType])->toArray();
				$attributeFieldsArray = $this->_table->Navigation->array_column($attributesArray, 'attribute_field');
				foreach ($attribute as $key => $values) {
					$attributeValue = '';
					if (array_search($key, $attributeFieldsArray) !== false) {
						$attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
					}
					$attribute[$key]['value'] = $attributeValue;
				}
				$attr = $attribute;
				break;

		}
		return $event->subject()->renderElement('Configurations/authentication', ['attr' => $attr]);
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
		if ($data[$this->alias]['value'] != 'Local') {
			$authenticationType = $data[$this->alias]['value'];
			$AuthenticationTypeAttributesTable->deleteAll(
				['authentication_type' => $authenticationType]
			);

			foreach ($data['AuthenticationTypeAttributes'] as $key => $value) {
				$entityData = [
					'authentication_type' => $authenticationType,
					'attribute_field' => $key,
					'attribute_name' => $value['name'],
					'value' => $value['value']
				];
				$entity = $AuthenticationTypeAttributesTable->newEntity($entityData);
				$AuthenticationTypeAttributesTable->save($entity);
			}
		}
	}
}
