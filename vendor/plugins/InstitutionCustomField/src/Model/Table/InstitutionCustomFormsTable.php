<?php
namespace InstitutionCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionCustomFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		$config['extra'] = [
			'fieldClass' => [
				'className' => 'InstitutionCustomField.InstitutionCustomFields',
				'joinTable' => 'institution_custom_forms_fields',
				'foreignKey' => 'institution_custom_form_id',
				'targetForeignKey' => 'institution_custom_field_id',
				'through' => 'InstitutionCustomField.InstitutionCustomFormsFields',
				'dependent' => true
			],
			'filterClass' => [
				'className' => 'Institution.Types',
				'joinTable' => 'institution_custom_forms_filters',
				'foreignKey' => 'institution_custom_form_id',
				'targetForeignKey' => 'institution_custom_filter_id',
				'through' => 'InstitutionCustomField.InstitutionCustomFormsFilters',
				'dependent' => true
			]
		];
		parent::initialize($config);
	}

	public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request) {
		$module = $this->CustomModules
			->find()
			->where([$this->CustomModules->aliasField('code') => 'Institution'])
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
		return $query->where([$this->CustomModules->aliasField('code') => 'Institution']);
	}
}
