<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class InstitutionCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_fields');

		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'Institution.InstitutionCustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Institution.InstitutionCustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Institution.InstitutionCustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'Institution.InstitutionCustomForms',
			'joinTable' => 'institution_site_custom_form_fields',
			'foreignKey' => 'institution_site_custom_field_id',
			'targetForeignKey' => 'institution_site_custom_form_id',
			'through' => 'Institution.InstitutionCustomFormFields',
			'dependent' => true
		]);
	}
}
