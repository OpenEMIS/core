<?php
namespace StudentCustomField\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class StudentCustomFormsTable extends CustomFormsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFields', [
			'className' => 'StudentCustomField.StudentCustomFields',
			'joinTable' => 'student_custom_forms_fields',
			'foreignKey' => 'student_custom_form_id',
			'targetForeignKey' => 'student_custom_field_id',
			'through' => 'StudentCustomField.StudentCustomFormsFields',
			'dependent' => true
		]);
	}

	public function _getSelectOptions() {
		list($moduleOptions, $selectedModule, $applyToAllOptions, $selectedApplyToAll) = array_values(parent::_getSelectOptions());
		$moduleOptions = $this->CustomModules
			->find('list')
			->find('visible')
			->where([
				$this->CustomModules->aliasField('code') => 'Student'
			])
			->toArray();
		$selectedModule = $this->queryString('module', $moduleOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
