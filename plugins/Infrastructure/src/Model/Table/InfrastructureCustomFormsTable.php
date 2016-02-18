<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InfrastructureCustomFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		$config['custom_filter'] = [
			'className' => 'Infrastructure.InfrastructureLevels',
			'joinTable' => 'infrastructure_custom_forms_filters',
			'foreignKey' => 'infrastructure_custom_form_id',
			'targetForeignKey' => 'infrastructure_custom_filter_id',
			'through' => 'Infrastructure.InfrastructureCustomFormsFilters',
			'dependent' => true
		];
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFields', [
			'className' => 'Infrastructure.InfrastructureCustomFields',
			'joinTable' => 'infrastructure_custom_forms_fields',
			'foreignKey' => 'infrastructure_custom_form_id',
			'targetForeignKey' => 'infrastructure_custom_field_id',
			'through' => 'Infrastructure.InfrastructureCustomFormsFields',
			'dependent' => true
		]);
	}

	public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request) {
		$module = $this->CustomModules
			->find()
			->where([$this->CustomModules->aliasField('code') => 'Infrastructure'])
			->first();
		$selectedModule = $module->id;
		$request->query['module'] = $selectedModule;

		$attr['type'] = 'readonly';
		$attr['value'] = $selectedModule;
		$attr['attr']['value'] = $module->name;

		return $attr;
	}

	public function getModuleQuery() {
		$query = parent::getModuleQuery();
		return $query->where([$this->CustomModules->aliasField('code') => 'Infrastructure']);
	}
}
