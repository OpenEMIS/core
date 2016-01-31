<?php 
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use App\Model\Behavior\AuthenticationBehavior;

class Saml2AuthenticationBehavior extends AuthenticationBehavior {

	private $alias;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->alias = $this->_table->alias();
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.beforeAction' => 'beforeAction',
		];
		$events = array_merge($events, $newEvent);
		return $events;
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
