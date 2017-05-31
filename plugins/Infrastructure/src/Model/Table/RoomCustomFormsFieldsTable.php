<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFormsFieldsTable;

class RoomCustomFormsFieldsTable extends CustomFormsFieldsTable {
    public function initialize(array $config) {
        $this->table('infrastructure_custom_forms_fields');
        parent::initialize($config);
        $this->belongsTo('CustomForms', ['className' => 'Infrastructure.RoomCustomForms', 'foreignKey' => 'infrastructure_custom_form_id']);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.RoomCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
    }
}
