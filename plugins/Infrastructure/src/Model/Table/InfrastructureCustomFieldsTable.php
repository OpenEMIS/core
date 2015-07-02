<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

class InfrastructureCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->hasMany('CustomFieldOptions', ['className' => 'Infrastructure.InfrastructureCustomFieldOptions', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Infrastructure.InfrastructureCustomTableColumns', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Infrastructure.InfrastructureCustomTableRows', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Infrastructure.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function addEditBeforeAction(Event $event) {
		parent::addEditBeforeAction($event);
		//Setup fields
		$levelOptions = $this->Levels->find('list')->toArray();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		$this->fields['infrastructure_level_id']['type'] = 'hidden';
		$this->fields['infrastructure_level_id']['attr']['value'] = $selectedLevel;

		$LevelName = $this->Levels
			->find('all')
			->select([$this->Levels->aliasField('name')])
			->where([$this->Levels->aliasField('id') => $selectedLevel])
			->first();
		$this->ControllerAction->field('level_name', [
			'type' => 'readonly',
			'attr' => ['value' => $LevelName->name]
		]);

		array_unshift($this->_fieldOrder, "level_name");
		$this->setFieldOrder();
	}
}
