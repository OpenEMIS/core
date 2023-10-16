<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class BuildingCustomFormsFieldsTable extends CustomFormsFieldsTable {
    public function initialize(array $config) {
        $this->table('infrastructure_custom_forms_fields');
        parent::initialize($config);
        $this->belongsTo('CustomForms', ['className' => 'Infrastructure.BuildingCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.BuildingCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
    }
}
