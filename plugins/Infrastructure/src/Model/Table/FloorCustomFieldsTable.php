<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;

class FloorCustomFieldsTable extends CustomFieldsTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_custom_fields');
        $this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Institution.InstitutionFloors');
        parent::initialize($config);
        $this->hasMany('CustomFieldOptions', ['className' => 'Infrastructure.InfrastructureCustomFieldOptions', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CustomFieldValues', ['className' => 'Infrastructure.InfrastructureCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('CustomForms', [
            'className' => 'Infrastructure.InfrastructureCustomForms',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_field_id',
            'targetForeignKey' => 'infrastructure_custom_form_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);
    }
}
