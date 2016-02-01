<?php 
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;

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

	protected function processAuthentication(&$attribute, $authenticationType) {
		$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
		$attributesArray = $AuthenticationTypeAttributesTable->find()->where([$AuthenticationTypeAttributesTable->aliasField('authentication_type') => $authenticationType])->toArray();
		$attributeFieldsArray = $this->_table->array_column($attributesArray, 'attribute_field');
		foreach ($attribute as $key => $values) {
			$attributeValue = '';
			if (array_search($key, $attributeFieldsArray) !== false) {
				$attributeValue = $attributesArray[array_search($key, $attributeFieldsArray)]['value'];
			}
			if (method_exists($this, strtolower($authenticationType).'ModifyValue')) {
				$method = strtolower($authenticationType).'ModifyValue';
				$result = $this->$method($key, $attributeValue);
				if ($result !== false) {
					$attributeValue = $result;
				}
			}
			$attribute[$key]['value'] = $attributeValue;
		}
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
		if ($data[$this->alias]['value'] != 'Local' && $data[$this->alias]['type'] == 'Authentication') {
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

	public function saml2Authentication(&$attribute) {
		$attribute['idp_entity_id'] = ['label' => 'Identity Provider - Entity ID', 'type' => 'text'];
		$attribute['idp_sso'] = ['label' => 'Identity Provider - Single Signon Service', 'type' => 'text'];
		$attribute['idp_slo'] = ['label' => 'Identity Provider - Single Logout Service', 'type' => 'text'];
		$attribute['idp_x509cert'] = ['label' => 'Identity Provider - X509 Certificate', 'type' => 'textarea', 'maxlength' => 1500];
		$attribute['sp_entity_id'] = ['label' => 'Service Provider - Entity ID', 'type' => 'text', 'readonly' => true];
		$attribute['sp_acs'] = ['label' => 'Service Provider - Assertion Consumer Service', 'type' => 'text', 'readonly' => true];
		$attribute['sp_slo'] = ['label' => 'Service Provider - Single Logout Service', 'type' => 'text', 'readonly' => true];
		$attribute['sp_name_id_format'] = ['label' => 'Service Provider - Name ID Format', 'type' => 'text'];
		$attribute['saml_username_mapping'] = ['label' => 'Username Mapping', 'type' => 'text'];
		$attribute['saml_first_name_mapping'] = ['label' => 'First Name Mapping', 'type' => 'text'];
		$attribute['saml_middle_name_mapping'] = ['label' => 'Middle Name Mapping', 'type' => 'text'];
		$attribute['saml_third_name_mapping'] = ['label' => 'Third Name Mapping', 'type' => 'text'];
		$attribute['saml_last_name_mapping'] = ['label' => 'Last Name Mapping', 'type' => 'text'];
		$attribute['saml_gender_mapping'] = ['label' => 'Gender', 'type' => 'text'];
		$attribute['saml_date_of_birth_mapping'] = ['label' => 'Date of birth mapping', 'type' => 'text'];
	}

	public function saml2ModifyValue($key, $attributeValue) {
		if ($key == 'sp_entity_id') {
			return Router::url(['plugin' => null, 'controller' => null, 'action' => 'index'], true);
		} else if ($key == 'sp_slo') {
			return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'logout'],true);
		} else if ($key == 'sp_acs') {
			return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
		}
		return false;
	}

	public function googleAuthentication(&$attribute) {
		$attribute['client_id'] = ['label' => 'Client ID', 'type' => 'text'];
		$attribute['client_secret'] = ['label' => 'Client Secret', 'type' => 'text'];
		$attribute['redirect_uri'] = ['label' => 'Redirect URI', 'type' => 'text', 'readonly' => true];
		$attribute['hd'] = ['label' => 'Hosted Domain', 'type' => 'text'];
	}

	public function googleModifyValue($key, $attributeValue) {
		if ($key == 'redirect_uri' && empty($attributeValue)) {
			return Router::url(['plugin' => null, 'controller' => 'Users', 'action' => 'postLogin'],true);
		}
		return false;
	}

	public function onGetAuthenticationTypeElement(Event $event, $action, $entity, $attr, $options=[]) {
		switch ($action){
			case "view":			
				$authenticationType = $this->_table->request->data[$this->alias]['value'];
				$attribute = [];
				$methodName = strtolower($authenticationType).'Authentication';
				if (method_exists($this, $methodName)) {
					$this->$methodName($attribute);
					$this->processAuthentication($attribute, $authenticationType);
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
				$authenticationType = $this->_table->request->data[$this->alias]['value'];
				$attribute = [];
				$methodName = strtolower($authenticationType).'Authentication';
				if (method_exists($this, $methodName)) {
					$this->$methodName($attribute);
					$this->processAuthentication($attribute, $authenticationType);
				}

				$attr = $attribute;
				break;
		}
		return $event->subject()->renderElement('Configurations/authentication', ['attr' => $attr]);
	}
}
