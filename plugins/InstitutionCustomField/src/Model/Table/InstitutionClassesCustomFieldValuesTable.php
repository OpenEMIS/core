<?php
namespace InstitutionCustomField\Model\Table;
//POCOR-8538 
use CustomField\Model\Table\CustomFieldValuesTable;

class InstitutionClassesCustomFieldValuesTable extends CustomFieldValuesTable {
	protected $extra = ['scope' => 'institution_custom_field_id'];

	public function initialize(array $config): void {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
		// $this->belongsTo('CustomRecords', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('CustomRecords', [
			'foreignKey' => 'institution_class_id',
			'className' =>  'Institution.InstitutionClasses'
		]);

    }
}