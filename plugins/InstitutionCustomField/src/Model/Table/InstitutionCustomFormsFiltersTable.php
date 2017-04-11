<?php
namespace InstitutionCustomField\Model\Table;

use App\Model\Table\AppTable;

class InstitutionCustomFormsFiltersTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'InstitutionCustomField.InstitutionCustomForms', 'foreignKey' => 'institution_custom_form_id']);
		$this->belongsTo('CustomFilters', ['className' => 'Institution.Types', 'foreignKey' => 'institution_custom_filter_id']);
	}
}
