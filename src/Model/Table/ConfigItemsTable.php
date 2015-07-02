<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

class ConfigItemsTable extends AppTable {
	private $configurations = [];

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function value($code) {
		$value = '';
		if (array_key_exists($code, $this->configurations)) {
			$value = $this->configurations[$code];
		} else {
			$entity = $this->findByCode($code)->first();
			$value = strlen($entity->value) ? $entity->value : $entity->default_value;
			$this->configurations[$code] = $value;
		}
		return $value;
	}

	public function defaultValue($code) {
		$value = '';
		if (array_key_exists($code, $this->configurations)) {
			$value = $this->configurations[$code];
		} else {
			$entity = $this->findByCode($code)->first();
			$value = $entity->default;
			$this->configurations[$code] = $value;
		}
		return $value;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('default_value', ['visible' => false]);
		$this->ControllerAction->field('visible', ['visible' => false]);
		$this->ControllerAction->field('editable', ['visible' => false]);
		$this->ControllerAction->field('field_type', ['visible' => false]);
		$this->ControllerAction->field('option_type', ['visible' => false]);
		$this->ControllerAction->field('type', ['visible' => false]);
		$this->ControllerAction->field('code', ['visible' => false]);
	}

	public function indexBeforeAction(Event $event) {
		$toolbarElements = [
			['name' => 'Configurations/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$typeOptions = array_keys($this->find('list', ['keyField' => 'type', 'valueField' => 'type'])->toArray());

		$selectedType = $this->queryString('type', $typeOptions);
		$this->advancedSelectOptions($typeOptions, $selectedType);
		$this->request->query['type_value'] = $typeOptions[$selectedType]['text'];

		$this->controller->set('typeOptions', $typeOptions);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$type = $request->query['type_value'];
		$options['finder'] = ['visible' => []];
		$options['conditions'][$this->aliasField('type')] = $type;
		return $options;
	}
}
