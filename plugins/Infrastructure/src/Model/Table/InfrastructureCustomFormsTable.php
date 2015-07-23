<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
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
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomFields', [
			'className' => 'Infrastructure.InfrastructureCustomFields',
			'joinTable' => 'infrastructure_custom_forms_fields',
			'foreignKey' => 'infrastructure_custom_form_id',
			'targetForeignKey' => 'infrastructure_custom_field_id',
			'through' => 'Infrastructure.InfrastructureCustomFormsFields',
			'dependent' => true
		]);
	}

	public function _getSelectOptions() {
		list($moduleOptions, $selectedModule, $applyToAllOptions, $selectedApplyToAll) = array_values(parent::_getSelectOptions());
		$moduleOptions = $this->CustomModules
			->find('list')
			->where([
				$this->CustomModules->aliasField('code') => 'Infrastructure'
			])
			->toArray();
		$selectedModule = $this->queryString('module', $moduleOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
