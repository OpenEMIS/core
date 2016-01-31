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

class GoogleAuthenticationBehavior extends AuthenticationBehavior {

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
