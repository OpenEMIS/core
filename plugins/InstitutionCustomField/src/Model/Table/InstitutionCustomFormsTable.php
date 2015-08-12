<?php
namespace InstitutionCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionCustomFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		$config['custom_filter'] = [
			'className' => 'FieldOption.InstitutionSiteTypes',
			'joinTable' => 'institution_custom_forms_filters',
			'foreignKey' => 'institution_custom_form_id',
			'targetForeignKey' => 'institution_custom_filter_id',
			'through' => 'InstitutionCustomField.InstitutionCustomFormsFilters',
			'dependent' => true
		];
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFields', [
			'className' => 'InstitutionCustomField.InstitutionCustomFields',
			'joinTable' => 'institution_custom_forms_fields',
			'foreignKey' => 'institution_custom_form_id',
			'targetForeignKey' => 'institution_custom_field_id',
			'through' => 'InstitutionCustomField.InstitutionCustomFormsFields',
			'dependent' => true
		]);
	}

	public function _getSelectOptions() {
		list($moduleOptions, $selectedModule, $applyToAllOptions, $selectedApplyToAll) = array_values(parent::_getSelectOptions());
		$moduleOptions = $this->CustomModules
			->find('list')
			->find('visible')
			->where([
				$this->CustomModules->aliasField('code') => 'Institution'
			])
			->toArray();
		$selectedModule = $this->queryString('module', $moduleOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
