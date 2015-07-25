<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InfrastructureTypesTable extends AppTable {
	private $_fieldOrder = ['infrastructure_level_id', 'name'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Infrastructure.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
        $this->controller->set(compact('levelOptions', 'selectedLevel'));

        if (empty($levelOptions)) {
        	$this->Alert->warning('InfrastructureTypes.noLevels');
        }

		$options['conditions'][] = [
        	$this->aliasField('infrastructure_level_id') => $selectedLevel
        ];
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
		$this->fields['infrastructure_level_id']['type'] = 'hidden';
		$this->fields['infrastructure_level_id']['attr']['value'] = $selectedLevel;

		$levelName = '';
		if (!empty($levelOptions)) {
			$levelName = $this->Levels->get($selectedLevel)->name;
		}

		$this->ControllerAction->field('level_name', [
			'type' => 'readonly',
			'attr' => ['value' => $levelName]
		]);

		array_unshift($this->_fieldOrder, "level_name");
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$levelId = $this->request->query('level');

		$levelOptions = $this->Levels->find('list')->toArray();
		$selectedLevel = !is_null($levelId) ? $levelId : key($levelOptions);

		return compact('levelOptions', 'selectedLevel');
	}
}
