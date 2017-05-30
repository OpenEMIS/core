<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class FloorCustomFormsFieldsTable extends CustomFormsFieldsTable {
    public function initialize(array $config) {
        $this->table('infrastructure_custom_forms_fields');
        parent::initialize($config);
        $this->belongsTo('CustomForms', ['className' => 'Infrastructure.FloorCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.FloorCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
    }
}
